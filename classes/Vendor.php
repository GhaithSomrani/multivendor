<?php

/**
 * Vendor model class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Vendor extends ObjectModel
{
    /** @var int Vendor ID */
    public $id;

    /** @var int Customer ID */
    public $id_customer;

    /** @var int Supplier ID */
    public $id_supplier;

    /** @var string Shop name */
    public $shop_name;

    /** @var string Description */
    public $description;

    /** @var string Logo */
    public $logo;

    /** @var string Banner */
    public $banner;

    /** @var int Status (0 = Pending, 1 = Active, 2 = Rejected) */
    public $status = 0;

    /** @var string Creation date */
    public $date_add;

    /** @var string Last update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'vendor',
        'primary' => 'id_vendor',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'shop_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'logo' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'banner' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'status' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get vendor by customer ID
     * 
     * @param int $id_customer Customer ID
     * @return array|false Vendor data
     */
    public static function getVendorByCustomer($id_customer)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('vendor');
        $query->where('id_customer = ' . (int)$id_customer);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Get vendor by supplier ID
     * 
     * @param int $id_supplier Supplier ID
     * @return array|false Vendor data
     */
    public static function getVendorBySupplier($id_supplier)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('vendor');
        $query->where('id_supplier = ' . (int)$id_supplier);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Get all active vendors
     * 
     * @return array List of active vendors
     */
    public static function getActiveVendors()
    {
        $query = new DbQuery();
        $query->select('v.*, c.firstname, c.lastname, c.email, s.name as supplier_name');
        $query->from('vendor', 'v');
        $query->leftJoin('customer', 'c', 'c.id_customer = v.id_customer');
        $query->leftJoin('supplier', 's', 's.id_supplier = v.id_supplier');
        $query->where('v.status = 1');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get all vendors
     * 
     * @return array List of all vendors
     */
    public static function getAllVendors()
    {
        $query = new DbQuery();
        $query->select('v.*, c.firstname, c.lastname, c.email, s.name as supplier_name');
        $query->from('vendor', 'v');
        $query->leftJoin('customer', 'c', 'c.id_customer = v.id_customer');
        $query->leftJoin('supplier', 's', 's.id_supplier = v.id_supplier');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get vendor orders
     * 
     * @param int $id_vendor Vendor ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array List of orders
     */
    public static function getVendorOrders($id_vendor, $limit = 10, $offset = 0)
    {
        $query = new DbQuery();
        $query->select('DISTINCT vod.id_order, o.reference, o.total_paid, o.date_add, osl.name as status');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('order_state_lang', 'osl', 'osl.id_order_state = o.current_state AND osl.id_lang = ' . (int)Context::getContext()->language->id);
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');
        $query->limit($limit, $offset);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Count vendor orders
     * 
     * @param int $id_vendor Vendor ID
     * @return int Number of orders
     */
    public static function countVendorOrders($id_vendor)
    {
        $query = new DbQuery();
        $query->select('COUNT(DISTINCT id_order)');
        $query->from('vendor_order_detail');
        $query->where('id_vendor = ' . (int)$id_vendor);

        return (int)Db::getInstance()->getValue($query);
    }

    /**
     * Get vendor order details
     * 
     * @param int $id_vendor Vendor ID
     * @param int $id_order Order ID
     * @return array Order details
     */
    public static function getVendorOrderDetails($id_vendor, $id_order)
    {
        $query = new DbQuery();
        $query->select('vod.*, od.product_name, od.product_quantity, od.product_price, od.total_price_tax_incl, ols.status as line_status');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->where('vod.id_order = ' . (int)$id_order);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get vendor's commission summary
     * 
     * @param int $id_vendor Vendor ID
     * @return array Commission summary
     */
    public static function getVendorCommissionSummary($id_vendor)
    {
        $totalSales = Db::getInstance()->getValue(
            '
            SELECT SUM(vendor_amount + commission_amount) 
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail 
            WHERE id_vendor = ' . (int)$id_vendor
        );

        $totalCommission = Db::getInstance()->getValue(
            '
            SELECT SUM(commission_amount) 
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail 
            WHERE id_vendor = ' . (int)$id_vendor
        );

        $pendingCommission = Db::getInstance()->getValue(
            '
            SELECT SUM(commission_amount) 
            FROM ' . _DB_PREFIX_ . 'vendor_transaction 
            WHERE id_vendor = ' . (int)$id_vendor . ' AND status = "pending"'
        );

        $paidCommission = Db::getInstance()->getValue(
            '
            SELECT SUM(commission_amount) 
            FROM ' . _DB_PREFIX_ . 'vendor_transaction 
            WHERE id_vendor = ' . (int)$id_vendor . ' AND status = "paid"'
        );

        $pendingAmount = Db::getInstance()->getValue(
            '
            SELECT SUM(vendor_amount) 
            FROM ' . _DB_PREFIX_ . 'vendor_order_detail 
            WHERE id_vendor = ' . (int)$id_vendor . ' AND id_order_detail NOT IN (
                SELECT id_order_detail FROM ' . _DB_PREFIX_ . 'vendor_transaction 
                WHERE id_vendor = ' . (int)$id_vendor . ' AND status IN ("pending", "paid")
            )'
        );

        return [
            'total_sales' => (float)$totalSales,
            'total_commission' => (float)$totalCommission,
            'pending_commission' => (float)$pendingCommission,
            'paid_commission' => (float)$paidCommission,
            'pending_amount' => (float)$pendingAmount
        ];
    }
}
