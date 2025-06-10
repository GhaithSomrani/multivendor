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
            $currentStatus = VendorHelper::getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor);

            // Get status type info
            $statusType = new OrderLineStatusType($id_status_type);

            if (!Validate::isLoadedObject($statusType)) {
                error_log('Invalid status type ID: ' . $id_status_type);
                return false;
            }

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

                if ($success) {
                    OrderLineStatusLog::logStatusChange($id_order_detail, $id_vendor, $old_status_type_id, $id_status_type, $changed_by, $comment);
                }
            }

            // Process commission if needed - FIXED VERSION
            if ($success && $statusType->affects_commission == 1) {
                self::processCommissionForOrderDetail($id_order_detail, $statusType->commission_action);
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
    protected static function processCommissionForOrderDetail($id_order_detail, $action)
    {
        try {
            return TransactionHelper::processCommissionTransaction($id_order_detail, $action);
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

        return (int)$defaultStatusType['id_order_line_status_type'];
    }

    public static function getDeleteStatusTypeId()
    {
        $deleteStatusType = Db::getInstance()->getRow('
            SELECT id_order_line_status_type FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
            WHERE active = 1 
            ORDER BY position desc
        ');

        return (int)$deleteStatusType['id_order_line_status_type'];
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
        $status = VendorHelper::getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor);

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
