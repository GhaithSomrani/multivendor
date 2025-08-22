<?php

/**
 * Admin Manifest Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminManifestController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_manifest';
        $this->className = 'Manifest';
        $this->lang = false;
        $this->identifier = 'id_manifest';
        $this->_defaultOrderBy = 'id_manifest';
        $this->_defaultOrderWay = 'DESC';
        $this->list_id = 'manifest';

        // Enable actions
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->allow_export = true;
        $this->_use_found_rows = true;


        parent::__construct();

        $this->fields_list = [
            'id_manifest' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'reference' => [
                'title' => $this->l('Reference'),
                'filter_key' => 'a!reference',
            ],
            'type' => [
                'title' => $this->l('Type'),
                'type' => 'select',
                'list' => [
                    Manifest::TYPE_PICKUP => $this->l('Pickup'),
                    Manifest::TYPE_RETURNS => $this->l('Returns')
                ],
                'filter_key' => 'a!type',
                'filter_type' => 'select',
                'filter_list' => [
                    Manifest::TYPE_PICKUP => $this->l('Pickup'),
                    Manifest::TYPE_RETURNS => $this->l('Returns')
                ]
            ],
            'address_name' => [
                'title' => $this->l('Address'),
                'filter_key' => 'v!shop_name',
                'callback' => 'displayAddressName',
                'search' => true
            ],
            'total_items' => [
                'title' => $this->l('Total Items'),
                'align' => 'center',
                'search' => true,
                'orderby' => true,
                'callback' => 'displayTotalItems'
            ],

            'add_date' => [
                'title' => $this->l('Date Add'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!add_date'
            ],
            'update_date' => [
                'title' => $this->l('Date Update'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!update_date'
            ]
        ];

        // $this->_join = '
        //     LEFT JOIN `' . _DB_PREFIX_ . 'address` ad ON (a.id_address = ad.id_address)';
        $this->_join .= '
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)';


        $this->_select = '
           v.shop_name as address_name,
            a.id_manifest_status as status_display';
    }

    /**
     * Display address name callback
     */
    public function displayAddressName($value, $row)
    {
        return $value;
    }

    /**
     * Display total items callback
     */
    public function displayTotalItems($value, $row)
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_details` 
                WHERE id_manifest = ' . (int)$row['id_manifest'];

        $count = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        return (int)$count;
    }
    /**
     * Render form for add/edit
     */
    public function renderForm()
    {

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Manifest'),
                'icon' => 'icon-list'
            ],
            'input' => [

                [
                    'type' => 'select',
                    'label' => $this->l('vendor'),
                    'name' => 'id_vendor',
                    'required' => true,
                    'options' => [
                        'query' => $this->getVendors(),
                        'id' => 'id_vendor',
                        'name' => 'shop_name',
                        'value' => (int)Tools::getValue('vendor'),
                        'default' => [
                            'value' => '',
                            'label' => $this->l('Select vendor')
                        ],
                    ],
                ],

                [
                    'type' => 'select',
                    'label' => $this->l('Type'),
                    'name' => 'type',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => Manifest::TYPE_PICKUP, 'name' => $this->l('Pickup')],
                            ['id' => Manifest::TYPE_RETURNS, 'name' => $this->l('Returns')]
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],

                [
                    'type' => 'select',
                    'label' => $this->l('Pick up Address'),
                    'name' => 'id_address',
                    'required' => true,
                    'options' => [
                        'query' => $this->getAddresses(),
                        'id' => 'id_address',
                        'name' => 'address_display'
                    ]
                ],

            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];



        // Auto-generate reference if creating new manifest
        if (!$this->object->id) {
            $this->fields_value['reference'] = Manifest::generateReference();
            $this->fields_value['id_manifest_status'] = 1;
        }


        $form = parent::renderForm();
        $form .= $this->renderVendorOrderDetailsTable();
        return $form;
    }





    protected function renderVendorOrderDetailsTable()
    {
        $selected_ids = [];

        // Get vendor ID from form data or URL parameter
        $vendorId = (int)Tools::getValue('vendor');

        // Check if we're in edit mode (object exists)
        $isEditMode = $this->object->id > 0;

        if ($isEditMode) {
            // In edit mode, get the vendor from the manifest object
            $manifest = new Manifest($this->object->id);
            if (Validate::isLoadedObject($manifest)) {
                $vendorId = $manifest->id_vendor;

                // Get selected order details for this manifest
                $selected_ids = Manifest::getOrderdetailsIDs($this->object->id);
                // Convert to simple array of IDs if needed
                if (!empty($selected_ids)) {
                    $selected_ids = array_column($selected_ids, 'id_order_detail');
                }
            }

            // If no vendor found in manifest, show error
            if (!$vendorId) {
                $this->context->smarty->assign([
                    'selected_ids' => [],
                    'order_details' => [],
                    'vendor_id' => 0,
                    'manifest_id' => $this->object->id,
                    'is_edit_mode' => true,
                    'show_vendor_selection_message' => false,
                    'show_no_orders_message' => true,
                    'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
                ]);
                return $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table.tpl');
            }
        } else {
            // New manifest - if no vendor selected, show message
            if (!$vendorId) {
                $this->context->smarty->assign([
                    'selected_ids' => [],
                    'order_details' => [],
                    'vendor_id' => 0,
                    'manifest_id' => 0,
                    'is_edit_mode' => false,
                    'show_vendor_selection_message' => true,
                    'show_no_orders_message' => false,
                    'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
                ]);
                return $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table.tpl');
            }
        }

        // Load order details for selected vendor
        $details = OrderHelper::getVendorOrderDetails($vendorId);

        if (empty($details)) {
            $this->context->smarty->assign([
                'selected_ids' => $selected_ids,
                'order_details' => [],
                'vendor_id' => $vendorId,
                'manifest_id' => $isEditMode ? $this->object->id : (int)Tools::getValue('id_manifest'),
                'is_edit_mode' => $isEditMode,
                'show_vendor_selection_message' => false,
                'show_no_orders_message' => true,
                'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
            ]);
        } else {
            $this->context->smarty->assign([
                'selected_ids' => $selected_ids,
                'order_details' => $details,
                'vendor_id' => $vendorId,
                'manifest_id' => $isEditMode ? $this->object->id : (int)Tools::getValue('id_manifest'),
                'is_edit_mode' => $isEditMode,
                'show_vendor_selection_message' => false,
                'show_no_orders_message' => false,
                'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
            ]);
        }

        return $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table.tpl');
    }
    /**
     * Get vendor for dropdown
     */
    private function getVendors()
    {

        return Vendor::getAllVendors();
    }



    /**
     * Get addresses for dropdown
     */

    private function getAddresses()
    {
        $vendor = new Vendor((int)Tools::getValue('vendor'));
        if (!Validate::isLoadedObject($vendor)) {
            return [];
        }
        $addresses = $vendor->getVendorAddress();
    }

    /**
     * Process save
     */
    public function processSave()
    {
        // Auto-generate reference if empty
        if (!Tools::getValue('reference')) {
            $_POST['reference'] = Manifest::generateReference();
        }

        if (Tools::getValue('selected_order_details')) {

            $orderDetailIds =   array_map('intval', explode(',', Tools::getValue('selected_order_details')));
        }

        $result = parent::processSave();

        foreach ($orderDetailIds as $id_order_detail) {
            $this->addOrderDetailToManifest($result->id, $id_order_detail);
        }
    }

    /**
     * Add order detail to manifest
     */
    private function addOrderDetailToManifest($id_manifest, $id_order_detail)
    {
        $manifest = new Manifest($id_manifest);
        return $manifest->addOrderDetail($id_order_detail);
    }
    /**
     * Render view page
     */
    public function renderView()
    {
        $manifest = new Manifest($this->object->id);
        $vendor = $manifest->getVendorByManifest();
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->l('The manifest cannot be found.');
            return false;
        }
        // Get manifest details
        $details = $this->getManifestDetails($manifest->id);
        $address = new Address($manifest->id_address);
        $this->context->smarty->assign([
            'vendor_name' =>  $vendor['shop_name'],
            'manifest' => $manifest,
            'manifest_details' => $details,
            'address' => $address,
            'total_items' => count($details),
            'back_url' => $this->context->link->getAdminLink('AdminManifest')
        ]);

        return $this->context->smarty->fetch($this->getTemplatePath() . 'view.tpl');
    }

    /**
     * Get manifest details with order information
     */
    private function getManifestDetails($id_manifest)
    {
        $sql = 'SELECT md.*, 
                       vod.product_name, 
                       vod.product_reference, 
                       vod.product_quantity,
                       vod.product_price,
                       o.reference as order_reference,
                       o.id_order,
                         v.shop_name as name
                FROM `' . _DB_PREFIX_ . 'mv_manifest_details` md
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_order_detail` vod ON (vod.id_order_detail = md.id_order_details)
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (vod.id_order = o.id_order)
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest` m ON (m.id_manifest =' . (int)$id_manifest . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (m.id_vendor = v.id_vendor)
                WHERE md.id_manifest = ' . (int)$id_manifest . '
                ORDER BY md.add_date ASC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }


    /**
     * Get template path
     */
    public function getTemplatePath()
    {
        return _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/manifest/';
    }

    /**
     * Set media (CSS/JS)
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');

        $this->addJS($this->module->getPathUri() . 'views/js/manifest_admin.js');

        $this->context->smarty->assign([
            'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
            'manifestToken' => Tools::getAdminTokenLite('AdminManifest')
        ]);

        Media::addJsDef([
            'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
            'manifestToken' => Tools::getAdminTokenLite('AdminManifest')
        ]);
    }

    /**
     * Initialize page header toolbar
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_manifest'] = [
                'href' => self::$currentIndex . '&addmv_manifest&token=' . $this->token,
                'desc' => $this->l('Add new manifest'),
                'icon' => 'process-icon-new'
            ];
        }

        parent::initPageHeaderToolbar();
    }


    /**
     * Post process - handle additional actions
     */
    public function postProcess()
    {

        if (Tools::isSubmit('removeOrderDetail')) {
            $this->processRemoveOrderDetail();
        }

        return parent::postProcess();
    }

    public function ajaxProcessLoadVendorAddress()
    {
        $vendorId = (int)Tools::getValue('vendor_id');

        if (!$vendorId) {
            die(json_encode([
                'success' => false,
                'message' => 'Vendor ID is required'
            ]));
        }

        $vendor = new Vendor($vendorId);
        if (!Validate::isLoadedObject($vendor)) {
            die(json_encode([
                'success' => false,
                'message' => 'Vendor not found'
            ]));
        }

        $address = $vendor->getVendorAddress();
        $formatAddress = [];
        if ($address) {
            foreach ($address as $addr) {
                $addressObj = new Address($addr['id_address']);
                $formatted = AddressFormat::generateAddress($addressObj, [], ' - ', ' ');
                $formatAddress[] = [
                    'id_address' => $addr['id_address'],
                    'address_display' => $formatted
                ];
            }
        }
        if (!$address) {
            die(json_encode([
                'success' => false,
                'message' => 'No address found for this vendor'
            ]));
        }

        die(json_encode([
            'success' => true,
            'address' => $formatAddress
        ]));
    }

    public function ajaxProcessLoadVendorOrderDetails()
    {
        $vendorId = (int)Tools::getValue('vendor_id');

        if (!$vendorId) {
            die(json_encode([
                'success' => false,
                'message' => 'Vendor ID is required'
            ]));
        }

        $details = OrderHelper::getVendorOrderDetails($vendorId);

        $this->context->smarty->assign([
            'order_details' => $details,
            'vendor_id' => $vendorId,
            'manifest_id' => (int)Tools::getValue('id_manifest'),
            'show_vendor_selection_message' => empty($details),
            'show_no_orders_message' => empty($details),
        ]);

        $html = $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table.tpl');

        die(json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($details)
        ]));
    }
    protected function ajaxProcessRemoveOrderDetail()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $id_order_detail = (int)Tools::getValue('id_order_detail');

        if (!$id_manifest || !$id_order_detail) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => $this->l('Invalid parameters')
            ]));
        }

        try {
            $manifest = new Manifest($id_manifest);
            if (!Validate::isLoadedObject($manifest)) {
                $this->ajaxDie(json_encode([
                    'success' => false,
                    'message' => $this->l('Manifest not found')
                ]));
            }

            if ($manifest->removeOrderDetail($id_order_detail)) {
                $this->ajaxDie(json_encode([
                    'success' => true,
                    'message' => $this->l('Order detail removed from manifest successfully')
                ]));
            } else {
                $this->ajaxDie(json_encode([
                    'success' => false,
                    'message' => $this->l('Error removing order detail from manifest')
                ]));
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Error removing order detail from manifest: ' . $e->getMessage(),
                3,
                null,
                'AdminManifest'
            );

            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => $this->l('Error removing order detail. Please try again.')
            ]));
        }
    }


    /**
     * Process remove order detail from manifest
     */
    private function processRemoveOrderDetail()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $id_order_detail = (int)Tools::getValue('id_order_detail');

        if (!$id_manifest || !$id_order_detail) {
            $this->errors[] = $this->l('Invalid parameters');
            return;
        }

        $manifest = new Manifest($id_manifest);
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->l('Manifest not found');
            return;
        }

        if ($manifest->removeOrderDetail($id_order_detail)) {
            $this->confirmations[] = $this->l('Order detail removed from manifest successfully');
        } else {
            $this->errors[] = $this->l('Error removing order detail from manifest');
        }
    }
}
