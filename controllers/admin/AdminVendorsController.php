<?php

/**
 * Admin Vendors Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminVendorsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'vendor';
        $this->className = 'Vendor';
        $this->lang = false;
        $this->identifier = 'id_vendor';
        $this->_defaultOrderBy = 'id_vendor';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_vendor' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'shop_name' => [
                'title' => $this->l('Shop Name'),
                'filter_key' => 'a!shop_name'
            ],
            'customer_name' => [
                'title' => $this->l('Customer'),
                'callback' => 'getCustomerName',
                'filter_key' => 'customer!email',
                'havingFilter' => true
            ],
            'supplier_name' => [
                'title' => $this->l('Supplier'),
                'callback' => 'getSupplierName',
                'filter_key' => 'supplier!name',
                'havingFilter' => true
            ],
            'status' => [
                'title' => $this->l('Status'),
                'callback' => 'getVendorStatus',
                'type' => 'select',
                'list' => [
                    0 => $this->l('Pending'),
                    1 => $this->l('Active'),
                    2 => $this->l('Rejected')
                ],
                'filter_key' => 'a!status',
                'badge_success' => [1],
                'badge_warning' => [0],
                'badge_danger' => [2]
            ],
            'date_add' => [
                'title' => $this->l('Date Added'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ],
        ];

        $this->bulk_actions = [
            'approve' => [
                'text' => $this->l('Approve selected vendors'),
                'confirm' => $this->l('Approve selected vendors?')
            ],
            'reject' => [
                'text' => $this->l('Reject selected vendors'),
                'confirm' => $this->l('Reject selected vendors?')
            ]
        ];

        $this->_select = '
            CONCAT(c.firstname, " ", c.lastname, " (", c.email, ")") as customer_name,
            s.name as supplier_name
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.id_customer = a.id_customer)
            LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON (s.id_supplier = a.id_supplier)
        ';
    }

    /**
     * Get customer name for list display
     */
    public function getCustomerName($customerName, $row)
    {
        return $customerName;
    }

    /**
     * Get supplier name for list display
     */
    public function getSupplierName($supplierName, $row)
    {
        return $supplierName;
    }

    /**
     * Get vendor status for list display
     */
    public function getVendorStatus($status, $row)
    {
        $statuses = [
            0 => $this->l('Pending'),
            1 => $this->l('Active'),
            2 => $this->l('Rejected')
        ];

        $statusClasses = [
            0 => 'badge-warning',
            1 => 'badge-success',
            2 => 'badge-danger'
        ];

        return '<span class="badge ' . $statusClasses[$status] . '">' . $statuses[$status] . '</span>';
    }

    /**
     * Process bulk actions
     */
    public function processBulkApprove()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $success = true;

            foreach ($this->boxes as $id) {
                $vendor = new Vendor((int)$id);
                if (Validate::isLoadedObject($vendor)) {
                    $vendor->status = 1;
                    $success &= $vendor->save();
                }
            }

            if ($success) {
                $this->confirmations[] = $this->l('Selected vendors have been approved.');
            } else {
                $this->errors[] = $this->l('An error occurred while approving vendors.');
            }
        } else {
            $this->errors[] = $this->l('No vendor has been selected.');
        }
    }

    /**
     * Process bulk actions
     */
    public function processBulkReject()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $success = true;

            foreach ($this->boxes as $id) {
                $vendor = new Vendor((int)$id);
                if (Validate::isLoadedObject($vendor)) {
                    $vendor->status = 2;
                    $success &= $vendor->save();
                }
            }

            if ($success) {
                $this->confirmations[] = $this->l('Selected vendors have been rejected.');
            } else {
                $this->errors[] = $this->l('An error occurred while rejecting vendors.');
            }
        } else {
            $this->errors[] = $this->l('No vendor has been selected.');
        }
    }

    /**
     * Render form
     */
    public function renderForm()
    {
        // Get customers
        $customersArray = [];
        $suppliersArray = [];
        if ($this->object->id) {
            // Get the current customer
            $customer = $this->getCustomerByVendor($this->object->id);
            if (!empty($customer)) {
                $customersArray[] = [
                    'id' => $customer['id'],
                    'name' => $customer['name']
                ];
            }

            // Get the current supplier
            $supplier = $this->getSupplierByVendor($this->object->id);
            if (!empty($supplier)) {
                $suppliersArray[] = [
                    'id' => $supplier['id'],
                    'name' => $supplier['name']
                ];
            }
        }
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Vendor'),
                'icon' => 'icon-user'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Customer'),
                    'name' => 'id_customer',
                    'options' => [
                        'query' => $customersArray,
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Supplier'),
                    'name' => 'id_supplier',
                    'options' => [
                        'query' => $suppliersArray,
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Commission Rate (%)'),
                    'name' => 'commission_rate',
                    'required' => true,
                    'suffix' => '%',
                    'class' => 'fixed-width-sm',
                    'validation' => 'isPositiveInt'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Shop Name'),
                    'name' => 'shop_name',
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'autoload_rte' => true,
                    'rows' => 5
                ],

                [
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'status',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Active')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Inactive')
                        ]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];


        if (!$this->object->id) {
            $this->fields_value['status'] = 1;
        } else {

            $this->fields_value['commission_rate'] = VendorCommission::getCommissionRate($this->object->id);
        }

        return parent::renderForm();
    }

    /**
     * Process save
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            // Get values from form
            $commission_rate = (float)Tools::getValue('commission_rate');
            if (empty($commission_rate) || $commission_rate <= 0) {
                $this->errors[] = $this->l('Commission Rate is required and must be greater than zero.');
                return false;
            }

            // Create or update vendor commission after saving vendor
            $result = parent::postProcess();

            // If vendor saved successfully, create/update its commission
            if ($result && !empty($this->object->id)) {
                // Check if commission already exists
                $existingCommission = Db::getInstance()->getRow(
                    'SELECT * FROM `' . _DB_PREFIX_ . 'vendor_commission` 
                 WHERE `id_vendor` = ' . (int)$this->object->id
                );

                if ($existingCommission) {

                    // Update existing commission
                    Db::getInstance()->update('vendor_commission', [
                        'commission_rate' => $commission_rate,
                        'date_upd' => date('Y-m-d H:i:s')
                    ], '`id_vendor_commission` = ' . (int)$existingCommission['id_vendor_commission']);
                } else {
                    // Create new commission
                    Db::getInstance()->insert('vendor_commission', [
                        'id_vendor' => (int)$this->object->id,
                        'commission_rate' => $commission_rate,
                        'date_add' => date('Y-m-d H:i:s'),
                        'date_upd' => date('Y-m-d H:i:s')
                    ]);
                }

                return true;
            }

            return $result;
        }

        return parent::postProcess();
    }

    /**
     * Add custom buttons to toolbar
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['view_commissions'] = [
                'href' => $this->context->link->getAdminLink('AdminVendorCommissions'),
                'desc' => $this->l('Manage Commissions'),
                'icon' => 'process-icon-money'
            ];

            $this->page_header_toolbar_btn['view_payments'] = [
                'href' => $this->context->link->getAdminLink('AdminVendorPayments'),
                'desc' => $this->l('Manage Payments'),
                'icon' => 'process-icon-payment'
            ];
        }

        parent::initPageHeaderToolbar();
    }


    /**
     * AJAX process to search customers
     */
    public function ajaxProcessSearchCustomers()
    {
        $query = Tools::getValue('q', false);

        if (!$query || $query == '' || strlen($query) < 3) {
            die(json_encode([]));
        }

        $customers = Db::getInstance()->executeS('
        SELECT c.id_customer, c.firstname, c.lastname, c.email
        FROM `' . _DB_PREFIX_ . 'customer` c
        WHERE (
            LOWER(c.firstname) LIKE LOWER("%' . pSQL($query) . '%") 
            OR LOWER(c.lastname) LIKE LOWER("%' . pSQL($query) . '%") 
            OR LOWER(c.email) LIKE LOWER("%' . pSQL($query) . '%")
        )
        ORDER BY c.firstname, c.lastname
        LIMIT 20
    ');

        die(json_encode($customers));
    }

    /**
     * AJAX process to search suppliers
     */
    public function ajaxProcessSearchSuppliers()
    {
        $query = Tools::getValue('q', false);

        if (!$query || $query == '' || strlen($query) < 3) {
            die(json_encode([]));
        }
        $suppliers = Db::getInstance()->executeS('
        SELECT s.id_supplier, s.name
        FROM `' . _DB_PREFIX_ . 'supplier` s
        WHERE LOWER(s.name) LIKE LOWER("%' . pSQL($query) . '%")
        ORDER BY s.name
        LIMIT 20
    ');

        die(json_encode($suppliers));
    }

    /**
     * Get customer by vendor ID
     *
     * @param int $id_vendor Vendor ID
     * @return array Customer data or empty array
     */
    protected function getCustomerByVendor($id_vendor)
    {
        if (!$id_vendor) {
            return [];
        }

        $vendor = new Vendor($id_vendor);
        if (!Validate::isLoadedObject($vendor) || !$vendor->id_customer) {
            return [];
        }

        $customer = new Customer($vendor->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return [];
        }

        return [
            'id' => $customer->id,
            'name' => $customer->firstname . ' ' . $customer->lastname . ' (' . $customer->email . ')'
        ];
    }

    /**
     * Get supplier by vendor ID
     *
     * @param int $id_vendor Vendor ID
     * @return array Supplier data or empty array
     */
    protected function getSupplierByVendor($id_vendor)
    {
        if (!$id_vendor) {
            return [];
        }

        $vendor = new Vendor($id_vendor);
        if (!Validate::isLoadedObject($vendor) || !$vendor->id_supplier) {
            return [];
        }

        $supplier = new Supplier($vendor->id_supplier);
        if (!Validate::isLoadedObject($supplier)) {
            return [];
        }

        return [
            'id' => $supplier->id,
            'name' => $supplier->name
        ];
    }
}
