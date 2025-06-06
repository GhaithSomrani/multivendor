<?php

/**
 * OrderHelper - Helper class for managing vendor order details
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderHelper
{
    /**
     * Process a new order detail to create vendor order detail if needed
     *
     * @param OrderDetail $orderDetail The order detail object
     * @return bool Success status
     */
    public static function processOrderDetailForVendor($orderDetail)
    {
        try {
            // Get the product to check if it belongs to a vendor
            $product = new Product($orderDetail->product_id);

            if (!Validate::isLoadedObject($product) || !$product->id_supplier) {
                return false; // No supplier, not a vendor product
            }

            $id_supplier = $product->id_supplier;

            // Check if this supplier is associated with a vendor
            $vendor = Vendor::getVendorBySupplier($id_supplier);

            if (!$vendor) {
                return false; // No vendor associated with this supplier
            }

            // Check if vendor order detail already exists
            $existingVendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor(
                $orderDetail->id_order_detail,
                $vendor['id_vendor']
            );

            if ($existingVendorOrderDetail) {
                return true; // Already exists, no need to create again
            }

            // Calculate commission
            $commission_rate = VendorHelper::getCommissionRate($vendor['id_vendor']);
            $total_price = $orderDetail->total_price_tax_incl;
            $commission_amount = $total_price * ($commission_rate / 100);
            $vendor_amount = $total_price - $commission_amount;

            // Create vendor order detail record
            $vendorOrderDetail = new VendorOrderDetail();
            $vendorOrderDetail->id_order_detail = $orderDetail->id_order_detail;
            $vendorOrderDetail->id_vendor = $vendor['id_vendor'];
            $vendorOrderDetail->id_order = $orderDetail->id_order;
            $vendorOrderDetail->commission_rate = $commission_rate;
            $vendorOrderDetail->commission_amount = $commission_amount;
            $vendorOrderDetail->vendor_amount = $vendor_amount;
            $vendorOrderDetail->date_add = date('Y-m-d H:i:s');

            if ($vendorOrderDetail->save()) {
                // Get default status
                $defaultStatus = OrderLineStatus::getDefaultStatusTypeId();

                // Create initial order line status
                $orderLineStatus = new OrderLineStatus();
                $orderLineStatus->id_order_detail = $orderDetail->id_order_detail;
                $orderLineStatus->id_vendor = $vendor['id_vendor'];
                $orderLineStatus->id_order_line_status_type = $defaultStatus;
                $orderLineStatus->date_add = date('Y-m-d H:i:s');
                $orderLineStatus->date_upd = date('Y-m-d H:i:s');
                $orderLineStatus->save();

                // Log status change
                OrderLineStatusLog::logStatusChange(
                    $orderDetail->id_order_detail,
                    $vendor['id_vendor'],
                    null, // no old status
                    $defaultStatus,
                    0, // system change
                    'Order detail added from admin'
                );

                return true;
            }

            return false;
        } catch (Exception $e) {
            // Log error but don't break the order process
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error processing order detail for vendor: ' . $e->getMessage(),
                3,
                null,
                'OrderDetail',
                $orderDetail->id_order_detail ?? 0
            );
            return false;
        }
    }

    /**
     * Update vendor order detail when order detail is modified
     *
     * @param OrderDetail $orderDetail The order detail object
     * @return bool Success status
     */
    public static function updateOrderDetailForVendor($orderDetail)
    {
        try {
            // Get the product to check if it belongs to a vendor
            $product = new Product($orderDetail->product_id);

            if (!Validate::isLoadedObject($product) || !$product->id_supplier) {
                return false;
            }

            $id_supplier = $product->id_supplier;
            $vendor = Vendor::getVendorBySupplier($id_supplier);

            if (!$vendor) {
                return false;
            }

            // Get existing vendor order detail
            $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor(
                $orderDetail->id_order_detail,
                $vendor['id_vendor']
            );

            if ($vendorOrderDetail) {
                // Recalculate commission based on updated order detail
                $commission_rate = VendorHelper::getCommissionRate($vendor['id_vendor']);
                $total_price = $orderDetail->total_price_tax_incl;
                $commission_amount = $total_price * ($commission_rate / 100);
                $vendor_amount = $total_price - $commission_amount;

                // Update the vendor order detail
                $result = Db::getInstance()->update('mv_vendor_order_detail', [
                    'commission_rate' => (float)$commission_rate,
                    'commission_amount' => (float)$commission_amount,
                    'vendor_amount' => (float)$vendor_amount,
                ], 'id_vendor_order_detail = ' . (int)$vendorOrderDetail['id_vendor_order_detail']);

                // Log the update
                OrderLineStatusLog::logStatusChange(
                    $orderDetail->id_order_detail,
                    $vendor['id_vendor'],
                    'updated',
                    'updated',
                    0, // system change
                    'Order detail updated from admin - commission recalculated'
                );

                return $result;
            } else {
                // If vendor order detail doesn't exist, create it
                return self::processOrderDetailForVendor($orderDetail);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error updating order detail for vendor: ' . $e->getMessage(),
                3,
                null,
                'OrderDetail',
                $orderDetail->id_order_detail ?? 0
            );
            return false;
        }
    }

    /**
     * Delete vendor order detail when order detail is removed
     *
     * @param OrderDetail $orderDetail The order detail object
     * @return bool Success status
     */
    public static function deleteOrderDetailForVendor($orderDetail)
    {
        try {
            // Find and delete vendor order detail
            $vendorOrderDetail = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'mv_vendor_order_detail` 
                 WHERE `id_order_detail` = ' . (int)$orderDetail->id_order_detail
            );

            if ($vendorOrderDetail) {
                $id_vendor = $vendorOrderDetail['id_vendor'];

                // Delete vendor order detail
                $result1 = Db::getInstance()->delete(
                    'mv_vendor_order_detail',
                    'id_order_detail = ' . (int)$orderDetail->id_order_detail
                );

                // Delete order line status
                $result2 = Db::getInstance()->delete(
                    'mv_order_line_status',
                    'id_order_detail = ' . (int)$orderDetail->id_order_detail .
                        ' AND id_vendor = ' . (int)$id_vendor
                );

                // Log the deletion
                OrderLineStatusLog::logStatusChange(
                    $orderDetail->id_order_detail,
                    $id_vendor,
                    'deleted',
                    null,
                    0, // system change
                    'Order detail deleted from admin'
                );

                Db::getInstance()->update('mv_vendor_transaction', [
                    'status' => 'cancelled',
                ], 'order_detail_id = ' . (int)$orderDetail->id_order_detail .
                    ' AND id_vendor = ' . (int)$id_vendor .
                    ' AND status = "pending"');

                return $result1 && $result2;
            }

            return true; // Nothing to delete, consider it successful

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error deleting order detail for vendor: ' . $e->getMessage(),
                3,
                null,
                'OrderDetail',
                $orderDetail->id_order_detail ?? 0
            );
            return false;
        }
    }


    /**
     * Process multiple order details for vendors (bulk operation)
     *
     * @param array $orderDetails Array of OrderDetail objects
     * @return array Results with success/failure count
     */
    public static function processBulkOrderDetailsForVendor($orderDetails)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        foreach ($orderDetails as $orderDetail) {
            $result = self::processOrderDetailForVendor($orderDetail);

            if ($result === true) {
                $results['success']++;
            } elseif ($result === false) {
                $results['failed']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Synchronize all existing order details with vendor order details
     * This is useful for migration or data consistency checks
     *
     * @param int $id_order Optional order ID to sync specific order
     * @return array Results with statistics
     */
    public static function synchronizeOrderDetailsWithVendors($id_order = null)
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        try {
            // Get order details to process
            $query = new DbQuery();
            $query->select('od.*, p.id_supplier');
            $query->from('order_detail', 'od');
            $query->leftJoin('product', 'p', 'p.id_product = od.product_id');

            if ($id_order) {
                $query->where('od.id_order = ' . (int)$id_order);
            }

            $query->where('p.id_supplier > 0'); // Only products with suppliers

            $orderDetails = Db::getInstance()->executeS($query);

            foreach ($orderDetails as $detailData) {
                $results['processed']++;

                // Check if supplier has a vendor
                $vendor = Vendor::getVendorBySupplier($detailData['id_supplier']);

                if (!$vendor) {
                    $results['skipped']++;
                    continue;
                }

                // Check if vendor order detail exists
                $existingVendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor(
                    $detailData['id_order_detail'],
                    $vendor['id_vendor']
                );

                if (!$existingVendorOrderDetail) {
                    // Create new vendor order detail
                    $orderDetail = new OrderDetail($detailData['id_order_detail']);
                    if (self::processOrderDetailForVendor($orderDetail)) {
                        $results['created']++;
                    } else {
                        $results['errors']++;
                    }
                } else {
                    // Update existing vendor order detail
                    $orderDetail = new OrderDetail($detailData['id_order_detail']);
                    if (self::updateOrderDetailForVendor($orderDetail)) {
                        $results['updated']++;
                    } else {
                        $results['errors']++;
                    }
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error in synchronizeOrderDetailsWithVendors: ' . $e->getMessage(),
                3,
                null,
                'OrderHelper'
            );
            $results['errors']++;
        }

        return $results;
    }

    /**
     * Check if an order detail belongs to a vendor
     *
     * @param int $id_order_detail Order detail ID
     * @return array|false Vendor data or false if not found
     */
    public static function getVendorByOrderDetail($id_order_detail)
    {
        try {
            $query = new DbQuery();
            $query->select('v.*');
            $query->from('mv_vendor_order_detail', 'vod');
            $query->leftJoin('mv_vendor', 'v', 'v.id_vendor = vod.id_vendor');
            $query->where('vod.id_order_detail = ' . (int)$id_order_detail);

            return Db::getInstance()->getRow($query);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error getting vendor by order detail: ' . $e->getMessage(),
                3,
                null,
                'OrderHelper'
            );
            return false;
        }
    }

    /**
     * Get vendor order details statistics
     *
     * @param int $id_vendor Optional vendor ID to get specific vendor stats
     * @return array Statistics
     */
    public static function getVendorOrderDetailsStats($id_vendor = null)
    {
        try {
            $query = new DbQuery();
            $query->select('
                COUNT(*) as total_order_details,
                SUM(commission_amount) as total_commission,
                SUM(vendor_amount) as total_vendor_amount,
                AVG(commission_rate) as avg_commission_rate
            ');
            $query->from('mv_vendor_order_detail');

            if ($id_vendor) {
                $query->where('id_vendor = ' . (int)$id_vendor);
            }

            return Db::getInstance()->getRow($query);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error getting vendor order details stats: ' . $e->getMessage(),
                3,
                null,
                'OrderHelper'
            );
            return [
                'total_order_details' => 0,
                'total_commission' => 0,
                'total_vendor_amount' => 0,
                'avg_commission_rate' => 0
            ];
        }
    }
    /**
     * Get all vendor order details for a specific order
     *
     * @param int $id_order Order ID
     * @return array Vendor order details
     */
    public static function getVendorAmountByOrderDetail($id_order_detail)
    {
        try {
            $query = new DbQuery();
            $query->select('vendor_amount');
            $query->from('mv_vendor_order_detail');
            $query->where('id_order_detail = ' . (int)$id_order_detail);

            return Db::getInstance()->getValue($query);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: Error getting vendor amount by order detail: ' . $e->getMessage(),
                3,
                null,
                'OrderHelper'
            );
            return false;
        }
    }

    /**
     * Reset order line statuses to default French statuses
     */
    public static function resetOrderLineStatuses()
    {
        try {
            // Clear existing status types
            $deleteQuery = 'DELETE FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type`';
            if (!Db::getInstance()->execute($deleteQuery)) {
                return false;
            }

            // Insert default French status types
            $insertQuery = 'INSERT INTO `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
                (`name`, `color`, `is_admin_allowed`, `is_vendor_allowed`, `affects_commission`, `commission_action`, `position`, `active`, `date_add`, `date_upd`) 
                VALUES 
                ("en attente client", "#0079FF", 1, 0, 0, "none", 1, 1, NOW(), NOW()),
                ("à traiter", "#FF865D", 1, 1, 0, "none", 2, 1, NOW(), NOW()),
                ("emballé", "#0079FF", 1, 1, 0, "none", 3, 1, NOW(), NOW()),
                ("prêt pour ramassage", "#0079FF", 1, 1, 0, "none", 4, 1, NOW(), NOW()),
                ("ramassé", "#0079FF", 1, 1, 1, "add", 5, 1, NOW(), NOW()),
                ("réception magasin", "#FF865D", 1, 0, 1, "add", 6, 1, NOW(), NOW()),
                ("prêt pour expédition", "#FF865D", 1, 0, 1, "add", 7, 1, NOW(), NOW()),
                ("expédié", "#00DFA2", 1, 0, 1, "add", 8, 1, NOW(), NOW()),
                ("endommagé", "#FF0060", 1, 0, 1, "refund", 9, 1, NOW(), NOW()),
                ("perdu", "#FF0060", 1, 0, 1, "add", 10, 1, NOW(), NOW()),
                ("rejeté", "#FF0060", 1, 0, 1, "refund", 11, 1, NOW(), NOW()),
                ("retour magasin", "#FF0060", 1, 0, 1, "refund", 12, 1, NOW(), NOW()),
                ("retour fournisseur", "#FF0060", 1, 0, 1, "refund", 13, 1, NOW(), NOW()),
                ("remboursé", "#FF0060", 1, 0, 1, "cancel", 14, 1, NOW(), NOW()),
                ("livré", "#00DFA2", 1, 0, 1, "add", 15, 1, NOW(), NOW()),
                ("payé", "#00DFA2", 1, 0, 0, "add", 16, 1, NOW(), NOW()),
                ("non conforme", "#FF0060", 1, 0, 1, "cancel", 17, 1, NOW(), NOW()),
                ("rupture de stock", "#FF0060", 1, 1, 0, "none", 18, 1, NOW(), NOW()),
                ("annulé par client", "#000000", 1, 0, 0, "none", 19, 1, NOW(), NOW()),
                ("retour fournisseur", "#FF0060", 1, 0, 0, "refund", 20, 1, NOW(), NOW())';

            return Db::getInstance()->execute($insertQuery);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error resetting order line statuses: ' . $e->getMessage(), 3);
            return false;
        }
    }
    public static function getStatusTotalCount()
    {
        return (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type`');
    }
}
