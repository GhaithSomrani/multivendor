<?php

/**
 * TransactionHelper - Centralized transaction management class
 * Contains all common transaction functions used throughout the multi-vendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class TransactionHelper
{
    /**
     * Create or update transaction only when transaction_type or status changes
     * This is the core optimization - prevents duplicate transactions
     *
     * @param int $id_vendor Vendor ID
     * @param int $id_order Order ID
     * @param int $order_detail_id Order detail ID
     * @param float $commission_amount Commission amount
     * @param float $vendor_amount Vendor amount
     * @param string $transaction_type Transaction type
     * @param string $status Status
     * @param int|null $id_vendor_payment Vendor payment ID
     * @return bool Success
     */
    public static function createOrUpdateTransaction($id_vendor, $id_order, $order_detail_id, $commission_amount, $vendor_amount, $transaction_type, $status, $id_vendor_payment = null)
    {
        try {
            // Get existing transaction for this order detail and type
            $existing = self::getExistingTransaction($id_vendor, $order_detail_id, $transaction_type);
            
            if ($existing) {
                // Check if status changed
                if ($existing['status'] !== $status) {
                    // Update existing transaction
                    return Db::getInstance()->update(
                        'mv_vendor_transaction',
                        [
                            'status' => pSQL($status),
                            'id_vendor_payment' => $id_vendor_payment ? (int)$id_vendor_payment : null,
                            'date_add' => date('Y-m-d H:i:s') // Update timestamp on status change
                        ],
                        'id_vendor_transaction = ' . (int)$existing['id_vendor_transaction']
                    );
                }
                // No change needed, transaction already exists with same status
                return true;
            }

            // Create new transaction
            $transaction = new VendorTransaction();
            $transaction->id_vendor = (int)$id_vendor;
            $transaction->id_order = (int)$id_order;
            $transaction->order_detail_id = (int)$order_detail_id;
            $transaction->commission_amount = (float)$commission_amount;
            $transaction->vendor_amount = (float)$vendor_amount;
            $transaction->transaction_type = pSQL($transaction_type);
            $transaction->status = pSQL($status);
            $transaction->id_vendor_payment = $id_vendor_payment ? (int)$id_vendor_payment : null;
            $transaction->date_add = date('Y-m-d H:i:s');

            return $transaction->save();

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'TransactionHelper::createOrUpdateTransaction - Error: ' . $e->getMessage(),
                3,
                null,
                'TransactionHelper',
                $order_detail_id
            );
            return false;
        }
    }

    /**
     * Get existing transaction for order detail and transaction type
     *
     * @param int $id_vendor Vendor ID
     * @param int $order_detail_id Order detail ID
     * @param string $transaction_type Transaction type
     * @return array|false Existing transaction or false
     */
    public static function getExistingTransaction($id_vendor, $order_detail_id, $transaction_type)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('order_detail_id = ' . (int)$order_detail_id);
        $query->where('transaction_type = "' . pSQL($transaction_type) . '"');
        $query->orderBy('date_add DESC');

        return Db::getInstance()->getRow($query);
    }

    /**
     * Process commission based on order line status change
     * Only creates/updates transactions when necessary
     *
     * @param int $id_order_detail Order detail ID
     * @param int $id_vendor Vendor ID
     * @param string $commission_action Commission action (add, cancel, refund, none)
     * @return bool Success
     */
    public static function processCommissionTransaction($id_order_detail, $id_vendor, $commission_action)
    {
        try {
            // Get vendor order detail
            $vendorOrderDetail = VendorHelper::getVendorOrderDetailByOrderDetailAndVendor($id_order_detail, $id_vendor);
            
            if (!$vendorOrderDetail) {
                return false;
            }

            $id_order = (int)$vendorOrderDetail['id_order'];
            $commission_amount = (float)$vendorOrderDetail['commission_amount'];
            $vendor_amount = (float)$vendorOrderDetail['vendor_amount'];

            switch ($commission_action) {
                case 'add':
                    return self::createOrUpdateTransaction(
                        $id_vendor,
                        $id_order,
                        $id_order_detail,
                        $commission_amount,
                        $vendor_amount,
                        'commission',
                        'pending'
                    );

                case 'cancel':
                    return self::createOrUpdateTransaction(
                        $id_vendor,
                        $id_order,
                        $id_order_detail,
                        0,
                        0,
                        'commission',
                        'cancelled'
                    );

                case 'refund':
                    return self::createOrUpdateTransaction(
                        $id_vendor,
                        $id_order,
                        $id_order_detail,
                        -$commission_amount,
                        -$vendor_amount,
                        'refund',
                        'pending'
                    );

                case 'none':
                default:
                    // Remove any existing transaction for this order detail
                    return self::removeTransaction($id_vendor, $id_order_detail);
            }

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'TransactionHelper::processCommissionTransaction - Error: ' . $e->getMessage(),
                3,
                null,
                'TransactionHelper',
                $id_order_detail
            );
            return false;
        }
    }

    /**
     * Remove transaction for order detail (when action is 'none')
     *
     * @param int $id_vendor Vendor ID
     * @param int $order_detail_id Order detail ID
     * @return bool Success
     */
    public static function removeTransaction($id_vendor, $order_detail_id)
    {
        return Db::getInstance()->delete(
            'mv_vendor_transaction',
            'id_vendor = ' . (int)$id_vendor . ' AND order_detail_id = ' . (int)$order_detail_id
        );
    }

    /**
     * Get vendor transactions with pagination and filtering
     *
     * @param int $id_vendor Vendor ID
     * @param string|null $status Optional status filter
     * @param string|null $transaction_type Optional type filter
     * @param int|null $limit Optional limit
     * @param int|null $offset Optional offset
     * @return array Transactions
     */
    public static function getVendorTransactions($id_vendor, $status = null, $transaction_type = null, $limit = null, $offset = null)
    {
        $query = new DbQuery();
        $query->select('vt.*, o.reference as order_reference, od.product_name, vp.reference as payment_reference, vp.payment_method');
        $query->from('mv_vendor_transaction', 'vt');
        $query->leftJoin('orders', 'o', 'o.id_order = vt.id_order');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vt.order_detail_id');
        $query->leftJoin('mv_vendor_payment', 'vp', 'vp.id_vendor_payment = vt.id_vendor_payment');
        $query->where('vt.id_vendor = ' . (int)$id_vendor);

        if ($status) {
            $query->where('vt.status = "' . pSQL($status) . '"');
        }

        if ($transaction_type) {
            $query->where('vt.transaction_type = "' . pSQL($transaction_type) . '"');
        }

        $query->orderBy('vt.date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Count vendor transactions for pagination
     *
     * @param int $id_vendor Vendor ID
     * @param string|null $status Optional status filter
     * @param string|null $transaction_type Optional type filter
     * @return int Total count
     */
    public static function countVendorTransactions($id_vendor, $status = null, $transaction_type = null)
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);

        if ($status) {
            $query->where('status = "' . pSQL($status) . '"');
        }

        if ($transaction_type) {
            $query->where('transaction_type = "' . pSQL($transaction_type) . '"');
        }

        return (int)Db::getInstance()->getValue($query);
    }

    /**
     * Get transaction statistics for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return array Statistics
     */
    public static function getVendorTransactionStats($id_vendor)
    {
        $query = new DbQuery();
        $query->select('
            COUNT(*) as total_transactions,
            COALESCE(SUM(CASE WHEN status = "pending" AND transaction_type = "commission" THEN vendor_amount ELSE 0 END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN status = "paid" AND transaction_type = "commission" THEN vendor_amount ELSE 0 END), 0) as paid_amount,
            COALESCE(SUM(CASE WHEN status = "cancelled" THEN vendor_amount ELSE 0 END), 0) as cancelled_amount,
            COALESCE(SUM(CASE WHEN transaction_type = "refund" THEN ABS(vendor_amount) ELSE 0 END), 0) as refund_amount
        ');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);

        $result = Db::getInstance()->getRow($query);
        
        return [
            'total_transactions' => (int)$result['total_transactions'],
            'pending_amount' => (float)$result['pending_amount'],
            'paid_amount' => (float)$result['paid_amount'],
            'cancelled_amount' => (float)$result['cancelled_amount'],
            'refund_amount' => (float)$result['refund_amount'],
            'total_earned' => (float)$result['paid_amount'] - (float)$result['refund_amount']
        ];
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
        $query->select('COALESCE(SUM(vendor_amount), 0)');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('status = "pending"');
        $query->where('transaction_type = "commission"');

        return (float)Db::getInstance()->getValue($query);
    }

    /**
     * Get total paid commission amount for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float Total paid commission amount
     */
    public static function getVendorPaidCommission($id_vendor)
    {
        $query = new DbQuery();
        $query->select('COALESCE(SUM(vendor_amount), 0)');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('status = "paid"');
        $query->where('transaction_type = "commission"');

        return (float)Db::getInstance()->getValue($query);
    }

    /**
     * Pay vendor commissions - optimized version
     * Creates payment record and updates transaction statuses
     *
     * @param int $id_vendor Vendor ID
     * @param string $payment_method Payment method
     * @param string $reference Payment reference
     * @return array Result with success flag and details
     */
    public static function payVendorCommissions($id_vendor, $payment_method, $reference)
    {
        try {
            // Get pending commission transactions
            $pendingTransactions = self::getVendorTransactions($id_vendor, 'pending', 'commission');
            
            if (empty($pendingTransactions)) {
                return [
                    'success' => false,
                    'message' => 'No pending commissions found for this vendor'
                ];
            }

            // Calculate total amount
            $totalAmount = 0;
            foreach ($pendingTransactions as $transaction) {
                $totalAmount += $transaction['vendor_amount'];
            }

            if ($totalAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'No commission amount to pay'
                ];
            }

            // Start transaction
            Db::getInstance()->execute('START TRANSACTION');

            // Create payment record
            $payment = new VendorPayment();
            $payment->id_vendor = (int)$id_vendor;
            $payment->amount = $totalAmount;
            $payment->payment_method = pSQL($payment_method);
            $payment->reference = pSQL($reference);
            $payment->status = 'completed';
            $payment->date_add = date('Y-m-d H:i:s');

            if (!$payment->save()) {
                throw new Exception('Failed to create payment record');
            }

            // Update all pending commission transactions to paid
            $updateResult = Db::getInstance()->update(
                'mv_vendor_transaction',
                [
                    'status' => 'paid',
                    'id_vendor_payment' => (int)$payment->id,
                    'date_add' => date('Y-m-d H:i:s')
                ],
                'id_vendor = ' . (int)$id_vendor . ' AND status = "pending" AND transaction_type = "commission"'
            );

            if (!$updateResult) {
                throw new Exception('Failed to update transaction statuses');
            }

            Db::getInstance()->execute('COMMIT');

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'amount_paid' => $totalAmount,
                'transactions_count' => count($pendingTransactions)
            ];

        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            
            PrestaShopLogger::addLog(
                'TransactionHelper::payVendorCommissions - Error: ' . $e->getMessage(),
                3,
                null,
                'TransactionHelper',
                $id_vendor
            );
            
            return [
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get transactions for specific order detail
     *
     * @param int $id_vendor Vendor ID
     * @param int $order_detail_id Order detail ID
     * @return array Transactions for this order detail
     */
    public static function getTransactionsByOrderDetail($id_vendor, $order_detail_id)
    {
        $query = new DbQuery();
        $query->select('vt.*, vp.reference as payment_reference, vp.payment_method');
        $query->from('mv_vendor_transaction', 'vt');
        $query->leftJoin('mv_vendor_payment', 'vp', 'vp.id_vendor_payment = vt.id_vendor_payment');
        $query->where('vt.order_detail_id = ' . (int)$order_detail_id);
        $query->where('vt.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('vt.date_add DESC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Cancel pending transactions for order details
     *
     * @param int $id_vendor Vendor ID
     * @param array $order_detail_ids Array of order detail IDs
     * @param string $reason Cancellation reason
     * @return bool Success
     */
    public static function cancelPendingTransactions($id_vendor, $order_detail_ids, $reason = 'Order cancelled')
    {
        if (empty($order_detail_ids)) {
            return false;
        }

        $orderDetailIdsList = implode(',', array_map('intval', $order_detail_ids));

        return Db::getInstance()->update(
            'mv_vendor_transaction',
            [
                'status' => 'cancelled',
                'date_add' => date('Y-m-d H:i:s')
            ],
            'id_vendor = ' . (int)$id_vendor . 
            ' AND order_detail_id IN (' . $orderDetailIdsList . ')' .
            ' AND status = "pending"'
        );
    }

    /**
     * Get transaction summary by date range
     *
     * @param int $id_vendor Vendor ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Transaction summary
     */
    public static function getTransactionSummaryByDateRange($id_vendor, $start_date, $end_date)
    {
        $query = new DbQuery();
        $query->select('
            transaction_type,
            status,
            COUNT(*) as transaction_count,
            SUM(vendor_amount) as total_amount,
            DATE(date_add) as transaction_date
        ');
        $query->from('mv_vendor_transaction');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('DATE(date_add) BETWEEN "' . pSQL($start_date) . '" AND "' . pSQL($end_date) . '"');
        $query->groupBy('transaction_type, status, DATE(date_add)');
        $query->orderBy('transaction_date DESC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Validate transaction data before processing
     *
     * @param array $transactionData Transaction data to validate
     * @return array Validation result
     */
    public static function validateTransactionData($transactionData)
    {
        $errors = [];

        $requiredFields = ['id_vendor', 'transaction_type', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($transactionData[$field]) || empty($transactionData[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }

        $validTypes = ['commission', 'refund', 'adjustment'];
        if (isset($transactionData['transaction_type']) && !in_array($transactionData['transaction_type'], $validTypes)) {
            $errors[] = "Invalid transaction type: " . $transactionData['transaction_type'];
        }

        $validStatuses = ['pending', 'paid', 'cancelled'];
        if (isset($transactionData['status']) && !in_array($transactionData['status'], $validStatuses)) {
            $errors[] = "Invalid status: " . $transactionData['status'];
        }

        if (isset($transactionData['vendor_amount']) && !is_numeric($transactionData['vendor_amount'])) {
            $errors[] = "Invalid vendor amount";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Cleanup old completed transactions (maintenance function)
     *
     * @param int $days_old Number of days to keep
     * @return bool Success
     */
    public static function cleanupOldTransactions($days_old = 365)
    {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_old days"));
        
        return Db::getInstance()->delete(
            'mv_vendor_transaction',
            'status IN ("paid", "cancelled") AND date_add < "' . pSQL($cutoff_date) . '"'
        );
    }
}