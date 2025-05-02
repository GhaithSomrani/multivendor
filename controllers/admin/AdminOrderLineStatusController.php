<?php

/**
 * Admin Order Line Status Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminOrderLineStatusController  extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order_line_status_type';
        $this->className = 'OrderLineStatusType';
        $this->lang = false;
        $this->identifier = 'id_order_line_status_type';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->position_identifier = 'id_order_line_status_type';

        parent::__construct();

        $this->fields_list = [
            'id_order_line_status_type' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Status Name'),
                'filter_key' => 'a!name'
            ],
            'color' => [
                'title' => $this->l('Color'),
                'type' => 'color',
                'filter_key' => 'a!color'
            ],
            'is_vendor_allowed' => [
                'title' => $this->l('Vendor Allowed'),
                'align' => 'center',
                'type' => 'bool',
                'filter_key' => 'a!is_vendor_allowed',
                'active' => 'vendorallowed'
            ],
            'is_admin_allowed' => [
                'title' => $this->l('Admin Allowed'),
                'align' => 'center',
                'type' => 'bool',
                'filter_key' => 'a!is_admin_allowed',
                'active' => 'adminallowed'
            ],
            'affects_commission' => [
                'title' => $this->l('Affects Commission'),
                'align' => 'center',
                'type' => 'bool',
                'filter_key' => 'a!affects_commission',
                'active' => 'affectscommission'
            ],
            'commission_action' => [
                'title' => $this->l('Commission Action'),
                'filter_key' => 'a!commission_action',
                'callback' => 'getCommissionActionText'
            ],
            'position' => [
                'title' => $this->l('Position'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->l('Active'),
                'align' => 'center',
                'type' => 'bool',
                'filter_key' => 'a!active',
                'active' => 'status'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            ],
            'enableSelection' => [
                'text' => $this->l('Enable selection')
            ],
            'disableSelection' => [
                'text' => $this->l('Disable selection')
            ]
        ];

        $this->actions = ['edit', 'delete'];
    }

    public function getCommissionActionText($action, $row)
    {
        $actions = [
            'none' => $this->l('None'),
            'add' => $this->l('Add Commission'),
            'cancel' => $this->l('Cancel Commission'),
            'refund' => $this->l('Refund Commission')
        ];

        return isset($actions[$action]) ? $actions[$action] : $action;
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Order Line Status'),
                'icon' => 'icon-list'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Status Name'),
                    'name' => 'name',
                    'required' => true
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Color'),
                    'name' => 'color',
                    'hint' => $this->l('Status color for easy identification.')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Vendor Allowed'),
                    'name' => 'is_vendor_allowed',
                    'hint' => $this->l('Allow vendors to set this status?'),
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Admin Allowed'),
                    'name' => 'is_admin_allowed',
                    'hint' => $this->l('Allow admin to set this status?'),
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Affects Commission'),
                    'name' => 'affects_commission',
                    'hint' => $this->l('Does this status affect commission calculation?'),
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Commission Action'),
                    'name' => 'commission_action',
                    'hint' => $this->l('How this status affects commission'),
                    'options' => [
                        'query' => [
                            ['id' => 'none', 'name' => $this->l('None')],
                            ['id' => 'add', 'name' => $this->l('Add Commission')],
                            ['id' => 'cancel', 'name' => $this->l('Cancel Commission (Set to 0)')],
                            ['id' => 'refund', 'name' => $this->l('Refund Commission (Negative)')],
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Position'),
                    'name' => 'position',
                    'required' => false
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        return parent::renderForm();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_status'] = [
                'href' => self::$currentIndex . '&addorder_line_status_type&token=' . $this->token,
                'desc' => $this->l('Add New Status'),
                'icon' => 'process-icon-new'
            ];
        }

        parent::initPageHeaderToolbar();
    }
}
