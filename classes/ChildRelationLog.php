<?php

class ChildRelationLog extends ObjectModel
{
    public $id;
    public $parent_entity;
    public $parent_id;
    public $child_entity;
    public $child_id;
    public $action;
    public $changed_by;
    public $date_add;
    public static $actions = ['add', 'update', 'delete'];
    public static $definition = [
        'table' => 'mv_child_relation_logs',
        'primary' => 'id',
        'fields' => [
            'parent_entity' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'parent_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'child_entity' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'child_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'action' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 50],
            'changed_by' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function save($nullValues = false, $autodate = true)
    {
        if (in_array($this->action, self::$actions) === false) {
            return false;
        }
        return parent::save($nullValues, $autodate);
    }
}
