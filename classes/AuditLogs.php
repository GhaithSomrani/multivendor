<?php

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



    public static function setLogs($object, $action, $beforeData)
    {

        if ($action === 'delete') {
            $after = [];
            $changed = [
                'before' => $beforeData,
                'after' => $after
            ];
        } else {
            $after = $object->getFields();
            $changed = self::getChanges($beforeData, $after);
        }


        if (empty($changed['after'])  && empty($changed['before'])  && $action === 'update') {
            return;
        }

        $entity_type = get_class($object);
        $entity_id = $object->id;
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $context = EntityLogHelper::getContext($backtrace);
        $changed_data = json_encode($changed);


        $mv_audity_log = new AudityLog();
        $mv_audity_log->entity_type = $entity_type;
        $mv_audity_log->entity_id = $entity_id;
        $mv_audity_log->context = $context;
        $mv_audity_log->changed_data = $changed_data;
        $mv_audity_log->changed_by = EntityLogHelper::getChangedBy();
        $mv_audity_log->action = $action;
        $mv_audity_log->save();
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
}
