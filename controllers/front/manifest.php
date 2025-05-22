<?php

/**
 * Corrected Unified Pickup Manifest Generation Controller
 * Fixed to work with PrestaShop HTMLTemplate core properly
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

        $orderDetailIds = [];

        // Handle single order detail
        if (!empty($id_order_detail)) {
            if (!$this->verifyOrderDetailOwnership($id_order_detail)) {
                $this->errors[] = $this->module->l('Access denied for order detail: ') . $id_order_detail;
                $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
                return;
            }
            $orderDetailIds = [$id_order_detail];
        }
        // Handle multiple order details
        elseif (!empty($details)) {
            $orderDetailIds = explode(',', $details);
            $orderDetailIds = array_map('intval', $orderDetailIds);
            $orderDetailIds = array_filter($orderDetailIds);

            if (empty($orderDetailIds)) {
                $this->errors[] = $this->module->l('Invalid order details provided');
                $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
                return;
            }

            // Verify all order details
            foreach ($orderDetailIds as $detailId) {
                if (!$this->verifyOrderDetailOwnership($detailId)) {
                    $this->errors[] = $this->module->l('Access denied for order detail: ') . $detailId;
                    $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
                    return;
                }
            }
        } else {
            $this->errors[] = $this->module->l('No order details specified');
            $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
            return;
        }

        // Generate single manifest for all order details
        $this->generateSingleManifest($orderDetailIds);
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
     * Generate a single pickup manifest PDF for multiple order details
     * CORRECTED: Simplified PDF generation
     *
     * @param array $orderDetailIds Array of order detail IDs
     */
    protected function generateSingleManifest($orderDetailIds)
    {
        try {
            $pdfData = $this->getPdfData($orderDetailIds);

            // Create single manifest filename
            $filename = count($orderDetailIds) > 1 
                ? 'pickup_manifest_' . count($orderDetailIds) . '_items_' . date('YmdHis') . '.pdf'
                : 'pickup_manifest_' . $orderDetailIds[0] . '_' . date('YmdHis') . '.pdf';

            $pdfData['filename'] = $filename;

            // CORRECTED: Create and render PDF
            $pdf = new VendorManifestPDF($pdfData, 'manifest');
            $pdf->render('D');
            
        } catch (Exception $e) {
            // If PDF generation fails, show HTML version
            $this->generateHTMLFallback($orderDetailIds, $e);
        }
    }

    /**
     * Generate HTML fallback if PDF fails
     *
     * @param array $orderDetailIds Order detail IDs
     * @param Exception $e The exception that occurred
     */
    protected function generateHTMLFallback($orderDetailIds, $e)
    {
        try {
            $pdfData = $this->getPdfData($orderDetailIds);
            
            // Set headers for HTML download
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="pickup_manifest_' . date('YmdHis') . '.html"');
            
            // Generate simple HTML content
            $html = $this->generateSimpleHTMLContent($pdfData);
            
            // Output complete HTML document
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Pickup Manifest</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border: 2px solid #333; text-align: center; }
                    .title { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                    th, td { border: 1px solid #333; padding: 8px; text-align: left; }
                    th { background-color: #e9ecef; font-weight: bold; text-align: center; }
                    .summary { background-color: #f8f9fa; padding: 10px; margin: 10px 0; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>' . $html . '</body>
            </html>';
            
            exit;
            
        } catch (Exception $e2) {
            // Even HTML generation failed, redirect with error
            $this->errors[] = $this->module->l('Error generating manifest: ') . $e->getMessage();
            $this->redirectWithNotification(Context::getContext()->link->getModuleLink('multivendor', 'orders'));
        }
    }

    /**
     * Generate simple HTML content for the manifest
     *
     * @param array $pdfData PDF data
     * @return string HTML content
     */
    protected function generateSimpleHTMLContent($pdfData)
    {
        $manifests = $pdfData['manifests'];
        $vendor_info = $pdfData['vendor_info'];
        
        $html = '<div class="header">';
        $html .= '<div class="title">PICKUP MANIFEST</div>';
        $html .= '<div>Vendor: ' . htmlspecialchars($vendor_info->shop_name) . '</div>';
        $html .= '<div>Date: ' . date('Y-m-d H:i') . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="summary">';
        $html .= '<strong>Summary:</strong> ' . count($manifests) . ' items from multiple orders';
        $html .= '</div>';
        
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>#</th>';
        $html .= '<th>Order Ref</th>';
        $html .= '<th>SKU</th>';
        $html .= '<th>Product</th>';
        $html .= '<th>Qty</th>';
        $html .= '<th>Customer</th>';
        $html .= '<th>City</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        $counter = 1;
        foreach ($manifests as $manifest) {
            $html .= '<tr>';
            $html .= '<td>' . $counter++ . '</td>';
            $html .= '<td>' . htmlspecialchars($manifest['order']['reference'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($manifest['orderDetail']['product_reference'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars(substr($manifest['orderDetail']['product_name'] ?? '', 0, 40)) . '</td>';
            $html .= '<td>' . htmlspecialchars($manifest['orderDetail']['product_quantity'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars(($manifest['customer']['firstname'] ?? '') . ' ' . ($manifest['customer']['lastname'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars($manifest['delivery_address']['city'] ?? '') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        $html .= '<div style="margin-top: 40px;">';
        $html .= '<div style="display: inline-block; width: 200px; border-top: 1px solid #000; text-align: center; margin-right: 50px;">Vendor Signature</div>';
        $html .= '<div style="display: inline-block; width: 200px; border-top: 1px solid #000; text-align: center;">Transporter Signature</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Prepare data for PDF generation - consolidated for single manifest
     *
     * @param array $orderDetailIds Array of order detail IDs
     * @return array Formatted data for PDF generation
     */
    protected function getPdfData($orderDetailIds)
    {
        $vendor = new Vendor($this->vendor['id_vendor']);
        $supplier = new Supplier($vendor->id_supplier);
        $manifestData = [];

        // Get warehouse/shop address
        $warehouseAddress = [
            'address1' => Configuration::get('PS_SHOP_ADDR1'),
            'address2' => Configuration::get('PS_SHOP_ADDR2'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'postcode' => Configuration::get('PS_SHOP_CODE'),
            'country' => Configuration::get('PS_SHOP_COUNTRY'),
            'phone' => Configuration::get('PS_SHOP_PHONE')
        ];

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
                'warehouse_address' => $warehouseAddress
            ];
        }

        return [
            'manifests' => $manifestData,
            'vendor_info' => $vendor,
            'warehouse_address' => $warehouseAddress,
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
     * AJAX endpoint for manifest generation
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
                    $orderDetailIds = explode(',', $details);
                    $orderDetailIds = array_map('intval', $orderDetailIds);
                    $orderDetailIds = array_filter($orderDetailIds);
                    
                    // Verify all order details
                    $allAuthorized = true;
                    foreach ($orderDetailIds as $detailId) {
                        if (!$this->verifyOrderDetailOwnership($detailId)) {
                            $allAuthorized = false;
                            break;
                        }
                    }
                    
                    if ($allAuthorized) {
                        $url = Context::getContext()->link->getModuleLink(
                            'multivendor', 
                            'manifest', 
                            ['details' => $details]
                        );
                        die(json_encode(['success' => true, 'url' => $url]));
                    } else {
                        die(json_encode(['success' => false, 'message' => 'Access denied for one or more order details']));
                    }
                } else {
                    die(json_encode(['success' => false, 'message' => 'No details provided']));
                }
                break;
                
            default:
                die(json_encode(['success' => false, 'message' => 'Unknown action']));
        }
    }
}