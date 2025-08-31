<?php

/**
 * Manifest Type Class - NEW
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ManifestType extends ObjectModel
{
    /** @var int ID */
    public $id;

    /** @var string Name */
    public $name;

    /** @var string Creation date */
    public $date_add;

    /** @var string Update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_manifest_type',
        'primary' => 'id_manifest_type',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    /**
     * Get all manifest types for dropdown
     */
    public static function getAll()
    {
        $sql = 'SELECT id_manifest_type as id, name FROM `' . _DB_PREFIX_ . 'mv_manifest_type` ORDER BY name';
        return Db::getInstance()->executeS($sql);
    }
    
    public static function getDefaultType()
    {
        $sql = 'SELECT id_manifest_type FROM `' . _DB_PREFIX_ . 'mv_manifest_type` WHERE active = 1 LIMIT 1';
        return Db::getInstance()->getValue($sql);
    }
}
