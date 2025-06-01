
<?php

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
        'table' => 'mv_order_line_status_log',
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
        return Db::getInstance()->insert('mv_order_line_status_log', [
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
        $query->from('mv_order_line_status_log', 'l');
        $query->leftJoin('employee', 'e', 'e.id_employee = l.changed_by');
        $query->leftJoin('customer', 'c', 'c.id_customer = l.changed_by');
        $query->where('l.id_order_detail = ' . (int)$id_order_detail);
        $query->orderBy('l.date_add DESC');

        return Db::getInstance()->executeS($query);
    }
}
