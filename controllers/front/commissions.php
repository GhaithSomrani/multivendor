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

        // Get filters and sorting from URL - don't set defaults yet
        $filter = [
            'order_id' => Tools::getValue('order_id'),
            'product_name' => Tools::getValue('product_name'),
            'reference' => Tools::getValue('reference'),
            'amount_min' => Tools::getValue('amount_min'),
            'amount_max' => Tools::getValue('amount_max'),
            'commission_action' => Tools::getValue('commission_action'),
            'transaction_status' => Tools::getValue('transaction_status'),
            'line_status' => Tools::getValue('line_status'),
            'datefilter' => Tools::getValue('datefilter'),
            'payment_datefilter' => Tools::getValue('payment_datefilter'),
            'order_by' => Tools::getValue('order_by'),
            'order_way' => Tools::getValue('order_way'),
        ];

        // Remove empty filter values for cleaner URLs
        $filter = array_filter($filter, function ($value) {
            return $value !== '' && $value !== null && $value !== false;
        });

        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        // Get commission summary with filters
        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor, $filter);

        // Get transactions with filters and sorting (defaults will be applied in the function)
        $transactions = VendorHelper::getTransactionsWithCommissionDetails($id_vendor, $per_page, $offset, $filter);
        $totalTransactions = VendorHelper::countTotalTransactions($id_vendor, $filter);
        $totalPages = ceil($totalTransactions / $per_page);

        // Get payments
        $payments = VendorPayment::getVendorPaymentsWithDetails($id_vendor);

        try {
            $vendorCommissionRate = VendorCommission::getCommissionRate($id_vendor);
            $effectiveRate = $vendorCommissionRate;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error fetching commission rate: ' . $e->getMessage(), 3, null, 'MultivendorCommissionsModuleFrontController', $id_vendor);
        }

        // Add CSS/JS files
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/commissions.css');

        $this->context->controller->registerStylesheet('daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', ['media' => 'all', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('moment-js', 'https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js', ['position' => 'head', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('module-drift-js', 'https://unpkg.com/drift-zoom/dist/Drift.min.js', ['position' => 'head', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerStylesheet('module-drift-css', 'https://unpkg.com/drift-zoom/dist/drift-basic.min.css', ['media' => 'all', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['position' => 'bottom', 'priority' => 201, 'server' => 'remote']);

        // Assign to template
        $this->context->smarty->assign([
            'commission_summary' => $commissionSummary,
            'vendor_commission_rate' => $effectiveRate,
            'transactions' => $transactions,
            'payments' => $payments,
            'filter' => $filter,
            'pages_nb' => $totalPages,
            'current_page' => $page,
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'vendor_manifest_url' => $this->context->link->getModuleLink('multivendor', 'manifestmanager', []),
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
