<?php

/**
 * Vendor Dashboard controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Include VendorHelper

class multivendorDashboardModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        parent::init();

        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if ((int)$vendor['status'] !== 1) {
            // Vendor is not active, show verification page
            $this->showVendorVerificationPage($vendor);
            return;
        }


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
        if (!$id_vendor) {
            return;
        }
        $vendor = new Vendor($id_vendor);
        $this->context->smarty->assign('currency', $this->context->currency);

        // Process date filter form if submitted
        $start_date = null;
        $end_date = null;
        $date_filter_active = false;
        $filter_label = $this->l('Tout le temps');
        $date_filter_type = 'this_month'; // Default filter type

        if (Tools::isSubmit('submitDateFilter')) {
            $date_filter_type = Tools::getValue('date_filter_type', 'all');

            switch ($date_filter_type) {
                case 'all':
                    // No date filtering, use all time
                    $filter_label = $this->l('Tout le temps');
                    break;

                case 'today':
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d');
                    $date_filter_active = true;
                    $filter_label = $this->l('Aujourd\'hui');
                    break;

                case 'yesterday':
                    $start_date = date('Y-m-d', strtotime('-1 day'));
                    $end_date = date('Y-m-d', strtotime('-1 day'));
                    $date_filter_active = true;
                    $filter_label = $this->l('Hier');
                    break;

                case 'this_week':
                    $start_date = date('Y-m-d', strtotime('monday this week'));
                    $end_date = date('Y-m-d');
                    $date_filter_active = true;
                    $filter_label = $this->l('Cette semaine');
                    break;

                case 'last_week':
                    $start_date = date('Y-m-d', strtotime('monday last week'));
                    $end_date = date('Y-m-d', strtotime('sunday last week'));
                    $date_filter_active = true;
                    $filter_label = $this->l('La semaine dernière');
                    break;

                case 'this_month':
                    $start_date = date('Y-m-01');
                    $end_date = date('Y-m-d');
                    $date_filter_active = true;
                    $filter_label = $this->l('Ce mois-ci');
                    break;

                case 'last_month':
                    $start_date = date('Y-m-01', strtotime('first day of last month'));
                    $end_date = date('Y-m-t', strtotime('last day of last month'));
                    $date_filter_active = true;
                    $filter_label = $this->l('Mois dernier');
                    break;

                case 'custom':
                    $custom_start = Tools::getValue('custom_start_date');
                    $custom_end = Tools::getValue('custom_end_date');

                    if (Validate::isDate($custom_start) && Validate::isDate($custom_end)) {
                        $start_date = $custom_start;
                        $end_date = $custom_end;
                        $date_filter_active = true;
                        $filter_label = sprintf($this->l('De %s à %s'), $custom_start, $custom_end);
                    }
                    break;
            }
        } else {
            // Default to "This Month" when no filter is selected
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            $date_filter_active = true;
            $filter_label = $this->l('Ce mois-ci');
        }

        $dashboardStats = VendorHelper::getDashboardStats($id_vendor, $start_date, $end_date);

        $recentOrderLines = VendorHelper::getOrderLinesByStatus($id_vendor, null, 5, 0);

        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor);

        $monthlySales = VendorHelper::getMonthlySales($id_vendor);

        $filteredDailySales = VendorHelper::getDailySales($id_vendor, $start_date, $end_date);

        $statusBreakdown = VendorHelper::getStatusBreakdown($id_vendor);

        $topProducts = $this->getTopSellingProducts($id_vendor, $start_date, $end_date, $date_filter_active, 5);

        $orderSummary = array_merge($dashboardStats, [
            'status_breakdown' => $statusBreakdown
        ]);

        $this->context->smarty->assign([
            'vendor' => $vendor,
            'recent_order_lines' => $recentOrderLines,
            'indicators' => $dashboardStats,
            'order_summary' => $orderSummary,
            'commission_summary' => $commissionSummary,
            'monthly_sales' => $monthlySales,
            'filtered_daily_sales' => $filteredDailySales,
            'top_products' => $topProducts,
            'shop_name' => $vendor->shop_name,
            'status' => $vendor->status,
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_manage_orders_url' => $this->context->link->getModuleLink('multivendor', 'manageorders', []),
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'currency_sign' => $this->context->currency->sign,
            'date_filter_active' => $date_filter_active,
            'filter_label' => $filter_label,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'current_date' => date('Y-m-d'),
            'date_filter_type' => $date_filter_type
        ]);

        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->setTemplate('module:multivendor/views/templates/front/dashboard.tpl');
    }

    /**
     * Get top selling products with date filter
     * 
     * @param int $id_vendor Vendor ID
     * @param string|null $start_date Start date (Y-m-d)
     * @param string|null $end_date End date (Y-m-d)
     * @param bool $date_filter_active Whether date filtering is active
     * @param int $limit Number of products to return
     * @return array Top selling products
     * 
     * Note: This method could be moved to VendorHelper in the future
     */
    protected function getTopSellingProducts($id_vendor, $start_date = null, $end_date = null, $date_filter_active = false, $limit = 5)
    {
        $query = '
            SELECT od.product_id, od.product_name, SUM(od.product_quantity) as quantity_sold,
                   SUM(vod.vendor_amount) as total_sales
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vod.id_order_detail
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor;

        // Apply date filters if active
        if ($date_filter_active && $start_date && $end_date) {
            $query .= ' AND DATE(o.date_add) BETWEEN "' . pSQL($start_date) . '" AND "' . pSQL($end_date) . '"';
        }

        $query .= ' GROUP BY od.product_id
                    ORDER BY quantity_sold DESC
                    LIMIT ' . (int)$limit;

        return Db::getInstance()->executeS($query);
    }

    /**
     * Show vendor verification page for inactive vendors
     */
    protected function showVendorVerificationPage($vendor)
    {
        $status_messages = [
            0 => $this->l('Your vendor account is pending approval. Please wait for admin verification.'),
            2 => $this->l('Your vendor account has been rejected. Please contact support for more information.')
        ];

        $status_titles = [
            0 => $this->l('Account Pending'),
            2 => $this->l('Account Rejected')
        ];

        $this->context->smarty->assign([
            'vendor' => $vendor,
            'status_message' => $status_messages[$vendor['status']] ?? $this->l('Account status unknown'),
            'status_title' => $status_titles[$vendor['status']] ?? $this->l('Account Status'),
            'is_pending' => (int)$vendor['status'] === 0,
            'is_rejected' => (int)$vendor['status'] === 2,
            'support_email' => Configuration::get('PS_SHOP_EMAIL'),
            'shop_name' => Configuration::get('PS_SHOP_NAME')
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/vendor_verification.tpl');
    }
}
