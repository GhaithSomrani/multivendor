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
        $this->table = 'mv_order_line_status_type';
        $this->className = 'OrderLineStatusType';
        $this->lang = false;
        $this->identifier = 'id_order_line_status_type';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->position_identifier = 'position';

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
                'align' => 'center',
                'class' => 'pointer dragHandle'
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

        $position = Db::getInstance()->getValue(
            '
            SELECT `position` 
            FROM `' . _DB_PREFIX_ . $this->table . '` 
            WHERE `' . $this->identifier . '` = ' . (int)$this->object->id
        );
        $this->fields_value['position'] = $position ? $position : 1;
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
                    'type' => 'checkbox',
                    'label' => $this->l('Available Status Transitions'),
                    'name' => 'AvailableData',
                    'values' => [
                        'query' => $this->getAvailableStatusOptions(),
                        'id' => 'id_status',
                        'name' => 'name'
                    ],
                    'hint' => $this->l('Select which statuses this status can transition to. Leave empty to allow all transitions.')
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

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table) && !Tools::getValue('id_order_line_status_type')) {
            $position = (int)Tools::getValue('position');

            if ($position <= 0) {
                $max_position = Db::getInstance()->getValue(
                    'SELECT MAX(position) FROM `' . _DB_PREFIX_ . $this->table . '`'
                );
                $_POST['position'] = $max_position + 1;
            }
        }
        $this->processAvailableStatus();


        return parent::postProcess();
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


    protected function countUsedStatuses($id_status_type)
    {
        return Db::getInstance()->getValue(
            'SELECT COUNT(*) 
         FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
         WHERE active = 1 and id_order_line_status_type = ' . (int)$id_status_type
        );
    }
    protected function isDeletable($id_status_type)
    {
        $count = $this->countUsedStatuses($id_status_type);
        return (int)$count === 0;
    }


    public function processDelete()
    {
        $id = (int)Tools::getValue('id_order_line_status_type');
        $status = new OrderLineStatusType($id);

        if (!Validate::isLoadedObject($status)) {
            $this->errors[] = $this->l('Invalid status ID');
            return false;
        }

        if (!$this->isDeletable($id)) {
            $count = $this->countUsedStatuses($id);
            $this->errors[] = sprintf('Cannot delete "%s" (used by %d order lines)', $status->name, $count);
            return false;
        }

        return parent::processDelete();
    }

    /**
     * Process bulk delete with proper error handling using array_filter
     */
    public function processBulkDelete()
    {
        if (!is_array($this->boxes) || empty($this->boxes)) {
            return parent::processBulkDelete();
        }

        $this->boxes = array_filter($this->boxes, function ($id) {
            return $this->isDeletable($id);
        });

        if (empty($this->boxes)) {
            $this->errors[] = $this->l('Selected items cannot be deleted (in use)');
            return false;
        }

        return parent::processBulkDelete();
    }

    protected function processAvailableStatus()
    {
        $available_status = [];

        // Loop through all POST data to find available_status checkboxes
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'AvailableData_') === 0 && $value) {
                $status_id = str_replace('AvailableData_', '', $key);
                if (is_numeric($status_id)) {
                    $available_status[] = (int)$status_id;
                }
            }
        }

        // Set the available_status as comma-separated string
        $_POST['available_status'] = implode(',', $available_status);
    }
    protected function getAvailableStatusOptions()
    {
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes();
        $statusOptions = [];
        foreach ($statusTypes as $status) {
            if ($status['id_order_line_status_type'] != $this->object->id) {
                $statusOptions[] = [
                    'id_status' => $status['id_order_line_status_type'],
                    'name' => $status['name'],
                ];
            }
        }

        return $statusOptions;
    }


    public function getFieldsValue($obj)
    {
        $fields = parent::getFieldsValue($obj);
        $available_status = OrderLineStatusType::getAvailableStatusListBystatusId($this->object->id);
        foreach ($available_status as $id) {
            $fields['AvailableData_' .  $id] = true;
        }
        return $fields;
    }


}
