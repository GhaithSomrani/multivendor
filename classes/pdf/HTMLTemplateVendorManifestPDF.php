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
        $this->data = $data;
        $this->smarty = $smarty;
        $this->context = Context::getContext();
    }

    public function getContent()
    {
        if (empty($this->data['vendor']['id_vendor'])) {
            $id_vendor = $this->data['vendor'];
        } else {
            $id_vendor = $this->data['vendor']['id_vendor'];
        }

        $id_address = isset($this->data['id_address']) ? $this->data['id_address'] : null;
        $manifestData = $this->getPdfData($this->data['orderDetailIds'], $id_vendor, $id_address);
        $shop_address = $this->getShopAddress();

        $export_type = isset($this->data['export_type']) ? $this->data['export_type'] : 'pickup';
        $pdf_title = ($export_type === 'retour') ? 'BON DE RETOUR' : 'BON DE RAMASSAGE';
        $pdf_subtitle = ($export_type === 'retour') ? 'Document de Retour Multi-Articles' : 'Document de Collecte Multi-Articles';
        $commissionRate = (float)VendorCommission::getCommissionRate($id_vendor) / 100;
        $totalQty = $this->getTotalQty($this->data['orderDetailIds']);
        $this->smarty->assign([
            'total_qty' => $totalQty,
            'maniefest_reference' => $this->data['maniefest_reference'] ?? '',
            'manifest_type' => $this->data['manifest_type'],
            'commissionRate' => $commissionRate,
            'manifests' => $manifestData['manifests'],
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s'),
            'shop_address' => $shop_address,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE'),
            'shop_fax' => Configuration::get('PS_SHOP_FAX'),
            'shop_details' => Configuration::get('PS_SHOP_DETAILS'),
            'free_text' => Configuration::get('PS_SHOP_FREE_TEXT'),
            'export_type' => $export_type,
            'pdf_title' => $pdf_title,
            'pdf_subtitle' => $pdf_subtitle,
        ]);

        $template_path = _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/VendorManifestPDF.tpl';

        return $this->smarty->fetch($template_path);
    }

    protected function getPdfData($orderDetailIds, $vendor, $id_address)
    {
        $manifestData = [];
        $vendorObj = null;
        $Address = null;

        if ($id_address) {
            $Address = new Address((int)$id_address);
        }

        if ($vendor) {
            $vendorObj = new Vendor($vendor);
        }

        foreach ($orderDetailIds as $id_order_detail) {
            $orderDetail = new OrderDetail($id_order_detail);
            $order = new Order($orderDetail->id_order);
            $vendorOrderDetail = (float)OrderHelper::getVendorAmountByOrderDetail($id_order_detail);
            $currency = new Currency($order->id_currency);

            $manifestItem = [
                'vendor' => [
                    'id' => $vendorObj->id ?? null,
                    'name' => $vendorObj->shop_name ?? '',
                ],
                'supplier_address' => $Address ? [
                    'name' => $vendorObj->shop_name,
                    'address1' => $Address->address1,
                    'address2' => $Address->address2,
                    'city' => $Address->city,
                    'postcode' => $Address->postcode,
                    'country' => $Address->country,
                    'phone' => $Address->phone,
                    'vat_number' => $Address->vat_number,
                    'dni' => $Address->dni
                ] : [],
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
                    'vendor_amount' => $vendorOrderDetail,
                    'barcode' =>  $this->generateBarCode($orderDetail->product_mpn ?? '')
                ],
                'pickup_id' => 'PU-' . $order->reference . '-' . $id_order_detail,
                'date' => date('Y-m-d'),
                'time' => date('H:i'),
                'line_status' => 'Pending',
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
    }


    //get the total qty of the order details
    private function getTotalQty($orderDetailIds)
    {
        $totalQty = 0;
        foreach ($orderDetailIds as $id_order_detail) {
            $orderDetail = new OrderDetail($id_order_detail);
            $totalQty += (int)$orderDetail->product_quantity;
        }
        return $totalQty;
    }
    private function generateBarCode($data)
    {
        if (empty($data)) {
            return '';
        }

        $barcodeobj = new TCPDFBarcode($data, 'C128');

        // Generate PNG raw data
        $pngData = $barcodeobj->getBarcodePngData(2, 40, [0, 0, 0]);

        // Encode to base64 for embedding in <img src="...">
        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    protected function getShopAddress()
    {
        $shop_address = Configuration::get('PS_SHOP_NAME') . "\n" . Configuration::get('PS_SHOP_ADDR1');
        if (Configuration::get('PS_SHOP_ADDR2')) {
            $shop_address .= ' ' . Configuration::get('PS_SHOP_ADDR2');
        }
        $shop_address .= "\n" . Configuration::get('PS_SHOP_CODE') . ' ' . Configuration::get('PS_SHOP_CITY');
        if (Configuration::get('PS_SHOP_COUNTRY')) {
            $shop_address .= "\n" . Configuration::get('PS_SHOP_COUNTRY');
        }
        return $shop_address;
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
        $this->assignCommonHeaderData();
        return parent::getHeader();
    }

    public function getFooter()
    {
        return '';
    }
}
