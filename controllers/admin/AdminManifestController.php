<?php

/**
 * Admin Manifest Controller
 */

use GuzzleHttp\Transaction;

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
            'type_name' => [
                'title' => $this->l('Type'),
                'filter_key' => 'mt!name',
                'type' => 'select',
                'filter_type' => 'select',
                'list' => $this->getAllManifestTypesForFilter(),
                'filter_list' => $this->getAllManifestTypesForFilter(),
                'search' => true
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
                'filter_key' => 'a!total_items',
                'title' => $this->l('Total Items'),
                'align' => 'center',
                'search' => false,
                'orderby' => false
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
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status_type` mst ON (mst.id_manifest_status_type = a.id_manifest_status) 
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_details` md ON (md.id_manifest = a.id_manifest) 
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_type` mt ON (mt.id_manifest_type = a.id_manifest_type)';

        $this->_select = '
        a.id_manifest as manifest,
            v.shop_name as shop_name,
            mst.name as status_name,
            a.id_manifest_status as status_display,
            count(md.id_manifest_details) as total_items,
            mt.name as type_name';

        $this->_group = 'GROUP BY a.id_manifest';
        $this->bulk_actions = array(
            'merge' => array(
                'text' => $this->l('merge'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Are you sure?'),
                'action' => 'merge'
            )
        );
        $this->addRowAction('view');
        $this->addRowAction('merge');
    }

    private function getAllManifestTypesForFilter()
    {
        $types = ManifestType::getAll();
        $filterList = [];

        foreach ($types as $type) {
            $filterList[$type['name']] = $type['name'];
        }

        return $filterList;
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
        $statuses = ManifestStatusType::getAllActive();
        $filterList = [];

        foreach ($statuses as $status) {
            $filterList[$status['name']] = $status['name'];
        }

        return $filterList;
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
                    'type' => 'hidden',
                    'label' => $this->l('Reference'),
                    'name' => 'reference',
                    'id' => 'reference',
                    'hidden' => true
                ],

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
                    'disabled' => (bool)$this->object->id,
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


        return $this->context->smarty->fetch($this->getTemplatePath() . 'vendor_order_details_table.tpl');
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

        $idManifestType = Tools::getValue('id_manifest_type', $this->object->id_manifest_type);


        return ManifestStatusType::getManifestStatusByAllowedManifestType($idManifestType);
    }

    protected function getNonModifedIds()
    {
        $query = new DbQuery();
        $query->select('id_manifest');
        $query->from('mv_manifest', 'ms');
        $query->leftJoin('mv_manifest_status_type', 'mst', 'mst.id_manifest_status_type = ms.id_manifest_status');
        $query->where('mst.allowed_modification = 0');
        $result = Db::getInstance()->executeS($query);
        return array_column($result, 'id_manifest');
    }
    protected function getNonDeleteIds()
    {
        $query = new DbQuery();
        $query->select('id_manifest');
        $query->from('mv_manifest', 'ms');
        $query->leftJoin('mv_manifest_status_type', 'mst', 'mst.id_manifest_status_type = ms.id_manifest_status');
        $query->where('mst.allowed_delete = 0');
        $result = Db::getInstance()->executeS($query);
        return array_column($result, 'id_manifest');
    }



    /**
     * Process save
     */
    public function processSave()
    {


        if (!Tools::getValue('reference')) {
            $_POST['reference'] = Manifest::generateReference($_POST['id_vendor'], $_POST['id_manifest_type']);
        }

        $result = parent::processSave();

        if ($this->object->id) {
            $manifest = new Manifest($this->object->id);
            $manifest->clearOrderDetails();
            if ((int)Tools::getValue('id_manifest_status')) {
                $manifest->id_manifest_status = (int)Tools::getValue('id_manifest_status');
            }
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

    public function processDelete()
    {
        $manifest = new Manifest(Tools::getValue('id_manifest'));
        $manifest->clearOrderDetails();
        return  parent::processDelete();
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
        $availableStatuses = ManifestStatusType::getAvailable($this->object->id_manifest_status);
        $this->context->smarty->assign([
            'available_statuses' => $availableStatuses,
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

    public function processsubmitUpdateManifestStatus()
    {
        try {
            $id_manifest = (int)Tools::getValue('id_manifest');
            $id_manifest_status = (int)Tools::getValue('id_manifest_status');
            $manifest = new Manifest($id_manifest);
            $manifest->id_manifest_status = $id_manifest_status;
            $manifest->update();
        } catch (Exception $e) {
            return $this->errors[] = $e->getMessage();
        }
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
        if (Tools::isSubmit('submitBulkmergemv_manifest')) {
            try {
                $this->mergeManifestIntoPayment(Tools::getValue('manifestBox'));
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        if (Tools::isSubmit('submitUpdateManifestStatus')) {
            $this->processsubmitUpdateManifestStatus();
        }

        return parent::postProcess();
    }


    public function mergeManifestIntoPayment($manifestIds)
    {
        $manifestsData = [];
        $totalAmount = 0;
        $reference = 'M';
        foreach ($manifestIds as $id_manifest) {
            $reference .=  '-' . $id_manifest;

            try {
                $manifest = new Manifest($id_manifest);
                $orderDetailIds = array_column(Manifest::getOrderdetailsIDs($id_manifest), 'id_order_details');
                $transactionType = $manifest->getTransactionType();
                $Amount = $this->getAmounts($orderDetailIds, $transactionType, $id_manifest);
                $manifestsData[] = [
                    'id_vendor' => $manifest->id_vendor,
                    'id_manifest' => $id_manifest,
                    'Manifest_type' => $manifest->id_manifest_type,
                    'orderDetailIds' => $orderDetailIds,
                    'amount' => $Amount,
                ];
                $totalAmount += $Amount;
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        $vendorArray = array_column($manifestsData, 'id_vendor');

        if (count(array_unique($vendorArray)) != 1) {
            throw new Exception('Vous ne pouvez fusionner que les manifestes du même fournisseur');
        }

        $vendorPaymentObj = new VendorPayment();
        $vendorPaymentObj->id_vendor = $vendorArray[0];
        $vendorPaymentObj->amount = $totalAmount;
        $vendorPaymentObj->reference = $reference;
        $vendorPaymentObj->status = 'pending';
        $vendorPaymentObj->save();

        $id_payment = $vendorPaymentObj->id;
        dump($id_payment);
        foreach ($manifestsData as $manifestData) {
            $manifest = new Manifest($manifestData['id_manifest']);
            $transactionType = $manifest->getTransactionType();
            $this->updateTransaction($manifestData['orderDetailIds'], $transactionType, $id_payment);
        }
    }
    private function getAmounts($orderDetailIds, $transactionType, $id_manifest): float
    {
        $totalAmount = 0;
        foreach ($orderDetailIds as $id_order_detail) {
            $transactionsRow = TransactionHelper::getExistingTransaction($id_order_detail, $transactionType);
            if (!$transactionsRow) {
                throw new Exception('Transaction non trouvée pour le détail de commande : ' . $id_order_detail . ' et le type de transaction : ' . $transactionType . ' dans le manifeste : ' . $id_manifest);
            }
            $totalAmount += (float)$transactionsRow['vendor_amount'];
        }
        return $totalAmount;
    }


    private function updateTransaction($orderDetailIds, $transactionType, $id_payment)
    {

        foreach ($orderDetailIds as $id_order_detail) {
            $transactionsRow = TransactionHelper::getExistingTransaction($id_order_detail, $transactionType);
            if ($transactionsRow['id_vendor_payment'] == 0) {
                $transactionsObj = new VendorTransaction($transactionsRow['id_vendor_transaction']);
                $transactionsObj->id_vendor_payment = (int)$id_payment;
                $transactionsObj->save();
            } else {
                throw new Exception('order detail ' . $id_order_detail . ' already has a payment :' . $transactionsRow['id_vendor_payment']);
            }
        }
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

        $html = $this->context->smarty->fetch($this->getTemplatePath() . 'vendor_order_details_table_body.tpl');

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
