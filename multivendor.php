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
require_once(dirname(__FILE__) . '/classes/Manifest.php');
require_once(dirname(__FILE__) . '/classes/ManifestStatusType.php');
require_once(dirname(__FILE__) . '/classes/ManifestType.php');
require_once(dirname(__FILE__) . '/classes/ManifestDetails.php');
require_once(dirname(__FILE__) . '/classes/ProductCommission.php');
require_once(dirname(__FILE__) . '/classes/ProductCommissionLog.php');
require_once(dirname(__FILE__) . '/classes/EntityLogHelper.php');
require_once(dirname(__FILE__) . '/classes/AuditLogs.php');
require_once(dirname(__FILE__) . '/classes/ChildRelationLog.php');
require_once(dirname(__FILE__) . '/classes/StatusChangeLog.php');

class multivendor extends Module
{
    private $beforeData = [];

    public function __construct()
    {
        $this->name = 'multivendor';
        $this->tab = 'market_place';
        $this->version = '3.0.0';
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
            !$this->registerHook('actionObjectOrderUpdateAfter') ||
            !$this->registerHook('actionObjectOrderDetailAddAfter') ||
            !$this->registerHook('actionObjectOrderDetailUpdateAfter') ||
            !$this->registerHook('actionObjectOrderDetailDeleteAfter') ||
            !$this->registerHook('addWebserviceResources') ||
            !$this->registerHook('actionObjectAddAfter') ||
            !$this->registerHook('actionObjectUpdateBefore') ||
            !$this->registerHook('actionObjectUpdateAfter') ||
            !$this->registerHook('actionObjectDeleteAfter') ||
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
            ['class_name' => 'AdminVendorOrderDetails', 'name' => 'Lignes des Commandes', 'icon' => 'shopping-cart'],
            ['class_name' => 'AdminManifest', 'name' => 'Manifests', 'icon' => 'file-truck'],
            ['class_name' => 'AdminVendorPayments', 'name' => 'Paiements', 'icon' => 'wallet'],
            ['class_name' => 'AdminVendors', 'name' => 'Fournisseurs', 'icon' => 'storefront'],
            ['class_name' => 'AdminVendorCommissions', 'name' => 'Commissions', 'icon' => 'money-off'],
            ['class_name' => 'AdminOrderLineStatus', 'name' => 'Statut des Lignes de Commande', 'icon' => 'mi-settings'],
            ['class_name' => 'AdminManifestStatusType', 'name' => 'Statut du Manifest', 'icon' => 'mi-settings'],
            ['class_name' => 'AdminManifestType', 'name' => 'Types de Manifests', 'icon' => 'mi-settings'],
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
            'AdminVendorOrderDetails',
            'AdminManifest'
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
    private function getManifestStatusOptions($id_manifest_type)
    {
        return ManifestStatusType::getManifestStatusByAllowedManifestType($id_manifest_type);
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
            // In getContent() method, after existing Configuration::updateValue calls:
            Configuration::updateValue('MULTIVENDOR_COLLECT_GROUP', (int)Tools::getValue('MULTIVENDOR_COLLECT_GROUP'));
            Configuration::updateValue('MULTIVENDOR_RECEIVE_GROUP', (int)Tools::getValue('MULTIVENDOR_RECEIVE_GROUP'));
            Configuration::updateValue('MULTIVENDOR_STATUS_COLLECTED', (int)Tools::getValue('MULTIVENDOR_STATUS_COLLECTED'));
            Configuration::updateValue('MULTIVENDOR_STATUS_RECEIVED', (int)Tools::getValue('MULTIVENDOR_STATUS_RECEIVED'));
            Configuration::updateValue('MULTIVENDOR_STATUS_REFUND_PROCESSING', (int)Tools::getValue('MULTIVENDOR_STATUS_REFUND_PROCESSING'));
            Configuration::updateValue('MULTIVENDOR_STATUS_REFUND_COLLECTED', (int)Tools::getValue('MULTIVENDOR_STATUS_REFUND_COLLECTED'));
            Configuration::updateValue('MANIFEST_PICKUP_DRAFT', (int)Tools::getValue('MANIFEST_PICKUP_DRAFT'));
            Configuration::updateValue('MANIFEST_RETURN_DRAFT', (int)Tools::getValue('MANIFEST_RETURN_DRAFT'));
            $output .= $this->displayConfirmation($this->l('Configuration mise à jour'));

            Configuration::updateValue('MV_FIRST_STATUS', (int)Tools::getValue('MV_FIRST_STATUS'));
            Configuration::updateValue('MV_AVAILABLE_STATUS', (int)Tools::getValue('MV_AVAILABLE_STATUS'));
            Configuration::updateValue('MV_OUT_OF_STOCK_STATUS', (int)Tools::getValue('MV_OUT_OF_STOCK_STATUS'));

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
            Configuration::updateValue('mv_pickup', (int)Tools::getValue('mv_pickup'));
            Configuration::updateValue('mv_returns', (int)Tools::getValue('mv_returns'));


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
        $pickup_options = $this->getManifestStatusOptions(1); // TYPE_PICKUP
        $returns_options = $this->getManifestStatusOptions(2); // TYPE_RETURNS

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
                        'label' => $this->l('Taux de Commission par Défaut (%)'),
                        'name' => 'MV_DEFAULT_COMMISSION',
                        'desc' => $this->l('Taux de commission par défaut pour tous les vendeurs (en pourcentage)'),
                        'size' => 5,
                        'required' => true
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Approbation Automatique des Vendeurs'),
                        'name' => 'MV_AUTO_APPROVE_VENDORS',
                        'desc' => $this->l('Approuver automatiquement les nouvelles inscriptions de vendeurs'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ]
                    ],


                    [
                        'type' => 'select',
                        'label' => $this->l('Groupe de Collecte'),
                        'name' => 'MULTIVENDOR_COLLECT_GROUP',
                        'desc' => $this->l('Groupe autorisé à collecter les manifestes'),
                        'options' => [
                            'query' => Group::getGroups($this->context->language->id),
                            'id' => 'id_group',
                            'name' => 'name'
                        ],
                    ],

                    [
                        'type' => 'select',
                        'label' => $this->l('Groupe de Réception'),
                        'name' => 'MULTIVENDOR_RECEIVE_GROUP',
                        'desc' => $this->l('Groupe autorisé à recevoir les manifestes'),
                        'options' => [
                            'query' => Group::getGroups($this->context->language->id),
                            'id' => 'id_group',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut du Manifeste de Ramassage'),
                        'name' => 'mv_pickup',
                        'desc' => $this->l('Statut par défaut du manifeste pour les manifestes de ramassage'),
                        'options' => [
                            'query' => $pickup_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut du Manifeste de Retours'),
                        'name' => 'mv_returns',
                        'desc' => $this->l('Statut par défaut du manifeste pour les manifestes de retours'),
                        'options' => [
                            'query' => $returns_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],

                    [
                        'type' => 'select',
                        'label' => $this->l('Statut du Manifeste Collecté'),
                        'name' => 'MULTIVENDOR_STATUS_COLLECTED',
                        'desc' => $this->l('Statut du manifeste pour les articles collectés'),
                        'options' => [
                            'query' => $pickup_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut du Manifeste Reçu'),
                        'name' => 'MULTIVENDOR_STATUS_RECEIVED',
                        'desc' => $this->l('Statut du manifeste pour les articles reçus'),
                        'options' => [
                            'query' => $pickup_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut du Manifeste de Traitement des Remboursements'),
                        'name' => 'MULTIVENDOR_STATUS_REFUND_PROCESSING',
                        'desc' => $this->l('Statut pour les articles de remboursement en traitement'),
                        'options' => [
                            'query' => $returns_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut du Manifeste de Remboursement Collecté'),
                        'name' => 'MULTIVENDOR_STATUS_REFUND_COLLECTED',
                        'desc' => $this->l('Statut pour les articles de remboursement collectés'),
                        'options' => [
                            'query' => $returns_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],

                    [
                        'type' => 'select',
                        'label' => $this->l('Brouillion de Ramassage'),
                        'name' => 'MANIFEST_PICKUP_DRAFT',
                        'desc' => $this->l('statut du manifeste par défaut pour les nouveaux manifestes de ramassage'),
                        'options' => [
                            'query' => $pickup_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Brouillion de Retours'),
                        'name' => 'MANIFEST_RETURN_DRAFT',
                        'desc' => $this->l('Statut du manifeste par défaut pour les nouveaux manifestes de retours'),
                        'options' => [
                            'query' => $returns_options,
                            'id' => 'id_manifest_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Masquer les Types de Statut de Ligne de Commande aux Vendeurs'),
                        'name' => 'MV_HIDE_FROM_VENDOR',
                        'desc' => $this->l('Sélectionner les types de statut de ligne de commande qui doivent être masqués aux vendeurs et accessibles uniquement par l\'administrateur'),
                        'values' => [
                            'query' => $statusOptions,
                            'id' => 'value',
                            'name' => 'label'
                        ],
                        'expand' => [
                            'print_total' => count($statusOptions),
                            'default' => 'show',
                            'show' => ['text' => $this->l('Afficher tout'), 'icon' => 'plus-sign-alt'],
                            'hide' => ['text' => $this->l('Masquer tout'), 'icon' => 'minus-sign-alt']
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Premier Statut de Ligne de Commande'),
                        'name' => 'MV_FIRST_STATUS',
                        'desc' => $this->l('Statut initial pour les nouvelles lignes de commande'),
                        'options' => [
                            'query' => $statusTypes,
                            'id' => 'id_order_line_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut Produit Disponible chez le Fournisseur'),
                        'name' => 'MV_AVAILABLE_STATUS',
                        'desc' => $this->l('Statut indiquant que le produit est disponible chez le fournisseur'),
                        'options' => [
                            'query' => $statusTypes,
                            'id' => 'id_order_line_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Statut Rupture de Stock'),
                        'name' => 'MV_OUT_OF_STOCK_STATUS',
                        'desc' => $this->l('Statut indiquant que le produit est en rupture de stock'),
                        'options' => [
                            'query' => $statusTypes,
                            'id' => 'id_order_line_status_type',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'stats_display',
                        'html_content' => '
            <div class="alert alert-info">
                <h4>' . $this->l('Statistiques des Commandes Vendeur') . '</h4>
                <p><strong>' . $this->l('Total des Détails de Commande :') . '</strong> ' . (int)$stats['total_order_details'] . '</p>
                <p><strong>' . $this->l('Commission Totale :') . '</strong> ' . Tools::displayPrice($stats['total_commission']) . '</p>
                <p><strong>' . $this->l('Montant Total Vendeur :') . '</strong> ' . Tools::displayPrice($stats['total_vendor_amount']) . '</p>
                <p><strong>' . $this->l('Taux de Commission Moyen :') . '</strong> ' . number_format($stats['avg_commission_rate'], 2) . '%</p>
            </div>
            '
                    ],
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

            'MV_FIRST_STATUS' => Configuration::get('MV_FIRST_STATUS'),
            'MV_AVAILABLE_STATUS' => Configuration::get('MV_AVAILABLE_STATUS'),
            'MV_OUT_OF_STOCK_STATUS' => Configuration::get('MV_OUT_OF_STOCK_STATUS'),
            'MV_DEFAULT_COMMISSION' => Configuration::get('MV_DEFAULT_COMMISSION', 10),
            'MV_AUTO_APPROVE_VENDORS' => Configuration::get('MV_AUTO_APPROVE_VENDORS', 0),
            'mv_pickup' => Configuration::get('mv_pickup'),
            'mv_returns' => Configuration::get('mv_returns'),
            'MULTIVENDOR_COLLECT_GROUP' => Configuration::get('MULTIVENDOR_COLLECT_GROUP'),
            'MULTIVENDOR_RECEIVE_GROUP' => Configuration::get('MULTIVENDOR_RECEIVE_GROUP'),
            'MULTIVENDOR_STATUS_COLLECTED' => Configuration::get('MULTIVENDOR_STATUS_COLLECTED'),
            'MULTIVENDOR_STATUS_RECEIVED' => Configuration::get('MULTIVENDOR_STATUS_RECEIVED'),
            'MULTIVENDOR_STATUS_REFUND_PROCESSING' => Configuration::get('MULTIVENDOR_STATUS_REFUND_PROCESSING'),
            'MULTIVENDOR_STATUS_REFUND_COLLECTED' => Configuration::get('MULTIVENDOR_STATUS_REFUND_COLLECTED'),
            'MANIFEST_PICKUP_DRAFT' => Configuration::get('MANIFEST_PICKUP_DRAFT'),
            'MANIFEST_RETURN_DRAFT' => Configuration::get('MANIFEST_RETURN_DRAFT'),
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
                'vendor_manifest_url' => $this->context->link->getModuleLink('multivendor', 'manifestmanager', []),
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

    public function hookActionObjectOrderUpdateAfter($params)
    {
        $order = $params['object'];

        if (!Validate::isLoadedObject($order)) {
            return;
        }

        try {
            PrestaShopLogger::addLog(
                'Multivendor: Order update detected for order #' . $order->id,
                1,
                null,
                'multivendor',
                $order->id
            );

            $this->processOrderUpdateForVendors($order);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor hookActionObjectOrderUpdateAfter error: ' . $e->getMessage(),
                3,
                null,
                'multivendor',
                $order->id
            );
        }
    }
    private function processOrderUpdateForVendors($order)
    {
        $orderDetails = $order->getOrderDetailList();

        foreach ($orderDetails as $orderDetail) {
            $orderDetailObj = new OrderDetail($orderDetail['id_order_detail']);

            if (!Validate::isLoadedObject($orderDetailObj)) {
                continue;
            }

            OrderHelper::updateOrderDetailForVendor($orderDetailObj);
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
            'product_commissions' => [
                'description' => 'Multi-vendor product commissions',
                'class' => 'ProductCommission',
                'specific_management' => false,
                'forbidden_method' => ['DELETE']
            ],
            'payments' => [
                'description' => 'Multi-vendor payments',
                'class' => 'VendorPayment',
                'specific_management' => false,
                'forbidden_method' => ['POST', 'PUT', 'DELETE']
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




    public function hookActionObjectAddAfter($params)
    {
        $entity = $params['object'];
        $className = get_class($entity);

        if (EntityLogHelper::isLoggable($className)) {
            AudityLog::setLogs($entity, 'add', []);
        }

        if (EntityLogHelper::ischildisLoggable($className)) {
        }
    }

    public function hookActionObjectUpdateBefore($params)
    {
        $object = $params['object'];
        $className = get_class($object);

        if (EntityLogHelper::isLoggable($className)) {
            $old_object = new $className($object->id);
            $key = $className . '_' . $object->id;
            $this->beforeData[$key] = $old_object->getFields();
        }
    }


    public function hookActionObjectDeleteAfter($params)
    {
        $entity = $params['object'];
        $className = get_class($entity);

        if (EntityLogHelper::isLoggable($className)) {
            AudityLog::setLogs($entity, 'delete', $entity->getFields());
        }
    }

    public function hookActionObjectUpdateAfter($params)
    {
        $object = $params['object'];
        $className = get_class($object);

        if (EntityLogHelper::isLoggable($className)) {
            $key = $className . '_' . $object->id;
            $before = isset($this->beforeData[$key]) ? $this->beforeData[$key] : [];
            if (!empty($before)) {
                AudityLog::setLogs($object, 'update', $before);
                unset($this->beforeData[$key]);
            }
        }
    }
}
