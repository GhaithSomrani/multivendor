<?php

/**
 * VendorManifestPDF File - classes/pdf/VendorManifestPDF.php
 * This should be saved as: classes/pdf/VendorManifestPDF.php
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorManifestPDF extends HTMLTemplate
{
    public $objects;
    public $template_name;
    public $filename;

    /**
     * Constructor
     *
     * @param array $objects Data for PDF
     * @param string $template Template name
     */
    public function __construct($objects, $template)
    {
        $this->objects = $objects;
        $this->template_name = $template;
        $this->filename = isset($objects['filename']) ? $objects['filename'] : 'pickup_manifest.pdf';

        // Set title and date as required by HTMLTemplate
        $this->title = 'Pickup Manifest';
        $this->date = date('Y-m-d H:i:s');

        // Initialize smarty and shop from context
        $this->smarty = Context::getContext()->smarty;
        $this->shop = Context::getContext()->shop;
    }

    /**
     * Returns the template filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Returns the bulk filename (required by HTMLTemplate)
     *
     * @return string
     */
    public function getBulkFilename()
    {
        return 'pickup_manifests_bulk_' . date('YmdHis') . '.pdf';
    }

    /**
     * Returns the template's HTML content for single manifest with multiple items
     *
     * @return string HTML content
     */
    public function getContent()
    {
        // Get manifests from objects array
        $manifests = isset($this->objects['manifests']) ? $this->objects['manifests'] : [];



        $manifestData = $this->prepareSingleManifestData($manifests);

        $this->smarty->assign($manifestData);

        $template_path = _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/' . $this->template_name . '.tpl';

        return $this->smarty->fetch($template_path);
    }

    /**
     * Prepare data for single manifest with multiple items
     */
    protected function prepareSingleManifestData($manifests)
    {
        $manifest_id = 'MF' . date('YmdHis') . rand(100, 999);
        $vendor_info = isset($manifests[0]['vendor']) ? $manifests[0]['vendor'] : [];
        $warehouse_address = isset($manifests[0]['warehouse_address']) ? $manifests[0]['warehouse_address'] : [];

        $total_items = 0;
        $total_packages = count($manifests);
        $total_weight = 0;
        $total_value = 0;
        $unique_orders = [];

        foreach ($manifests as $manifest) {
            if (isset($manifest['orderDetail']['product_quantity'])) {
                $total_items += (int)$manifest['orderDetail']['product_quantity'];
            }

            if (isset($manifest['orderDetail']['product_weight']) && isset($manifest['orderDetail']['product_quantity'])) {
                $item_weight = (float)$manifest['orderDetail']['product_weight'] * (int)$manifest['orderDetail']['product_quantity'];
                $total_weight += $item_weight;
            } else {
                $total_weight += 0.5;
            }

            if (isset($manifest['orderDetail']['total_price_tax_incl'])) {
                $total_value += (float)$manifest['orderDetail']['total_price_tax_incl'];
            }

            if (isset($manifest['order']['id_order'])) {
                $unique_orders[$manifest['order']['id_order']] = true;
            }
        }

        $total_orders = count($unique_orders);

        if ($total_weight == 0) {
            $total_weight = $total_items * 0.5;
        }

        return [
            'manifest_id' => $manifest_id,
            'vendor_info' => $vendor_info,
            'warehouse_address' => $warehouse_address,
            'pickup_date' => date('Y-m-d'),
            'pickup_time' => date('H:i'),
            'total_items' => $total_items,
            'total_orders' => $total_orders,
            'manifests' => $manifests,
            'summary' => [
                'total_packages' => $total_packages,
                'total_weight' => $total_weight,
                'total_value' => $total_value,
            ],
        ];
    }
    /**
     * Render PDF with proper error handling
     */
    public function render($mode = 'D')
    {
       $pdf = new PDF($this, PDF::TEMPLATE_INVOICE, $this->smarty, 'P');

            if ($mode === 'D') {
                $pdf->render($this->getFilename(), true);
            } else {
                $pdf->render($this->getFilename(), false);
            }
    }
}
