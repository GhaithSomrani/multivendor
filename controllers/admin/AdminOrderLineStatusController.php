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

    public function ajaxProcessUpdatePositions()
    {
        $way = (int)Tools::getValue('way');
        $id = (int)Tools::getValue('id');
        $positions = Tools::getValue($this->table);

        if (is_array($positions)) {
            // Update all positions based on the new order
            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);

                if (isset($pos[2])) {
                    $item_id = (int)$pos[2];
                    $new_position = (int)$position;

                    // Update each item with its new position
                    Db::getInstance()->execute(
                        '
                    UPDATE `' . _DB_PREFIX_ . $this->table . '` 
                    SET `position` = ' . $new_position . ' 
                    WHERE `' . $this->identifier . '` = ' . $item_id
                    );
                }
            }
        } else {
            // Single item position change (using way parameter)
            $object = new $this->className($id);
            if (Validate::isLoadedObject($object)) {
                $current_position = $object->position;

                if ($way == 1) {
                    // Move up (decrease position)
                    $new_position = $current_position - 1;

                    // Find item currently at that position and swap
                    $other_item = Db::getInstance()->getRow(
                        '
                    SELECT `' . $this->identifier . '`, `position` 
                    FROM `' . _DB_PREFIX_ . $this->table . '` 
                    WHERE `position` = ' . (int)$new_position
                    );

                    if ($other_item) {
                        // Swap positions
                        Db::getInstance()->execute(
                            '
                        UPDATE `' . _DB_PREFIX_ . $this->table . '` 
                        SET `position` = ' . (int)$current_position . ' 
                        WHERE `' . $this->identifier . '` = ' . (int)$other_item[$this->identifier]
                        );
                    }

                    Db::getInstance()->execute(
                        '
                    UPDATE `' . _DB_PREFIX_ . $this->table . '` 
                    SET `position` = ' . (int)$new_position . ' 
                    WHERE `' . $this->identifier . '` = ' . (int)$id
                    );
                } elseif ($way == 0) {
                    // Move down (increase position)
                    $new_position = $current_position + 1;

                    // Find item currently at that position and swap
                    $other_item = Db::getInstance()->getRow(
                        '
                    SELECT `' . $this->identifier . '`, `position` 
                    FROM `' . _DB_PREFIX_ . $this->table . '` 
                    WHERE `position` = ' . (int)$new_position
                    );

                    if ($other_item) {
                        // Swap positions
                        Db::getInstance()->execute(
                            '
                        UPDATE `' . _DB_PREFIX_ . $this->table . '` 
                        SET `position` = ' . (int)$current_position . ' 
                        WHERE `' . $this->identifier . '` = ' . (int)$other_item[$this->identifier]
                        );
                    }

                    Db::getInstance()->execute(
                        '
                    UPDATE `' . _DB_PREFIX_ . $this->table . '` 
                    SET `position` = ' . (int)$new_position . ' 
                    WHERE `' . $this->identifier . '` = ' . (int)$id
                    );
                }
            }
        }

        die('OK');
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
        // Your existing renderForm code...
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
                    'hint' => $this->l('Position in the status list (lower numbers appear first)'),
                    'class' => 'fixed-width-sm'
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
    /**
     * Process delete with proper error handling
     */

    public function processDelete()
    {
        $id = Tools::getValue('id_order_line_status_type');
        $status = new OrderLineStatusType($id);

        if (!Validate::isLoadedObject($status)) {
            $this->errors[] = $this->l('Invalid status ID');
            return false;
        }
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            // Simple check - count how many order lines use this status
            $count = Db::getInstance()->getValue(
                '
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'order_line_status` 
            WHERE `status` = "' . pSQL($status->name) . '"'
            );

            if ($count > 0) {
                $this->errors[] = sprintf('Cannot delete "%s" (used by %d order lines)', $status->name, $count);
                return false;
            }
        }

        return parent::processDelete();
    }


    /**
     * Process bulk delete with proper error handling
     */
    public function processBulkDelete()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $errors = [];
            $success = 0;

            foreach ($this->boxes as $id) {
                $status = new OrderLineStatusType($id);
                if (Validate::isLoadedObject($status)) {
                    // Check if used
                    $count = Db::getInstance()->getValue(
                        '
                    SELECT COUNT(*) 
                    FROM `' . _DB_PREFIX_ . 'order_line_status` 
                    WHERE `status` = "' . pSQL($status->name) . '"'
                    );

                    if ($count > 0) {
                        $errors[] = sprintf('Cannot delete "%s" (used by %d order lines)', $status->name, $count);
                    } else {
                        if ($status->delete()) {
                            $success++;
                        }
                    }
                }
            }

            if ($success > 0) {
                $this->confirmations[] = sprintf('%d status(es) deleted successfully.', $success);
            }

            foreach ($errors as $error) {
                $this->errors[] = $error;
            }

            return $success > 0;
        }

        return parent::processBulkDelete();
    }
}
