<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorManifestModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $manifest_id = (int)Tools::getValue('id');
        if (!$manifest_id) {
            $this->errors[] = $this->module->l('ID du manifeste invalide', 'manifest');
            return $this->displayTemplate();
        }

        $manifest = new Manifest($manifest_id);
        if (!Validate::isLoadedObject($manifest)) {
            $this->errors[] = $this->module->l('Manifeste introuvable', 'manifest');
            return $this->displayTemplate();
        }

        $group_list = $this->context->customer->getGroups();
        $status = $manifest->id_manifest_status;
        $vendor = VendorHelper::getVendorByCustomer($this->context->customer->id);
        $isvendor = !empty($vendor) ? true : false;
        // Refund/Return flow (id_manifest_type = 2)
        if ($manifest->id_manifest_type == 2) {
            $collect_group = (int)Configuration::get('MULTIVENDOR_COLLECT_GROUP');
            $receive_group = (int)Configuration::get('MULTIVENDOR_RECEIVE_GROUP');
            $status_processing = (int)Configuration::get('MULTIVENDOR_STATUS_REFUND_PROCESSING');
            $status_collected = (int)Configuration::get('MULTIVENDOR_STATUS_REFUND_COLLECTED');
            $status_refunded = (int)Configuration::get('mv_returns');

            if ($status == $status_processing && in_array($collect_group, $group_list)) {
                $manifest->id_manifest_status = $status_collected;
                if ($manifest->update()) {
                    $this->success[] = sprintf(
                        $this->module->l('Tous les produits du bon de retour %s ont été collectés avec succès', 'manifest'),
                        $manifest->reference
                    );
                    $status = $status_collected;
                }
            } elseif ($status == $status_collected && $isvendor && $vendor['id_vendor'] == $manifest->id_vendor) {
                $manifest->id_manifest_status = $status_refunded;
                if ($manifest->update()) {
                    $this->success[] = sprintf(
                        $this->module->l('Tous les produits du bon de retour %s ont été remboursés avec succès', 'manifest'),
                        $manifest->reference
                    );
                }
            } else {
                $this->errors[] = $this->module->l('Vous n\'avez pas l\'autorisation d\'effectuer cette action', 'manifest');
            }
        }
        // Standard pickup flow (id_manifest_type = 1)
        else {
            $collect_group = (int)Configuration::get('MULTIVENDOR_COLLECT_GROUP');
            $receive_group = (int)Configuration::get('MULTIVENDOR_RECEIVE_GROUP');
            $status_collect = (int)Configuration::get('mv_pickup');
            $status_collected = (int)Configuration::get('MULTIVENDOR_STATUS_COLLECTED');
            $status_received = (int)Configuration::get('MULTIVENDOR_STATUS_RECEIVED');

            if ($status == $status_collect && in_array($collect_group, $group_list)) {
                $manifest->id_manifest_status = $status_collected;
                if ($manifest->update()) {
                    $this->success[] = sprintf(
                        $this->module->l('Tous les produits du bon de collecte %s ont été récupérés avec succès', 'manifest'),
                        $manifest->reference
                    );
                    $status = $status_collected;
                }
            } elseif ($status == $status_collected && in_array($receive_group, $group_list)) {
                $manifest->id_manifest_status = $status_received;
                if ($manifest->update()) {
                    $this->success[] = sprintf(
                        $this->module->l('Tous les produits du bon de collecte %s ont été reçus avec succès', 'manifest'),
                        $manifest->reference
                    );
                    $status = $status_received;
                }
            } else {
                $this->errors[] = $this->module->l('Vous n\'avez pas l\'autorisation d\'effectuer cette action', 'manifest');
            }
        }

        $this->displayTemplate($manifest_id, $status, $status_collected, in_array($receive_group, $group_list), $isvendor);
    }

    private function displayTemplate($manifest_id = 0, $status = 0, $status_collected = 0, $can_receive = false, $isvendor = false)
    {
        $statusobj = new ManifestStatusType($status);

        $this->context->smarty->assign([
            'success' => $this->success,
            'errors' => $this->errors,
            'manifest_id' => $manifest_id,
            'manifest_status' => $status,
            'name' => $statusobj->name,
            'status_collected' => $status_collected,
            'can_receive' => $can_receive,
            'isvendor' => $isvendor
        ]);
        $this->setTemplate('module:multivendor/views/templates/front/_qrscanned.tpl');
    }
}
