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

    // Auto-set position when adding new status
    public function add($auto_date = true, $null_values = false)
    {
        if (!$this->position) {
            $this->position = $this->getNextPosition();
        }
        return parent::add($auto_date, $null_values);
    }

    // Get next available position
    private function getNextPosition()
    {
        $sql = 'SELECT MAX(position) + 1 FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type`';
        return (int)Db::getInstance()->getValue($sql) ?: 1;
    }

    // Get all active status types ordered by position
    public static function getAllManifestStatusTypes()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
                WHERE active = 1 
                ORDER BY position ASC';
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    // Get allowed next status IDs for a given status
    public static function getAllowedManifestStatusTypesIds($id_manifest_status_type)
    {
        $sql = 'SELECT allowed_manifest_status_type_ids FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
                WHERE active = 1 
                AND id_manifest_status_type = "' . $id_manifest_status_type . '" 
                ORDER BY position ASC';
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }


    // Get manifest type (pickup/returns) for a status
    public static function getAllowedManifestTypes($id_manifest_status_type)
    {
        $sql = 'SELECT allowed_manifest_type FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
                WHERE active = 1 
                AND id_manifest_status_type = ' . $id_manifest_status_type;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
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


    // Get allowed order line status type IDs for a manifest status
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
}
