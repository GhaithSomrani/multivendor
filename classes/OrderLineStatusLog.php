<?php

/**
 * OrderLineStatusLog model class - Updated to use status type IDs
 */
class OrderLineStatusLog extends ObjectModel
{
    /** @var int Log ID */
    public $id;

    /** @var int Order detail ID */
    public $id_order_detail;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Old status type ID */
    public $old_id_order_line_status_type;

    /** @var int New status type ID */
    public $new_id_order_line_status_type;

    /** @var string Comment */
    public $comment;

    /** @var string Changed by (employee/customer ID) */
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
            'old_id_order_line_status_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'new_id_order_line_status_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'comment' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'changed_by' => ['type' => self::TYPE_STRING, 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];
    protected $webserviceParameters = [
        'objectsNodeName' => 'order_line_history',
        'objectNodeName' => 'order_line_history',
        'fields' => [
            'id_order_detail' => [],
            'id_vendor' => [],
            'old_id_order_line_status_type' => [],
            'new_id_order_line_status_type' => [],
            'comment' => [],
            'changed_by' => [],
            'date_add' => []
        ]
    ];
    /**
     * Log status change
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param int|null $old_status_type_id Old status type ID
     * @param int $new_status_type_id New status type ID
     * @param int $changed_by ID of the employee or customer who made the change
     * @param string $comment Optional comment
     * @return bool Success
     */
    public static function logStatusChange($id_order_detail, $id_vendor, $old_status_type_id, $new_status_type_id, $changed_by, $comment = null)
    {
        $log = new OrderLineStatusLog();
        $log->id_order_detail = (int)$id_order_detail;
        $log->id_vendor = (int)$id_vendor;
        $log->old_id_order_line_status_type = $old_status_type_id ? (int)$old_status_type_id : null;
        $log->new_id_order_line_status_type = (int)$new_status_type_id;
        $log->comment = $comment;
        $log->changed_by = $changed_by;
        return $log->save();
    }

    /**
     * Get status history for an order detail with status type names
     *
     * @param int $id_order_detail Order detail ID
     * @return array Status history with readable status names
     */
    public static function getStatusHistory($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('l.*, 
            old_st.name as old_status_name, 
            new_st.name as new_status_name,
            old_st.color as old_status_color, 
            new_st.color as new_status_color',);
        $query->from('mv_order_line_status_log', 'l');
        $query->leftJoin('mv_order_line_status_type', 'old_st', 'old_st.id_order_line_status_type = l.old_id_order_line_status_type');
        $query->leftJoin('mv_order_line_status_type', 'new_st', 'new_st.id_order_line_status_type = l.new_id_order_line_status_type');
        $query->where('l.id_order_detail = ' . (int)$id_order_detail);
        $query->orderBy('l.date_add DESC');

        return Db::getInstance()->executeS($query);
    }
}
