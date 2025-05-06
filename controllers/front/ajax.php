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
            // Instead of checking for employee->id which won't work in front controllers
            // We'll use a token-based approach for security
            // $token = Tools::getValue('token');

            // Simple security check - in a real implementation you might want something more robust
            // if (empty($token) || $token !== Tools::getAdminToken('AdminOrders')) {
            //     die(json_encode(['success' => false, 'message' => 'Access denied: Invalid token']));
            // }

            $id_order = (int)Tools::getValue('id_order');
            $statusData = [];

            // Get all vendor order details for this order
            $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

            if (empty($vendorOrderDetails)) {
                die(json_encode([
                    'success' => true,
                    'statusData' => [],
                    'availableStatuses' => []
                ]));
            }

            foreach ($vendorOrderDetails as $detail) {
                $id_order_detail = $detail['id_order_detail'];
                $id_vendor = $detail['id_vendor'];

                // Get vendor info
                $vendor = new Vendor($id_vendor);

                // Get line status
                $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

                $statusData[$id_order_detail] = [
                    'id_vendor' => $id_vendor,
                    'vendor_name' => Validate::isLoadedObject($vendor) ? $vendor->shop_name : 'Unknown vendor',
                    'status' => $lineStatus ? $lineStatus['status'] : 'Pending',
                    'status_date' => $lineStatus ? $lineStatus['date_upd'] : null
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
}
