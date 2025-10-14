<?php


class ProductCommission extends ObjectModel
{
    public $id_product;
    public $id_attribute;
    public $commission_rate;
    public $expires_at;
    public $changed_by;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mv_product_commission',
        'primary' => 'id_product_commission',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'expires_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'changed_by' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 256],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public  $webserviceParameters = [
        'objectNodeName' => 'product_commission',
        'objectsNodeName' => 'product_commissions',
        'fields' => [
            'id_product' => [],
            'id_attribute' => [],
            'commission_rate' => [],
            'expires_at' => [],
            'changed_by' => [],

        ],
    ];



    public function update($null_values = false, $auto_date = true)
    {
        if ($this->commission_rate < 0 || $this->commission_rate > 100) {
            return false;
        }

        $changedFields = [];
        $old = new self($this->id);
        $this->id_product = (int) $old->id_product;
        $this->id_attribute = (int) $old->id_attribute;
        foreach (static::$definition['fields'] as $field => $def) {
            if ($field == 'expires_at' && new DateTime($old->$field) != new DateTime($this->$field) && $this->$field !== null) {
                $changedFields[$field] = [
                    'old' => $old->$field,
                    'new' => $this->$field,
                ];
            }
            if ($old->$field !== $this->$field && $this->$field !== null && $field != 'changed_by' &&  $field != "expires_at") {
                $changedFields[$field] = [
                    'old' => $old->$field,
                    'new' => $this->$field,
                ];
            }
        }
        foreach ($changedFields as $field => $value) {
            if ($value['old'] == $value['new']) {
                unset($changedFields[$field]);
            }
        }

        if (!empty($changedFields) && parent::update($null_values, $auto_date)) {
            $comment = '';
            $productCommissionLogObj = new ProductCommissionLog();
            $productCommissionLogObj->id_product_commission = $old->id;
            if ((float)$changedFields['commission_rate']['old'] != (float)$changedFields['commission_rate']['new']) {
                $productCommissionLogObj->old_commission_rate = (float)$changedFields['commission_rate']['old'];
                $productCommissionLogObj->new_commission_rate = (float)$changedFields['commission_rate']['new'];
                $comment .= 'commission rate from : ' . $changedFields['commission_rate']['old'] . ' to : ' . $changedFields['commission_rate']['new'] .' / ' ;
            } else {
                $productCommissionLogObj->old_commission_rate = $old->commission_rate;
                $productCommissionLogObj->new_commission_rate = $old->commission_rate;
            }
            if ($changedFields['expires_at'] && new DateTime($changedFields['expires_at']['old']) != new DateTime($changedFields['expires_at']['new'])) {
                $comment  .=  'date from :' .  date('Y-m-d', strtotime($changedFields['expires_at']['old'])) . ' to :' .  date('Y-m-d', strtotime($changedFields['expires_at']['new']));
            }
            $productCommissionLogObj->comment  = $comment;
            $productCommissionLogObj->changed_by = $this->changed_by;
            return  $productCommissionLogObj->add();
        }
        return true;
    }

    public function add($auto_date = true, $null_values = false)
    {
        if ($this->commission_rate < 0 || $this->commission_rate > 100) {
            return false;
        }

        if (!$this->doesAttributeBelongToProduct($this->id_product, $this->id_attribute)) {
            return false;
        }


        if (parent::add($auto_date, $null_values)) {
            $productCommissionLogObj = new ProductCommissionLog();
            $productCommissionLogObj->id_product_commission = $this->id;
            $productCommissionLogObj->old_commission_rate = $this->commission_rate;
            $productCommissionLogObj->new_commission_rate = $this->commission_rate;
            $productCommissionLogObj->comment = 'Ajout une nouvelle commission';
            $productCommissionLogObj->changed_by = $this->changed_by;
            return $productCommissionLogObj->add();
        }
        return false;
    }

    public static function getByProductAttribute($id_product, $id_attribute)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mv_product_commission` WHERE `id_product` = ' . (int)$id_product . ' AND `id_attribute` = ' . (int)$id_attribute
        );
    }

    private function doesAttributeBelongToProduct($id_product, $id_attribute)
    {
        $product = new Product((int)$id_product);
        $attributes = $product->getAttributeCombinations(Context::getContext()->language->id);

        foreach ($attributes as $combination) {
            if ((int)$combination['id_product_attribute'] === (int)$id_attribute) {
                return true;
            }
        }
        return false;
    }

}
