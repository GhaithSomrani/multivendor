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

    /** @var int Order detail ID */
    public $order_detail_id;

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
        'table' => 'vendor_transaction',
        'primary' => 'id_vendor_transaction',
        'fields' => [
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'transaction_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'order_detail_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'id_vendor_payment' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
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
        $query->select('SUM(vendor_amount)');
        $query->from('vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('status = "pending"');

        return (float)Db::getInstance()->getValue($query);
    }

    /**
     * Pay pending commissions for a vendor
     * This creates a payment record and properly tracks which order lines are being paid
     *
     * @param int $id_vendor Vendor ID
     * @param string $payment_method Payment method
     * @param string $reference Payment reference
     * @param int $id_employee Employee ID who made the payment
     * @return bool|array Success or error details
     */
    public static function payVendorCommissions($id_vendor, $payment_method, $reference, $id_employee)
    {
        // Get the default status
        $defaultStatus = Db::getInstance()->getRow(
            '
        SELECT * FROM `' . _DB_PREFIX_ . 'order_line_status_type` 
        WHERE active = 1 
        ORDER BY position ASC '
        );

        $defaultAction = $defaultStatus ? $defaultStatus['commission_action'] : 'none';

        // Get all unpaid order lines with commission_action = 'add'
        $unpaidOrderLines = Db::getInstance()->executeS('
        SELECT DISTINCT vod.*, od.id_order_detail, od.product_name, vod.id_order as id_order,
               COALESCE(ols.status, "' . pSQL($defaultStatus['name']) . '") as line_status,
               COALESCE(olst.commission_action, "' . pSQL($defaultAction) . '") as commission_action
        FROM ' . _DB_PREFIX_ . 'vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vod.id_order_detail
        LEFT JOIN ' . _DB_PREFIX_ . 'order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        LEFT JOIN ' . _DB_PREFIX_ . 'order_line_status_type olst ON olst.name = ols.status
        LEFT JOIN ' . _DB_PREFIX_ . 'vendor_transaction vt ON vt.id_order = vod.id_order 
            AND vt.id_vendor = vod.id_vendor 
            AND vt.transaction_type = "commission"
            AND vt.order_detail_id = vod.id_order_detail
        WHERE vod.id_vendor = ' . (int)$id_vendor . '
        AND (
            (olst.commission_action = "add") 
            OR 
            (ols.status IS NULL AND "' . pSQL($defaultAction) . '" = "add")
        )
        AND vt.id_vendor_transaction IS NULL
    ');

        if (empty($unpaidOrderLines)) {
            return ['success' => false, 'message' => 'No unpaid commissions found'];
        }

        // Calculate total amount to pay
        $totalCommissionAmount = 0;
        $totalVendorAmount = 0;
        foreach ($unpaidOrderLines as $line) {
            $totalCommissionAmount += $line['vendor_amount'];
            $totalVendorAmount += $line['vendor_amount'];
        }

        // Start transaction
        Db::getInstance()->execute('START TRANSACTION');

        try {
            // Create payment record
            $payment = new VendorPayment();
            $payment->id_vendor = (int)$id_vendor;
            $payment->amount = $totalCommissionAmount;
            $payment->payment_method = $payment_method;
            $payment->reference = $reference;
            $payment->status = 'completed';
            $payment->date_add = date('Y-m-d H:i:s');

            if (!$payment->save()) {
                throw new Exception('Failed to create payment record');
            }

            // Create a transaction record for each order line being paid
            foreach ($unpaidOrderLines as $line) {
                $transaction = new VendorTransaction();
                $transaction->id_vendor = (int)$id_vendor;
                $transaction->id_order = (int)$line['id_order'];
                $transaction->order_detail_id = (int)$line['id_order_detail'];
                $transaction->commission_amount = $line['vendor_amount'];
                $transaction->vendor_amount = $line['vendor_amount'];
                $transaction->transaction_type = 'commission';
                $transaction->status = 'paid';
                $transaction->id_vendor_payment = $payment->id;
                $transaction->date_add = date('Y-m-d H:i:s');

                if (!$transaction->save()) {
                    throw new Exception('Failed to create transaction record for order detail: ' . $line['id_order_detail']);
                }
            }

            // Commit transaction
            Db::getInstance()->execute('COMMIT');

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'amount_paid' => $totalCommissionAmount,
                'lines_paid' => count($unpaidOrderLines)
            ];
        } catch (Exception $e) {
            // Rollback transaction
            Db::getInstance()->execute('ROLLBACK');

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
