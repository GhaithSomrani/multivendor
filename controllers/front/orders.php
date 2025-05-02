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

        // Handle status update
        if (Tools::isSubmit('submitStatusUpdate')) {
            $this->processStatusUpdate();
        }

        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Get order lines specific to this vendor's supplier ID
        $orderLines = $this->getVendorOrderLines($id_vendor, $id_supplier, $per_page, $offset);
        $total_lines = $this->countVendorOrderLines($id_vendor, $id_supplier);
        $total_pages = ceil($total_lines / $per_page);

        // Get available line statuses (only ones that vendor can use)
        $statuses = [];
        $status_colors = [];
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes(true);
        foreach ($statusTypes as $status) {
            $statuses[$status['name']] = $status['name'];
            $status_colors[$status['name']] = $status['color'];
        }

        // Assign data to template
        $this->context->smarty->assign([
            'order_lines' => $orderLines,
            'statuses' => $statuses,
            'status_colors' => $status_colors,
            'pages_nb' => $total_pages,
            'current_page' => $page,
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'currency_sign' => $this->context->currency->sign
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
        $query->select('od.id_order_detail, od.product_name, od.product_quantity, od.product_price, 
                      o.reference as order_reference, o.date_add as order_date, p.id_supplier,
                      vod.id_vendor, vod.commission_amount, vod.vendor_amount, vod.id_order,
                      ols.status as line_status');
        $query->from('order_detail', 'od');
        $query->innerJoin('orders', 'o', 'o.id_order = od.id_order');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->leftJoin('vendor_order_detail', 'vod', 'vod.id_order_detail = od.id_order_detail AND vod.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = od.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->where('p.id_supplier = ' . (int)$id_supplier);
        $query->orderBy('o.date_add DESC');
        $query->limit($limit, $offset);

        return Db::getInstance()->executeS($query);
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
            $this->createVendorOrderDetail($id_order_detail, $id_vendor, $id_supplier);
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
    protected function createVendorOrderDetail($id_order_detail, $id_vendor, $id_supplier)
    {
        // Get order detail info
        $orderDetail = new OrderDetail($id_order_detail);

        if (!Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        // Get default commission rate
        $commission_rate = $this->getCommissionRate($id_vendor, $orderDetail->product_id);
        $product_price = $orderDetail->product_price;
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
        $product = new Product($id_product);
        $id_category = $product->id_category_default;

        // Check if there's a specific category commission
        $categoryCommission = CategoryCommission::getCommissionRate($id_vendor, $id_category);

        if ($categoryCommission) {
            return $categoryCommission;
        }

        // Check if there's a vendor-specific commission
        $vendorCommission = VendorCommission::getCommissionRate($id_vendor);

        if ($vendorCommission) {
            return $vendorCommission;
        }

        // Return default commission
        return Configuration::get('MV_DEFAULT_COMMISSION', 10);
    }
}
