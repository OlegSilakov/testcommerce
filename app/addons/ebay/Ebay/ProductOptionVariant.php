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
 * Class ProductOptionVariant
 * @package Ebay
 */
class ProductOptionVariant
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $picture;

    /**
     * @var ProductOption
     */
    protected $option;

    /**
     * ProductOptionVariant constructor.
     * @param ProductOption $option
     * @param $name
     * @param $picture
     */
    public function __construct(ProductOption $option, $name, $picture = '')
    {
        $this->option = $option;
        $this->name = trim($name);
        $this->picture = $picture;
    }

    /**
     * Return id
     * @return string
     */
    public function getId()
    {
        return md5($this->name . $this->option->name);
    }

    /**
     * Return option
     * @return ProductOption
     */
    public function getOption()
    {
        return $this->option;
    }
}
