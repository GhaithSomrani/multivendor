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
    }

    public function initContent()
    {
        parent::initContent();

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');

        // Handle order detail view
        if (Tools::isSubmit('id_order')) {
            $this->displayOrderDetail();
            return;
        }

        // Handle status update
        if (Tools::isSubmit('submitStatusUpdate')) {
            $this->processStatusUpdate();
        }

        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        // Get orders
        $orders = Vendor::getVendorOrders($id_vendor, $per_page, $offset);
        $total_orders = Vendor::countVendorOrders($id_vendor);
        $total_pages = ceil($total_orders / $per_page);

        // Assign data to template
        $this->context->smarty->assign([
            'orders' => $orders,
            'pages_nb' => $total_pages,
            'current_page' => $page,
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'currency_sign' => $this->context->currency->sign
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/orders.tpl');
    }

    /**
     * Display order detail
     */
    protected function displayOrderDetail()
    {
        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_order = (int)Tools::getValue('id_order');

        // Get order details
        $order = new Order($id_order);
        $orderDetails = Vendor::getVendorOrderDetails($id_vendor, $id_order);

        // Check if this order belongs to the vendor
        if (!$orderDetails) {
            Tools::redirect($this->context->link->getModuleLink('multivendor', 'orders'));
        }

        // Get available line statuses (only ones that vendor can use)
        $statuses = [
            'pending' => $this->module->l('Pending'),
            'processing' => $this->module->l('Processing'),
            'shipped' => $this->module->l('Shipped'),
            'cancelled' => $this->module->l('Cancelled')
        ];

        // Assign data to template
        $this->context->smarty->assign([
            'order' => $order,
            'order_details' => $orderDetails,
            'statuses' => $statuses,
            'currency_sign' => $this->context->currency->sign,
            'back_url' => $this->context->link->getModuleLink('multivendor', 'orders')
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/order_detail.tpl');
    }

    /**
     * Process order line status update
     */
    protected function processStatusUpdate()
    {
        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_order = (int)Tools::getValue('id_order');
        $new_status = Tools::getValue('status');
        $comment = Tools::getValue('comment');

        // Check if this order detail belongs to the vendor
        $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$vendorOrderDetail) {
            $this->errors[] = $this->module->l('You do not have permission to update this order.');
            return;
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
            $this->success[] = $this->module->l('Order status updated successfully.');
        } else {
            $this->errors[] = $this->module->l('Failed to update order status.');
        }

        // Redirect to order detail page
        Tools::redirect($this->context->link->getModuleLink('multivendor', 'orders', ['id_order' => $id_order]));
    }
}
