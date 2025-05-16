<?php

/**
 * Vendor Commissions controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorCommissionsModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        parent::init();

        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if (!$vendor) {
            Tools::redirect('index.php?controller=my-account');
        }

        // Set vendor ID for later use
        $this->context->smarty->assign('id_vendor', $vendor['id_vendor']);
    }

    public function initContent()
    {
        parent::initContent();

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');

        // Get commission summary
        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor);

        // Get commission rates
        $vendorCommissionRate = VendorCommission::getCommissionRate($id_vendor);
        $defaultCommissionRate = Configuration::get('MV_DEFAULT_COMMISSION', 10);
        $effectiveRate = $vendorCommissionRate !== null ? $vendorCommissionRate : $defaultCommissionRate;

        // Get category-specific commission rates
        $categoryCommissions = $this->getCategoryCommissions($id_vendor);

        // Pagination for transactions
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        // Get transactions with commission action details
        $transactions = $this->getTransactionsWithCommissionDetails($id_vendor, $per_page, $offset);
        $totalTransactions = $this->countTotalTransactions($id_vendor);
        $totalPages = ceil($totalTransactions / $per_page);

        // Get payments
        $payments = VendorPayment::getVendorPaymentsWithDetails($id_vendor, 5);
        // Add CSS file
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/commissions.css');

        // Register displayPrice modifier if needed
        if (!is_callable('smartyDisplayPrice')) {
            smartyRegisterFunction(
                $this->context->smarty,
                'modifier',
                'displayPrice',
                ['Tools', 'displayPrice']
            );
        }

        // Assign data to template
        $this->context->smarty->assign([
            'commission_summary' => $commissionSummary,
            'vendor_commission_rate' => $effectiveRate,
            'category_commissions' => $categoryCommissions,
            'transactions' => $transactions,
            'payments' => $payments,
            'pages_nb' => $totalPages,
            'current_page' => $page,
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_manage_orders_url' => $this->context->link->getModuleLink('multivendor', 'manageorders'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/commissions.tpl');
    }

    

    /**
     * Get transactions with commission action details
     * This method gets all vendor order details and determines their commission status
     * based on either existing order_line_status or the default status from order_line_status_type
     */
    protected function getTransactionsWithCommissionDetails($id_vendor, $limit = null, $offset = null)
    {
        // Get the default status and its commission action
        $defaultStatus = Db::getInstance()->getRow(
            '
            SELECT * FROM `' . _DB_PREFIX_ . 'order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC '
        );

        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_quantity, od.product_reference,
                       o.reference as order_reference, o.date_add as order_date,
                       vod.commission_amount, vod.vendor_amount, vod.id_order,
                       COALESCE(ols.status, "' . pSQL($defaultStatus['name']) . '") as line_status,
                       COALESCE(olst.commission_action, "' . pSQL($defaultStatus['commission_action']) . '") as commission_action,
                       COALESCE(olst.color, "' . pSQL($defaultStatus['color']) . '") as status_color');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('order_line_status_type', 'olst', 'olst.name = ols.status');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        // Filter to show only transactions with add or refund actions
        // Include records where either the status has these actions OR no status exists yet (will use default)
        $query->where('(
            (olst.commission_action IN ("add", "refund")) 
            OR 
            (ols.status IS NULL AND "' . pSQL($defaultStatus['commission_action']) . '" IN ("add", "refund"))
        )');

        $query->groupBy('od.id_order_detail');
        $query->orderBy('o.date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Count total transactions with commission actions
     */
    protected function countTotalTransactions($id_vendor)
    {
        // Get the default status and its commission action
        $defaultStatus = Db::getInstance()->getRow(
            '
            SELECT * FROM `' . _DB_PREFIX_ . 'order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC '
        );

        $query = new DbQuery();
        $query->select('COUNT(DISTINCT od.id_order_detail)');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->leftJoin('order_line_status_type', 'olst', 'olst.name = ols.status');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

        // Filter to count only transactions with add or refund actions
        $query->where('(
            (olst.commission_action IN ("add", "refund")) 
            OR 
            (ols.status IS NULL AND "' . pSQL($defaultStatus['commission_action']) . '" IN ("add", "refund"))
        )');

        return (int)Db::getInstance()->getValue($query);
    }

    /**
     * Get category-specific commission rates for a vendor
     */
    protected function getCategoryCommissions($id_vendor)
    {
        $query = new DbQuery();
        $query->select('cc.*, cl.name as category_name');
        $query->from('category_commission', 'cc');
        $query->leftJoin('category_lang', 'cl', 'cl.id_category = cc.id_category AND cl.id_lang = ' . (int)$this->context->language->id);
        $query->where('cc.id_vendor = ' . (int)$id_vendor);

        return Db::getInstance()->executeS($query);
    }
}
