<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Manages manifest status types and their configuration
 */
class ManifestStatusType extends ObjectModel
{
    public $name;
    public $allowed_manifest_status_type_ids;
    public $allowed_order_line_status_type_ids;
    public $next_order_line_status_type_ids;
    public $id_manifest_type;
    public $allowed_modification;
    public $allowed_delete;
    public $position;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mv_manifest_status_type',
        'primary' => 'id_manifest_status_type',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'allowed_manifest_status_type_ids' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'allowed_order_line_status_type_ids' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'next_order_line_status_type_ids' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_manifest_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'allowed_modification' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'allowed_delete' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public function add($auto_date = true, $null_values = false)
    {

        return parent::add($auto_date, $null_values);
    }


    /**
     * Get all manifest status types
     * 
     * @return array Associative array of all manifest status types, sorted by position
     */
    public static function getAllActive()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
                WHERE active = 1 
                ORDER BY position ASC';
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get allowed manifest status types IDs for a manifest status type
     * 
     * @param int $id_manifest_status_type ID of manifest status type
     * @return string Comma-separated list of allowed manifest status type IDs
     */
    public static function getAllowedIds($id_manifest_status_type)
    {
        $sql = 'SELECT allowed_manifest_status_type_ids FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
                WHERE active = 1 
                AND id_manifest_status_type = "' . $id_manifest_status_type . '" 
                ORDER BY position ASC';
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }


    /**
     * Get allowed manifest types for a manifest status type
     * 
     * @param int $id_manifest_status_type ID of manifest status type
     * @return string Comma-separated list of allowed manifest types
     */
    public static function getAvailable($id_manifest_status_type)

    {

        $allowed_manifest_status_type_ids = self::getAllowedIds($id_manifest_status_type);
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_manifest_status_type', 'mst');
        $query->where('mst.active = 1');
        if ($allowed_manifest_status_type_ids) {
            $query->where('mst.id_manifest_status_type IN (' . $allowed_manifest_status_type_ids . ')');
        }
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Get the default manifest status type for a given manifest type (pickup/returns)
     * 
     * @param string $manifest_type Manifest type (pickup or returns)
     * @return int
     */
    public static function getDefaultManifestStatusType($id_manifest_type)
    {
        $sql = 'SELECT id_manifest_status_type FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
            WHERE active = 1 
            AND id_manifest_type = ' . (int)$id_manifest_type . '
            ORDER BY position ASC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get all manifest status types for a given manifest type (pickup/returns) and status.
     * If no status is provided, the default status for the manifest type is used.
     * 
     * @param string $manifest_type Manifest type (pickup or returns)
     * @param int $id_manifest_status_type Optional - status type ID
     * @return array
     */

    public static function getManifestStatusByAllowedManifestType($id_manifest_type)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type`
            WHERE active = 1 
            AND id_manifest_type = ' . (int)$id_manifest_type . '
            ORDER BY position ASC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }


    /**
     * Get allowed order line status types for a manifest status type
     * 
     * @param int $id_manifest_status_type Manifest status type ID
     * @return string Comma-separated list of allowed order line status type IDs
     */
    public static function getAllowedOrderLineStatusTypes($id_manifest_status_type)
    {
        $sql = 'SELECT allowed_order_line_status_type_ids FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
                WHERE active = 1 
                AND id_manifest_status_type = ' . $id_manifest_status_type;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get the next order line status type IDs for a manifest status.
     * 
     * @param int $id_manifest_status_type Manifest status type ID
     * @return string Comma-separated list of order line status type IDs
     */
    public static function getTheNextOrderlineStatus($id_manifest_status_type)
    {
        $sql = 'SELECT next_order_line_status_type_ids FROM `'
            . _DB_PREFIX_ . 'mv_manifest_status_type` WHERE id_manifest_status_type = ' . $id_manifest_status_type;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    public static function getName($id_manifest_status_type)
    {
        $object = new ManifestStatusType($id_manifest_status_type);
        return $object->name;
    }



    public function delete()
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest` 
            WHERE id_manifest_status = ' . (int)$this->id;

        if (Db::getInstance()->getValue($sql)) {
            throw new PrestaShopException('Cannot delete a manifest status that is linked to a manifest');
        }

        return parent::delete();
    }
}
