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

/**
 * Class ProductVariation
 * @package Ebay
 */
class ProductVariation
{
    /**
     * @var float
     */
    public $price;

    /**
     * @var int
     */
    public $quantity;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @var ProductOptionVariant[]
     */
    protected $option_variants = array();

    /**
     * ProductVariation constructor.
     * @param float                  $price
     * @param int                    $quantity
     * @param ProductOptionVariant[] $option_variants
     */
    public function __construct($price, $quantity, $option_variants)
    {
        $this->price = (float) $price;
        $this->quantity = (int) $quantity;

        foreach ($option_variants as $variant) {
            $this->setOptionVariant($variant);
        }
    }

    /**
     * Set option variant
     * @param ProductOptionVariant $variant
     */
    public function setOptionVariant(ProductOptionVariant $variant)
    {
        $this->option_variants[] = $variant;
    }

    /**
     * Set mark variation deleted
     * @param bool|true $flag
     */
    public function markDeleted($flag = true)
    {
        $this->deleted = $flag;
    }

    /**
     * Return flag is variation deleted
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted === true;
    }

    /**
     * Return option variants
     * @return ProductOptionVariant[]
     */
    public function getOptionVariants()
    {
        return $this->option_variants;
    }

    /**
     * Return id
     * @return string
     */
    public function getId()
    {
        $result = array();

        foreach ($this->option_variants as $item) {
            $result[] = $item->getOption()->name . $item->name;
        }

        sort($result);

        return implode('', $result);
    }
}
