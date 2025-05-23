<?php

/**
 * Fixed VendorManifestPDF Class with proper SVG barcode support
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
        $this->filename = isset($objects['filename']) ? $objects['filename'] : 'manifest.pdf';
        $this->context = Context::getContext();
        $this->pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8');
        $this->setupPdf();
    }

    /**
     * Setup PDF document properties
     */
    protected function setupPdf()
    {
        $this->pdf->SetCreator('PrestaShop Multivendor');
        $this->pdf->SetAuthor('Multivendor Module');
        $this->pdf->SetTitle('Pickup Manifest');
        $this->pdf->SetSubject('Vendor Pickup Manifest');
        $this->pdf->SetKeywords('pickup, manifest, vendor, prestashop');

        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetAutoPageBreak(true, 15);

        $this->pdf->SetFont('helvetica', '', 10);

        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    /**
     * Required method: Get the content for the PDF
     *
     * @return string
     */
    public function getContent()
    {
        $shop_address = Configuration::get('PS_SHOP_NAME') . "\n";
        $shop_address .= Configuration::get('PS_SHOP_ADDR1');
        if (Configuration::get('PS_SHOP_ADDR2')) {
            $shop_address .= ' ' . Configuration::get('PS_SHOP_ADDR2');
        }
        $shop_address .= "\n" . Configuration::get('PS_SHOP_CODE') . ' ' . Configuration::get('PS_SHOP_CITY');
        if (Configuration::get('PS_SHOP_COUNTRY')) {
            $shop_address .= "\n" . Configuration::get('PS_SHOP_COUNTRY');
        }

        $this->assignCommonHeaderData();

        $manifest = $this->objects['manifests'];



        $this->context->smarty->assign([
            'manifests' => $manifest,
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s'),
            'shop_address' => $shop_address,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE'),
            'shop_fax' => Configuration::get('PS_SHOP_FAX'),
            'shop_details' => Configuration::get('PS_SHOP_DETAILS'),
            'free_text' => Configuration::get('PS_SHOP_FREE_TEXT')
        ]);

        $template_path = _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/' . $this->template . '.tpl';

        if (!file_exists($template_path)) {
            throw new Exception('Template file not found: ' . $template_path);
        }

        return $this->context->smarty->fetch($template_path);
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
     * Generate PDF and output it with enhanced barcode support
     *
     * @param bool $display Whether to display the PDF
     * @return string PDF content
     */
    public function render($display = true)
    {
        $this->pdf->AddPage();

        $content = $this->getContent();

        $this->pdf->writeHTML($content, true, false, true, false, '');

        if ($display) {
            $this->pdf->Output($this->filename, 'D');
            exit;
        } else {
            return $this->pdf->Output($this->filename, 'S');
        }
    }
}
