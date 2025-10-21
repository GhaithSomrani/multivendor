<?php

/**
 * ManifestDetails model class
 * Handles the relationship between manifests and order details
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ManifestDetails extends ObjectModel
{
    /** @var int ManifestDetails ID */
    public $id;

    /** @var int Manifest ID */
    public $id_manifest;

    /** @var int Order detail ID */
    public $id_order_details;

    /** @var string Creation date */
    public $date_add;

    /** @var string Update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_manifest_details',
        'primary' => 'id_manifest_details',
        'fields' => [
            'id_manifest' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order_details' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Constructor
     *
     * @param int|null $id ManifestDetails ID
     * @param int|null $id_lang Language ID
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
    }

    /**
     * Add an order detail to a manifest
     *
     * @param int $id_manifest Manifest ID
     * @param int $id_order_detail Order detail ID
     * @return bool|ManifestDetails Returns the ManifestDetails object on success, false on failure
     */
    public static function addOrderDetailToManifest($id_manifest, $id_order_detail)
    {
        if (!Validate::isUnsignedId($id_manifest) || !Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        // Check if the relationship already exists
        if (self::exists($id_manifest, $id_order_detail)) {
            PrestaShopLogger::addLog(
                'ManifestDetails: Order detail ' . $id_order_detail . ' already exists in manifest ' . $id_manifest,
                2,
                null,
                'ManifestDetails'
            );
            return false;
        }

        // Check if order detail is already in another manifest of the same type
        $existingManifest = self::getManifestByOrderDetail($id_order_detail);
        if ($existingManifest) {
            $newManifest = new Manifest($id_manifest);
            $existingManifestObj = new Manifest($existingManifest);

            if ($newManifest->id_manifest_type == $existingManifestObj->id_manifest_type) {
                PrestaShopLogger::addLog(
                    'ManifestDetails: Order detail ' . $id_order_detail . ' already exists in manifest ' . $existingManifest . ' of the same type',
                    2,
                    null,
                    'ManifestDetails'
                );
                return false;
            }
        }

        // Create new manifest detail entry
        $manifestDetail = new ManifestDetails();
        $manifestDetail->id_manifest = (int)$id_manifest;
        $manifestDetail->id_order_details = (int)$id_order_detail;

        if ($manifestDetail->add()) {
            return $manifestDetail;
        }

        return false;
    }

    /**
     * Remove an order detail from a manifest
     *
     * @param int $id_manifest Manifest ID
     * @param int $id_order_detail Order detail ID
     * @return bool True on success, false on failure
     */
    public static function removeOrderDetailFromManifest($id_manifest, $id_order_detail)
    {
        if (!Validate::isUnsignedId($id_manifest) || !Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        // Check if manifest is editable
        if (!Manifest::IsEditable($id_manifest)) {
            PrestaShopLogger::addLog(
                'ManifestDetails: Cannot remove order detail from non-editable manifest ' . $id_manifest,
                2,
                null,
                'ManifestDetails'
            );
            return false;
        }

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_manifest = ' . (int)$id_manifest . '
                AND id_order_details = ' . (int)$id_order_detail;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Check if an order detail exists in a manifest
     *
     * @param int $id_manifest Manifest ID
     * @param int $id_order_detail Order detail ID
     * @return bool True if exists, false otherwise
     */
    public static function exists($id_manifest, $id_order_detail)
    {
        if (!Validate::isUnsignedId($id_manifest) || !Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_manifest = ' . (int)$id_manifest . '
                AND id_order_details = ' . (int)$id_order_detail;

        return (bool)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get all order details for a manifest
     *
     * @param int $id_manifest Manifest ID
     * @param bool $id_only Return only IDs (default: false)
     * @return array Array of order detail IDs or full records
     */
    public static function getOrderDetailsByManifest($id_manifest, $id_only = false)
    {
        if (!Validate::isUnsignedId($id_manifest)) {
            return [];
        }

        $query = new DbQuery();

        if ($id_only) {
            $query->select('id_order_details');
        } else {
            $query->select('*');
        }

        $query->from('mv_manifest_details');
        $query->where('id_manifest = ' . (int)$id_manifest);
        $query->orderBy('date_add DESC');

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if ($id_only && $results) {
            return array_column($results, 'id_order_details');
        }

        return $results ?: [];
    }

    /**
     * Get manifest ID by order detail ID
     *
     * @param int $id_order_detail Order detail ID
     * @return int|false Manifest ID or false if not found
     */
    public static function getManifestByOrderDetail($id_order_detail)
    {
        if (!Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        $sql = 'SELECT id_manifest FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_order_details = ' . (int)$id_order_detail;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get full manifest details by order detail ID
     *
     * @param int $id_order_detail Order detail ID
     * @return array|false Manifest details or false if not found
     */
    public static function getManifestDetailsByOrderDetail($id_order_detail)
    {
        if (!Validate::isUnsignedId($id_order_detail)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('md.*, m.reference, m.id_vendor, m.id_manifest_status, m.id_manifest_type');
        $query->from('mv_manifest_details', 'md');
        $query->leftJoin('mv_manifest', 'm', 'md.id_manifest = m.id_manifest');
        $query->where('md.id_order_details = ' . (int)$id_order_detail);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
    }

    /**
     * Get count of order details in a manifest
     *
     * @param int $id_manifest Manifest ID
     * @return int Number of order details
     */
    public static function getOrderDetailsCount($id_manifest)
    {
        if (!Validate::isUnsignedId($id_manifest)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_manifest = ' . (int)$id_manifest;

        return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Remove all order details from a manifest
     *
     * @param int $id_manifest Manifest ID
     * @return bool True on success, false on failure
     */
    public static function clearManifest($id_manifest)
    {
        if (!Validate::isUnsignedId($id_manifest)) {
            return false;
        }

        // Check if manifest is editable
        if (!Manifest::IsEditable($id_manifest)) {
            PrestaShopLogger::addLog(
                'ManifestDetails: Cannot clear non-editable manifest ' . $id_manifest,
                2,
                null,
                'ManifestDetails'
            );
            return false;
        }

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'mv_manifest_details`
                WHERE id_manifest = ' . (int)$id_manifest;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get all order details in manifests by vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int|null $id_manifest_type Optional manifest type filter
     * @return array Array of order detail IDs
     */
    public static function getOrderDetailsByVendor($id_vendor, $id_manifest_type = null)
    {
        if (!Validate::isUnsignedId($id_vendor)) {
            return [];
        }

        $query = new DbQuery();
        $query->select('md.id_order_details');
        $query->from('mv_manifest_details', 'md');
        $query->leftJoin('mv_manifest', 'm', 'md.id_manifest = m.id_manifest');
        $query->where('m.id_vendor = ' . (int)$id_vendor);

        if ($id_manifest_type !== null && Validate::isUnsignedId($id_manifest_type)) {
            $query->where('m.id_manifest_type = ' . (int)$id_manifest_type);
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        return $results ? array_column($results, 'id_order_details') : [];
    }

    /**
     * Bulk add order details to a manifest
     *
     * @param int $id_manifest Manifest ID
     * @param array $order_detail_ids Array of order detail IDs
     * @return array Array with 'success' count and 'failed' array of IDs
     */
    public static function bulkAddOrderDetails($id_manifest, $order_detail_ids)
    {
        if (!Validate::isUnsignedId($id_manifest) || !is_array($order_detail_ids)) {
            return ['success' => 0, 'failed' => $order_detail_ids];
        }

        $success = 0;
        $failed = [];

        foreach ($order_detail_ids as $id_order_detail) {
            if (self::addOrderDetailToManifest($id_manifest, $id_order_detail)) {
                $success++;
            } else {
                $failed[] = $id_order_detail;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Bulk remove order details from a manifest
     *
     * @param int $id_manifest Manifest ID
     * @param array $order_detail_ids Array of order detail IDs
     * @return array Array with 'success' count and 'failed' array of IDs
     */
    public static function bulkRemoveOrderDetails($id_manifest, $order_detail_ids)
    {
        if (!Validate::isUnsignedId($id_manifest) || !is_array($order_detail_ids)) {
            return ['success' => 0, 'failed' => $order_detail_ids];
        }

        $success = 0;
        $failed = [];

        foreach ($order_detail_ids as $id_order_detail) {
            if (self::removeOrderDetailFromManifest($id_manifest, $id_order_detail)) {
                $success++;
            } else {
                $failed[] = $id_order_detail;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }
}
