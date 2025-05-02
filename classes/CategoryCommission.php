<?php

/**
 * CategoryCommission model class
 */
class CategoryCommission extends ObjectModel
{
    /** @var int Commission ID */
    public $id;

    /** @var int Category ID */
    public $id_category;

    /** @var int Vendor ID */
    public $id_vendor;

    /** @var float Commission rate */
    public $commission_rate;

    /** @var string Creation date */
    public $date_add;

    /** @var string Last update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'category_commission',
        'primary' => 'id_category_commission',
        'fields' => [
            'id_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_vendor' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'commission_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    /**
     * Get commission rate for a category and vendor
     *
     * @param int $id_vendor Vendor ID
     * @param int $id_category Category ID
     * @return float|null Commission rate or null if not found
     */
    public static function getCommissionRate($id_vendor, $id_category)
    {
        // Get all parent categories
        $categories = [$id_category];
        $parentCategories = self::getParentCategories($id_category);
        $categories = array_merge($categories, $parentCategories);

        // Check for commission rates in this category and all parent categories
        $query = new DbQuery();
        $query->select('commission_rate');
        $query->from('category_commission');
        $query->where('id_vendor = ' . (int)$id_vendor);
        $query->where('id_category IN (' . implode(',', array_map('intval', $categories)) . ')');
        $query->orderBy('date_add DESC');

        $result = Db::getInstance()->getValue($query);

        return $result ? (float)$result : null;
    }

    /**
     * Get all parent categories
     *
     * @param int $id_category Category ID
     * @return array Parent category IDs
     */
    protected static function getParentCategories($id_category)
    {
        $categories = [];
        $category = new Category($id_category);

        if (Validate::isLoadedObject($category)) {
            $parents = $category->getParentsCategories();

            foreach ($parents as $parent) {
                $categories[] = (int)$parent['id_category'];
            }
        }

        return $categories;
    }

    /**
     * Log category commission rate change
     *
     * @param int $id_vendor Vendor ID
     * @param int $id_category Category ID
     * @param float $old_rate Old commission rate
     * @param float $new_rate New commission rate
     * @param int $changed_by ID of the employee who made the change
     * @param string $comment Optional comment
     * @return bool Success
     */
    public static function logCommissionRateChange($id_vendor, $id_category, $old_rate, $new_rate, $changed_by, $comment = null)
    {
        return Db::getInstance()->insert('vendor_commission_log', [
            'id_vendor' => (int)$id_vendor,
            'id_category' => (int)$id_category,
            'old_commission_rate' => (float)$old_rate,
            'new_commission_rate' => (float)$new_rate,
            'changed_by' => (int)$changed_by,
            'comment' => pSQL($comment),
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }
}
