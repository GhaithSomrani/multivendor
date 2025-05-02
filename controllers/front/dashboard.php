
<?php
/**
 * Vendor Dashboard controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class multivendorDashboardModuleFrontController extends ModuleFrontController
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
        $vendor = new Vendor($id_vendor);
        $this->context->smarty->assign('currency', $this->context->currency);

        // Get recent orders (last 5)
        $recentOrders = Vendor::getVendorOrders($id_vendor, 5, 0);

        // Get commission summary
        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor);

        // Get monthly sales data for chart
        $monthlySales = VendorOrderDetail::getVendorMonthlySales($id_vendor);

        // Get top selling products
        $topProducts = VendorOrderDetail::getVendorTopProducts($id_vendor, 5);

        // Assign data to template
        $this->context->smarty->assign([
            'vendor' => $vendor,
            'recent_orders' => $recentOrders,
            'commission_summary' => $commissionSummary,
            'monthly_sales' => $monthlySales,
            'top_products' => $topProducts,
            'shop_name' => $vendor->shop_name,
            'status' => $vendor->status,
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'currency_sign' => $this->context->currency->sign
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/dashboard.tpl');
    }
}
