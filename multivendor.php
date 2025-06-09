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
            !$this->registerHook('actionObjectOrderDetailAddAfter') ||
            !$this->registerHook('actionObjectOrderDetailUpdateAfter') ||
            !$this->registerHook('actionObjectOrderDetailDeleteAfter') ||
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
        $stats = OrderHelper::getVendorOrderDetailsStats();
        $statusCount = OrderHelper::getStatusTotalCount();

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
            'SELECT * FROM `' . _DB_PREFIX_ . 'mv_order_status_permission`
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
                    Db::getInstance()->update('mv_vendor_transaction', [
                        'status' => 'cancelled',
                    ], 'id_order = ' . (int)$id_order . ' AND id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');
                    break;

                case 'pay':
                    // Update transaction status to paid
                    Db::getInstance()->update('mv_vendor_transaction', [
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

    $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();

    // Get order details
    $orderDetails = OrderDetail::getList($order->id);

    foreach ($orderDetails as $detail) {
        $product = new Product($detail['product_id']);
        $id_supplier = $product->id_supplier;

        // Check if this supplier is associated with a vendor
        $vendor = Vendor::getVendorBySupplier($id_supplier);

        if ($vendor) {
            // Calculate commission
            $commission_rate = VendorHelper::getCommissionRate($vendor['id_vendor']);
            $product_price = $detail['product_price'];
            $quantity = $detail['product_quantity'];
            $total_price = $quantity * $product_price ; 
            $commission_amount = $total_price * ($commission_rate / 100);
            $vendor_amount = $total_price - $commission_amount;

            // Create vendor order detail record with product information including reference
            $vendorOrderDetail = new VendorOrderDetail();
            $vendorOrderDetail->id_order_detail = $detail['id_order_detail'];
            $vendorOrderDetail->id_vendor = $vendor['id_vendor'];
            $vendorOrderDetail->id_order = $order->id;
            $vendorOrderDetail->product_id = $detail['product_id'];
            $vendorOrderDetail->product_name = $detail['product_name'];
            $vendorOrderDetail->product_reference = $detail['product_reference'];
            $vendorOrderDetail->product_mpn = $product->mpn ;
            $vendorOrderDetail->product_price = $detail['product_price'];
            $vendorOrderDetail->product_quantity = $detail['product_quantity'];
            $vendorOrderDetail->product_attribute_id = $detail['product_attribute_id'] ?: null;
            $vendorOrderDetail->commission_rate = $commission_rate;
            $vendorOrderDetail->commission_amount = $commission_amount;
            $vendorOrderDetail->vendor_amount = $vendor_amount;
            $vendorOrderDetail->date_add = date('Y-m-d H:i:s');
            $vendorOrderDetail->save();

            // Create initial order line status (simple table)
            $orderLineStatus = new OrderLineStatus();
            $orderLineStatus->id_order_detail = $detail['id_order_detail'];
            $orderLineStatus->id_vendor = $vendor['id_vendor'];
            $orderLineStatus->id_order_line_status_type = $defaultStatusTypeId;
            $orderLineStatus->date_add = date('Y-m-d H:i:s');
            $orderLineStatus->date_upd = date('Y-m-d H:i:s');
            $orderLineStatus->save();

            // Log status change
            OrderLineStatusLog::logStatusChange(
                $detail['id_order_detail'],
                $vendor['id_vendor'],
                null,
                $defaultStatusTypeId,
                0, // System
                'New order created'
            );
        }
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
