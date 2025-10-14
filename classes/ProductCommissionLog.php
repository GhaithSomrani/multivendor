<?php

class ProductCommissionLog extends ObjectModel
{
    public $id_product_commission;
    public $old_commission_rate;
    public $new_commission_rate;
    public $changed_by;
    public $comment;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mv_product_commission_log',
        'primary' => 'id_product_commission_log',
        'fields' => [
            'id_product_commission' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'old_commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'new_commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'changed_by' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 256],
            'comment' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public static function getByidProductCommission($id_product_commission)
    {
        $id_product_commission_log = Db::getInstance()->getValue(
            '
            SELECT id_product_commission_log
            FROM `' . _DB_PREFIX_ . 'mv_product_commission_log`
            WHERE `id_product_commission` = ' . (int)$id_product_commission
        );
        return new ProductCommissionLog($id_product_commission_log);
    }
}
