<?php
class WebserviceOrderLineHistory implements WebserviceSpecificManagementInterface
{
    protected $outputFormat = 'JSON';
    protected $wsObject;
    protected $method = 'GET';
    protected $urlSegment = [];
    public function getObjectsNodeName()
    {
        return 'order_line_histories';
    }

    public function getObjectNodeName()
    {
        return 'order_line_history';
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
                'id_order_detail' => [],
                'id_vendor' => [],
                'old_status_name' => [],
                'new_status_name' => [],
                'old_status_color' => [],
                'new_status_color' => [],
                'comment' => [],
                'changed_by' => [],
                'date' => []
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
        try {
            $id_order_detail = (int)Tools::getValue('id_order_detail');

            if (!$id_order_detail) {
                throw new WebserviceException('Missing required parameter: id_order_detail', 400);
            }

            $history = OrderLineStatusLog::getStatusHistory($id_order_detail);

            // Format for API response
            $formattedHistory = [];
            foreach ($history as $log) {
                $formattedHistory[] = [
                    'id_order_detail' => $log['id_order_detail'],
                    'id_vendor' => $log['id_vendor'],
                    'old_status_name' => $log['old_status_name'] ?: 'Initial',
                    'new_status_name' => $log['new_status_name'],
                    'old_status_color' => $log['old_status_color'],
                    'new_status_color' => $log['new_status_color'],
                    'comment' => $log['comment'],
                    'changed_by' => $log['changed_by_firstname'] . ' ' . $log['changed_by_lastname'],
                    'date' => $log['date_add']
                ];
            }

            return [
                'order_line_histories' => [
                    'order_line_history' => $formattedHistory
                ]
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
        $id_order_detail = (int)Tools::getValue('id_order_detail');
        if (!$id_order_detail) return 0;

        return count(OrderLineStatusLog::getStatusHistory($id_order_detail));
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
