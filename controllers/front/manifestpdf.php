<?php
/**
 * Class MultivendorManifestpdfModuleFrontController
 * 
 * Generates and outputs PDF for manifests
 */
class MultivendorManifestpdfModuleFrontController extends ModuleFrontController
{
    use VendorInterfaceTrait;

    /** @var bool */
    public $ssl = true;

    /** @var bool */
    public $auth = true;

    /** @var string */
    public $authRedirection = 'authentication';

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        if (!$this->initVendorContext()) {
            Tools::redirect($this->context->link->getPageLink('authentication'));
        }

        $this->validateManifestAccess();

        // Prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Validate manifest access
     */
    private function validateManifestAccess()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $token = Tools::getValue('token');

        if (!$id_manifest) {
            $this->redirectToError('Missing manifest ID');
        }

        // Verify token for security
        $expected_token = Tools::getToken(false);
        if (!$token || $token !== $expected_token) {
            $this->redirectToError('Invalid security token');
        }

        // Verify manifest belongs to vendor
        if (!$this->validateVendorAccess('manifest', $id_manifest)) {
            $this->redirectToError('Access denied');
        }
    }

    /**
     * Redirect to error page
     * 
     * @param string $message
     */
    private function redirectToError($message)
    {
        PrestaShopLogger::addLog('Manifest PDF Access Denied: ' . $message, 3);

        if ($this->ajax) {
            header('HTTP/1.1 403 Forbidden');
            die(json_encode(['error' => $message]));
        }

        $this->context->controller->errors[] = $this->l('Access denied');
        Tools::redirect($this->context->link->getPageLink('my-account'));
    }

    /**
     * Process request and generate PDF
     */
    public function postProcess()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');

        try {
            $manifest = new Manifest($id_manifest);

            if (!Validate::isLoadedObject($manifest)) {
                throw new Exception('Manifest not found');
            }

            $this->generatePDF($manifest);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Manifest PDF Error: ' . $e->getMessage(), 3);

            if ($this->ajax) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(['error' => 'Error generating PDF']));
            }

            $this->context->controller->errors[] = $this->l('Error generating PDF');
            Tools::redirect($this->context->link->getPageLink('my-account'));
        }
    }

    /**
     * Generate PDF for manifest
     * 
     * @param Manifest $manifest
     */
    private function generatePDF($manifest)
    {
        try {
            // Log access
            $this->logVendorActivity(
                'manifest_pdf_generated',
                'PDF generated for manifest: ' . $manifest->reference,
                $manifest->id
            );

            // Create PDF template
            $pdf_template = new HTMLTemplateManifestPDF($manifest, $this->context->smarty);

            // Create PDF instance with template
            $pdf = new PDF($pdf_template, 'L', Context::getContext()->smarty);
            // Get filename
            $filename = $pdf_template->getFilename();

            // Set headers for download
            $this->setPDFHeaders($filename);

            // Generate and output PDF
            $pdf->render($filename, 'D'); // 'D' = force download

            exit;
        } catch (Exception $e) {
            throw new Exception('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Set PDF download headers
     * 
     * @param string $filename
     */
    private function setPDFHeaders($filename)
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
    }

    /**
     * Handle bulk PDF generation
     */
    public function processBulkPDF()
    {
        $manifest_ids = Tools::getValue('manifest_ids');

        if (!$manifest_ids || !is_array($manifest_ids)) {
            $this->redirectToError('No manifests selected');
        }

        try {
            $manifests = [];

            foreach ($manifest_ids as $id) {
                $id = (int)$id;

                if (!$this->validateVendorAccess('manifest', $id)) {
                    continue;
                }

                $manifest = new Manifest($id);
                if (Validate::isLoadedObject($manifest)) {
                    $manifests[] = $manifest;
                }
            }

            if (empty($manifests)) {
                throw new Exception('No valid manifests found');
            }

            $this->generateBulkPDF($manifests);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Bulk Manifest PDF Error: ' . $e->getMessage(), 3);
            $this->redirectToError('Error generating bulk PDF');
        }
    }

    /**
     * Generate bulk PDF for multiple manifests
     * 
     * @param array $manifests
     */
    private function generateBulkPDF($manifests)
    {
        try {
            // Log bulk access
            $references = array_map(function ($m) {
                return $m->reference;
            }, $manifests);
            $this->logVendorActivity(
                'bulk_manifest_pdf_generated',
                'Bulk PDF generated for manifests: ' . implode(', ', $references)
            );

            // Generate bulk PDF
            $pdf = HTMLTemplateManifestPDF::generateBulkPDF($manifests, $this->context->smarty);

            // Get filename
            $filename = $this->getBulkFilename($manifests);

            // Set headers
            $this->setPDFHeaders($filename);

            // Output PDF
            $pdf->render($filename, 'D');

            exit;
        } catch (Exception $e) {
            throw new Exception('Failed to generate bulk PDF: ' . $e->getMessage());
        }
    }
    private function getBulkFilename($manifests)
    {
        if (count($manifests) === 1) {
            return 'Manifest_' . $manifests[0]->reference . '_' . date('YmdHis') . '.pdf';
        }

        return 'Manifests_Bulk_' . date('YmdHis') . '.pdf';
    }
    /**
     * Process AJAX requests
     */
    public function displayAjax()
    {
        $action = Tools::getValue('action');

        switch ($action) {
            case 'generate':
                $this->postProcess();
                break;

            case 'bulk':
                $this->processBulkPDF();
                break;

            default:
                header('HTTP/1.1 400 Bad Request');
                die(json_encode(['error' => 'Invalid action']));
        }
    }

    /**
     * Display content (not used as we output PDF directly)
     */
    public function initContent()
    {
        // Handle AJAX requests
        if ($this->ajax) {
            $this->displayAjax();
            return;
        }

        // Regular request - process PDF generation
        $this->postProcess();
    }

    /**
     * Check if request is for bulk operation
     * 
     * @return bool
     */
    private function isBulkRequest()
    {
        return Tools::getValue('bulk') && Tools::getValue('manifest_ids');
    }

    /**
     * Get error message for user
     * 
     * @param string $key
     * @return string
     */
    private function getErrorMessage($key)
    {
        $messages = [
            'access_denied' => $this->l('You do not have access to this manifest'),
            'manifest_not_found' => $this->l('Manifest not found'),
            'invalid_token' => $this->l('Invalid security token'),
            'pdf_generation_failed' => $this->l('Failed to generate PDF document'),
            'bulk_generation_failed' => $this->l('Failed to generate bulk PDF document')
        ];

        return $messages[$key] ?? $this->l('An error occurred');
    }
}
