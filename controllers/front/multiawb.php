<?php

/**
 * Multi-Pickup Manifest Generation Controller for transporters
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorMultiawbModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;
    public $vendor;
    public function init()
    {
        parent::init();

        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            Tools::redirect('index.php?controller=my-account');
        }

        $this->vendor = $vendor;
    }

    public function initContent()
    {
        $orderDetails = Tools::getValue('details');
        
        if (!$orderDetails) {
            die('No order details provided');
        }

        // Convert comma-separated string to array
        $orderDetailIds = explode(',', $orderDetails);
        $orderDetailIds = array_map('intval', $orderDetailIds);
        $orderDetailIds = array_filter($orderDetailIds);

        if (empty($orderDetailIds)) {
            die('Invalid order details');
        }

        // Verify all order details belong to the vendor
        foreach ($orderDetailIds as $id_order_detail) {
            if (!$this->verifyOrderDetailOwnership($id_order_detail)) {
                die('Access denied for order detail: ' . $id_order_detail);
            }
        }

        // Generate PDF with multiple pickup manifests
        $this->generateMultiPickupPdf($orderDetailIds);
    }

    /**
     * Verify that the order detail belongs to the vendor
     */
    protected function verifyOrderDetailOwnership($id_order_detail)
    {
        $query = new DbQuery();
        $query->select('vod.id_vendor');
        $query->from('vendor_order_detail', 'vod');
        $query->where('vod.id_order_detail = ' . (int)$id_order_detail);
        $query->where('vod.id_vendor = ' . (int)$this->vendor['id_vendor']);

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Generate PDF with multiple pickup manifests
     */
    protected function generateMultiPickupPdf($orderDetailIds)
    {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $vendor = new Vendor($this->vendor['id_vendor']);
        $pdf->SetCreator('MultiVendor Module');
        $pdf->SetAuthor($vendor->shop_name);
        $pdf->SetTitle('Pickup Manifests - Batch Print');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(10, 10, 10);

        // Process each order detail
        foreach ($orderDetailIds as $id_order_detail) {
            $this->addPickupPage($pdf, $id_order_detail);
        }

        // Close and output PDF document
        $pdf->Output('Pickup_Manifest_' . date('YmdHis') . '.pdf', 'D');
        exit;
    }

    /**
     * Add pickup manifest page to PDF
     */
    protected function addPickupPage($pdf, $id_order_detail)
    {
        // Get order detail information
        $orderDetail = new OrderDetail($id_order_detail);
        $order = new Order($orderDetail->id_order);
        
        // Get vendor information
        $vendor = new Vendor($this->vendor['id_vendor']);
        $supplier = new Supplier($vendor->id_supplier);
        
        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Pickup manifest content
        $html = '
        <style>
            .pickup-header { background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; }
            .pickup-title { font-size: 24px; font-weight: bold; text-align: center; }
            .pickup-section { margin-bottom: 15px; }
            .pickup-label { font-weight: bold; }
            .pickup-box { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
            .pickup-barcode { text-align: center; margin: 20px 0; }
            table { width: 100%; }
            td { padding: 5px; }
            .signature-box { border-top: 1px solid #000; width: 200px; margin: 0 auto; }
        </style>
        
        <div class="pickup-header">
            <div class="pickup-title">PICKUP MANIFEST</div>
            <div style="text-align: center;">Transporter Pickup Document</div>
        </div>
        
        <table>
            <tr>
                <td width="50%">
                    <div class="pickup-box">
                        <div class="pickup-label">PICKUP FROM:</div>
                        <strong>' . $vendor->shop_name . '</strong><br>
                        ' . Configuration::get('PS_SHOP_ADDR1') . '<br>
                        ' . Configuration::get('PS_SHOP_CITY') . ', ' . Configuration::get('PS_SHOP_CODE') . '<br>
                        ' . Configuration::get('PS_SHOP_COUNTRY') . '<br>
                        Phone: ' . Configuration::get('PS_SHOP_PHONE') . '<br>
                        Contact: ' . $supplier->name . '
                    </div>
                </td>
                <td width="50%">
                    <div class="pickup-box">
                        <div class="pickup-label">PICKUP DETAILS:</div>
                        <strong>Date:</strong> ' . date('Y-m-d') . '<br>
                        <strong>Time:</strong> ' . date('H:i') . '<br>
                        <strong>Order Reference:</strong> ' . $order->reference . '<br>
                        <strong>Pickup ID:</strong> PU-' . $order->reference . '-' . $id_order_detail . '
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="pickup-barcode">
            <div style="font-size: 20px; font-weight: bold;">PU-' . $order->reference . '-' . $id_order_detail . '</div>
        </div>
        
        <div class="pickup-box">
            <div class="pickup-label">ITEM DETAILS:</div>
            <table border="1" cellpadding="5">
                <tr style="background-color: #f0f0f0;">
                    <th width="15%"><strong>SKU</strong></th>
                    <th width="45%"><strong>Product Description</strong></th>
                    <th width="15%"><strong>Quantity</strong></th>
                    <th width="15%"><strong>Weight (kg)</strong></th>
                    <th width="10%"><strong>Pieces</strong></th>
                </tr>
                <tr>
                    <td>' . $orderDetail->product_reference . '</td>
                    <td>' . $orderDetail->product_name . '</td>
                    <td align="center">' . $orderDetail->product_quantity . '</td>
                    <td align="center">' . number_format($orderDetail->product_weight * $orderDetail->product_quantity, 2) . '</td>
                    <td align="center">' . $orderDetail->product_quantity . '</td>
                </tr>
            </table>
        </div>
        
        <div class="pickup-box">
            <div class="pickup-label">PACKAGING INSTRUCTIONS:</div>
            <p>Please ensure all items are properly packaged and labeled.<br>
            Fragile items must be marked accordingly.<br>
            All packages must have order reference: <strong>' . $order->reference . '</strong></p>
        </div>
        
        <div class="pickup-box">
            <div class="pickup-label">TRANSPORTER NOTES:</div>
            <div style="height: 50px; border: 1px solid #ccc; background-color: #f9f9f9;"></div>
        </div>
        
        <table style="margin-top: 30px;">
            <tr>
                <td width="33%" style="text-align: center;">
                    <div class="signature-box">
                        Vendor Signature & Date
                    </div>
                </td>
                <td width="33%" style="text-align: center;">
                    <div class="signature-box">
                        Transporter Signature & Date
                    </div>
                </td>
                <td width="33%" style="text-align: center;">
                    <div class="signature-box">
                        Pickup Time
                    </div>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; text-align: center; font-size: 9px; color: #666;">
            This document confirms the pickup of the above items by the transporter from the vendor location.<br>
            Generated on: ' . date('Y-m-d H:i:s') . '
        </div>
        ';

        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
    }
}