<?php

/**
 * OrderLineStatus model class - FIXED VERSION
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderLineStatus extends ObjectModel
{
    /** @var int Order line status ID */
    public $id;

    /** @var int Order detail ID */
    public $id_order_detail;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Status Type ID */
    public $id_order_line_status_type;

    /** @var string Comment */
    public $comment;

    /** @var string Creation date */
    public $date_add;

    /** @var string Last update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_order_line_status',
        'primary' => 'id_order_line_status',
        'fields' => [
            'id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order_line_status_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'comment' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get status by order detail ID with status type details
     *
     * @param int $id_order_detail Order detail ID
     * @return array|false Status data with type information
     */
    public static function getByOrderDetail($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('ols.*, olst.name as status_name, olst.color, olst.commission_action');
        $query->from('mv_order_line_status', 'ols');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('ols.id_order_detail = ' . (int)$id_order_detail);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Get status by order detail ID and vendor ID with status type details
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array|false Status data with type information
     */
    public static function getByOrderDetailAndVendor($id_order_detail, $id_vendor)
    {
        $query = new DbQuery();
        $query->select('ols.*, olst.name as status_name, olst.color, olst.commission_action, olst.affects_commission');
        $query->from('mv_order_line_status', 'ols');
        $query->leftJoin('mv_order_line_status_type', 'olst', 'olst.id_order_line_status_type = ols.id_order_line_status_type');
        $query->where('ols.id_order_detail = ' . (int)$id_order_detail);
        $query->where('ols.id_vendor = ' . (int)$id_vendor);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Update status of an order line - FIXED VERSION
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param int $id_status_type Status type ID
     * @param int $changed_by ID of the employee or customer who made the change
     * @param string $comment Optional comment
     * @param bool $is_admin Whether the change was made by an admin
     * @return bool Success
     */
    public static function updateStatus($id_order_detail, $id_vendor, $id_status_type, $changed_by, $comment = null, $is_admin = false)
    {
        try {
            // Get current status
            $currentStatus = self::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

            // Get status type info
            $statusType = new OrderLineStatusType($id_status_type);

            if (!Validate::isLoadedObject($statusType)) {
                error_log('Invalid status type ID: ' . $id_status_type);
                return false;
            }

            // Check permissions
            if (!$is_admin && $statusType->is_vendor_allowed != 1) {
                error_log('Vendor not allowed to set status: ' . $statusType->name);
                return false;
            }

            if ($is_admin && $statusType->is_admin_allowed != 1) {
                error_log('Admin not allowed to set status: ' . $statusType->name);
                return false;
            }

            $success = false;

            if (!$currentStatus) {
                // Create new status if it doesn't exist
                $orderLineStatus = new OrderLineStatus();
                $orderLineStatus->id_order_detail = (int)$id_order_detail;
                $orderLineStatus->id_vendor = (int)$id_vendor;
                $orderLineStatus->id_order_line_status_type = (int)$id_status_type;
                $orderLineStatus->comment = $comment;
                $orderLineStatus->date_add = date('Y-m-d H:i:s');
                $orderLineStatus->date_upd = date('Y-m-d H:i:s');
                $success = $orderLineStatus->save();

                // Log the status change
                if ($success) {
                    OrderLineStatusLog::logStatusChange($id_order_detail, $id_vendor, null, $id_status_type, $changed_by, $comment);
                }
            } else {
                // Update existing status
                $old_status_type_id = $currentStatus['id_order_line_status_type'];

                $success = Db::getInstance()->update('mv_order_line_status', [
                    'id_order_line_status_type' => (int)$id_status_type,
                    'comment' => pSQL($comment),
                    'date_upd' => date('Y-m-d H:i:s')
                ], 'id_order_detail = ' . (int)$id_order_detail . ' AND id_vendor = ' . (int)$id_vendor);

                // Log the status change
                if ($success) {
                    OrderLineStatusLog::logStatusChange($id_order_detail, $id_vendor, $old_status_type_id, $id_status_type, $changed_by, $comment);
                }
            }

            // Process commission if needed - FIXED VERSION
            if ($success && $statusType->affects_commission == 1) {
                self::processCommissionForOrderDetail($id_order_detail, $id_vendor, $statusType->commission_action);
            }

            return $success;

        } catch (Exception $e) {
            error_log('Error in OrderLineStatus::updateStatus: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Process commission for order detail - NEW METHOD
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $action Commission action
     * @return bool Success
     */
    protected static function processCommissionForOrderDetail($id_order_detail, $id_vendor, $action)
    {
        try {
            // Get vendor order detail
            $vendorOrderDetail = Db::getInstance()->getRow('
                SELECT vod.*, od.id_order 
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vod.id_order_detail
                WHERE vod.id_order_detail = ' . (int)$id_order_detail . ' 
                AND vod.id_vendor = ' . (int)$id_vendor
            );

            if (!$vendorOrderDetail) {
                error_log('Vendor order detail not found for order_detail: ' . $id_order_detail . ', vendor: ' . $id_vendor);
                return false;
            }

            $id_order = $vendorOrderDetail['id_order'];

            switch ($action) {
                case 'add':
                    // Create transaction when status is set
                    $transaction = new VendorTransaction();
                    $transaction->id_vendor = $id_vendor;
                    $transaction->id_order = $id_order;
                    $transaction->order_detail_id = $id_order_detail;
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
                    ], 'order_detail_id = ' . (int)$id_order_detail . ' AND id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');

                case 'refund':
                    // Create a negative transaction for refund
                    $transaction = new VendorTransaction();
                    $transaction->id_vendor = $id_vendor;
                    $transaction->id_order = $id_order;
                    $transaction->order_detail_id = $id_order_detail;
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

        } catch (Exception $e) {
            error_log('Error in processCommissionForOrderDetail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get default status type ID
     *
     * @return int Default status type ID
     */
    public static function getDefaultStatusTypeId()
    {
        $defaultStatusType = Db::getInstance()->getRow('
            SELECT id_order_line_status_type FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
            WHERE active = 1 
            ORDER BY position ASC 
        ');

        return $defaultStatusType ? (int)$defaultStatusType['id_order_line_status_type'] : 1;
    }

    /**
     * Get status with full type information by order detail and vendor
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array Status information including type details
     */
    public static function getFullStatusInfo($id_order_detail, $id_vendor)
    {
        $status = self::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$status) {
            // Return default status if no status exists
            $defaultStatusTypeId = self::getDefaultStatusTypeId();
            $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

            return [
                'id_order_line_status_type' => $defaultStatusTypeId,
                'status_name' => $defaultStatusType->name,
                'color' => $defaultStatusType->color,
                'commission_action' => $defaultStatusType->commission_action,
                'affects_commission' => $defaultStatusType->affects_commission,
                'comment' => null,
                'date_add' => null,
                'date_upd' => null
            ];
        }

        return $status;
    }
}