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
                $this->errors[] = $this->module->l('Access denied for order detail: ') . $id_order_detail;
                $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
                return;
            }

            $this->generateSingleManifest($id_order_detail);
        } elseif (!empty($details)) {
            $orderDetailIds = explode(',', $details);
            $orderDetailIds = array_map('intval', $orderDetailIds);
            $orderDetailIds = array_filter($orderDetailIds);

            if (empty($orderDetailIds)) {
                $this->errors[] = $this->module->l('Invalid order details provided');
                $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
                return;
            }

            foreach ($orderDetailIds as $detailId) {
                if (!$this->verifyOrderDetailOwnership($detailId)) {
                    $this->errors[] = $this->module->l('Access denied for order detail: ') . $detailId;
                    $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
                    return;
                }
            }

            $this->generateMultipleManifest($orderDetailIds);
        } else {
            $this->errors[] = $this->module->l('No order details specified');
            $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
        }
    }

    /**
     * Verify that the order detail belongs to the vendor
     *
     * @param int $id_order_detail Order detail ID to verify
     * @return bool True if vendor owns this order detail
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
     *
     * @param int $id_order_detail Order detail ID
     */
    protected function generateSingleManifest($id_order_detail)
    {
        try {
            $pdfData = $this->getPdfData([$id_order_detail]);

            
            $pdf = new VendorManifestPDF($pdfData, 'manifest', $this->context->smarty);
            $pdf->render('D');
        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Error generating manifest: ') . $e->getMessage();
            $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
        }
    }

    /**
     * Generate multiple pickup manifests in a single PDF
     *
     * @param array $orderDetailIds Array of order detail IDs
     */
    protected function generateMultipleManifest($orderDetailIds)
    {
        try {
            $pdfData = $this->getPdfData($orderDetailIds);
            
            
            $pdf = new VendorManifestPDF($pdfData, 'manifest', $this->context->smarty);
            $pdf->render('D'); 
        } catch (Exception $e) {
            $this->errors[] = $this->module->l('Error generating manifests: ') . $e->getMessage();
            $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
        }
    }

    /**
     * Prepare data for PDF generation
     *
     * @param array $orderDetailIds Array of order detail IDs
     * @return array Formatted data for PDF generation
     */
    protected function getPdfData($orderDetailIds)
    {
        $vendor = new Vendor($this->vendor['id_vendor']);
        $supplier = new Supplier($vendor->id_supplier);
        $manifestData = [];

        foreach ($orderDetailIds as $id_order_detail) {
            $orderDetail = new OrderDetail($id_order_detail);
            $order = new Order($orderDetail->id_order);
            $customer = new Customer($order->id_customer);
            $deliveryAddress = new Address($order->id_address_delivery);
            $country = new Country($deliveryAddress->id_country);
            
            $product = new Product($orderDetail->product_id);
            
            $lineStatus = OrderLineStatus::getByOrderDetailAndVendor($id_order_detail, $this->vendor['id_vendor']);
            
            $vendorOrderDetail = VendorOrderDetail::getByOrderDetailAndVendor($id_order_detail, $this->vendor['id_vendor']);

            $manifestData[] = [
                'vendor' => [
                    'id_vendor' => $vendor->id,
                    'shop_name' => $vendor->shop_name,
                    'description' => $vendor->description,
                    'logo' => $vendor->logo,
                    'banner' => $vendor->banner
                ],
                'supplier' => [
                    'id_supplier' => $supplier->id,
                    'name' => $supplier->name,
                    'description' => $supplier->description,
                    'active' => $supplier->active
                ],
                'order' => [
                    'id_order' => $order->id,
                    'reference' => $order->reference,
                    'date_add' => $order->date_add,
                    'total_paid' => $order->total_paid,
                    'current_state' => $order->current_state
                ],
                'orderDetail' => [
                    'id_order_detail' => $orderDetail->id_order_detail,
                    'product_name' => $orderDetail->product_name,
                    'product_reference' => $orderDetail->product_reference,
                    'product_quantity' => $orderDetail->product_quantity,
                    'product_weight' => $orderDetail->product_weight,
                    'unit_price_tax_incl' => $orderDetail->unit_price_tax_incl,
                    'total_price_tax_incl' => $orderDetail->total_price_tax_incl
                ],
                'customer' => [
                    'id_customer' => $customer->id,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,
                    'email' => $customer->email
                ],
                'delivery_address' => [
                    'firstname' => $deliveryAddress->firstname,
                    'lastname' => $deliveryAddress->lastname,
                    'company' => $deliveryAddress->company,
                    'address1' => $deliveryAddress->address1,
                    'address2' => $deliveryAddress->address2,
                    'postcode' => $deliveryAddress->postcode,
                    'city' => $deliveryAddress->city,
                    'phone' => $deliveryAddress->phone,
                    'phone_mobile' => $deliveryAddress->phone_mobile,
                    'country' => $country->name[Context::getContext()->language->id]
                ],
                'product' => [
                    'id_product' => $product->id,
                    'name' => $product->name[Context::getContext()->language->id],
                    'reference' => $product->reference,
                    'weight' => $product->weight,
                    'width' => $product->width,
                    'height' => $product->height,
                    'depth' => $product->depth
                ],
                'line_status' => $lineStatus ? $lineStatus['status'] : 'Pending',
                'commission_info' => $vendorOrderDetail ? [
                    'commission_rate' => $vendorOrderDetail['commission_rate'],
                    'commission_amount' => $vendorOrderDetail['commission_amount'],
                    'vendor_amount' => $vendorOrderDetail['vendor_amount']
                ] : null,
                'pickup_id' => 'PU-' . $order->reference . '-' . $id_order_detail,
                'date' => date('Y-m-d'),
                'time' => date('H:i'),
                'warehouse_address' => [
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
            'filename' => count($orderDetailIds) > 1 
                ? 'pickup_manifests_' . date('YmdHis') . '.pdf'
                : 'pickup_manifest_' . $orderDetailIds[0] . '_' . date('YmdHis') . '.pdf',
            'vendor_info' => $vendor,
            'total_manifests' => count($manifestData)
        ];
    }

    /**
     * Redirect with notification message
     *
     * @param string $url URL to redirect to
     */
    protected function redirectWithNotification($url)
    {
        if (!empty($this->errors)) {
            $errorMessage = implode(', ', $this->errors);
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'error=' . urlencode($errorMessage);
        }
        
        Tools::redirect($url);
    }

    /**
     * Display error page if something goes wrong
     */
    public function displayError()
    {
        $this->context->smarty->assign([
            'errors' => $this->errors,
            'vendor_orders_url' => Context::getContext()->link->getModuleLink('multivendor', 'orders')
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/error.tpl');
    }

    /**
     * AJAX endpoint for manifest generation (if needed)
     */
    public function displayAjax()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'generate_single':
                $id_order_detail = (int)Tools::getValue('id_order_detail');
                if ($this->verifyOrderDetailOwnership($id_order_detail)) {
                    $url = Context::getContext()->link->getModuleLink(
                        'multivendor', 
                        'manifest', 
                        ['id_order_detail' => $id_order_detail]
                    );
                    die(json_encode(['success' => true, 'url' => $url]));
                } else {
                    die(json_encode(['success' => false, 'message' => 'Access denied']));
                }
                break;
                
            case 'generate_multiple':
                $details = Tools::getValue('details');
                if (!empty($details)) {
                    $url = Context::getContext()->link->getModuleLink(
                        'multivendor', 
                        'manifest', 
                        ['details' => $details]
                    );
                    die(json_encode(['success' => true, 'url' => $url]));
                } else {
                    die(json_encode(['success' => false, 'message' => 'No details provided']));
                }
                break;
                
            default:
                die(json_encode(['success' => false, 'message' => 'Unknown action']));
        }
    }
}