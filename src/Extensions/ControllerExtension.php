<?php

namespace SilverCommerce\Checkout\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverCommerce\Checkout\Control\Checkout;

/**
 * Extension for Content Controller that provide methods such as cart link and category list
 * to templates
 *
 * @author  i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class ControllerExtension extends Extension
{
    /**
     * Get the checkout (so we can retrieve links, etc)
     *
     * @return Checkout
     */
    public function getCheckout()
    {
        return Injector::inst()->create(Checkout::class);
    }
}
