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
 * Class ProductOption
 * @package Ebay
 */
class ProductOption
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var ProductOptionVariant[]
     */
    protected $variants = array();

    /**
     * ProductOption constructor.
     * @param $name
     * @param ProductOptionVariant[] $variants
     */
    public function __construct($name, array $variants = array())
    {
        $this->name = trim($name);

        foreach ($variants as $variant) {
            $this->setVariant($variant);
        }
    }

    /**
     * Set option variant
     * @param ProductOptionVariant $variant
     */
    public function setVariant(ProductOptionVariant $variant)
    {
        $this->variants[$variant->getId()] = $variant;
    }

    /**
     * Return option variants
     * @return ProductOptionVariant[]
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Return option id
     * @return string
     */
    public function getId()
    {
        return md5($this->name);
    }
}
