<?php

/**
 * Unified Pickup Manifest Generation Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorManifestModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;
    public $vendor;

    public function init()
    {
        parent::init();

        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$vendor) {
            Tools::redirect('index.php?controller=my-account');
        }

        $this->vendor = $vendor;
    }

    public function initContent()
    {
        parent::initContent();

        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $details = Tools::getValue('details', '');

        if (!empty($id_order_detail)) {
            if (!$this->verifyOrderDetailOwnership($id_order_detail)) {
                die('Access denied for order detail: ' . $id_order_detail);
            }

            $this->generateManifest([$id_order_detail]);
        } elseif (!empty($details)) {
            $orderDetailIds = explode(',', $details);
            $orderDetailIds = array_map('intval', $orderDetailIds);
            $orderDetailIds = array_filter($orderDetailIds);

            if (empty($orderDetailIds)) {
                die('Invalid order details');
            }

            foreach ($orderDetailIds as $detailId) {
                if (!$this->verifyOrderDetailOwnership($detailId)) {
                    die('Access denied for order detail: ' . $detailId);
                }
            }

            $this->generateManifest($orderDetailIds);
        } else {
            die('No order details specified');
        }
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
     * Generate manifest PDF
     */
    protected function generateManifest($orderDetailIds)
    {
        $manifestData = $this->getPdfData($orderDetailIds);

        try {
            $pdf = new VendorManifestPDF($manifestData, 'manifest', $this->context->smarty);
            $pdf->render();
        } catch (Exception $e) {
            die('Error generating manifest: ' . $e->getMessage());
        }
    }
    /**
     * Generate barcode for MPN
     * 
     * @param string $mpn MPN code
     * @return string Base64 encoded barcode image
     */

    // Islem look here 
    protected function generateBarcode($mpn)
    {
        if (empty($mpn)) {
            return '';
        }
        $barcode = new TCPDFBarcode($mpn, 'C128');
        $barcodeImage = $barcode->getBarcodeHTML(2, 40, 'black');
        return  $barcodeImage;
    }
    /**
     * Prepare data for PDF generation
     */
    protected function getPdfData($orderDetailIds)
    {
        $vendor = new Vendor($this->vendor['id_vendor']);
        $manifestData = [];
        foreach ($orderDetailIds as $id_order_detail) {
            $orderDetail = new OrderDetail($id_order_detail);
            $order = new Order($orderDetail->id_order);
            $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $this->vendor['id_vendor']);
            $currentStatus = $lineStatus ? $lineStatus['status'] : 'Pending';
            $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $this->vendor['id_vendor']);
            $vendorAmount = $vendorOrderDetail ? $vendorOrderDetail['vendor_amount'] : 0;
            // Islem look here 
            $barcode = $this->generateBarcode($orderDetail->product_mpn);
            $manifestData[] = [
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->shop_name,
                ],

                'order' => [
                    'id' => $order->id,
                    'reference' => $order->reference,
                    'date_add' => $order->date_add,
                    'total_paid' => $order->total_paid,
                    'currency' => new Currency($order->id_currency)
                ],
                'orderDetail' => [
                    'id' => $orderDetail->id,
                    'product_name' => $orderDetail->product_name,
                    'product_reference' => $orderDetail->product_reference,
                    'product_quantity' => $orderDetail->product_quantity,
                    'unit_price_tax_incl' => $orderDetail->unit_price_tax_incl,
                    'total_price_tax_incl' => $orderDetail->total_price_tax_incl,
                    'product_weight' => $orderDetail->product_weight ?: 0.5,
                    'product_mpn' => $orderDetail->product_mpn,
                    'barcode' => $barcode,

                ],
                'vendor_amount' => $vendorAmount,
                'pickup_id' => 'PU-' . $order->reference . '-' . $id_order_detail,
                'date' => date('Y-m-d'),
                'time' => date('H:i'),
                'line_status' => $currentStatus,
                'shop_address' => [
                    'address1' => Configuration::get('PS_SHOP_ADDR1'),
                    'address2' => Configuration::get('PS_SHOP_ADDR2'),
                    'city' => Configuration::get('PS_SHOP_CITY'),
                    'postcode' => Configuration::get('PS_SHOP_CODE'),
                    'country' => Configuration::get('PS_SHOP_COUNTRY'),
                    'phone' => Configuration::get('PS_SHOP_PHONE')
                ]
            ];
        }

        return [
            'manifests' => $manifestData,
            'filename' => 'Pickup_Manifest_' . date('YmdHis') . '.pdf'
        ];
    }
}
