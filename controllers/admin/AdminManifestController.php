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
        $this->row_hover = false;

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
                'remove_onclick' => true

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
            'total_amount' => [
                'title' => $this->l('Total Amount'),
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
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
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_type` mt ON (mt.id_manifest_type = a.id_manifest_type)
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_order_detail` vod ON (vod.id_order_detail = md.id_order_details)';

        $this->_select = '
        a.id_manifest as manifest,
            v.shop_name as shop_name,
            mst.name as status_name,
            a.id_manifest_status as status_display,
            count(md.id_manifest_details) as total_items,
            mt.name as type_name,
            COALESCE(SUM(vod.vendor_amount * vod.product_quantity), 0) as total_amount';

        $this->_group = 'GROUP BY a.id_manifest';
        $this->bulk_actions = array(
            'merge' => array(
                'text' => $this->l('Generer paiement'),
                'confirm' => $this->l('Êtes-vous sûr de vouloir fusionner les manifestes sélectionnés en un paiement ?'),
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


        $filters['id_vendor'] = $vendorId;

        $details = OrderHelper::getVendorOrderDetails($vendorId, $filters);


        $this->context->smarty->assign([
            'orderStatuses' => $orderStatuses,
            'selected_ids' => $selected_ids,
            'order_details' => $this->object->id ? $details : [],
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
            $_POST['reference'] = Manifest::generateReference(
                (int)Tools::getValue('id_vendor'),
                (int)Tools::getValue('id_manifest_type')
            );
        }
        $result = parent::processSave();

        if ($this->object->id) {
            $manifest = new Manifest($this->object->id);
            if ((int)Tools::getValue('id_manifest_status')) {
                $manifest->id_manifest_status = (int)Tools::getValue('id_manifest_status');
            }
            $manifest->save();
        }


        $selectedOrderDetails = Tools::getValue('selected_order_details');
        $orderDetailIds = array_map('intval', explode(',', $selectedOrderDetails));

        $old_ids = ManifestDetails::getOrderDetailsByManifest($this->object->id);
        $new_ids = $orderDetailIds;
        $ids_to_add = array_diff($new_ids, $old_ids);
        $ids_to_remove = array_diff($old_ids, $new_ids);
        if ($ids_to_add) {
            foreach ($ids_to_add as $id_order_detail) {
                $this->addOrderDetailToManifest($result->id, $id_order_detail);
            }
        }
        if ($ids_to_remove) {
            foreach ($ids_to_remove as $id_order_detail) {
                $this->removeOrderDetail($result->id, $id_order_detail);
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
     * Remove order detail from manifest
     */
    private function removeOrderDetail($id_manifest, $id_order_detail)
    {
        $manifest = new Manifest($id_manifest);
        return $manifest->removeOrderDetail($id_order_detail);
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
            $manifest->save();
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
            if (!Tools::getValue('manifestBox')) {
                $this->errors[] = 'Veuillez sélectionner au moins un manifeste à fusionner.';
                return false;
            }
            try {
                $this->mergeManifestIntoPayment(Tools::getValue('manifestBox'));
                $this->confirmations[] = 'La fusion a été effectuée avec succès pour les manifestes : ' . implode(', ', Tools::getValue('manifestBox'));
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        if (Tools::isSubmit('submitUpdateManifestStatus')) {
            $this->processsubmitUpdateManifestStatus();
        }

        return parent::postProcess();
    }


    public function mergeManifestIntoPayment(array $manifestIds)
    {
        $validatedManifests = $this->validateManifests($manifestIds);
        $allTransactions = $this->collectTransactions($validatedManifests);
        $this->validateTransactionRequirements($allTransactions);

        $vendorId = $validatedManifests[0]['vendor_id'];
        $reference = $this->buildReference($manifestIds);
        $totalAmount = array_sum(array_column($allTransactions, 'vendor_amount'));

        $paymentId = $this->createPayment($vendorId, $totalAmount, $reference);
        $this->linkTransactionsToPayment($allTransactions, $paymentId);

        return $paymentId;
    }

    private function validateManifests(array $manifestIds)
    {
        if (empty($manifestIds)) {
            throw new Exception('Aucun manifeste fourni pour la fusion.');
        }

        $manifests = [];
        $vendorIds = [];

        foreach ($manifestIds as $id_manifest) {
            $manifest = new Manifest((int)$id_manifest);
            if (!$manifest->id) {
                throw new Exception("Manifeste introuvable (ID: {$id_manifest}).");
            }

            $orderDetailIds = array_column(Manifest::getOrderdetailsIDs($id_manifest), 'id_order_details');
            if (empty($orderDetailIds)) {
                throw new Exception("Aucune ligne de commande trouvée pour le manifeste (ID: {$id_manifest}).");
            }

            $manifests[] = [
                'id' => $id_manifest,
                'vendor_id' => $manifest->id_vendor,
                'transaction_type' => $manifest->getTransactionType(),
                'order_detail_ids' => $orderDetailIds
            ];

            $vendorIds[] = $manifest->id_vendor;
        }

        if (count(array_unique($vendorIds)) !== 1) {
            throw new Exception('Tous les manifestes doivent appartenir au même fournisseur.');
        }

        return $manifests;
    }

    private function collectTransactions(array $manifests)
    {
        $allTransactions = [];

        foreach ($manifests as $manifest) {
            $manifestTransactions = TransactionHelper::getAvailableTransaction(
                $manifest['order_detail_ids'],
                $manifest['transaction_type']
            );

            if (empty($manifestTransactions)) {
                throw new Exception("Aucune transaction disponible pour le manifeste (ID: {$manifest['id']}).");
            }

            $allTransactions = array_merge($allTransactions, $manifestTransactions);
        }

        return $allTransactions;
    }

    private function validateTransactionRequirements(array $transactions)
    {
        if (empty($transactions)) {
            throw new Exception('Aucune transaction valide trouvée pour la fusion.');
        }
    }

    private function buildReference(array $manifestIds)
    {
        return 'M-' . implode('-', $manifestIds);
    }

    private function createPayment($vendorId, $totalAmount, $reference)
    {
        $vendorPaymentObj = new VendorPayment();
        $vendorPaymentObj->id_vendor = $vendorId;
        $vendorPaymentObj->amount = $totalAmount;
        $vendorPaymentObj->reference = $reference;
        $vendorPaymentObj->status = 'pending';

        if (!$vendorPaymentObj->save()) {
            throw new Exception("Échec de l'enregistrement du paiement du fournisseur.");
        }

        return $vendorPaymentObj->id;
    }

    private function linkTransactionsToPayment(array $transactions, $paymentId)
    {
        foreach ($transactions as $transaction) {
            $transactionObj = new VendorTransaction($transaction['id_vendor_transaction']);

            if (!$transactionObj->id) {
                throw new Exception("Transaction introuvable (ID: {$transaction['id_vendor_transaction']}).");
            }

            if ($transactionObj->id_vendor_payment > 0) {
                throw new Exception(
                    "La transaction {$transactionObj->id} est déjà associée au paiement #{$transactionObj->id_vendor_payment}."
                );
            }

            $transactionObj->id_vendor_payment = $paymentId;

            if (!$transactionObj->save()) {
                throw new Exception("Impossible de mettre à jour la transaction (ID: {$transactionObj->id}).");
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
        $currentManifestId = (int)Tools::getValue('id_manifest') ? (int)Tools::getValue('id_manifest') : null;

        $filters = Tools::getValue('filters', []);
        $filters['allowed_order_line_status_types'] =  $AllowedOrderLineStatusTypes;

        $filters['id_vendor'] = $vendorId;

        $details = OrderHelper::getVendorOrderDetails($vendorId, $filters);

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
            'id_manifest_type' => $manifesTypeId,
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

    public function ajaxProcessGetManifestDetails()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');

        if (!$id_manifest) {
            die(json_encode(['success' => false, 'message' => 'Invalid manifest ID']));
        }

        try {
            $manifest = new Manifest($id_manifest);

            if (!Validate::isLoadedObject($manifest)) {
                die(json_encode(['success' => false, 'message' => 'Manifest not found']));
            }

            $id_vendor = $manifest->id_vendor;
            $vendor = $manifest->getVendorByManifest();
            $filters['manifest'] = $id_manifest;
            $details = OrderHelper::getVendorOrderDetails($id_vendor, $filters);

            $address = new Address($manifest->id_address);
            $addressFormatted = AddressFormat::generateAddress($address, [], '<br>', ' ');

            $manifestType = $manifest->getManifestType();
            $manifestStatus = Manifest::getStatus($id_manifest);

            // Calculate totals
            $totalQuantity = 0;
            $totalAmount = 0;
            foreach ($details as $detail) {
                $totalQuantity += (int)$detail['product_quantity'];
                $totalAmount += (float)($detail['vendor_amount'] * $detail['product_quantity']);
            }

            die(json_encode([
                'success' => true,
                'manifest' => [
                    'id_manifest' => $manifest->id,
                    'reference' => $manifest->reference,
                    'vendor_name' => $vendor['shop_name'],
                    'type' => $manifestType,
                    'status' => $manifestStatus,
                    'address' => $addressFormatted,
                    'date_add' => $manifest->date_add,
                    'date_upd' => $manifest->date_upd,
                    'total_items' => count($details),
                    'total_quantity' => $totalQuantity,
                    'total_amount' => $totalAmount
                ],
                'order_details' => $details
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
}
