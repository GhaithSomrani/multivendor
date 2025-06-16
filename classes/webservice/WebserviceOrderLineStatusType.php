<?php
class WebserviceOrderLineStatusType implements WebserviceSpecificManagementInterface
{
    protected $outputFormat = 'JSON';
    protected $wsObject;
    protected $method = 'GET';
    protected $urlSegment = [];

    public function getObjectsNodeName()
    {
        return 'order_line_status_types';
    }

    public function getObjectNodeName()
    {
        return 'order_line_status_type';
    }

    public function setObjectOutput($outputFormat)
    {
        $this->outputFormat = $outputFormat;
        return $this;
    }

    public function getObjectOutput()
    {
        return $this->outputFormat;
    }

    public function setWsObject($wsObject)
    {
        $this->wsObject = $wsObject;
        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    public function getObjectMethod()
    {
        return $this->method;
    }

    public function setWSObjectMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function getWSObjectMethod()
    {
        return $this->method;
    }

    public function getWebserviceParameters($resource = null)
    {
        return [
            'objectsNodeName' => $this->getObjectsNodeName(),
            'objectNodeName' => $this->getObjectNodeName(),
            'fields' => [
                'id_order_line_status_type' => [],
                'name' => [],
                'color' => [],
                'is_vendor_allowed' => [],
                'is_admin_allowed' => [],
                'affects_commission' => [],
                'commission_action' => [],
                'position' => [],
                'active' => []
            ]
        ];
    }

    public function setUrlSegment($segments)
    {
        $this->urlSegment = $segments;
        return $this;
    }

    public function getUrlSegment()
    {
        return $this->urlSegment;
    }

    public function manage()
    {
        switch ($this->method) {
            case 'GET':
                return $this->executeGet();
            default:
                throw new WebserviceException('Method not allowed', 405);
        }
    }

    protected function executeGet()
    {
        try {
            $vendor_only = (bool)Tools::getValue('vendor_only', false);
            $admin_only = (bool)Tools::getValue('admin_only', false);

            $statusTypes = OrderLineStatusType::getAllActiveStatusTypes($vendor_only, $admin_only);

            if (empty($statusTypes)) {
                $statusTypes = [];
            }

            return [
                'order_line_status_types' => $statusTypes
            ];
        } catch (Exception $e) {
            throw new WebserviceException($e->getMessage(), 500);
        }
    }

    public function getContent()
    {
        return $this->manage();
    }

    public function getSize()
    {
        try {
            return count(OrderLineStatusType::getAllActiveStatusTypes());
        } catch (Exception $e) {
            return 0;
        }
    }

    public function validateFields()
    {
        return true;
    }

    public function addSQL($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit)
    {
        return true;
    }
}
