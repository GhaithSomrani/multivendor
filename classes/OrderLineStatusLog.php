<?php

/**
 * OrderLineStatusLog model class - Updated to use status type IDs with webservice user name resolution
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
            'old_id_order_line_status_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'new_id_order_line_status_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'comment' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'changed_by' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
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
            'changed_by' => ['getter' => 'getChangedByName', 'setter' => false],
            'date_add' => []
        ]
    ];

    /**
     * Get the name of the user who made the change for webservice
     * Used by webservice getter
     * 
     * @return string User name or "System"
     */
    public function getChangedByName()
    {
        if ($this->changed_by == 0) {
            return 'System';
        }

        // Try to get employee name first (admin)
        $employee = new Employee($this->changed_by);
        if (Validate::isLoadedObject($employee)) {
            return trim($employee->firstname . ' ' . $employee->lastname);
        }

        // Try to get customer name (vendor)
        $customer = new Customer($this->changed_by);
        if (Validate::isLoadedObject($customer)) {
            return trim($customer->firstname . ' ' . $customer->lastname);
        }

        return 'Unknown User';
    }

    /**
     * Get the type of user who made the change for webservice
     * 
     * @return string User type: 'system', 'admin', 'vendor', or 'unknown'
     */
    

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
        return Db::getInstance()->insert('mv_order_line_status_log', [
            'id_order_detail' => (int)$id_order_detail,
            'id_vendor' => (int)$id_vendor,
            'old_id_order_line_status_type' => $old_status_type_id ? (int)$old_status_type_id : null,
            'new_id_order_line_status_type' => (int)$new_status_type_id,
            'comment' => pSQL($comment),
            'changed_by' => (int)$changed_by,
            'date_add' => date('Y-m-d H:i:s')
        ]);
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
            new_st.color as new_status_color,
            COALESCE(e.firstname, c.firstname) as changed_by_firstname, 
            COALESCE(e.lastname, c.lastname) as changed_by_lastname,
            CASE 
                WHEN l.changed_by = 0 THEN "system"
                WHEN e.id_employee IS NOT NULL THEN "admin"
                WHEN c.id_customer IS NOT NULL THEN "vendor"
                ELSE "unknown"
            END as changed_by_type');
        $query->from('mv_order_line_status_log', 'l');
        $query->leftJoin('mv_order_line_status_type', 'old_st', 'old_st.id_order_line_status_type = l.old_id_order_line_status_type');
        $query->leftJoin('mv_order_line_status_type', 'new_st', 'new_st.id_order_line_status_type = l.new_id_order_line_status_type');
        $query->leftJoin('employee', 'e', 'e.id_employee = l.changed_by');
        $query->leftJoin('customer', 'c', 'c.id_customer = l.changed_by');
        $query->where('l.id_order_detail = ' . (int)$id_order_detail);
        $query->orderBy('l.date_add DESC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get status history with detailed user information for webservice
     *
     * @param int $id_order_detail Order detail ID
     * @return array Status history with user details
     */
    public static function getStatusHistoryForWebservice($id_order_detail)
    {
        $history = self::getStatusHistory($id_order_detail);
        
        foreach ($history as &$entry) {
            if ($entry['changed_by'] == 0) {
                $entry['changed_by_name'] = 'System';
            } else {
                $entry['changed_by_name'] = trim($entry['changed_by_firstname'] . ' ' . $entry['changed_by_lastname']);
            }
        }
        
        return $history;
    }

}