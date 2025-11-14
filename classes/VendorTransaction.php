<?php

/**
 * Final VendorTransaction model class
 * Acts as a facade/wrapper that delegates all operations to TransactionHelper
 * Maintains backward compatibility while encouraging use of TransactionHelper
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorTransaction extends ObjectModel
{
    /** @var int Order detail ID */
    public $order_detail_id;

    /** @var float Commission amount */
    public $commission_amount;

    /** @var float Vendor amount */
    public $vendor_amount;

    /** @var string Transaction type */
    public $transaction_type;

    /** @var string Status */
    public $status;

    /** @var int Vendor payment ID */
    public $id_vendor_payment;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_vendor_transaction',
        'primary' => 'id_vendor_transaction',
        'fields' => [
            'order_detail_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'vendor_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'transaction_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'id_vendor_payment' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];


    /**
     * Add a new transaction record
     * Override to ensure proper data validation
     *
     * @param bool $auto_date Auto-add creation date
     * @param bool $null_values Allow null values
     * @return bool Success
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!$this->order_detail_id || !$this->transaction_type || !$this->status) {
            return false;
        }

        if ($auto_date && !$this->date_add) {
            $this->date_add = date('Y-m-d H:i:s');
        }

        $validTypes = ['commission', 'refund', 'adjustment'];
        if (!in_array($this->transaction_type, $validTypes)) {
            return false;
        }

        $validStatuses = ['pending', 'paid', 'cancelled'];
        if (!in_array($this->status, $validStatuses)) {
            return false;
        }

        return parent::add($auto_date, $null_values);
    }

    /**
     * Update transaction record
     * Override to log changes
     *
     * @param bool $null_values Allow null values
     * @return bool Success
     */
    public function update($null_values = false)
    {
        PrestaShopLogger::addLog(
            'VendorTransaction updated: ID ' . $this->id . ', Status: ' . $this->status,
            1,
            null,
            'VendorTransaction',
            $this->id
        );

        return parent::update($null_values);
    }

    public static function getByOrderDetailAndType($order_detail_id, $transaction_type)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor_transaction');
        $query->where('order_detail_id = ' . (int)$order_detail_id);
        $query->where('transaction_type = "' . pSQL($transaction_type) . '"');
        $query->orderBy('date_add DESC');

        return Db::getInstance()->getRow($query);
    }
    public static function getTransactionsByVendorPayment($id_vendor_payment)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor_payment = ' . (int)$id_vendor_payment);
        $query->orderBy('date_add DESC');
        return Db::getInstance()->executeS($query);
    }
}
