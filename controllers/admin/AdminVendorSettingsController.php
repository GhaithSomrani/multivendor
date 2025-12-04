<?php

/**
 * AdminVendorSettingsController - Module configuration settings
 */
class AdminVendorSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        parent::__construct();

        $this->meta_title = $this->l('Module Settings');
    }

    /**
     * Main content rendering
     */
    public function initContent()
    {
        // Redirect to the module configuration page
        $link = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'multivendor',
            'tab_module' => 'market_place',
            'module_name' => 'multivendor'
        ]);

        Tools::redirectAdmin($link);
    }
}
