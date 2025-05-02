<?php

/**
 * Vendor Profile controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class multivendorProfileModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        parent::init();

        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = Vendor::getVendorByCustomer($id_customer);

        if (!$vendor) {
            Tools::redirect('index.php?controller=my-account');
        }

        // Set vendor ID for later use
        $this->context->smarty->assign('id_vendor', $vendor['id_vendor']);
    }

    public function initContent()
    {
        parent::initContent();

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');
        $vendor = new Vendor($id_vendor);

        // Handle form submission
        if (Tools::isSubmit('submitVendorProfile')) {
            $this->processProfileUpdate($vendor);
        }

        // Assign data to template
        $this->context->smarty->assign([
            'vendor' => $vendor,
            'languages' => Language::getLanguages(),
            'default_language' => $this->context->language->id,
            'image_base_path' => _PS_IMG_ . 'vendors/' . $id_vendor . '/',
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard')
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/profile.tpl');
    }

    /**
     * Process profile update
     */
    protected function processProfileUpdate($vendor)
    {
        // Validate inputs
        $shop_name = Tools::getValue('shop_name');
        $description = Tools::getValue('description');

        if (empty($shop_name)) {
            $this->errors[] = $this->module->l('Shop name is required.');
            return;
        }

        // Update vendor data
        $vendor->shop_name = $shop_name;
        $vendor->description = $description;
        $vendor->date_upd = date('Y-m-d H:i:s');

        // Handle logo upload
        if (isset($_FILES['logo']) && !empty($_FILES['logo']['name'])) {
            $this->uploadLogo($vendor);
        }

        // Handle banner upload
        if (isset($_FILES['banner']) && !empty($_FILES['banner']['name'])) {
            $this->uploadBanner($vendor);
        }

        // Save vendor
        if (empty($this->errors) && $vendor->save()) {
            $this->success[] = $this->module->l('Profile updated successfully.');
        } else {
            $this->errors[] = $this->module->l('Failed to update profile.');
        }
    }

    /**
     * Upload vendor logo
     */
    protected function uploadLogo($vendor)
    {
        $id_vendor = $vendor->id;
        $upload_dir = _PS_IMG_DIR_ . 'vendors/' . $id_vendor . '/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        // Get file extension
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($ext), $allowed_extensions)) {
            $this->errors[] = $this->module->l('Invalid file format. Allowed formats: jpg, jpeg, png, gif');
            return;
        }

        // Generate unique filename
        $filename = 'logo_' . $id_vendor . '.' . $ext;
        $filepath = $upload_dir . $filename;

        // Upload file
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
            // Update vendor logo field
            $vendor->logo = $filename;
        } else {
            $this->errors[] = $this->module->l('Failed to upload logo.');
        }
    }

    /**
     * Upload vendor banner
     */
    protected function uploadBanner($vendor)
    {
        $id_vendor = $vendor->id;
        $upload_dir = _PS_IMG_DIR_ . 'vendors/' . $id_vendor . '/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        // Get file extension
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($ext), $allowed_extensions)) {
            $this->errors[] = $this->module->l('Invalid file format. Allowed formats: jpg, jpeg, png, gif');
            return;
        }

        // Generate unique filename
        $filename = 'banner_' . $id_vendor . '.' . $ext;
        $filepath = $upload_dir . $filename;

        // Upload file
        if (move_uploaded_file($_FILES['banner']['tmp_name'], $filepath)) {
            // Update vendor banner field
            $vendor->banner = $filename;
        } else {
            $this->errors[] = $this->module->l('Failed to upload banner.');
        }
    }
}
