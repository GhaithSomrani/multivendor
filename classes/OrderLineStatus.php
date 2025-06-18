<?php

/**
 * OrderLineStatus model class - FIXED VERSION for Webservice
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
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'id_order_line_status_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'comment' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public  $webserviceParameters = [
        'objectsNodeName' => 'order_line_statuses',
        'objectNodeName' => 'order_line_status',
        'fields' => [
            'id_order_line_status' => ['required' => false],
            'id_order_detail' => ['xlink_resource' => 'order_line_statuses'],
            'id_vendor' => ['required' => false], // Make optional for webservice
            'id_order_line_status_type' => [],
            'comment' => [],
            'date_add' => [],
            'date_upd' => []
        ]
    ];

    /**
     * Get vendor ID by order detail ID
     *
     * @param int $id_order_detail Order detail ID
     * @return int|false Vendor ID or false if not found
     */
    public static function getVendorByOrderDetail($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('id_vendor');
        $query->from('mv_vendor_order_detail');
        $query->where('id_order_detail = ' . (int)$id_order_detail);
        
        $result = Db::getInstance()->getValue($query);
        return $result ? (int)$result : false;
    }

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
     * Update status using webservice with only order detail ID and status type ID
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_status_type Status type ID
     * @return bool Success
     */
    public static function updateStatusByWebservice($id_order_detail, $id_status_type)
    {
        // Get vendor ID automatically from order detail
        $id_vendor = self::getVendorByOrderDetail($id_order_detail);
        
        if (!$id_vendor) {
            error_log('Vendor not found for order detail ID: ' . $id_order_detail);
            return false;
        }

        $changed_by = 0; // System/webservice user
        $comment = 'Updated via webservice';
        $is_admin = true; // Webservice has admin privileges

        return self::updateStatus($id_order_detail, $id_vendor, $id_status_type, $changed_by, $comment, $is_admin);
    }

    /**
     * Override add method for webservice compatibility
     */
    public function add($autodate = true, $nullValues = false)
    {
        // If vendor ID is not provided, try to get it from order detail
        if (empty($this->id_vendor) && !empty($this->id_order_detail)) {
            $this->id_vendor = self::getVendorByOrderDetail($this->id_order_detail);
            
            if (!$this->id_vendor) {
                error_log('Cannot determine vendor for order detail ID: ' . $this->id_order_detail);
                return false;
            }
        }

        if ($this->id_order_detail && $this->id_order_line_status_type) {
            // Use the updateStatusByWebservice method which handles both create and update
            $success = self::updateStatusByWebservice($this->id_order_detail, $this->id_order_line_status_type);
            
            if ($success) {
                // Set the ID for webservice response - find the created/updated record
                $existingRecord = self::getByOrderDetailId($this->id_order_detail);
                if ($existingRecord) {
                    $this->id = $existingRecord['id_order_line_status'];
                }
            }
            
            return $success;
        }

        error_log('Missing required fields: id_order_detail=' . $this->id_order_detail . 
                 ', id_order_line_status_type=' . $this->id_order_line_status_type . 
                 ', id_vendor=' . $this->id_vendor);
        return false;
    }

    /**
     * Override update method for webservice compatibility
     */
    public function update($autodate = true, $nullValues = false)
    {
        // If vendor ID is not provided, try to get it from order detail
        if (empty($this->id_vendor) && !empty($this->id_order_detail)) {
            $this->id_vendor = self::getVendorByOrderDetail($this->id_order_detail);
        }

        if ($this->id_order_detail && $this->id_order_line_status_type && $this->id_vendor) {
            return self::updateStatusByWebservice($this->id_order_detail, $this->id_order_line_status_type);
        }

        error_log('Missing required fields for update: id_order_detail=' . $this->id_order_detail . 
                 ', id_order_line_status_type=' . $this->id_order_line_status_type . 
                 ', id_vendor=' . $this->id_vendor);
        return false;
    }

    /**
     * Get existing order line status by order detail ID
     *
     * @param int $id_order_detail Order detail ID
     * @return array|false Existing status record or false
     */
    public static function getByOrderDetailId($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_order_line_status');
        $query->where('id_order_detail = ' . (int)$id_order_detail);
        
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
            $currentStatus = VendorHelper::getOrderLineStatusByOrderDetailAndVendor($id_order_detail, $id_vendor);
            $statusType = new OrderLineStatusType($id_status_type);
            
            if (!OrderHelper::isChangableStatusType($id_order_detail, $id_status_type)) {
                error_log('Status change not allowed for order detail: ' . $id_order_detail);
                return false;
            }

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
                // Create new status record
                $orderLineStatus = new OrderLineStatus();
                $orderLineStatus->id_order_detail = (int)$id_order_detail;
                $orderLineStatus->id_vendor = (int)$id_vendor;
                $orderLineStatus->id_order_line_status_type = (int)$id_status_type;
                $orderLineStatus->comment = $comment;
                $orderLineStatus->date_add = date('Y-m-d H:i:s');
                $orderLineStatus->date_upd = date('Y-m-d H:i:s');
                $success = $orderLineStatus->save();

                if ($success) {
                    OrderLineStatusLog::logStatusChange($id_order_detail, $id_vendor, null, $id_status_type, $changed_by, $comment);
                }
            } else {
                // Update existing status record
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

            // Process commission if needed
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
     * Process commission for order detail
     *
     * @param int $id_order_detail Order detail ID
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

    /**
     * Get delete status type ID
     *
     * @return int Delete status type ID
     */
    public static function getDeleteStatusTypeId()
    {
        $deleteStatusType = Db::getInstance()->getRow('
            SELECT id_order_line_status_type FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
            WHERE active = 1 
            ORDER BY position DESC
        ');

        return (int)$deleteStatusType['id_order_line_status_type'];
    }
}