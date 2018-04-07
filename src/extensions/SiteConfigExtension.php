<?php

namespace SilverCommerce\Checkout\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\FieldList;
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
        'CheckoutShowTax' => "Boolean",
        'CheckoutLoginForm' => "Boolean",
        'CheckoutAllowGuest' => "Boolean",
        'PaymentSuccessContent' => 'HTMLText',
        'PaymentFailerContent'  => 'HTMLText',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $checkout_fields = ToggleCompositeField::create(
            'CheckoutSettings',
            _t("Checkout.CheckoutSettings", "Checkout Settings"),
            [
                CheckboxField::create(
                    'CheckoutShowTax',
                    _t("Checkout.CheckoutShowTax", "Show Tax")
                ),
                CheckboxField::create(
                    'CheckoutLoginForm',
                    _t("Checkout.CheckoutLoginForm", "Show Login Form")
                ),
                CheckboxField::create(
                    'CheckoutAllowGuest',
                    _t("Checkout.CheckoutAllowGuest", "Allow Guest Checkout")
                )
            ]
        );

        $payment_fields = ToggleCompositeField::create(
            'PaymentSettings',
            _t("Checkout.PaymentSettings", "Payment Settings"),
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

        $fields->addFieldsToTab(
            'Root.Shop',
            [
                $checkout_fields,
                $payment_fields
            ]
        );
    }
}