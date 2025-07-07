<?php

/**
 * multivendor - A PrestaShop module for multi-vendor marketplace
 *
 * @author      Ghaith Somrani
 * @copyright   Copyright (c) 2025
 * @license     [License]
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/Vendor.php');
require_once(dirname(__FILE__) . '/classes/VendorCommission.php');
require_once(dirname(__FILE__) . '/classes/VendorTransaction.php');
require_once(dirname(__FILE__) . '/classes/VendorPayment.php');
require_once(dirname(__FILE__) . '/classes/OrderLineStatus.php');
require_once(dirname(__FILE__) . '/classes/OrderLineStatusLog.php');
require_once(dirname(__FILE__) . '/classes/VendorOrderDetail.php');
require_once(dirname(__FILE__) . '/classes/OrderLineStatusType.php');
require_once(dirname(__FILE__) . '/classes/VendorHelper.php');
require_once(dirname(__FILE__) . '/classes/pdf/HTMLTemplateVendorManifestPDF.php');
require_once(dirname(__FILE__) . '/classes/OrderHelper.php');
require_once(dirname(__FILE__) . '/classes/TransactionHelper.php');


class multivendor extends Module
{
    public function __construct()
    {
        $this->name = 'multivendor';
        $this->tab = 'market_place';
        $this->version = '1.1.0';
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
        $this->description = $this->l('Transformez votre boutique PrestaShop en une place de marché multi-vendeurs.');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
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
            !$this->registerHook('actionObjectOrderDetailAddAfter') ||
            !$this->registerHook('actionObjectOrderDetailUpdateAfter') ||
            !$this->registerHook('actionObjectOrderDetailDeleteAfter') ||
            !$this->registerHook('addWebserviceResources') ||
            !$this->installTab()
        ) {
            return false;
        }

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
            [
                'class_name' => 'AdminVendorOrderDetails',
                'name' => 'Order Details'
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
            'AdminOrderLineStatus',
            'AdminVendorOrderDetails'
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
     * Module configuration page
     */
    public function getContent()
    {
        $output = '';

        // Handle AJAX requests first
        if (Tools::isSubmit('ajax') && Tools::getValue('ajax') == '1') {
            $this->handleAdminAjaxRequests();
            return; // Stop execution after AJAX response
        }

        // Handle form submissions
        if (Tools::isSubmit('resetStatus')) {
            if (OrderHelper::resetOrderLineStatuses()) {
                $output .= $this->displayConfirmation($this->l('Order line statuses have been reset to default French statuses'));
            } else {
                $output .= $this->displayError($this->l('Error occurred while resetting statuses'));
            }
        }

        if (Tools::isSubmit('syncOrderDetails')) {
            $results = OrderHelper::synchronizeOrderDetailsWithVendors();
            $output .= $this->displayConfirmation(
                sprintf(
                    $this->l('Synchronization completed: %d processed, %d created, %d updated, %d skipped, %d errors'),
                    $results['processed'],
                    $results['created'],
                    $results['updated'],
                    $results['skipped'],
                    $results['errors']
                )
            );
        }

        if (Tools::isSubmit('submit' . $this->name)) {
            // Process existing configuration
            $defaultCommission = (float)Tools::getValue('MV_DEFAULT_COMMISSION');
            Configuration::updateValue('MV_DEFAULT_COMMISSION', $defaultCommission);

            $autoApproveVendors = (int)Tools::getValue('MV_AUTO_APPROVE_VENDORS');
            Configuration::updateValue('MV_AUTO_APPROVE_VENDORS', $autoApproveVendors);

            // NEW: Process MV_HIDE_FROM_VENDOR checkboxes
            $hiddenStatusTypes = [];
            $statusTypes = $this->getOrderLineStatusTypes();

            foreach ($statusTypes as $status) {
                $checkboxName = 'MV_HIDE_FROM_VENDOR_' . $status['id_order_line_status_type'];
                if (Tools::getValue($checkboxName)) {
                    $hiddenStatusTypes[] = $status['id_order_line_status_type'];
                }
            }

            // Save as comma-separated string
            $hiddenStatusTypesString = implode(',', $hiddenStatusTypes);
            Configuration::updateValue('MV_HIDE_FROM_VENDOR', $hiddenStatusTypesString);

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderConfigForm();
    }

    /**
     * Handle admin AJAX requests securely
     */
    private function handleAdminAjaxRequests()
    {
        // Verify this is an admin context
        if (!$this->context->employee || !$this->context->employee->id) {
            die(json_encode(['success' => false, 'message' => 'Access denied: Admin access required']));
        }

        // Verify admin token
        $token = Tools::getValue('token');
        $expectedToken = Tools::getAdminTokenLite('AdminModules');

        if (empty($token) || $token !== $expectedToken) {
            die(json_encode(['success' => false, 'message' => 'Access denied: Invalid token']));
        }

        $action = Tools::getValue('action');

        try {
            switch ($action) {
                case 'getOrderLineStatusesForAdmin':
                    $this->processGetOrderLineStatusesForAdmin();
                    break;

                case 'updateOrderLineStatus':
                    $this->processUpdateOrderLineStatusAdmin();
                    break;

                default:
                    die(json_encode(['success' => false, 'message' => 'Unknown admin action: ' . $action]));
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Multivendor AJAX error: ' . $e->getMessage(), 3, null, 'multivendor');
            die(json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]));
        }
    }

    /**
     * Handle getting order line statuses for admin
     */
    private function processGetOrderLineStatusesForAdmin()
    {
        $id_order = (int)Tools::getValue('id_order');

        if (!$id_order) {
            die(json_encode(['success' => false, 'message' => 'Missing order ID']));
        }

        $result = VendorHelper::getOrderLineStatusesForAdmin($id_order);
        die(json_encode($result));
    }

    /**
     * Handle admin order line status updates
     */
    private function processUpdateOrderLineStatusAdmin()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_vendor = (int)Tools::getValue('id_vendor');
        $id_status_type = (int)Tools::getValue('status'); // Admin sends this as 'status'
        $employee_id = $this->context->employee->id;

        if (!$id_order_detail || !$id_vendor || !$id_status_type) {
            die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
        }

        $result = VendorHelper::updateOrderLineStatusAsAdmin($id_order_detail, $id_vendor, $id_status_type, $employee_id);
        die(json_encode($result));
    }

    /**
     * Render configuration form
     */
    protected function renderConfigForm()
    {
        $statusTypes = $this->getOrderLineStatusTypes();

        $stats = OrderHelper::getVendorOrderDetailsStats();
        $statusCount = OrderHelper::getStatusTotalCount();
        $statusOptions = [];

        foreach ($statusTypes as $status) {
            if ($status['commission_action'] === 'none') {
                $statusOptions[] = [
                    'id' => 'status_' . $status['id_order_line_status_type'],
                    'value' => $status['id_order_line_status_type'],
                    'label' => $status['name']
                ];
            }
        }

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
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Hide Order Line Status Types from Vendors'),
                        'name' => 'MV_HIDE_FROM_VENDOR',
                        'desc' => $this->l('Select order line status types that should be hidden from vendors and only accessible by admin'),
                        'values' => [
                            'query' => $statusOptions,
                            'id' => 'value',
                            'name' => 'label'
                        ],
                        'expand' => [
                            'print_total' => count($statusOptions),
                            'default' => 'show',
                            'show' => ['text' => $this->l('Show all'), 'icon' => 'plus-sign-alt'],
                            'hide' => ['text' => $this->l('Hide all'), 'icon' => 'minus-sign-alt']
                        ]
                    ],
                    [
                        'type' => 'html',
                        'name' => 'stats_display',
                        'html_content' => '
                        <div class="alert alert-info">
                            <h4>' . $this->l('Vendor Order Statistics') . '</h4>
                            <p><strong>' . $this->l('Total Order Details:') . '</strong> ' . (int)$stats['total_order_details'] . '</p>
                            <p><strong>' . $this->l('Total Commission:') . '</strong> ' . Tools::displayPrice($stats['total_commission']) . '</p>
                            <p><strong>' . $this->l('Total Vendor Amount:') . '</strong> ' . Tools::displayPrice($stats['total_vendor_amount']) . '</p>
                            <p><strong>' . $this->l('Average Commission Rate:') . '</strong> ' . number_format($stats['avg_commission_rate'], 2) . '%</p>
                        </div>
                        <div class="alert alert-warning">
                            <h4>' . $this->l('Synchronization') . '</h4>
                            <p>' . $this->l('If you have existing orders that were created before installing this module, you can synchronize them with vendor order details.') . '</p>
                            <button type="submit" name="syncOrderDetails" class="btn btn-warning">
                                <i class="icon-refresh"></i> ' . $this->l('Synchronize Existing Orders') . '
                            </button>
                        </div>
                        '
                    ],
                    [
                        'type' => 'html',
                        'name' => 'reset_status_section',
                        'html_content' => '
                        <div class="alert alert-info">
                            <h4><i class="icon-info"></i> ' . $this->l('Order Status Information') . '</h4>
                            <p><strong>' . $this->l('Current Status Count:') . '</strong> ' . $statusCount . ' ' . $this->l('status types configured') . '</p>
                            <p>' . $this->l('You can manage individual statuses in the "Order Line Statuses" tab or reset all to defaults below.') . '</p>
                        </div>
                        <div class="alert alert-warning">
                            <h4><i class="icon-warning"></i> ' . $this->l('Reset Order Status Types') . '</h4>
                            <p>' . $this->l('Reset all order line statuses to default French statuses with proper commission settings.') . '</p>
                            <p><strong>' . $this->l('Warning:') . '</strong> ' . $this->l('This action will delete all existing custom statuses and cannot be undone.') . '</p>
                            <button type="submit" name="resetStatus" class="btn btn-warning" onclick="return confirm(\'' . $this->l('Are you sure you want to reset all order line statuses? This action cannot be undone.') . '\');">
                                <i class="icon-refresh"></i> ' . $this->l('Reset to Default French Statuses') . '
                            </button>
                        </div>
                        <style>
                        .btn-warning {
                            background-color: #f0ad4e !important;
                            border-color: #eea236 !important;
                            color: #fff !important;
                        }
                        .btn-warning:hover {
                            background-color: #ec971f !important;
                            border-color: #d58512 !important;
                        }
                        </style>
                        '
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ],
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

        $hiddenStatusTypes = $this->getHiddenStatusTypesArray();
        $fieldsValue = [
            'MV_DEFAULT_COMMISSION' => Configuration::get('MV_DEFAULT_COMMISSION', 10),
            'MV_AUTO_APPROVE_VENDORS' => Configuration::get('MV_AUTO_APPROVE_VENDORS', 0)
        ];
        foreach ($statusOptions as $option) {
            $fieldsValue['MV_HIDE_FROM_VENDOR_' . $option['value']] = in_array($option['value'], $hiddenStatusTypes);
        }

        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Get all order line status types for configuration checkboxes
     * 
     * @return array
     */
    private function getOrderLineStatusTypes()
    {
        $sql = 'SELECT id_order_line_status_type, name , commission_action
            FROM ' . _DB_PREFIX_ . 'mv_order_line_status_type 
            WHERE active = 1 
            ORDER BY id_order_line_status_type ASC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get hidden status types as array
     * 
     * @return array
     */
    private function getHiddenStatusTypesArray()
    {
        $hiddenConfig = Configuration::get('MV_HIDE_FROM_VENDOR');
        if (empty($hiddenConfig)) {
            return [];
        }

        // Configuration stores comma-separated IDs
        return array_map('intval', explode(',', $hiddenConfig));
    }

    /**
     * Helper method to check if a status type is hidden from vendors
     * 
     * @param int $id_order_line_status_type
     * @return bool
     */
    public static function isStatusTypeHiddenFromVendor($id_order_line_status_type)
    {
        $hiddenStatusTypes = Configuration::get('MV_HIDE_FROM_VENDOR');
        if (empty($hiddenStatusTypes)) {
            return false;
        }

        $hiddenArray = array_map('intval', explode(',', $hiddenStatusTypes));
        return in_array((int)$id_order_line_status_type, $hiddenArray);
    }

    /**
     * Get status types available for vendors (not hidden)
     * 
     * @return array
     */
    public static function getVendorAvailableStatusTypes()
    {
        $sql = 'SELECT ost.id_order_line_status_type, ost.name, ost.color 
                FROM ' . _DB_PREFIX_ . 'mv_order_line_status_type ost 
                WHERE ost.active = 1 
                ORDER BY ost.name ASC';

        $allStatuses = Db::getInstance()->executeS($sql);
        $hiddenStatusTypes = Configuration::get('MV_HIDE_FROM_VENDOR');

        if (empty($hiddenStatusTypes)) {
            return $allStatuses;
        }

        $hiddenArray = array_map('intval', explode(',', $hiddenStatusTypes));

        // Filter out hidden status types
        return array_filter($allStatuses, function ($status) use ($hiddenArray) {
            return !in_array((int)$status['id_order_line_status_type'], $hiddenArray);
        });
    }

    /**
     * Get status types available for admin only
     * 
     * @return array
     */
    public static function getAdminOnlyStatusTypes()
    {
        $hiddenStatusTypes = Configuration::get('MV_HIDE_FROM_VENDOR');
        if (empty($hiddenStatusTypes)) {
            return [];
        }

        $hiddenArray = array_map('intval', explode(',', $hiddenStatusTypes));
        $placeholders = str_repeat('?,', count($hiddenArray) - 1) . '?';

        $sql = 'SELECT ost.id_order_line_status_type, ost.name, ost.color 
                FROM ' . _DB_PREFIX_ . 'mv_order_line_status_type ost 
                WHERE ost.active = 1 
                AND ost.id_order_line_status_type IN (' . $placeholders . ')
                ORDER BY ost.name ASC';

        return Db::getInstance()->executeS($sql, $hiddenArray);
    }

    /**
     * Hook: When a new order is validated
     */
    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $orderDetails = OrderDetail::getList($order->id);
        foreach ($orderDetails as $detail) {
            $orderDetail = new OrderDetail($detail['id_order_detail']);
            OrderHelper::processOrderDetailForVendor($orderDetail);
        }
    }

    /**
     * Hook: Display vendor tabs in customer account
     */
    public function hookDisplayCustomerAccount()
    {
        $id_customer = $this->context->customer->id;

        // Check if this customer is a vendor
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

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
     * Hook: When an order detail is added (alternative hook)
     */
    public function hookActionObjectOrderDetailAddAfter($params)
    {
        if (isset($params['object'])) {
            OrderHelper::processOrderDetailForVendor($params['object']);
        }
    }

    /**
     * Hook: When an order detail is updated (alternative hook)
     */
    public function hookActionObjectOrderDetailUpdateAfter($params)
    {
        if (isset($params['object'])) {
            OrderHelper::updateOrderDetailForVendor($params['object']);
        }
    }

    /**
     * Hook: When an order detail is deleted (alternative hook)
     */
    public function hookActionObjectOrderDetailDeleteAfter($params)
    {
        if (isset($params['object'])) {
            OrderHelper::deleteOrderDetailForVendor($params['object']);
        }
    }

    public function hookAddWebserviceResources()
    {
        return [
            'order_line_status_types' => [
                'description' => 'Multi-vendor order line status types',
                'class' => 'OrderLineStatusType',
                'specific_management' => false,
                'forbidden_method' => ['PUT', 'DELETE', 'POST'],

            ],
            'order_line_history' => [
                'description' => 'Multi-vendor order line status history',
                'class' => 'OrderLineStatusLog',
                'specific_management' => false,
                'forbidden_method' => ['POST', 'PUT', 'DELETE']

            ],
            'order_line_statuses' => [
                'description' => 'Multi-vendor order line status',
                'class' => 'OrderLineStatus',
                'specific_management' => false,
                'forbidden_method' => ['POST', 'DELETE']

            ],

        ];
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
            'AdminOrderLineStatus',
            'AdminVendorOrderDetails',
        ];

        if (in_array($controller, $allowedControllers)) {
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }

    /**
     * Hook: Add JS/CSS to back office header
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        $controller = Tools::getValue('controller');

        // Only add our JS variables on order pages
        if ($controller === 'AdminOrders') {
            // Build the admin AJAX URL properly
            $adminAjaxUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
                'ajax' => '1'
            ]);

            // Add JS variables for admin AJAX
            Media::addJsDef([
                'multivendorAdminAjaxUrl' => $adminAjaxUrl,
                'multivendorToken' => Tools::getAdminTokenLite('AdminModules')
            ]);

            // Log for debugging
            PrestaShopLogger::addLog('Multivendor admin AJAX URL: ' . $adminAjaxUrl, 1, null, 'multivendor');
        }
    }
}
