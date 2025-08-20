<?php

/**
 * Manifest model class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Manifest extends ObjectModel
{
    /** @var int Manifest ID */
    public $id;

    /** @var string Manifest reference */
    public $reference;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Address ID */
    public $id_address;

    /** @var int Manifest status ID */
    public $id_manifest_status;

    /** @var string Manifest type */
    public $type;

    /** @var string Creation date */
    public $add_date;

    /** @var string Update date */
    public $update_date;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_manifest',
        'primary' => 'id_manifest',
        'fields' => [
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_address' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false, 'default' => null],
            'id_manifest_status' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false, 'default' => 1],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'default' => 'pickup'],
            'add_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'update_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    const TYPE_PICKUP = 'pickup';
    const TYPE_RETURNS = 'returns';
    /**
     * Constructor
     * 
     * @param int|null $id Manifest ID
     * @param int|null $id_lang Language ID
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        // Set default dates if creating new manifest
        if (!$this->id) {
            $this->add_date = date('Y-m-d H:i:s');
            $this->update_date = date('Y-m-d H:i:s');
            $this->id_manifest_status = 1; // Default to "En préparation"
        }
    }

    /**
     * Update the update_date before saving
     * 
     * @param bool $null_values Allow null values
     * @return bool
     */
    public function update($null_values = false)
    {
        $this->update_date = date('Y-m-d H:i:s');
        return parent::update($null_values);
    }

    /**
     * Get manifest details
     * 
     * @return array|false
     */
    public function getManifestDetails()
    {
        if (!$this->id) {
            return false;
        }

        $sql = 'SELECT md.*, od.product_name, od.product_reference, od.product_quantity
                FROM `' . _DB_PREFIX_ . 'mv_manifest_details` md
                LEFT JOIN `' . _DB_PREFIX_ . 'order_detail` od ON (md.id_order_details = od.id_order_detail)
                WHERE md.id_manifest = ' . (int)$this->id . '
                ORDER BY md.add_date ASC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Add order detail to manifest
     * 
     * @param int $id_order_detail Order detail ID
     * @return bool
     */
    public function addOrderDetail($id_order_detail)
    {
        if (!$this->id || !$id_order_detail) {
            return false;
        }

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mv_manifest_details` 
                (id_manifest, id_order_details, add_date, update_date)
                VALUES (' . (int)$this->id . ', ' . (int)$id_order_detail . ', NOW(), NOW())';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Remove order detail from manifest
     * 
     * @param int $id_order_detail Order detail ID
     * @return bool
     */
    public function removeOrderDetail($id_order_detail)
    {
        if (!$this->id || !$id_order_detail) {
            return false;
        }

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'mv_manifest_details` 
                WHERE id_manifest = ' . (int)$this->id . ' 
                AND id_order_details = ' . (int)$id_order_detail;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get manifest status name
     * 
     * @return string|false
     */
    public function getStatusName()
    {
        if (!$this->id_manifest_status) {
            return false;
        }

        $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'mv_manifest_status` 
                WHERE id_manifest_status = ' . (int)$this->id_manifest_status;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get all manifest statuses
     * 
     * @return array|false
     */
    public static function getStatuses()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mv_manifest_status` 
                WHERE active = 1 
                ORDER BY position ASC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get manifests by status
     * 
     * @param int $id_manifest_status Status ID
     * @param int|null $limit Limit
     * @param int|null $offset Offset
     * @return array|false
     */
    public static function getByStatus($id_manifest_status, $limit = null, $offset = null)
    {
        $sql = 'SELECT m.*, ms.name as status_name, a.firstname, a.lastname, a.company
                FROM `' . _DB_PREFIX_ . 'mv_manifest` m
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status` ms ON (m.id_manifest_status = ms.id_manifest_status)
                LEFT JOIN `' . _DB_PREFIX_ . 'address` a ON (m.id_address = a.id_address)
                WHERE m.id_manifest_status = ' . (int)$id_manifest_status . '
                ORDER BY m.add_date DESC';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$offset . ', ' . (int)$limit;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Generate unique reference
     * 
     * @param string $prefix Prefix for reference
     * @return string
     */
    public static function generateReference($prefix = 'MAN' , $vendor = null , $type = self::TYPE_PICKUP)
    {
        $date = date('Ymd');
        $counter = 1;

        do {
            $reference = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

            $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest` 
                    WHERE reference = "' . pSQL($reference) . '"';

            $exists = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            $counter++;
        } while ($exists > 0);

        return $reference;
    }

    /**
     * Get total order details count in manifest
     * 
     * @return int|false
     */
    public function getTotalOrderDetails()
    {
        if (!$this->id) {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_details` 
                WHERE id_manifest = ' . (int)$this->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get address information
     * 
     * @return array|false
     */
    public function getAddress()
    {
        if (!$this->id_address) {
            return false;
        }

        return new Address($this->id_address);
    }

    /**
     * add new manifest
     * @param string $orderdetails order details array of int
     * @param int $id_vendor vendor id
     * @param string $type manifest type (pickup or returns)
     * @param int|null $id_address address id 
     * @param int|null $id_manifest_status manifest status id default 1
     */
    public static function addNewManifest($orderdetails, $id_vendor, $type = self::TYPE_PICKUP, $id_address = null, $id_manifest_status = 1)
    {
        if (empty($orderdetails) || !is_array($orderdetails) || !Validate::isUnsignedId($id_vendor)) {
            return false;
        }
        if (!Validate::isGenericName($type) || !in_array($type, [self::TYPE_PICKUP, self::TYPE_RETURNS])) {
            return false;
        }
        $manifest = new Manifest();
        $manifest->reference = self::generateReference();
        $manifest->id_vendor = (int)$id_vendor;
        $manifest->id_address = $id_address;
        $manifest->id_manifest_status = $id_manifest_status;
        $manifest->type = $type;
        $manifest->add_date = date('Y-m-d H:i:s');
        $manifest->update_date = date('Y-m-d H:i:s');

        if (!$manifest->add()) {
            return false;
        }

        foreach ($orderdetails as $id_order_detail) {
            if (!Validate::isUnsignedId($id_order_detail)) {
                continue;
            }
            $manifest->addOrderDetail($id_order_detail);
        }

        return $manifest;
    }

    public function getVendorByManifest()
    {
        $sql = 'SELECT v.* FROM `' . _DB_PREFIX_ . 'mv_vendor` v
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest` m ON (v.id_vendor = m.id_vendor)
            WHERE m.id_manifest = ' . (int)$this->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }

    /**
     * Generate new manifest
     * @param string $orderdetails order details array of int
     * @param int $id_vendor vendor id
     * @param string $type manifest type (pickup or returns)
     * @param int|null $id_address address id 
     * @param int|null $id_manifest_status manifest status id default 1
     */

    public static function generateNewManifestPDF($orderdetails, $id_vendor, $type = self::TYPE_PICKUP, $id_address = null, $id_manifest_status = 1)
    {
        $manifest = self::addNewManifest($orderdetails, $id_vendor, $type, $id_address, $id_manifest_status);

        $pdfData = [
            'orderDetailIds' => $orderdetails,
            'vendor' => $id_vendor,
            'filename' => 'Pickup_Manifest_' . $manifest->reference . '.pdf'
        ];
        $pdf = new PDF([$pdfData], 'VendorManifestPDF', Context::getContext()->smarty);
        $pdf->render(true);
        exit;
    }
}
