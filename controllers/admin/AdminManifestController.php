<?php

/**
 * Admin Manifest Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminManifestController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_manifest';
        $this->className = 'Manifest';
        $this->lang = false;
        $this->identifier = 'id_manifest';
        $this->_defaultOrderBy = 'id_manifest';
        $this->_defaultOrderWay = 'DESC';
        $this->list_id = 'manifest';

        // Enable actions
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->allow_export = true;
        $this->_use_found_rows = true;


        parent::__construct();

        $this->fields_list = [
            'id_manifest' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'reference' => [
                'title' => $this->l('Reference'),
                'filter_key' => 'a!reference',
            ],
            'type' => [
                'title' => $this->l('Type'),
                'type' => 'select',
                'list' => [
                    Manifest::TYPE_PICKUP => $this->l('Pickup'),
                    Manifest::TYPE_RETURNS => $this->l('Returns')
                ],
                'filter_key' => 'a!type',
                'filter_type' => 'select',
                'filter_list' => [
                    Manifest::TYPE_PICKUP => $this->l('Pickup'),
                    Manifest::TYPE_RETURNS => $this->l('Returns')
                ]
            ],
            'address_name' => [
                'title' => $this->l('Address'),
                'filter_key' => 'v!shop_name',
                'callback' => 'displayAddressName',
                'search' => true
            ],
            'total_items' => [
                'title' => $this->l('Total Items'),
                'align' => 'center',
                'search' => true,
                'orderby' => true,
                'callback' => 'displayTotalItems'
            ],

            'add_date' => [
                'title' => $this->l('Date Add'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!add_date'
            ],
            'update_date' => [
                'title' => $this->l('Date Update'),
                'align' => 'right',
                'type' => 'datetime',
                'filter_key' => 'a!update_date'
            ]
        ];

        // $this->_join = '
        //     LEFT JOIN `' . _DB_PREFIX_ . 'address` ad ON (a.id_address = ad.id_address)';
        $this->_join .= '
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)';


        $this->_select = '
           v.shop_name as address_name,
            a.id_manifest_status as status_display';
    }

    /**
     * Display address name callback
     */
    public function displayAddressName($value, $row)
    {
        return $value;
    }

    /**
     * Display total items callback
     */
    public function displayTotalItems($value, $row)
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mv_manifest_details` 
                WHERE id_manifest = ' . (int)$row['id_manifest'];

        $count = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        return (int)$count;
    }
    /**
     * Render form for add/edit
     */
    public function renderForm()
    {
        // add 1st option for vendor select
        $this->fields_value['vendor'] = 0;
        $this->fields_value['id_address'] = 0;
        $this->fields_value['reference'] = '';
        $this->fields_value['id_manifest_status'] = 1;
        $this->fields_value['type'] = Manifest::TYPE_PICKUP;
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Manifest'),
                'icon' => 'icon-list'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Reference'),
                    'name' => 'reference',
                    'required' => true,
                    'maxlength' => 128,
                    'hint' => $this->l('Manifest reference (leave empty to auto-generate)')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('vendor'),
                    'name' => 'vendor',
                    'required' => true,
                    'options' => [
                        'query' => $this->getVendors(),
                        'id' => 'id_vendor',
                        'name' => 'shop_name',
                        'default' => [
                            'value' => '',
                            'label' => $this->l('Select vendor')
                        ],
                    ],
                    'onchange' =>  'updateAddresses()',
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Type'),
                    'name' => 'type',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => Manifest::TYPE_PICKUP, 'name' => $this->l('Pickup')],
                            ['id' => Manifest::TYPE_RETURNS, 'name' => $this->l('Returns')]
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Pick up Address'),
                    'name' => 'id_address',
                    'required' => true,
                    'options' => [
                        'query' => $this->getAddresses(),
                        'id' => 'id_address',
                        'name' => 'address_display'
                    ]
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];

        if (!$this->object->id) {
            $this->fields_form['input'][] = [
                'type' => 'html',
                'name' => 'vendor_order_details_table',
                'html_content' => $this->renderVendorOrderDetailsTable()
            ];
        }

        // Auto-generate reference if creating new manifest
        if (!$this->object->id) {
            $this->fields_value['reference'] = Manifest::generateReference();
            $this->fields_value['id_manifest_status'] = 1;
        }

        return parent::renderForm();
    }





    protected function renderVendorOrderDetailsTable()
    {

        $details = OrderHelper::getVendorOrderDetails(1);
        if (empty($details)) {
            $this->errors[] = $this->l('No order details found for this vendor.');
            return false;
        }
        $this->context->smarty->assign([
            'order_details' => $details,
            'vendor_id' => (int)Tools::getValue('id_vendor'),
            'manifest_id' => (int)Tools::getValue('id_manifest'),

        ]);
        return $this->context->smarty->fetch($this->getTemplatePath() . '/vendor_order_details_table.tpl');
    }

    /**
     * Get vendor for dropdown
     */
    private function getVendors()
    {

        return Vendor::getAllVendors();
    }



    /**
     * Get addresses for dropdown
     */

    private function getAddresses()
    {
        $vendor = new Vendor((int)Tools::getValue('vendor'));
        if (!Validate::isLoadedObject($vendor)) {
            return [];
        }
        $addresses = $vendor->getVendorAddress();
    }

    /**
     * Process save
     */
    public function processSave()
    {
        // Auto-generate reference if empty
        if (!Tools::getValue('reference')) {
            $_POST['reference'] = Manifest::generateReference();
        }

        return parent::processSave();
    }

    /**
     * Render view page
     */
    public function renderView()
    {
        $manifest = new Manifest($this->object->id);
        $vendor = $manifest->getVendorByManifest();
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->l('The manifest cannot be found.');
            return false;
        }

        // Get manifest details
        $details = $this->getManifestDetails($manifest->id);
        $address = new Address($manifest->id_address);
        $this->context->smarty->assign([
            'vendor_name' =>  $vendor['shop_name'],
            'manifest' => $manifest,
            'manifest_details' => $details,
            'address' => $address,
            'total_items' => count($details),
            'back_url' => $this->context->link->getAdminLink('AdminManifest')
        ]);

        return $this->context->smarty->fetch($this->getTemplatePath() . 'view.tpl');
    }

    /**
     * Get manifest details with order information
     */
    private function getManifestDetails($id_manifest)
    {
        $sql = 'SELECT md.*, 
                       vod.product_name, 
                       vod.product_reference, 
                       vod.product_quantity,
                       vod.product_price,
                       o.reference as order_reference,
                       o.id_order,
                         v.shop_name as name
                FROM `' . _DB_PREFIX_ . 'mv_manifest_details` md
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor_order_detail` vod ON (vod.id_order_detail = md.id_order_details)
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (vod.id_order = o.id_order)
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest` m ON (m.id_manifest =' . (int)$id_manifest . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (m.id_vendor = v.id_vendor)
                WHERE md.id_manifest = ' . (int)$id_manifest . '
                ORDER BY md.add_date ASC';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }


    /**
     * Get template path
     */
    public function getTemplatePath()
    {
        return _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/manifest/';
    }

    /**
     * Set media (CSS/JS)
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');
    }

    /**
     * Initialize page header toolbar
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_manifest'] = [
                'href' => self::$currentIndex . '&addmv_manifest&token=' . $this->token,
                'desc' => $this->l('Add new manifest'),
                'icon' => 'process-icon-new'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Post process - handle additional actions
     */
    public function postProcess()
    {
        // Handle manifest details management
        if (Tools::isSubmit('addOrderDetail')) {
            $this->processAddOrderDetail();
        } elseif (Tools::isSubmit('removeOrderDetail')) {
            $this->processRemoveOrderDetail();
        }

        return parent::postProcess();
    }

    /**
     * Process add order detail to manifest
     */
    private function processAddOrderDetail()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $id_order_detail = (int)Tools::getValue('id_order_detail');

        if (!$id_manifest || !$id_order_detail) {
            $this->errors[] = $this->l('Invalid parameters');
            return;
        }

        $manifest = new Manifest($id_manifest);
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->l('Manifest not found');
            return;
        }

        if ($manifest->addOrderDetail($id_order_detail)) {
            $this->confirmations[] = $this->l('Order detail added to manifest successfully');
        } else {
            $this->errors[] = $this->l('Error adding order detail to manifest');
        }
    }

    /**
     * Process remove order detail from manifest
     */
    private function processRemoveOrderDetail()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $id_order_detail = (int)Tools::getValue('id_order_detail');

        if (!$id_manifest || !$id_order_detail) {
            $this->errors[] = $this->l('Invalid parameters');
            return;
        }

        $manifest = new Manifest($id_manifest);
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->l('Manifest not found');
            return;
        }

        if ($manifest->removeOrderDetail($id_order_detail)) {
            $this->confirmations[] = $this->l('Order detail removed from manifest successfully');
        } else {
            $this->errors[] = $this->l('Error removing order detail from manifest');
        }
    }
}
