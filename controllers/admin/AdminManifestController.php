<?php
/**
 * Class AdminManifestController
 * 
 * Handles manifest management operations in vendor dashboard
 */
class AdminManifestController extends ModuleAdminController
{
    use VendorInterfaceTrait;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_manifest';
        $this->className = 'Manifest';
        $this->identifier = 'id_manifest';
        $this->lang = false;

        parent::__construct();

        if (!$this->initVendorContext()) {
            Tools::redirect($this->context->link->getAdminLink('AdminLogin'));
        }
    }

    /**
     * Init page header toolbar
     */
    public function initPageHeaderToolbar(): void
    {
        $this->page_header_toolbar_title = $this->l('Manifest Management');
        $this->page_header_toolbar_btn['new_manifest'] = [
            'href' => self::$currentIndex . '&action=new&token=' . $this->token,
            'desc' => $this->l('Create New Manifest'),
            'icon' => 'process-icon-new'
        ];

        parent::initPageHeaderToolbar();
    }

    /**
     * Render manifest management interface
     */
    public function renderView(): string
    {
        $this->context->smarty->assign([
            'vendor_id' => $this->vendorId,
            'controller_url' => self::$currentIndex . '&token=' . $this->token,
            'available_status_types' => $this->getVendorOrderLineStatusTypes(),
            'saved_manifests' => $this->getVendorManifests(10),
            'vendor_info' => $this->getVendorShopInfo()
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/manifest_management.tpl'
        );
    }

    /**
     * AJAX: Get available order details for manifest
     */
    public function ajaxProcessGetAvailableOrderDetails(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $id_status_type = (int)Tools::getValue('id_status_type');
        
        if (!$id_status_type) {
            $this->ajaxError('Invalid status type');
        }

        $orderDetails = $this->getAvailableOrderDetails($id_status_type);
        
        $this->ajaxSuccess($orderDetails);
    }

    /**
     * AJAX: Get manifest details
     */
    public function ajaxProcessGetManifestDetails(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $id_manifest = (int)Tools::getValue('id_manifest');
        
        if (!$id_manifest) {
            $this->ajaxError('Invalid manifest ID');
        }

        $manifestData = $this->loadVendorManifest($id_manifest);
        
        if (!$manifestData) {
            $this->ajaxError('Manifest not found');
        }

        $this->ajaxSuccess($manifestData);
    }

    /**
     * AJAX: Save manifest
     */
    public function ajaxProcessSaveManifest(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $orderDetailIds = Tools::getValue('order_detail_ids');
        $address_data = Tools::getValue('address_data');
        $id_status_type = (int)Tools::getValue('id_status_type');
        
        if (empty($orderDetailIds) || !$address_data) {
            $this->ajaxError('Missing required data');
        }

        if (!is_array($orderDetailIds)) {
            $orderDetailIds = json_decode($orderDetailIds, true);
        }

        $data = [
            'order_detail_ids' => $orderDetailIds,
            'address_data' => json_decode($address_data, true),
            'id_order_line_status_type' => $id_status_type,
            'status' => 'verified'
        ];

        $result = $this->createVendorManifest($data);
        
        if ($result['success']) {
            $this->logVendorActivity('manifest_saved', 'Manifest created: ' . $result['manifest']['reference']);
            $this->ajaxSuccess($result['manifest'], 'Manifest saved successfully');
        } else {
            $this->ajaxError(implode(', ', $result['errors']));
        }
    }

    /**
     * AJAX: Print manifest
     */
    public function ajaxProcessPrintManifest(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $orderDetailIds = Tools::getValue('order_detail_ids');
        $address_data = Tools::getValue('address_data');
        $id_status_type = (int)Tools::getValue('id_status_type');
        
        if (empty($orderDetailIds) || !$address_data) {
            $this->ajaxError('Missing required data');
        }

        if (!is_array($orderDetailIds)) {
            $orderDetailIds = json_decode($orderDetailIds, true);
        }

        $data = [
            'order_detail_ids' => $orderDetailIds,
            'address_data' => json_decode($address_data, true),
            'id_order_line_status_type' => $id_status_type,
            'status' => 'printed'
        ];

        $result = $this->createVendorManifest($data);
        
        if ($result['success']) {
            $pdfUrl = $this->getManifestPdfUrl($result['manifest']['id']);
            
            $this->logVendorActivity('manifest_printed', 'Manifest printed: ' . $result['manifest']['reference']);
            
            $this->ajaxSuccess([
                'pdf_url' => $pdfUrl,
                'manifest_id' => $result['manifest']['id'],
                'reference' => $result['manifest']['reference']
            ]);
        } else {
            $this->ajaxError(implode(', ', $result['errors']));
        }
    }

    /**
     * AJAX: Load manifest to workspace
     */
    public function ajaxProcessLoadManifest(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $id_manifest = (int)Tools::getValue('id_manifest');
        
        if (!$id_manifest) {
            $this->ajaxError('Invalid manifest ID');
        }

        $manifestData = $this->loadVendorManifest($id_manifest);
        
        if (!$manifestData) {
            $this->ajaxError('Manifest not found or access denied');
        }

        $this->logVendorActivity('manifest_loaded', 'Manifest loaded: ' . $manifestData['reference']);
        
        $this->ajaxSuccess($manifestData);
    }

    /**
     * AJAX: Get vendor addresses
     */
    public function ajaxProcessGetVendorAddresses(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $helper = ManifestHelper::getInstance();
        $addresses = $helper->getFormattedVendorAddresses($this->vendorId);
        
        $this->ajaxSuccess($addresses);
    }

    /**
     * AJAX: Delete manifest (draft only)
     */
    public function ajaxProcessDeleteManifest(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $id_manifest = (int)Tools::getValue('id_manifest');
        
        if (!$id_manifest || !$this->validateVendorAccess('manifest', $id_manifest)) {
            $this->ajaxError('Invalid manifest or access denied');
        }

        $manifest = new Manifest($id_manifest);
        
        if (!Validate::isLoadedObject($manifest)) {
            $this->ajaxError('Manifest not found');
        }

        if ($manifest->status !== 'draft') {
            $this->ajaxError('Can only delete draft manifests');
        }

        if ($manifest->delete()) {
            $this->logVendorActivity('manifest_deleted', 'Manifest deleted: ' . $manifest->reference);
            $this->ajaxSuccess([], 'Manifest deleted successfully');
        } else {
            $this->ajaxError('Failed to delete manifest');
        }
    }

    /**
     * AJAX: Update manifest status
     */
    public function ajaxProcessUpdateManifestStatus(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $id_manifest = (int)Tools::getValue('id_manifest');
        $new_status = Tools::getValue('new_status');
        
        if (!$id_manifest || !$new_status) {
            $this->ajaxError('Missing required parameters');
        }

        if (!$this->validateVendorAccess('manifest', $id_manifest)) {
            $this->ajaxError('Access denied');
        }

        $manifest = new Manifest($id_manifest);
        
        if (!Validate::isLoadedObject($manifest)) {
            $this->ajaxError('Manifest not found');
        }

        if (!$manifest->canEdit()) {
            $this->ajaxError('Manifest cannot be modified');
        }

        if ($manifest->updateStatus($new_status)) {
            $this->logVendorActivity(
                'manifest_status_updated', 
                "Manifest {$manifest->reference} status changed to {$new_status}"
            );
            
            $helper = ManifestHelper::getInstance();
            $manifestData = $helper->formatManifestForResponse($manifest);
            
            $this->ajaxSuccess($manifestData, 'Status updated successfully');
        } else {
            $this->ajaxError('Failed to update status');
        }
    }

    /**
     * AJAX: Get manifest statistics
     */
    public function ajaxProcessGetManifestStatistics(): void
    {
        if (!$this->validateAjaxRequest()) {
            $this->ajaxError('Invalid request');
        }

        $statistics = $this->getVendorManifestStatistics();
        
        $this->ajaxSuccess($statistics);
    }

    /**
     * Process page display
     */
    public function postProcess(): void
    {
        if (!$this->isVendorActive()) {
            $this->errors[] = $this->l('Your vendor account is not active');
            return;
        }

        parent::postProcess();
    }

    /**
     * Initialize content
     */
    public function initContent(): void
    {
        if (!$this->hasVendorPermission('view_manifest')) {
            $this->errors[] = $this->l('You do not have permission to access manifests');
            return;
        }

        $this->content = $this->renderView();
        
        $this->context->smarty->assign([
            'content' => $this->content,
            'show_page_header_toolbar' => $this->show_page_header_toolbar,
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn' => $this->page_header_toolbar_btn
        ]);
    }

    /**
     * Set media (CSS/JS)
     */
    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);
        
        $this->addCSS(_PS_MODULE_DIR_ . 'multivendor/views/css/manifest-admin.css');
        $this->addJS(_PS_MODULE_DIR_ . 'multivendor/views/js/manifest-admin.js');
        
        // Add localized JavaScript variables
        $this->addJqueryPlugin('jquery-ui');
        
        Media::addJsDef([
            'manifestControllerUrl' => self::$currentIndex . '&token=' . $this->token,
            'manifestTexts' => [
                'confirmDelete' => $this->l('Are you sure you want to delete this manifest?'),
                'errorOccurred' => $this->l('An error occurred. Please try again.'),
                'loadingText' => $this->l('Loading...'),
                'noItemsSelected' => $this->l('No items selected'),
                'selectStatusType' => $this->l('Please select a status type first'),
                'addressRequired' => $this->l('Please select or add an address')
            ]
        ]);
    }
}