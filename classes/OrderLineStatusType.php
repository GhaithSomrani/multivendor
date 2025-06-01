<?php

/**
 * OrderLineStatusType model class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderLineStatusType extends ObjectModel
{
    /** @var int Status type ID */
    public $id;

    /** @var string Name */
    public $name;

    /** @var string Color */
    public $color;

    /** @var bool Is vendor allowed to set this status */
    public $is_vendor_allowed;

    /** @var bool Is admin allowed to set this status */
    public $is_admin_allowed;

    /** @var bool Does this status affect commission */
    public $affects_commission;

    /** @var string Commission action (none, add, cancel, refund) */
    public $commission_action;

    /** @var int Position */
    public $position;

    /** @var bool Active */
    public $active;

    /** @var string Creation date */
    public $date_add;

    /** @var string Last update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_order_line_status_type',
        'primary' => 'id_order_line_status_type',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
            'is_vendor_allowed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'is_admin_allowed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'affects_commission' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'commission_action' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get all active status types
     *
     * @param bool $vendor_only Only get statuses allowed for vendors
     * @param bool $admin_only Only get statuses allowed for admin
     * @return array Status types
     */
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

        return Db::getInstance()->getRow($query);
    }

    /**
     * Process commission based on status type
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $action Commission action
     * @return bool Success
     */
    public static function processCommission($id_order_detail, $id_vendor, $action)
    {
        // Get vendor order detail
        $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$vendorOrderDetail) {
            return false;
        }

        // Get order
        $id_order = $vendorOrderDetail['id_order'];

        switch ($action) {
            case 'add':
                // Create transaction when status is set
                $transaction = new VendorTransaction();
                $transaction->id_vendor = $id_vendor;
                $transaction->id_order = $id_order;
                $transaction->commission_amount = $vendorOrderDetail['commission_amount'];
                $transaction->vendor_amount = $vendorOrderDetail['vendor_amount'];
                $transaction->transaction_type = 'commission';
                $transaction->status = 'pending';
                $transaction->date_add = date('Y-m-d H:i:s');
                return $transaction->save();

            case 'cancel':
                // Cancel any pending transactions for this order detail
                return Db::getInstance()->update('mv_vendor_transaction', [
                    'status' => 'cancelled',
                ], 'id_order = ' . (int)$id_order . ' AND id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');

            case 'refund':
                // Create a negative transaction for refund
                $transaction = new VendorTransaction();
                $transaction->id_vendor = $id_vendor;
                $transaction->id_order = $id_order;
                $transaction->commission_amount = -$vendorOrderDetail['commission_amount'];
                $transaction->vendor_amount = -$vendorOrderDetail['vendor_amount'];
                $transaction->transaction_type = 'refund';
                $transaction->status = 'pending';
                $transaction->date_add = date('Y-m-d H:i:s');
                return $transaction->save();

            case 'none':
            default:
                return true;
        }
    }

    public function delete()
    {
        // Check if this status is assigned to any order lines
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('mv_order_line_status');
        $query->where('status = "' . pSQL($this->name) . '"');
        $count = Db::getInstance()->getValue($query);

        if ($count > 0) {
            // Cannot delete - status is in use
            throw new PrestaShopException(
                sprintf(
                    'Cannot delete status "%s" because it is assigned to %d order line(s). Please change the status of these order lines first.',
                    $this->name,
                    $count
                )
            );
        }

        return parent::delete();
    }
}
