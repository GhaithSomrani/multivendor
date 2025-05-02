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
        $this->table = 'vendor_commission';
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
                'type' => 'price',
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
            LEFT JOIN `' . _DB_PREFIX_ . 'vendor` v ON (v.id_vendor = a.id_vendor)
        ';

        // Add additional tabs
        $this->tabs = [
            [
                'class_name' => 'AdminVendorCommissions',
                'name' => $this->l('Vendor Commissions')
            ],
            [
                'class_name' => 'AdminCategoryCommissions',
                'name' => $this->l('Category Commissions')
            ]
        ];

        // Add custom actions
        $this->addRowAction('edit');
        $this->addRowAction('delete');
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
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Commission Rate (%)'),
                    'name' => 'commission_rate',
                    'suffix' => '%',
                    'required' => true
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

        // Add category commissions tab
        if (!$this->display) {
            $this->content .= $this->renderCategoryCommissionsTab();
            $this->content .= $this->renderCommissionHistoryTab();
        }
    }

    /**
     * Render category commissions tab
     */
    protected function renderCategoryCommissionsTab()
    {
        // Get category commissions
        $query = new DbQuery();
        $query->select('cc.*, v.shop_name, cl.name as category_name');
        $query->from('category_commission', 'cc');
        $query->leftJoin('vendor', 'v', 'v.id_vendor = cc.id_vendor');
        $query->leftJoin('category_lang', 'cl', 'cl.id_category = cc.id_category AND cl.id_lang = ' . (int)$this->context->language->id);
        $query->orderBy('cc.date_add DESC');

        $categoryCommissions = Db::getInstance()->executeS($query);

        // Get vendors
        $vendors = Vendor::getAllVendors();
        $vendorsArray = [];
        foreach ($vendors as $vendor) {
            $vendorsArray[$vendor['id_vendor']] = $vendor['shop_name'];
        }

        // Get categories
        $categories = Category::getCategories((int)$this->context->language->id, true, false);
        $categoriesArray = [];
        foreach ($categories as $category) {
            $categoriesArray[$category['id_category']] = $category['name'];
        }

        $this->context->smarty->assign([
            'category_commissions' => $categoryCommissions,
            'vendors' => $vendorsArray,
            'categories' => $categoriesArray,
            'category_commission_url' => $this->context->link->getAdminLink('AdminVendorCommissions') . '&action=addCategoryCommission'
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'multivendor/views/templates/admin/category_commissions.tpl');
    }

    /**
     * Render commission history tab
     */
    protected function renderCommissionHistoryTab()
    {
        // Get commission logs
        $query = new DbQuery();
        $query->select('vcl.*, v.shop_name, CONCAT(e.firstname, " ", e.lastname) as employee_name, cl.name as category_name');
        $query->from('vendor_commission_log', 'vcl');
        $query->leftJoin('vendor', 'v', 'v.id_vendor = vcl.id_vendor');
        $query->leftJoin('employee', 'e', 'e.id_employee = vcl.changed_by');
        $query->leftJoin('category_lang', 'cl', 'cl.id_category = vcl.id_category AND cl.id_lang = ' . (int)$this->context->language->id);
        $query->orderBy('vcl.date_add DESC');
        $query->limit(50);

        $commissionLogs = Db::getInstance()->executeS($query);

        $this->context->smarty->assign([
            'commission_logs' => $commissionLogs
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'multivendor/views/templates/admin/commission_logs.tpl');
    }

    /**
     * Process AJAX actions
     */
    public function ajaxProcessAddCategoryCommission()
    {
        $id_vendor = (int)Tools::getValue('id_vendor');
        $id_category = (int)Tools::getValue('id_category');
        $commission_rate = (float)Tools::getValue('commission_rate');
        $comment = Tools::getValue('comment');

        // Check if this category commission already exists
        $existingCommission = Db::getInstance()->getRow(
            '
            SELECT * FROM `' . _DB_PREFIX_ . 'category_commission`
            WHERE `id_vendor` = ' . (int)$id_vendor . ' AND `id_category` = ' . (int)$id_category
        );

        if ($existingCommission) {
            // Update existing commission
            $oldRate = (float)$existingCommission['commission_rate'];

            $success = Db::getInstance()->update('category_commission', [
                'commission_rate' => $commission_rate,
                'date_upd' => date('Y-m-d H:i:s')
            ], '`id_category_commission` = ' . (int)$existingCommission['id_category_commission']);

            // Log the change
            CategoryCommission::logCommissionRateChange(
                $id_vendor,
                $id_category,
                $oldRate,
                $commission_rate,
                $this->context->employee->id,
                $comment
            );
        } else {
            // Create new category commission
            $success = Db::getInstance()->insert('category_commission', [
                'id_vendor' => $id_vendor,
                'id_category' => $id_category,
                'commission_rate' => $commission_rate,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s')
            ]);

            // Log the change
            CategoryCommission::logCommissionRateChange(
                $id_vendor,
                $id_category,
                Configuration::get('MV_DEFAULT_COMMISSION', 10),
                $commission_rate,
                $this->context->employee->id,
                $comment
            );
        }

        die(json_encode([
            'success' => (bool)$success
        ]));
    }
}
