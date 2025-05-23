<?php
// /**
//  * Fixed PDF Override for Multivendor Module
//  * Save this as: override/classes/pdf/PDF.php
//  */

// if (!defined('_PS_VERSION_')) {
//     exit;
// }

// class PDF extends PDFCore
// {
//     const TEMPLATE_MANIFEST = 'Manifest';

//     /**
//      * Override the getTemplateObject method to handle our custom template
//      */
//     public function getTemplateObject($object)
//     {
//         // Handle our custom manifest template
//         if ($this->template === self::TEMPLATE_MANIFEST) {
//             return new HTMLTemplateManifest($object, $this->smarty, $this->send_bulk_flag);
//         }

//         // For all other templates, use parent method
//         return parent::getTemplateObject($object);
//     }
// }

