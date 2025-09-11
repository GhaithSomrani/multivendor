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
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor');
        $query->where('id_customer = ' . (int)$id_customer);
        $result = Db::getInstance()->getRow($query);
        return $result;
    }






    public static function getOrderLinesByStatus($id_vendor)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();
        $query = new DbQuery();
        $query->select('
        vod.id_order_detail, 
        vod.product_name, 
        vod.product_reference, 
        vod.product_mpn,
        vod.product_quantity,
        vod.product_price,
        vod.id_order,
        vod.commission_amount, 
        vod.vendor_amount,
        o.reference as order_reference, 
        o.date_add as order_date,
        c.firstname, 
        c.lastname,
        a.address1, 
        a.city, 
        a.postcode,
        COALESCE(olst.name, "' . pSQL($defaultStatusType->name) . '") as line_status,
        COALESCE(olst.commission_action, "' . pSQL($defaultStatusType->commission_action) . '") as commission_action,
        COALESCE(olst.color, "' . pSQL($defaultStatusType->color) . '") as status_color,
        COALESCE(ols.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') as status_type_id
    ');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('customer', 'c', 'c.id_customer = o.id_customer');
        $query->leftJoin('address', 'a', 'a.id_address = o.id_address_delivery');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->where('ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . ')');
        $query->orderBy('o.date_add DESC');

        return Db::getInstance()->executeS($query);
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
        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();

        $query = '
            SELECT 
                COUNT(DISTINCT vod.id_order_detail) as order_quantity,
                IFNULL(SUM(vod.vendor_amount), 0) as total_ca, 
                COUNT(DISTINCT vod.product_id) as total_products_by_ref,
                (
                    SELECT COUNT(DISTINCT vod2.id_order_detail) 
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod2
                    LEFT JOIN ' . _DB_PREFIX_ . 'orders o2 ON o2.id_order = vod2.id_order
                    WHERE vod2.id_vendor = ' . (int)$id_vendor . '
                    AND DATE(o2.date_add) = CURDATE()
                ) as todays_orders
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order 
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail 
            WHERE  ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . ') and vod.id_vendor = ' . (int)$id_vendor . $dateFilter;

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


        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();

        $query = '
            SELECT 
                MONTH(o.date_add) as month_num,
                MONTHNAME(o.date_add) as month,
                IFNULL(SUM(vod.vendor_amount), 0) as sales
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail 
            WHERE ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . ') AND vod.id_vendor = ' . (int)$id_vendor . '
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
        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();


        $query = '
            SELECT 
                DATE(o.date_add) as date,
                DATE_FORMAT(o.date_add, "%b %d") as formatted_date,
                IFNULL(SUM(vod.vendor_amount), 0) as sales
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail 
            WHERE ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . ') AND vod.id_vendor = ' . (int)$id_vendor . '
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

        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();
        $visibleStatusFilter = ' AND lstype.id_order_line_status_type NOT IN (' . $hiddenIdsString . ')';

        $query = '
        SELECT 
            lstype.id_order_line_status_type,
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
        WHERE lstype.active = 1' .
            $visibleStatusFilter . '
        ORDER BY lstype.position ASC';

        $results = Db::getInstance()->executeS($query);

        return $results;
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
                    $lineStatus = self::getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor);

                    if ($lineStatus) {
                        $currentStatusTypeId = $lineStatus['id_order_line_status_type'];
                        $currentStatusName = $lineStatus['status_name'];
                    } else {
                        // Use default status if no status exists
                        $currentStatusTypeId = $defaultStatusTypeId;
                        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);
                        $currentStatusName = $defaultStatusType->name;
                    }

                    // Get available statuses for this current status + include current status
                    $availableStatusIds = OrderLineStatusType::getAvailableStatusListBystatusId($currentStatusTypeId);

                    // Always include the current status first
                    $availableStatuses = [];
                    $currentStatusType = new OrderLineStatusType($currentStatusTypeId);
                    if (Validate::isLoadedObject($currentStatusType)) {
                        $availableStatuses[] = [
                            'id_order_line_status_type' => $currentStatusType->id,
                            'name' => $currentStatusType->name,
                            'color' => $currentStatusType->color
                        ];
                    }

                    // Add other available statuses
                    if (!empty($availableStatusIds)) {
                        foreach ($availableStatusIds as $statusId) {
                            $statusId = (int)trim($statusId);
                            if ($statusId > 0 && $statusId != $currentStatusTypeId) { // Don't duplicate current status
                                $statusType = new OrderLineStatusType($statusId);
                                if (
                                    Validate::isLoadedObject($statusType) &&
                                    $statusType->active == 1 &&
                                    $statusType->is_admin_allowed == 1
                                ) {

                                    $availableStatuses[] = [
                                        'id_order_line_status_type' => $statusType->id,
                                        'name' => $statusType->name,
                                        'color' => $statusType->color
                                    ];
                                }
                            }
                        }
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
                'SELECT id_order_detail 
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

            // Return available statuses (including current statuses)
            $availableStatuses = [];
            foreach ($statusData as $id_order_detail => $data) {
                if ($data['is_vendor_product'] && isset($data['status_type_id'])) {
                    $currentStatusId = $data['status_type_id'];

                    // Always include current status first
                    $currentStatusType = new OrderLineStatusType($currentStatusId);
                    if (Validate::isLoadedObject($currentStatusType)) {
                        $found = false;
                        foreach ($availableStatuses as $existing) {
                            if ($existing['id_order_line_status_type'] == $currentStatusType->id) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $availableStatuses[] = [
                                'id_order_line_status_type' => $currentStatusType->id,
                                'name' => $currentStatusType->name,
                                'color' => $currentStatusType->color
                            ];
                        }
                    }

                    // Add available transition statuses
                    $availableStatusIds = OrderLineStatusType::getAvailableStatusListBystatusId($currentStatusId);
                    foreach ($availableStatusIds as $statusId) {
                        $statusId = (int)trim($statusId);
                        if ($statusId > 0) {
                            $statusType = new OrderLineStatusType($statusId);
                            if (
                                Validate::isLoadedObject($statusType) &&
                                $statusType->active == 1 &&
                                $statusType->is_admin_allowed == 1
                            ) {

                                // Add to available statuses if not already there
                                $found = false;
                                foreach ($availableStatuses as $existing) {
                                    if ($existing['id_order_line_status_type'] == $statusType->id) {
                                        $found = true;
                                        break;
                                    }
                                }

                                if (!$found) {
                                    $availableStatuses[] = [
                                        'id_order_line_status_type' => $statusType->id,
                                        'name' => $statusType->name,
                                        'color' => $statusType->color
                                    ];
                                }
                            }
                        }
                    }
                }
            }

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

            if ($statusType->is_admin_allowed != 1) {
                return ['success' => false, 'message' => 'Admin not allowed to set this status'];
            }

            $orderDetailInfo = Db::getInstance()->getRow(
                '
            SELECT od.id_order, od.product_id 
            FROM ' . _DB_PREFIX_ . 'order_detail od 
            WHERE od.id_order_detail = ' . (int)$id_order_detail
            );

            if (!$orderDetailInfo) {
                return ['success' => false, 'message' => 'Order detail not found'];
            }

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

            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $id_status_type,
                $employee_id,
                null,
                true
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

    public static function validateVendorOrderDetailAccess($id_order_detail, $id_vendor)
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->where('vod.id_order_detail = ' . (int)$id_order_detail);
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $count = (int)Db::getInstance()->getValue($query);
        $isValid = ($count > 0);
        return $isValid;
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
        try {
            if (!self::validateVendorOrderDetailAccess($id_order_detail, $id_vendor)) {
                return ['success' => false, 'message' => 'This order detail does not belong to this specified vendor'];
            }
            if (!self::isChangeable($id_order_detail, $id_vendor)) {
                return ['success' => false, 'message' => 'This order line status is not changeable'];
            }
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $id_status_type,
                $id_customer,
                $comment,
                false
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
                    'old_status' => $log['old_status_name'] ?: 'Initial',
                    'new_status' => $log['new_status_name'],
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
        $lineStatus = self::getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor);

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

        if (empty($order_detail_ids) || !is_array($order_detail_ids) || empty($id_status_type)) {
            return ['success' => false, 'message' => 'Missing required parameters'];
        }

        $success_count = 0;
        $error_count = 0;
        $skipped_count = 0;
        $results = [];
        $skipped_details = [];

        foreach ($order_detail_ids as $id_order_detail) {
            if (!self::validateVendorOrderDetailAccess($id_order_detail, $id_vendor)) {
                $error_count++;
                $results[$id_order_detail] = 'Unauthorized access to order detail';
                continue;
            }

            if (!self::isChangeable($id_order_detail, $id_vendor)) {
                $skipped_count++;
                $results[$id_order_detail] = 'skipped';
                $skipped_details[] = $id_order_detail;
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
        $message_parts = [];
        if ($success_count > 0) {
            $message_parts[] = sprintf('%d orders updated successfully', $success_count);
        }
        if ($skipped_count > 0) {
            $message_parts[] = sprintf('%d orders skipped (status not changeable)', $skipped_count);
        }
        if ($error_count > 0) {
            $message_parts[] = sprintf('%d orders failed', $error_count);
        }
        return [
            'success' => $success_count > 0,
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'skipped_count' => $skipped_count,
            'skipped_details' => $skipped_details,
            'message' => implode(', ', $message_parts)
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
        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();
        $id_vendor = $vendor['id_vendor'];

        $query = new DbQuery();
        $query->select('
            vod.id_order_detail,
            vod.product_name,
            vod.product_reference as product_reference,
            vod.product_quantity,
            o.reference as order_reference,
            o.date_add as order_date,
            o.id_order,
            vod.commission_amount,
            vod.vendor_amount,
            olst.name as line_status,
            vp.reference as payment_reference,
            vp.date_add as payment_date,
            vp.status as payment_status
        ');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->leftJoin('mv_vendor_transaction', 'vt', 'vt.order_detail_id = vod.id_order_detail');
        $query->leftJoin('mv_vendor_payment', 'vp', 'vp.id_vendor_payment = vt.id_vendor_payment ');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->where('ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . ')');

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
            $module->l('Order Date'),
            $module->l('Payment Reference'),
            $module->l('Payment Status'),
            $module->l('Payment Date')

        ]);

        foreach ($orderLines as $line) {
            fputcsv($output, [
                $line['order_reference'],
                $line['product_name'],
                $line['product_reference'],
                $line['product_quantity'],
                $line['vendor_amount'],
                $line['line_status'],
                date('Y-m-d H:i:s', strtotime($line['order_date'])),
                $line['payment_reference'] ?: '-',
                $line['payment_status'] ?: '-',
                $line['payment_date'] ? date('Y-m-d H:i:s', strtotime($line['payment_date'])) : '-'
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
        $query->select('
        vod.id_order_detail, 
        vod.product_name, 
        vod.product_quantity, 
        vod.product_reference,
        vod.product_mpn,
        vod.id_order,
        vod.commission_amount, 
        vod.vendor_amount,
        o.reference as order_reference, 
        o.date_add as order_date,
        COALESCE(olst.name, "' . pSQL($defaultStatusType->name) . '") as line_status,
        COALESCE(olst.commission_action, "' . pSQL($defaultStatusType->commission_action) . '") as commission_action,
        COALESCE(olst.color, "' . pSQL($defaultStatusType->color) . '") as status_color
    ');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        $query->where('(
        (olst.commission_action IN ("add", "refund")) 
        OR 
        (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" IN ("add", "refund"))
    )');

        $query->groupBy('vod.id_order_detail');
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
     * Get order line status by order detail and vendor
     * This is the centralized function that should be used everywhere
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array|false Status data with type information
     */
    public static function getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor)
    {
        $query = new DbQuery();
        $query->select('ols.id_order_line_status, ols.id_order_detail, ols.id_vendor, ols.id_order_line_status_type, ols.comment, ols.date_add, ols.date_upd, olst.name as status_name, olst.color, olst.commission_action, olst.affects_commission');
        $query->from('mv_order_line_status', 'ols');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('ols.id_order_detail = ' . (int)$id_order_detail);
        $query->where('ols.id_vendor = ' . (int)$id_vendor);

        $result = Db::getInstance()->getRow($query);

        // Debug logging to see what we're getting
        error_log('getOrderLineStatusByOrderDetailAndVendor SQL: ' . $query->build());
        error_log('getOrderLineStatusByOrderDetailAndVendor result for order_detail ' . $id_order_detail . ', vendor ' . $id_vendor . ': ' . print_r($result, true));

        return $result;
    }

    /**
     * Get vendor order detail by order detail and vendor
     * This gets the vendor order detail information (with product data)
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array|false Vendor order detail data
     */
    public static function getVendorOrderDetailByOrderDetailAndVendor($id_order_detail, $id_vendor)
    {
        $query = new DbQuery();
        $query->select('vod.*');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->where('vod.id_order_detail = ' . (int)$id_order_detail);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Check if an order line status can be changed by a vendor
     * This function validates if the current status allows vendor modifications
     * 
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return bool True if vendor can change the status, false otherwise
     */
    public static function isChangeable($id_order_detail, $id_vendor)
    {
        try {
            $currentStatus = self::getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor);

            if ($currentStatus) {
                $currentStatusTypeId = $currentStatus['id_order_line_status_type'];
                $currentStatusType = new OrderLineStatusType($currentStatusTypeId);

                if (!Validate::isLoadedObject($currentStatusType)) {
                    error_log('Invalid current status type ID: ' . $currentStatusTypeId);
                    return false;
                }

                if ($currentStatusType->is_vendor_allowed != 1) {
                    error_log('Vendor not allowed to change from current status: ' . $currentStatusType->name);
                    return false;
                }

                return true;
            } else {
                $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
                $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

                if (!Validate::isLoadedObject($defaultStatusType)) {
                    error_log('Invalid default status type');
                    return false;
                }

                if ($defaultStatusType->is_vendor_allowed != 1) {
                    error_log('Vendor not allowed to change from default status: ' . $defaultStatusType->name);
                    return false;
                }

                return true;
            }
        } catch (Exception $e) {
            error_log('Error in isChangeable validation: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Get supplier address details by vendor ID from ps_address table
     *
     * @param int $id_vendor Vendor ID
     * @return array|false Supplier address data or false if not found
     */
    public static function getSupplierAddressByVendor($id_vendor)
    {
        $query = new DbQuery();
        $query->select('a.id_address, s.name, a.address1 as address, a.address2, a.postcode, a.city, a.phone, co.name as country');
        $query->from('mv_vendor', 'v');
        $query->leftJoin('supplier', 's', 's.id_supplier = v.id_supplier');
        $query->leftJoin('address', 'a', 'a.id_supplier = s.id_supplier');
        $query->leftJoin('country_lang', 'co', 'co.id_country = a.id_country');
        $query->where('v.id_vendor = ' . (int)$id_vendor);
        $query->where('a.deleted = 0');

        return Db::getInstance()->getRow($query);
    }

    public static function getProductPubliclink($id_product, $id_product_attribute = null)
    {
        $product = new Product($id_product, true, Context::getContext()->language->id);
        if (!$product->active) {
            return '';
        }

        $link = new Link();
        if ($id_product_attribute) {
            return $link->getProductLink($product, null, null, null, null, null, $id_product_attribute);
        } else {
            return $link->getProductLink($product);
        }
    }
}
