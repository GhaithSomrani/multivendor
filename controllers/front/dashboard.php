
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
        $id_supplier = $vendor->id_supplier;
        $this->context->smarty->assign('currency', $this->context->currency);

        // Get updated indicators
        $indicators = $this->getVendorIndicators($id_vendor);

        // Get recent orders (last 5)
        $recentOrderLines = $this->getRecentOrderLines($id_vendor, $id_supplier, 5);

        // Get commission summary
        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor);

        // Get monthly sales data for chart
        $monthlySales = VendorOrderDetail::getVendorMonthlySales($id_vendor);

        // Get last 28 days sales data
        $last28DaysSales = $this->getLast28DaysSales($id_vendor);

        // Get top selling products
        $topProducts = VendorOrderDetail::getVendorTopProducts($id_vendor, 5);

        // Assign data to template
        $this->context->smarty->assign([
            'vendor' => $vendor,
            'recent_order_lines' => $recentOrderLines,
            'indicators' => $indicators,
            'commission_summary' => $commissionSummary,
            'monthly_sales' => $monthlySales,
            'last_28_days_sales' => $last28DaysSales,
            'top_products' => $topProducts,
            'shop_name' => $vendor->shop_name,
            'status' => $vendor->status,
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'currency_sign' => $this->context->currency->sign
        ]);
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->setTemplate('module:multivendor/views/templates/front/dashboard.tpl');
    }




    /**
     * Get vendor indicators
     */
    protected function getVendorIndicators($id_vendor)
    {
        // Get order quantity (count of all order lines)
        $orderQty = Db::getInstance()->getValue(
            '
        SELECT COUNT(DISTINCT vod.id_order_detail)
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        WHERE vod.id_vendor = ' . (int)$id_vendor
        );

        // Get total CA (revenue)
        $totalCA = Db::getInstance()->getValue(
            '
        SELECT SUM(vod.vendor_amount)
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        WHERE vod.id_vendor = ' . (int)$id_vendor
        );

        // Get total products by reference (distinct product references)
        $totalProductsByRef = Db::getInstance()->getValue(
            '
        SELECT COUNT(DISTINCT od.product_reference)
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vod.id_order_detail
        WHERE vod.id_vendor = ' . (int)$id_vendor . '
        AND od.product_reference != ""'
        );

        return [
            'order_quantity' => (int)$orderQty,
            'total_ca' => (float)$totalCA,
            'total_products_by_ref' => (int)$totalProductsByRef
        ];
    }

    /**
     * Get recent order lines for vendor
     * 
     * @param int $id_vendor Vendor ID
     * @param int $id_supplier Supplier ID
     * @param int $limit Number of lines to retrieve
     * @return array Recent order lines
     */
    protected function getRecentOrderLines($id_vendor, $id_supplier, $limit = 5)
    {
        $query = new DbQuery();
        $query->select('od.id_order_detail, od.product_name, od.product_quantity, od.unit_price_tax_incl,
                    od.total_price_tax_incl, o.reference as order_reference, o.date_add as order_date,
                    vod.commission_amount, vod.vendor_amount, vod.id_order,
                    ols.status as line_status');
        $query->from('order_detail', 'od');
        $query->innerJoin('orders', 'o', 'o.id_order = od.id_order');
        $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
        $query->leftJoin('vendor_order_detail', 'vod', 'vod.id_order_detail = od.id_order_detail AND vod.id_vendor = ' . (int)$id_vendor);
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = od.id_order_detail AND ols.id_vendor = ' . (int)$id_vendor);
        $query->where('p.id_supplier = ' . (int)$id_supplier);
        $query->orderBy('o.date_add DESC');
        $query->limit($limit);

        $results = Db::getInstance()->executeS($query);

        // Get status colors for display
        $status_colors = [];
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes();
        foreach ($statusTypes as $status) {
            $status_colors[$status['name']] = $status['color'];
        }

        // Add status color to each line
        foreach ($results as &$line) {
            if (isset($status_colors[$line['line_status']])) {
                $line['status_color'] = $status_colors[$line['line_status']];
            } else {
                $line['status_color'] = '#777777'; // Default gray color
            }
        }

        return $results;
    }


    /**
     * Get last 28 days sales data
     * 
     * @param int $id_vendor Vendor ID
     * @return array Daily sales data
     */
    protected function getLast28DaysSales($id_vendor)
    {
        $dailySales = [];
        $today = new DateTime();

        // Generate data for each of the last 28 days
        for ($i = 27; $i >= 0; $i--) {
            $date = clone $today;
            $date->sub(new DateInterval('P' . $i . 'D'));
            $dateStr = $date->format('Y-m-d');

            // Get sales for this day
            $dayTotal = Db::getInstance()->getValue('
            SELECT SUM(vod.vendor_amount)
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND DATE(o.date_add) = "' . pSQL($dateStr) . '"
        ');

            $dailySales[] = [
                'date' => $date->format('M d'),
                'fullDate' => $dateStr,
                'sales' => $dayTotal ? (float)$dayTotal : 0
            ];
        }

        return $dailySales;
    }
}
