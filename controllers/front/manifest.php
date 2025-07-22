<?php

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
        $this->vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$this->vendor) {
            Tools::redirect('index.php?controller=my-account');
        }
    }

    public function initContent()
    {
        // DON'T call parent::initContent() - this prevents template rendering
        // parent::initContent(); 

        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $details = Tools::getValue('details', '');

        if (!empty($id_order_detail)) {
        $this->generateManifest([$id_order_detail]);
        } elseif (!empty($details)) {
            $orderDetailIds = array_filter(array_map('intval', explode(',', $details)));
            if (empty($orderDetailIds)) {
                die($this->module->l('Invalid order details'));
            }
        $this->generateManifest($orderDetailIds);
        } else {
            die($this->module->l('No order details specified'));
        }
    }

   

    protected function generateManifest($orderDetailIds)
    {
        try {
            // Pass only the order detail IDs and vendor info to the PDF template
            $pdfData = [
                'orderDetailIds' => $orderDetailIds,
                'vendor' => $this->vendor,
                'filename' => 'Pickup_Manifest_' . date('YmdHis') . '.pdf'
            ];
            
            $pdf = new PDF([$pdfData], 'VendorManifestPDF', $this->context->smarty);
            $pdf->render(true);
            
            // Make sure to exit after PDF is rendered to prevent further processing
            exit;
            
        } catch (Exception $e) {
            die($this->module->l('Error generating manifest:') . ' ' . $e->getMessage());
        }
    }

    // Alternative approach - override the display method to prevent template rendering
    public function display()
    {
        // Don't call parent::display() to prevent template rendering
        // The PDF will be generated in initContent()
    }

   
}