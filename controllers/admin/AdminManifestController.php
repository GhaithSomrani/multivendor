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
            'shop_name' => [
                'title' => $this->l('Vendor'),
                'filter_key' => 'v!shop_name',
                'callback' => 'displayAddressName',
                'search' => true
            ],
            'status_name' => [
                'title' => $this->l('Status'),
                'filter_key' => 'mst!name',
                'callback' => 'displayStatusName',
                'type' => 'select',
                'filter_type' => 'select',
                'list' => $this->getAllManifestStatusesForFilter(),
                'filter_list' => $this->getAllManifestStatusesForFilter(),
                'search' => true
            ],

            'total_items' => [
                'title' => $this->l('Total Items'),
                'align' => 'center',
                'search' => true,
                'orderby' => true,
                'callback' => 'displayTotalItems'
            ],

            'date_add' => [
                'title' => $this->l('Date Add'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ],
            'date_upd' => [
                'title' => $this->l('Date Update'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!date_upd'
            ]
        ];


        $this->_join .= '
    LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)
    LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status_type` mst ON (mst.id_manifest_status_type = a.id_manifest_status)';



        $this->_select = '
   v.shop_name as shop_name,
   mst.name as status_name,
   a.id_manifest_status as status_display';
    }

    /**
     * Display address name callback
     */
    public function displayAddressName($value, $row)
    {
        return $value;
    }
    public function displayStatusName($value, $row)
    {
        if (!$value) {
            return $this->l('No Status');
        }

        return Tools::safeOutput($value);
    }
    private function getAllManifestStatusesForFilter()
    {
        $statuses = ManifestStatusType::getAllManifestStatusTypes();
        $filterList = [];

        foreach ($statuses as $status) {
            $filterList[$status['name']] = $status['name'];
        }

        return $filterList;
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
                    'disabled' => (bool)$this->object->id,

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
                    'label' => $this->l('Manifest Type'),
                    'name' => 'id_manifest_type',
                    'required' => true,
                    'disabled' => (bool)$this->object->id,
                    'options' => [
                        'query' => ManifestType::getAll(),
                        'id' => 'id',
                        'name' => 'name',
                        'default' => [
                            'value' => '',
                            'label' => $this->l('Select Manifest Type')
                        ],
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Status'),
                    'name' => 'id_manifest_status',
                    'required' => true,
                    'options' => [
                        'query' => $this->getManifestStatuses(),
                        'id' => 'id_manifest_status_type',
                        'name' => 'name',
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
                [
                    'type' => 'hidden',
                    'name' => 'selected_order_details'
                ],

            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];



        // Auto-generate reference if creating new manifest
        if (!$this->object->id) {
            $this->fields_value['reference'] = Manifest::generateReference();
        }


        $form = parent::renderForm();
        $form .= $this->renderVendorOrderDetailsTable();
        return $form;
    }

    protected function renderVendorOrderDetailsTable()
    {
        $statuses = OrderLineStatusType::getAllActiveStatusTypes();
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);
        $vendorId = (int)Tools::getValue('vendor');
        $isEditMode = $this->object->id > 0;
        $manifestId = $isEditMode ? $this->object->id : (int)Tools::getValue('id_manifest');

        $selected_ids = [];
        if ($isEditMode && $this->object->id) {
            $manifest = new Manifest($this->object->id);
            if (Validate::isLoadedObject($manifest)) {
                $vendorId = (int)$manifest->id_vendor;
                $selected_data = Manifest::getOrderdetailsIDs($manifest->id);
                $selected_ids = !empty($selected_data) ? array_column($selected_data, 'id_order_details') : [];
            }
        }

        $this->context->smarty->assign([
            'orderStatuses' => $orderStatuses,
            'selected_ids' => $selected_ids,
            'order_details' => [],
            'vendor_id' => $vendorId,
            'manifest_id' => $manifestId,
            'is_edit_mode' => $isEditMode,
            'statuses' => $statuses,
            'manifestAjaxUrl' => $this->context->link->getAdminLink('AdminManifest'),
        ]);


        return $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table.tpl');
    }

    /**
     * Get vendor for dropdown
     */
    private function getVendors()
    {

        return Vendor::getAllVendors();
    }

    private function getAddresses()
    {
        // In edit mode, use vendor from manifest object
        $vendorId = $this->object->id ? $this->object->id_vendor : (int)Tools::getValue('vendor');

        if (!$vendorId) {
            return [];
        }

        $vendor = new Vendor($vendorId);
        if (!Validate::isLoadedObject($vendor)) {
            return [];
        }

        $addresses = $vendor->getVendorAddress();
        $formatAddresses = [];

        if ($addresses) {
            foreach ($addresses as $addr) {
                $addressObj = new Address($addr['id_address']);
                $formatted = AddressFormat::generateAddress($addressObj, [], ' - ', ' ');
                $formatAddresses[] = [
                    'id_address' => $addr['id_address'],
                    'address_display' => $formatted
                ];
            }
        }

        return $formatAddresses;
    }

    private function getManifestStatuses()
    {

        $idManifestType = Tools::getValue('id_manifest_type', $this->object->id_manifest_type ?? 0);


        return ManifestStatusType::getManifestStatusByAllowedManifestType($idManifestType);
    }

    /**
     * Process save
     */
    public function processSave()
    {
        if (!Tools::getValue('reference')) {
            $_POST['reference'] = Manifest::generateReference();
        }

        $result = parent::processSave();

        if ($this->object->id) {
            $manifest = new Manifest($this->object->id);
            $manifest->clearOrderDetails();
            $manifest->id_manifest_status = (int)Tools::getValue('id_manifest_status');
            $manifest->update();
        }

        // Add selected order details
        $selectedOrderDetails = Tools::getValue('selected_order_details');
        if ($selectedOrderDetails) {
            $orderDetailIds = array_map('intval', explode(',', $selectedOrderDetails));
            foreach ($orderDetailIds as $id_order_detail) {
                $this->addOrderDetailToManifest($result->id, $id_order_detail);
            }
        }

        return $result;
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
        $id_vendor = $manifest->id_vendor;
        $vendor = $manifest->getVendorByManifest();
        $filters['manifest'] = (int)$this->object->id;
        $details = OrderHelper::getVendorOrderDetails($id_vendor, $filters);
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->l('The manifest cannot be found.');
            return false;
        }

        $address = new Address($manifest->id_address);

        $this->context->smarty->assign([
            'vendor_name' =>  $vendor['shop_name'],
            'manifest' => $manifest,
            'manifest_details' => $details,
            'manifestType' => $manifest->getManifestType(),
            'address' => $address,
            'total_items' => count($details),
            'back_url' => $this->context->link->getAdminLink('AdminManifest')
        ]);

        return $this->context->smarty->fetch($this->getTemplatePath() . 'view.tpl');
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
        if (Tools::isSubmit('printManifest')) {
            $id_manifest = (int)Tools::getValue('id_manifest');

            if ($id_manifest) {
                try {
                    Manifest::generatePrintablePDF($id_manifest);
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }
            return null;
        }

        return parent::postProcess();
    }

    public function ajaxProcessLoadVendorAddress()
    {
        $vendorId = (int)Tools::getValue('vendor_id');
        $currentAddressId = (int)Tools::getValue('current_address_id', 0);
        // Boolean test of isEditMode
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

        $addresses = $vendor->getVendorAddress();
        $formatAddresses = [];

        if ($addresses) {
            foreach ($addresses as $addr) {
                $addressObj = new Address($addr['id_address']);
                $formatted = AddressFormat::generateAddress($addressObj, [], ' - ', ' ');
                $formatAddresses[] = [
                    'id_address' => $addr['id_address'],
                    'address_display' => $formatted,
                    'selected' => ($addr['id_address'] == $currentAddressId)
                ];
            }
        }

        if (empty($addresses)) {
            die(json_encode([
                'success' => false,
                'message' => 'No address found for this vendor'
            ]));
        }

        die(json_encode([
            'success' => true,
            'addresses' => $formatAddresses,
            'current_address_id' => $currentAddressId
        ]));
    }
    protected function ajaxProcessLoadVendorOrderDetailsBody()
    {
        $vendorId = (int)Tools::getValue('vendor_id');
        $manifesTypeStatusId = (int)Tools::getValue('id_manifest_status');
        $manifesTypeId = (int)Tools::getValue('id_manifest_type');
        if (!$vendorId) {
            die(json_encode([
                'success' => false,
                'message' => 'Vendor ID is required'
            ]));
        }
        if (!$manifesTypeStatusId) {
            die(json_encode([
                'success' => false,
                'message' => 'Manifest Type ID is required'
            ]));
        }
        if (!$manifesTypeId) {
            die(json_encode([
                'success' => false,
                'message' => 'Manifest Type ID is required'
            ]));
        }



        $AllowedOrderLineStatusTypes = ManifestStatusType::getAllowedOrderLineStatusTypes($manifesTypeStatusId);

        $filters = Tools::getValue('filters', []);
        $filters['allowed_order_line_status_types'] =  $AllowedOrderLineStatusTypes;

        $filters['id_vendor'] = $vendorId;
        $details = OrderHelper::getVendorOrderDetails($vendorId, $filters);
        $currentManifestId = (int)Tools::getValue('id_manifest') ? (int)Tools::getValue('id_manifest') : null;
        $isEditMode = $currentManifestId > 0 ? true : false;

        $selected_ids = [];
        if ($isEditMode) {
            $selectedDetails = Manifest::getOrderdetailsIDs($currentManifestId);
            if (!empty($selectedDetails)) {
                $selected_ids = array_column($selectedDetails, 'id_order_details');
            }
        }
        if ($currentManifestId > 0) {
            $manifest = new Manifest($currentManifestId);
            if (Validate::isLoadedObject($manifest)) {
                $selected_data = Manifest::getOrderdetailsIDs($manifest->id);
                $selected_ids = !empty($selected_data) ? array_column($selected_data, 'id_order_details') : [];
            }
        }
        // Add checkbox states to each order detail
        foreach ($details as &$detail) {
            $checkboxState = Manifest::getOrderDetailCheckboxState(
                $detail['id_order_detail'],
                $currentManifestId,
                $manifesTypeId
            );
            $detail['checkbox_checked'] = $checkboxState['checked'];
            $detail['checkbox_disabled'] = $checkboxState['disabled'];
        }


        $this->context->smarty->assign([
            'manifest_id' => $currentManifestId,
            'is_edit_mode' => $isEditMode,
            'order_details' => $details,
            'selected_ids' => $selected_ids,
        ]);

        $html = $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table_body.tpl');

        die(json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($details)
        ]));
    }

    public function ajaxProcessPrintManifest()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');

        if (!$id_manifest) {
            die(json_encode(['success' => false, 'message' => 'Invalid manifest ID']));
        }

        try {
            Manifest::generatePrintablePDF($id_manifest);
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    public function ajaxProcessGetManifestStatusesByType()
    {
        $manifestType = (int)Tools::getValue('id_manifest_type');

        if (!$manifestType) {
            die(json_encode(['success' => false, 'message' => 'Invalid manifest type']));
        }

        $statuses = ManifestStatusType::getManifestStatusByAllowedManifestType($manifestType);

        die(json_encode([
            'success' => true,
            'statuses' => $statuses
        ]));
    }
}
