<?php

/**
 * VendorPayment model class
 */
class VendorPayment extends ObjectModel
{
    /** @var int Payment ID */
    public $id;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var float Amount */
    public $amount;

    /** @var string Payment method */
    public $payment_method;

    /** @var string Reference */
    public $reference;

    /** @var string Status */
    public $status;

    /** @var string Creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_vendor_payment',
        'primary' => 'id_vendor_payment',
        'fields' => [
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'payment_method' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 64],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];


    // webservice parameters
    protected $webserviceParameters = [
        'objectsNodeName' => 'vendor_payments',
        'objectNodeName' => 'vendor_payment',
        'fields' => [
            'id_vendor' => ['xlink_resource' => 'vendors'],
            'amount' => [],
            'product' => ['getter' => 'getwsProducts' , 'type' => 'json'],
            'id_supplier' => ['getter' => 'getwsSupplier' , 'type' => 'int'],
        ],
    ];

    public function getwsProducts()
    {
        $products = [];
        $payemntDetails = VendorTransaction::getTransactionsByVendorPayment($this->id);

        foreach ($payemntDetails as $payemntDetail) {
            if ($payemntDetail['order_detail_id']) {
                $vendorOrderDetail = VendorOrderDetail::getByIdOrderDetail($payemntDetail['order_detail_id']);
                if ($vendorOrderDetail) {
                    $unit_price = (float)$vendorOrderDetail['vendor_amount'] / (float) $vendorOrderDetail['product_quantity'];
                    $unit_price = $payemntDetail['transaction_type'] === 'commission' ? $unit_price : -$unit_price;
                    $products[] = [
                        'id_product' => $vendorOrderDetail['product_id'],
                        'id_product_attribute' => $vendorOrderDetail['product_attribute_id'],
                        'quantity' => $vendorOrderDetail['product_quantity'],
                        'unit_amout' => number_format($unit_price, 3),
                    ];
                }
            }
        }

        return  json_encode($products);
    }

    public function getwsSupplier()
    {
        $vendorObj = new Vendor($this->id_vendor);
        return $vendorObj->id_supplier;
    }

    public function save($null_values = false, $auto_date = true)
    {
        $payemntDetails = VendorTransaction::getTransactionsByVendorPayment($this->id);
        $total = 0;
        foreach ($payemntDetails as $payemntDetail) {
            $total = $total + $payemntDetail['vendor_amount'];
        }
        $this->amount = $total;
        return parent::save($auto_date, $null_values);
    }

    /**
     * Get payments for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Payments
     */
    public static function getVendorPayments($id_vendor, $limit = null, $offset = null, $completed_only = true)
    {

        $query = new DbQuery();
        $query->select('*');
        $query->from('mv_vendor_payment');
        $query->where('id_vendor = ' . (int)$id_vendor);
        if ($completed_only) {
            $query->where('status = "completed"');
        }

        $query->orderBy('date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }



    /**
     * Get payment details including the order lines that were paid
     * 
     * @param int $id_vendor_payment Payment ID
     * @return array Payment details with order lines
     */
    public static function getPaymentDetails($id_vendor_payment)
    {
        $query = '
        SELECT vt.*, 
        vod.product_name, 
        vod.product_reference, 
        vod.product_quantity,
        o.reference as order_reference, 
        vod.date_add as order_date,
        vod.id_order,
        vod.id_vendor
        FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
        WHERE vt.id_vendor_payment = ' . (int)$id_vendor_payment . '
        ORDER BY o.date_add DESC';

        $results = Db::getInstance()->executeS($query);

        return $results;
    }

    /**
     * Get vendors payments with order details
     *
     * @param int $id_vendor Vendor ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Payments with order details
     */
    public static function getVendorPaymentsWithDetails($id_vendor, $limit = null, $offset = null)
    {
        $payments = self::getVendorPayments($id_vendor, $limit, $offset);

        foreach ($payments as &$payment) {
            $payment['order_details'] = self::getPaymentDetails($payment['id_vendor_payment']);
        }

        return $payments;
    }

    public static function getLastId()
    {
        $query = new DbQuery();
        $query->select('MAX(id_vendor_payment) as max_id');
        $query->from('mv_vendor_payment');
        $result = Db::getInstance()->getRow($query);
        return $result['max_id'] ?? 0;
    }

    public static function generateReference($id_vendor)
    {
        $vendorObj = new Vendor($id_vendor);
        $vendorName = $vendorObj->shop_name;
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', $vendorName);
        $noVowels = preg_replace('/[aeiouAEIOU]/', '', $cleanName);
        $today = date('Y-m-d');
        $prefix =  strtoupper(substr($noVowels, 0, 3));
        $id = VendorPayment::getLastId() + 1 ?? 0;
        return $prefix . "-" . $today . "-" . str_pad($id, 5, '0', STR_PAD_LEFT);
    }

    public static function getByOrderDetailAndType($order_detail_id, $transaction_type)
    {
        $transaction = VendorTransaction::getByOrderDetailAndType($order_detail_id, $transaction_type);
        return  new self((int)$transaction['id_vendor_payment']);;
    }

    public static function getIdByReference($reference)
    {
        $query = new DbQuery();
        $query->select('id_vendor_payment');
        $query->from('mv_vendor_payment', 'vp');
        $query->where('vp.reference = "' . pSQL($reference) . '"');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
    public static function getAdminlink($id_vendor_payment)
    {
        $link = Context::getContext()->link->getAdminLink('AdminVendorPayments');
        $link .= '&viewmv_vendor_payment&id_vendor_payment=' . (int)$id_vendor_payment;
        return $link;
    }
}
