<?php
// classes/StatusChangeLog.php

class StatusChangeLog extends ObjectModel
{
    public $id;
    public $entity_type;
    public $entity_id;
    public $old_status;
    public $new_status;
    public $changed_by;
    public $date_add;

    public static $definition = [
        'table' => 'mv_status_change_logs',
        'primary' => 'id',
        'fields' => [
            'entity_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'entity_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'old_status' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'new_status' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'changed_by' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
