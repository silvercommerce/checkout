# SilverCommerce Checkout Module

Adds a checkout process, to allow users to pay for an Estimate using configured
omnipay payment gateways.

Also allows you to overwrite the process so you can add more custom payment integration.

## Install

Install this module using composer:

`composer require silvercommerce/checkout`

## Usage

By default, this module works with `silvercommerce/shoppingcart` out of the box. But it is fairly
simple to use it to create payment workflows for a custom estimate if required.

### Paying for a custom estimate

If you want to create a payment flow for a custom estimate, you simply have to create the estimate,
add some items, add it to the checkout and then redirect. This can be done with a simple bit of code.

The example below has a custom controller that creates an estimate from a pre-defined product and then
redirects to the checkout:

```
use SilverStripe\Core\Injector\Injector;
use SilverCommerce\Checkout\Control\Checkout;
use SilverCommerce\OrdersAdmin\Factory\OrderFactory;

class ProductRedirectController extends PageController
{
    public function init()
    {
        parent::init();

        $product = $this->Product(); // Instance of SilverCommerce\CatalogueAdmin\CatalogueProduct
        $factory = OrderFactory::create();
        $factory->addItem($product);
        $factory->write();

        $checkout = Injector::inst()->get(Checkout::class);
        $checkout->setEstimate($factory->getOrder());

        $this->redirect($checkout->Link());
    }
}
```