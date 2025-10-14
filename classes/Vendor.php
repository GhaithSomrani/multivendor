<?php

/**
 * Vendor model class
 */

use Twig\Cache\NullCache;

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
     * Get vendor's address from the their supplier id or customer id
     *  
     * @return array|false
     */
    public function getVendorAddress()
    {
        if (!$this->id_customer) {
            return false;
        }
        $query = new DbQuery();
        $query->select('*');
        $query->from('address');
        $query->where('id_customer = ' . (int)$this->id_customer);
        $query->where('deleted = 0');
        $query->where('active = 1');

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


        $query = new DbQuery();
        $query->select('SUM(vod.vendor_amount) * -1 as total, count(vod.id_order_detail) as count_details');
        $query->from('mv_vendor_order_detail', 'vod');
        $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->where('ols.id_order_line_status_type = 13');
        $totalCommissionRefunded = Db::getInstance()->getRow($query);





        $pendingAmount = Db::getInstance()->getRow(
            'SELECT SUM(vt.vendor_amount) as total , count(vod.id_order_detail)  as count_details
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_transaction vt ON vt.order_detail_id = vod.id_order_detail 
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_payment vp ON vp.id_vendor_payment = vt.id_vendor_payment 
                WHERE vod.id_vendor = ' . (int)$id_vendor . ' 
                AND olst.id_order_line_status_type != 15
                AND olst.commission_action = "add"
                AND vt.transaction_type =  "commission" 
                AND ( vt.status = "pending"  OR  vp.status = "pending"  OR vt.id_vendor_payment = 0)  '
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
                AND vt.status = "pending"  
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
    /**
     * Get vendor ID from order detail ID
     * @param int $orderDetailId
     * @return int|false
     */
    public static function getVendorIdFromOrderDetail($orderDetailId)
    {
        $sql = 'SELECT id_vendor
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail
                WHERE id_order_detail = ' . (int)$orderDetailId;
        $result = Db::getInstance()->getValue($sql);

        return $result ? (int)$result : false;
    }



    /**
     * Get products from a vendor
     * 
     * @param int $id_vendor Vendor ID
     * @param int|array $idCategory Optional category ID(s)
     * @param float $priceFrom Optional price from
     * @param float $priceTo Optional price to
     * @param string $name Optional product name
     * @param string $reference Optional product reference
     * @param string $mpn Optional product mpn
     * @return array Products
     */
    public static function getOutOfStockProducts(
        $id_vendor,
        $idCategory = false,
        $priceFrom = false,
        $priceTo = false,
        $name = false,
        $reference = false,
        $mpn = false,
        $limit = 21,
        $offset = 0,
        $countOnly = false,
        $product_price = false
    ) {
        $idLang = (int)Configuration::get('PS_LANG_DEFAULT');
        $idShop = (int)Context::getContext()->shop->id;
        $vendor = new Vendor($id_vendor);
        $idSupplier = $vendor->id_supplier ? (int)$vendor->id_supplier : false;

        $query = new DbQuery();

        if ($countOnly) {
            $query->select('COUNT(DISTINCT p.id_product)');
        } else {
            $query->select('
            p.id_product, 
            pl.name, 
            p.reference, 
            p.mpn, 
            p.price,
            pa.id_product_attribute,
            IF(sp.reduction_type = "percentage", 
                (p.price + IFNULL(pa.price, 0)) * (1 - IFNULL(sp.reduction, 0)),
                (p.price + IFNULL(pa.price, 0)) - IFNULL(sp.reduction, 0)
            ) as final_price,
           ( IF(
                sp.reduction_type = "percentage",
                (p.price + IFNULL(pa.price, 0)) * (1 - IFNULL(sp.reduction, 0)),
                (p.price + IFNULL(pa.price, 0)) - IFNULL(sp.reduction, 0)
            ) - ' . (float)$product_price . ') as shift_price
        ');
        }

        $query->from('product', 'p');
        $query->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . (int)$idLang . ' AND pl.id_shop = ' . (int)$idShop);
        $query->leftJoin('product_attribute', 'pa', 'pa.id_product = p.id_product');
        $query->leftJoin('specific_price', 'sp', 'sp.id_product = p.id_product AND (sp.id_product_attribute = 0 OR sp.id_product_attribute = pa.id_product_attribute) AND sp.id_shop IN (0, ' . (int)$idShop . ') AND (sp.from = "0000-00-00 00:00:00" OR sp.from <= NOW()) AND (sp.to = "0000-00-00 00:00:00" OR sp.to >= NOW())');

        if ($idCategory) {
            $ids = is_array($idCategory) ? array_map('intval', $idCategory) : [(int)$idCategory];
            $query->innerJoin('category_product', 'cp', 'cp.id_product = p.id_product AND cp.id_category IN (' . implode(',', $ids) . ')');
        }

        if ($idSupplier) {
            $query->where('p.id_supplier = ' . (int)$idSupplier);
        }

        if ($priceFrom > 0) {
            $query->where('IF(sp.reduction_type = "percentage", (p.price + IFNULL(pa.price, 0)) * (1 - IFNULL(sp.reduction, 0)), (p.price + IFNULL(pa.price, 0)) - IFNULL(sp.reduction, 0)) >= ' . (float)$priceFrom);
        }
        if ($priceTo > 0) {
            $query->where('IF(sp.reduction_type = "percentage", (p.price + IFNULL(pa.price, 0)) * (1 - IFNULL(sp.reduction, 0)), (p.price + IFNULL(pa.price, 0)) - IFNULL(sp.reduction, 0)) <= ' . (float)$priceTo);
        }

        if (trim($name) || trim($reference)) {
            $query->where('pl.name LIKE "%' . pSQL($name) . '%" OR p.reference LIKE "%' . pSQL($reference) . '%" OR p.mpn LIKE "%' . pSQL($mpn) . '%" Or pa.reference LIKE "%' . pSQL($reference) . '%" Or pa.mpn LIKE "%' . pSQL($mpn) . '%"');
        }

        $query->where('p.active = 1');
        if ($product_price) {
            $query->orderBy('ABS(final_price - ' . (float)$product_price . ')', 'asc');
        }
        if (!$countOnly && $limit) {
            $query->groupBy('p.id_product');
            $query->limit((int)$limit, (int)$offset);
        }

        return $countOnly
            ? (int)Db::getInstance()->getValue($query)
            : Db::getInstance()->executeS($query);
    }

    public static function getProductPriceByAttributes($idProduct, array $idAttributes = [], $withTaxes = true)
    {

        $idProductAttribute = !empty($idAttributes) ? Product::getIdProductAttributeByIdAttributes($idProduct, $idAttributes, true) : null;


        $price = Product::getPriceStatic(
            (int)$idProduct,
            $withTaxes,
            $idProductAttribute,
            6,
            null,
            false,
            true,
            1,
            false,
            null,
            null,
            null
        );

        return $price;
    }
    public static function getProductsAttribute($id_product)
    {
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $id_product = (int)$id_product;

        $query = new DbQuery();
        $query->select('pa.id_product_attribute')
            ->select('CONCAT_WS(" – ", pl.name, GROUP_CONCAT(al.name ORDER BY ag.position, a.position SEPARATOR ", ")) AS name')
            ->select('COALESCE(ROUND(p.price + pa.price, 6), 0) AS price')
            ->select('sa.quantity AS stock')
            ->select('pa.reference AS sku')
            ->select('pa.mpn AS mpn');
        $query->from('product_attribute', 'pa');
        $query->innerJoin('product_lang', 'pl', 'pl.id_product = pa.id_product AND pl.id_lang = ' . $id_lang);
        $query->innerJoin('stock_available', 'sa', 'sa.id_product = pa.id_product AND sa.id_product_attribute = pa.id_product_attribute');
        $query->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
        $query->leftJoin('attribute', 'a', 'a.id_attribute = pac.id_attribute');
        $query->leftJoin('attribute_lang', 'al', 'al.id_attribute = a.id_attribute AND al.id_lang = ' . $id_lang);
        $query->leftJoin('attribute_group', 'ag', 'ag.id_attribute_group = a.id_attribute_group');
        $query->leftJoin('product', 'p', 'p.id_product = pa.id_product');
        $query->where('pa.id_product = ' . $id_product);
        $query->where('sa.quantity > 0');
        $query->groupBy('pa.id_product_attribute');
        $query->orderBy('pa.id_product_attribute');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }


}
