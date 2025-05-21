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
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

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


        // Pagination for transactions
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        // Get transactions with commission action details
        $transactions = VendorHelper::getTransactionsWithCommissionDetails($id_vendor, $per_page, $offset);
        $totalTransactions =VendorHelper::countTotalTransactions($id_vendor);
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

    

   
}
