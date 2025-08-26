<?php

use LDAP\Result;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminManifestStatusTypeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_manifest_status_type';
        $this->className = 'ManifestStatusType';
        $this->identifier = 'id_manifest_status_type';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->position_identifier = 'position';

        parent::__construct();

        $this->fields_list = [
            'id_manifest_status_type' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Nom'),
                'filter_key' => 'a!name'
            ],
            'allowed_manifest_type' => [
                'title' => $this->l('Type'),
                'filter_key' => 'a!allowed_manifest_type',
                'type' => 'select',
                'list' => [
                    'pickup' => $this->l('Collecte'),
                    'returns' => $this->l('Retours')
                ]
            ],
            'position' => [
                'title' => $this->l('Position'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->l('Actif'),
                'align' => 'center',
                'type' => 'bool',
                'filter_key' => 'a!active',
                'active' => 'status'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Supprimer la sélection'),
                'confirm' => $this->l('Supprimer les éléments sélectionnés ?')
            ]
        ];
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Type de statut de manifeste'),
                'icon' => 'icon-cog'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nom'),
                    'name' => 'name',
                    'required' => true,
                    'maxlength' => 255
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->l('Transitions de statut de manifeste autorisées'),
                    'name' => 'allowed_manifest_status_type_ids',
                    'values' => [
                        'query' => $this->getManifestStatusOptions(),
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->l('Types de statut de ligne de commande autorisés'),
                    'name' => 'allowed_order_line_status_type_ids',
                    'values' => [
                        'query' => $this->getOrderLineStatusOptions(),
                        'id' => 'id_order_line_status_type',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->l('Types de statut de ligne de commande suivants'),
                    'name' => 'next_order_line_status_type_ids',
                    'values' => [
                        'query' => $this->getOrderLineStatusOptions(),
                        'id' => 'id_order_line_status_type',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Type de manifeste'),
                    'name' => 'allowed_manifest_type',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'pickup', 'name' => $this->l('Collecte')],
                            ['id' => 'returns', 'name' => $this->l('Retours')]
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Autoriser la modification'),
                    'name' => 'allowed_modification',
                    'values' => [
                        ['id' => 'allowed_modification_on', 'value' => 1, 'label' => $this->l('Oui')],
                        ['id' => 'allowed_modification_off', 'value' => 0, 'label' => $this->l('Non')]
                    ]
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Autoriser la suppression'),
                    'name' => 'allowed_delete',
                    'values' => [
                        ['id' => 'allowed_delete_on', 'value' => 1, 'label' => $this->l('Oui')],
                        ['id' => 'allowed_delete_off', 'value' => 0, 'label' => $this->l('Non')]
                    ]
                ],

                [
                    'type' => 'switch',
                    'label' => $this->l('Actif'),
                    'name' => 'active',
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Enregistrer')
            ]
        ];

        return parent::renderForm();
    }

    public function processAdd()
    {
        $_POST = $this->processMultiSelectFields($_POST);
        return parent::processAdd();
    }

    public function processUpdate()
    {
        $_POST = $this->processMultiSelectFields($_POST);
        return parent::processUpdate();
    }

    private function processMultiSelectFields($post)
    {
        $fields = ['allowed_manifest_status_type_ids', 'allowed_order_line_status_type_ids', 'next_order_line_status_type_ids'];

        foreach ($fields as $field) {
            $values = [];
            foreach ($post as $key => $value) {
                if (strpos($key, $field . '_') === 0) {
                    $values[] = str_replace($field . '_', '', $key);
                }
            }
            $post[$field] = implode(',', $values);
        }

        return $post;
    }


    public function getFieldsValue($obj)
    {
        // Start with default values
        $fields = parent::getFieldsValue($obj);

        // Multi-select checkbox fields
        $multiSelectFields = [
            'allowed_manifest_status_type_ids',
            'allowed_order_line_status_type_ids',
            'next_order_line_status_type_ids'
        ];

        foreach ($multiSelectFields as $field) {
            if (!empty($obj->$field)) {
                $ids = explode(',', $obj->$field);
                foreach ($ids as $id) {
                    $fields[$field . '_' . $id] = true;
                }
            }
        }

        return $fields;
    }

    private function getManifestStatusOptions()
    {
        $sql = 'SELECT id_manifest_status_type as id, name FROM ' . _DB_PREFIX_ . 'mv_manifest_status_type WHERE active = 1 ORDER BY position';
        return Db::getInstance()->executeS($sql) ?: [];
    }

    private function getOrderLineStatusOptions()
    {

        return OrderLineStatusType::getAllActiveStatusTypes();
    }
}
