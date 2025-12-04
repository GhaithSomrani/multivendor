<?php

/**
 * Vendor Orders controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class multivendorOrdersModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;
    public $filter = [];
    public function init()
    {
        parent::init();

        $access_result = VendorHelper::validateVendorAccess($this->context->customer->id);

        if (!$access_result['has_access']) {
            if ($access_result['status'] === 'not_vendor') {
                Tools::redirect('index.php?controller=my-account');
            } else {
                // Redirect to dashboard which will show verification page
                Tools::redirect($this->context->link->getModuleLink('multivendor', 'dashboard'));
            }
        }

        $this->context->smarty->assign('id_vendor', $access_result['vendor']['id_vendor']);
    }

    public function initContent()
    {
        parent::initContent();

        // get ajax actions from template


        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_supplier = $this->context->smarty->getTemplateVars('id_supplier');
        // Get Vendor Address 
        $address_list = $this->getVendorAddress($id_vendor);

        $this->getVendorAddress($id_vendor);

        if (Tools::isSubmit('submitStatusUpdate')) {
            $this->processStatusUpdate();
        }
        $filter_status = Tools::getValue('status', '');

        $orderSummary = $this->getOrderSummary($id_vendor, $id_supplier);
        $filter = Tools::getValue('filter', []);
        $filter['status'] = $filter_status;
        $this->filter = $filter;
        $page = (int)Tools::getValue('page', 1);



        $per_page = (int)Tools::getValue('per_page', 25);
        $offset = ($page - 1) * $per_page;
        $filter_data = OrderHelper::getVendorOrderDetails($id_vendor, $filter, $per_page, $offset, true);
        $orderLines  = $filter_data['data'];
        $total_lines = $filter_data['total'];
        $total_pages = ceil($total_lines / $per_page);
        $changeableStatusInfo = $this->getChangeableStatusInfo($orderLines, $id_vendor);

        $vendorStatuses = [];
        $allStatuses = [];
        $status_colors = [];

        $vendorStatusTypes = OrderLineStatusType::getAllActiveStatusTypes(true);

        foreach ($vendorStatusTypes as $status) {
            $vendorStatuses[$status['id_order_line_status_type']] = $status['name'];
            $status_colors[$status['name']] = $status['color'];
        }

        $allStatusTypes = OrderLineStatusType::getAllActiveStatusTypes();
        foreach ($allStatusTypes as $status) {
            $allStatuses[$status['id_order_line_status_type']] = $status['name'];
            $status_colors[$status['name']] = $status['color'];
        }


        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/commissions.css');

        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/orders.js');

        Media::addJsDef([
            'ordersAjaxUrl' => $this->context->link->getModuleLink('multivendor', 'ajax'),
            'ordersAjaxLink' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'ordersAjaxToken' => Tools::getToken('multivendor')
        ]);

        $this->context->controller->registerStylesheet(
            'daterangepicker-css',
            'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css',
            [
                'media' => 'all',
                'priority' => 200,
                'server' => 'remote'
            ]
        );

        $this->context->controller->registerJavascript(
            'moment-js',
            'https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js',
            [
                'position' => 'head',
                'priority' => 200,
                'server' => 'remote'
            ]
        );

        $this->context->controller->registerJavascript(
            'module-drift-js',
            'https://unpkg.com/drift-zoom/dist/Drift.min.js',
            [
                'position' => 'head',
                'priority' => 200,
                'server' => 'remote'

            ]
        );

        $this->context->controller->registerStylesheet(
            'module-drift-css',
            'https://unpkg.com/drift-zoom/dist/drift-basic.min.css',
            [
                'media' => 'all',
                'priority' => 200,
                'server' => 'remote'

            ]
        );
        $this->context->controller->registerJavascript(
            'daterangepicker-js',
            'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',
            [
                'position' => 'bottom',
                'priority' => 201,
                'server' => 'remote'
            ]
        );
        $this->context->controller->registerStylesheet(
            'nouislider-css',
            'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css',
            [
                'media' => 'all',
                'priority' => 201,
                'server' => 'remote'
            ]
        );

        $this->context->controller->registerJavascript(
            'nouislider-js',
            'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js',
            [
                'position' => 'head',
                'priority' => 201,
                'server' => 'remote'
            ]
        );



        $available_status_vendor = new orderlineStatusType(Configuration::get('MV_AVAILABLE_STATUS'));
        $out_of_stock_status = new orderlineStatusType(Configuration::get('MV_OUT_OF_STOCK_STATUS'));
        // Assign data to template

        if (Tools::isSubmit('ajax')) {
            $action = Tools::getValue('action');
            switch ($action) {
                case 'getExportFilteredCSV':
                    $this->getExportFilteredCSV();
                    return;
                    break;
                default:
                    break;
            }
        }
        $this->context->smarty->assign([
            'filter' => $filter,
            'first_status' => Configuration::get('MV_FIRST_STATUS'),
            'available_status_vendor' => $available_status_vendor,
            'out_of_stock_status' => $out_of_stock_status,
            'addresses' => $address_list,
            'per_page' => $per_page,
            'filter_status' => $filter_status,
            'order_lines' => $orderLines,
            'order_summary' => $orderSummary,
            'vendor_statuses' => $vendorStatuses,
            'all_statuses' => $allStatuses,
            'status_colors' => $status_colors,
            'pages_nb' => $total_pages,
            'current_page' => $page,
            'changeable_info' => $changeableStatusInfo['changeable_info'],
            'allowed_status_transitions' => $changeableStatusInfo['allowed_transitions'],
            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/orders.tpl');
    }


    public function getExportFilteredCSV()
    {
        try {
            $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
            $filter = Tools::getValue('filter', '');
            $orderLines = OrderHelper::getVendorOrderDetails($id_vendor, $filter, null, null, true);
            $orderLines = array_map(function ($line) {
                $rm = Manifest::getManifestByOrderDetailAndType($line['id_order_detail'], 1);
                $rt = Manifest::getManifestByOrderDetailAndType($line['id_order_detail'], 2);
                $p = Vendorpayment::getByOrderDetailAndType($line['id_order_detail'], 'commission');
                $r = Vendorpayment::getByOrderDetailAndType($line['id_order_detail'], 'refund');
                return [
                    'id_order_detail' => $line['id_order_detail'],
                    'id_order' => $line['id_order'],
                    'designation' => $line['product_name'],
                    'reference produit' => $line['product_reference'],
                    'code barre' => $line['product_mpn'],
                    'quantite' => $line['product_quantity'],
                    'prix public' => $line['product_price'],
                    'montant commission' => $line['commission_amount'],
                    'montant fournisseur' => $line['vendor_amount'],
                    'date commande' => $line['order_date'],
                    'statut commande' => $line['line_status'],
                    'precentage commission' => $line['commission_rate'],
                    'action commission' => $line['commission_action'],
                    'bon de ramassage' => $rm['reference'] ?? '',
                    'date ramassage' => $rm['date_add'] ?? '',
                    'bon de retour' => $rt['reference'] ?? '',
                    'date retour' => $rt['date_add'] ?? '',
                    'paiement' => $p->reference ?? '',
                    'date paiement' => $p->date_add ?? '',
                    'paiement de remboursement' => $r->reference ?? '',
                    'date paiement de remboursement' => $r->date_add ?? '',
                ];
            }, $orderLines);

            if (empty($orderLines)) {
                die(json_encode(['success' => false, 'error' => 'No data found']));
            }



            $csv = $this->generateCSV($orderLines);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="vendor_orders_' . date('Y-m-d') . '.csv"');
            echo $csv;
            die();
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    private function generateCSV($data)
    {
        $output = fopen('php://temp', 'r+');

        // Add headers (adjust to your data structure)
        fputcsv($output, array_keys($data[0]));

        // Add rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
    protected function getVendorAddress($id_vendor)
    {
        $vendor = new Vendor($id_vendor);
        $addressses = $vendor->getVendorAddress();
        $addressList = [];
        foreach ($addressses  as  $address) {
            $vendorAddress = new Address($address['id_address']);
            $addressList[] = [
                'id_address' => $address['id_address'],
                'address' => AddressFormat::generateAddress($vendorAddress, [], ' - ', ' ')
            ];
        }
        return $addressList;
    }

    /**
     * Get vendor order lines (details) based on supplier ID
     * 
     * @param int $id_vendor Vendor ID
     * @param int $id_supplier Supplier ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array List of order line items
     */
    protected function getVendorOrderLines($id_vendor, $limit = 16, $offset = 0, $filters = [])
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();

        $query = new DbQuery();
        $query->select('
        vod.id_order_detail, 
        vod.product_name, 
        vod.product_reference, 
        vod.product_quantity, 
        vod.vendor_amount,
        o.reference as order_reference, 
        o.date_add as order_date, 
        vod.product_mpn, 
        od.unit_price_tax_incl as product_price,
        vod.id_vendor, 
        vod.commission_amount, 
        vod.id_order, 
        vod.product_id, 
        vod.product_attribute_id,
        ols.id_order_line_status_type as status_type_id,
        olst.name as line_status , 
        olst.color as status_color,
        vod.commission_rate , 
        olst.commission_action');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->innerJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        // Apply status filter only
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status_id = (int)$filters['status'];
            $query->where('(COALESCE(ols.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') = ' . $status_id . ')');
        }

        $query->orderBy('vod.id_order_detail DESC');
        $query->limit($limit, $offset);
        $rawStatus = Db::getInstance()->executeS($query);
        $filteredStatus = [];
        foreach ($rawStatus as $status) {
            if (!OrderHelper::isHideFromVendor($status['id_order_detail'])) {
                $filteredStatus[] = $status;
            }
        }
        return   $filteredStatus;
    }


    /**
     * Ajax function to get the list of product of the vendor 
     * 
     */




    /**
     * Get changeable status info for each order line
     * 
     * @param array $orderLines List of order lines
     * @param int $id_vendor Vendor ID
     * @return array Associative array with changeable info and allowed transitions
     */

    protected function getChangeableStatusInfo($orderLines, $id_vendor)
    {
        $changeableInfo = array();
        $allowedStatusTransitions = array();

        foreach ($orderLines as $line) {
            $id_order_detail = $line['id_order_detail'];
            $currentStatusId = $line['status_type_id'];

            // Check if this order line is changeable using your existing method
            $isChangeable = VendorHelper::isChangeable($id_order_detail, $id_vendor);
            $changeableInfo[$id_order_detail] = $isChangeable;

            // Get allowed status transitions for this order line
            $allowedStatuses = array();
            if ($isChangeable) {
                $allVendorStatuses = OrderLineStatusType::getAllActiveStatusTypes(true, false);

                foreach ($allVendorStatuses as $statusType) {
                    $statusId = $statusType['id_order_line_status_type'];
                    $statusName = $statusType['name'];

                    // Skip if it's the same as current status (optional)
                    if ($statusId == $currentStatusId) {
                        continue;
                    }

                    // Check if transition from current status to this status is allowed
                    if ($this->canChangeStatusTo($currentStatusId, $statusId)) {
                        $allowedStatuses[$statusId] = $statusName;
                    }
                }
            }
            $allowedStatusTransitions[$id_order_detail] = $allowedStatuses;
        }

        return array(
            'changeable_info' => $changeableInfo,
            'allowed_transitions' => $allowedStatusTransitions
        );
    }
    /**
     * Count vendor order lines based on supplier ID
     * 
     * @param int $id_vendor Vendor ID
     * @param int $id_supplier Supplier ID
     * @return int Number of order lines
     */
    protected function countVendorOrderLines($id_vendor, $filters = [])
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();
        $query = new DbQuery();
        $query->select('COUNT(vod.id_order_detail)');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->where('ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . ')');
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status_id = (int)$filters['status'];
            $query->where('(COALESCE(ols.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') = ' . $status_id . ')');
        }

        return (int)Db::getInstance()->getValue($query);
    }



    /**
     * Validate if status transition is allowed based on available_status column
     * 
     * @param int $from_status_id Current status ID
     * @param int $to_status_id Target status ID
     * @return bool True if transition is allowed
     */
    public static function canChangeStatusTo($from_status_id, $to_status_id)
    {
        // Get the current status type
        $query = new DbQuery();
        $query->select('available_status');
        $query->from('mv_order_line_status_type');
        $query->where('id_order_line_status_type = ' . (int)$from_status_id);
        $query->where('active = 1');

        $available_status = Db::getInstance()->getValue($query);

        // If no available_status is set, allow all transitions (backward compatibility)
        if (empty($available_status)) {
            return true;
        }

        // Check if target status is in the comma-separated list
        $allowed_statuses = explode(',', $available_status);
        $allowed_statuses = array_map('trim', $allowed_statuses);
        $allowed_statuses = array_map('intval', $allowed_statuses);

        return in_array((int)$to_status_id, $allowed_statuses);
    }

    /**
     * Process order line status update
     */
    protected function processStatusUpdate()
    {
        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_status_type = (int)Tools::getValue('id_status_type');
        $comment = Tools::getValue('comment');
        if (!VendorHelper::validateVendorOrderDetailAccess($id_order_detail, $id_vendor)) {
            $this->errors[] = $this->module->l('You do not have permission to update this order line.');
            return;
        }

        $vendorOrderDetail = VendorHelper::getVendorOrderDetailByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$vendorOrderDetail) {
            if (!$this->createVendorOrderDetail($id_order_detail, $id_vendor)) {
                $this->errors[] = $this->module->l('Failed to create vendor order detail.');
                return;
            }
        }

        // Update the status using the status type ID
        $success = OrderLineStatus::updateStatus(
            $id_order_detail,
            $id_vendor,
            $id_status_type,
            $this->context->customer->id,
            $comment,
            false
        );

        if ($success) {
            $this->success[] = $this->module->l('Order line status updated successfully.');
        } else {
            $this->errors[] = $this->module->l('Failed to update order line status.');
        }
    }

    /**
     * Create vendor order detail if it doesn't exist
     * This handles cases where order was placed before the vendor was associated
     */
    protected function createVendorOrderDetail($id_order_detail, $id_vendor)
    {
        $orderDetail = new OrderDetail($id_order_detail);

        if (!Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        $commission_rate = VendorCommission::getCommissionRate($id_vendor, $orderDetail->product_id);
        $product_price = $orderDetail->unit_price_tax_incl;
        $quantity = $orderDetail->product_quantity;
        $total_price = $product_price * $quantity;
        $commission_amount = $total_price * ($commission_rate / 100);
        $vendor_amount = $total_price - $commission_amount;

        $vendorOrderDetail = new VendorOrderDetail();
        $vendorOrderDetail->id_order_detail = $id_order_detail;
        $vendorOrderDetail->id_vendor = $id_vendor;
        $vendorOrderDetail->id_order = $orderDetail->id_order;
        $vendorOrderDetail->commission_rate = $commission_rate;
        $vendorOrderDetail->commission_amount = $commission_amount;
        $vendorOrderDetail->vendor_amount = $vendor_amount;
        $vendorOrderDetail->date_add = date('Y-m-d H:i:s');

        return $vendorOrderDetail->save();
    }



    /**
     * Get order summary data
     */

    protected function getOrderSummary($id_vendor)
    {
        // Get default status type for fallback
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        // Get hidden status types for vendor visibility filter
        $hiddenIdsString = OrderHelper::getHiddenStatusTypeString();
        $hiddenStatusFilter = ' AND (ols.id_order_line_status_type IS NULL OR ols.id_order_line_status_type NOT IN (' . $hiddenIdsString . '))';

        $totalLines = Db::getInstance()->getValue(
            '
        SELECT COUNT(DISTINCT vod.id_order_detail)
        FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        WHERE vod.id_vendor = ' . (int)$id_vendor .
                $hiddenStatusFilter
        );

        $totalRevenue = Db::getInstance()->getValue(
            '
        SELECT SUM(vod.vendor_amount)
        FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
        WHERE vod.id_vendor = ' . (int)$id_vendor . '
        AND DATE(o.date_add) >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
        AND (
            (olst.commission_action = "add" AND olst.id_order_line_status_type NOT IN (' .
                (!empty($hiddenIds) ? $hiddenIdsString : '0') . ')) 
            OR 
            (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" = "add"' .
                (!OrderHelper::isStatusTypeHiddenFromVendor($defaultStatusTypeId) ? '' : ' AND 1=0') . ')
        )' . $hiddenStatusFilter
        );

        // Get status breakdown (filtered for vendor visibility)
        $statusBreakdown = VendorHelper::getStatusBreakdown($id_vendor);

        // Today's orders (only count orders with visible statuses)
        $todaysOrders = Db::getInstance()->getValue(
            '
        SELECT COUNT(DISTINCT vod.id_order_detail)
        FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        WHERE vod.id_vendor = ' . (int)$id_vendor . '
        AND DATE(vod.date_add) = CURDATE()' .
                $hiddenStatusFilter
        );

        return [
            'total_lines' => (int)$totalLines,
            'total_revenue' => (float)$totalRevenue ?: 0.00,
            'todays_orders' => (int)$todaysOrders,
            'status_breakdown' => $statusBreakdown
        ];
    }





    public function processBulkUpdateVendorStatus()
    {
        $order_detail_ids = Tools::getValue('order_detail_ids', []);
        $id_status_type = (int)Tools::getValue('id_status_type');
        $comment = Tools::getValue('comment', 'Bulk status update');
        $id_customer = $this->context->customer->id;

        $result = VendorHelper::bulkUpdateVendorOrderLineStatus($id_customer, $order_detail_ids, $id_status_type, $comment);
        die(json_encode($result));
    }



    /**
     * Process get add commission status
     * Returns the first status that has commission_action = 'add' and is vendor allowed
     */
    public function processGetAddCommissionStatus()
    {
        try {
            $query = new DbQuery();
            $query->select('id_order_line_status_type, name, color, position');
            $query->from('mv_order_line_status_type');
            $query->where('commission_action = "add"');
            $query->where('is_vendor_allowed = 1');
            $query->where('active = 1');
            $query->orderBy('position ASC');

            $status = Db::getInstance()->getRow($query);

            if ($status) {
                die(json_encode([
                    'success' => true,
                    'status' => $status
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'No suitable status found'
                ]));
            }
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }
}
