<?php

/**
 * VendorManifestPDF Class extending HTMLTemplate
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorManifestPDF extends HTMLTemplate
{
    public $objects;
    public $template;
    public $filename;
    public $orientation;

    /**
     * Constructor
     *
     * @param array $objects Data for PDF
     * @param string $template Template name
     * @param object $smarty Smarty instance (not used in HTMLTemplate)
     * @param string $orientation Page orientation
     */
    public function __construct($objects, $template, $smarty = null, $orientation = 'P')
    {
        $this->objects = $objects;
        $this->template = $template;
        $this->orientation = $orientation;
        $this->filename = isset($objects['filename']) ? $objects['filename'] : 'pickup_manifest.pdf';
        
        $manifestsData = isset($objects['manifests']) ? $objects['manifests'] : [];
        if (!is_array($manifestsData)) {
            $manifestsData = [];
        }
        
        parent::__construct();
        
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
     * Get the shop information for PDF header/footer
     *
     * @return array
     */
    public function getShop()
    {
        // This method is used by HTMLTemplate for shop data in header/footer
        return Context::getContext()->shop;
    }

    /**
     * Returns the template's HTML content for each manifest
     *
     * @return string HTML content
     */
    public function getContent()
    {
        $html = '';
        
        // Get manifests from objects array
        $manifests = isset($this->objects['manifests']) ? $this->objects['manifests'] : [];
        
        if (!is_array($manifests) || empty($manifests)) {
            return '<div style="text-align: center; padding: 50px;">No manifest data available</div>';
        }
        
        foreach ($manifests as $index => $manifest) {
            // Assign manifest-specific data
            $this->smarty->assign($manifest);
            
            // Add page break if not the first page
            if ($index > 0) {
                $html .= '<pagebreak />';
            }
            
            // Get the template content
            $template_path = _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/' . $this->template . '.tpl';
            
            if (file_exists($template_path)) {
                $html .= $this->smarty->fetch($template_path);
            } else {
                // Fallback to module template path
                try {
                    $html .= $this->smarty->fetch('module:multivendor/views/templates/pdf/' . $this->template . '.tpl');
                } catch (Exception $e) {
                    // If template not found, create basic HTML
                    $html .= $this->getBasicManifestHtml($manifest);
                }
            }
        }
        
        return $html;
    }

    /**
     * Generate basic HTML manifest if template is not found
     *
     * @param array $manifest Manifest data
     * @return string Basic HTML content
     */
    protected function getBasicManifestHtml($manifest)
    {
        $html = '<div style="margin-bottom: 30px; page-break-after: always;">';
        $html .= '<h1 style="text-align: center;">PICKUP MANIFEST</h1>';
        
        if (isset($manifest['pickup_id'])) {
            $html .= '<p><strong>Pickup ID:</strong> ' . htmlspecialchars($manifest['pickup_id']) . '</p>';
        }
        
        if (isset($manifest['order']['reference'])) {
            $html .= '<p><strong>Order Reference:</strong> ' . htmlspecialchars($manifest['order']['reference']) . '</p>';
        }
        
        if (isset($manifest['orderDetail']['product_name'])) {
            $html .= '<p><strong>Product:</strong> ' . htmlspecialchars($manifest['orderDetail']['product_name']) . '</p>';
        }
        
        if (isset($manifest['orderDetail']['product_quantity'])) {
            $html .= '<p><strong>Quantity:</strong> ' . htmlspecialchars($manifest['orderDetail']['product_quantity']) . '</p>';
        }
        
        $html .= '<p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '</div>';
        
        return $html;
    }



    /**
     * Returns the invoice logo
     *
     * @return string Logo path or empty string
     */
    public function getLogo()
    {
        $logo = Configuration::get('PS_LOGO_INVOICE');
        if ($logo && file_exists(_PS_IMG_DIR_ . $logo)) {
            return _PS_IMG_DIR_ . $logo;
        }
        return '';
    }

    /**
     * Get bulk filename for multiple manifests
     *
     * @return string
     */
    public function getBulkFilename()
    {
        return 'pickup_manifests_' . date('YmdHis') . '.pdf';
    }

    /**
     * Generate and output the PDF using PrestaShop's standard method
     *
     * @param string $mode Output mode ('D' for download, 'I' for inline, 'S' for string)
     * @return string|void
     */
    public function render($mode = 'D')
    {
        // Use PrestaShop's PDF class with this HTMLTemplate
        $pdf = new PDF($this, PDF::TEMPLATE_INVOICE, Context::getContext()->smarty, $this->orientation);
        
        // Override the filename
        $pdf->filename = $this->getFilename();
        
        // Generate and output based on mode
        return $pdf->render($mode === 'D');
    }
}