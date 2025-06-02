<?php

/**
 * AJAX controller for multivendor module - COMPLETE FIXED VERSION
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

        // Log the action for debugging
        error_log('MultivendorAjax: Action received: ' . $action);
        error_log('MultivendorAjax: All parameters: ' . print_r($_POST, true));

        switch ($action) {


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
                error_log('MultivendorAjax: Unknown action: ' . $action);
                die(json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]));
        }
    }

    

    /**
     * Process vendor status update - FIXED VERSION
     */
    private function processUpdateVendorStatus()
    {
        try {
            error_log('MultivendorAjax: processUpdateVendorStatus called');

            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_status_type = (int)Tools::getValue('id_status_type'); // NOT 'status'
            $comment = Tools::getValue('comment', '');
            $id_customer = $this->context->customer->id;

            error_log('MultivendorAjax: Vendor status update parameters: ' . print_r([
                'id_order_detail' => $id_order_detail,
                'id_status_type' => $id_status_type,
                'comment' => $comment,
                'id_customer' => $id_customer
            ], true));

            // Validate inputs
            if (!$id_order_detail || !$id_status_type) {
                die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
            }

            $result = VendorHelper::updateVendorOrderLineStatus($id_customer, $id_order_detail, $id_status_type, $comment);

            error_log('MultivendorAjax: Vendor status update result: ' . print_r($result, true));

            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processUpdateVendorStatus: ' . $e->getMessage());
            error_log('MultivendorAjax: Stack trace: ' . $e->getTraceAsString());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Process get status history
     */
    private function processGetStatusHistory()
    {
        try {
            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_customer = $this->context->customer->id;

            if (!$id_order_detail) {
                die(json_encode(['success' => false, 'message' => 'Missing order detail ID']));
            }

            $result = VendorHelper::getOrderLineStatusHistory($id_customer, $id_order_detail);
            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processGetStatusHistory: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Process bulk update vendor status
     */
    private function processBulkUpdateVendorStatus()
    {
        try {
            $order_detail_ids = Tools::getValue('order_detail_ids', []);
            $id_status_type = (int)Tools::getValue('id_status_type'); // Changed from 'status'
            $comment = Tools::getValue('comment', 'Bulk status update');
            $id_customer = $this->context->customer->id;

            error_log('MultivendorAjax: Bulk update parameters: ' . print_r([
                'order_detail_ids' => $order_detail_ids,
                'id_status_type' => $id_status_type,
                'comment' => $comment,
                'id_customer' => $id_customer
            ], true));

            if (empty($order_detail_ids) || !$id_status_type) {
                die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
            }

            $result = VendorHelper::bulkUpdateVendorOrderLineStatus($id_customer, $order_detail_ids, $id_status_type, $comment);

            error_log('MultivendorAjax: Bulk update result: ' . print_r($result, true));

            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processBulkUpdateVendorStatus: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Process export orders to CSV
     */
    private function processExportOrdersCSV()
    {
        try {
            $id_customer = $this->context->customer->id;

            $result = VendorHelper::exportVendorOrdersToCSV($id_customer, $this->module);

            // If it's not a success response, display error
            if (is_array($result) && isset($result['success']) && !$result['success']) {
                header('Content-Type: text/plain');
                die('Error: ' . $result['message']);
            }

            // If it reached here, the CSV has been sent
            exit;
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processExportOrdersCSV: ' . $e->getMessage());
            header('Content-Type: text/plain');
            die('Error: ' . $e->getMessage());
        }
    }

    /**
     * Process get add commission status
     */
    private function processGetAddCommissionStatus()
    {
        try {
            $result = VendorHelper::getAddCommissionStatus();
            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processGetAddCommissionStatus: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Generate manifest URL
     */
    private function processGetManifestUrl()
    {
        try {
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
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processGetManifestUrl: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }
}
