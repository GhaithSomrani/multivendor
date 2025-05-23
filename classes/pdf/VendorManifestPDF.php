<?php

/**
 * VendorManifestPDF Class
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class VendorManifestPDF extends HTMLTemplate
{
    public $objects;
    public $template;
    public $smarty;
    protected $pdf;
    public $filename;
    protected $context;

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
        $this->objects = $objects;
        $this->template = $template;
        $this->smarty = $smarty;
        $this->filename = isset($objects['filename']) ? $objects['filename'] : 'document.pdf';
        $this->context = Context::getContext();

        $this->pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8');
        $this->setupPdf();
    }

    /**
     * Setup PDF document properties
     */
    protected function setupPdf()
    {
        $this->pdf->SetCreator('PrestaShop');
        $this->pdf->SetAuthor('Multivendor Module');
        $this->pdf->SetTitle('Pickup Manifest');

        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetAutoPageBreak(true, 10);

        $this->pdf->SetFont('helvetica', '', 10);
    }

    /**
     * Required method: Get the content for the PDF
     *
     * @return string
     */
    public function getContent()
    {
        $manifests = $this->objects['manifests'];
        $content = '';

        foreach ($manifests as $index => $manifest) {
            $this->context->smarty->assign($manifest);

            $this->context->smarty->assign([
                'current_date' => date('Y-m-d'),
                'current_time' => date('H:i:s')
            ]);

            $template_path = 'module:multivendor/views/templates/pdf/' . $this->template . '.tpl';
            $content .= $this->context->smarty->fetch($template_path);
        }

        return $content;
    }

    /**
     * Required method: Get the filename for the PDF
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Required method: Get the bulk filename for multiple PDFs
     *
     * @return string
     */
    public function getBulkFilename()
    {
        return 'vendor_manifests_' . date('Y-m-d_H-i-s') . '.pdf';
    }

    /**
     * Generate PDF and output it
     *
     * @param bool $display Whether to display the PDF
     * @return string PDF content
     */
    public function render($display = true)
    {
        $manifests = $this->objects['manifests'];
        
        // $headerContent = self::getFooter();
        $footerContent = self::getHeader();

        foreach ($manifests as $index => $manifest) {
            $this->pdf->AddPage();

            if (!empty($headerContent)) {
                $this->pdf->writeHTML($headerContent, true, false, true, false, '');
            }

            $this->context->smarty->assign($manifest);

            $this->context->smarty->assign([
                'current_date' => date('Y-m-d'),
                'current_time' => date('H:i:s')
            ]);

            $template_path = 'module:multivendor/views/templates/pdf/' . $this->template . '.tpl';
            $content = $this->context->smarty->fetch($template_path);

            $this->pdf->writeHTML($content, true, false, true, false, '');

            if (!empty($footerContent)) {
                $this->pdf->writeHTML($footerContent, true, false, true, false, '');
            }
        }

        if ($display) {
            $this->pdf->Output($this->filename, 'D');
            exit;
        } else {
            return $this->pdf->Output($this->filename, 'S');
        }
    }


}
