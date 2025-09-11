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
        $query = new DbQuery();
        $query->select('id_manifest_type as id, name');
        $query->from('mv_manifest_type');
        $query->orderBy('id_manifest_type ASC');

        return Db::getInstance()->executeS($query);
    }


    public function delete()
    {
        // Check if type is linked to any manifest status
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type` 
            WHERE id_manifest_type = ' . (int)$this->id;

        if (Db::getInstance()->getValue($sql)) {
            throw new PrestaShopException('Cannot remove a manifest type linked to a manifest status');
        }

        return parent::delete();
    }
}
