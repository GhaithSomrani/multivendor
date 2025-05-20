<?php

/**
 * AJAX controller for multivendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorAjaxModuleFrontController extends ModuleFrontController
{
    // Disable the standard page rendering
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        parent::initContent();

        $action = Tools::getValue('action');

        switch ($action) {
            case 'getOrderLineStatusesForAdmin':
                $this->processGetOrderLineStatusesForAdmin();
                break;

            case 'updateOrderLineStatus':
                $this->processUpdateOrderLineStatus();
                break;
            case 'updateVendorStatus':
                $this->processUpdateVendorStatus();
                break;
            case 'getStatusHistory':
                $this->processGetStatusHistory();
                break;
            case 'bulkUpdateVendorStatus':
                $this->processBulkUpdateVendorStatus();
                break;
            case 'exportOrdersCSV':
                $this->processExportOrdersCSV();
                break;
            case 'getAddCommissionStatus':
                $this->processGetAddCommissionStatus();
                break;
            default:
                die(json_encode(['success' => false, 'message' => 'Unknown action']));
        }
    }

    /**
     * Get order line statuses for admin
     */
    private function processGetOrderLineStatusesForAdmin()
    {
        try {


            $id_order = (int)Tools::getValue('id_order');
            $statusData = [];

            $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

            if (!empty($vendorOrderDetails)) {
                foreach ($vendorOrderDetails as $detail) {
                    $id_order_detail = $detail['id_order_detail'];
                    $id_vendor = $detail['id_vendor'];
                    $vendor = new Vendor($id_vendor);
                    $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

                    $statusData[$id_order_detail] = [
                        'id_vendor' => $id_vendor,
                        'vendor_name' => Validate::isLoadedObject($vendor) ? $vendor->shop_name : 'Unknown vendor',
                        'status' => $lineStatus ? $lineStatus['status'] : 'Pending',
                        'status_date' => $lineStatus ? $lineStatus['date_upd'] : null,
                        'is_vendor_product' => true
                    ];
                }
            }

            // Get all order details for this order to handle non-vendor products
            $allOrderDetails = OrderDetail::getList($id_order);

            // Process all order details to include non-vendor products
            foreach ($allOrderDetails as $orderDetail) {
                $id_order_detail = $orderDetail['id_order_detail'];

                // Skip if this order detail is already handled by a vendor
                if (isset($statusData[$id_order_detail])) {
                    continue;
                }

                // This is a non-vendor product, add it with appropriate indication
                $statusData[$id_order_detail] = [
                    'id_vendor' => 0,
                    'vendor_name' => null,
                    'status' => 'Not a vendor product',
                    'status_date' => null,
                    'is_vendor_product' => false
                ];
            }

            // Get all available statuses that admin can set
            $availableStatuses = OrderLineStatusType::getAllActiveStatusTypes(false, true);

            die(json_encode([
                'success' => true,
                'statusData' => $statusData,
                'availableStatuses' => $availableStatuses
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Update order line status
     */
    private function processUpdateOrderLineStatus()
    {
        try {
            // Use token-based security check instead of employee context
            // $token = Tools::getValue('token');

            // if (empty($token) || $token !== Tools::getAdminToken('AdminOrders')) {
            //     die(json_encode(['success' => false, 'message' => 'Access denied: Invalid token']));
            // }

            // Get parameters
            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_vendor = (int)Tools::getValue('id_vendor');
            $new_status = Tools::getValue('status');

            // We need a way to identify which employee is making the change
            // Since we don't have direct access to the employee context
            // For now, we'll use a placeholder employee ID (1 = usually the superadmin)
            $employee_id = 1;

            // Update the status
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $employee_id,
                null, // No comment
                true // is admin
            );

            die(json_encode(['success' => $success]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }


    /**
     * Process vendor status update
     */
    private function processUpdateVendorStatus()
    {
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        $id_vendor = $vendor['id_vendor'];
        $id_supplier = $vendor['id_supplier'];
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $new_status = Tools::getValue('status');
        $comment = Tools::getValue('comment');

        try {
            // Verify authorization
            $query = new DbQuery();
            $query->select('p.id_supplier, od.id_order');
            $query->from('order_detail', 'od');
            $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
            $query->where('od.id_order_detail = ' . (int)$id_order_detail);

            $result = Db::getInstance()->getRow($query);

            if (!$result || (int)$result['id_supplier'] !== (int)$id_supplier) {
                throw new Exception('Not authorized for this product');
            }

            $id_order = $result['id_order'];

            // Update the status
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $this->context->customer->id,
                $comment,
                false // not admin
            );

            if (!$success) {
                throw new Exception('Failed to update status');
            }

            // Get updated status data
            $statusData = $this->getOrderLineStatusData($id_order_detail, $id_vendor);

            die(json_encode([
                'success' => true,
                'statusData' => $statusData,
                'message' => 'Status updated successfully'
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
    /**
     * Get status history for an order line
     */
    private function processGetStatusHistory()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');

        // Check authorization
        $id_customer = $this->context->customer->id;
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        try {
            $history = OrderLineStatusLog::getStatusHistory($id_order_detail);

            // Format the history data
            $formattedHistory = [];
            foreach ($history as $log) {
                $formattedHistory[] = [
                    'date' => date('Y-m-d H:i:s', strtotime($log['date_add'])),
                    'old_status' => $log['old_status'] ?: 'Initial',
                    'new_status' => $log['new_status'],
                    'comment' => $log['comment'],
                    'changed_by' => $log['changed_by_firstname'] . ' ' . $log['changed_by_lastname']
                ];
            }

            die(json_encode([
                'success' => true,
                'history' => $formattedHistory
            ]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Get order line status data
     */
    private function getOrderLineStatusData($id_order_detail, $id_vendor)
    {
        $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

        return [
            'status' => $lineStatus ? $lineStatus['status'] : 'Pending',
            'last_update' => $lineStatus ? date('Y-m-d H:i:s', strtotime($lineStatus['date_upd'])) : null,
            'comment' => $lineStatus ? $lineStatus['comment'] : null
        ];
    }

    private function processBulkUpdateVendorStatus()
    {
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        $id_vendor = $vendor['id_vendor'];
        $id_supplier = $vendor['id_supplier'];
        $order_detail_ids = Tools::getValue('order_detail_ids', []);
        $new_status = Tools::getValue('status');
        $comment = Tools::getValue('comment', 'Bulk status update');

        if (empty($order_detail_ids) || !is_array($order_detail_ids) || empty($new_status)) {
            die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
        }

        $success_count = 0;
        $error_count = 0;
        $results = [];

        foreach ($order_detail_ids as $id_order_detail) {
            // Verify this order detail belongs to this vendor's supplier
            $query = new DbQuery();
            $query->select('p.id_supplier, od.id_order');
            $query->from('order_detail', 'od');
            $query->innerJoin('product', 'p', 'p.id_product = od.product_id');
            $query->where('od.id_order_detail = ' . (int)$id_order_detail);

            $result = Db::getInstance()->getRow($query);

            if (!$result || (int)$result['id_supplier'] !== (int)$id_supplier) {
                $error_count++;
                $results[$id_order_detail] = false;
                continue;
            }

            // Update the status
            $update_result = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                $this->context->customer->id,
                $comment,
                false // not admin
            );

            if ($update_result) {
                $success_count++;
                $results[$id_order_detail] = true;
            } else {
                $error_count++;
                $results[$id_order_detail] = false;
            }
        }

        die(json_encode([
            'success' => $success_count > 0,
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'message' => sprintf(
                '%d orders updated successfully, %d failed',
                $success_count,
                $error_count
            )
        ]));
    }



    /**
     * Process export orders to CSV
     * This function directly outputs a CSV file for download
     */
    private function processExportOrdersCSV()
    {
        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if (!$vendor) {
            // For direct download, we can't return JSON error
            // Instead, output a simple error message
            header('Content-Type: text/plain');
            die('Error: Not authorized');
        }

        $id_vendor = $vendor['id_vendor'];

        // Get all order lines for this vendor with complete data
        $query = new DbQuery();
        $query->select('
        od.id_order_detail,
        od.product_name,
        od.product_reference,
        od.product_quantity,
        od.unit_price_tax_incl,
        od.total_price_tax_incl,
        o.reference as order_reference,
        o.date_add as order_date,
        o.id_order,
        vod.commission_amount,
        vod.vendor_amount,
        COALESCE(ols.status, "Pending") as line_status
    ');
        $query->from('vendor_order_detail', 'vod');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vod.id_order_detail');
        $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
        $query->leftJoin('order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->orderBy('o.date_add DESC');

        $orderLines = Db::getInstance()->executeS($query);

        if (!$orderLines || empty($orderLines)) {
            // For direct download, output a simple error message
            header('Content-Type: text/plain');
            die('Error: No order data found');
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=vendor_orders_export_' . date('Y-m-d') . '.csv');
        // Disable caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Add CSV headers
        fputcsv($output, [
            $this->module->l('Order Reference'),
            $this->module->l('Product Name'),
            $this->module->l('SKU'),
            $this->module->l('Quantity'),
            $this->module->l('Vendor Amount'),
            $this->module->l('Status'),
            $this->module->l('Order Date')
        ]);

        // Add data rows
        foreach ($orderLines as $line) {
            fputcsv($output, [
                $line['order_reference'],
                $line['product_name'],
                $line['product_reference'],
                $line['product_quantity'],
                $line['vendor_amount'],
                $line['line_status'],
                date('Y-m-d H:i:s', strtotime($line['order_date']))
            ]);
        }

        fclose($output);
        exit; // Important: stop execution after sending the file
    }
    /**
     * Process get add commission status
     * Returns the first status that has commission_action = 'add' and is vendor allowed
     */
    private function processGetAddCommissionStatus()
    {
        try {
            $query = new DbQuery();
            $query->select('name, color, position');
            $query->from('order_line_status_type');
            $query->where('commission_action = "add"');
            $query->where('is_vendor_allowed = 1');
            $query->where('active = 1');
            $query->orderBy('position ASC');

            $status = Db::getInstance()->getRow($query);

            if ($status) {
                die(json_encode([
                    'success' => true,
                    'status' => $status
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'No suitable status found'
                ]));
            }
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }
}
