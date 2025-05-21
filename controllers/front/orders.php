<?php

/**
 * Vendor Orders controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class multivendorOrdersModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        parent::init();

        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            Tools::redirect('index.php?controller=my-account');
        }

        // Set vendor ID for later use
        $this->context->smarty->assign('id_vendor', $vendor['id_vendor']);

        // Also set supplier ID for later use
        $this->context->smarty->assign('id_supplier', $vendor['id_supplier']);
    }

    public function initContent()
    {
        parent::initContent();

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_supplier = $this->context->smarty->getTemplateVars('id_supplier');

        // Handle status update submission
        if (Tools::isSubmit('submitStatusUpdate')) {
            $this->processStatusUpdate();
        }
        $filter_status = Tools::getValue('status', 'all');

        // Get order summary data
        $orderSummary = $this->getOrderSummary($id_vendor, $id_supplier);

        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Get order lines specific to this vendor's supplier ID
        $orderLines = $this->getVendorOrderLines($id_vendor, $id_supplier, $per_page, $offset, $filter_status);
        $total_lines = $this->countVendorOrderLines($id_vendor, $id_supplier, $filter_status);
        $total_pages = ceil($total_lines / $per_page);

        // Get available line statuses (only ones that vendor can use)
        $vendorStatuses = [];
        $allStatuses = [];
        $status_colors = [];

        // Get only vendor-allowed statuses for dropdown
        $vendorStatusTypes = OrderLineStatusType::getAllActiveStatusTypes(true); // true = vendor only
        foreach ($vendorStatusTypes as $status) {
            $vendorStatuses[$status['name']] = $status['name'];
            $status_colors[$status['name']] = $status['color'];
        }

        // Get ALL statuses (including admin-only) for display
        $allStatusTypes = OrderLineStatusType::getAllActiveStatusTypes();
        foreach ($allStatusTypes as $status) {
            $allStatuses[$status['name']] = $status['name'];
            $status_colors[$status['name']] = $status['color'];
        }


        // Add CSS and JS files
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/orders.js');

        // Add JS definitions for AJAX
        Media::addJsDef([
            'ordersAjaxUrl' => $this->context->link->getModuleLink('multivendor', 'ajax'),
            'ordersAjaxToken' => Tools::getToken('multivendor')
        ]);

        // Assign data to template
        $this->context->smarty->assign([
            'order_lines' => $orderLines,
            'order_summary' => $orderSummary,
            'vendor_statuses' => $vendorStatuses,
            'all_statuses' => $allStatuses,
            'status_colors' => $status_colors,
            'pages_nb' => $total_pages,
            'current_page' => $page,
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_manage_orders_url' => $this->context->link->getModuleLink('multivendor', 'manageorders', []),

            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/orders.tpl');
    }

    /**
     * Get vendor order lines (details) based on supplier ID
     * 
     * @param int $id_vendor Vendor ID
     * @param int $id_supplier Supplier ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array List of order line items
     */
    protected function getVendorOrderLines($id_vendor, $id_supplier, $limit = 20, $offset = 0, $status = 'all')
    {
        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_reference, od.product_quantity, od.unit_price_tax_incl, od.total_price_tax_incl,
                  o.reference as order_reference, o.date_add as order_date, p.id_supplier, od.product_mpn,
                  vod.id_vendor, vod.commission_amount, vod.vendor_amount, vod.id_order, 
                  COALESCE(ols.status, "Pending") as line_status');
        $query->from('order_detail', 'od');
        $query->innerJoin('orders', 'o', 'o.id_order = od.id_order');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->leftJoin('vendor_order_detail', 'vod', 'vod.id_order_detail = od.id_order_detail AND vod.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = od.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        // Add status filter if not "all"
        if ($status !== 'all' && $status) {
            $query->where('LOWER(COALESCE(ols.status, "Pending")) = "' . pSQL(strtolower($status)) . '"');
        }

        $query->orderBy('od.id_order_detail DESC');
        $query->limit($limit, $offset);

        $results = Db::getInstance()->executeS($query);

        // Ensure status is set for each line
        foreach ($results as &$result) {
            if (empty($result['line_status'])) {
                $result['line_status'] = 'Pending';
            }
        }

        return $results;
    }

    /**
     * Count vendor order lines based on supplier ID
     * 
     * @param int $id_vendor Vendor ID
     * @param int $id_supplier Supplier ID
     * @return int Number of order lines
     */
    protected function countVendorOrderLines($id_vendor, $id_supplier, $status = 'all')
    {
        $query = new DbQuery();
        $query->select('COUNT(od.id_order_detail)');
        $query->from('order_detail', 'od');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->leftJoin('vendor_order_detail', 'vod', 'vod.id_order_detail = od.id_order_detail AND vod.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = od.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        // Add status filter if not "all"
        if ($status !== 'all' && $status) {
            $query->where('LOWER(COALESCE(ols.status, "Pending")) = "' . pSQL(strtolower($status)) . '"');
        }

        return (int)Db::getInstance()->getValue($query);
    }

    /**
     * Process order line status update
     */
    protected function processStatusUpdate()
    {
        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_supplier = $this->context->smarty->getTemplateVars('id_supplier');
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $new_status = Tools::getValue('status');
        $comment = Tools::getValue('comment');

        // Verify this order detail has a product from this vendor's supplier
        $query = new DbQuery();
        $query->select('p.id_supplier');
        $query->from('order_detail', 'od');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->where('od.id_order_detail = ' . (int)$id_order_detail);

        $result = Db::getInstance()->getValue($query);

        if ((int)$result !== (int)$id_supplier) {
            $this->errors[] = $this->module->l('You do not have permission to update this order line.');
            return;
        }

        // Check if vendor order detail exists, if not create it
        $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$vendorOrderDetail) {
            // Create a new vendor order detail record
            if (!$this->createVendorOrderDetail($id_order_detail, $id_vendor)) {
                $this->errors[] = $this->module->l('Failed to create vendor order detail.');
                return;
            }
        }

        // Update the status
        $success = OrderLineStatus::updateStatus(
            $id_order_detail,
            $id_vendor,
            $new_status,
            $this->context->customer->id,
            $comment,
            false // not admin
        );

        if ($success) {
            $this->success[] = $this->module->l('Order line status updated successfully.');
        } else {
            $this->errors[] = $this->module->l('Failed to update order line status.');
        }
    }

    /**
     * Create vendor order detail if it doesn't exist
     * This handles cases where order was placed before the vendor was associated
     */
    protected function createVendorOrderDetail($id_order_detail, $id_vendor)
    {
        // Get order detail info
        $orderDetail = new OrderDetail($id_order_detail);

        if (!Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        // Get default commission rate
        $commission_rate = VendorHelper::getCommissionRate($id_vendor, $orderDetail->product_id);
        $product_price = $orderDetail->unit_price_tax_incl;
        $quantity = $orderDetail->product_quantity;
        $total_price = $product_price * $quantity;
        $commission_amount = $total_price * ($commission_rate / 100);
        $vendor_amount = $total_price - $commission_amount;

        // Create vendor order detail record
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
     * Get order summary data
     */
    protected function getOrderSummary($id_vendor)
    {
        // Total order lines
        $totalLines = Db::getInstance()->getValue(
            '
        SELECT COUNT(DISTINCT vod.id_order_detail)
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        WHERE vod.id_vendor = ' . (int)$id_vendor
        );

        // Total revenue - Only count when commission_action = "add"
        $totalRevenue = Db::getInstance()->getValue('
        SELECT SUM(vod.vendor_amount)
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
        LEFT JOIN ' . _DB_PREFIX_ . 'order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        LEFT JOIN ' . _DB_PREFIX_ . 'order_line_status_type olst ON olst.name = ols.status
        WHERE vod.id_vendor = ' . (int)$id_vendor . '
        AND DATE(o.date_add) >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
        AND olst.commission_action = "add"
    ');

        $statusBreakdown = VendorHelper::getStatusBreakdown($id_vendor);

        // Today's orders
        $todaysOrders = Db::getInstance()->getValue('
        SELECT COUNT(DISTINCT vod.id_order_detail)
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
        WHERE vod.id_vendor = ' . (int)$id_vendor . '
        AND DATE(o.date_add) = CURDATE()
    ');

        return [
            'total_lines' => (int)$totalLines,
            'total_revenue' => (float)$totalRevenue ?: 0.00,
            'todays_orders' => (int)$todaysOrders,
            'status_breakdown' => $statusBreakdown
        ];
    }
    /**
     * Get order line statuses for admin
     */
    public function processGetOrderLineStatusesForAdmin()
    {
        try {


            $id_order = (int)Tools::getValue('id_order');
            $statusData = [];

            $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

            if (!empty($vendorOrderDetails)) {
                foreach ($vendorOrderDetails as $detail) {
                    $id_order_detail = $detail['id_order_detail'];
                    $id_vendor = $detail['id_vendor'];
                    $vendor = new Vendor($id_vendor);
                    $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

                    $statusData[$id_order_detail] = [
                        'id_vendor' => $id_vendor,
                        'vendor_name' => Validate::isLoadedObject($vendor) ? $vendor->shop_name : 'Unknown vendor',
                        'status' => $lineStatus ? $lineStatus['status'] : 'Pending',
                        'status_date' => $lineStatus ? $lineStatus['date_upd'] : null,
                        'is_vendor_product' => true
                    ];
                }
            }

            // Get all order details for this order to handle non-vendor products
            $allOrderDetails = OrderDetail::getList($id_order);

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
                    'status_date' => null,
                    'is_vendor_product' => false
                ];
            }

            // Get all available statuses that admin can set
            $availableStatuses = OrderLineStatusType::getAllActiveStatusTypes(false, true);

            die(json_encode([
                'success' => true,
                'statusData' => $statusData,
                'availableStatuses' => $availableStatuses
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Update order line status
     */
    public function processUpdateOrderLineStatus()
    {
        try {
            // Use token-based security check instead of employee context
            // $token = Tools::getValue('token');

            // if (empty($token) || $token !== Tools::getAdminToken('AdminOrders')) {
            //     die(json_encode(['success' => false, 'message' => 'Access denied: Invalid token']));
            // }

            // Get parameters
            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_vendor = (int)Tools::getValue('id_vendor');
            $new_status = Tools::getValue('status');

            // We need a way to identify which employee is making the change
            // Since we don't have direct access to the employee context
            // For now, we'll use a placeholder employee ID (1 = usually the superadmin)
            $employee_id = 1;

            // Update the status
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $employee_id,
                null, // No comment
                true // is admin
            );

            die(json_encode(['success' => $success]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }


    /**
     * Process vendor status update
     */
    public function processUpdateVendorStatus()
    {
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        $id_vendor = $vendor['id_vendor'];
        $id_supplier = $vendor['id_supplier'];
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $new_status = Tools::getValue('status');
        $comment = Tools::getValue('comment');

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

            $id_order = $result['id_order'];

            // Update the status
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $this->context->customer->id,
                $comment,
                false // not admin
            );

            if (!$success) {
                throw new Exception('Failed to update status');
            }

            // Get updated status data
            $statusData = $this->getOrderLineStatusData($id_order_detail, $id_vendor);

            die(json_encode([
                'success' => true,
                'statusData' => $statusData,
                'message' => 'Status updated successfully'
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
    /**
     * Get status history for an order line
     */
    public function processGetStatusHistory()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');

        // Check authorization
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        try {
            $history = OrderLineStatusLog::getStatusHistory($id_order_detail);

            // Format the history data
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

            die(json_encode([
                'success' => true,
                'history' => $formattedHistory
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Get order line status data
     */
    public function getOrderLineStatusData($id_order_detail, $id_vendor)
    {
        $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        return [
            'status' => $lineStatus ? $lineStatus['status'] : 'Pending',
            'last_update' => $lineStatus ? date('Y-m-d H:i:s', strtotime($lineStatus['date_upd'])) : null,
            'comment' => $lineStatus ? $lineStatus['comment'] : null
        ];
    }

    public function processBulkUpdateVendorStatus()
    {
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        $id_vendor = $vendor['id_vendor'];
        $id_supplier = $vendor['id_supplier'];
        $order_detail_ids = Tools::getValue('order_detail_ids', []);
        $new_status = Tools::getValue('status');
        $comment = Tools::getValue('comment', 'Bulk status update');

        if (empty($order_detail_ids) || !is_array($order_detail_ids) || empty($new_status)) {
            die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
        }

        $success_count = 0;
        $error_count = 0;
        $results = [];

        foreach ($order_detail_ids as $id_order_detail) {
            // Verify this order detail belongs to this vendor's supplier
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

            // Update the status
            $update_result = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $this->context->customer->id,
                $comment,
                false // not admin
            );

            if ($update_result) {
                $success_count++;
                $results[$id_order_detail] = true;
            } else {
                $error_count++;
                $results[$id_order_detail] = false;
            }
        }

        die(json_encode([
            'success' => $success_count > 0,
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'message' => sprintf(
                '%d orders updated successfully, %d failed',
                $success_count,
                $error_count
            )
        ]));
    }




    public function processExportOrdersCSV()
    {
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            // For direct download, we can't return JSON error
            // Instead, output a simple error message
            header('Content-Type: text/plain');
            die('Error: Not authorized');
        }

        $id_vendor = $vendor['id_vendor'];

        // Get all order lines for this vendor with complete data
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
        COALESCE(ols.status, "Pending") as line_status
    ');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        $orderLines = Db::getInstance()->executeS($query);

        if (!$orderLines || empty($orderLines)) {
            // For direct download, output a simple error message
            header('Content-Type: text/plain');
            die('Error: No order data found');
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=vendor_orders_export_' . date('Y-m-d') . '.csv');
        // Disable caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Add CSV headers
        fputcsv($output, [
            $this->module->l('Order Reference'),
            $this->module->l('Product Name'),
            $this->module->l('SKU'),
            $this->module->l('Quantity'),
            $this->module->l('Vendor Amount'),
            $this->module->l('Status'),
            $this->module->l('Order Date')
        ]);

        // Add data rows
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
        exit; // Important: stop execution after sending the file
    }
    /**
     * Process get add commission status
     * Returns the first status that has commission_action = 'add' and is vendor allowed
     */
    public function processGetAddCommissionStatus()
    {
        try {
            $query = new DbQuery();
            $query->select('name, color, position');
            $query->from('order_line_status_type');
            $query->where('commission_action = "add"');
            $query->where('is_vendor_allowed = 1');
            $query->where('active = 1');
            $query->orderBy('position ASC');

            $status = Db::getInstance()->getRow($query);

            if ($status) {
                die(json_encode([
                    'success' => true,
                    'status' => $status
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'No suitable status found'
                ]));
            }
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }
}
