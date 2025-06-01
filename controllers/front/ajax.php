<?php

/**
 * AJAX controller for multivendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Load VendorHelper class
require_once(_PS_MODULE_DIR_ . 'multivendor/classes/VendorHelper.php');

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
     * Process get order line statuses for admin
     */
    private function processGetOrderLineStatusesForAdmin()
    {
        $id_order = (int)Tools::getValue('id_order');
        $result = VendorHelper::getOrderLineStatusesForAdmin($id_order);
        die(json_encode($result));
    }

    /**
     * Process update order line status
     */
    private function processUpdateOrderLineStatus()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_vendor = (int)Tools::getValue('id_vendor');
        $new_status = Tools::getValue('status');
        $employee_id = (isset($this->context->employee) && $this->context->employee->id)
            ? $this->context->employee->id
            : 1; // Default to admin ID 1 if no employee context

        $result = VendorHelper::updateOrderLineStatusAsAdmin($id_order_detail, $id_vendor, $new_status, $employee_id);
        die(json_encode($result));
    }

    /**
     * Process vendor status update
     */
    private function processUpdateVendorStatus()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_status_type = (int)Tools::getValue('id_status_type');
        $comment = Tools::getValue('comment', '');
        $id_customer = $this->context->customer->id;

        $result = VendorHelper::updateVendorOrderLineStatus($id_customer, $id_order_detail, $id_status_type, $comment);
        die(json_encode($result));
    }

    /**
     * Process get status history
     */
    private function processGetStatusHistory()
    {
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $id_customer = $this->context->customer->id;

        $result = VendorHelper::getOrderLineStatusHistory($id_customer, $id_order_detail);
        die(json_encode($result));
    }

    /**
     * Process bulk update vendor status
     */
    private function processBulkUpdateVendorStatus()
    {
        $order_detail_ids = Tools::getValue('order_detail_ids', []);
        $new_status = (int)Tools::getValue('status');
        $comment = Tools::getValue('comment', 'Bulk status update');
        $id_customer = $this->context->customer->id;

        $result = VendorHelper::bulkUpdateVendorOrderLineStatus($id_customer, $order_detail_ids, $new_status, $comment);
        die(json_encode($result));
    }

    /**
     * Process export orders to CSV
     */
    private function processExportOrdersCSV()
    {
        $id_customer = $this->context->customer->id;

        $result = VendorHelper::exportVendorOrdersToCSV($id_customer, $this->module);

        // If it's not a success response, display error
        if (is_array($result) && isset($result['success']) && !$result['success']) {
            header('Content-Type: text/plain');
            die('Error: ' . $result['message']);
        }

        // If it reached here, the CSV has been sent
        exit;
    }

    /**
     * Process get add commission status
     */
    private function processGetAddCommissionStatus()
    {
        $result = VendorHelper::getAddCommissionStatus();
        die(json_encode($result));
    }

    /**
     * Generate manifest URL
     */
    private function processGetManifestUrl()
    {
        $order_detail_ids = Tools::getValue('order_detail_ids', []);

        if (empty($order_detail_ids) || !is_array($order_detail_ids)) {
            die(json_encode(['success' => false, 'message' => 'No order details provided']));
        }

        // Verify that all order details belong to this vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            die(json_encode(['success' => false, 'message' => 'Not authorized']));
        }

        // Verify ownership of all order details
        foreach ($order_detail_ids as $id_order_detail) {
            if (!VendorHelper::verifyOrderDetailOwnership($id_order_detail, $id_customer)) {
                die(json_encode(['success' => false, 'message' => 'Not authorized for order detail: ' . $id_order_detail]));
            }
        }

        // Generate the correct URL
        $url = $this->context->link->getModuleLink(
            'multivendor',
            'manifest',
            ['details' => implode(',', $order_detail_ids)]
        );

        die(json_encode([
            'success' => true,
            'url' => $url
        ]));
    }
}
