<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorManifestModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;
    public $vendor =  [];

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
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $details = Tools::getValue('details', '');

        if (!empty($id_order_detail)) {
            Manifest::generateNewManifestPDF([$id_order_detail], (int)$this->vendor['id_vendor'], Manifest::TYPE_PICKUP, null, 1);
        } elseif (!empty($details)) {
            $orderDetailIds = array_filter(array_map('intval', explode(',', $details)));
            if (empty($orderDetailIds)) {
                die($this->module->l('Invalid order details'));
            }
            Manifest::generateNewManifestPDF($orderDetailIds,   (int)$this->vendor['id_vendor'], Manifest::TYPE_PICKUP, null, 1);
        } else {
            die($this->module->l('No order details specified'));
        }
    }
}
