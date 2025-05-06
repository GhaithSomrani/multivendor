<?php

/**
 * Override AdminOrdersController
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('AdminOrdersController', false)) {
    class AdminOrdersController extends AdminController
    {
        // Define the methods you want to override
        public function ajaxProcessGetOrderLineStatusesForAdmin()
        {
            $id_order = (int)Tools::getValue('id_order');
            $statusData = [];

            // Get all vendor order details for this order
            $vendorOrderDetails = VendorOrderDetail::getByOrderId($id_order);

            foreach ($vendorOrderDetails as $detail) {
                $id_order_detail = $detail['id_order_detail'];
                $id_vendor = $detail['id_vendor'];

                $vendor = new Vendor($id_vendor);
                $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $id_vendor);

                $statusData[$id_order_detail] = [
                    'id_vendor' => $id_vendor,
                    'vendor_name' => $vendor->shop_name,
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
        }

        public function ajaxProcessUpdateOrderLineStatus()
        {
            // Get parameters
            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_vendor = (int)Tools::getValue('id_vendor');
            $new_status = Tools::getValue('status');

            // Update the status
            $success = OrderLineStatus::updateStatus(
                $id_order_detail,
                $id_vendor,
                $new_status,
                Context::getContext()->employee->id,
                null, // No comment
                true // is admin
            );

            die(json_encode(['success' => $success]));
        }
    }
}
