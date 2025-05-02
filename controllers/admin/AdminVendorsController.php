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
        $customers = Customer::getCustomers();
        $customersArray = [];
        foreach ($customers as $customer) {
            $customersArray[] = [
                'id' => $customer['id_customer'],
                'name' => $customer['firstname'] . ' ' . $customer['lastname'] . ' (' . $customer['email'] . ')'
            ];
        }

        // Get suppliers
        $suppliers = Supplier::getSuppliers();
        $suppliersArray = [];
        foreach ($suppliers as $supplier) {
            $suppliersArray[] = [
                'id' => $supplier['id_supplier'],
                'name' => $supplier['name']
            ];
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
                // [
                //     'type' => 'file',
                //     'label' => $this->l('Logo'),
                //     'name' => 'logo',
                //     'display_image' => true,
                //     'image' => $this->object->logo ? _PS_IMG_ . 'vendors/' . $this->object->id . '/' . $this->object->logo : false
                // ],
                // [
                //     'type' => 'file',
                //     'label' => $this->l('Banner'),
                //     'name' => 'banner',
                //     'display_image' => true,
                //     'image' => $this->object->banner ? _PS_IMG_ . 'vendors/' . $this->object->id . '/' . $this->object->banner : false
                // ],
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
        }

        return parent::renderForm();
    }

    /**
     * Process save
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            // Handle image uploads
            if ($this->object->id) {
                $upload_dir = _PS_IMG_DIR_ . 'vendors/' . $this->object->id . '/';

                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }

                // Handle logo upload
                if (isset($_FILES['logo']) && !empty($_FILES['logo']['name'])) {
                    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $filename = 'logo_' . $this->object->id . '.' . $ext;
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
                        $_POST['logo'] = $filename;
                    }
                }

                // Handle banner upload
                if (isset($_FILES['banner']) && !empty($_FILES['banner']['name'])) {
                    $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
                    $filename = 'banner_' . $this->object->id . '.' . $ext;
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['banner']['tmp_name'], $filepath)) {
                        $_POST['banner'] = $filename;
                    }
                }
            }
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
}
