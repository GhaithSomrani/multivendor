<?php

/**
 * Admin Vendor Order Details Controller - Complete with Export Card and Order Status
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminVendorOrderDetailsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_vendor_order_detail';
        $this->className = 'VendorOrderDetail';
        $this->lang = false;
        $this->identifier = 'id_vendor_order_detail';
        $this->_defaultOrderBy = 'id_vendor_order_detail';
        $this->_defaultOrderWay = 'DESC';
        $this->list_id = 'vendor_order_details';

        // Enable bulk actions for checkbox selection
        $this->bulk_actions = [
            'updateStatus' => [
                'text' => 'Mettre à jour le statut',
                'icon' => 'icon-refresh',
            ]
        ];

        // Disable add/edit/delete actions but keep view
        $this->addRowAction('view');
        $this->allow_export = true;
        $this->_use_found_rows = true;

        parent::__construct();

        $this->fields_list = [
            'id_vendor_order_detail' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'id_order' => [
                'title' => $this->l('ID Order'),
                'filter_key' => 'o!id_order',
                'havingFilter' => true,
                'callback' => 'displayOrderReference',
            ],
            'id_order_detail' => [
                'title' => $this->l('ID Détail '),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!id_order_detail'
            ],
            'vendor_name' => [
                'title' => $this->l('Vendeur'),
                'filter_key' => 'v!shop_name',
                'havingFilter' => true
            ],
            'product_name' => [
                'title' => $this->l('Nom'),
                'filter_key' => 'a!product_name',
                'maxlength' => 60
            ],
            'product_reference' => [
                'title' => $this->l('Référence produit'),
                'filter_key' => 'a!product_reference',
                'align' => 'center'
            ],
            'product_quantity' => [
                'title' => $this->l('QTÉ'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!product_quantity'
            ],

            'vendor_amount' => [
                'title' => $this->l('Montant vendeur'),
                'type' => 'price',
                'currency' => true,
                'callback' => 'displayVendorAmount'
            ],
            'payment_status' => [
                'title' => $this->l('Statut de paiement'),
                'callback' => 'displayPaymentStatus',
                'orderby' => false,
                'search' => false
            ],
            'order_status_name' => [
                'title' => $this->l('Statut de commande'),
                'type' => 'select',
                'list' => [],
                'filter_key' => 'osl!name',
                'havingFilter' => true,
                'callback' => 'displayOrderStatus'
            ],
            'name' => [
                'title' => $this->l('Statut ligne de commande'),
                'type' => 'select',
                'list' => [],
                'filter_key' => 'olst!name',
                'havingFilter' => true,
                'callback' => 'displayOrderLineStatus'
            ],
            'order_date' => [
                'title' => $this->l('Date de commande'),
                'type' => 'datetime',
                'filter_key' => 'o!date_add',
                'havingFilter' => true
            ]
        ];

        $this->populateStatusList();
        $this->populateOrderStatusList();

        $this->_select = '
            o.reference as order_reference,
            o.date_add as order_date,
            o.id_order,
            o.current_state as order_current_state,
            v.shop_name as vendor_name,
            olst.name as name,
            COALESCE(olst.color, "#777777") as status_color,
            vt.id_vendor_payment,
            vp.status as payment_status,
            vp.reference as payment_reference,
            osl.name as order_status_name,
            COALESCE(os.color, "#777777") as order_status_color
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = a.id_order)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status` ols ON (ols.id_order_detail = a.id_order_detail AND ols.id_vendor = a.id_vendor)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status_type` olst ON (olst.id_order_line_status_type = ols.id_order_line_status_type)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_transaction` vt ON (vt.order_detail_id = a.id_order_detail AND vt.transaction_type = "commission")
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_payment` vp ON (vp.id_vendor_payment = vt.id_vendor_payment)
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (osl.id_order_state = o.current_state AND osl.id_lang = ' . (int)$this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.id_order_state = o.current_state)
        ';
    }

    /**
     * Populate the order status list for the dropdown filter
     */
    protected function populateOrderStatusList()
    {
        $statuses = Db::getInstance()->executeS('
            SELECT DISTINCT osl.name
            FROM `' . _DB_PREFIX_ . 'order_state_lang` osl
            INNER JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.id_order_state = osl.id_order_state)
            WHERE osl.id_lang = ' . (int)$this->context->language->id . '
            AND os.deleted = 0
            ORDER BY osl.name ASC
        ');

        $statusList = [];
        foreach ($statuses as $status) {
            $statusList[$status['name']] = $status['name'];
        }

        $this->fields_list['order_status_name']['list'] = $statusList;
    }

    /**
     * Display order status with color
     */
    public function displayOrderStatus($status, $row)
    {
        if (!$status) {
            return '<span class="badge" style="background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 3px;">' .
                $this->l('Inconnu') . '</span>';
        }

        $color = isset($row['order_status_color']) ? $row['order_status_color'] : '#777777';
        return '<span class="badge" style="background-color: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px;">' .
            htmlspecialchars($status) . '</span>';
    }
    /**
     * Process bulk status update for selected checkboxes
     */
    public function ajaxProcessAjaxMassUpdateStatus()
    {
        // Security checks
        if (!$this->context->employee || !$this->context->employee->id) {
            die(json_encode(['success' => false, 'message' => 'Access denied: Admin access required']));
        }

        // Get parameters
        $id = (int)Tools::getValue('id');
        $id_new_status = (int)Tools::getValue('status_id');
        $comment = Tools::getValue('comment', 'Mise à jour AJAX');

        // Validation
        if (!$id || !$id_new_status) {
            die(json_encode([
                'success' => false,
                'message' => 'ID and Status ID are required'
            ]));
        }

        try {
            // Load the vendor order detail
            $vendorOrderDetail = new VendorOrderDetail((int)$id);
            if (!Validate::isLoadedObject($vendorOrderDetail)) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Vendor order detail not found for ID: ' . $id
                ]));
            }

            // Validate status type
            $statusType = new OrderLineStatusType($id_new_status);
            if (!Validate::isLoadedObject($statusType)) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Invalid status type ID: ' . $id_new_status
                ]));
            }

            // Use the existing VendorHelper function exactly as in processBulkUpdateStatus
            $result = VendorHelper::updateOrderLineStatusAsAdmin(
                $vendorOrderDetail->id_order_detail,
                $vendorOrderDetail->id_vendor,
                $id_new_status,
                Context::getContext()->employee->id,
                $comment
            );

            if ($result['success']) {
                // Return success with detailed information
                die(json_encode([
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'data' => [
                        'id' => $id,
                        'order_detail_id' => $vendorOrderDetail->id_order_detail,
                        'vendor_id' => $vendorOrderDetail->id_vendor,
                        'new_status' => $id_new_status,
                        'status_name' => $statusType->name,
                        'vendor_helper_message' => $result['message'] ?? 'Updated via VendorHelper'
                    ]
                ]));
            } else {
                // Return error from VendorHelper
                die(json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Unknown error from VendorHelper'
                ]));
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'AJAX Mass Update Error: ' . $e->getMessage(),
                3,
                null,
                'AdminVendorOrderDetails',
                $id
            );

            die(json_encode([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Render mass update panel using template
     */
    protected function renderMassUpdatePanel()
    {
        // Get all active order line status types
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes();

        // Get all vendors for filtering
        $vendors = Vendor::getAllVendors();

        // Assign variables to Smarty
        $this->context->smarty->assign([
            'status_types' => $statusTypes,
            'vendors' => $vendors,
            'current_index' => self::$currentIndex,
            'token' => $this->token
        ]);

        // Fetch and return the template
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'multivendor/views/templates/admin/panel/mass_update_panel.tpl');
    }

    /**
     * Process mass status update (original filter-based method)
     */
    protected function processMassUpdateStatus()
    {
        $id_vendor = (int)Tools::getValue('mass_vendor_id');
        $id_new_status = (int)Tools::getValue('mass_new_status');
        $comment = Tools::getValue('mass_comment', 'Mise à jour en masse');

        // Optional filters
        $id_current_status = (int)Tools::getValue('mass_current_status');
        $date_from = Tools::getValue('mass_date_from');
        $date_to = Tools::getValue('mass_date_to');
        $order_reference = Tools::getValue('mass_order_reference');

        // Validation
        if (empty($id_vendor) || empty($id_new_status)) {
            $this->errors[] = $this->l('Vendeur et nouveau statut sont requis.');
            return;
        }

        // Get vendor info
        $vendor = Vendor::getVendorById($id_vendor);
        if (!$vendor) {
            $this->errors[] = $this->l('Vendeur introuvable.');
            return;
        }

        // Get status type info
        $statusType = new OrderLineStatusType($id_new_status);
        if (!Validate::isLoadedObject($statusType)) {
            $this->errors[] = $this->l('Statut introuvable.');
            return;
        }

        try {
            // Build query to get matching order details
            $sql = 'SELECT DISTINCT vod.id_order_detail
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                INNER JOIN ' . _DB_PREFIX_ . 'order_detail od ON vod.id_order_detail = od.id_order_detail
                INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON (vod.id_order_detail = ols.id_order_detail AND vod.id_vendor = ols.id_vendor)
                WHERE vod.id_vendor = ' . (int)$id_vendor;

            // Add optional filters
            if ($id_current_status > 0) {
                $sql .= ' AND ols.id_order_line_status_type = ' . (int)$id_current_status;
            }

            if ($date_from) {
                $sql .= ' AND DATE(o.date_add) >= "' . pSQL($date_from) . '"';
            }

            if ($date_to) {
                $sql .= ' AND DATE(o.date_add) <= "' . pSQL($date_to) . '"';
            }

            if ($order_reference) {
                $sql .= ' AND o.reference LIKE "%' . pSQL($order_reference) . '%"';
            }

            $sql .= ' ORDER BY o.date_add DESC';

            $results = Db::getInstance()->executeS($sql);

            if (!$results || empty($results)) {
                $this->errors[] = $this->l('Aucun détail de commande trouvé avec les critères spécifiés.');
                return;
            }

            $success_count = 0;
            $error_count = 0;

            // Extract order detail IDs and update each one
            foreach ($results as $row) {
                $updateResult = VendorHelper::updateOrderLineStatusAsAdmin(
                    $row['id_order_detail'],
                    $id_vendor,
                    $id_new_status,
                    Context::getContext()->employee->id,
                    $comment
                );

                if ($updateResult['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }

            $this->confirmations[] = sprintf(
                $this->l('Mise à jour réussie : %d lignes mises à jour, %d erreurs.'),
                $success_count,
                $error_count
            );

            // Log the mass update
            PrestaShopLogger::addLog(
                sprintf(
                    'Mass status update: Vendor %d, Status %d, %d order details updated',
                    $id_vendor,
                    $id_new_status,
                    $success_count
                ),
                1,
                null,
                'AdminVendorOrderDetails',
                null,
                true
            );
        } catch (Exception $e) {
            $this->errors[] = $this->l('Erreur lors de la mise à jour en masse : ') . $e->getMessage();
            PrestaShopLogger::addLog(
                'Mass status update error: ' . $e->getMessage(),
                3,
                null,
                'AdminVendorOrderDetails'
            );
        }
    }

    /**
     * Display payment status
     */
    public function displayPaymentStatus($value, $row)
    {
        if (isset($row['id_vendor_payment']) && $row['id_vendor_payment'] > 0) {
            $status = isset($row['payment_status']) ? $row['payment_status'] : 'pending';
            $color = '';
            $text = '';

            switch ($status) {
                case 'completed':
                    $color = '#28a745';
                    $text = $this->l('Payé');
                    break;
                case 'pending':
                    $color = '#ffc107';
                    $text = $this->l('En attente');
                    break;
                case 'cancelled':
                    $color = '#dc3545';
                    $text = $this->l('Annulé');
                    break;
                default:
                    $color = '#6c757d';
                    $text = $this->l('Inconnu');
            }

            $payment_ref = isset($row['payment_reference']) ? $row['payment_reference'] : '';
            $title = $payment_ref ? 'title="Réf: ' . htmlspecialchars($payment_ref) . '"' : '';

            return '<span class="badge" style="background-color: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px;" ' . $title . '>' .
                htmlspecialchars($text) . '</span>';
        } else {
            return '<span class="badge" style="background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 3px;">' .
                $this->l('Non payé') . '</span>';
        }
    }


    /**
     * Render export card using template
     */
    protected function renderExportCard()
    {
        $vendors = Vendor::getAllVendors();
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes();
        $manifestTypes = ManifestType::getAll();
        $this->context->smarty->assign([
            'manifest_types' => $manifestTypes,
            'vendors' => $vendors,
            'status_types' => $statusTypes,
            'current_index' => self::$currentIndex,
            'token' => $this->token
        ]);

        // Fetch and return the template
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'multivendor/views/templates/admin/vendor_export_card.tpl');
    }

    /**
     * Override renderList to add mass update panel, export card and custom filter form
     */
    public function renderList()
    {
        // Add the mass update panel at the top
        $massUpdatePanel = $this->renderMassUpdatePanel();

        $exportCard = $this->renderExportCard();

        $content = parent::renderList();


        return  $exportCard  . $massUpdatePanel . $content;
    }

    /**
     * Process export filtered PDF
     */
    protected function processExportFilteredPDF()
    {
        $id_vendor = (int)Tools::getValue('export_vendor') > 0 ? (int)Tools::getValue('export_vendor') : null;
        $id_status_type = (int)Tools::getValue('export_status_type');
        $export_type = Tools::getValue('export_type');
        $orderDetailIds = $this->getFilteredOrderDetails($id_vendor, $id_status_type, $export_type);

        if (empty($orderDetailIds)) {
            $this->errors[] = $this->l('No order lines found with the specified criteria.');
            return;
        }
        Manifest::generateMulipleManifestPDF($orderDetailIds,$export_type, $id_vendor);
        // Generate PDF using same logic as pickup manifest
        // $this->generateFilteredManifest($orderDetailIds, $id_vendor, $export_type);
    }
    public function ajaxProcesseExportSelectedIds()
    {

        $ids = Tools::getValue('ids', []);

        $export_type = Tools::getValue('export_type');
        $orderDetailIds = [];
        foreach ($ids as $id) {
            $ordervendor =  new VendorOrderDetail((int)$id);
            if (Validate::isLoadedObject($ordervendor)) {
                $orderDetailIds[] = $ordervendor->id_order_detail;
            }
        }

        Manifest::generateMulipleManifestPDF($orderDetailIds,$export_type);
    }


    /**
     * Get filtered order details based on criteria
     */
    protected function getFilteredOrderDetails($id_vendor, $id_status_type, $export_type)
    {
        $sql = 'SELECT DISTINCT vod.id_order_detail
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            INNER JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON (vod.id_order_detail = ols.id_order_detail AND vod.id_vendor = ols.id_vendor)
            INNER JOIN ' . _DB_PREFIX_ . 'order_detail od ON vod.id_order_detail = od.id_order_detail
            INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
            WHERE ols.id_order_line_status_type = ' . (int)$id_status_type;
        if ($id_vendor > 0) {
            $sql .= ' AND vod.id_vendor = ' . (int)$id_vendor;
        }
        $sql .=  ' ORDER BY o.date_add DESC';



        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return [];
        }

        $orderDetailIds = [];
        foreach ($results as $row) {
            $orderDetailIds[] = (int)$row['id_order_detail'];
        }

        return $orderDetailIds;
    }



    /**
     * Handle POST actions including export and mass update
     */
    public function postProcess()
    {
        // Handle AJAX requests
        if (Tools::getValue('ajax')) {
            $action = Tools::getValue('action');

            switch ($action) {
                case 'ajaxMassUpdateStatus':
                    $this->ajaxProcessAjaxMassUpdateStatus();
                case 'exportSelectedIds':
                    $this->ajaxProcesseExportSelectedIds();
                    break;
            }

            // If we reach here, unknown AJAX action
            die(json_encode(['success' => false, 'message' => 'Unknown AJAX action']));
        }

        // Handle other POST actions
        if (Tools::getValue('action') === 'exportFilteredPDF') {
            $this->processExportFilteredPDF();
            return;
        }

        if (Tools::getValue('action') === 'massUpdateStatus') {
            $this->processMassUpdateStatus();
            return;
        }

        parent::postProcess();
    }

    /**
     * Display vendor amount
     */
    public function displayVendorAmount($amount, $row)
    {
        return Tools::displayPrice($amount, $this->context->currency);
    }

    /**
     * Populate the status list for the dropdown filter
     */
    protected function populateStatusList()
    {
        $statuses = OrderLineStatusType::getAllActiveStatusTypes();
        $statusList = [];

        foreach ($statuses as $status) {
            $statusList[$status['name']] = $status['name'];
        }

        $this->fields_list['name']['list'] = $statusList;
    }

    /**
     * Display order reference with link
     */
    public function displayOrderReference($reference, $row)
    {
        if (isset($row['id_order']) && $row['id_order']) {
            $orderLink = Context::getContext()->link->getAdminLink('AdminOrders', true, [], [
                'id_order' => (int)$row['id_order'],
                'vieworder' => 1
            ]);

            return '<a href="' . $orderLink . '" target="_blank" class="order-reference-link" title="' .
                $this->l('View Order Details') . '">' . htmlspecialchars($row['id_order']) . '</a>';
        }

        return htmlspecialchars($reference);
    }

    /**
     * Display order line status with color
     */
    public function displayOrderLineStatus($status, $row)
    {
        $color = isset($row['status_color']) ? $row['status_color'] : '#777777';
        return '<span class="badge" style="background-color: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px;">' .
            htmlspecialchars($status) . '</span>';
    }

    /**
     * Override the toolbar to remove add/edit/delete buttons
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        // Remove the "Add new" button
        unset($this->page_header_toolbar_btn['new']);

        // Add custom export button
        $this->page_header_toolbar_btn['export'] = [
            'href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('Export to CSV'),
            'icon' => 'process-icon-export'
        ];
    }

    /**
     * Override renderView to show detailed information
     */
    public function renderView()
    {
        $vendorOrderDetail = new VendorOrderDetail($this->id_object);

        if (!Validate::isLoadedObject($vendorOrderDetail)) {
            $this->errors[] = $this->l('Vendor order detail not found.');
            return false;
        }

        // Load order
        $order = new Order($vendorOrderDetail->id_order);

        // Load vendor
        $vendor_data = Vendor::getVendorById($vendorOrderDetail->id_vendor);
        $vendor = (object)[
            'id' => $vendor_data['id_vendor'],
            'shop_name' => $vendor_data['shop_name'],
            'id_supplier' => $vendor_data['id_supplier'],
            'status' => $vendor_data['status']
        ];

        // Load currency
        $currency = new Currency($order->id_currency);

        // Get current status
        $status_info = Db::getInstance()->getRow(
            '
        SELECT ols.*, olst.name as status_name, olst.color
        FROM ' . _DB_PREFIX_ . 'mv_order_line_status ols
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
        WHERE ols.id_order_detail = ' . (int)$vendorOrderDetail->id_order_detail . '
        AND ols.id_vendor = ' . (int)$vendorOrderDetail->id_vendor
        );

        // Get payment information
        $payment_info = Db::getInstance()->getRow(
            '
        SELECT vt.id_vendor_payment, vp.amount, vp.payment_method, vp.reference, vp.status, vp.date_add as payment_date
        FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_payment vp ON vp.id_vendor_payment = vt.id_vendor_payment
        WHERE vt.order_detail_id = ' . (int)$vendorOrderDetail->id_order_detail . '
        AND vt.transaction_type = "commission"
        ORDER BY vt.date_add DESC
       '
        );

        // Get status history
        $status_history = Db::getInstance()->executeS(
            '
        SELECT olsl.*, 
               old_st.name as old_status_name, old_st.color as old_status_color,
               new_st.name as new_status_name, new_st.color as new_status_color,
               e.firstname as changed_by_firstname, e.lastname as changed_by_lastname
        FROM ' . _DB_PREFIX_ . 'mv_order_line_status_log olsl
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type old_st ON old_st.id_order_line_status_type = olsl.old_id_order_line_status_type
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type new_st ON new_st.id_order_line_status_type = olsl.new_id_order_line_status_type
        LEFT JOIN ' . _DB_PREFIX_ . 'employee e ON e.id_employee = olsl.changed_by
        WHERE olsl.id_order_detail = ' . (int)$vendorOrderDetail->id_order_detail . '
        AND olsl.id_vendor = ' . (int)$vendorOrderDetail->id_vendor . '
        ORDER BY olsl.date_add DESC'
        );

        // Get order status information
        $order_status_info = Db::getInstance()->getRow(
            '
            SELECT osl.name as order_status_name, os.color as order_status_color
            FROM ' . _DB_PREFIX_ . 'order_state_lang osl
            LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON os.id_order_state = osl.id_order_state
            WHERE osl.id_order_state = ' . (int)$order->current_state . '
            AND osl.id_lang = ' . (int)$this->context->language->id
        );

        // Assign to template
        $this->context->smarty->assign([
            'vendor_order_detail' => $vendorOrderDetail,
            'order' => $order,
            'vendor' => (object)$vendor,
            'currency' => $currency,
            'status_info' => $status_info,
            'payment_info' => $payment_info,
            'status_history' => $status_history ?: [],
            'order_status_info' => $order_status_info
        ]);

        // Get the template content and return it directly
        $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/vendor_order_detail_view.tpl');

        return $this->content;
    }

    public function processDelete()
    {
        $this->errors[] = $this->l('Delete action is not allowed for this list.');
        return false;
    }

    /**
     * Disable bulk delete action
     */
    public function processBulkDelete()
    {
        $this->errors[] = $this->l('Bulk delete action is not allowed for this list.');
        return false;
    }
}
