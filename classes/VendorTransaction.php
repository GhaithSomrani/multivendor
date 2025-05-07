<?php

/**
 * VendorTransaction model class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorTransaction extends ObjectModel
{
    /** @var int Transaction ID */
    public $id;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Order ID */
    public $id_order;

    /** @var float Commission amount */
    public $commission_amount;

    /** @var float Vendor amount */
    public $vendor_amount;

    /** @var string Transaction type */
    public $transaction_type;

    /** @var string Status */
    public $status;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'vendor_transaction',
        'primary' => 'id_vendor_transaction',
        'fields' => [
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'commission_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'vendor_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'transaction_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get transactions for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param string $status Optional status filter
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Transactions
     */
    public static function getVendorTransactions($id_vendor, $status = null, $limit = null, $offset = null)
    {
        $query = new DbQuery();
        $query->select('vt.*, o.reference');
        $query->from('vendor_transaction', 'vt');
        $query->leftJoin('orders', 'o', 'o.id_order = vt.id_order');
        $query->where('vt.id_vendor = ' . (int)$id_vendor);

        if ($status) {
            $query->where('vt.status = "' . pSQL($status) . '"');
        }

        $query->orderBy('vt.date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get pending commission amount for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float Pending commission amount
     */
    public static function getVendorPendingCommission($id_vendor)
    {
        $query = new DbQuery();
        $query->select('SUM(commission_amount)');
        $query->from('vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('status = "pending"');

        return (float)Db::getInstance()->getValue($query);
    }

    /**
     * Pay pending commissions for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param string $payment_method Payment method
     * @param string $reference Payment reference
     * @param int $id_employee Employee ID who made the payment
     * @return bool Success
     */
    public static function payVendorCommissions($id_vendor, $payment_method, $reference, $id_employee)
    {
        // Get pending amount
        $pending_amount = self::getVendorPendingCommission($id_vendor);

        if ($pending_amount <= 0) {
            return false;
        }

        // Create payment record
        $payment = new VendorPayment();
        $payment->id_vendor = (int)$id_vendor;
        $payment->amount = $pending_amount;
        $payment->payment_method = $payment_method;
        $payment->reference = $reference;
        $payment->status = 'completed';
        $payment->date_add = date('Y-m-d H:i:s');

        if (!$payment->save()) {
            return false;
        }

        // Update transaction status
        Db::getInstance()->update('vendor_transaction', [
            'status' => 'paid'
        ], 'id_vendor = ' . (int)$id_vendor . ' AND status = "pending"');

        return true;
    }
}
