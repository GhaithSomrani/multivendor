<?php

/**
 * OrderLineStatus model class - UPDATED VERSION with update() override
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
            'id' => [],
            'id_order_detail' => ['xlink_resource' => 'order_line_statuses', 'setter' => 'setorderlinedetails'],
            'id_vendor' => ['required' => false],
            'id_order_line_status_type' => [],
            'comment' => [],
            'date_add' => [],
            'date_upd' => []
        ],


    ];

    /**
     * Override update method to handle status updates with changability validation
     */
    public function update($nullValues = false)
    {
        try {
            // Auto-determine vendor if not set
            if (empty($this->id_vendor) && !empty($this->id_order_detail)) {
                $this->id_vendor = self::getVendorByOrderDetail($this->id_order_detail);

                if (!$this->id_vendor) {
                    $error_msg = 'Impossible de déterminer le vendeur pour l\'ID de détail de commande : ' . $this->id_order_detail;
                    error_log($error_msg);
                    PrestaShopLogger::addLog($error_msg, 3, null, 'OrderLineStatus', $this->id, true);

                    if ($this->isApiCall()) {
                        $this->setWsError($error_msg, 400);
                        return false;
                    }
                    return false;
                }
            }

            // Check if status change is allowed (changability validation)
            if (!OrderHelper::isChangableStatusType($this->id_order_detail, $this->id_order_line_status_type)) {
                $error_msg = 'Changement de statut non autorisé pour le détail de commande : ' . $this->id_order_detail;
                error_log($error_msg);
                PrestaShopLogger::addLog($error_msg, 3, null, 'OrderLineStatus', $this->id, true);

                if ($this->isApiCall()) {
                    $this->setWsError($error_msg, 403);
                    return false;
                }
                return false;
            }

            // Validate status type
            $statusType = new OrderLineStatusType($this->id_order_line_status_type);
            if (!Validate::isLoadedObject($statusType)) {
                $error_msg = 'ID de type de statut invalide : ' . $this->id_order_line_status_type;
                error_log($error_msg);
                PrestaShopLogger::addLog($error_msg, 3, null, 'OrderLineStatus', $this->id, true);

                if ($this->isApiCall()) {
                    $this->setWsError($error_msg, 400);
                    return false;
                }
                return false;
            }

            // Check user permissions
            $is_api_call = $this->isApiCall();
            $is_admin = $is_api_call ? true : (Context::getContext()->employee ? (bool)Context::getContext()->employee->id : false);

            if (!$is_api_call) {
                if (!$is_admin && $statusType->is_vendor_allowed != 1) {
                    $error_msg = 'Vendeur non autorisé à définir le statut : ' . $statusType->name;
                    error_log($error_msg);
                    PrestaShopLogger::addLog($error_msg, 3, null, 'OrderLineStatus', $this->id, true);
                    return false;
                }

                if ($is_admin && $statusType->is_admin_allowed != 1) {
                    $error_msg = 'Administrateur non autorisé à définir le statut : ' . $statusType->name;
                    error_log($error_msg);
                    PrestaShopLogger::addLog($error_msg, 3, null, 'OrderLineStatus', $this->id, true);
                    return false;
                }
            }

            // Get current status for logging
            $currentStatus = self::getByOrderDetailAndVendor($this->id_order_detail, $this->id_vendor);
            $old_status_type_id = $currentStatus ? $currentStatus['id_order_line_status_type'] : null;

            // Perform the update
            $success = parent::update($nullValues);

            // Log status change if successful
            if ($success) {
                OrderLineStatusLog::logStatusChange(
                    $this->id_order_detail,
                    $this->id_vendor,
                    $old_status_type_id,
                    $this->id_order_line_status_type,
                    $this->getChangedBy(),
                    $this->comment
                );
            }

            // Process commission if the status affects commission
            if ($success && $statusType->affects_commission == 1) {
                self::processCommissionForOrderDetail($this->id_order_detail, $statusType->commission_action);
            }

            return $success;
        } catch (Exception $e) {
            $error_msg = 'Erreur dans OrderLineStatus::update : ' . $e->getMessage();
            PrestaShopLogger::addLog($error_msg . ' \n - Trace de la pile : ' . $e->getTraceAsString(), 3, null, 'OrderLineStatus', $this->id, true);

            if ($this->isApiCall()) {
                $this->setWsError($error_msg, 500);
                return false;
            }
            return false;
        }
    }

    /**
     * Standard save method without changability validation - used for initial creation
     */
    public function save($nullValues = false, $autodate = true)
    {
        try {
            // Auto-determine vendor if not set
            if (empty($this->id_vendor) && !empty($this->id_order_detail)) {
                $this->id_vendor = self::getVendorByOrderDetail($this->id_order_detail);

                if (!$this->id_vendor) {
                    error_log('Impossible de déterminer le vendeur pour l\'ID de détail de commande : ' . $this->id_order_detail);
                    PrestaShopLogger::addLog(
                        'Impossible de déterminer le vendeur pour l\'ID de détail de commande : ' . $this->id_order_detail,
                        3,
                        null,
                        'OrderLineStatus',
                        $this->id,
                        true
                    );
                    return false;
                }
            }

            return parent::save($nullValues, $autodate);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Erreur dans OrderLineStatus::save : ' . $e->getMessage() . ' \n - Trace de la pile : ' . $e->getTraceAsString(),
                3,
                null,
                'OrderLineStatus',
                $this->id,
                true
            );
            return false;
        }
    }

    /**
     * Get the user who made the change
     *
     * @return int User ID
     */
    protected function getChangedBy()
    {
        if (Context::getContext()->employee && Context::getContext()->employee->id) {
            return (int)Context::getContext()->employee->id;
        }
        if (Context::getContext()->customer && Context::getContext()->customer->id) {
            return (int)Context::getContext()->customer->id;
        }
        return 0; // System/webservice
    }

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
        $query->from('mv_order_line_status');
        $query->where('id_order_detail = ' . (int)$id_order_detail . ' AND id_vendor = ' . (int)$id_vendor);

        return Db::getInstance()->getRow($query);
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
            error_log('Erreur dans processCommissionForOrderDetail : ' . $e->getMessage());
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

    /**
     * Legacy method for backward compatibility - creates or updates status
     */
    public static function updateStatus($id_order_detail, $id_vendor, $id_status_type, $changed_by, $comment = null, $is_admin = false)
    {
        // Check if status already exists
        $currentStatus = self::getByOrderDetailAndVendor($id_order_detail, $id_vendor);
     
        if ($currentStatus) {
            $orderLineStatus = new OrderLineStatus($currentStatus['id_order_line_status']);
            $orderLineStatus->id_order_line_status_type = (int)$id_status_type;
            $orderLineStatus->comment = $comment;

            return $orderLineStatus->update();
        } else {
            // Create new status
            $orderLineStatus = new OrderLineStatus();
            $orderLineStatus->id_order_detail = (int)$id_order_detail;
            $orderLineStatus->id_vendor = (int)$id_vendor;
            $orderLineStatus->id_order_line_status_type = (int)$id_status_type;
            $orderLineStatus->comment = $comment;

            return $orderLineStatus->save();
        }
    }


    /**
     * Set webservice error for API calls
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     */
    protected function setWsError($message, $code = 400)
    {
        if (class_exists('WebserviceRequest') && method_exists('WebserviceRequest', 'getInstance')) {
            $webservice = WebserviceRequest::getInstance();
            if (method_exists($webservice, 'setError')) {
                $webservice->setError($code, $message, 999);
            }
        }

        if (Context::getContext()->controller) {
            Context::getContext()->controller->errors[] = $message;
        }

        error_log('Erreur du service web ' . $code . ' : ' . $message);
    }

    /**
     * Check if the current request is an API call
     */
    protected function isApiCall()
    {
        return (Tools::getValue('ws_key') || Tools::getValue('key')) ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) ||
            (Context::getContext()->controller && Context::getContext()->controller instanceof WebserviceRequestCore) ||
            (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'PrestaShop Webservice') !== false);
    }


    public function setorderlinedetails($id_order_detail)
    {
        // First, validate that the order detail exists
        $query = new DbQuery();
        $query->select('id_order_detail');
        $query->from('order_detail');
        $query->where('id_order_detail = ' . (int)$id_order_detail);

        $existing_order_detail = Db::getInstance()->getValue($query);

        if (!$existing_order_detail) {
            $this->setWsError('L\'ID de détail de commande n\'existe pas : ' . $id_order_detail, 400);
            return false;
        }

        if ($this->id) {
            $query = new DbQuery();
            $query->select('id_order_detail');
            $query->from('mv_order_line_status');
            $query->where('id_order_line_status = ' . (int)$this->id);

            $current_order_detail = Db::getInstance()->getValue($query);

            if ($current_order_detail && $current_order_detail != $id_order_detail) {
                $this->setWsError('Impossible de modifier l\'ID de détail de commande pour un statut de ligne de commande existant. Actuel : ' . $current_order_detail . ', Demandé : ' . $id_order_detail, 400);
                return false;
            }
        }

        $this->id_order_detail = (int)$id_order_detail;
        return true;
    }
}
