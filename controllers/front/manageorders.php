<?php

/**
 * Vendor Manage Orders controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}


class MultivendorManageOrdersModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        Parent::init();
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            Tools::redirect('index.php?controller=my-account');
        }

        // Set vendor ID for later use
        $this->context->smarty->assign('id_vendor', $vendor['id_vendor']);
        $this->context->smarty->assign('id_supplier', $vendor['id_supplier']);
    }

    public function initContent()
    {
        parent::initContent();

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_supplier = $this->context->smarty->getTemplateVars('id_supplier');

        // Get order lines grouped by status type
        $orderLinesByStatus = VendorHelper::getOrderLinesByStatusGrouped($id_vendor);

        // Get status information
        $statusInfo = $this->getStatusInfo();

        // Handle AJAX requests
        if (Tools::getValue('ajax')) {
            $this->handleAjax();
            return;
        }

        // Add CSS and JS files
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/manageorders.css');
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/manageorders.js');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');

        // Register displayPrice modifier if needed
        if (!is_callable('smartyDisplayPrice')) {
            smartyRegisterFunction(
                $this->context->smarty,
                'modifier',
                'displayPrice',
                ['Tools', 'displayPrice']
            );
        }

        // Add JS definitions for AJAX
        Media::addJsDef([
            'manageOrdersAjaxUrl' => $this->context->link->getModuleLink('multivendor', 'manageorders'),
            'manageOrdersAjaxToken' => Tools::getToken('multivendor'),
            'generateAwbUrl' => $this->context->link->getModuleLink('multivendor', 'awb'),
            'printMultipleAwbUrl' => $this->context->link->getModuleLink('multivendor', 'multiawb')
        ]);

        // Assign data to template
        $this->context->smarty->assign([
            'order_lines_by_status' => $orderLinesByStatus,
            'status_info' => $statusInfo,
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_manage_orders_url' => $this->context->link->getModuleLink('multivendor', 'manageorders'),
            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/manageorders.tpl');
    }



    /**
     * Get status information for vendor
     */
    protected function getStatusInfo()
    {
        $statuses = OrderLineStatusType::getAllActiveStatusTypes(true); // vendor allowed only
        $statusInfo = [];

        foreach ($statuses as $status) {
            $statusInfo[$status['name']] = [
                'color' => $status['color'],
                'commission_action' => $status['commission_action']
            ];
        }

        return $statusInfo;
    }

    /**
     * Handle AJAX requests
     */
    protected function handleAjax()
    {
        $action = Tools::getValue('action');

        switch ($action) {
            case 'updateOrderLineStatus':
                $this->ajaxUpdateOrderLineStatus();
                break;
            case 'generateAwb':
                $this->ajaxGenerateAwb();
                break;
            case 'generateMultipleAwb':
                $this->ajaxGenerateMultipleAwb();
                break;
            default:
                die(json_encode(['success' => false, 'message' => 'Unknown action']));
        }
    }

    /**
     * AJAX update order line status
     */
    protected function ajaxUpdateOrderLineStatus()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $new_status = Tools::getValue('new_status');
        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');

        try {
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $this->context->customer->id,
                'Status updated via drag and drop',
                false // not admin
            );

            if ($success) {
                die(json_encode([
                    'success' => true,
                    'message' => 'Status updated successfully'
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'Failed to update status'
                ]));
            }
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * AJAX generate AWB for single order
     */
    protected function ajaxGenerateAwb()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        // Use the new controller URL
        $awbUrl = $this->context->link->getModuleLink('multivendor', 'manifest', ['id_order_detail' => $id_order_detail]);

        die(json_encode([
            'success' => true,
            'awb_url' => $awbUrl
        ]));
    }

    /**
     * AJAX generate AWB for multiple orders
     */
    protected function ajaxGenerateMultipleAwb()
    {
        $order_details = Tools::getValue('order_details');

        if (!is_array($order_details) || empty($order_details)) {
            die(json_encode([
                'success' => false,
                'message' => 'No orders selected'
            ]));
        }

        // Use the new controller URL
        $awbUrl = $this->context->link->getModuleLink('multivendor', 'manifest', ['details' => implode(',', $order_details)]);

        die(json_encode([
            'success' => true,
            'awb_url' => $awbUrl
        ]));
    }
}
