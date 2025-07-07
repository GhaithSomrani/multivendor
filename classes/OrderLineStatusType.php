<?php

/**
 * Optimized OrderLineStatusType model class
 * Handles commission processing with transaction optimization
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderLineStatusType extends ObjectModel
{
    /** @var string Status name */
    public $name;

    /** @var string Status color */
    public $color;

    /** @var string Commission action */
    public $commission_action;

    /** @var bool Is vendor allowed */
    public $is_vendor_allowed;

    /** @var bool Is admin allowed */
    public $is_admin_allowed;

    /** @var int Position */
    public $position;

    /** @var string  Available status */
    public $available_status;

    /** @var bool Active */
    public $active;

    /** @var bool affect Commission */
    public $affects_commission;


    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_order_line_status_type',
        'primary' => 'id_order_line_status_type',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'required' => true, 'size' => 7],
            'commission_action' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'is_vendor_allowed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'is_admin_allowed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'affects_commission' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'available_status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true]
        ]
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'order_line_status_types',
        'objectNodeName' => 'order_line_status_type',
        'fields' => [
            'name' => [],
            'color' => [],
            'is_vendor_allowed' => [],
            'is_admin_allowed' => [],
            'affects_commission' => [],
            'commission_action' => [],
            'position' => [],
            'active' => []
        ],
        'hidden_fields' => [
            'is_vendor_allowed',
            'is_admin_allowed',
            'affects_commission', 
            'commission_action',
            'color',

        ]
    ];

    public static function getAllActiveStatusTypes($vendor_only = false, $admin_only = false)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_order_line_status_type');
        $query->where('active = 1');

        if ($vendor_only) {
            $query->where('is_vendor_allowed = 1');
        }

        if ($admin_only) {
            $query->where('is_admin_allowed = 1');
        }

        $query->orderBy('position ASC');


        return Db::getInstance()->executeS($query);
    }

    protected function isActiveStatus($id_order_line_status_type)
    {
        $query = new DbQuery();
        $query->select('active');
        $query->from('mv_order_line_status_type');
        $query->where('id_order_line_status_type = ' . (int)$id_order_line_status_type);
        $active = Db::getInstance()->getValue($query);
        return (bool)$active;
    }

    public static function getAvailableStatusListBystatusId($id_order_line_status_type = 0)
    {
        $query = new DbQuery();
        $query->select('available_status');
        $query->from('mv_order_line_status_type');
        if ($id_order_line_status_type > 0) {
            $query->where('id_order_line_status_type = ' . $id_order_line_status_type);
        } else {
            return [];
        }
        $result = Db::getInstance()->getValue($query);
        if (!$result) {
        }
        $available_status = explode(',', $result);
        return $available_status;
    }


    public static function isAvailableStatus($current_status_id, $next_status_id)
    {
        if (self::isActiveStatus($next_status_id)) {
            $available_status = self::getAvailableStatusListBystatusId($current_status_id);
            return in_array($next_status_id, $available_status);
        } else {
            return false;
        }
    } 
    
}
