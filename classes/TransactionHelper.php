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
     * @param int $order_detail_id Order detail ID
     * @param float $commission_amount Commission amount
     * @param float $vendor_amount Vendor amount
     * @param string $transaction_type Transaction type
     * @param string $status Status
     * @param int|null $id_vendor_payment Vendor payment ID
     * @return bool Success
     */
    public static function createOrUpdateTransaction($order_detail_id, $vendor_amount, $transaction_type, $status, $id_vendor_payment = null)
    {
        try {
            $existing = self::getExistingTransaction($order_detail_id, $transaction_type);
            if ($existing) {
                $id_vendor_payment = $id_vendor_payment ?? $existing['id_vendor_payment'];
                $status = $id_vendor_payment ? $existing['status'] : $status;
                if ($existing['status'] !== $status) {
                    $transactionObj = new VendorTransaction($existing['id_vendor_transaction']);
                    $transactionObj->status = pSQL($status);
                    $transactionObj->id_vendor_payment = $id_vendor_payment ? (int)$id_vendor_payment : null;
                    $transactionObj->date_add = date('Y-m-d H:i:s');
                    return $transactionObj->save();
                }
                return true;
            }

            $transaction = new VendorTransaction();
            $transaction->order_detail_id = (int)$order_detail_id;
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
     * @param int $order_detail_id Order detail ID
     * @param string $transaction_type Transaction type
     * @return array|false Existing transaction or false
     */
    public static function getExistingTransaction($order_detail_id, $transaction_type)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor_transaction');
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
     * @param string $commission_action Commission action (add, cancel, refund, none)
     * @return bool Success
     */
    public static function processCommissionTransaction($id_order_detail, $commission_action)
    {
        try {
            $id_vendor = Vendor::getVendorIdFromOrderDetail($id_order_detail);
            if (!$id_vendor) {
                PrestaShopLogger::addLog(
                    'TransactionHelper::processCommissionTransaction - No vendor found for order detail: ' . $id_order_detail,
                    2,
                    null,
                    'TransactionHelper',
                    $id_order_detail
                );
                return false;
            }

            // Get vendor order detail
            $vendorOrderDetail = VendorHelper::getVendorOrderDetailByOrderDetailAndVendor($id_order_detail, $id_vendor);

            if (!$vendorOrderDetail) {
                PrestaShopLogger::addLog(
                    'TransactionHelper::processCommissionTransaction - No vendor order detail found for order detail: ' . $id_order_detail . ', vendor: ' . $id_vendor,
                    2,
                    null,
                    'TransactionHelper',
                    $id_order_detail
                );
                return false;
            }

            $vendor_amount = (float)$vendorOrderDetail['vendor_amount'];

            switch ($commission_action) {
                case 'add':
                    return self::createOrUpdateTransaction(
                        $id_order_detail,
                        $vendor_amount,
                        'commission',
                        'pending'
                    );

                case 'cancel':
                    return self::createOrUpdateTransaction(
                        $id_order_detail,
                        0,
                        0,
                        'commission',
                        'cancelled'
                    );

                case 'refund':
                    return self::createOrUpdateTransaction(
                        $id_order_detail,
                        -$vendor_amount,
                        'refund',
                        'pending'
                    );

                case 'none':
                default:
                    break;
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
     * @param int $order_detail_id Order detail ID
     * @return bool Success
     */
    public static function removeTransaction($order_detail_id)
    {

        $transactionIds = Db::getInstance()->executeS(
            'SELECT id_vendor_transaction FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction
            WHERE order_detail_id = ' . (int)$order_detail_id
        );

        foreach ($transactionIds as $transactionId) {
            $transaction = new VendorTransaction($transactionId['id_vendor_transaction']);
            $transaction->delete();
        }
        return true;
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
        $query->select('vt.*, o.reference as order_reference, vod.product_name, vp.reference as payment_reference, vp.payment_method');
        $query->from('mv_vendor_transaction', 'vt');
        $query->leftJoin('mv_vendor_order_detail', 'vod', 'vod.id_order_detail = vt.order_detail_id');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vt.order_detail_id');
        $query->leftJoin('orders', 'o', 'o.id_order = od.id_order');
        $query->leftJoin('mv_vendor_payment', 'vp', 'vp.id_vendor_payment = vt.id_vendor_payment');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);

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
            $pendingTransactions = self::getVendorTransactions($id_vendor, 'pending', '');

            if (empty($pendingTransactions)) {
                return [
                    'success' => false,
                    'message' => 'No pending commissions found for this vendor'
                ];
            }

            $totalAmount = 0;
            $transactionIds = [];
            foreach ($pendingTransactions as $transaction) {
                $totalAmount += $transaction['vendor_amount'];
                $transactionIds[] = (int)$transaction['id_vendor_transaction'];
            }

            if ($totalAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'No commission amount to pay'
                ];
            }

            Db::getInstance()->execute('START TRANSACTION');

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


            $updateResult = Db::getInstance()->update(
                'mv_vendor_transaction',
                [
                    'status' => 'paid',
                    'id_vendor_payment' => (int)$payment->id,
                    'date_add' => date('Y-m-d H:i:s')
                ],
                'id_vendor_transaction IN (' . implode(',', $transactionIds) . ') AND status = "pending" AND transaction_type = "commission"'
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



    public static function isOrderDetailPaid($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('mv_vendor_transaction', 'vt');
        $query->leftJoin('mv_vendor_payment', 'vp', 'vt.id_vendor_payment = vp.id_vendor_payment');
        $query->where('vt.order_detail_id = ' . (int)$id_order_detail);
        $query->where('vt.status = "paid"');
        $query->where('vt.transaction_type = "commission"');
        $query->where('vt.transaction_type = "commission"');
        $query->where('vp.status = "completed"');
        return (bool)Db::getInstance()->getValue($query);
    }

    public static function getAvailableTransaction($OrderDetailIds, $transactionType)
    {
        $availableTransactions = [];
        foreach ($OrderDetailIds as $id_order_detail) {
            try {
                $transactionsRow = self::getExistingTransaction($id_order_detail, $transactionType);
                if ($transactionsRow && $transactionsRow['id_vendor_payment'] == 0) {
                    $availableTransactions[] = $transactionsRow;
                }
            } catch (Exception $e) {
                throw new Exception('Transaction non trouvée pour le détail de commande : ' . $id_order_detail . ' et le type de transaction : ' . $transactionType);
            }
        }

        return $availableTransactions;
    }

    public static function getManifestReference($id_order_detail, $transaction_type)
    {
        if ($transaction_type == 'commission') {
            $manifest_type = Manifest::TYPE_PICKUP;
        } elseif ($transaction_type == 'refund') {
            $manifest_type = Manifest::TYPE_RETURNS;
        }
        return Manifest::getManifestByOrderDetailAndType($id_order_detail, $manifest_type);
    }
}
