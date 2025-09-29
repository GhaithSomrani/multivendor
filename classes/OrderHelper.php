<?php

/**
 * OrderHelper - Helper class for managing vendor order details
 */

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

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
            $product = new Product($orderDetail->product_id);

            if (!Validate::isLoadedObject($product) || !$product->id_supplier) {
                return false;
            }

            $id_supplier = $product->id_supplier;

            $vendor = Vendor::getVendorBySupplier($id_supplier);

            if (!$vendor) {
                return false;
            }

            $existingVendorOrderDetail = VendorHelper::getVendorOrderDetailByOrderDetailAndVendor(
                $orderDetail->id_order_detail,
                $vendor['id_vendor']
            );

            if ($existingVendorOrderDetail) {
                return true;
            }
            $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();

            $commission_rate = VendorCommission::getCommissionRate($vendor['id_vendor']);
            $product_price = $orderDetail->unit_price_tax_incl;
            $quantity = $orderDetail->product_quantity;
            $total_price = $quantity * $product_price;
            $commission_amount = $total_price * ($commission_rate / 100);
            $vendor_amount = $total_price - $commission_amount;

            $vendorOrderDetail = new VendorOrderDetail();
            $vendorOrderDetail->id_order_detail = $orderDetail->id_order_detail;
            $vendorOrderDetail->id_vendor = $vendor['id_vendor'];
            $vendorOrderDetail->id_order = $orderDetail->id_order;
            $vendorOrderDetail->product_id = $orderDetail->product_id;
            $vendorOrderDetail->product_name = $orderDetail->product_name;
            $vendorOrderDetail->product_reference = $orderDetail->product_reference;
            $vendorOrderDetail->product_mpn = $orderDetail->product_mpn;
            $vendorOrderDetail->product_price = $orderDetail->unit_price_tax_incl;
            $vendorOrderDetail->product_quantity = $orderDetail->product_quantity;
            $vendorOrderDetail->product_attribute_id = $orderDetail->product_attribute_id ?: null;
            $vendorOrderDetail->commission_rate = $commission_rate;
            $vendorOrderDetail->commission_amount = $commission_amount;
            $vendorOrderDetail->vendor_amount = $vendor_amount;
            $vendorOrderDetail->date_add = date('Y-m-d H:i:s');
            $vendorOrderDetail->save();
            $orderLineStatus = new OrderLineStatus();
            $orderLineStatus->id_order_detail = $orderDetail->id_order_detail;
            $orderLineStatus->id_vendor = $vendor['id_vendor'];
            $orderLineStatus->id_order_line_status_type = $defaultStatusTypeId;
            $orderLineStatus->date_add = date('Y-m-d H:i:s');
            $orderLineStatus->date_upd = date('Y-m-d H:i:s');
            $orderLineStatus->save();

            OrderLineStatusLog::logStatusChange(
                $orderDetail->id_order_detail,
                $vendor['id_vendor'],
                null,
                $defaultStatusTypeId,
                0,
            );

            return true;
        } catch (Exception $e) {
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
     * Retrieves vendor order details based on filters
     *
     * @param int $id_vendor The ID of the vendor
     * @param array $filters The filters to apply
     * @param int $limit The maximum number of results to return
     * @param int $offset The offset to start from
     *
     * @return array The vendor order details
     */
    public static function getVendorOrderDetails($id_vendor, $filters = [], $limit = null, $offset = 0)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();

        $sql = 'SELECT ' . ($limit ? 'SQL_CALC_FOUND_ROWS ' : '') . '
            vod.id_order_detail,
            vod.id_order,
            vod.product_name,
            vod.product_reference,
            vod.product_mpn,
            vod.product_quantity,
            vod.product_price,
            vod.commission_amount,
            vod.vendor_amount,
            vod.date_add as order_date,
            v.shop_name as vendor_name,
            o.reference as order_reference,
            o.current_state as order_state_id,
            osl.name as order_state_name,
            o.date_add as order_created_date,
            vt.status as payment_status,
            olst.name as line_status,
            olst.color as status_color,
            ols.id_order_line_status_type as status_type_id,
            od.unit_price_tax_incl as unit_price , 
            md.id_manifest 
        FROM `' . _DB_PREFIX_ . 'mv_vendor_order_detail` vod
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (vod.id_vendor = v.id_vendor)
        LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (vod.id_order = o.id_order)
        LEFT JOIN `' . _DB_PREFIX_ . 'order_detail` od ON (od.id_order_detail = vod.id_order_detail)
        LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (osl.id_order_state = o.current_state)
        LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.id_order_state = o.current_state)
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status` ols ON (ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor)
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_order_line_status_type` olst ON (olst.id_order_line_status_type = ols.id_order_line_status_type)
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_transaction` vt ON (vt.order_detail_id = vod.id_order_detail )
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_details` md ON (md.id_order_details = vod.id_order_detail ) 
        LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest` m ON (m.id_manifest = md.id_manifest )
        WHERE vod.id_vendor = ' . (int)$id_vendor;

        // Order ID filter
        if (!empty($filters['order_id'])) {
            $order_id = (int)$filters['order_id'];
            if ($order_id > 0) {
                $sql .= ' AND vod.id_order = ' . $order_id;
            }
        }

        // Order Detail ID filter
        if (!empty($filters['id_order_detail'])) {
            $id_order_detail = (int)$filters['id_order_detail'];
            if ($id_order_detail > 0) {
                $sql .= ' AND vod.id_order_detail = ' . $id_order_detail;
            }
        }

        // Product name filter
        if (!empty($filters['product_name'])) {
            $product_name = pSQL($filters['product_name']);
            $sql .= ' AND vod.product_name LIKE "%' . $product_name . '%"';
        }

        // Order current state filter
        if (!empty($filters['order_status']) && is_numeric($filters['order_status'])) {
            $current_state = (int)$filters['order_status'];
            $sql .= ' AND o.current_state = ' . $current_state;
        }

        // Product reference filter
        if (!empty($filters['reference'])) {
            $reference = pSQL($filters['reference']);
            $sql .= ' AND vod.product_reference LIKE "%' . $reference . '%"';
        }

        // Status filter
        if (!empty($filters['status']) && is_numeric($filters['status'])) {
            $status_id = (int)$filters['status'];
            $sql .= ' AND COALESCE(ols.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') = ' . $status_id;
        }

        // Quantity filters
        if (!empty($filters['quantity']) && is_numeric($filters['quantity'])) {
            $sql .= ' AND vod.product_quantity = ' . (int)$filters['quantity'];
        }

        if (!empty($filters['quantity_min']) && is_numeric($filters['quantity_min'])) {
            $sql .= ' AND vod.product_quantity >= ' . (int)$filters['quantity_min'];
        }

        if (!empty($filters['quantity_max']) && is_numeric($filters['quantity_max'])) {
            $sql .= ' AND vod.product_quantity <= ' . (int)$filters['quantity_max'];
        }

        // Price filters
        if (!empty($filters['price_min']) && is_numeric($filters['price_min'])) {
            $sql .= ' AND od.unit_price_tax_incl >= ' . (float)$filters['price_min'];
        }

        if (!empty($filters['price_max']) && is_numeric($filters['price_max'])) {
            $sql .= ' AND od.unit_price_tax_incl <= ' . (float)$filters['price_max'];
        }

        // Date filters
        if (!empty($filters['date_from'])) {
            $date_from = pSQL($filters['date_from']);
            $sql .= ' AND DATE(o.date_add) >= "' . $date_from . '"';
        }

        if (!empty($filters['date_to'])) {
            $date_to = pSQL($filters['date_to']);
            $sql .= ' AND DATE(o.date_add) <= "' . $date_to . '"';
        }

        // MPN filter
        if (!empty($filters['mpn'])) {
            $mpn = pSQL($filters['mpn']);
            $sql .= ' AND vod.product_mpn LIKE "%' . $mpn . '%"';
        }

        // Order reference filter
        if (!empty($filters['order_reference'])) {
            $order_ref = pSQL($filters['order_reference']);
            $sql .= ' AND o.reference LIKE "%' . $order_ref . '%"';
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $payment_status = pSQL($filters['payment_status']);
            $sql .= ' AND vt.status = "' . $payment_status . '"';
        }

        // Commission amount filters
        if (!empty($filters['commission_min']) && is_numeric($filters['commission_min'])) {
            $sql .= ' AND vod.commission_amount >= ' . (float)$filters['commission_min'];
        }

        if (!empty($filters['commission_max']) && is_numeric($filters['commission_max'])) {
            $sql .= ' AND vod.commission_amount <= ' . (float)$filters['commission_max'];
        }

        // Vendor amount filters
        if (!empty($filters['vendor_amount_min']) && is_numeric($filters['vendor_amount_min'])) {
            $sql .= ' AND vod.vendor_amount >= ' . (float)$filters['vendor_amount_min'];
        }

        if (!empty($filters['vendor_amount_max']) && is_numeric($filters['vendor_amount_max'])) {
            $sql .= ' AND vod.vendor_amount <= ' . (float)$filters['vendor_amount_max'];
        }


        // Available for manifest filter
        if (!empty($filters['available_for_manifest'])) {
            $allowedStatusTypes = $filters['available_for_manifest'];
            $sql .= ' AND ols.id_order_line_status_type IN (' . $allowedStatusTypes . ')';
        }
        // Manifest filter
        if (!empty($filters['manifest'])) {
            $manifest_id = (int)$filters['manifest'];

            if ($manifest_id > 0) {
                $sql .= ' AND md.id_manifest = ' . $manifest_id;
            } else {
                $sql .= ' AND md.id_manifest IS NULL';
            }
        }
        // Manifest Type filter
        if (!empty($filters['id_manifest_type'])) {
            $manifest_type_id = (int)$filters['id_manifest_type'];
            $sql .= ' AND m.id_manifest_type = ' . $manifest_type_id;
        }

        if (!empty($filters['allowed_order_line_status_types'])) {
            $allowedStatusTypes = $filters['allowed_order_line_status_types'];
            $sql .= ' AND ols.id_order_line_status_type IN (' . $allowedStatusTypes . ')';
        }

        // Group by 
        $sql .= ' GROUP BY vod.id_order_detail';

        // Order by
        $orderBy = !empty($filters['order_by']) ? pSQL($filters['order_by']) : 'vod.date_add';
        $orderWay = !empty($filters['order_way']) && strtoupper($filters['order_way']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY ' . $orderBy . ' ' . $orderWay;

        // Apply pagination if specified
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$offset . ', ' . (int)$limit;
        }
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        // If using pagination, also return total count
        if ($limit !== null) {
            $totalQuery = 'SELECT FOUND_ROWS() as total';
            $totalResult = Db::getInstance()->getRow($totalQuery);
            return [
                'data' => $results,
                'total' => (int)$totalResult['total']
            ];
        }

        return $results;
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
            //get the current status of the order detail
            $currentStatus = self::getCurrentOrderDetailStatus($orderDetail->id_order_detail) ? self::getCurrentOrderDetailStatus($orderDetail->id_order_detail) : 0;
            if (!Validate::isLoadedObject($product) || !$product->id_supplier) {
                return false;
            }

            $id_supplier = $product->id_supplier;
            $vendor = Vendor::getVendorBySupplier($id_supplier);

            if (!$vendor) {
                return false;
            }

            // Get existing vendor order detail
            $vendorOrderDetail = VendorHelper::getVendorOrderDetailByOrderDetailAndVendor(
                $orderDetail->id_order_detail,
                $vendor['id_vendor']
            );


            if ($vendorOrderDetail) {
                // Recalculate commission based on updated order detail
                $commission_rate = VendorCommission::getCommissionRate($vendor['id_vendor']);
                $total_price = $orderDetail->unit_price_tax_incl * $orderDetail->product_quantity;
                $commission_amount = $total_price * ($commission_rate / 100);
                $vendor_amount = $total_price - $commission_amount;

                // Update the vendor order detail with new product info including reference
                $result = Db::getInstance()->update('mv_vendor_order_detail', [
                    'product_name' => pSQL($orderDetail->product_name),
                    'product_reference' => pSQL($orderDetail->product_reference), 
                    'product_mpn' => pSQL($product->mpn ?: $orderDetail->product_reference),
                    'product_price' => (float)$orderDetail->unit_price_tax_incl,
                    'product_quantity' => (int)$orderDetail->product_quantity,
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
                    $currentStatus,
                    'commande modifiée de l\'administration - mise à jour de la ligne de commande pour le vendeur'
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
                echo 'Deleting vendor order detail for vendor ID: ' . $id_vendor;

                OrderLineStatus::updateStatus(
                    $orderDetail->id_order_detail,
                    $id_vendor,
                    OrderLineStatus::getDeleteStatusTypeId(),
                    0,
                    'commande supprimée de l\'administration',
                    true
                );



                Db::getInstance()->update('mv_vendor_transaction', [
                    'status' => 'cancelled',
                ], 'order_detail_id = ' . (int)$orderDetail->id_order_detail .
                    ' AND id_vendor = ' . (int)$id_vendor .
                    ' AND status = "pending"');
            }
            return true;
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
                $existingVendorOrderDetail = VendorHelper::getVendorOrderDetailByOrderDetailAndVendor(
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

            $insertQuery = 'INSERT INTO `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
    (`id_order_line_status_type`, `name`, `is_admin_allowed`, `is_vendor_allowed`, `color`, `affects_commission`, `commission_action`, `position`, `active`, `available_status`, `date_add`, `date_upd`) 
    VALUES 
    (1, "en attente client", 1, 0, "#0079FF", 0, "none", 1, 1, "2,19,22", NOW(), NOW()),
    (2, "à traiter", 1, 0, "#FF865D", 0, "none", 2, 1, "3,18,19,22", NOW(), NOW()),
    (3, "disponible", 1, 1, "#0079FF", 0, "none", 3, 1, "4,19,22", NOW(), NOW()),
    (4, "prêt pour ramassage", 1, 1, "#0079FF", 1, "add", 4, 1, "5,19,22", NOW(), NOW()),
    (5, "ramassé", 1, 1, "#0079FF", 1, "add", 5, 1, "6,22", NOW(), NOW()),
    (6, "réception magasin", 1, 0, "#FF865D", 1, "add", 6, 1, "7,10,9,17,22", NOW(), NOW()),
    (7, "prêt pour expédition", 1, 0, "#FF865D", 1, "add", 7, 1, "8,22", NOW(), NOW()),
    (8, "expédié", 1, 0, "#00DFA2", 1, "add", 8, 1, "11,10,9,15,22", NOW(), NOW()),
    (9, "endommagé", 1, 0, "#FF0060", 1, "refund", 9, 1, "20,22", NOW(), NOW()),
    (10, "perdu", 1, 0, "#FF0060", 1, "add", 10, 1, "22", NOW(), NOW()),
    (11, "rejeté", 1, 0, "#FF0060", 1, "refund", 11, 1, "12,22", NOW(), NOW()),
    (12, "retour magasin", 1, 0, "#FF0060", 1, "refund", 12, 1, "13,22", NOW(), NOW()),
    (13, "retour fournisseur", 1, 0, "#FF0060", 1, "refund", 13, 1, "14,22", NOW(), NOW()),
    (14, "remboursé", 1, 0, "#FF0060", 1, "cancel", 14, 1, "22", NOW(), NOW()),
    (15, "livré", 1, 0, "#00DFA2", 1, "add", 15, 1, "16", NOW(), NOW()),
    (16, "payé", 1, 0, "#00DFA2", 1, "add", 16, 1, "21", NOW(), NOW()),
    (17, "non conforme", 1, 0, "#FF0060", 1, "cancel", 17, 1, "13", NOW(), NOW()),
    (18, "rupture de stock", 1, 1, "#FF0060", 0, "none", 18, 1, "22", NOW(), NOW()),
    (19, "annulé par client", 1, 0, "#000000", 0, "none", 19, 1, "22", NOW(), NOW()),
    (20, "recipition endommagé", 1, 0, "#FF0060", 0, "none", 20, 1, "12", NOW(), NOW()),
    (21, "demande du retour", 1, 0, "#FF0060", 0, "none", 21, 1, "12", NOW(), NOW()),
    (22, "Faute", 1, 0, "#FFFFFF", 0, "none", 22, 0, "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22", NOW(), NOW())';

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


    public static function isHideFromVendor($id_order_detail)
    {
        $HiddenStatusTypes = Configuration::get('MV_HIDE_FROM_VENDOR');

        $HiddenStatusTypesArray = explode(',', $HiddenStatusTypes);

        $currentStatus = self::getCurrentOrderDetailStatus($id_order_detail);
        return in_array($currentStatus, $HiddenStatusTypesArray);
    }


    /**
     * Helper method to check if a status type is hidden from vendors
     * Add this method to your multivendor class
     * 
     * @param int $id_order_line_status_type
     * @return bool
     */
    public static function isStatusTypeHiddenFromVendor($id_order_line_status_type)
    {
        $hiddenStatusTypes = Configuration::get('MV_HIDE_FROM_VENDOR');
        if (empty($hiddenStatusTypes)) {
            return false;
        }

        $hiddenArray = array_map('intval', explode(',', $hiddenStatusTypes));
        return in_array((int)$id_order_line_status_type, $hiddenArray);
    }


    public function getCurrentOrderDetailStatus($id_order_detail)
    {

        $query = new DbQuery();
        $query->select('id_order_line_status_type');
        $query->from('mv_order_line_status');
        $query->where('id_order_detail = ' . (int)$id_order_detail);
        $id_order_line_status_type = Db::getInstance()->getValue($query);
        return $id_order_line_status_type;
    }

    public static function isChangableStatusType($id_order_detail, $next_status_id)
    {
        $current_status_id  = self::getCurrentOrderDetailStatus($id_order_detail);
        if (!$current_status_id) {
            PrestaShopLogger::addLog(
                'Multivendor OrderHelper: No current status found for order detail ID ' . $id_order_detail,
                3,
                null,
                'OrderDetail',
                $id_order_detail
            );
            return false;
        }
        return  OrderLineStatusType::isAvailableStatus($current_status_id, $next_status_id);
    }

    public static function getHiddenStatusTypeString()
    {
        $hiddenConfig = Configuration::get('MV_HIDE_FROM_VENDOR');
        if (!empty($hiddenConfig)) {
            $hiddenIds = array_map('intval', explode(',', $hiddenConfig));
            $hiddenIdsString = implode(',', $hiddenIds);
        }
        return $hiddenIdsString;
    }

    /**
     * Get product image link by product ID or product attribute ID
     * 
     * @param int $id_product Product ID
     * @param int $id_product_attribute Product attribute ID (optional)
     * @param string $image_type Image type (home_default, large_default, etc.)
     * @param bool $force_https Force HTTPS
     * @return string|false Image URL or false if not found
     */
    public static function getProductImageLink($id_product, $id_product_attribute = null, $image_type = 'cart_default', $force_https = false)
    {
        if (!$id_product) {
            return false;
        }

        $context = Context::getContext();
        $link = $context->link;

        try {
            $product = new Product($id_product, false, $context->language->id);
            if (!Validate::isLoadedObject($product)) {
                return false;
            }

            // Check if combination has specific images
            if ($id_product_attribute) {
                $Product = new Product($id_product, false, $context->language->id);
                $images = $Product->getCombinationImages($context->language->id);
                if (!empty($images[$id_product_attribute])) {
                    $id_image = $images[$id_product_attribute][0]['id_image'];
                    return $link->getImageLink($product->link_rewrite, $id_product . '-' . $id_image, $image_type, null, null, null, $force_https);
                }
            }

            // Use cover image
            $cover = Product::getCover($id_product);
            if ($cover && isset($cover['id_image'])) {
                return $link->getImageLink($product->link_rewrite, $id_product . '-' . $cover['id_image'], $image_type, null, null, null, $force_https);
            }

            // Get any image as fallback
            $images = Image::getImages($context->language->id, $id_product);
            if (!empty($images)) {
                return $link->getImageLink($product->link_rewrite, $id_product . '-' . $images[0]['id_image'], $image_type, null, null, null, $force_https);
            }

            // Return placeholder image
            return $link->getImageLink('default', 'default', $image_type, null, null, null, $force_https);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('ProductImageHelper error: ' . $e->getMessage(), 3);
            return false;
        }
    }
}
