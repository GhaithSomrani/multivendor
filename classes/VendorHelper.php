<?php

/**
 * Vendor Helper Utility Class
 * Centralizes common functions used throughout the multi-vendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorHelper
{
    /**
     * Cache for database results
     * @var array
     */
    private static $cache = [];

    /**
     * Cache timeout in seconds
     * @var int
     */
    private static $cacheTimeout = 300; // 5 minutes

    /**
     * Get vendor by customer ID with caching
     *
     * @param int $id_customer Customer ID
     * @return array|false Vendor data or false if not found
     */
    public static function getVendorByCustomer($id_customer)
    {
        $cacheKey = 'vendor_by_customer_' . $id_customer;

        // Check if data is in cache
        if (self::getCachedData($cacheKey)) {
            return self::getCachedData($cacheKey);
        }

        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor');
        $query->where('id_customer = ' . (int)$id_customer);

        $result = Db::getInstance()->getRow($query);

        // Store in cache
        self::setCachedData($cacheKey, $result);

        return $result;
    }

    /**
     * Validate vendor authorization for order detail
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param int $id_supplier Supplier ID
     * @return bool True if vendor is authorized for this order detail
     */
    public static function validateVendorOrderDetailAccess($id_order_detail, $id_vendor, $id_supplier)
    {
        $cacheKey = "validate_vendor_order_{$id_order_detail}_{$id_vendor}_{$id_supplier}";

        if (self::getCachedData($cacheKey)) {
            return self::getCachedData($cacheKey);
        }

        // Check if order detail exists and belongs to vendor's supplier
        $query = new DbQuery();
        $query->select('p.id_supplier');
        $query->from('order_detail', 'od');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->where('od.id_order_detail = ' . (int)$id_order_detail);

        $result = Db::getInstance()->getValue($query);
        $isValid = ((int)$result === (int)$id_supplier);

        self::setCachedData($cacheKey, $isValid);

        return $isValid;
    }

    /**
     * Calculate commission rate for a vendor 
     * 
     * @param int $id_vendor Vendor ID
     * @return float Commission rate percentage
     */
    public static function getCommissionRate($id_vendor)
    {
        try {
            $vendorCommission = VendorCommission::getCommissionRate($id_vendor);
            return $vendorCommission;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error getting commission rate: ' . $e->getMessage(), 3, null, 'Vendor', $id_vendor);
        }
    }

    /**
     * Get order lines by status with efficient querying
     * 
     * @param int $id_vendor Vendor ID
     * @param string $status Status to filter by (optional)
     * @param int $limit Limit number of results (optional)
     * @param int $offset Offset for pagination (optional)
     * @return array Order lines
     */
    public static function getOrderLinesByStatusGrouped($id_vendor)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_reference, od.product_quantity,
                   o.reference as order_reference, o.date_add as order_date, o.id_order,
                   vod.commission_amount, vod.vendor_amount,
                   c.firstname, c.lastname,
                   a.address1, a.city, a.postcode,
                   COALESCE(olst.name, "' . pSQL($defaultStatusType->name) . '") as line_status,
                   COALESCE(olst.commission_action, "' . pSQL($defaultStatusType->commission_action) . '") as commission_action,
                   COALESCE(olst.color, "' . pSQL($defaultStatusType->color) . '") as status_color,
                   COALESCE(ols.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') as status_type_id');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('customer', 'c', 'c.id_customer = o.id_customer');
        $query->leftJoin('address', 'a', 'a.id_address = o.id_address_delivery');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        $orderLines = Db::getInstance()->executeS($query);

        $grouped = [
            'no_commission' => [],
            'pending' => [],
            'ready' => []
        ];

        foreach ($orderLines as $line) {
            if ($line['commission_action'] == 'cancel' || $line['commission_action'] == 'refund') {
                $grouped['no_commission'][] = $line;
            } elseif ($line['line_status'] == 'Processing' || $line['line_status'] == 'processing') {
                $grouped['pending'][] = $line;
            } elseif (
                $line['line_status'] == 'Shipped' || $line['line_status'] == 'shipped' ||
                $line['line_status'] == 'Ready' || $line['line_status'] == 'ready'
            ) {
                $grouped['ready'][] = $line;
            } else {
                if ($line['commission_action'] == 'add') {
                    $grouped['pending'][] = $line;
                } else if ($line['commission_action'] == 'none') {
                    continue;
                } else {
                    $grouped['pending'][] = $line;
                }
            }
        }

        return $grouped;
    }


    public static function getOrderLinesByStatus($id_vendor)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_reference, od.product_quantity,
               o.reference as order_reference, o.date_add as order_date, o.id_order,
               vod.commission_amount, vod.vendor_amount,
               c.firstname, c.lastname,
               a.address1, a.city, a.postcode,
               COALESCE(olst.name, "' . pSQL($defaultStatusType->name) . '") as line_status,
               COALESCE(olst.commission_action, "' . pSQL($defaultStatusType->commission_action) . '") as commission_action,
               COALESCE(olst.color, "' . pSQL($defaultStatusType->color) . '") as status_color,
               COALESCE(ols.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') as status_type_id');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('customer', 'c', 'c.id_customer = o.id_customer');
        $query->leftJoin('address', 'a', 'a.id_address = o.id_address_delivery');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        $orderLines = Db::getInstance()->executeS($query);
        return $orderLines;
    }

    /**
     * Get efficient dashboard statistics in a single query
     * 
     * @param int $id_vendor Vendor ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Dashboard statistics
     */
    public static function getDashboardStats($id_vendor, $start_date = null, $end_date = null)
    {
        $dateFilter = '';
        if ($start_date && $end_date) {
            $dateFilter = ' AND DATE(o.date_add) BETWEEN "' . pSQL($start_date) . '" AND "' . pSQL($end_date) . '"';
        }

        $query = '
            SELECT 
                COUNT(DISTINCT vod.id_order_detail) as order_quantity,
                IFNULL(SUM(vod.vendor_amount), 0) as total_ca, 
                COUNT(DISTINCT od.product_reference) as total_products_by_ref,
                (
                    SELECT COUNT(DISTINCT vod2.id_order_detail) 
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod2
                    LEFT JOIN ' . _DB_PREFIX_ . 'orders o2 ON o2.id_order = vod2.id_order
                    WHERE vod2.id_vendor = ' . (int)$id_vendor . '
                    AND DATE(o2.date_add) = CURDATE()
                ) as todays_orders
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON vod.id_order_detail = od.id_order_detail
            WHERE vod.id_vendor = ' . (int)$id_vendor . $dateFilter;

        return Db::getInstance()->getRow($query);
    }

    /**
     * Get monthly sales data with a single efficient query
     * 
     * @param int $id_vendor Vendor ID
     * @param int $year Year to get data for
     * @return array Monthly sales data
     */
    public static function getMonthlySales($id_vendor, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $cacheKey = "monthly_sales_{$id_vendor}_{$year}";

        if (self::getCachedData($cacheKey)) {
            return self::getCachedData($cacheKey);
        }

        $query = '
            SELECT 
                MONTH(o.date_add) as month_num,
                MONTHNAME(o.date_add) as month,
                IFNULL(SUM(vod.vendor_amount), 0) as sales
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND YEAR(o.date_add) = ' . (int)$year . '
            GROUP BY MONTH(o.date_add)
            ORDER BY MONTH(o.date_add)';

        $results = Db::getInstance()->executeS($query);

        // Fill in missing months with zero sales
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = date('F', mktime(0, 0, 0, $i, 1, $year));
            $monthlyData[] = [
                'month' => $month,
                'sales' => 0
            ];
        }

        foreach ($results as $row) {
            $index = (int)$row['month_num'] - 1;
            $monthlyData[$index]['sales'] = (float)$row['sales'];
        }

        self::setCachedData($cacheKey, $monthlyData);

        return $monthlyData;
    }

    /**
     * Get daily sales data for a date range efficiently
     * 
     * @param int $id_vendor Vendor ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Daily sales data
     */
    public static function getDailySales($id_vendor, $start_date, $end_date)
    {
        $cacheKey = "daily_sales_{$id_vendor}_{$start_date}_{$end_date}";

        if (self::getCachedData($cacheKey)) {
            return self::getCachedData($cacheKey);
        }

        $query = '
            SELECT 
                DATE(o.date_add) as date,
                DATE_FORMAT(o.date_add, "%b %d") as formatted_date,
                IFNULL(SUM(vod.vendor_amount), 0) as sales
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND DATE(o.date_add) BETWEEN "' . pSQL($start_date) . '" AND "' . pSQL($end_date) . '"
            GROUP BY DATE(o.date_add)
            ORDER BY DATE(o.date_add)';

        $results = Db::getInstance()->executeS($query);

        $dailySales = [];
        foreach ($results as $row) {
            $dailySales[] = [
                'date' => $row['formatted_date'],
                'fullDate' => $row['date'],
                'sales' => (float)$row['sales']
            ];
        }

        $startDateTime = new DateTime($start_date);
        $endDateTime = new DateTime($end_date);
        $interval = $startDateTime->diff($endDateTime);

        if ($interval->days <= 60) {
            $allDates = [];
            $current = clone $startDateTime;

            while ($current <= $endDateTime) {
                $dateStr = $current->format('Y-m-d');
                $dateFormatted = $current->format('M d');

                $allDates[$dateStr] = [
                    'date' => $dateFormatted,
                    'fullDate' => $dateStr,
                    'sales' => 0
                ];

                $current->modify('+1 day');
            }

            foreach ($dailySales as $sale) {
                if (isset($allDates[$sale['fullDate']])) {
                    $allDates[$sale['fullDate']]['sales'] = $sale['sales'];
                }
            }

            $dailySales = array_values($allDates);
        }

        self::setCachedData($cacheKey, $dailySales);

        return $dailySales;
    }

    /**
     * Get status statistics for vendor orders
     * 
     * @param int $id_vendor Vendor ID
     * @return array Status statistics
     */
    public static function getStatusBreakdown($id_vendor)
    {
        $cacheKey = "status_breakdown_{$id_vendor}";

        if (self::getCachedData($cacheKey)) {
            return self::getCachedData($cacheKey);
        }

        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();

        $query = '
        SELECT 
            lstype.name as status,
            lstype.color,
            lstype.position,
            lstype.is_vendor_allowed,
            (
                SELECT COUNT(*)
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols 
                    ON ols.id_order_detail = vod.id_order_detail 
                    AND ols.id_vendor = vod.id_vendor
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND (
                    ols.id_order_line_status_type = lstype.id_order_line_status_type 
                    OR 
                    (ols.id_order_line_status_type IS NULL AND lstype.id_order_line_status_type = ' . (int)$defaultStatusTypeId . ')
                )
            ) as count
        FROM ' . _DB_PREFIX_ . 'mv_order_line_status_type lstype
        WHERE lstype.active = 1
        ORDER BY lstype.position ASC';

        $results = Db::getInstance()->executeS($query);

        self::setCachedData($cacheKey, $results);

        return $results;
    }

    /**
     * Get the default order line status (first by position)
     * 
     * @return string Default status name
     */
    public static function getDefaultOrderStatus()
    {
        $cacheKey = "default_order_status";

        if (self::getCachedData($cacheKey)) {
            return self::getCachedData($cacheKey);
        }

        $defaultStatus = Db::getInstance()->getValue('
            SELECT name FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC 
        ');

        if (!$defaultStatus) {
            $defaultStatus = 'Pending';
        }

        self::setCachedData($cacheKey, $defaultStatus);

        return $defaultStatus;
    }

    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @return mixed Cached data or false if not found/expired
     */
    private static function getCachedData($key)
    {
        if (!isset(self::$cache[$key])) {
            return false;
        }

        $cacheItem = self::$cache[$key];

        // Check if cache has expired
        if ($cacheItem['expires'] < time()) {
            unset(self::$cache[$key]);
            return false;
        }

        return $cacheItem['data'];
    }

    /**
     * Set cached data
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $timeout Cache timeout in seconds (null for default)
     */
    private static function setCachedData($key, $data, $timeout = null)
    {
        if ($timeout === null) {
            $timeout = self::$cacheTimeout;
        }

        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + $timeout
        ];
    }

    /**
     * Create vendor order detail if it doesn't exist
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return bool Success
     */
    public static function createVendorOrderDetailIfNotExists($id_order_detail, $id_vendor)
    {
        $existing = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if ($existing) {
            return true;
        }

        $orderDetail = new OrderDetail($id_order_detail);

        if (!Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        $product = new Product($orderDetail->product_id);

        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        $commission_rate = self::getCommissionRate($id_vendor);
        $product_price = $orderDetail->unit_price_tax_incl;
        $quantity = $orderDetail->product_quantity;
        $total_price = $product_price * $quantity;
        $commission_amount = $total_price * ($commission_rate / 100);
        $vendor_amount = $total_price - $commission_amount;

        $vendorOrderDetail = new VendorOrderDetail();
        $vendorOrderDetail->id_order_detail = $id_order_detail;
        $vendorOrderDetail->id_vendor = $id_vendor;
        $vendorOrderDetail->id_order = $orderDetail->id_order;
        $vendorOrderDetail->commission_rate = $commission_rate;
        $vendorOrderDetail->commission_amount = $commission_amount;
        $vendorOrderDetail->vendor_amount = $vendor_amount;
        $vendorOrderDetail->date_add = date('Y-m-d H:i:s');

        return $vendorOrderDetail->save();
    }

    /**
     * Process get order line statuses for admin
     * 
     * @param int $id_order Order ID
     * @return array Result data with success flag, status data and available statuses
     */
    public static function getOrderLineStatusesForAdmin($id_order)
    {
        try {
            $statusData = [];
            $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();

            // Get vendor order details for this order
            $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

            if (!empty($vendorOrderDetails)) {
                foreach ($vendorOrderDetails as $detail) {
                    $id_order_detail = $detail['id_order_detail'];
                    $id_vendor = $detail['id_vendor'];
                    $vendor = new Vendor($id_vendor);

                    // Get the current status with proper fallback
                    $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

                    if ($lineStatus) {
                        $currentStatusTypeId = $lineStatus['id_order_line_status_type'];
                        $currentStatusName = $lineStatus['status_name'];
                    } else {
                        // Use default status if no status exists
                        $currentStatusTypeId = $defaultStatusTypeId;
                        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);
                        $currentStatusName = $defaultStatusType->name;
                    }

                    $statusData[$id_order_detail] = [
                        'id_vendor' => $id_vendor,
                        'vendor_name' => Validate::isLoadedObject($vendor) ? $vendor->shop_name : 'Unknown vendor',
                        'status' => $currentStatusName,
                        'status_type_id' => $currentStatusTypeId,
                        'status_date' => $lineStatus ? $lineStatus['date_upd'] : null,
                        'is_vendor_product' => true
                    ];
                }
            }

            // Get all order details for this order to handle non-vendor products
            $allOrderDetails = Db::getInstance()->executeS(
                '
            SELECT id_order_detail 
            FROM ' . _DB_PREFIX_ . 'order_detail 
            WHERE id_order = ' . (int)$id_order
            );

            // Process all order details to include non-vendor products
            foreach ($allOrderDetails as $orderDetail) {
                $id_order_detail = $orderDetail['id_order_detail'];

                // Skip if this order detail is already handled by a vendor
                if (isset($statusData[$id_order_detail])) {
                    continue;
                }

                // This is a non-vendor product, add it with appropriate indication
                $statusData[$id_order_detail] = [
                    'id_vendor' => 0,
                    'vendor_name' => null,
                    'status' => 'Not a vendor product',
                    'status_type_id' => null,
                    'status_date' => null,
                    'is_vendor_product' => false
                ];
            }

            // Get all available statuses that admin can set
            $availableStatuses = OrderLineStatusType::getAllActiveStatusTypes(false, true);

            return [
                'success' => true,
                'statusData' => $statusData,
                'availableStatuses' => $availableStatuses
            ];
        } catch (Exception $e) {
            error_log('Error in getOrderLineStatusesForAdmin: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update order line status as admin
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $new_status New status to set
     * @param int $employee_id Employee ID making the change
     * @return array Result with success flag
     */
    public static function updateOrderLineStatusAsAdmin($id_order_detail, $id_vendor, $id_status_type, $employee_id = 1)
    {
        try {
            // Validate status type
            $statusType = new OrderLineStatusType($id_status_type);
            if (!Validate::isLoadedObject($statusType)) {
                return ['success' => false, 'message' => 'Invalid status type ID: ' . $id_status_type];
            }

            // Check if admin is allowed to set this status
            if ($statusType->is_admin_allowed != 1) {
                return ['success' => false, 'message' => 'Admin not allowed to set this status'];
            }

            // Verify that the order detail exists and get the order ID
            $orderDetailInfo = Db::getInstance()->getRow(
                '
            SELECT od.id_order, od.product_id 
            FROM ' . _DB_PREFIX_ . 'order_detail od 
            WHERE od.id_order_detail = ' . (int)$id_order_detail
            );

            if (!$orderDetailInfo) {
                return ['success' => false, 'message' => 'Order detail not found'];
            }

            // Verify that this order detail belongs to the specified vendor
            $vendorOrderDetail = Db::getInstance()->getRow(
                '
            SELECT vod.* 
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod 
            WHERE vod.id_order_detail = ' . (int)$id_order_detail . ' 
            AND vod.id_vendor = ' . (int)$id_vendor
            );

            if (!$vendorOrderDetail) {
                return ['success' => false, 'message' => 'This order detail does not belong to the specified vendor'];
            }

            // Update the status
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $id_status_type, // Use status type ID
                $employee_id,
                null, // No comment
                true // is admin
            );

            if ($success) {
                return ['success' => true, 'message' => 'Status updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update status'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update vendor order line status
     * 
     * @param int $id_customer Customer ID
     * @param int $id_order_detail Order detail ID
     * @param string $new_status New status to set
     * @param string $comment Optional comment
     * @return array Result with success flag, status data and message
     */
    public static function updateVendorOrderLineStatus($id_customer, $id_order_detail, $id_status_type, $comment = '')
    {
        $vendor = self::getVendorByCustomer($id_customer);

        if (!$vendor) {
            return ['success' => false, 'message' => 'Not authorized'];
        }

        $id_vendor = $vendor['id_vendor'];
        $id_supplier = $vendor['id_supplier'];

        try {
            // Verify authorization
            $query = new DbQuery();
            $query->select('p.id_supplier, od.id_order');
            $query->from('order_detail', 'od');
            $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
            $query->where('od.id_order_detail = ' . (int)$id_order_detail);

            $result = Db::getInstance()->getRow($query);

            if (!$result || (int)$result['id_supplier'] !== (int)$id_supplier) {
                throw new Exception('Not authorized for this product');
            }

            // Update the status using status type ID
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $id_status_type,
                $id_customer,
                $comment,
                false // not admin
            );

            if (!$success) {
                throw new Exception('Failed to update status');
            }

            $statusData = self::getOrderLineStatusData($id_order_detail, $id_vendor);

            return [
                'success' => true,
                'statusData' => $statusData,
                'message' => 'Status updated successfully'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * Get status history for an order line
     * 
     * @param int $id_customer Customer ID requesting the history
     * @param int $id_order_detail Order detail ID
     * @return array Result with success flag and history data
     */
    public static function getOrderLineStatusHistory($id_customer, $id_order_detail)
    {
        // Check authorization
        $vendor = self::getVendorByCustomer($id_customer);

        if (!$vendor) {
            return ['success' => false, 'message' => 'Not authorized'];
        }

        try {
            $history = OrderLineStatusLog::getStatusHistory($id_order_detail);

            $formattedHistory = [];
            foreach ($history as $log) {
                $formattedHistory[] = [
                    'date' => date('Y-m-d H:i:s', strtotime($log['date_add'])),
                    'old_status' => $log['old_status'] ?: 'Initial',
                    'new_status' => $log['new_status'],
                    'comment' => $log['comment'],
                    'changed_by' => $log['changed_by_firstname'] . ' ' . $log['changed_by_lastname']
                ];
            }

            return [
                'success' => true,
                'history' => $formattedHistory
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get order line status data
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array Status data
     */
    public static function getOrderLineStatusData($id_order_detail, $id_vendor)
    {
        $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        return [
            'status' => $lineStatus ? $lineStatus['status_name'] : 'Pending',
            'last_update' => $lineStatus ? date('Y-m-d H:i:s', strtotime($lineStatus['date_upd'])) : null,
            'comment' => $lineStatus ? $lineStatus['comment'] : null
        ];
    }

    /**
     * Bulk update vendor order line statuses
     * 
     * @param int $id_customer Customer ID
     * @param array $order_detail_ids Array of order detail IDs
     * @param string $new_status New status to set
     * @param string $comment Optional comment
     * @return array Result with success counts and details
     */

    public static function bulkUpdateVendorOrderLineStatus($id_customer, $order_detail_ids, $id_status_type, $comment = 'Bulk status update')
    {
        $vendor = self::getVendorByCustomer($id_customer);

        if (!$vendor) {
            return ['success' => false, 'message' => 'Not authorized'];
        }

        $id_vendor = $vendor['id_vendor'];
        $id_supplier = $vendor['id_supplier'];

        if (empty($order_detail_ids) || !is_array($order_detail_ids) || empty($id_status_type)) {
            return ['success' => false, 'message' => 'Missing required parameters'];
        }

        $success_count = 0;
        $error_count = 0;
        $results = [];

        foreach ($order_detail_ids as $id_order_detail) {
            $query = new DbQuery();
            $query->select('p.id_supplier, od.id_order');
            $query->from('order_detail', 'od');
            $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
            $query->where('od.id_order_detail = ' . (int)$id_order_detail);

            $result = Db::getInstance()->getRow($query);

            if (!$result || (int)$result['id_supplier'] !== (int)$id_supplier) {
                $error_count++;
                $results[$id_order_detail] = false;
                continue;
            }

            $update_result = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $id_status_type,
                $id_customer,
                $comment,
                false
            );

            if ($update_result) {
                $success_count++;
                $results[$id_order_detail] = true;
            } else {
                $error_count++;
                $results[$id_order_detail] = false;
            }
        }

        return [
            'success' => $success_count > 0,
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'message' => sprintf(
                '%d orders updated successfully, %d failed',
                $success_count,
                $error_count
            )
        ];
    }
    /**
     * Export vendor orders to CSV
     * 
     * @param int $id_customer Customer ID
     * @param object $module Module instance for translation
     * @return array|bool Success status or array with error details
     */
    public static function exportVendorOrdersToCSV($id_customer, $module)
    {
        $vendor = self::getVendorByCustomer($id_customer);

        if (!$vendor) {
            return ['success' => false, 'message' => 'Not authorized'];
        }

        $id_vendor = $vendor['id_vendor'];

        $query = new DbQuery();
        $query->select('
            od.id_order_detail,
            od.product_name,
            od.product_reference,
            od.product_quantity,
            od.unit_price_tax_incl,
            od.total_price_tax_incl,
            o.reference as order_reference,
            o.date_add as order_date,
            o.id_order,
            vod.commission_amount,
            vod.vendor_amount,
            olst.name as line_status
        ');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        $orderLines = Db::getInstance()->executeS($query);

        if (!$orderLines || empty($orderLines)) {
            return ['success' => false, 'message' => 'No order data found'];
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=vendor_orders_export_' . date('Y-m-d') . '.csv');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            $module->l('Order Reference'),
            $module->l('Product Name'),
            $module->l('SKU'),
            $module->l('Quantity'),
            $module->l('Vendor Amount'),
            $module->l('Status'),
            $module->l('Order Date')
        ]);

        foreach ($orderLines as $line) {
            fputcsv($output, [
                $line['order_reference'],
                $line['product_name'],
                $line['product_reference'],
                $line['product_quantity'],
                $line['vendor_amount'],
                $line['line_status'],
                date('Y-m-d H:i:s', strtotime($line['order_date']))
            ]);
        }

        fclose($output);
        return true;
    }

    /**
     * Get the first status that has commission_action = 'add' and is vendor allowed
     * 
     * @return array Result with success flag and status details
     */
    public static function getAddCommissionStatus()
    {
        try {
            $query = new DbQuery();
            $query->select('*'); // Select all fields including id_order_line_status_type
            $query->from('mv_order_line_status_type');
            $query->where('commission_action = "add"');
            $query->where('is_vendor_allowed = 1');
            $query->where('active = 1');
            $query->orderBy('position ASC');

            $status = Db::getInstance()->getRow($query);

            if ($status) {
                return [
                    'success' => true,
                    'status' => $status // This now includes id_order_line_status_type
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No suitable status found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get transactions with commission action details
     * This method gets all vendor order details and determines their commission status
     * based on either existing mv_order_line_status or the default status from mv_order_line_status_type
     */
    public static function getTransactionsWithCommissionDetails($id_vendor, $limit = null, $offset = null)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_quantity, od.product_reference,
                   o.reference as order_reference, o.date_add as order_date,
                   vod.commission_amount, vod.vendor_amount, vod.id_order,
                   COALESCE(olst.name, "' . pSQL($defaultStatusType->name) . '") as line_status,
                   COALESCE(olst.commission_action, "' . pSQL($defaultStatusType->commission_action) . '") as commission_action,
                   COALESCE(olst.color, "' . pSQL($defaultStatusType->color) . '") as status_color');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        $query->where('(
        (olst.commission_action IN ("add", "refund")) 
        OR 
        (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" IN ("add", "refund"))
    )');

        $query->groupBy('od.id_order_detail');
        $query->orderBy('o.date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Count total transactions with commission actions
     */
    public static function countTotalTransactions($id_vendor)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = new DbQuery();
        $query->select('COUNT(DISTINCT od.id_order_detail)');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        $query->where('(
        (olst.commission_action IN ("add", "refund")) 
        OR 
        (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" IN ("add", "refund"))
    )');

        return (int)Db::getInstance()->getValue($query);
    }
    /**
     * Verify that the order detail belongs to the vendor
     */
    public static function verifyOrderDetailOwnership($id_order_detail, $id_customer)
    {

        $vendor = self::getVendorByCustomer($id_customer);

        $query = new DbQuery();
        $query->select('vod.id_vendor');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->where('vod.id_order_detail = ' . (int)$id_order_detail);
        $query->where('vod.id_vendor = ' . $vendor['id_vendor']);

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Get the list of the available customers that are not vendors
     */
    public static function getAvailableCustomer($query)
    {
        $customers = Db::getInstance()->executeS('
        SELECT c.id_customer, c.firstname, c.lastname, c.email
        FROM `' . _DB_PREFIX_ . 'customer` c
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON c.id_customer = v.id_customer
        WHERE (
            LOWER(c.firstname) LIKE LOWER("%' . pSQL($query) . '%") 
            OR LOWER(c.lastname) LIKE LOWER("%' . pSQL($query) . '%") 
            OR LOWER(c.email) LIKE LOWER("%' . pSQL($query) . '%")
        )
        AND v.id_customer IS NULL
        AND c.active = 1
        ORDER BY c.firstname, c.lastname
        LIMIT 20
    ');
        return $customers;
    }

    /**
     * Get the list of the available suppliers that are not vendors
     */
    public static function getAvailableSupplier($query)
    {
        $suppliers = Db::getInstance()->executeS('
        SELECT s.id_supplier, s.name
        FROM `' . _DB_PREFIX_ . 'supplier` s
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON s.id_supplier = v.id_supplier
        WHERE LOWER(s.name) LIKE LOWER("%' . pSQL($query) . '%")
        AND v.id_supplier IS NULL
        AND s.active = 1
        ORDER BY s.name
        LIMIT 20
    ');
        return $suppliers;
    }

    /**
     * Validate vendor access - checks both existence and active status
     */
    public static function validateVendorAccess($id_customer)
    {
        $vendor = self::getVendorByCustomer($id_customer);

        if (!$vendor) {
            return [
                'has_access' => false,
                'vendor' => null,
                'status' => 'not_vendor',
                'message' => 'Customer is not a vendor'
            ];
        }

        $status = (int)$vendor['status'];

        switch ($status) {
            case 1: // Active
                return [
                    'has_access' => true,
                    'vendor' => $vendor,
                    'status' => 'active',
                    'message' => 'Vendor is active'
                ];

            case 0: // Pending
                return [
                    'has_access' => false,
                    'vendor' => $vendor,
                    'status' => 'pending',
                    'message' => 'Vendor account is pending approval'
                ];

            case 2: // Rejected
                return [
                    'has_access' => false,
                    'vendor' => $vendor,
                    'status' => 'rejected',
                    'message' => 'Vendor account has been rejected'
                ];

            default:
                return [
                    'has_access' => false,
                    'vendor' => $vendor,
                    'status' => 'unknown',
                    'message' => 'Unknown vendor status'
                ];
        }
    }

    /**
     * Check if vendor is active
     */
    public static function isVendorActive($id_vendor)
    {
        $status = Db::getInstance()->getValue(
            'SELECT status FROM ' . _DB_PREFIX_ . 'mv_vendor WHERE id_vendor = ' . (int)$id_vendor
        );
        return ((int)$status === 1);
    }
}
