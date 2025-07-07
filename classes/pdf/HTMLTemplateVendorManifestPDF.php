<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class HTMLTemplateVendorManifestPDF extends HTMLTemplate
{
    public $data;
    public $smarty;
    protected $context;

    public function __construct($data, Smarty $smarty)
    {
        try {
            $this->data = $data;
            $this->smarty = $smarty;
            $this->context = Context::getContext();
        } catch (Exception $e) {
            error_log('Error in HTMLTemplateVendorManifestPDF constructor: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getContent()
    {
        try {
            $manifestData = $this->getPdfData($this->data['orderDetailIds'], $this->data['vendor']);
            $shop_address = $this->getShopAddress();

            // Handle export type for different PDF headers
            $export_type = isset($this->data['export_type']) ? $this->data['export_type'] : 'pickup';
            $pdf_title = ($export_type === 'retour') ? 'BON DE RETOUR' : 'BON DE RAMASSAGE';
            $pdf_subtitle = ($export_type === 'retour') ? 'Document de Retour Multi-Articles' : 'Document de Collecte Multi-Articles';

            $this->smarty->assign([
                'manifests'    => $manifestData['manifests'],
                'current_date' => date('Y-m-d'),
                'current_time' => date('H:i:s'),
                'shop_address' => $shop_address,
                'shop_phone'   => Configuration::get('PS_SHOP_PHONE'),
                'shop_fax'     => Configuration::get('PS_SHOP_FAX'),
                'shop_details' => Configuration::get('PS_SHOP_DETAILS'),
                'free_text'    => Configuration::get('PS_SHOP_FREE_TEXT'),
                'export_type'  => $export_type,
                'pdf_title'    => $pdf_title,
                'pdf_subtitle' => $pdf_subtitle,
            ]);

            $template_path = _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/VendorManifestPDF.tpl';
            $content = $this->smarty->fetch($template_path);

            return $content;
        } catch (Exception $e) {
            error_log('Error in getContent(): ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    protected function getPdfData($orderDetailIds, $vendor)
    {
        try {

            $manifestData = [];
            $vendorObj = new Vendor($vendor['id_vendor']);
            $supplierAddress = VendorHelper::getSupplierAddressByVendor($vendor['id_vendor']);

            foreach ($orderDetailIds as $id_order_detail) {


                $orderDetail = new OrderDetail($id_order_detail);
                $order = new Order($orderDetail->id_order);
                $lineStatus = null;
                $vendorOrderDetail = (float)OrderHelper::getVendorAmountByOrderDetail($id_order_detail);
                $currency = new Currency($order->id_currency);

                $manifestItem = [
                    'vendor' => [
                        'id' => $vendorObj->id,
                        'name' => $vendorObj->shop_name,
                    ],
                    'supplier_address' => $supplierAddress,
                    'order' => [
                        'id' => $order->id,
                        'reference' => $order->reference,
                        'date_add' => $order->date_add,
                        'total_paid' => $order->total_paid,
                        'currency' => $currency
                    ],
                    'orderDetail' => [
                        'id' => $orderDetail->id,
                        'product_name' => $orderDetail->product_name,
                        'product_reference' => $orderDetail->product_reference ?: 'SKU-' . $orderDetail->id,
                        'product_quantity' => (int)$orderDetail->product_quantity,
                        'unit_price_tax_incl' => (float)$orderDetail->unit_price_tax_incl,
                        'total_price_tax_incl' => (float)$orderDetail->total_price_tax_incl,
                        'product_weight' => (float)($orderDetail->product_weight ?: 0.5),
                        'product_mpn' => $orderDetail->product_mpn ?: '',
                        'barcode' => $this->generateBarcode($orderDetail->product_mpn),
                    ],
                    'vendor_amount' => $vendorOrderDetail,
                    'pickup_id' => 'PU-' . $order->reference . '-' . $id_order_detail,
                    'date' => date('Y-m-d'),
                    'time' => date('H:i'),
                    'line_status' => $lineStatus ? $lineStatus['status'] : 'Pending',
                    'shop_address' => [
                        'address1' => Configuration::get('PS_SHOP_ADDR1') ?: '',
                        'address2' => Configuration::get('PS_SHOP_ADDR2') ?: '',
                        'city' => Configuration::get('PS_SHOP_CITY') ?: '',
                        'postcode' => Configuration::get('PS_SHOP_CODE') ?: '',
                        'country' => Configuration::get('PS_SHOP_COUNTRY') ?: '',
                        'phone' => Configuration::get('PS_SHOP_PHONE') ?: ''
                    ]
                ];

                $manifestData[] = $manifestItem;
            }
            return [
                'manifests' => $manifestData,
                'filename' => 'Pickup_Manifest_' . date('YmdHis') . '.pdf',
            ];
        } catch (Exception $e) {
            error_log('Error in getPdfData(): ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    protected function generateBarcode($mpn)
    {
        try {
            if (empty($mpn)) {
                return '';
            }
            $barcode = new TCPDFBarcode($mpn, 'C128');
            return $barcode->getBarcodeHTML(1, 15, 'black');
        } catch (Exception $e) {
            error_log('Error generating barcode: ' . $e->getMessage());
            return '';
        }
    }

    protected function getShopAddress()
    {
        try {
            $shop_address = Configuration::get('PS_SHOP_NAME') . "\n" . Configuration::get('PS_SHOP_ADDR1');
            if (Configuration::get('PS_SHOP_ADDR2')) {
                $shop_address .= ' ' . Configuration::get('PS_SHOP_ADDR2');
            }
            $shop_address .= "\n" . Configuration::get('PS_SHOP_CODE') . ' ' . Configuration::get('PS_SHOP_CITY');
            if (Configuration::get('PS_SHOP_COUNTRY')) {
                $shop_address .= "\n" . Configuration::get('PS_SHOP_COUNTRY');
            }
            return $shop_address;
        } catch (Exception $e) {
            error_log('Error in getShopAddress(): ' . $e->getMessage());
            return 'Address not available';
        }
    }

    public function getFilename()
    {
        return $this->data['filename'] ?? 'manifest.pdf';
    }

    public function getBulkFilename()
    {
        return 'vendor_manifests.pdf';
    }

    public function getHeader()
    {
        try {
            $this->assignCommonHeaderData();
            return parent::getHeader();
        } catch (Exception $e) {
            error_log('Error in getHeader(): ' . $e->getMessage());
            return '';
        }
    }
}
