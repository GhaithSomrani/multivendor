<?php

/**
 * Admin Vendor Order Details Controller - Complete with range filter
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

        // Define the fields for the list
        $this->fields_list = [
            'id_vendor_order_detail' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'order_reference' => [
                'title' => $this->l('Order Reference'),
                'filter_key' => 'o!reference',
                'havingFilter' => true,
                'callback' => 'displayOrderReference',
            ],
            'id_order_detail' => [
                'title' => $this->l('Order Detail ID'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!id_order_detail'
            ],
            'vendor_name' => [
                'title' => $this->l('Vendor'),
                'filter_key' => 'v!shop_name',
                'havingFilter' => true
            ],
            'product_name' => [
                'title' => $this->l('Product Name'),
                'filter_key' => 'a!product_name',
                'maxlength' => 60
            ],
            'product_reference' => [
                'title' => $this->l('Product SKU'),
                'filter_key' => 'a!product_reference',
                'align' => 'center'
            ],
            'product_quantity' => [
                'title' => $this->l('Quantity'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!product_quantity'
            ],
            'commission_rate' => [
                'title' => $this->l('Commission Rate (%)'),
                'type' => 'percentage',
                'align' => 'center',
                'filter_key' => 'a!commission_rate',
                'havingFilter' => true
            ],
            'commission_amount' => [
                'title' => $this->l('Commission Amount'),
                'type' => 'price',
                'currency' => true,
                'filter_key' => 'a!commission_amount',
                'havingFilter' => true,
            ],
            'vendor_amount' => [
                'title' => $this->l('Vendor Amount'),
                'type' => 'price',
                'currency' => true,
                'callback' => 'displayVendorAmount'
            ],
            'name' => [
                'title' => $this->l('Order Line Status'),
                'type' => 'select',
                'list' => [],
                'filter_key' => 'olst!name',
                'havingFilter' => true,
                'callback' => 'displayOrderLineStatus'
            ],
            'order_date' => [
                'title' => $this->l('Order Date'),
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
            COALESCE(olst.color, "#777777") as status_color
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = a.id_order)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status` ols ON (ols.id_order_detail = a.id_order_detail AND ols.id_vendor = a.id_vendor)
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status_type` olst ON (olst.id_order_line_status_type = ols.id_order_line_status_type)
        ';
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
     * Override renderList to add custom filter form
     */
    public function renderList()
    {
        $content = parent::renderList();
        
        // Add custom range filter form
        $vendor_amount_min = Tools::getValue('vendor_amount_min');
        $vendor_amount_max = Tools::getValue('vendor_amount_max');
        
        $filter_form = '
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-filter"></i> ' . $this->l('Vendor Amount Filter') . '
            </div>
            <div class="panel-body">
                <form method="get" class="form-inline" id="vendor-amount-filter">
                    <input type="hidden" name="controller" value="' . Tools::getValue('controller') . '" />
                    <input type="hidden" name="token" value="' . Tools::getValue('token') . '" />
                    
                    <div class="form-group" style="margin-right: 15px;">
                        <label for="vendor_amount_min" style="margin-right: 5px;">' . $this->l('Min Amount:') . '</label>
                        <input type="number" step="0.01" name="vendor_amount_min" id="vendor_amount_min"
                               value="' . htmlspecialchars($vendor_amount_min) . '" class="form-control" style="width: 120px;" />
                    </div>
                    
                    <div class="form-group" style="margin-right: 15px;">
                        <label for="vendor_amount_max" style="margin-right: 5px;">' . $this->l('Max Amount:') . '</label>
                        <input type="number" step="0.01" name="vendor_amount_max" id="vendor_amount_max"
                               value="' . htmlspecialchars($vendor_amount_max) . '" class="form-control" style="width: 120px;" />
                    </div>
                    
                    <button type="submit" class="btn btn-primary">' . $this->l('Filter') . '</button>
                    <a href="' . self::$currentIndex . '&token=' . $this->token . '" class="btn btn-default">' . $this->l('Reset') . '</a>
                </form>
            </div>
        </div>';
        
        return $filter_form . $content;
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

        // Get additional information
        $vendor = new Vendor($vendorOrderDetail->id_vendor);
        $order = new Order($vendorOrderDetail->id_order);
        $orderDetail = new OrderDetail($vendorOrderDetail->id_order_detail);

        // Get status information
        $statusInfo = VendorHelper::getOrderLineStatusByOrderDetailAndVendor(
            $vendorOrderDetail->id_order_detail,
            $vendorOrderDetail->id_vendor
        );

        // Get status history
        $statusHistory = OrderLineStatusLog::getStatusHistory($vendorOrderDetail->id_order_detail);

        $this->context->smarty->assign([
            'vendor_order_detail' => $vendorOrderDetail,
            'vendor' => $vendor,
            'order' => $order,
            'order_detail' => $orderDetail,
            'status_info' => $statusInfo,
            'status_history' => $statusHistory,
            'currency' => $this->context->currency
        ]);

        return $this->createTemplate('vendor_order_detail_view.tpl')->fetch();
    }

    /**
     * Disable delete action
     */
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