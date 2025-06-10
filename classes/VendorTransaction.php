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
    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Order ID */
    public $id_order;

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
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'order_detail_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'commission_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
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
        // Validate required fields
        if (!$this->id_vendor || !$this->transaction_type || !$this->status) {
            return false;
        }

        // Set default date if not provided
        if ($auto_date && !$this->date_add) {
            $this->date_add = date('Y-m-d H:i:s');
        }

        // Validate transaction type
        $validTypes = ['commission', 'refund', 'adjustment'];
        if (!in_array($this->transaction_type, $validTypes)) {
            return false;
        }

        // Validate status
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
        // Log the update for audit purposes
        PrestaShopLogger::addLog(
            'VendorTransaction updated: ID ' . $this->id . ', Status: ' . $this->status,
            1,
            null,
            'VendorTransaction',
            $this->id
        );

        return parent::update($null_values);
    }



    

    /**
     * Get transaction type display name
     *
     * @return string Display name
     */
    public function getTransactionTypeDisplayName()
    {
        $names = [
            'commission' => 'Commission',
            'refund' => 'Refund',
            'adjustment' => 'Adjustment'
        ];

        return isset($names[$this->transaction_type]) ? $names[$this->transaction_type] : $this->transaction_type;
    }

    /**
     * Get status display name
     *
     * @return string Display name
     */
    public function getStatusDisplayName()
    {
        $names = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled'
        ];

        return isset($names[$this->status]) ? $names[$this->status] : $this->status;
    }

    /**
     * Check if transaction is pending
     *
     * @return bool Is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is paid
     *
     * @return bool Is paid
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if transaction is cancelled
     *
     * @return bool Is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get formatted amount for display
     *
     * @param Context|null $context Context for currency formatting
     * @return string Formatted amount
     */
    public function getFormattedAmount($context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        return Tools::displayPrice($this->vendor_amount, $context->currency);
    }

    /**
     * Get related order if exists
     *
     * @return Order|false Order object or false
     */
    public function getOrder()
    {
        if (!$this->id_order) {
            return false;
        }

        return new Order($this->id_order);
    }

    /**
     * Get related vendor
     *
     * @return Vendor|false Vendor object or false
     */
    public function getVendor()
    {
        if (!$this->id_vendor) {
            return false;
        }

        return new Vendor($this->id_vendor);
    }

}