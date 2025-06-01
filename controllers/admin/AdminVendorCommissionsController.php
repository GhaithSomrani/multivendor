<?php

/**
 * Admin Vendor Commissions Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminVendorCommissionsController extends ModuleAdminController

{

    // Other property declarations...

    /**
     * @var array Tabs for this controller
     */
    protected $tabs = [];

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_vendor_commission';
        $this->className = 'VendorCommission';
        $this->lang = false;
        $this->identifier = 'id_vendor_commission';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_vendor_commission' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'

            ],
            'vendor_name' => [
                'title' => $this->l('Vendor'),
                'filter_key' => 'vendor!shop_name',
                'havingFilter' => true

            ],
            'commission_rate' => [
                'title' => $this->l('Commission Rate (%)'),
                'type' => 'percentage',
                'filter_key' => 'a!commission_rate'
            ],
            'date_add' => [
                'title' => $this->l('Date Added'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ]
        ];

        $this->_select = '
            v.shop_name as vendor_name
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)
        ';

        // Add additional tabs
        $this->tabs = [
            [
                'class_name' => 'AdminVendorCommissions',
                'name' => $this->l('Vendor Commissions')
            ],
          
        ];

        // Add custom actions
        $this->addRowAction('edit');
    }

    /**
     * Render form
     */
    public function renderForm()
    {
        // Get vendors
        $vendors = Vendor::getAllVendors();
        $vendorsArray = [];
        foreach ($vendors as $vendor) {
            $vendorsArray[] = [
                'id' => $vendor['id_vendor'],
                'name' => $vendor['shop_name']
            ];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Vendor Commission'),
                'icon' => 'icon-money'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Vendor'),
                    'name' => 'id_vendor',
                    'options' => [
                        'query' => $vendorsArray,
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'required' => true,
                    'disabled' => (bool)$this->object->id,
                    'desc' => $this->object->id ? $this->l('Vendor cannot be changed when editing a commission') : null

                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Commission Rate (%)'),
                    'name' => 'commission_rate',
                    'suffix' => '%',
                    'required' => true,
                    'desc' => $this->l('Commission rate must be between 0% and 100%. Use 0% for no commission.')

                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Comment'),
                    'name' => 'comment',
                    'desc' => $this->l('This comment will be saved in the commission log history')
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        return parent::renderForm();
    }


    /**
     * Process save
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $id_vendor = (int)Tools::getValue('id_vendor');
            $commission_rate = (float)Tools::getValue('commission_rate');
            if ($commission_rate <= 0 || $commission_rate > 100) {
                $this->errors[] = $this->l('Commission Rate must be between 0% and 100%.');
                return false;
            }
            $comment = Tools::getValue('comment');

            // Get current commission rate
            $oldRate = VendorCommission::getCommissionRate($id_vendor);
            $oldRate = $oldRate !== null ? $oldRate : Configuration::get('MV_DEFAULT_COMMISSION', 10);

            // Log commission rate change
            VendorCommission::logCommissionRateChange(
                $id_vendor,
                $oldRate,
                $commission_rate,
                $this->context->employee->id,
                $comment
            );
        }

        return parent::postProcess();
    }

    /**
     * Init content
     */
    public function initContent()
    {
        if ($this->display == 'view') {
            $this->display = 'list';
            $this->displayInformation($this->l('To view commission logs, use the Commission History tab.'));
        }

        parent::initContent();

    
    }

    
  
}
