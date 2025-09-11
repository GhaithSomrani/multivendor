<?php

/**
 * Admin Manifest Type Controller - NEW
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminManifestTypeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_manifest_type';
        $this->className = 'ManifestType';
        $this->lang = false;
        $this->identifier = 'id_manifest_type';
        $this->_defaultOrderBy = 'name';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $this->fields_list = [
            'id_manifest_type' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Name'),
                'filter_key' => 'a!name'
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Manifest Type'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'maxlength' => 255
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function processDelete()
    {
        try {
            return parent::processDelete();
        } catch (PrestaShopException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
}
