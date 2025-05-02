<?php

/**
 * OrderLineStatus model class
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

    /** @var string Status */
    public $status;

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
        'table' => 'order_line_status',
        'primary' => 'id_order_line_status',
        'fields' => [
            'id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'comment' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get status by order detail ID
     *
     * @param int $id_order_detail Order detail ID
     * @return array|false Status data
     */
    public static function getByOrderDetail($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('order_line_status');
        $query->where('id_order_detail = ' . (int)$id_order_detail);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Get status by order detail ID and vendor ID
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @return array|false Status data
     */
    public static function getByOrderDetailAndVendor($id_order_detail, $id_vendor)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('order_line_status');
        $query->where('id_order_detail = ' . (int)$id_order_detail);
        $query->where('id_vendor = ' . (int)$id_vendor);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Update status of an order line
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $new_status New status
     * @param int $changed_by ID of the employee or customer who made the change
     * @param string $comment Optional comment
     * @param bool $is_admin Whether the change was made by an admin
     * @return bool Success
     */
    public static function updateStatus($id_order_detail, $id_vendor, $new_status, $changed_by, $comment = null, $is_admin = false)
    {
        $currentStatus = self::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$currentStatus) {
            // Create new status if it doesn't exist
            $orderLineStatus = new OrderLineStatus();
            $orderLineStatus->id_order_detail = (int)$id_order_detail;
            $orderLineStatus->id_vendor = (int)$id_vendor;
            $orderLineStatus->status = $new_status;
            $orderLineStatus->comment = $comment;
            $orderLineStatus->date_add = date('Y-m-d H:i:s');
            $orderLineStatus->date_upd = date('Y-m-d H:i:s');
            $success = $orderLineStatus->save();

            // Log the status change
            if ($success) {
                OrderLineStatusLog::logStatusChange($id_order_detail, $id_vendor, '', $new_status, $changed_by, $comment);
            }

            return $success;
        } else {
            // Update existing status
            $old_status = $currentStatus['status'];

            // Check if vendor has permission to change to this status
            if (!$is_admin) {
                // Get permission for the new status
                if (!self::vendorCanChangeToStatus($new_status)) {
                    return false;
                }
            }

            $success = Db::getInstance()->update('order_line_status', [
                'status' => pSQL($new_status),
                'comment' => pSQL($comment),
                'date_upd' => date('Y-m-d H:i:s')
            ], 'id_order_detail = ' . (int)$id_order_detail . ' AND id_vendor = ' . (int)$id_vendor);

            // Log the status change
            if ($success) {
                OrderLineStatusLog::logStatusChange($id_order_detail, $id_vendor, $old_status, $new_status, $changed_by, $comment);

                // Check if the order status affects commission
                self::processStatusChange($id_order_detail, $id_vendor, $old_status, $new_status);
            }

            return $success;
        }
    }

    /**
     * Process status change to handle commission if needed
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return bool Success
     */
    protected static function processStatusChange($id_order_detail, $id_vendor, $old_status, $new_status)
    {
        // Get order detail
        $orderDetail = new OrderDetail($id_order_detail);
        $id_order = $orderDetail->id_order;

        // Get vendor order detail
        $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        if (!$vendorOrderDetail) {
            return false;
        }

        // Process based on new status
        switch ($new_status) {
            case 'shipped':
                // Create transaction when order is shipped
                $transaction = new VendorTransaction();
                $transaction->id_vendor = $id_vendor;
                $transaction->id_order = $id_order;
                $transaction->commission_amount = $vendorOrderDetail['commission_amount'];
                $transaction->vendor_amount = $vendorOrderDetail['vendor_amount'];
                $transaction->transaction_type = 'commission';
                $transaction->status = 'pending';
                $transaction->date_add = date('Y-m-d H:i:s');
                return $transaction->save();

            case 'cancelled':
                // Cancel any pending transactions for this order detail
                return Db::getInstance()->update('vendor_transaction', [
                    'status' => 'cancelled',
                ], 'id_order = ' . (int)$id_order . ' AND id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');
        }

        return true;
    }

    /**
     * Check if vendor can change to this status
     *
     * @param string $status Status to check
     * @return bool Whether vendor can change to this status
     */
    protected static function vendorCanChangeToStatus($status)
    {
        $allowedStatuses = ['processing', 'shipped', 'cancelled'];

        return in_array($status, $allowedStatuses);
    }
}

/**
 * OrderLineStatusLog model class
 */
class OrderLineStatusLog extends ObjectModel
{
    /** @var int Log ID */
    public $id;

    /** @var int Order detail ID */
    public $id_order_detail;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var string Old status */
    public $old_status;

    /** @var string New status */
    public $new_status;

    /** @var string Comment */
    public $comment;

    /** @var int Changed by (employee/customer ID) */
    public $changed_by;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'order_line_status_log',
        'primary' => 'id_order_line_status_log',
        'fields' => [
            'id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'old_status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'new_status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'comment' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'changed_by' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Log status change
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @param int $changed_by ID of the employee or customer who made the change
     * @param string $comment Optional comment
     * @return bool Success
     */
    public static function logStatusChange($id_order_detail, $id_vendor, $old_status, $new_status, $changed_by, $comment = null)
    {
        return Db::getInstance()->insert('order_line_status_log', [
            'id_order_detail' => (int)$id_order_detail,
            'id_vendor' => (int)$id_vendor,
            'old_status' => pSQL($old_status),
            'new_status' => pSQL($new_status),
            'comment' => pSQL($comment),
            'changed_by' => (int)$changed_by,
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get status history for an order detail
     *
     * @param int $id_order_detail Order detail ID
     * @return array Status history
     */
    public static function getStatusHistory($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('l.*, COALESCE(e.firstname, c.firstname) as changed_by_firstname, COALESCE(e.lastname, c.lastname) as changed_by_lastname');
        $query->from('order_line_status_log', 'l');
        $query->leftJoin('employee', 'e', 'e.id_employee = l.changed_by');
        $query->leftJoin('customer', 'c', 'c.id_customer = l.changed_by');
        $query->where('l.id_order_detail = ' . (int)$id_order_detail);
        $query->orderBy('l.date_add DESC');

        return Db::getInstance()->executeS($query);
    }
}
