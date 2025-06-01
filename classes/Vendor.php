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
        'table' => 'mv_vendor',
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
     * Get vendor by supplier ID
     * 
     * @param int $id_supplier Supplier ID
     * @return array|false Vendor data
     */
    public static function getVendorBySupplier($id_supplier)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor');
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
        $query->from('mv_vendor', 'v');
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
        $query->from('mv_vendor', 'v');
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
        $query->from('mv_vendor_order_detail', 'vod');
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
        $query->from('mv_vendor_order_detail');
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
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
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
        // Get the default status and its commission action
        $defaultStatus = Db::getInstance()->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC '
        );
        
        $defaultAction = $defaultStatus ? $defaultStatus['commission_action'] : 'none';

        // Total sales (only when commission_action = 'add')
        // Include records with no status if default action is 'add'
        $totalSales = Db::getInstance()->getValue(
            '
            SELECT SUM(vod.vendor_amount) 
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.name = ols.status
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND (
                (olst.commission_action = "add") 
                OR 
                (ols.status IS NULL AND "' . pSQL($defaultAction) . '" = "add")
            )'
        );

        // Total commissions added (only when commission_action = 'add')
        $totalCommissionAdded = Db::getInstance()->getValue(
            '
            SELECT SUM(vod.vendor_amount) 
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.name = ols.status
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND (
                (olst.commission_action = "add") 
                OR 
                (ols.status IS NULL AND "' . pSQL($defaultAction) . '" = "add")
            )'
        );

        // Total commissions refunded (only when commission_action = 'refund')
        $totalCommissionRefunded = Db::getInstance()->getValue(
            '
            SELECT SUM(vod.vendor_amount) 
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.name = ols.status
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND olst.commission_action = "refund"'
        );

        // Total paid commissions
        $paidCommission = Db::getInstance()->getValue(
            '
            SELECT SUM(amount) 
            FROM ' . _DB_PREFIX_ . 'mv_vendor_payment 
            WHERE id_vendor = ' . (int)$id_vendor . ' AND status = "completed"'
        );

        // Pending amount = (Commissions Added) - (Total Paid)
        $pendingAmount = (float)$totalCommissionAdded - (float)$paidCommission;

        return [
            'total_commission_added' => (float)$totalCommissionAdded,
            'total_commission_refunded' => (float)$totalCommissionRefunded,
            'paid_commission' => (float)$paidCommission,
            'pending_amount' => (float)$pendingAmount
        ];
    }
}
