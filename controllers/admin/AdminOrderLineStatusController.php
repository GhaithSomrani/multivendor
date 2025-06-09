<?php

/**
 * Admin Order Line Status Controller
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminOrderLineStatusController extends ModuleAdminController
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

        // CRITICAL FIX: This must be 'position' for native PrestaShop positioning
        $this->position_identifier = 'position';

        parent::__construct();

        // Add debugging to see what's happening
        if (Tools::getValue('ajax') && Tools::getValue('action') == 'updatePositions') {
            PrestaShopLogger::addLog('Position update attempt detected', 1, null, 'AdminOrderLineStatus');
        }

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
                'position' => 'position',  // This tells PrestaShop this column is for positioning
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

 public function ajaxProcessUpdatePositions()
{
    $way = (int)Tools::getValue('way');
    $id = (int)Tools::getValue('id');
    
    if (!$id || ($way !== 0 && $way !== 1)) {
        die('Error: Invalid parameters');
    }

    try {
        // Get current object
        $object = new $this->className($id);
        if (!Validate::isLoadedObject($object)) {
            die('Error: Object not found');
        }

        $current_position = (int)$object->position;
        
        if ($way == 1) {
            // Move up (decrease position)
            $new_position = $current_position - 1;
            if ($new_position < 1) {
                die('OK'); // Already at top
            }
        } else {
            // Move down (increase position) 
            $new_position = $current_position + 1;
            
            // Check if there's a next item
            $max_position = (int)Db::getInstance()->getValue(
                'SELECT MAX(position) FROM `' . _DB_PREFIX_ . $this->table . '`'
            );
            if ($new_position > $max_position) {
                die('OK'); // Already at bottom
            }
        }

        // Find the item at the target position
        $target_item = Db::getInstance()->getRow(
            'SELECT `' . $this->identifier . '`, `position` 
             FROM `' . _DB_PREFIX_ . $this->table . '` 
             WHERE `position` = ' . (int)$new_position
        );

        if ($target_item) {
            // Swap positions - Update target item to current position
            $swap_result = Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . $this->table . '` 
                 SET `position` = ' . (int)$current_position . ' 
                 WHERE `' . $this->identifier . '` = ' . (int)$target_item[$this->identifier]
            );
            
            if (!$swap_result) {
                die('Error: Failed to update target item position');
            }
        }

        // Update current item to new position
        $update_result = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . $this->table . '` 
             SET `position` = ' . (int)$new_position . ' 
             WHERE `' . $this->identifier . '` = ' . (int)$id
        );

        if (!$update_result) {
            die('Error: Failed to update current item position');
        }

        die('OK');
        
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
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
                            'id' => 'is_vendor_allowed_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'is_vendor_allowed_off',
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
                            'id' => 'is_admin_allowed_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'is_admin_allowed_off',
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
                            'id' => 'affects_commission_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'affects_commission_off',
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

    public function processAdd()
    {
        $maxPosition = Db::getInstance()->getValue(
            'SELECT MAX(position) FROM `' . _DB_PREFIX_ . $this->table . '`'
        );
        $_POST['position'] = (int)$maxPosition + 1;

        return parent::processAdd();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_status'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
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
             FROM `' . _DB_PREFIX_ . 'mv_order_line_status` 
             WHERE id_order_line_status_type = ' . (int)$id_status_type
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
}
