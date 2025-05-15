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
        $vendor = Vendor::getVendorByCustomer($id_customer);

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

        // Get order summary data
        $orderSummary = $this->getOrderSummary($id_vendor, $id_supplier);

        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Get order lines specific to this vendor's supplier ID
        $orderLines = $this->getVendorOrderLines($id_vendor, $id_supplier, $per_page, $offset);
        $total_lines = $this->countVendorOrderLines($id_vendor, $id_supplier);
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
    protected function getVendorOrderLines($id_vendor, $id_supplier, $limit = 20, $offset = 0)
    {
        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name,od.product_reference ,  od.product_quantity, od.unit_price_tax_incl,od.total_price_tax_incl,
                  o.reference as order_reference, o.date_add as order_date, p.id_supplier,
                  vod.id_vendor, vod.commission_amount, vod.vendor_amount, vod.id_order,
                  COALESCE(ols.status, "Pending") as line_status');
        $query->from('order_detail', 'od');
        $query->innerJoin('orders', 'o', 'o.id_order = od.id_order');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->leftJoin('vendor_order_detail', 'vod', 'vod.id_order_detail = od.id_order_detail AND vod.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = od.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');
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
    protected function countVendorOrderLines($id_vendor, $id_supplier)
    {
        $query = new DbQuery();
        $query->select('COUNT(od.id_order_detail)');
        $query->from('order_detail', 'od');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->where('p.id_supplier = ' . (int)$id_supplier);

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
        $commission_rate = $this->getCommissionRate($id_vendor, $orderDetail->product_id);
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
     * Get commission rate for a vendor and product
     */
    protected function getCommissionRate($id_vendor, $id_product)
    {
        if (!$id_product || !$id_vendor) {
            return Configuration::get('MV_DEFAULT_COMMISSION', 10);
        }

        $product = new Product($id_product);

        if (!Validate::isLoadedObject($product)) {
            return Configuration::get('MV_DEFAULT_COMMISSION', 10);
        }

        $id_category = $product->id_category_default;

        // Check if there's a specific category commission
        $categoryCommission = CategoryCommission::getCommissionRate($id_vendor, $id_category);
        if ($categoryCommission !== null) {
            return $categoryCommission;
        }

        // Check if there's a vendor-specific commission
        $vendorCommission = VendorCommission::getCommissionRate($id_vendor);
        if ($vendorCommission !== null) {
            return $vendorCommission;
        }

        // Return default commission
        return Configuration::get('MV_DEFAULT_COMMISSION', 10);
    }

    /**
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

        $statusBreakdown = $this->getStatusBreakdown($id_vendor);

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

    protected function getStatusBreakdown($id_vendor)
    {
        // First, get the default status (first by position)
        $defaultStatus = Db::getInstance()->getValue('
        SELECT name FROM `' . _DB_PREFIX_ . 'order_line_status_type` 
        WHERE active = 1 
        ORDER BY position ASC 
    ');

        // Get actual status counts from order lines
        $statusBreakdown = Db::getInstance()->executeS('
        SELECT 
            lstype.name as status,
            lstype.color,
            lstype.position,
            lstype.is_vendor_allowed,
            (
                -- Count entries with this status
                SELECT COUNT(*)
                FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'order_line_status ols 
                    ON ols.id_order_detail = vod.id_order_detail 
                    AND ols.id_vendor = vod.id_vendor
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND (
                    ols.status = lstype.name 
                    OR 
                    (ols.status IS NULL AND lstype.name = "' . pSQL($defaultStatus) . '")
                )
            ) as count
        FROM ' . _DB_PREFIX_ . 'order_line_status_type lstype
        WHERE lstype.active = 1
        ORDER BY lstype.position ASC
    ');

        return $statusBreakdown;
    }
}
