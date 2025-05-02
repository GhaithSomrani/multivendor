
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

        // Get transactions
        $transactions = VendorTransaction::getVendorTransactions($id_vendor, null, $per_page, $offset);
        $totalTransactions = count(VendorTransaction::getVendorTransactions($id_vendor));
        $totalPages = ceil($totalTransactions / $per_page);

        // Get payments
        $payments = VendorPayment::getVendorPayments($id_vendor, 5);

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
            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/commissions.tpl');
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
