<?php

/**
 * VendorPayment model class
 */
class VendorPayment extends ObjectModel
{
    /** @var int Payment ID */
    public $id;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var float Amount */
    public $amount;

    /** @var string Payment method */
    public $payment_method;

    /** @var string Reference */
    public $reference;

    /** @var string Status */
    public $status;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'vendor_payment',
        'primary' => 'id_vendor_payment',
        'fields' => [
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'payment_method' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get payments for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Payments
     */
    public static function getVendorPayments($id_vendor, $limit = null, $offset = null)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('vendor_payment');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->orderBy('date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get total paid amount for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float Total paid amount
     */
    public static function getVendorTotalPaid($id_vendor)
    {
        $query = new DbQuery();
        $query->select('SUM(amount)');
        $query->from('vendor_payment');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('status = "completed"');

        return (float)Db::getInstance()->getValue($query);
    }
}
