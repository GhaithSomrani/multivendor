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
        'table' => 'vendor_payment',
        'primary' => 'id_vendor_payment',
        'fields' => [
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'payment_method' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get payments for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Payments
     */
    public static function getVendorPayments($id_vendor, $limit = null, $offset = null)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('vendor_payment');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->orderBy('date_add DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get total paid amount for a vendor
     *
     * @param int $id_vendor Vendor ID
     * @return float Total paid amount
     */
    public static function getVendorTotalPaid($id_vendor)
    {
        $query = new DbQuery();
        $query->select('SUM(amount)');
        $query->from('vendor_payment');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('status = "completed"');

        return (float)Db::getInstance()->getValue($query);
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
               od.product_name, 
               od.product_reference, 
               od.product_quantity,
               o.reference as order_reference, 
               o.date_add as order_date,
               o.id_order
        FROM ' . _DB_PREFIX_ . 'vendor_transaction vt
        LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON od.id_order_detail = vt.order_detail_id  
        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vt.id_order
        WHERE vt.id_vendor_payment = ' . (int)$id_vendor_payment . '
        AND vt.transaction_type = "commission"
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
}
