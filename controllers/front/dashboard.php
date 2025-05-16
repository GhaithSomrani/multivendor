
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

        // Process date filter form if submitted
        $start_date = null;
        $end_date = null;
        $date_filter_active = false;
        $filter_label = $this->l('All Time');
        $date_filter_type = 'this_month'; // Default filter type

        if (Tools::isSubmit('submitDateFilter')) {
            $date_filter_type = Tools::getValue('date_filter_type', 'all');

            switch ($date_filter_type) {
                case 'all':
                    // No date filtering, use all time
                    $filter_label = $this->l('All Time');
                    break;

                case 'today':
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d');
                    $date_filter_active = true;
                    $filter_label = $this->l('Today');
                    break;

                case 'yesterday':
                    $start_date = date('Y-m-d', strtotime('-1 day'));
                    $end_date = date('Y-m-d', strtotime('-1 day'));
                    $date_filter_active = true;
                    $filter_label = $this->l('Yesterday');
                    break;

                case 'this_week':
                    $start_date = date('Y-m-d', strtotime('monday this week'));
                    $end_date = date('Y-m-d');
                    $date_filter_active = true;
                    $filter_label = $this->l('This Week');
                    break;

                case 'last_week':
                    $start_date = date('Y-m-d', strtotime('monday last week'));
                    $end_date = date('Y-m-d', strtotime('sunday last week'));
                    $date_filter_active = true;
                    $filter_label = $this->l('Last Week');
                    break;

                case 'this_month':
                    $start_date = date('Y-m-01');
                    $end_date = date('Y-m-d');
                    $date_filter_active = true;
                    $filter_label = $this->l('This Month');
                    break;

                case 'last_month':
                    $start_date = date('Y-m-01', strtotime('first day of last month'));
                    $end_date = date('Y-m-t', strtotime('last day of last month'));
                    $date_filter_active = true;
                    $filter_label = $this->l('Last Month');
                    break;

                case 'custom':
                    $custom_start = Tools::getValue('custom_start_date');
                    $custom_end = Tools::getValue('custom_end_date');

                    if (Validate::isDate($custom_start) && Validate::isDate($custom_end)) {
                        $start_date = $custom_start;
                        $end_date = $custom_end;
                        $date_filter_active = true;
                        $filter_label = sprintf($this->l('From %s to %s'), $custom_start, $custom_end);
                    }
                    break;
            }
        } else {
            // Default to "This Month" when no filter is selected
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
            $date_filter_active = true;
            $filter_label = $this->l('This Month');
        }

        // Get updated indicators with date filter
        $indicators = $this->getVendorIndicators($id_vendor, $start_date, $end_date, $date_filter_active);

        // Get recent orders (last 5)
        $recentOrderLines = $this->getRecentOrderLines($id_vendor, $id_supplier, 5);

        // Get commission summary
        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor);

        // Get monthly sales data for chart
        // Always show complete months for the bar chart
        $monthlySales = VendorOrderDetail::getVendorMonthlySales($id_vendor);

        // Get daily filtered sales data for chart (uses current filter)
        $filteredDailySales = $this->getDailyFilteredSales($id_vendor, $start_date, $end_date, $date_filter_active);

        // Get top selling products with date filter
        $topProducts = $this->getTopSellingProducts($id_vendor, $start_date, $end_date, $date_filter_active, 5);

        // Assign data to template
        $this->context->smarty->assign([
            'vendor' => $vendor,
            'recent_order_lines' => $recentOrderLines,
            'indicators' => $indicators,
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
            // Date filter variables
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
     * Get vendor indicators with date filtering
     */
    protected function getVendorIndicators($id_vendor, $start_date = null, $end_date = null, $date_filter_active = false)
    {
        // Base query for order quantity
        $orderQtyQuery = '
            SELECT COUNT(DISTINCT vod.id_order_detail)
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor;

        // Base query for total CA (revenue)
        $totalCAQuery = '
            SELECT SUM(vod.vendor_amount)
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor;

        // Base query for total products by reference
        $totalProductsByRefQuery = '
            SELECT COUNT(DISTINCT od.product_reference)
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vod.id_order_detail
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND od.product_reference != ""';

        // Apply date filters if active
        if ($date_filter_active && $start_date && $end_date) {
            $dateFilter = ' AND DATE(o.date_add) BETWEEN "' . pSQL($start_date) . '" AND "' . pSQL($end_date) . '"';
            $orderQtyQuery .= $dateFilter;
            $totalCAQuery .= $dateFilter;
            $totalProductsByRefQuery .= $dateFilter;
        }

        // Execute queries
        $orderQty = Db::getInstance()->getValue($orderQtyQuery);
        $totalCA = Db::getInstance()->getValue($totalCAQuery);
        $totalProductsByRef = Db::getInstance()->getValue($totalProductsByRefQuery);

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
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
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
     * Get daily sales data for a specific date range
     * 
     * @param int $id_vendor Vendor ID
     * @param string|null $start_date Start date (Y-m-d)
     * @param string|null $end_date End date (Y-m-d)
     * @param bool $date_filter_active Whether date filtering is active
     * @return array Daily sales data
     */
    protected function getDailyFilteredSales($id_vendor, $start_date = null, $end_date = null, $date_filter_active = false)
    {
        // If no date filter is active or dates aren't provided, default to last 28 days
        if (!$date_filter_active || !$start_date || !$end_date) {
            return $this->getLast28DaysSales($id_vendor);
        }

        $dailySales = [];

        // Calculate the number of days in the range
        $startDateTime = new DateTime($start_date);
        $endDateTime = new DateTime($end_date);
        $interval = $startDateTime->diff($endDateTime);
        $dayCount = $interval->days + 1; // Include both start and end dates

        // If it's a large range (more than 60 days), we'll aggregate by week to avoid too many data points
        $aggregateByWeek = ($dayCount > 60);

        if ($aggregateByWeek) {
            return $this->getWeeklyFilteredSales($id_vendor, $start_date, $end_date);
        }

        // Generate data for each day in the range
        $currentDate = clone $startDateTime;
        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');

            // Get sales for this day
            $dayTotal = Db::getInstance()->getValue('
                SELECT SUM(vod.vendor_amount)
                FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND DATE(o.date_add) = "' . pSQL($dateStr) . '"
            ');

            $dailySales[] = [
                'date' => $currentDate->format('M d'),
                'fullDate' => $dateStr,
                'sales' => $dayTotal ? (float)$dayTotal : 0
            ];

            // Move to next day
            $currentDate->modify('+1 day');
        }

        return $dailySales;
    }

    /**
     * Get weekly aggregated sales data for a specific date range
     * Used for longer date ranges to avoid too many data points
     * 
     * @param int $id_vendor Vendor ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Weekly sales data
     */
    protected function getWeeklyFilteredSales($id_vendor, $start_date, $end_date)
    {
        $weeklySales = [];

        // Adjust start date to the beginning of the week
        $startDateTime = new DateTime($start_date);
        $startDateTime->modify('Monday this week');

        // Ensure start date isn't before the requested start date
        $requestedStartDate = new DateTime($start_date);
        if ($startDateTime < $requestedStartDate) {
            $startDateTime = $requestedStartDate;
        }

        $endDateTime = new DateTime($end_date);

        // Generate data for each week in the range
        $weekStart = clone $startDateTime;

        while ($weekStart <= $endDateTime) {
            // Calculate week end (Sunday or the end date, whichever comes first)
            $weekEnd = clone $weekStart;
            $weekEnd->modify('Sunday this week');

            if ($weekEnd > $endDateTime) {
                $weekEnd = clone $endDateTime;
            }

            $weekStartStr = $weekStart->format('Y-m-d');
            $weekEndStr = $weekEnd->format('Y-m-d');

            // Get sales for this week
            $weekTotal = Db::getInstance()->getValue('
                SELECT SUM(vod.vendor_amount)
                FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND DATE(o.date_add) BETWEEN "' . pSQL($weekStartStr) . '" AND "' . pSQL($weekEndStr) . '"
            ');

            $weeklySales[] = [
                'date' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                'fullDate' => $weekStartStr . ' to ' . $weekEndStr,
                'sales' => $weekTotal ? (float)$weekTotal : 0
            ];

            // Move to next week
            $weekStart->modify('+1 week');
        }

        return $weeklySales;
    }

    /**
     * Get top selling products with date filtering
     * 
     * @param int $id_vendor Vendor ID
     * @param string|null $start_date Start date (Y-m-d)
     * @param string|null $end_date End date (Y-m-d)
     * @param bool $date_filter_active Whether date filtering is active
     * @param int $limit Number of products to return
     * @return array Top selling products
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
     * Get filtered monthly sales data
     * 
     * @param int $id_vendor Vendor ID
     * @param string|null $start_date Start date (Y-m-d)
     * @param string|null $end_date End date (Y-m-d)
     * @param bool $date_filter_active Whether date filtering is active
     * @return array Monthly sales data
     */
    protected function getFilteredMonthlySales($id_vendor, $start_date = null, $end_date = null, $date_filter_active = false)
    {
        // If no dates provided or filter not active, use standard method
        if (!$date_filter_active || !$start_date || !$end_date) {
            return VendorOrderDetail::getVendorMonthlySales($id_vendor);
        }

        // Calculate start and end months based on provided dates
        $startDateTime = new DateTime($start_date);
        $endDateTime = new DateTime($end_date);

        // Adjust to get full months
        $startMonth = $startDateTime->format('m');
        $startYear = $startDateTime->format('Y');
        $endMonth = $endDateTime->format('m');
        $endYear = $endDateTime->format('Y');

        $months = [];
        $currentDate = clone $startDateTime;
        $currentDate->modify('first day of this month'); // Start from first day of start month

        while ($currentDate->format('Y-m') <= $endDateTime->format('Y-m')) {
            $month = $currentDate->format('m');
            $year = $currentDate->format('Y');

            $startOfMonth = $year . '-' . $month . '-01';
            $endOfMonth = $year . '-' . $month . '-' . $currentDate->format('t'); // t gives number of days in month

            // Adjust for partial months if needed
            if ($month == $startMonth && $year == $startYear) {
                $startOfMonth = $start_date;
            }

            if ($month == $endMonth && $year == $endYear) {
                $endOfMonth = $end_date;
            }

            $totalSales = Db::getInstance()->getValue('
                SELECT SUM(vod.vendor_amount)
                FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND o.date_add BETWEEN "' . pSQL($startOfMonth) . ' 00:00:00" AND "' . pSQL($endOfMonth) . ' 23:59:59"
            ');

            $months[] = [
                'month' => $currentDate->format('F'),
                'sales' => $totalSales ? (float)$totalSales : 0
            ];

            // Move to next month
            $currentDate->modify('+1 month');
        }

        return $months;
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
