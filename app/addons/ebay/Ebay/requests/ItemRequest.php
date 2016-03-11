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

namespace Ebay\requests;
use Ebay\Product;
use Tygh\Enum\ProductFeatures;
use Tygh\Settings;

/**
 * Class ItemRequest
 *
 * Parent class for AddItemRequest, RelistItemRequest, ReviseItemRequest
 * @package Ebay\requests
 */
abstract class ItemRequest extends Request
{
    protected static $companies = array();

    /** @var Product  */
    protected $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Return company data
     * @param  int   $company_id
     * @return array
     */
    protected function getCompany($company_id)
    {
        if (!isset(static::$companies[$company_id])) {
            static::$companies[$company_id] = fn_get_company_placement_info($company_id);
        }

        return static::$companies[$company_id];
    }

    /**
     * @return string
     */
    protected function getCurrency()
    {
        return CART_PRIMARY_CURRENCY;
    }

    /**
     * @inheritdoc
     */
    public function xml()
    {
        $template = $this->product->getTemplate();
        $company = $this->getCompany($template->company_id);

        $location = fn_get_country_name($company['company_country']);
        $secondary_category = $this->product->getExternalSecondCategoryId();
        $secondary_category_xml = "";

        if (!empty($secondary_category)) {
            $secondary_category_xml = <<<XML
            <SecondaryCategory>
                <CategoryID>{$secondary_category}</CategoryID>
            </SecondaryCategory>
XML;
        }

        return <<<XML
        <Item>
            {$this->getItemIdXml()}
            <Site>{$template->site}</Site>
            <ListingType>FixedPriceItem</ListingType>
            <Currency>{$this->getCurrency()}</Currency>
            <PrimaryCategory>
                <CategoryID>{$this->product->getExternalCategoryId()}</CategoryID>
            </PrimaryCategory>
            {$secondary_category_xml}
            <ConditionID>{$template->condition_id}</ConditionID>
            <CategoryMappingAllowed>true</CategoryMappingAllowed>
            <Country>{$company['company_country']}</Country>
            <PostalCode>{$company['company_zipcode']}</PostalCode>
            <Location><![CDATA[{$location}]]></Location>
            <Title><![CDATA[{$this->product->title}]]></Title>
            <Description><![CDATA[{$this->product->description}]]></Description>
            <ListingDuration><![CDATA[{$template->ebay_duration}]]></ListingDuration>
            <DispatchTimeMax><![CDATA[{$template->dispatch_days}]]></DispatchTimeMax>
            <ReturnPolicy>
                <ReturnsAcceptedOption><![CDATA[{$template->return_policy}]]></ReturnsAcceptedOption>
                <RefundOption><![CDATA[{$template->refund_method}]]></RefundOption>
                <ReturnsWithinOption><![CDATA[{$template->contact_time}]]></ReturnsWithinOption>
                <Description><![CDATA[{$template->return_policy_descr}]]></Description>
                <ShippingCostPaidByOption><![CDATA[{$template->cost_paid_by}]]></ShippingCostPaidByOption>
            </ReturnPolicy>
            {$this->getPaymentXml()}
            {$this->getShippingXml()}
            {$this->getPictureDetailsXml()}
            {$this->getFeaturesXml()}
            {$this->getProductOptionsXml()}
            {$this->getAdditionalXml()}
        </Item>
XML;
    }

    protected function getAdditionalXml()
    {
        return '';
    }

    protected function getItemIdXml()
    {
        if (empty($this->product->external_id)) {
            return '';
        }

        return "<ItemID>{$this->product->external_id}</ItemID>";
    }

    protected function getPaymentXml()
    {
        $template = $this->product->getTemplate();

        $result = '<PaymentMethods>'
            . implode("</PaymentMethods>\n<PaymentMethods>", $template->payment_methods)
            . '</PaymentMethods>';

        if (in_array('PayPal', $template->payment_methods)) {
            $result .= "\n<PayPalEmailAddress>{$template->paypal_email}</PayPalEmailAddress>";
        }

        return $result;
    }

    protected function getShippingXml()
    {
        $template = $this->product->getTemplate();

        if ($template->shipping_type === 'C') {
            return <<<XML
            <ShippingDetails>
                <ShippingType>Calculated</ShippingType>
                <CalculatedShippingRate>
                    <PackageDepth>{$this->product->shipping_box_height}</PackageDepth>
                    <PackageLength>{$this->product->shipping_box_length}</PackageLength>
                    <PackageWidth>{$this->product->shipping_box_width}</PackageWidth>
                    <ShippingPackage>{$this->product->package_type}</ShippingPackage>
                    <WeightMajor>{$this->product->weight_major}</WeightMajor>
                    <WeightMinor>{$this->product->weight_minor}</WeightMinor>
                    <MeasurementUnit>{$template->getMeasureType()}</MeasurementUnit>
                </CalculatedShippingRate>
                <ShippingServiceOptions>
                    <ShippingService>{$template->shippings}</ShippingService>
                    <ShippingServicePriority>1</ShippingServicePriority>
                </ShippingServiceOptions>
            </ShippingDetails>
XML;
        } else {
            $shipping_cost_additional = '<ShippingServiceAdditionalCost currencyID="' . CART_PRIMARY_CURRENCY . '">'
                . number_format($template->shipping_cost_additional, 2, '.', '')
                . '</ShippingServiceAdditionalCost>';

            if ($template->free_shipping === 'N') {
                $free_shipping = "false";
                $shipping_cost = '<ShippingServiceCost currencyID="' . CART_PRIMARY_CURRENCY . '">'
                    . number_format($template->shipping_cost, 2, '.', '')
                    . '</ShippingServiceCost>';
            } else {
                $shipping_cost = null;
                $free_shipping = 'true';
            }

            return <<<XML
            <ShippingDetails>
                <ShippingType>Flat</ShippingType>
                <ShippingServiceOptions>
                    <FreeShipping>{$free_shipping}</FreeShipping>
                    {$shipping_cost}
                    <ShippingService>{$template->shippings}</ShippingService>
                    <ShippingServicePriority>1</ShippingServicePriority>
                    {$shipping_cost_additional}
                </ShippingServiceOptions>
            </ShippingDetails>
XML;
        }
    }

    protected function getPictureDetailsXml()
    {
        $result = '';

        if (!empty($this->product->pictures)) {
            $result .= "<PictureDetails>\n";

            foreach ($this->product->pictures as $path) {
                $result .= "<PictureURL>" . $this->product->getExternalPicture($path) . "</PictureURL>\n";
            }

            $result .= "</PictureDetails>\n";
        }

        return $result;
    }

    protected function getFeaturesXml()
    {
        if (empty($this->product->features)) {
            return '';
        }

        return <<<XML
        <ItemSpecifics>
        {$this->getFeaturesXmlRecursive($this->product->features)}
        </ItemSpecifics>
XML;
    }

    protected function getFeaturesXmlRecursive($features)
    {
        $result = '';

        foreach ($features as $key => $feature) {
            $type = $feature['feature_type'];

            if ($type == ProductFeatures::GROUP && !empty($feature['subfeatures'])) {
                $result .= $this->getFeaturesXmlRecursive($feature['subfeatures']);
                continue;
            } else {
                $value = '';

                if ($type == ProductFeatures::SINGLE_CHECKBOX && $feature['value'] == 'Y') {
                    $value = __('yes');
                } elseif ($type == ProductFeatures::DATE) {
                    $value = strftime(Settings::instance()->getValue('date_format', 'Appearance'), $feature['value_int']);
                } elseif ($type == ProductFeatures::MULTIPLE_CHECKBOX && $feature['variants']) {
                    if (!empty($feature['variants'])) {
                        $variants = array();

                        foreach ($feature['variants'] as $var) {
                            if ($var['selected']) {
                                $variants[] = $var['variant'];
                            }
                        }

                        $value = implode("]]></Value>\n<Value><![CDATA[", $variants);
                    }
                } elseif ($type == ProductFeatures::TEXT_SELECTBOX || $type == ProductFeatures::EXTENDED) {
                    if (!empty($feature['variants'])) {
                        foreach ($feature['variants'] as $var) {
                            if ($var['selected']) {
                                $value = $var['variant'];
                            }
                        }
                    }
                } elseif ($type == ProductFeatures::NUMBER_SELECTBOX || $type == ProductFeatures::NUMBER_FIELD) {
                    $value = floatval($feature['value_int']);
                } else {
                    $value = $feature['value'];
                }

                $result .= <<<XML
                    <NameValueList>
                        <Name><![CDATA[{$feature['description']}]]></Name>
                        <Value><![CDATA[{$value}]]></Value>
                    </NameValueList>
XML;
            }
        }

        return $result;
    }


    /**
     * @return string
     */
    protected function getProductOptionsXml()
    {
        $combinations = $this->getProductCombinations();

        if (empty($combinations)) {
            $price = fn_format_price($this->product->price);

            return <<<XML
                <StartPrice currencyID="{$this->getCurrency()}">{$price}</StartPrice>
                <Quantity>{$this->product->amount}</Quantity>
XML;
        } else {
            $result = '<Variations><VariationSpecificsSet>';
            $picturesXml = '';

            foreach ($this->product->getOptions() as $option) {

                $result .= <<<XML
                    <NameValueList>
                        <Name><![CDATA[{$option->name}]]></Name>
XML;

                $picturesXml .= <<<XML
                    <Pictures>
                        <VariationSpecificName><![CDATA[{$option->name}]]></VariationSpecificName>
XML;
                foreach ($option->getVariants() as $variant) {
                    $picturesXml .= <<<XML
                    <VariationSpecificPictureSet>
                        <VariationSpecificValue><![CDATA[{$variant->name}]]></VariationSpecificValue>
                        <PictureURL>{$this->product->getExternalPicture($variant->picture)}</PictureURL>
                    </VariationSpecificPictureSet>
XML;
                    $result .= "<Value><![CDATA[{$variant->name}]]></Value>\n";
                }

                $picturesXml .= "</Pictures>\n";
                $result .= "</NameValueList>\n";
            }

            $result .= '</VariationSpecificsSet>' . $picturesXml;

            foreach ($combinations as $combination) {
                $sku = '';
                $variationsXml = '';

                foreach ($combination->getOptionVariants() as $item) {
                    $sku .= $item->getOption()->name . $item->name;

                    $variationsXml .= <<<XML
                        <NameValueList>
                            <Name><![CDATA[{$item->getOption()->name}]]></Name>
                            <Value><![CDATA[{$item->name}]]></Value>\n
                        </NameValueList>
XML;
                }

                $deleted = '';

                if ($combination->isDeleted()) {
                    $deleted = "<Delete>true</Delete>";
                }

                $result .= <<<XML
                    <Variation>
                        {$deleted}
                        <SKU><![CDATA[{$sku}]]></SKU>
                        <StartPrice>{$combination->price}</StartPrice>
                        <Quantity>{$combination->quantity}</Quantity>
                        <VariationSpecifics>
                            {$variationsXml}
                        </VariationSpecifics>
                    </Variation>
XML;
            }

            $result .= '</Variations>';

            return $result;
        }
    }

    /**
     * @return \Ebay\ProductVariation[]
     */
    protected function getProductCombinations()
    {
        return $this->product->getCombinations();
    }
}
