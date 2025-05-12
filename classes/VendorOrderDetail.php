<?php

/**
 * VendorOrderDetail model class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorOrderDetail extends ObjectModel
{
    /** @var int Vendor order detail ID */
    public $id;

    /** @var int Order detail ID */
    public $id_order_detail;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Order ID */
    public $id_order;

    /** @var float Commission rate */
    public $commission_rate;

    /** @var float Commission amount */
    public $commission_amount;

    /** @var float Vendor amount */
    public $vendor_amount;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'vendor_order_detail',
        'primary' => 'id_vendor_order_detail',
        'fields' => [
            'id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'commission_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'vendor_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get vendor order details by order ID
     *
     * @param int $id_order Order ID
     * @return array Vendor order details
     */
    public static function getByOrderId($id_order)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('vendor_order_detail');
        $query->where('id_order = ' . (int)$id_order);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get vendor order detail by order detail ID and vendor ID
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array|false Vendor order detail
     */
    public static function getByOrderDetailAndVendor($id_order_detail, $id_vendor)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('vendor_order_detail');
        $query->where('id_order_detail = ' . (int)$id_order_detail);
        $query->where('id_vendor = ' . (int)$id_vendor);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Get vendor order details by vendor ID
     *
     * @param int $id_vendor Vendor ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Vendor order details
     */
    public static function getByVendorId($id_vendor, $limit = null, $offset = null)
    {
        $query = new DbQuery();
        $query->select('vod.*, od.product_name, od.product_quantity, od.product_price, o.reference as order_reference, o.date_add as order_date');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get total sales for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float Total sales
     */
    public static function getVendorTotalSales($id_vendor)
    {
        $query = new DbQuery();
        $query->select('SUM(vendor_amount + commission_amount)');
        $query->from('vendor_order_detail');
        $query->where('id_vendor = ' . (int)$id_vendor);

        return (float)Db::getInstance()->getValue($query);
    }

    /**
     * Get total commission for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float Total commission
     */
    public static function getVendorTotalCommission($id_vendor)
    {
        $query = new DbQuery();
        $query->select('SUM(commission_amount)');
        $query->from('vendor_order_detail');
        $query->where('id_vendor = ' . (int)$id_vendor);

        return (float)Db::getInstance()->getValue($query);
    }

    /**
     * Get sales by month for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int $year Optional year (defaults to current year)
     * @return array Monthly sales data
     */
    public static function getVendorMonthlySales($id_vendor, $year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $startDate = $year . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = $year . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-31';

            $totalSales = Db::getInstance()->getValue('
                SELECT SUM(vendor_amount)
                FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND o.date_add BETWEEN "' . pSQL($startDate) . ' 00:00:00" AND "' . pSQL($endDate) . ' 23:59:59"
            ');

            $months[] = [
                'month' => date('F', mktime(0, 0, 0, $i, 1, $year)),
                'sales' => $totalSales ? (float)$totalSales : 0
            ];
        }

        return $months;
    }

    /**
     * Get top selling products for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int $limit Number of products to return
     * @return array Top selling products
     */
    public static function getVendorTopProducts($id_vendor, $limit = 5)
    {
        $query = '
            SELECT od.product_id, od.product_name, SUM(od.product_quantity) as quantity_sold,
                   SUM(vod.vendor_amount + vod.commission_amount) as total_sales
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vod.id_order_detail
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            GROUP BY od.product_id
            ORDER BY quantity_sold DESC
            LIMIT ' . (int)$limit;

        return Db::getInstance()->executeS($query);
    }
}
