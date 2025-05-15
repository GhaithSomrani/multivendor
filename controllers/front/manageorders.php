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
        $vendor = Vendor::getVendorByCustomer($id_customer);

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
        $orderLinesByStatus = $this->getOrderLinesByStatus($id_vendor);

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
     * Get order lines grouped by status type
     */
    protected function getOrderLinesByStatus($id_vendor)
    {
        // Get the default status
        $defaultStatus = Db::getInstance()->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC 
        ');

        // Get all order lines for this vendor
        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_reference, od.product_quantity,
                       o.reference as order_reference, o.date_add as order_date, o.id_order,
                       vod.commission_amount, vod.vendor_amount,
                       c.firstname, c.lastname,
                       a.address1, a.city, a.postcode,
                       COALESCE(ols.status, "' . pSQL($defaultStatus['name']) . '") as line_status,
                       COALESCE(olst.commission_action, "' . pSQL($defaultStatus['commission_action']) . '") as commission_action,
                       COALESCE(olst.color, "' . pSQL($defaultStatus['color']) . '") as status_color');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('customer', 'c', 'c.id_customer = o.id_customer');
        $query->leftJoin('address', 'a', 'a.id_address = o.id_address_delivery');
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('order_line_status_type', 'olst', 'olst.name = ols.status');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        $orderLines = Db::getInstance()->executeS($query);

        // Group by commission action
        $grouped = [
            'no_commission' => [],  // Orders that are cancelled or refunded
            'pending' => [],        // Orders pending commission action
            'ready' => []          // Orders ready for shipment (commission will be added)
        ];

        foreach ($orderLines as $line) {
            // Check if this is a cancelled or refunded order
            if ($line['commission_action'] == 'cancel' || $line['commission_action'] == 'refund') {
                $grouped['no_commission'][] = $line;
            } elseif ($line['line_status'] == 'Processing' || $line['line_status'] == 'processing') {
                $grouped['pending'][] = $line;
            } elseif ($line['line_status'] == 'Shipped' || $line['line_status'] == 'shipped' || 
                      $line['line_status'] == 'Ready' || $line['line_status'] == 'ready') {
                $grouped['ready'][] = $line;
            } else {
                // Default to pending based on commission action
                if ($line['commission_action'] == 'add') {
                    $grouped['pending'][] = $line;
                } else if ($line['commission_action'] == 'none') {
                    // Skip orders with no commission action
                    continue;
                } else {
                    $grouped['pending'][] = $line;
                }
            }
        }

        return $grouped;
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
        $awbUrl = $this->context->link->getModuleLink('multivendor', 'awb', ['id_order_detail' => $id_order_detail]);
        
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
        
        $awbUrl = $this->context->link->getModuleLink('multivendor', 'multiawb', ['details' => implode(',', $order_details)]);
        
        die(json_encode([
            'success' => true,
            'awb_url' => $awbUrl
        ]));
    }
}