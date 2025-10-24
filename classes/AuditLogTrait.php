<?php

/**
 * AuditLogTrait - Trait for automatic CRUD operation tracking
 *
 * Add this trait to any ObjectModel class to automatically log:
 * - Create operations (add)
 * - Update operations (update) with before/after data
 * - Delete operations (delete)
 *
 * Usage:
 * class MyModel extends ObjectModel {
 *     use AuditLogTrait;
 *     // ... rest of your model
 * }
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

trait AuditLogTrait
{
    /**
     * Override add method to log create operations
     *
     * @param bool $auto_date Automatically set creation date
     * @param bool $null_values Allow null values
     * @return bool Success status
     */
    public function add($auto_date = true, $null_values = false)
    {
        // Call parent add method
        $result = parent::add($auto_date, $null_values);

        if ($result) {
            // Log the create operation
            $this->logAuditCreate();
        }

        return $result;
    }

    /**
     * Override update method to log update operations with before/after data
     *
     * @param bool $null_values Allow null values
     * @return bool Success status
     */
    public function update($null_values = false)
    {
        // Get the current state before update
        $beforeData = $this->getDataBeforeUpdate();

        // Call parent update method
        $result = parent::update($null_values);

        if ($result) {
            // Get the new state after update
            $afterData = $this->getCurrentData();

            // Only log if there are actual changes
            if ($this->hasChanges($beforeData, $afterData)) {
                $this->logAuditUpdate($beforeData, $afterData);
            }
        }

        return $result;
    }

    /**
     * Override delete method to log delete operations
     *
     * @return bool Success status
     */
    public function delete()
    {
        // Get the current state before deletion
        $beforeData = $this->getCurrentData();

        // Call parent delete method
        $result = parent::delete();

        if ($result) {
            // Log the delete operation
            $this->logAuditDelete($beforeData);
        }

        return $result;
    }

    /**
     * Get data before update by loading fresh copy from database
     *
     * @return array Field data
     */
    protected function getDataBeforeUpdate()
    {
        if (!$this->id) {
            return [];
        }

        try {
            $modelClass = get_class($this);
            $oldInstance = new $modelClass($this->id);
            return $this->extractModelData($oldInstance);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get current data from the model instance
     *
     * @return array Field data
     */
    protected function getCurrentData()
    {
        return $this->extractModelData($this);
    }

    /**
     * Extract relevant data from a model instance
     *
     * @param ObjectModel $instance Model instance
     * @return array Field data
     */
    protected function extractModelData($instance)
    {
        $data = [];
        $definition = $instance::$definition;

        if (isset($definition['fields'])) {
            foreach ($definition['fields'] as $field => $fieldDef) {
                if (property_exists($instance, $field)) {
                    $data[$field] = $instance->$field;
                }
            }
        }

        // Include the primary key
        if (isset($definition['primary']) && property_exists($instance, 'id')) {
            $data['id'] = $instance->id;
        }

        return $data;
    }

    /**
     * Check if there are any changes between before and after data
     *
     * @param array $before Data before change
     * @param array $after Data after change
     * @return bool True if there are changes
     */
    protected function hasChanges($before, $after)
    {
        // Exclude date_upd from comparison as it always changes
        $excludeFields = ['date_upd'];

        foreach ($after as $field => $value) {
            if (in_array($field, $excludeFields)) {
                continue;
            }

            $beforeValue = isset($before[$field]) ? $before[$field] : null;

            // Compare values, handle type coercion
            if ($this->valuesAreDifferent($beforeValue, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare two values accounting for type differences
     *
     * @param mixed $value1 First value
     * @param mixed $value2 Second value
     * @return bool True if values are different
     */
    protected function valuesAreDifferent($value1, $value2)
    {
        // Handle null comparisons
        if ($value1 === null || $value2 === null) {
            return $value1 !== $value2;
        }

        // Handle numeric comparisons (account for string/int variations)
        if (is_numeric($value1) && is_numeric($value2)) {
            return (float)$value1 !== (float)$value2;
        }

        // Standard comparison
        return $value1 !== $value2;
    }

    /**
     * Log a create operation
     */
    protected function logAuditCreate()
    {
        $auditLog = new AuditLog();
        $auditLog->model_name = get_class($this);
        $auditLog->record_id = (int)$this->id;
        $auditLog->operation = 'create';
        $auditLog->data_before = '{}';
        $auditLog->data_after = json_encode($this->getCurrentData());
        $auditLog->changed_by = $this->getChangedBy();
        $auditLog->context = $this->getAuditContext();
        $auditLog->ip_address = $this->getClientIp();
        $auditLog->user_agent = $this->getUserAgent();

        try {
            $auditLog->add();
        } catch (Exception $e) {
            // Log error but don't fail the operation
            PrestaShopLogger::addLog(
                'AuditLog create failed: ' . $e->getMessage(),
                3,
                null,
                get_class($this),
                $this->id
            );
        }
    }

    /**
     * Log an update operation
     *
     * @param array $beforeData Data before update
     * @param array $afterData Data after update
     */
    protected function logAuditUpdate($beforeData, $afterData)
    {
        $auditLog = new AuditLog();
        $auditLog->model_name = get_class($this);
        $auditLog->record_id = (int)$this->id;
        $auditLog->operation = 'update';
        $auditLog->data_before = json_encode($beforeData);
        $auditLog->data_after = json_encode($afterData);
        $auditLog->changed_by = $this->getChangedBy();
        $auditLog->context = $this->getAuditContext();
        $auditLog->ip_address = $this->getClientIp();
        $auditLog->user_agent = $this->getUserAgent();

        try {
            $auditLog->add();
        } catch (Exception $e) {
            // Log error but don't fail the operation
            PrestaShopLogger::addLog(
                'AuditLog update failed: ' . $e->getMessage(),
                3,
                null,
                get_class($this),
                $this->id
            );
        }
    }

    /**
     * Log a delete operation
     *
     * @param array $beforeData Data before deletion
     */
    protected function logAuditDelete($beforeData)
    {
        $auditLog = new AuditLog();
        $auditLog->model_name = get_class($this);
        $auditLog->record_id = (int)$this->id;
        $auditLog->operation = 'delete';
        $auditLog->data_before = json_encode($beforeData);
        $auditLog->data_after = '{}';
        $auditLog->changed_by = $this->getChangedBy();
        $auditLog->context = $this->getAuditContext();
        $auditLog->ip_address = $this->getClientIp();
        $auditLog->user_agent = $this->getUserAgent();

        try {
            $auditLog->add();
        } catch (Exception $e) {
            // Log error but don't fail the operation
            PrestaShopLogger::addLog(
                'AuditLog delete failed: ' . $e->getMessage(),
                3,
                null,
                get_class($this),
                $this->id
            );
        }
    }

    /**
     * Get the user who made the change
     * Override this method in your model if you have a custom way to track users
     *
     * @return string User identifier
     */
    protected function getChangedBy()
    {
        // Check for changed_by property (used in some models like ProductCommission)
        if (property_exists($this, 'changed_by') && !empty($this->changed_by)) {
            return $this->changed_by;
        }

        // Try to get current employee
        if (class_exists('Context') && Context::getContext()->employee) {
            $employee = Context::getContext()->employee;
            return sprintf(
                '%s %s (ID: %d)',
                $employee->firstname,
                $employee->lastname,
                $employee->id
            );
        }

        // Try to get current customer
        if (class_exists('Context') && Context::getContext()->customer && Context::getContext()->customer->id) {
            $customer = Context::getContext()->customer;
            return sprintf(
                'Customer: %s %s (ID: %d)',
                $customer->firstname,
                $customer->lastname,
                $customer->id
            );
        }

        return 'System';
    }

    /**
     * Get audit context (additional information about the operation)
     * Override this method in your model to provide custom context
     *
     * @return string Context information
     */
    protected function getAuditContext()
    {
        // Check for context from controller or other sources
        if (isset($_REQUEST['audit_context'])) {
            return $_REQUEST['audit_context'];
        }

        // Get controller name if available
        if (class_exists('Context') && Context::getContext()->controller) {
            return get_class(Context::getContext()->controller);
        }

        return '';
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    protected function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    /**
     * Get user agent
     *
     * @return string User agent string
     */
    protected function getUserAgent()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            return substr($_SERVER['HTTP_USER_AGENT'], 0, 512);
        }

        return '';
    }
}
