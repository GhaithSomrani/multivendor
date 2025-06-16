<?php
class WebserviceOrderLineStatusUpdate implements WebserviceSpecificManagementInterface
{
    protected $outputFormat = 'JSON';
    protected $wsObject;
    protected $method = 'POST';
    protected $urlSegment = [];

    public function getObjectsNodeName()
    {
        return 'order_line_status_updates';
    }

    public function getObjectNodeName()
    {
        return 'order_line_status_update';
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
                'orderline_id' => ['required' => true],
                'new_status_id' => ['required' => true],
                'comment' => ['required' => false],
                'success' => [],
                'id_vendor' => [],
                'updated_at' => [],
                'message' => []
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
            $method = $_SERVER['REQUEST_METHOD'];

            if ($method !== 'POST' && $method !== 'PUT') {
                throw new WebserviceException('Only POST and PUT methods are allowed', 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $orderline_id = (int)($input['orderline_id'] ?? Tools::getValue('orderline_id'));
            $new_status_id = (int)($input['new_status_id'] ?? Tools::getValue('new_status_id'));
            $comment = $input['comment'] ?? Tools::getValue('comment', '');

            if (!$orderline_id || !$new_status_id) {
                throw new WebserviceException('Missing required parameters: orderline_id, new_status_id', 400);
            }

            $id_vendor = TransactionHelper::getVendorIdFromOrderDetail($orderline_id);
            if (!$id_vendor) {
                throw new WebserviceException('Order detail not found or not associated with any vendor', 404);
            }

            $statusType = new OrderLineStatusType($new_status_id);
            if (!Validate::isLoadedObject($statusType) || !$statusType->active) {
                throw new WebserviceException('Invalid or inactive status type', 400);
            }

           
            $is_admin = true;

            if ($is_admin && !$statusType->is_admin_allowed) {
                throw new WebserviceException('Admin not allowed to set this status', 403);
            } elseif (!$is_admin && !$statusType->is_vendor_allowed) {
                throw new WebserviceException('Vendor not allowed to set this status', 403);
            }

            if (!$is_admin && !VendorHelper::isChangeable($orderline_id, $id_vendor)) {
                throw new WebserviceException('Status cannot be changed from current state', 403);
            }

            $changed_by = 1; 
            $success = OrderLineStatus::updateStatus(
                $orderline_id,
                $id_vendor,
                $new_status_id,
                $changed_by,
                $comment,
                $is_admin
            );

            if (!$success) {
                throw new WebserviceException('Failed to update order line status', 500);
            }

            return [
                'order_line_status_updates' => [
                    'order_line_status_update' => [
                        'success' => true,
                        'orderline_id' => $orderline_id,
                        'new_status_id' => $new_status_id,
                        'id_vendor' => $id_vendor,
                        'comment' => $comment,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'message' => 'Order line status updated successfully'
                    ]
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
        return 1;
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
