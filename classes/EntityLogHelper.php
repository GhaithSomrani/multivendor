<?php


if (!defined('_PS_VERSION_')) {
    exit;
}


class EntityLogHelper
{

    const ENTITIES = [
        [
            'parent' => 'Manifest',
            'parent_id' => 'id_manifest',
            'child' => 'ManifestDetails',
            'child_id' => 'id_manifest_details',
            'relation' => 'id_manifest',
            'status' => 'id_manifest_status',
            'statusObject' => 'ManifestStatusType',
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'VendorPayment',
            'parent_id' => 'id_vendor_payment',
            'child' => 'VendorTransaction',
            'child_id' => 'id_vendor_transaction',
            'relation' => 'id_vendor_payment',
            'status' => 'status',
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'VendorTransaction',
            'parent_id' => 'id_vendor_transaction',
            'child' => null,
            'child_id' => null,
            'relation' => null,
            'status' => 'status',
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']

        ],
        [
            'parent' => 'ManifestDetails',
            'parent_id' => 'id_manifest_details',
            'child' => null,
            'child_id' => null,
            'relation' => null,
            'status' => null,
            'statusObject' => null,
            'action' => ['add', 'delete']

        ],
        [
            'parent' => 'OrderLineStatusType',
            'parent_id' => 'id_order_line_status_type',
            'child' => null,
            'child_id' => null,
            'relation' => null,
            'status' => 'active',
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'Vendor',
            'parent_id' => 'id_vendor',
            'child' => null,
            'child_id' => null,
            'relation' => null,
            'status' => null,
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'VendorOrderDetail',
            'parent_id' => 'id_order_detail',
            'child' => null,
            'child_id' => null,
            'relation' => null,
            'status' => null,
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'VendorCommission',
            'parent_id' => 'id_vendor_commission',
            'child' => null,
            'child_id' => null,
            'relation' => null,
            'status' => null,
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']
        ]
    ];

    /**
     * Return true if the current request is an API call.
     *
     *
     * @return bool
     */
    public static function isApiCall()
    {
        return (Tools::getValue('ws_key') || Tools::getValue('key')) ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) ||
            (Context::getContext()->controller && Context::getContext()->controller instanceof WebserviceRequestCore) ||
            (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'PrestaShop Webservice') !== false);
    }


    /**
     * Return the user who made the change
     *
     * @return string
     */
    public static function getChangedBy()
    {
        $context = Context::getContext();
        $source = 'default';
        $changedBy = null;

        // Determine the source type first
        if (self::isApiCall()) {
            $source = 'api';
        } elseif ($context->employee && $context->employee->id) {
            $source = 'employee';
        } elseif ($context->customer && $context->customer->id) {
            $source = 'customer';
        }

        // Switch based on the detected source
        switch ($source) {
            // case 'api':
            //     $changedBy = 'OD/' . pSQL(Tools::getValue('changed_by'));
            //     break;

            case 'employee':
                $employee = new Employee((int)$context->employee->id);
                $changedBy = 'Prestashop/' . $employee->firstname . ' ' . $employee->lastname;
                break;

            case 'customer':
                $customer = new Customer((int)$context->customer->id);
                $vendor =  VendorHelper::getVendorByCustomer((int)$context->customer->id);
                if (!empty($vendor)) {
                    $prefix = 'Vendeur/';
                } else {
                    $prefix = 'QrCode/';
                }
                $changedBy = $prefix . $customer->firstname . ' ' . $customer->lastname;
                break;

            default:
                $changedBy = 'System';
                break;
        }

        return $changedBy;
    }
    public static function getAllParents($entities = [])
    {
        if (!empty($entities)) {
            return array_column($entities, 'parent');
        }
        return array_column(self::ENTITIES, 'parent');
    }


    public static function getChildren()
    {
        return array_column(self::ENTITIES, 'child');
    }


    public static function isLoggable($className)
    {
        return in_array($className, self::getAllParents());
    }

    public static function ischildisLoggable($className)
    {
        return in_array($className, self::getChildren());
    }

    public static function getParentName($className)
    {
        return array_values(array_filter(self::ENTITIES, function ($entity) use ($className) {
            return $entity['child'] === $className;
        }))[0]['parent'];
    }
    public static function getParentId($entity_type, $entity_id)
    {
        foreach (self::ENTITIES as $config) {
            if ($config['child'] === $entity_type) {
                $obj = new $entity_type($entity_id);

                return $obj->{$config['relation']};
            }
        }
        return null;
    }

    public static function getRelationField($entity_type)
    {
        foreach (self::ENTITIES as $config) {
            if ($config['child'] === $entity_type) {
                return $config['relation'];
            }
        }
        return null;
    }

    public static function getStatusField($entity_type)
    {
        foreach (self::ENTITIES as $config) {
            if ($config['parent'] === $entity_type) {
                return $config['status'];
            }
        }
        return null;
    }
    public static function getStatusObjectField($entity_type)
    {
        foreach (self::ENTITIES as $config) {
            if ($config['parent'] === $entity_type ) {
                return $config['statusObject'];
            }
        }
        return null;
    }

    public static function isStatusLoggable($entity_type)
    {
        //test if the status in entity !=null 
        foreach (self::ENTITIES as $config) {
            if ($config['parent'] === $entity_type) {
                return $config['status'] !== null;
            }
        }
        return false;
    }

    public static function getContext($backtrace)
    {
        $skipClasses = [
            'Module',
            'multivendor',
            'Hook',
            'HookCore',
            'ObjectModel',
            'ObjectModelCore',
            'Db',
            'DbCore',
            'DbQuery',
            'DbQueryCore',
            'Product',
            'ProductCore',
            'Category',
            'CategoryCore',
            'Customer',
            'CustomerCore',
            'Order',
            'OrderCore',
            'Cart',
            'CartCore',
            'Employee',
            'EmployeeCore',
            'ControllerCore',
            'AudityLog',
            'StatusChangeLog',
            'ChildRelationLog',
        ];

        $skipFunctions = [
            'update',
            'add',
            'delete',
            'save',
            'getFields',
            'validateFields',
            'setLogs',
            'initContent',
            'handleAjaxRequest',
        ];

        $chain = [];

        foreach ($backtrace as $trace) {
            if (isset($trace['class'], $trace['function'])) {
                $baseClass = str_replace('Core', '', $trace['class']);

                if (in_array($trace['class'], $skipClasses) || in_array($baseClass, $skipClasses)) {
                    continue;
                }

                if (in_array($trace['function'], $skipFunctions)) {
                    continue;
                }

                $chain[] = $trace['class'] . '::' . $trace['function'];
            }
        }

        return !empty($chain) ? implode(' > ', array_reverse($chain)) : 'Unknown';
    }
}
