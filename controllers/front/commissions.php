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

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');

        // Get commission summary
        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor);

        // Get commission rates
        try {
            $vendorCommissionRate = VendorCommission::getCommissionRate($id_vendor);
            $effectiveRate = $vendorCommissionRate;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error fetching commission rate: ' . $e->getMessage(), 3, null, 'MultivendorCommissionsModuleFrontController', $id_vendor);
        }


        // Pagination for transactions
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        // Get transactions with commission action details
        $transactions = VendorHelper::getTransactionsWithCommissionDetails($id_vendor, $per_page, $offset);
        $totalTransactions = VendorHelper::countTotalTransactions($id_vendor);
        $totalPages = ceil($totalTransactions / $per_page);

        // Get payments
        $payments = VendorPayment::getVendorPaymentsWithDetails($id_vendor);
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
