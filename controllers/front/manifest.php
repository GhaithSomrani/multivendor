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

            $this->generateSingleManifest($id_order_detail);
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

            $this->generateMultipleManifest($orderDetailIds);
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
     * Generate a single pickup manifest PDF
     */
    protected function generateSingleManifest($id_order_detail)
    {
        $pdfData = $this->getPdfData([$id_order_detail]);

        $pdf = new VendorManifestPDF($pdfData, 'manifest', $this->context->smarty);
        $pdf->render();
    }

    /**
     * Generate multiple pickup manifests in a single PDF
     */
    protected function generateMultipleManifest($orderDetailIds)
    {
        $manifestData = $this->getPdfData($orderDetailIds);
        

        $pdf = new VendorManifestPDF($manifestData, 'manifest', $this->context->smarty);
        $pdf->render();
    }

  

    /**
     * Prepare data for PDF generation
     */
    protected function getPdfData($orderDetailIds)
    {
        $vendor = new Vendor($this->vendor['id_vendor']);
        $supplier = new Supplier($vendor->id_supplier);
        $manifestData = [];

        foreach ($orderDetailIds as $id_order_detail) {
            $orderDetail = new OrderDetail($id_order_detail);
            $order = new Order($orderDetail->id_order);
            
            // Get customer address for delivery
            $address = new Address($order->id_address_delivery);
            $customer = new Customer($order->id_customer);

            $manifestData[] = [
                'vendor' => [
                    'id' => $vendor->id,

                ],
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name
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
                    'product_quantity' => $orderDetail->product_quantity,
                    'unit_price_tax_incl' => $orderDetail->unit_price_tax_incl,
                    'total_price_tax_incl' => $orderDetail->total_price_tax_incl
                ],
                'customer' => [
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,
                    'email' => $customer->email
                ],
                'delivery_address' => [
                    'firstname' => $address->firstname,
                    'lastname' => $address->lastname,
                    'address1' => $address->address1,
                    'address2' => $address->address2,
                    'city' => $address->city,
                    'postcode' => $address->postcode,
                    'phone' => $address->phone,
                    'phone_mobile' => $address->phone_mobile
                ],
                'pickup_id' => 'PU-' . $order->reference . '-' . $id_order_detail,
                'date' => date('Y-m-d'),
                'time' => date('H:i'),
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