<?php

/**
 * AuditLog model class for tracking CRUD operations
 * Stores before and after data for create, update, and delete operations
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AuditLog extends ObjectModel
{
    /** @var string Model name */
    public $model_name;

    /** @var int Record ID */
    public $record_id;

    /** @var string Operation type (create, update, delete) */
    public $operation;

    /** @var string Data before operation (JSON) */
    public $data_before;

    /** @var string Data after operation (JSON) */
    public $data_after;

    /** @var string Changed by (user identifier) */
    public $changed_by;

    /** @var string Additional context/comment */
    public $context;

    /** @var string IP address */
    public $ip_address;

    /** @var string User agent */
    public $user_agent;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_audit_log',
        'primary' => 'id_audit_log',
        'fields' => [
            'model_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'record_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'operation' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'data_before' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'data_after' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'changed_by' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 256],
            'context' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'ip_address' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 45],
            'user_agent' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 512],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ]
    ];

    /**
     * Get audit logs for a specific model and record
     *
     * @param string $modelName Model class name
     * @param int $recordId Record ID
     * @param string $operation Optional operation filter (create, update, delete)
     * @return array List of audit logs
     */
    public static function getLogsByRecord($modelName, $recordId, $operation = null)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_audit_log');
        $query->where('model_name = "' . pSQL($modelName) . '"');
        $query->where('record_id = ' . (int)$recordId);

        if ($operation) {
            $query->where('operation = "' . pSQL($operation) . '"');
        }

        $query->orderBy('date_add DESC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get all audit logs for a specific model
     *
     * @param string $modelName Model class name
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array List of audit logs
     */
    public static function getLogsByModel($modelName, $limit = 100, $offset = 0)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_audit_log');
        $query->where('model_name = "' . pSQL($modelName) . '"');
        $query->orderBy('date_add DESC');
        $query->limit((int)$limit, (int)$offset);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get field changes from audit log
     * Returns a structured array of field changes
     *
     * @return array|null Array of changed fields or null
     */
    public function getChanges()
    {
        if (empty($this->data_before) || empty($this->data_after)) {
            return null;
        }

        $before = json_decode($this->data_before, true);
        $after = json_decode($this->data_after, true);

        if (!is_array($before) || !is_array($after)) {
            return null;
        }

        $changes = [];
        foreach ($after as $field => $newValue) {
            $oldValue = isset($before[$field]) ? $before[$field] : null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    /**
     * Format the audit log for display
     *
     * @return string Formatted log entry
     */
    public function getFormattedLog()
    {
        $output = sprintf(
            "[%s] %s operation on %s (ID: %d)\n",
            $this->date_add,
            ucfirst($this->operation),
            $this->model_name,
            $this->record_id
        );

        if ($this->changed_by) {
            $output .= "Changed by: {$this->changed_by}\n";
        }

        if ($this->context) {
            $output .= "Context: {$this->context}\n";
        }

        $changes = $this->getChanges();
        if ($changes) {
            $output .= "Changes:\n";
            foreach ($changes as $field => $values) {
                $output .= sprintf(
                    "  - %s: %s → %s\n",
                    $field,
                    $this->formatValue($values['old']),
                    $this->formatValue($values['new'])
                );
            }
        }

        return $output;
    }

    /**
     * Format a value for display
     *
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    private function formatValue($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string)$value;
    }
}
