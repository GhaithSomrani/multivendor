<?php

/**
 * VendorCommission model class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorCommission extends ObjectModel
{
    /** @var int Commission ID */
    public $id;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var float Commission rate */
    public $commission_rate;

    /** @var string Creation date */
    public $date_add;

    /** @var string Last update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_vendor_commission',
        'primary' => 'id_vendor_commission',
        'fields' => [
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get commission rate for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float|null Commission rate or null if not found
     */
    public static function getCommissionRate($id_vendor)
    {
        $query = new DbQuery();
        dump($id_vendor);
        $query->select('commission_rate');
        $query->from('mv_vendor_commission');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->orderBy('date_add DESC');

        $result = Db::getInstance()->getValue($query);

        return $result;
    }

    /**
     * Log commission rate change
     *
     * @param int $id_vendor Vendor ID
     * @param float $old_rate Old commission rate
     * @param float $new_rate New commission rate
     * @param int $changed_by ID of the employee who made the change
     * @param string $comment Optional comment
     * @return bool Success
     */
    public static function logCommissionRateChange($id_vendor, $old_rate, $new_rate, $changed_by, $comment = null)
    {
        return Db::getInstance()->insert('mv_vendor_commission_log', [
            'id_vendor' => (int)$id_vendor,
            'old_commission_rate' => (float)$old_rate,
            'new_commission_rate' => (float)$new_rate,
            'changed_by' => (int)$changed_by,
            'comment' => pSQL($comment),
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }
}
