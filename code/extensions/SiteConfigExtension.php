<?php

namespace SilverCommerce\Checkout\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

/**
 * Extension to add extra settings into siteconfig
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class SiteConfigExtension extends DataExtension
{
    private static $db = [
        'PaymentSuccessContent' => 'HTMLText',
        'PaymentFailerContent'  => 'HTMLText',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Payment options
        $payment_fields = ToggleCompositeField::create(
            'PaymentSettings',
            _t("Orders.PaymentSettings", "Payment Settings"),
            [
                HTMLEditorField::create(
                    'PaymentSuccessContent',
                    _t("Orders.PaymentSuccessContent", "Payment success content")
                )->addExtraClass('stacked'),
                
                HTMLEditorField::create(
                    'PaymentFailerContent',
                    _t("Orders.PaymentFailerContent", "Payment failer content")
                )->addExtraClass('stacked')
            ]
        );

        $fields->addFieldToTab(
            'Root.Shop',
            $payment_fields
        );
    }
}