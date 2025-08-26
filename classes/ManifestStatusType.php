<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ManifestStatusType extends ObjectModel
{
    public $name;
    public $allowed_manifest_status_type_ids;
    public $allowed_order_line_status_type_ids;
    public $next_order_line_status_type_ids;
    public $allowed_manifest_type;
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
            'next_order_line_status_type_ids' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'allowed_manifest_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'values' => ['pickup', 'returns']],
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
        if (!$this->position) {
            $this->position = $this->getNextPosition();
        }
        return parent::add($auto_date, $null_values);
    }

    private function getNextPosition()
    {
        $sql = 'SELECT MAX(position) + 1 FROM `' . _DB_PREFIX_ . 'mv_manifest_status_type`';
        return (int)Db::getInstance()->getValue($sql) ?: 1;
    }
}
