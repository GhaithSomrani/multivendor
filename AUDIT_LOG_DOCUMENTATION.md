# Audit Log System Documentation

## Overview

The Audit Log System provides comprehensive tracking of all CRUD (Create, Read, Update, Delete) operations across your PrestaShop multivendor module. It automatically captures before and after data for each operation, enabling you to track changes, investigate issues, and maintain a complete audit trail.

## Components

### 1. AuditLog Model (`classes/AuditLog.php`)

The main model class that stores audit log entries in the `mv_audit_log` table.

**Table Structure:**
- `id_audit_log` - Primary key
- `model_name` - Name of the model class
- `record_id` - ID of the affected record
- `operation` - Type of operation (create, update, delete)
- `data_before` - JSON snapshot of data before the operation
- `data_after` - JSON snapshot of data after the operation
- `changed_by` - User who made the change
- `context` - Additional context (controller name, etc.)
- `ip_address` - Client IP address
- `user_agent` - Client user agent string
- `date_add` - Timestamp of the operation

### 2. AuditLogTrait (`classes/AuditLogTrait.php`)

A trait that can be added to any ObjectModel class to automatically enable audit logging.

**Features:**
- Automatic tracking of add(), update(), and delete() operations
- Smart change detection (only logs when actual changes occur)
- Captures complete before/after state
- Tracks who made the change (employee, customer, or system)
- Records IP address and user agent
- Non-intrusive error handling (logs errors but doesn't break operations)

### 3. Database Table

Added to `sql/install.php`:
```sql
CREATE TABLE IF NOT EXISTS `mv_audit_log` (
    `id_audit_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `model_name` varchar(128) NOT NULL,
    `record_id` int(10) unsigned NOT NULL,
    `operation` varchar(32) NOT NULL,
    `data_before` LONGTEXT,
    `data_after` LONGTEXT,
    `changed_by` varchar(256) DEFAULT NULL,
    `context` TEXT,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(512) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_audit_log`),
    KEY `model_record` (`model_name`, `record_id`),
    KEY `operation` (`operation`),
    KEY `date_add` (`date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

## Usage

### Adding Audit Logging to a Model

#### Simple Case (No Custom Lifecycle Methods)

For models without custom add/update/delete methods:

```php
<?php

require_once _PS_MODULE_DIR_ . 'multivendor/classes/AuditLogTrait.php';
require_once _PS_MODULE_DIR_ . 'multivendor/classes/AuditLog.php';

class MyModel extends ObjectModel
{
    use AuditLogTrait;

    // Your model definition...
}
```

#### Complex Case (With Custom Lifecycle Methods)

For models that already have custom add(), update(), or delete() methods:

```php
<?php

require_once _PS_MODULE_DIR_ . 'multivendor/classes/AuditLogTrait.php';
require_once _PS_MODULE_DIR_ . 'multivendor/classes/AuditLog.php';

class MyModel extends ObjectModel
{
    use AuditLogTrait {
        add as protected traitAdd;
        update as protected traitUpdate;
        delete as protected traitDelete;
    }

    public function add($auto_date = true, $null_values = false)
    {
        // Your custom logic before add
        if (!$this->validateSomething()) {
            return false;
        }

        // Call trait's add method (includes audit logging)
        return $this->traitAdd($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        // Your custom logic before update
        $this->doSomethingBeforeUpdate();

        // Call trait's update method (includes audit logging)
        $result = $this->traitUpdate($null_values);

        // Your custom logic after update
        if ($result) {
            $this->doSomethingAfterUpdate();
        }

        return $result;
    }
}
```

## Models with Audit Logging Enabled

The following models in the multivendor module now have audit logging enabled:

1. **Vendor** - Tracks all vendor profile changes
2. **VendorCommission** - Logs commission rate changes
3. **VendorTransaction** - Tracks transaction modifications
4. **VendorPayment** - Logs payment processing
5. **VendorOrderDetail** - Tracks order detail changes
6. **Manifest** - Logs manifest status changes
7. **ManifestDetails** - Tracks manifest item modifications
8. **OrderLineStatus** - Logs order status changes

## Querying Audit Logs

### Get All Logs for a Specific Record

```php
// Get all audit logs for Vendor with ID 5
$logs = AuditLog::getLogsByRecord('Vendor', 5);

foreach ($logs as $log) {
    echo "Operation: " . $log['operation'] . "\n";
    echo "Date: " . $log['date_add'] . "\n";
    echo "Changed by: " . $log['changed_by'] . "\n";
    echo "Data before: " . $log['data_before'] . "\n";
    echo "Data after: " . $log['data_after'] . "\n";
    echo "\n";
}
```

### Get Update Logs Only

```php
// Get only update operations for a record
$updateLogs = AuditLog::getLogsByRecord('VendorCommission', 10, 'update');
```

### Get All Logs for a Model

```php
// Get last 100 audit logs for all Vendors
$logs = AuditLog::getLogsByModel('Vendor', 100, 0);
```

### Analyze Changes

```php
// Load a specific audit log
$auditLog = new AuditLog($auditLogId);

// Get structured changes
$changes = $auditLog->getChanges();

foreach ($changes as $field => $values) {
    echo "Field: $field\n";
    echo "Old value: " . $values['old'] . "\n";
    echo "New value: " . $values['new'] . "\n";
}

// Or get formatted output
echo $auditLog->getFormattedLog();
```

## Customizing Audit Behavior

### Custom User Tracking

Override the `getChangedBy()` method in your model:

```php
class MyModel extends ObjectModel
{
    use AuditLogTrait;

    protected function getChangedBy()
    {
        // Custom logic to identify the user
        if ($this->my_custom_user_field) {
            return "Custom User: " . $this->my_custom_user_field;
        }

        // Fall back to trait's default behavior
        return parent::getChangedBy();
    }
}
```

### Custom Context

Override the `getAuditContext()` method:

```php
class MyModel extends ObjectModel
{
    use AuditLogTrait;

    protected function getAuditContext()
    {
        return "Import batch ID: " . $this->import_batch_id;
    }
}
```

## Benefits

1. **Complete Audit Trail**: Track all changes to critical data
2. **Compliance**: Meet regulatory requirements for data tracking
3. **Debugging**: Investigate when and how data changed
4. **Security**: Identify unauthorized changes
5. **Data Recovery**: Restore previous values if needed
6. **User Accountability**: Know who made each change

## Performance Considerations

1. The audit log uses JSON to store data, which is efficient for most use cases
2. Indexes are created on key fields (model_name, record_id, operation, date_add)
3. Audit logging happens after the main operation, so it won't block critical paths
4. Errors in audit logging are logged but don't fail the main operation
5. Consider archiving old audit logs periodically to maintain performance

## Example Workflow

```php
// Create a new vendor (automatically logged)
$vendor = new Vendor();
$vendor->shop_name = "Test Shop";
$vendor->id_customer = 5;
$vendor->id_supplier = 10;
$vendor->status = 1;
$vendor->add(); // Audit log created with operation='create'

// Update the vendor (automatically logged with before/after data)
$vendor->status = 2;
$vendor->update(); // Audit log created with operation='update'

// View the audit trail
$logs = AuditLog::getLogsByRecord('Vendor', $vendor->id);
foreach ($logs as $log) {
    $auditLog = new AuditLog($log['id_audit_log']);
    echo $auditLog->getFormattedLog();
}

// Delete the vendor (automatically logged)
$vendor->delete(); // Audit log created with operation='delete'
```

## Migration and Installation

When upgrading the module, the audit log table will be automatically created during the installation process via `sql/install.php`. For existing installations, you may need to run the module upgrade or manually execute the CREATE TABLE statement.

## Troubleshooting

**Issue**: Audit logs are not being created
- Check that the `mv_audit_log` table exists
- Verify the model has the `use AuditLogTrait;` statement
- Check PrestaShop logs for any audit logging errors

**Issue**: Performance degradation
- Check the size of the audit log table
- Consider archiving old logs to a separate table
- Ensure indexes are properly created

**Issue**: Incomplete data in audit logs
- Verify your model's `$definition` array is complete
- Check that all fields you want to track are in the definition

## Future Enhancements

Potential improvements to consider:

1. Admin interface to view and search audit logs
2. Automatic archiving of old audit logs
3. Configurable retention policies
4. Export audit logs to external systems
5. Real-time alerts for specific changes
6. Rollback functionality to restore previous values
