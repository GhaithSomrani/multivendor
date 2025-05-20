<?php

/**
 * multivendor - A PrestaShop module for multi-vendor marketplace
 *
 * @author      YourName
 * @copyright   Copyright (c) 2025
 * @license     [License]
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/Vendor.php');
require_once(dirname(__FILE__) . '/classes/VendorCommission.php');
require_once(dirname(__FILE__) . '/classes/CategoryCommission.php');
require_once(dirname(__FILE__) . '/classes/VendorTransaction.php');
require_once(dirname(__FILE__) . '/classes/VendorPayment.php');
require_once(dirname(__FILE__) . '/classes/OrderLineStatus.php');
require_once(dirname(__FILE__) . '/classes/OrderLineStatusLog.php');
require_once(dirname(__FILE__) . '/classes/VendorOrderDetail.php');
require_once(dirname(__FILE__) . '/classes/OrderLineStatusType.php');

class multivendor extends Module
{
    public function __construct()
    {
        $this->name = 'multivendor';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = 'Ghaith Somrani';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();
        if (!is_callable('smartyDisplayPrice')) {
            smartyRegisterFunction(
                $this->context->smarty,
                'modifier',
                'displayPrice',
                ['Tools', 'displayPrice']
            );
        }


        $this->displayName = $this->l('Multi-Vendor Marketplace');
        $this->description = $this->l('Transform your PrestaShop store into a multi-vendor marketplace.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * Install the module
     * 
     * @return bool
     */
    public function install()
    {
        // Create database tables
        include(dirname(__FILE__) . '/sql/install.php');

        // Register hooks
        if (
            !parent::install() ||
            !$this->registerHook('actionOrderStatusUpdate') ||
            !$this->registerHook('displayAdminOrder') ||
            !$this->registerHook('displayCustomerAccount') ||
            !$this->registerHook('actionValidateOrder') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('actionAdminControllerSetMedia') ||
            !$this->installOverrides() ||
            !$this->installTab()
        ) {
            return false;
        }

        // Create vendor order statuses
        $this->createOrderStatuses();

        return true;
    }

    /**
     * Uninstall the module
     * 
     * @return bool
     */
    public function uninstall()
    {
        // Drop database tables
        include(dirname(__FILE__) . '/sql/uninstall.php');

        // Unregister tabs
        if (!parent::uninstall() || !$this->uninstallTab()) {
            return false;
        }

        return true;
    }

    /**
     * Install tabs for admin controllers
     * 
     * @return bool
     */
    public function installTab()
    {
        $tabParent = new Tab();
        $tabParent->active = 1;
        $tabParent->name = array();
        $tabParent->class_name = 'AdminVendor';

        foreach (Language::getLanguages() as $language) {
            $tabParent->name[$language['id_lang']] = $this->l('Marketplace');
        }

        $tabParent->id_parent = 0;
        $tabParent->module = $this->name;
        $tabParent->add();

        // Sub-tabs
        $tabs = [
            [
                'class_name' => 'AdminVendors',
                'name' => 'Vendors'
            ],
            [
                'class_name' => 'AdminVendorCommissions',
                'name' => 'Commissions'
            ],
            [
                'class_name' => 'AdminVendorPayments',
                'name' => 'Payments'
            ],
            [
                'class_name' => 'AdminVendorSettings',
                'name' => 'Settings',

            ],
            [
                'class_name' => 'AdminOrderLineStatus',
                'name' => 'Order Line Statuses'
            ],
        ];

        foreach ($tabs as $t) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $t['class_name'];
            $tab->name = array();
            foreach (Language::getLanguages() as $language) {
                $tab->name[$language['id_lang']] = $this->l($t['name']);
            }
            $tab->id_parent = $tabParent->id;
            $tab->module = $this->name;
            $tab->add();
        }

        return true;
    }

    /**
     * Uninstall tabs
     * 
     * @return bool
     */
    public function uninstallTab()
    {
        $tabs = [
            'AdminVendors',
            'AdminVendorCommissions',
            'AdminVendorPayments',
            'AdminVendorSettings',
            'AdminVendor',
            'AdminOrderLineStatus'
        ];

        foreach ($tabs as $className) {
            $id_tab = (int)Tab::getIdFromClassName($className);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }

    /**
     * Create custom order statuses for vendors
     * 
     * @return bool
     */
    public function createOrderStatuses()
    {
        // Create vendor-specific order statuses
        $statuses = [
            [
                'name' => 'Awaiting Vendor Processing',
                'color' => '#FFDD99',
                'logable' => true,
                'paid' => false,
                'shipped' => false,
                'delivery' => false,
                'invoice' => false,
                'vendor_allowed' => true,
                'admin_allowed' => true,
                'affects_commission' => false
            ],
            [
                'name' => 'Processing by Vendor',
                'color' => '#8AAAE5',
                'logable' => true,
                'paid' => true,
                'shipped' => false,
                'delivery' => false,
                'invoice' => true,
                'vendor_allowed' => true,
                'admin_allowed' => true,
                'affects_commission' => false
            ],
            [
                'name' => 'Shipped by Vendor',
                'color' => '#32CD32',
                'logable' => true,
                'paid' => true,
                'shipped' => true,
                'delivery' => false,
                'invoice' => true,
                'vendor_allowed' => true,
                'admin_allowed' => true,
                'affects_commission' => true,
                'commission_action' => 'add'
            ],
            [
                'name' => 'Cancelled by Vendor',
                'color' => '#DC143C',
                'logable' => true,
                'paid' => false,
                'shipped' => false,
                'delivery' => false,
                'invoice' => false,
                'vendor_allowed' => true,
                'admin_allowed' => true,
                'affects_commission' => true,
                'commission_action' => 'cancel'
            ]
        ];

        foreach ($statuses as $statusData) {
            $orderState = new OrderState();
            $orderState->color = $statusData['color'];
            $orderState->logable = $statusData['logable'];
            $orderState->paid = $statusData['paid'];
            $orderState->shipped = $statusData['shipped'];
            $orderState->delivery = $statusData['delivery'];
            $orderState->invoice = $statusData['invoice'];
            $orderState->module_name = $this->name;
            $orderState->name = array();

            foreach (Language::getLanguages() as $language) {
                $orderState->name[$language['id_lang']] = $statusData['name'];
            }

            $orderState->add();

            // Add permission record
            Db::getInstance()->insert('order_status_permission', [
                'id_order_status' => (int)$orderState->id,
                'is_vendor_allowed' => (int)$statusData['vendor_allowed'],
                'is_admin_allowed' => (int)$statusData['admin_allowed'],
                'affects_commission' => (int)$statusData['affects_commission'],
                'commission_action' => isset($statusData['commission_action']) ? $statusData['commission_action'] : null,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s')
            ]);
        }

        return true;
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            // Process configuration form
            $defaultCommission = (float)Tools::getValue('MV_DEFAULT_COMMISSION');
            Configuration::updateValue('MV_DEFAULT_COMMISSION', $defaultCommission);

            $autoApproveVendors = (int)Tools::getValue('MV_AUTO_APPROVE_VENDORS');
            Configuration::updateValue('MV_AUTO_APPROVE_VENDORS', $autoApproveVendors);

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderConfigForm();
    }

    /**
     * Render configuration form
     */
    protected function renderConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Multi-Vendor Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Default Commission Rate (%)'),
                        'name' => 'MV_DEFAULT_COMMISSION',
                        'desc' => $this->l('Default commission rate for all vendors (in percentage)'),
                        'size' => 5,
                        'required' => true
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Auto-approve Vendors'),
                        'name' => 'MV_AUTO_APPROVE_VENDORS',
                        'desc' => $this->l('Automatically approve new vendor registrations'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                'MV_DEFAULT_COMMISSION' => Configuration::get('MV_DEFAULT_COMMISSION', 10),
                'MV_AUTO_APPROVE_VENDORS' => Configuration::get('MV_AUTO_APPROVE_VENDORS', 0)
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Hook: When an order status is updated
     */
    public function hookActionOrderStatusUpdate($params)
    {
        $id_order = $params['id_order'];
        $new_order_status = $params['newOrderStatus'];

        // Check if this order status affects commission
        $orderStatusPermission = $this->getOrderStatusPermission($new_order_status->id);

        if ($orderStatusPermission && $orderStatusPermission['affects_commission']) {
            // Process commissions based on the action
            $this->processCommissionForOrder($id_order, $orderStatusPermission['commission_action']);
        }
    }

    /**
     * Get order status permission record
     */
    protected function getOrderStatusPermission($id_order_status)
    {
        return Db::getInstance()->getRow(
            '
            SELECT * FROM `' . _DB_PREFIX_ . 'order_status_permission`
            WHERE `id_order_status` = ' . (int)$id_order_status
        );
    }

    /**
     * Process commission for an order
     */
    protected function processCommissionForOrder($id_order, $action)
    {
        // Get all vendor order details for this order
        $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

        foreach ($vendorOrderDetails as $vendorOrderDetail) {
            $id_vendor = $vendorOrderDetail['id_vendor'];
            $commission_amount = $vendorOrderDetail['commission_amount'];
            $vendor_amount = $vendorOrderDetail['vendor_amount'];

            switch ($action) {
                case 'add':
                    // Create transaction record for commission
                    $transaction = new VendorTransaction();
                    $transaction->id_vendor = $id_vendor;
                    $transaction->id_order = $id_order;
                    $transaction->commission_amount = $commission_amount;
                    $transaction->vendor_amount = $vendor_amount;
                    $transaction->transaction_type = 'commission';
                    $transaction->status = 'pending';
                    $transaction->date_add = date('Y-m-d H:i:s');
                    $transaction->save();
                    break;

                case 'cancel':
                    // Cancel any pending transactions
                    Db::getInstance()->update('vendor_transaction', [
                        'status' => 'cancelled',
                    ], 'id_order = ' . (int)$id_order . ' AND id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');
                    break;

                case 'pay':
                    // Update transaction status to paid
                    Db::getInstance()->update('vendor_transaction', [
                        'status' => 'paid',
                    ], 'id_order = ' . (int)$id_order . ' AND id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');
                    break;
            }
        }
    }

    /**
     * Hook: When a new order is validated
     */
    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];

        $defaultStatus = Db::getInstance()->getValue('
            SELECT name FROM `' . _DB_PREFIX_ . 'order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC ');
        if (!$defaultStatus) {
            $defaultStatus = 'Pending';
        }

        // Get order details
        $orderDetails = OrderDetail::getList($order->id);

        foreach ($orderDetails as $detail) {
            $product = new Product($detail['product_id']);
            $id_supplier = $product->id_supplier;

            // Check if this supplier is associated with a vendor
            $vendor = Vendor::getVendorBySupplier($id_supplier);

            if ($vendor) {
                // Calculate commission
                $commission_rate = $this->getCommissionRate($vendor['id_vendor'], $product->id_category_default);
                $product_price = $detail['unit_price_tax_incl'];
                $quantity = $detail['product_quantity'];
                $total_price =  $detail['total_price_tax_incl'];
                $commission_amount = $total_price * ($commission_rate / 100);
                $vendor_amount = $total_price - $commission_amount;

                // Create vendor order detail record
                $vendorOrderDetail = new VendorOrderDetail();
                $vendorOrderDetail->id_order_detail = $detail['id_order_detail'];
                $vendorOrderDetail->id_vendor = $vendor['id_vendor'];
                $vendorOrderDetail->id_order = $order->id;
                $vendorOrderDetail->commission_rate = $commission_rate;
                $vendorOrderDetail->commission_amount = $commission_amount;
                $vendorOrderDetail->vendor_amount = $vendor_amount;
                $vendorOrderDetail->date_add = date('Y-m-d H:i:s');
                $vendorOrderDetail->save();

                // Create initial order line status
                $orderLineStatus = new OrderLineStatus();
                $orderLineStatus->id_order_detail = $detail['id_order_detail'];
                $orderLineStatus->id_vendor = $vendor['id_vendor'];
                $orderLineStatus->status = $defaultStatus;
                $orderLineStatus->date_add = date('Y-m-d H:i:s');
                $orderLineStatus->date_upd = date('Y-m-d H:i:s');
                $orderLineStatus->save();

                // Log status change
                $orderLineStatusLog = new OrderLineStatusLog();
                $orderLineStatusLog->id_order_detail = $detail['id_order_detail'];
                $orderLineStatusLog->id_vendor = $vendor['id_vendor'];
                $orderLineStatusLog->old_status = $defaultStatus;
                $orderLineStatusLog->new_status = $defaultStatus;
                $orderLineStatusLog->changed_by = 0; // System
                $orderLineStatusLog->date_add = date('Y-m-d H:i:s');
                $orderLineStatusLog->save();
            }
        }
    }

    /**
     * Get commission rate for a vendor and category
     */
    protected function getCommissionRate($id_vendor, $id_category)
    {
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

    /**
     * Hook: Display vendor tabs in customer account
     */
    public function hookDisplayCustomerAccount()
    {
        $id_customer = $this->context->customer->id;

        // Check if this customer is a vendor
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if ($vendor) {
            $this->context->smarty->assign([
                'is_vendor' => true,
                'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard', []),
                'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders', []),
                'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions', []),
                'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile', []),
                'vendor_manage_orders_url' => $this->context->link->getModuleLink('multivendor', 'manageorders', []),

            ]);

            return $this->display(__FILE__, 'views/templates/front/customer_account.tpl');
        } else {
            // Check if we allow customers to register as vendors
            if (Configuration::get('MV_ALLOW_VENDOR_REGISTRATION', 1)) {
                $this->context->smarty->assign([
                    'is_vendor' => false,
                    'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard', []),
                    'vendor_register_url' => $this->context->link->getModuleLink('multivendor', 'register', [])

                ]);

                return $this->display(__FILE__, 'views/templates/front/customer_account.tpl');
            }
        }

        return '';
    }

    /**
     * Hook: Display on admin order page
     */
    public function hookDisplayAdminOrder($params)
    {
        $id_order = $params['id_order'];

        // Get all vendor order details for this order
        $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);
        $orderDetails = [];

        foreach ($vendorOrderDetails as $vodDetail) {
            $orderDetail = new OrderDetail($vodDetail['id_order_detail']);
            $vendor = new Vendor($vodDetail['id_vendor']);
            $lineStatus = OrderLineStatus::getByOrderDetail($vodDetail['id_order_detail']);

            $orderDetails[] = [
                'id_order_detail' => $vodDetail['id_order_detail'],
                'id_vendor' => $vodDetail['id_vendor'],
                'vendor_name' => $vendor->shop_name,
                'product_name' => $orderDetail->product_name,
                'product_quantity' => $orderDetail->product_quantity,
                'product_price' => $orderDetail->product_price,
                'commission_rate' => $vodDetail['commission_rate'],
                'commission_amount' => $vodDetail['commission_amount'],
                'vendor_amount' => $vodDetail['vendor_amount'],
                'status' => $lineStatus ? $lineStatus['status'] : 'unknown'
            ];
        }

        // Get available line statuses
        $statuses = [
            'pending' => $this->l('Pending'),
            'processing' => $this->l('Processing'),
            'shipped' => $this->l('Shipped'),
            'delivered' => $this->l('Delivered'),
            'cancelled' => $this->l('Cancelled')
        ];

        $this->context->smarty->assign([
            'order_details' => $orderDetails,
            'statuses' => $statuses,
            'id_order' => $id_order,
            'update_status_url' => $this->context->link->getAdminLink('AdminOrders') . '&ajax=1&action=updateVendorLineStatus&id_order=' . $id_order
        ]);

        return $this->display(__FILE__, 'views/templates/admin/order_detail.tpl');
    }




    /**
     * Get order line statuses for admin
     */
    public function ajaxProcessGetOrderLineStatusesForAdmin()
    {
        // Check if this is an AJAX request
        if (!$this->isXmlHttpRequest()) {
            die(json_encode(['success' => false, 'message' => 'Invalid request']));
        }

        $id_order = (int)Tools::getValue('id_order');
        $statusData = [];

        // Get all vendor order details for this order
        $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

        foreach ($vendorOrderDetails as $detail) {
            $id_order_detail = $detail['id_order_detail'];
            $id_vendor = $detail['id_vendor'];

            $vendor = new Vendor($id_vendor);
            $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

            $statusData[$id_order_detail] = [
                'id_vendor' => $id_vendor,
                'vendor_name' => $vendor->shop_name,
                'status' => $lineStatus ? $lineStatus['status'] : 'Pending',
                'status_date' => $lineStatus ? $lineStatus['date_upd'] : null
            ];
        }

        // Get all available statuses that admin can set
        $availableStatuses = OrderLineStatusType::getAllActiveStatusTypes(false, true);

        die(json_encode([
            'success' => true,
            'statusData' => $statusData,
            'availableStatuses' => $availableStatuses
        ]));
    }

    /**
     * Update order line status
     */
    public function ajaxProcessUpdateOrderLineStatus()
    {
        // Check if this is an AJAX request
        if (!$this->isXmlHttpRequest()) {
            die(json_encode(['success' => false, 'message' => 'Invalid request']));
        }

        // Get parameters
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_vendor = (int)Tools::getValue('id_vendor');
        $new_status = Tools::getValue('status');

        // Update the status
        $success = OrderLineStatus::updateStatus(
            $id_order_detail,
            $id_vendor,
            $new_status,
            $this->context->employee->id,
            null, // No comment
            true // is admin
        );

        die(json_encode(['success' => $success]));
    }

    /**
     * Check if current request is an AJAX request
     */
    private function isXmlHttpRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * Hook: Add JS/CSS to admin pages
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Tools::getValue('controller');

        $allowedControllers = [
            'AdminOrders',       
            'AdminVendors',             
            'AdminVendorCommissions',    
            'AdminVendorPayments',       
            'AdminOrderLineStatus'       
        ];

        if (in_array($controller, $allowedControllers)) {
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }
    /**
     * Hook: Add JS/CSS to back office
     */
    public function hookDisplayBackOfficeHeader($params)

    {
        $ajaxUrl = $this->context->link->getModuleLink('multivendor', 'ajax');

        Media::addJsDef([
            'multivendorAjaxUrl' => $ajaxUrl,
            'adminToken' => Tools::getAdminToken('AdminOrders')
        ]);

      
    }
}
