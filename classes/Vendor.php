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

    public static function getVendorById($id_vendor)
    {
        $sql = 'SELECT v.*, s.name as supplier_name
            FROM ' . _DB_PREFIX_ . 'mv_vendor v
            LEFT JOIN ' . _DB_PREFIX_ . 'supplier s ON v.id_supplier = s.id_supplier
            WHERE v.id_vendor = ' . (int)$id_vendor;

        return Db::getInstance()->getRow($sql);
    }



    /**
     * Get vendor's commission summary
     * 
     * @param int $id_vendor Vendor ID
     * @return array Commission summary
     */
    public static function getVendorCommissionSummary($id_vendor)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $totalCommissionAdded = Db::getInstance()->getValue(
            '
    SELECT SUM(vod.vendor_amount) 
    FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
    WHERE vod.id_vendor = ' . (int)$id_vendor . '
    AND (
        (olst.commission_action = "add") 
        OR 
        (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" = "add")
    )'
        );

        $totalCommissionRefunded = Db::getInstance()->getValue(
            '
    SELECT SUM(vod.vendor_amount) 
    FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
    WHERE vod.id_vendor = ' . (int)$id_vendor . '
    AND olst.commission_action = "refund"'
        );

        $paidCommission = Db::getInstance()->getValue(
            '
    SELECT SUM(amount) 
    FROM ' . _DB_PREFIX_ . 'mv_vendor_payment 
    WHERE id_vendor = ' . (int)$id_vendor . ' AND status = "completed"'
        );

        $pendingAmount = (float)$totalCommissionAdded - (float)$paidCommission;

        return [
            'total_commission_added' => (float)$totalCommissionAdded,
            'total_commission_refunded' => (float)$totalCommissionRefunded,
            'paid_commission' => (float)$paidCommission,
            'pending_amount' => (float)$pendingAmount
        ];
    }
}
