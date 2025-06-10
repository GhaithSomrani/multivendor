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
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true]
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

    /**
     * Get status type by name
     *
     * @param string $name Status name
     * @return array|false Status type
     */
    public static function getByName($name)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_order_line_status_type');
        $query->where('name = "' . pSQL($name) . '"');
        $query->where('active = 1');

        return Db::getInstance()->getRow($query);
    }

    /**
     * Process commission based on status type - uses TransactionHelper
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $commission_action Commission action
     * @return bool Success
     */
    public static function processCommission($id_order_detail, $commission_action)
    {
        try {
            // Use TransactionHelper for all transaction processing
            return TransactionHelper::processCommissionTransaction($id_order_detail, $commission_action);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'OrderLineStatusType::processCommission - Error: ' . $e->getMessage(),
                3,
                null,
                'OrderLineStatusType',
                $id_order_detail
            );
            return false;
        }
    }

    /**
     * Get commission actions that create transactions
     *
     * @return array Actions that require transaction creation
     */
    public static function getTransactionActions()
    {
        return ['add', 'cancel', 'refund'];
    }

    /**
     * Get default status type ID
     *
     * @return int Default status type ID
     */
    public static function getDefaultStatusTypeId()
    {
        $query = new DbQuery();
        $query->select('id_order_line_status_type');
        $query->from('mv_order_line_status_type');
        $query->where('active = 1');
        $query->orderBy('position ASC');

        $result = Db::getInstance()->getValue($query);
        return (int)$result;
    }

    /**
     * Get statuses by commission action
     *
     * @param string $commission_action Commission action (add, cancel, refund, none)
     * @return array Status types with this commission action
     */
    public static function getByCommissionAction($commission_action)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_order_line_status_type');
        $query->where('commission_action = "' . pSQL($commission_action) . '"');
        $query->where('active = 1');
        $query->orderBy('position ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Validate commission action
     *
     * @param string $action Action to validate
     * @return bool Is valid action
     */
    public static function isValidCommissionAction($action)
    {
        $validActions = ['add', 'cancel', 'refund', 'none'];
        return in_array($action, $validActions);
    }

    /**
     * Get status types available for vendor
     *
     * @return array Vendor-allowed status types
     */
    public static function getVendorAllowedStatuses()
    {
        return self::getAllActiveStatusTypes(true, false);
    }

    /**
     * Get status types available for admin only
     *
     * @return array Admin-only status types
     */
    public static function getAdminOnlyStatuses()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_order_line_status_type');
        $query->where('active = 1');
        $query->where('is_admin_allowed = 1');
        $query->where('is_vendor_allowed = 0');
        $query->orderBy('position ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Check if status allows vendor modification
     *
     * @param int $id_status_type Status type ID
     * @return bool Can vendor modify
     */
    public static function canVendorModify($id_status_type)
    {
        $query = new DbQuery();
        $query->select('is_vendor_allowed');
        $query->from('mv_order_line_status_type');
        $query->where('id_order_line_status_type = ' . (int)$id_status_type);
        $query->where('active = 1');

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Update status position (for admin reordering)
     *
     * @param int $id_status_type Status type ID
     * @param int $new_position New position
     * @return bool Success
     */
    public static function updatePosition($id_status_type, $new_position)
    {
        return Db::getInstance()->update(
            'mv_order_line_status_type',
            ['position' => (int)$new_position],
            'id_order_line_status_type = ' . (int)$id_status_type
        );
    }

    /**
     * Get next position for new status
     *
     * @return int Next available position
     */
    public static function getNextPosition()
    {
        $query = new DbQuery();
        $query->select('MAX(position) + 1');
        $query->from('mv_order_line_status_type');

        $position = Db::getInstance()->getValue($query);
        return $position ? (int)$position : 1;
    }

    /**
     * Toggle status active state
     *
     * @param int $id_status_type Status type ID
     * @return bool Success
     */
    public static function toggleActive($id_status_type)
    {
        $current = new self($id_status_type);
        if (!Validate::isLoadedObject($current)) {
            return false;
        }

        $current->active = !$current->active;
        return $current->save();
    }
}
