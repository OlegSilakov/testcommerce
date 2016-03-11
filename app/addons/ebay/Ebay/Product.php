<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Ebay;
use Tygh\Enum\ProductTracking;
use Tygh\Helpdesk;
use Tygh\Registry;

/**
 * Class Product
 * @package Ebay
 */
class Product
{
    /** Product not exported on ebay */
    const STATUS_NOT_EXPORTED = 0;
    /** Product active sale on ebay */
    const STATUS_ACTIVE = 1;
    /** Product sale closed on ebay */
    const STATUS_CLOSED = 2;

    const GRAM_ON_LBS_UNIT = 453.6;
    const OZ_ON_LBS_UNIT = 16;
    const GRAM_ON_KG_UNIT = 1000;
    const MAX_TITLE_LENGTH = 80;

    public $id;
    public $external_id;
    public $template_id;
    public $company_id;
    public $main_category_id;
    public $original_title;
    public $title;
    public $description;
    public $package_type;
    public $weight;
    public $weight_major;
    public $weight_minor;
    public $shipping_box_height;
    public $shipping_box_length;
    public $shipping_box_width;
    public $price;
    public $base_price;
    public $amount;
    public $tracking;
    public $status;
    public $hash;
    public $features = array();
    public $pictures = array();
    public $all_pictures = array();
    /** @var ProductOption[] */
    protected $options = array();
    /** @var ProductVariation[] */
    protected $combinations = array();

    protected $external_pictures = array();
    /** @var ProductVariation[] */
    protected $external_combinations = array();

    /** @var array Categories */
    public static $categories = array();

    /**
     * Construct
     * @param int|array $product Product id or array of product data
     * ```php
     * array(
     *  'product_id' => int,
     *  'ebay_template_id' => int,
     *  ...
     * )
     * ```
     * @param string|array $relations Default * - all relations load
     * array(
     *   'additional',
     *   'options',
     *   'external'
     * );
     */
    public function __construct($product, $relations = '*')
    {
        if (!is_array($product)) {
            $product_id = (int) $product;
            $auth = \Tygh::$app['session']['auth'];

            $product = fn_get_product_data($product_id, $auth, CART_LANGUAGE);

            if ($relations === '*' || in_array('additional', $relations)) {
                fn_gather_additional_product_data($product, true, true);
            }
        }

        if (!empty($product)) {
            $this->init($product);

            if ($relations === '*' || in_array('external', $relations)) {
                $this->loadExternalData();
            }

            if ($relations === '*' || in_array('options', $relations)) {
                $this->loadOptions();
            }
        }
    }

    /**
     * Init model
     * @param array $data
     */
    protected function init(array $data)
    {
        $default = array(
            'product_id' => null,
            'ebay_template_id' => null,
            'company_id' => null,
            'product' => null,
            'full_description' => null,
            'base_price' => null,
            'price' => null,
            'box_length' => null,
            'box_width' => null,
            'box_height' => null,
            'tracking' => null,
            'amount' => null,
            'weight' => null,
            'package_type' => null,
            'ebay_status' => null,
            'ebay_override_price' => null,
            'ebay_price' => null,
            'ebay_title' => null,
            'ebay_description' => null,
            'product_features' => null,
            'main_pair' => null,
            'image_pairs' => null,
            'main_category' => null,
        );

        $data = array_merge($default, $data);

        $this->id = $data['product_id'];
        $this->template_id = $data['ebay_template_id'];
        $this->company_id = $data['company_id'];
        $this->title = $data['product'];
        $this->original_title = $data['product'];
        $this->description = $data['full_description'];
        $this->base_price = $data['base_price'];
        $this->price = $data['price'];
        $this->shipping_box_length = $data['box_length'];
        $this->shipping_box_width = $data['box_width'];
        $this->shipping_box_height = $data['box_height'];
        $this->tracking = $data['tracking'];
        $this->amount = $data['amount'];
        $this->weight = $data['weight'];
        $this->package_type = $data['package_type'];
        $this->main_category_id = $data['main_category'];
        $this->status = (int) $data['ebay_status'];

        if ($data['ebay_override_price'] === 'Y') {
            $this->price = $data['ebay_price'];
            $this->base_price = $data['ebay_price'];
        }

        if ($data['override'] === 'Y') {
            if (!empty($data['ebay_title'])) {
                $this->title = $data['ebay_title'];
            }

            if (!empty($data['ebay_description'])) {
                $this->description = $data['ebay_description'];
            }
        }

        $this->title = substr(strip_tags($this->title), 0, static::MAX_TITLE_LENGTH);

        if (!empty($data['product_features']) && is_array($data['product_features'])) {
            $this->features = $data['product_features'];
        }

        if (!empty($data['main_pair']) && !empty($data['main_pair']['detailed']['http_image_path'])) {
            $this->setPicture($data['main_pair']['detailed']['http_image_path']);
            $this->pictures[] = $data['main_pair']['detailed']['http_image_path'];
        }

        if (!empty($data['image_pairs'])) {
            foreach ($data['image_pairs'] as $item) {
                if (!empty($item['detailed']['http_image_path'])) {
                    $this->setPicture($item['detailed']['http_image_path']);
                    $this->pictures[] = $item['detailed']['http_image_path'];
                }
            }
        }

        $this->initWeight();
    }

    /**
     * Init weight major and minor
     */
    public function initWeight()
    {
        $template = $this->getTemplate();

        if ($template) {
            $grams = $template->getMeasureWeight();

            if ($template->getMeasureType() == 'English') {
                $divider = static::GRAM_ON_LBS_UNIT;
                $rate = static::OZ_ON_LBS_UNIT;
            } else {
                $divider = static::GRAM_ON_KG_UNIT;
                $rate = static::GRAM_ON_KG_UNIT;
            }

            $this->weight_major = floor($this->weight * $grams / $divider);
            $this->weight_minor = ($this->weight - $this->weight_major) * $rate;
        }
    }

    /**
     * Set template
     * @param int $template_id
     */
    public function setTemplateId($template_id)
    {
        $this->template_id = $template_id;
        $this->loadExternalData();
    }

    /**
     * Return product ebay template
     * @return Template
     */
    public function getTemplate()
    {
        return Template::getById($this->template_id);
    }

    /**
     * Return product UUID
     * @return string
     */
    public function getUUID()
    {
        return md5($this->id . Helpdesk::getStoreKey() . $this->template_id);
    }

    /**
     * Return primary category id
     * @return string
     */
    public function getExternalCategoryId()
    {
        $main_category = $this->getCategory();

        if (!empty($main_category['ebay_category_id']) && $main_category['ebay_site_id'] == $this->getTemplate()->site_id) {
            return $main_category['ebay_category_id'];
        }

        return $this->getTemplate()->category;
    }

    /**
     * Return second category id
     * @return string
     */
    public function getExternalSecondCategoryId()
    {
        $main_category = $this->getCategory();

        if (!empty($main_category['ebay_category_id']) && $main_category['ebay_site_id'] == $this->getTemplate()->site_id) {
            return false;
        }

        return $this->getTemplate()->sec_category;
    }

    /**
     * Return product options
     * @return ProductOption[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return product combinations
     * @param  bool               $all If true returned all combinations with deleted
     * @return ProductVariation[]
     */
    public function getCombinations($all = true)
    {
        $result = $this->combinations;

        if ($all) {
            foreach ($this->external_combinations as $item) {
                if (!isset($result[$item->getId()])) {
                    $item->markDeleted();
                    $result[$item->getId()] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * Calculate hash
     *
     * @return string
     */
    public function getHash()
    {
        $data = array(
            'price' => $this->price,
            'title' => $this->title,
            'description' => $this->description,
        );

        if (!empty($this->features)) {
            $data['product_features'] = serialize($this->features);
        }

        return fn_crc32(implode('_', $data));
    }

    /**
     * Save ebay data
     *
     * @return mixed
     */
    public function saveExternalData()
    {
        db_query('REPLACE INTO ?:ebay_template_products ?e', array(
            'ebay_item_id' => $this->external_id,
            'template_id' => $this->template_id,
            'product_id' => $this->id,
            'pictures' => serialize($this->external_pictures),
            'combinations' => serialize($this->combinations),
            'product_hash' => $this->getHash()
        ));
    }

    /**
     * Save product template id
     * @return int
     */
    public function saveTemplateId()
    {
        return static::updateProductTemplateId($this->id, $this->template_id);
    }

    /**
     * Update product template id
     * @param $product_id
     * @param $template_id
     * @return int
     */
    public static function updateProductTemplateId($product_id, $template_id)
    {
        db_query("UPDATE ?:ebay_template_products SET template_id = ?i WHERE product_id = ?i", $template_id, $product_id);

        return db_query("UPDATE ?:products SET ebay_template_id = ?i WHERE product_id = ?i", $template_id, $product_id);
    }

    /**
     * Delete ebay data by product external id
     *
     * @param  string $external_id
     * @return int
     */
    public static function deleteExternalData($external_id)
    {
        return db_query("DELETE FROM ?:ebay_template_products WHERE ebay_item_id = ?i", $external_id);
    }

    /**
     * Return template product ids
     *
     * @param  int   $template_id
     * @return array
     */
    public static function getTemplateProductIds($template_id)
    {
        return db_get_fields(
            "SELECT product_id FROM ?:products WHERE ebay_template_id = ?i",
            $template_id
        );
    }

    /**
     * Return all exported to ebay product ids
     *
     * @return array
     */
    public static function getExportedProductIds()
    {
        return db_get_fields("SELECT product_id FROM ?:ebay_template_products");
    }

    /**
     * Set external picture path
     *
     * @param  string $path
     * @param  string $external_path
     * @return bool
     */
    public function setExternalPicture($path, $external_path)
    {
        $hash = md5($path);

        if (isset($this->all_pictures[$hash])) {
            $this->external_pictures[$hash] = $external_path;
            $this->all_pictures[$hash]['external_path'] = $external_path;

            return true;
        }

        return false;
    }

    /**
     * Return external picture path
     *
     * @param  string      $path
     * @return bool|string
     */
    public function getExternalPicture($path)
    {
        $hash = md5($path);

        if (isset($this->external_pictures[$hash])) {
            return $this->external_pictures[$hash];
        }

        return false;
    }

    /**
     * Load options and combinations
     */
    public function loadOptions()
    {
        $options = fn_get_product_options($this->id, CART_LANGUAGE, true, true);
        $original_option_variants = $product_option_variants = array();

        foreach ($options as $item) {
            $option = new ProductOption($item['option_name']);

            foreach ($item['variants'] as $variant) {
                if (!empty($variant['image_pair']['icon']['http_image_path'])) {
                    $image_path = $variant['image_pair']['icon']['http_image_path'];

                } else {
                    $image_path = Registry::get('config.http_location') . '/images/no_image.png';
                }

                $this->setPicture($image_path);

                $option_variant = new ProductOptionVariant($option, $variant['variant_name'], $image_path);
                $option->setVariant($option_variant);

                $product_option_variants[$item['option_id']][$variant['variant_id']] = $option_variant;
                $original_option_variants[$item['option_id']][$variant['variant_id']] = $variant;
            }

            $this->options[$option->getId()] = $option;
        }

        list($inventory) = fn_get_product_options_inventory(array('product_id' => $this->id));

        foreach ($inventory as $combination) {
            $variants = array();
            $price = $this->base_price;

            if ($this->tracking === ProductTracking::TRACK_WITH_OPTIONS) {
                $amount = $combination['amount'];
            } else {
                $amount = $this->amount;
            }

            foreach ($combination['combination'] as $option_id => $variant_id) {
                if (isset($product_option_variants[$option_id][$variant_id])) {
                    $variants[] = $product_option_variants[$option_id][$variant_id];
                    $variant = $original_option_variants[$option_id][$variant_id];

                    if ($variant['modifier_type'] == 'A') {
                        $price += $variant['modifier'];
                    } else {
                        $price = $price + ($price * $variant['modifier'] / 100);
                    }
                }
            }

            if (!empty($variants)) {
                $product_variation = new ProductVariation($price, $amount, $variants);
                $this->combinations[$product_variation->getId()] = $product_variation;
            }
        }
    }

    /**
     * Load product external data
     */
    public function loadExternalData()
    {
        if (empty($this->template_id)) {
            return;
        }

        $data = db_get_row(
            'SELECT * FROM ?:ebay_template_products WHERE product_id = ?i AND template_id = ?i',
            $this->id,
            $this->template_id
        );

        if (!empty($data)) {
            $this->external_id = $data['ebay_item_id'];

            if (!empty($data['pictures'])) {
                $this->external_pictures = @unserialize($data['pictures']);

                if (!is_array($this->external_pictures)) {
                    $this->external_pictures = array();
                }

                foreach ($this->external_pictures as $hash => $path) {
                    if (isset($this->all_pictures[$hash])) {
                        $this->all_pictures[$hash]['external_path'] = $path;
                    }
                }
            }

            if (!empty($data['combinations'])) {
                $this->external_combinations = @unserialize($data['combinations']);

                if (!is_array($this->external_combinations)) {
                    $this->external_combinations = array();
                } else {
                    foreach ($this->external_combinations as $key => $item) {
                        if (!$item instanceof ProductVariation) {
                            unset($this->external_combinations[$key]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Set picture
     *
     * @param string $path
     */
    protected function setPicture($path)
    {
        if (empty($path)) {
            return;
        }

        $hash = md5($path);

        if (!isset($this->all_pictures[$hash])) {
            $this->all_pictures[$hash] = array(
                'hash' => $hash,
                'path' => $path,
                'external_path' => isset($this->external_pictures[$hash]) ? $this->external_pictures[$hash] : null
            );
        }
    }

    /**
     * Return array product statuses
     * @return array
     */
    public static function getStatuses()
    {
        return array(
            static::STATUS_NOT_EXPORTED => __('ebay_product_status_not_exported'),
            static::STATUS_ACTIVE => __('ebay_product_status_active'),
            static::STATUS_CLOSED => __('ebay_product_status_closed'),
        );
    }

    /**
     * Update product status
     *
     * @param  int  $product_id Product id
     * @param  int  $status     eBay product status
     * @return bool
     */
    public static function updateStatus($product_id, $status)
    {
        return db_query("UPDATE ?:products SET ebay_status = ?i WHERE product_id = ?i", $status, $product_id) > 0;
    }

    /**
     * Update product status on active
     * @return bool
     */
    public function setStatusActive()
    {
        return static::updateStatus($this->id, static::STATUS_ACTIVE);
    }

    /**
     * Update product status on closed
     * @return bool
     */
    public function setStatusClosed()
    {
        return static::updateStatus($this->id, static::STATUS_CLOSED);
    }

    /**
     * Return true if status active
     * @return bool
     */
    public function statusIsActive()
    {
        return $this->status === static::STATUS_ACTIVE;
    }

    /**
     * Return true if status closed
     * @return bool
     */
    public function statusIsClosed()
    {
        return $this->status === static::STATUS_CLOSED;
    }

    /**
     * Return product main category data
     * @return bool
     */
    protected function getCategory()
    {
        if (!isset(static::$categories[$this->main_category_id])) {
            $category = fn_get_category_data(
                $this->main_category_id,
                CART_LANGUAGE,
                '',
                false
            );

            if (!empty($category)) {
                static::$categories[$this->main_category_id] = $category;
            }
        }

        return isset(static::$categories[$this->main_category_id]) ? static::$categories[$this->main_category_id] : false;
    }

    /**
     * @param ProductVariation[] $values
     */
    public function setExternalCombinations(array $values)
    {
        $this->external_combinations = array();

        foreach ($values as $item) {
            if ($item instanceof ProductVariation) {
                $this->external_combinations[$item->getId()] = $item;
            }
        }
    }

    /**
     * @return ProductVariation[]
     */
    public function getExternalCombinations()
    {
        return $this->external_combinations;
    }
}
