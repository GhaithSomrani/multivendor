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

    /** @var int Manifest type */
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
            'id_manifest_type' => ['type' => self::TYPE_INT, 'validate' => 'isGenericName', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    const TYPE_PICKUP = 1;
    const TYPE_RETURNS = 2;
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
                    Context::getContext()->employee->id ?? Context::getContext()->customer->id ?? null,
                    'Status modifié depuis  le manifeste ' . $this->reference,
                    true
                );
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage(), 3, 'error in status change', 'Manifest', $this->id, (int)$this->id_vendor);
            }
        }


        return parent::update($null_values);
    }

    // functio that selecte the last id
    public static function getLastId()
    {
        $query = new DbQuery();
        $query->select(' max(id_manifest) as max_id ');
        $query->from('mv_manifest', 'm');
        $query->orderBy('id_manifest DESC');
        return Db::getInstance()->getValue($query);
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

        $sql = 'SELECT name FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
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
        $sql = 'SELECT m.*, mst.name as status_name, a.firstname, a.lastname, a.company
                FROM `' . _DB_PREFIX_ . 'mv_manifest` m
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status_type` mst ON (m.id_manifest_status = mst.id_manifest_status)
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
    public static function generateReference($vendor, $type)
    {
        $parts = [];

        if ($vendor) {
            $vendorObject = new Vendor($vendor);
            $vendorName = $vendorObject->shop_name;
            $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', $vendorName);
            $noVowels = preg_replace('/[aeiouAEIOU]/', '', $cleanName);
            $parts[] = strtoupper(substr($noVowels, 0, 3));
        }

        $typeObj = new ManifestType($type);
        $typeName = $typeObj->name;
        if ($typeName) {
            $cleanTypeName = preg_replace('/[^a-zA-Z0-9]/', '', $typeName);
            $noVowelsType = preg_replace('/[aeiouAEIOU]/', '', $cleanTypeName);
            $parts[] = strtoupper(substr($noVowelsType, 3, 2));
        }
        $parts[] = date('ymd');
        $id = Manifest::getLastId() + 1 ?? 0;
        $parts[] = str_pad($id, 4, '0', STR_PAD_LEFT);
        return implode('-', $parts);
    }



    /**
     * Add new manifest or update existing modifiable one
     * @param array $orderdetails order details array of int
     * @param int $id_vendor vendor id
     * @param string $type manifest type (pickup or returns)
     * @param int|null $id_address address id 
     * @param int|null $id_manifest_status manifest status id
     */
    public static function addNewManifest($orderdetails, $id_vendor, $type, $id_address = null, $id_manifest_status = null, $isadmin = false)
    {
        try {

            $existingManifest = self::getModifiableManifest($id_vendor, $type);
            $existedOrderDetails = self::getOrderDetailsByType($type) ?? [];
            if ($existingManifest) {
                if (!$isadmin) {
                    $vendorObj = new Vendor($existingManifest['id_vendor']);
                    throw new Exception('Un manifeste pour le fournisseur ' . $vendorObj->shop_name . ' existe deja avec la reference :' . $existingManifest['reference'] . '. <br> Veuillez le compléter ou créer un nouveau manifeste après avoir validé celui en cours.');
                    return false;
                } else {
                    $manifest = new Manifest($existingManifest['id_manifest']);
                    $manifest->id_address = $id_address;
                    $manifest->clearOrderDetails();
                    $manifest->update();
                }
            } else {
                $manifest = new Manifest();
                $manifest->id_vendor = (int)$id_vendor;
                $manifest->reference = self::generateReference($id_vendor, $type);
                $manifest->id_address = $id_address;
                $manifest->id_manifest_status = $id_manifest_status ?? 1;
                $manifest->id_manifest_type = $type;
                if (!$manifest->add()) {
                    return false;
                }
            }
            foreach ($orderdetails as $id_order_detail) {
                if (!Validate::isUnsignedId($id_order_detail)) {
                    continue;
                }
                if (!in_array($id_order_detail, $existedOrderDetails)) {

                    $manifest->addOrderDetail($id_order_detail);
                } else {
                    throw new Exception('Le détail de commande ' . $id_order_detail . ' existe déjà dans ce manifeste.');
                }
            }
            return $manifest;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());

            PrestaShopLogger::addLog('Add New Manifest Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
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
                return ['checked' => false, 'disabled' => true];
            }

            // Different manifest type - unchecked and enabled
            return ['checked' => false, 'disabled' => false];
        }

        // Default case - checked and disabled
        return ['checked' => false, 'disabled' => true];
    }


    public static function getOrderDetailsByType($id_manifest_type)
    {
        try {
            $query = new DbQuery();
            $query->select('md.id_order_details');
            $query->from('mv_manifest_details', 'md');
            $query->leftJoin('mv_manifest', 'm', 'm.id_manifest = md.id_manifest');
            $query->where('m.id_manifest_type = ' . (int)$id_manifest_type);
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Get Order Details By Type Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
            return false;
        }
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

    public static function generateNewManifestPDF($orderdetails, $id_vendor, $type, $id_address = null, $id_manifest_status = null)
    {

        try {
            if ($id_manifest_status == null) {
                $id_manifest_status = ManifestStatusType::getDefaultManifestStatusType($type);
            }
            $manifest = self::addNewManifest($orderdetails, $id_vendor, $type, $id_address, $id_manifest_status, true);
            $manifestTypeObj = new ManifestType($type);

            $pdfData = [
                'orderDetailIds' => $orderdetails,
                'vendor' => $id_vendor,
                'filename' => 'Pickup_Manifest_' . $manifest->reference . '.pdf',
                'id_address' => $id_address,
                'maniefest_reference' => $manifest->reference,
                'manifest_type' => $manifestTypeObj->name

            ];
            $template = new HTMLTemplateVendorManifestPDF($pdfData, Context::getContext()->smarty);
            $content = $template->getContent();
            echo $content;
            exit;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Export PDF Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
            throw new Exception("Error: " . $e->getMessage());
        }
    }

    public static function generateMulipleManifestPDF($orderDetails, $type, $vendorId = null)
    {

        try {
            if ($vendorId) {
                self::generateNewManifestPDF($orderDetails, $vendorId, $type);
            } else {
                $Data = [];
                $grouped = [];
                foreach ($orderDetails as $orderDetail) {
                    $id_vendor = Vendor::getVendorIdFromOrderDetail($orderDetail);
                    $id_order = $orderDetail;

                    if (!isset($grouped[$id_vendor])) {
                        $grouped[$id_vendor] = [];
                    }

                    $grouped[$id_vendor][] = $id_order;
                }
                foreach ($grouped as $vendorId => $orders) {
                    $Data[] = [
                        'vendor' => $vendorId,
                        'orderids' => array_values(array_unique($orders)),
                    ];
                }
                $id_manifest_status = ManifestStatusType::getDefaultManifestStatusType($type);
                $manifestTypeObj = new ManifestType($type);

                foreach ($Data as $data) {
                    self::addNewManifest($data['orderids'], $data['vendor'], $type, null,  $id_manifest_status, true);
                }
                $pdfData = [
                    'vendor' => '',
                    'orderDetailIds' => $orderDetails,
                    'export_type' => $type,
                    'id_address' => '',
                    'filename' => 'Manifest_Muliple_' . date('YmdHis') . '.pdf',
                    'maniefest_reference' => '',
                    'manifest_type' => $manifestTypeObj->name
                ];
                $template = new HTMLTemplateVendorManifestPDF($pdfData, Context::getContext()->smarty);
                $content = $template->getContent();
                echo $content;
                exit;
            }
        } catch (Exception $e) {

            throw new Exception("Error: " . $e->getMessage());
        }
    }


    public static function generatePrintablePDF($id_manifest)
    {
        try {
            $manifest = new Manifest($id_manifest);
            $orderDetailIds = array_column(self::getOrderdetailsIDs($id_manifest), 'id_order_details');
            $manifestTypeObj = new ManifestType($manifest->id_manifest_type);

            $pdfData = [
                'vendor' => (int)$manifest->id_vendor,
                'orderDetailIds' => $orderDetailIds,
                'export_type' => $manifest->id_manifest_type,
                'id_address' => $manifest->id_address,
                'filename' => 'Manifest_' . $manifest->reference . '_' . date('YmdHis') . '.pdf',
                'maniefest_reference' => $manifest->reference,
                'manifest_type' => $manifestTypeObj->name
            ];

            $template = new HTMLTemplateVendorManifestPDF($pdfData, Context::getContext()->smarty);
            $content = $template->getContent();
            echo $content;

            // $pdf = new PDF([$pdfData], 'VendorManifestPDF', Context::getContext()->smarty);
            // $pdf->render('I');
            exit;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Export PDF Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
        }
    }




    public static function getModifiableManifest($id_vendor, $type)
    {
        try {
            $sql = 'SELECT m.* 
            FROM `' . _DB_PREFIX_ . 'mv_manifest` m
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status_type` ms ON (m.id_manifest_status = ms.id_manifest_status_type)
            WHERE m.id_vendor = ' . (int)$id_vendor . ' 
            AND m.id_manifest_type = "' . pSQL($type) . '"
            AND ms.allowed_modification = 1
            ORDER BY m.date_add DESC';

            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Get Modifiable Manifest Error: ' . $e->getMessage(), 3, null, 'AdminVendorOrderDetails');
            return false;
        }
    }


    public static function getOrderdetailsIDs($id_manifest)
    {
        $manifest = new Manifest($id_manifest);
        $id_manifest_type = $manifest->id_manifest_type;
        $query = new DbQuery();
        $query->select('md.id_order_details');
        $query->from('mv_manifest_details', 'md');
        $query->leftJoin('mv_manifest', 'm', 'm.id_manifest = md.id_manifest');
        $query->where('md.id_manifest = ' . $id_manifest);
        $query->where('m.id_manifest_type = ' . $id_manifest_type);
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
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

    public static function IsEditable($id_manifest)
    {
        $query = new DbQuery();
        $query->select('ms.allowed_modification');
        $query->from('mv_manifest', 'm');
        $query->leftJoin('mv_manifest_status_type', 'ms', 'm.id_manifest_status = ms.id_manifest_status_type');
        $query->where('m.id_manifest = ' . (int)$id_manifest);
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
    public static function isDeletable($id_manifest)
    {
        $query = new DbQuery();
        $query->select('ms.allowed_delete');
        $query->from('mv_manifest', 'm');
        $query->leftJoin('mv_manifest_status_type', 'ms', 'm.id_manifest_status = ms.id_manifest_status_type');
        $query->where('m.id_manifest = ' . (int)$id_manifest);
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
    public function getTransactionType()
    {
        if ($this->id_manifest_type ==  self::TYPE_PICKUP) {
            return "commission";
        }
        if ($this->id_manifest_type ==  self::TYPE_RETURNS) {
            return "refund";
        }
    }

    public static function getManifestAddress($id_manifest)
    {
        $manifest = new Manifest($id_manifest);
        if (!$manifest->id_address) {
            return '';
        }
        $addressobj = new Address($manifest->id_address);
        return  AddressFormat::generateAddress($addressobj, [], ' - ', ' ');
    }
}
