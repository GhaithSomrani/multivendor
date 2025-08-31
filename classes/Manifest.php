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
    public $id_manifest_type;

    /** @var string Creation date */
    public $date_add;

    /** @var string Update date */
    public $date_upd;

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
            'id_manifest_status' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_manifest_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
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
    }

    /**
     * Update the date_upd before saving
     * 
     * @param bool $null_values Allow null values
     * @return bool
     */
    public function update($null_values = false)
    {
        $nextOrderlineStatus = ManifestStatusType::getTheNextOrderlineStatus($this->id_manifest_status);
        $orderDetailIds = Manifest::getOrderdetailsIDs($this->id);

        foreach ($orderDetailIds as $id_order_detail) {
            try {
                // to do remove the vendor id 
                OrderLineStatus::updateStatus(
                    $id_order_detail['id_order_details'],
                    (int)$this->id_vendor,
                    $nextOrderlineStatus,
                    Context::getContext()->employee->id,
                    'Status modifié depuis l\'administration',
                    true
                );
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage(), 3, 'error in status change', 'Manifest', $this->id, (int)$this->id_vendor);
            }
        }


        return parent::update($null_values);
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
                (id_manifest, id_order_details, date_add, date_upd)
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

    public function clearOrderDetails()
    {
        return Db::getInstance()->delete(
            'mv_manifest_details',
            'id_manifest = ' . (int)$this->id
        );
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
                ORDER BY m.date_add DESC';

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
    public static function generateReference($prefix = 'MAN', $vendor = null, $type = self::TYPE_PICKUP)
    {
        $counter = 1;


        $timestamp = strtotime(date('Y-m-d H:i:s'));
        do {
            $reference = $prefix . '-' . $timestamp . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

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
     * Add new manifest or update existing modifiable one
     * @param array $orderdetails order details array of int
     * @param int $id_vendor vendor id
     * @param string $type manifest type (pickup or returns)
     * @param int|null $id_address address id 
     * @param int|null $id_manifest_status manifest status id
     */
    public static function addNewManifest($orderdetails, $id_vendor, $type = self::TYPE_PICKUP, $id_address = null, $id_manifest_status = null)
    {
        try {

            $existingManifest = self::getModifiableManifest($id_vendor, $type);

            if ($existingManifest) {
                $manifest = new Manifest($existingManifest['id_manifest']);
                $manifest->clearOrderDetails();
                $manifest->update();
            } else {
                $manifest = new Manifest();
                $manifest->reference = self::generateReference();
                $manifest->id_vendor = (int)$id_vendor;
                $manifest->id_address = $id_address;
                $manifest->id_manifest_status = $id_manifest_status;
                $manifest->id_manifest_type = $type;


                if (!$manifest->add()) {
                    return false;
                }
            }

            foreach ($orderdetails as $id_order_detail) {
                if (!Validate::isUnsignedId($id_order_detail)) {
                    continue;
                }
                $manifest->addOrderDetail($id_order_detail);
            }

            return $manifest;
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 3, null, 'AdminVendorOrderDetails');
        }
    }

    public function getVendorByManifest()
    {
        $sql = 'SELECT v.* FROM `' . _DB_PREFIX_ . 'mv_vendor` v
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest` m ON (v.id_vendor = m.id_vendor)
            WHERE m.id_manifest = ' . (int)$this->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }


    public function getManifestType()
    {
        $manifestType = new ManifestType($this->id_manifest_type);
        return $manifestType->name;
    }

    public static function getOrderDetailCheckboxState($id_order_detail, $current_manifest_id = null, $current_manifest_type_id = null)
    {
        $existingManifestId = self::getManifestIdByOrderDetail($id_order_detail);

        // No existing manifest - enabled and unchecked
        if (!$existingManifestId) {
            return ['checked' => false, 'disabled' => false];
        }

        // Same manifest - checked and enabled
        if ($current_manifest_id && $existingManifestId == $current_manifest_id) {
            return ['checked' => true, 'disabled' => false];
        }

        // Different manifest - check if same manifest type
        if ($current_manifest_type_id) {
            $existingManifestTypeId = self::getManifestTypeByOrderDetail($id_order_detail);

            // Same manifest type - checked and disabled
            if ($existingManifestTypeId == $current_manifest_type_id) {
                return ['checked' => true, 'disabled' => true];
            }

            // Different manifest type - unchecked and enabled
            return ['checked' => false, 'disabled' => false];
        }

        // Default case - checked and disabled
        return ['checked' => true, 'disabled' => true];
    }

    public static function getManifestTypeByOrderDetail($id_order_detail)
    {
        if (!Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        $sql = 'SELECT m.id_manifest_type FROM `' . _DB_PREFIX_ . 'mv_manifest_details` md
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest` m ON (md.id_manifest = m.id_manifest)
            WHERE md.id_order_details = ' . (int)$id_order_detail;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
    /**
     * Generate new manifest
     * @param string $orderdetails order details array of int
     * @param int $id_vendor vendor id
     * @param string $type manifest type (pickup or returns)
     * @param int|null $id_address address id 
     * @param int|null $id_manifest_status manifest status id default 1
     */

    public static function generateNewManifestPDF($orderdetails, $id_vendor, $type = self::TYPE_PICKUP, $id_address = null, $id_manifest_status = null)
    {
        if ($id_manifest_status == null) {
            $id_manifest_status = ManifestStatusType::getDefaultManifestStatusType($type);
        }
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


    public static function generatePrintablePDF($id_manifest)
    {
        try {
            $manifest = new Manifest($id_manifest);
            $orderDetailIds = array_column(self::getOrderdetailsIDs($id_manifest), 'id_order_details');
            $pdfData = [

                'vendor' => (int)$manifest->id_vendor,
                'orderDetailIds' => $orderDetailIds,
                'export_type' => $manifest->id_manifest_type,
                'filename' => 'Manifest_' . $manifest->reference . '_' . date('YmdHis') . '.pdf'
            ];

            $pdf = new PDF([$pdfData], 'VendorManifestPDF', Context::getContext()->smarty);
            $pdf->render(true);
            exit;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Export PDF Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
        }
    }
    public static function getModifiableManifest($id_vendor, $type)
    {
        $sql = 'SELECT m.* 
            FROM `' . _DB_PREFIX_ . 'mv_manifest` m
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status_type` ms ON (m.id_manifest_status = ms.id_manifest_status_type)
            WHERE m.id_vendor = ' . (int)$id_vendor . ' 
            AND m.type = "' . pSQL($type) . '"
            AND ms.allowed_modification = 1
            ORDER BY m.date_add DESC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }


    public static function getOrderdetailsIDs($id_manifest)
    {
        $sql = 'SELECT md.id_order_details FROM `' . _DB_PREFIX_ . 'mv_manifest_details` md
            WHERE md.id_manifest = ' . $id_manifest;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }
    /**
     * Check if an order detail exists in the manifest
     *
     * @param int $id_order_detail Order detail ID
     * @return bool
     */
    public static function hasOrderDetail($id_order_detail)
    {

        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_order_details = ' . (int)$id_order_detail;

        return (bool)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
    /**
     * Get manifest ID by order detail ID
     *
     * @param int $id_order_detail Order detail ID
     * @return int|false Manifest ID or false if not found
     */
    public static function getManifestIdByOrderDetail($id_order_detail)
    {
        if (!Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        $sql = 'SELECT id_manifest FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_order_details = ' . (int)$id_order_detail;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}
