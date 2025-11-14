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

    /** @var int Product ID */
    public $product_id;

    /** @var string Product name */
    public $product_name;

    /** @var string Product reference (SKU) */
    public $product_reference;

    /** @var string Product MPN */
    public $product_mpn;

    /** @var float Product price */
    public $product_price;

    /** @var int Product quantity */
    public $product_quantity;

    /** @var int Product attribute ID */
    public $product_attribute_id;

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
        'table' => 'mv_vendor_order_detail',
        'primary' => 'id_vendor_order_detail',
        'fields' => [
            'id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'product_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'product_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 128],
            'product_mpn' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'product_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'product_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'product_attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'commission_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'vendor_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public static function getAdminLink()
    {
        return Context::getContext()->link->getAdminLink('AdminVendorOrderDetails');
    }
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
        $query->from('mv_vendor_order_detail');
        $query->where('id_order = ' . (int)$id_order);

        return Db::getInstance()->executeS($query);
    }


    /** get vendor order detail by id order details */
    public static function getByIdOrderDetail($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor_order_detail');
        $query->where('id_order_detail = ' . (int)$id_order_detail);

        return Db::getInstance()->getRow($query);
    }
    /**
     * Get brand name by product ID      
     * 
     * @param int $id_product Product ID
     * @return string Brand name or empty string if not found
     */

    public static function getBrandByProductId($id_product)
    {
        try {
            $brand_name = Manufacturer::getNameById((int)(new Product($id_product))->id_manufacturer);
        } catch (PrestaShopException $e) {
            PrestaShopLogger::addLog('Error fetching brand for product ID ' . (int)$id_product . ': ' . $e->getMessage(), 3);
            return '';
        }
        return $brand_name;
    }
}
