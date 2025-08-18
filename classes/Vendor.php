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


        $totalCommissionRefunded = Db::getInstance()->getRow(
            'SELECT SUM(vt.vendor_amount)  as total , count(vod.id_order_detail)  as count_details
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_transaction vt ON vt.order_detail_id = vod.id_order_detail
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_payment vp ON vp.id_vendor_payment = vt.id_vendor_payment
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND olst.id_order_line_status_type != 15
                AND vt.status = "pending"
                AND vt.transaction_type = "refund"
                AND vt.id_vendor_payment = 0
                AND vt.id_vendor_transaction = (
                    SELECT MAX(vt2.id_vendor_transaction)
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt2
                    WHERE vt2.order_detail_id = vt.order_detail_id
                    AND vt2.status = "pending"
                    AND vt2.id_vendor_payment = 0
                );'
        );

        $pendingAmount = Db::getInstance()->getRow(
            'SELECT SUM(vt.vendor_amount) as total , count(vod.id_order_detail)  as count_details
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_transaction vt ON vt.order_detail_id = vod.id_order_detail
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_payment vp ON vp.id_vendor_payment = vt.id_vendor_payment
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND olst.id_order_line_status_type != 15
                AND vt.status = "pending"
                AND vt.transaction_type = "commission"
                AND vt.id_vendor_payment = 0
                AND vt.id_vendor_transaction = (
                    SELECT MAX(vt2.id_vendor_transaction)
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt2
                    WHERE vt2.order_detail_id = vt.order_detail_id
                    AND vt2.status = "pending"
                    AND vt2.id_vendor_payment = 0
                );'
        );


        $totalCommissionPending = Db::getInstance()->getRow(
            'SELECT SUM(vt.vendor_amount) as total , count(vod.id_order_detail)  as count_details
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_transaction vt ON vt.order_detail_id = vod.id_order_detail 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_payment vp ON vp.id_vendor_payment = vt.id_vendor_payment 
                WHERE vod.id_vendor = ' . (int)$id_vendor . ' 
                AND olst.id_order_line_status_type = 15
                AND vt.transaction_type =  "commission" 
                AND  vt.status = "pending"  
                AND vt.id_vendor_payment = 0;'
        );

        $paidCommission = Db::getInstance()->getRow(
            '
                SELECT SUM(vp.amount)  as total
                FROM ' . _DB_PREFIX_ . 'mv_vendor_payment vp 
                WHERE vp.id_vendor = ' . (int)$id_vendor . ' AND vp.status = "completed"'
        );

        $totalCommissionAdded['total'] =   $paidCommission['total']  + $pendingAmount['total'] + $totalCommissionPending['total'];
        $summary = [
            'total_commission_added' => $totalCommissionAdded,
            'total_commission_refunded' => $totalCommissionRefunded,
            'total_commission_pending' => $totalCommissionPending,
            'paid_commission' => $paidCommission,
            'pending_amount' => $pendingAmount
        ];

        return $summary;
    }
}
