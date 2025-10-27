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
            'relation' => 'manifest_id',
            'status' => 'id_manifest_status',
            'statusObject' => 'ManifestStatusType',
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'VendorPayment',
            'parent_id' => 'id_vendor_payment',
            'child' => 'VendorTransaction',
            'child_id' => 'id_vendor_transaction',
            'relation' => 'vendor_payment_id',
            'status' => 'status',
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']
        ],
        [
            'parent' => 'VendorTransaction',
            'parent_id' => 'id_vendor_transaction',
            'child' => 'VendorOrderDetail',
            'child_id' => 'id_order_detail',
            'relation' => 'order_detail_id',
            'status' => 'status',
            'statusObject' => null,
            'action' => ['add', 'update', 'delete']

        ],
        [
            'parent' => 'ManifestDetails',
            'parent_id' => 'id_manifest_details',
            'child' => 'VendorOrderDetail',
            'child_id' => 'id_order_detail',
            'relation' => 'id_order_details',
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
                $changedBy = 'PR/' . $employee->firstname . ' ' . $employee->lastname;
                break;

            case 'customer':
                $customer = new Customer((int)$context->customer->id);
                $changedBy = 'VD/' . $customer->firstname . ' ' . $customer->lastname;
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

    public static function getSpecificEntity($parent)
    {
        return array_filter(self::ENTITIES, function ($entity) use ($parent) {
            return $entity['parent'] === $parent;
        });
    }

    public static function getChildren()
    {
        return array_column(self::ENTITIES, 'child');
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


    public static function isLoggable($className)
    {
        return in_array($className, self::getAllParents());
    }

    public static function ischildisLoggable($className)
    {
        return in_array($className, self::getChildren());
    }
}
