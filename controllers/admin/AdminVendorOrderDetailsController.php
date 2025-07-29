<?php

/**
 * Admin Vendor Order Details Controller - Complete with Export Card
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

        // Disable add/edit/delete actions
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
            'order_reference' => [
                'title' => $this->l('Référence de commande'),
                'filter_key' => 'o!reference',
                'havingFilter' => true,
                'callback' => 'displayOrderReference',
            ],
            'id_order_detail' => [
                'title' => $this->l('ID Détail de commande'),
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
                'title' => $this->l('Nom du produit'),
                'filter_key' => 'a!product_name',
                'maxlength' => 60
            ],
            'product_reference' => [
                'title' => $this->l('Référence produit'),
                'filter_key' => 'a!product_reference',
                'align' => 'center'
            ],
            'product_quantity' => [
                'title' => $this->l('Quantité'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!product_quantity'
            ],
            'commission_rate' => [
                'title' => $this->l('Taux de commission (%)'),
                'type' => 'percentage',
                'align' => 'center',
                'filter_key' => 'a!commission_rate',
                'havingFilter' => true
            ],
            'commission_amount' => [
                'title' => $this->l('Montant de commission'),
                'type' => 'price',
                'currency' => true,
                'filter_key' => 'a!commission_amount',
                'havingFilter' => true,
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

        $this->_select = '
            o.reference as order_reference,
            o.date_add as order_date,
            o.id_order,
            v.shop_name as vendor_name,
            olst.name as name,
            COALESCE(olst.color, "#777777") as status_color,
            vt.id_vendor_payment,
            vp.status as payment_status,
            vp.reference as payment_reference
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = a.id_order)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status` ols ON (ols.id_order_detail = a.id_order_detail AND ols.id_vendor = a.id_vendor)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status_type` olst ON (olst.id_order_line_status_type = ols.id_order_line_status_type)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_transaction` vt ON (vt.order_detail_id = a.id_order_detail AND vt.transaction_type = "commission")
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_payment` vp ON (vp.id_vendor_payment = vt.id_vendor_payment)
        ';
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
     * Override getList to add custom range filter
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        // Handle vendor amount range filter
        $vendor_amount_min = Tools::getValue('vendor_amount_min');
        $vendor_amount_max = Tools::getValue('vendor_amount_max');

        if ($vendor_amount_min !== false && $vendor_amount_min !== '') {
            $this->_where .= ' AND a.vendor_amount >= ' . (float)$vendor_amount_min;
        }

        if ($vendor_amount_max !== false && $vendor_amount_max !== '') {
            $this->_where .= ' AND a.vendor_amount <= ' . (float)$vendor_amount_max;
        }

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    /**
     * Render export card using template
     */
    protected function renderExportCard()
    {
        // Get all active vendors
        $vendors = Vendor::getAllVendors();

        // Get all active order line status types
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes();

        // Assign variables to Smarty
        $this->context->smarty->assign([
            'vendors' => $vendors,
            'status_types' => $statusTypes,
            'current_index' => self::$currentIndex,
            'token' => $this->token
        ]);

        // Fetch and return the template
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'multivendor/views/templates/admin/vendor_export_card.tpl');
    }

    /**
     * Override renderList to add export card and custom filter form
     */
    public function renderList()
    {
        // Add the export card at the top
        $exportCard = $this->renderExportCard();

        // Get the original list content
        $content = parent::renderList();

        // Add custom range filter form
        $vendor_amount_min = Tools::getValue('vendor_amount_min');
        $vendor_amount_max = Tools::getValue('vendor_amount_max');

        $filter_form = '
        <div class="panel">             
            <div class="panel-heading">                 
                <i class="icon-filter"></i> ' . $this->l('Filtre montant vendeur') . '             
            </div>             
            <div class="panel-body">                 
                <form method="get" class="form-inline" id="vendor-amount-filter">                     
                    <input type="hidden" name="controller" value="' . Tools::getValue('controller') . '" />                     
                    <input type="hidden" name="token" value="' . Tools::getValue('token') . '" />                                          
                    <div class="form-group" style="margin-right: 15px;">                         
                        <label for="vendor_amount_min" style="margin-right: 5px;">' . $this->l('Montant min :') . '</label>                         
                        <input type="number" step="0.01" name="vendor_amount_min" id="vendor_amount_min"                                
                            value="' . htmlspecialchars($vendor_amount_min) . '" class="form-control" style="width: 120px;" />                     
                    </div>                                          
                    <div class="form-group" style="margin-right: 15px;">                         
                        <label for="vendor_amount_max" style="margin-right: 5px;">' . $this->l('Montant max :') . '</label>                         
                        <input type="number" step="0.01" name="vendor_amount_max" id="vendor_amount_max"                                
                            value="' . htmlspecialchars($vendor_amount_max) . '" class="form-control" style="width: 120px;" />                     
                    </div>                                          
                    <button type="submit" class="btn btn-primary">' . $this->l('Filtrer') . '</button>                     
                    <a href="' . self::$currentIndex . '&token=' . $this->token . '" class="btn btn-default">' . $this->l('Réinitialiser') . '</a>                 
                </form>             
            </div>         
        </div>';

        // Combine them
        return $exportCard . $filter_form . $content;
    }

    /**
     * Process export filtered PDF
     */
    protected function processExportFilteredPDF()
    {
        $id_vendor = (int)Tools::getValue('export_vendor');
        $id_status_type = (int)Tools::getValue('export_status_type');
        $date_from = Tools::getValue('export_date_from');
        $date_to = Tools::getValue('export_date_to');
        $export_type = Tools::getValue('export_type');

        // Validation
        if (empty($id_vendor) || empty($id_status_type) || empty($date_from) || empty($date_to) || empty($export_type)) {
            $this->errors[] = $this->l('All fields are required for export.');
            return;
        }

        // Get filtered order details using same logic as pickup manifest
        $orderDetailIds = $this->getFilteredOrderDetails($id_vendor, $id_status_type, $date_from, $date_to, $export_type);

        if (empty($orderDetailIds)) {
            $this->errors[] = $this->l('No order lines found with the specified criteria.');
            return;
        }

        // Generate PDF using same logic as pickup manifest
        $this->generateFilteredManifest($orderDetailIds, $id_vendor, $export_type);
    }

    /**
     * Get filtered order details based on criteria
     */
    protected function getFilteredOrderDetails($id_vendor, $id_status_type, $date_from, $date_to, $export_type)
    {
        $sql = 'SELECT DISTINCT vod.id_order_detail
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            INNER JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON (vod.id_order_detail = ols.id_order_detail AND vod.id_vendor = ols.id_vendor)
            INNER JOIN ' . _DB_PREFIX_ . 'order_detail od ON vod.id_order_detail = od.id_order_detail
            INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND ols.id_order_line_status_type = ' . (int)$id_status_type . '
            AND DATE(o.date_add) >= "' . pSQL($date_from) . '"
            AND DATE(o.date_add) <= "' . pSQL($date_to) . '"
            ORDER BY o.date_add DESC';

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
     * Generate filtered manifest PDF
     */
    protected function generateFilteredManifest($orderDetailIds, $id_vendor, $export_type)
    {
        try {
            // Get vendor info (same as pickup manifest)
            $vendor = Vendor::getVendorById($id_vendor);

            if (!$vendor) {
                $this->errors[] = $this->l('Vendor not found.');
                return;
            }

            // Prepare PDF data (same structure as pickup manifest)
            $pdfData = [
                'orderDetailIds' => $orderDetailIds,
                'vendor' => $vendor,
                'export_type' => $export_type,
                'filename' => 'Export_' . $export_type . '_' . date('YmdHis') . '.pdf'
            ];

            // Use same PDF generation logic as pickup manifest
            $pdf = new PDF([$pdfData], 'VendorManifestPDF', Context::getContext()->smarty);
            $pdf->render(true);

            exit;
        } catch (Exception $e) {
            $this->errors[] = $this->l('Error generating PDF: ') . $e->getMessage();
            PrestaShopLogger::addLog('Export PDF Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
        }
    }

    /**
     * Handle POST actions including export
     */
    public function postProcess()
    {
        if (Tools::getValue('action') === 'exportFilteredPDF') {
            $this->processExportFilteredPDF();
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
                $this->l('View Order Details') . '">' . htmlspecialchars($reference) . '</a>';
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

        // Assign to template
        $this->context->smarty->assign([
            'vendor_order_detail' => $vendorOrderDetail,
            'order' => $order,
            'vendor' => (object)$vendor,
            'currency' => $currency,
            'status_info' => $status_info,
            'payment_info' => $payment_info,
            'status_history' => $status_history ?: []
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
