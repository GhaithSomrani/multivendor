<?php

use Dom\Entity;

class AudityLog extends ObjectModel
{
    /**
     * Entity type
     *
     * @var string
     */
    public $entity_type;

    /**
     * Entity ID
     *
     * @var int
     */
    public $entity_id;

    /**
     * Context of the action
     *
     * @var string
     */
    public $context;

    /**
     * Changed data
     *
     * @var string
     */
    public $changed_data;

    /**
     * Changed by
     *
     * @var string
     */
    public $changed_by;

    /**
     * Action type
     *
     * @var string
     */
    public $action;

    /**
     * Date of the action
     *
     * @var datetime
     */
    public $date_add;


    public static $definition = [
        'table' => 'mv_audit_logs',
        'primary' => 'id',
        'fields' => [
            'entity_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 100],
            'entity_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'context' => ['type' => self::TYPE_STRING],
            'changed_data' => ['type' => self::TYPE_STRING],
            'changed_by' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'action' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 20],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],

        ],
    ];



    /**
     * Save the logs of the given object for the given action
     *
     * @param object $object the object to log
     * @param string $action the action to log (add, update, delete)
     * @param array $beforeData the data before the action (for update and delete)
     *
     * @return void
     */
    public static function setLogs($object, $action, $beforeData)
    {
        $entityType = get_class($object);
        $entityId = $object->id;

        [$after, $changed] = self::prepareChangedData($object, $action, $beforeData);
        if (self::shouldSkipUpdate($changed, $action)) {
            return;
        }

        $changedBy = EntityLogHelper::getChangedBy();
        self::saveAuditLog($entityType, $entityId, $action, $changed, $changedBy);

        if (EntityLogHelper::isChildIsLoggable($entityType)) {
            self::handleChildRelationLogs($entityType, $entityId, $action, $beforeData, $after, $changedBy);
        }

        if (EntityLogHelper::isStatusLoggable($entityType)) {
            self::handleStatusChange($entityType, $entityId, $beforeData, $after, $changedBy);
        }
    }

    /**
     * Prepare "before" and "after" change data
     */
    private static function prepareChangedData($object, $action, $beforeData): array
    {
        if ($action === 'delete') {
            return [[], ['before' => $beforeData, 'after' => []]];
        }

        $after = $object->getFields();
        return [$after, self::getChanges($beforeData, $after)];
    }

    /**
     * Skip update logs if nothing actually changed
     */
    private static function shouldSkipUpdate(array $changed, string $action): bool
    {
        return $action === 'update'
            && empty($changed['after'])
            && empty($changed['before']);
    }

    /**
     * Create and save main audit log
     */
    private static function saveAuditLog(string $entityType, int $entityId, string $action, array $changed, string $changedBy): void
    {
        $context = EntityLogHelper::getContext(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10));
        $log = new AudityLog();
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->context = $context;
        $log->changed_data = json_encode($changed);
        $log->changed_by = $changedBy;
        $log->action = $action;
        $log->save();
    }

    /**
     * Handle child relation logging if entity is loggable
     */
    private static function handleChildRelationLogs(string $entityType, int $entityId, string $action, array $beforeData, array $after, string $changedBy): void
    {
        $relationField = EntityLogHelper::getRelationField($entityType);
        $parentEntity = EntityLogHelper::getParentName($entityType);

        $beforeVal = $beforeData[$relationField] ?? null;
        $afterVal  = $after[$relationField] ?? null;

        $changedRelation = $beforeVal !== $afterVal;

        if (!empty($beforeVal) && ($changedRelation || $action === 'delete')) {
            self::addChlildRelationLog($parentEntity, $beforeVal, $entityType, $entityId, 'delete', $changedBy);
        }

        if (!empty($afterVal) && $changedRelation && $action !== 'delete') {
            self::addChlildRelationLog($parentEntity, $afterVal, $entityType, $entityId, 'add', $changedBy);
        }

        if ($action === 'update') {
            self::addChlildRelationLog($parentEntity, $beforeVal, $entityType, $entityId, 'update', $changedBy);
        }
    }

    /**
     * Handle entity status change logging
     */
    private static function handleStatusChange(string $entityType, int $entityId, array $beforeData, array $after, string $changedBy): void
    {

        $statusField = EntityLogHelper::getStatusField($entityType);

        if (!$statusField) {
            return;
        }

        $oldStatus =  isset($beforeData[$statusField]) && $statusField ? $beforeData[$statusField] : "None";
        $newStatus =  isset($after[$statusField]) && $statusField ? $after[$statusField] : "Deleted";



        [$oldStatus, $newStatus] = self::resolveStatusNames($entityType, $oldStatus, $newStatus);
        if ($oldStatus !== $newStatus) {
            $statusLog = new StatusChangeLog();
            $statusLog->entity_type = $entityType;
            $statusLog->entity_id = $entityId;
            $statusLog->old_status = (string)$oldStatus;
            $statusLog->new_status = (string)$newStatus;
            $statusLog->changed_by = $changedBy;
            $statusLog->save();
        }
    }

    /**
     * Convert status IDs to readable names if applicable
     */
    private static function resolveStatusNames(string $entityType, $oldStatus, $newStatus): array
    {
        $statusClass = EntityLogHelper::getStatusObjectField($entityType);
        if (!$statusClass) {
            return [$oldStatus, $newStatus];
        }

        $old = new $statusClass($oldStatus);
        $new = new $statusClass($newStatus);

        return [$old->name, $new->name];
    }

    /**
     * Add a child relation log
     *
     * @param string $parent_entity The parent entity type
     * @param int $parent_id The parent entity id
     * @param string $child_entity The child entity type
     * @param int $child_id The child entity id
     * @param string $action The action type (add or delete)
     * @param string $changed_by The user who made the change
     */
    public static function addChlildRelationLog($parent_entity, $parent_id, $child_entity, $child_id, $action, $changed_by)
    {
        $child_relation_log = new ChildRelationLog();
        $child_relation_log->parent_entity = $parent_entity;
        $child_relation_log->parent_id = $parent_id;
        $child_relation_log->child_entity = $child_entity;
        $child_relation_log->child_id = $child_id;
        $child_relation_log->action = $action;
        $child_relation_log->changed_by = $changed_by;
        $child_relation_log->save();
    }


    public static function getChanges($before, $after)
    {
        unset($before['date_upd'], $after['date_upd']);

        $changed = array_diff_assoc($after, $before);

        return [
            'before' => array_intersect_key($before, $changed),
            'after' => $changed
        ];
    }


    public static function getLogsByNameAndId($name, $id)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('ps_audit_log');
        $query->where('entity_type = "' . pSQL($name) . '" AND entity_id = ' . (int)$id);
    }
}
