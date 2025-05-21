<?php
/**
 * VendorManifestPDF Class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorManifestPDF extends PDF
{
    // Define properties
    public $objects;
    public $template;
    protected $smarty;
    protected $pdf;
    public $filename;
    
    /**
     * Constructor
     *
     * @param array $objects Data for PDF
     * @param string $template Template name
     * @param object $smarty Smarty instance
     * @param string $orientation Page orientation
     */
    public function __construct($objects, $template, $smarty, $orientation = 'P')
    {
        // Initialize parent
        parent::__construct($objects, $template, $smarty, $orientation);
        
        // Store properties
        $this->objects = $objects;
        $this->template = $template;
        $this->smarty = $smarty;
        $this->filename = isset($objects['filename']) ? $objects['filename'] : 'document.pdf';
        
        // Initialize TCPDF
        $this->pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8');
        $this->setupPdf();
    }
    
    /**
     * Setup PDF document properties
     */
    protected function setupPdf()
    {
        // Set document information
        $this->pdf->SetCreator('PrestaShop');
        $this->pdf->SetAuthor('Multivendor Module');
        $this->pdf->SetTitle('Pickup Manifest');
        
        // Set default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Set margins
        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetAutoPageBreak(true, 10);
        
        // Set font
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    /**
     * Generate PDF and output it
     *
     * @param bool $display Whether to display the PDF
     * @return string PDF content
     */
    public function render($display = true)
    {
        // Assign data to Smarty template
        $this->smarty->assign($this->objects);
        
        // Get manifests data
        $manifests = $this->objects['manifests'];
        
        // Process each manifest
        foreach ($manifests as $manifest) {
            // Add a new page for each manifest
            $this->pdf->AddPage();
            
            // Assign manifest data to smarty
            $this->smarty->assign($manifest);
            
            // Fetch template content
            $template_path = _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/' . $this->template . '.tpl';
            if (!file_exists($template_path)) {
                throw new PrestaShopException('PDF template not found: ' . $template_path);
            }
            
            $content = $this->smarty->fetch($template_path);
            
            // Write HTML to PDF
            $this->pdf->writeHTML($content, true, false, true, false, '');
        }
        
        // Output the PDF
        if ($display) {
            $this->pdf->Output($this->filename, 'D'); // 'D' means download
            exit;
        } else {
            return $this->pdf->Output($this->filename, 'S'); // 'S' means return as string
        }
    }
}