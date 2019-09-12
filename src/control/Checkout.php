<?php

namespace SilverCommerce\Checkout\Control;

use Exception;
use NumberFormatter;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\Form;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Cookie;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Subsites\Model\Subsite;
use SilverCommerce\Postage\Forms\PostageForm;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Omnipay\GatewayFieldsFactory;
use SilverStripe\Security\MemberAuthenticator;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;
use SilverStripe\Omnipay\Service\ServiceFactory;
use SilverStripe\CMS\Controllers\ContentController;
use SilverCommerce\ShoppingCart\ShoppingCartFactory;
use SilverCommerce\Checkout\Forms\CustomerDetailsForm;

/**
 * Controller used to render the checkout process
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class Checkout extends Controller
{

    /**
     * URL Used to generate links to this controller.
     * 
     * NOTE If you alter routes.yml, you MUST alter this. 
     * \SilverStripe\GraphQL\Auth\MemberAuthenticator;
     * @var string
     * @config
     */
    private static $url_segment = 'checkout';

    /**
     * whether or not the cleaning task should be left to a cron job
     *
     * @var boolean
     * @config
     */
    private static $cron_cleaner = false;

    /**
     * Setup default templates for this controller
     *
     * @var array
     */
    protected $templates = [
        'index' => [
            'Checkout',
            Checkout::class,
            'Page'
        ],
        'postage' => [
            'Checkout_postage',
            Checkout::class . '_postage',
            'Checkout',
            Checkout::class,
            'Page'
        ],
        'payment' => [
            'Checkout_payment',
            Checkout::class . '_payment',
            'Checkout',
            Checkout::class,
            'Page'
        ],
        'complete' => [
            'Checkout_complete',
            Checkout::class . '_complete',
            'Checkout',
            Checkout::class,
            'Page'
        ],
        'noestimate' => [
            'Checkout_noestimate',
            Checkout::class . '_noestimate',
            'Checkout',
            Checkout::class,
            'Page'
        ]
    ];

    private static $allowed_actions = [
        'index',
        'postage',
        'payment',
        'complete',
        'noestimate',
        'CustomerForm',
        'PostageForm',
        'GatewayForm',
        'PaymentForm'
    ];

    /**
     * A list of fields in the checkout module that are mapped to
     * omnipay allowed fields.
     * 
     * This map is used to send the submitted checkout data
     * to omnipay services for payment.CheckoutLoginForm
     * 
     * NOTE: Be careful changing this as most of these keys are required
     * 
     * @var array
     * @config
     */
    private static $omnipay_map = [
        "FullRef" => "transactionId",
        "FirstName" => "firstName",
        "Surname" => "lastName",
        "Email" => "email",
        "Company" => "company",
        "Address1" => "billingAddress1",
        "Address2" => "billingAddress2",
        "City" => "billingCity",
        "State" => "billingState",
        "PostCode" => "billingPostcode",
        "CountryUC" => "billingState",
        "Country" => "billingCountry",
        "PhoneNumber" => "billingPhone",
        "DeliveryAddress1" => "shippingAddress1",
        "DeliveryAddress2" => "shippingAddress2",
        "DeliveryCity" => "shippingCity",
        "DeliveryCounty" => "shippingState",
        "DeliveryPostCode" => "shippingPostcode",
        "DeliveryCountryUC" => "shippingCountry",
        "PhoneNumber" => "shippingPhone"
    ];

    /**
     * Estimate linked to this checkout process
     * 
     * @var Estimate
     * @config
     */
    private $estimate = null;

    public function getEstimate()
    {
        return $this->estimate;
    }

    public function setEstimate(Estimate $estimate)
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $session = $request->getSession();
        $this->estimate = $estimate;
        
        $session->set("Checkout.EstimateID", $estimate->ID);

        return $estimate;
    }

    /**country
     * Is an estimate set? return false if not
     *
     * @return boolean
     */
    public function hasEstimate()
    {
        return (!empty($this->getEstimate()));
    }

    /**
     * Get a payment method to use (either the default or from a session)
     *
     * @return void
     */
    public function getPaymentMethod()
    {
        $session = $this
            ->getRequest()
            ->getSession();

        $payment_methods = GatewayInfo::getSupportedGateways();
        $payment_session = $session->get('Checkout.PaymentMethodID');
        $method = null;

        if (!empty($payment_session) && array_key_exists($payment_session, $payment_methods)) {
            $method = $payment_session;
        } else {
            $default = key($payment_methods);
            $method = $default;
            $session->set('Checkout.PaymentMethodID', $default);
        }

        return $method;
    }

    /**
     * Get the link to this controller
     * 
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }

    /**
     * Get an absolute link to this controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function AbsoluteLink($action = null)
    {
        return Director::absoluteURL($this->Link($action));
    }

    /**
     * Get a relative (to the root url of the site) link to this
     * controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function RelativeLink($action = null)
    {
        return Controller::join_links(
            Director::baseURL(),
            $this->Link($action)
        );
    }

    /**
     * If content controller exists, return it's menu function
     * @param int $level Menu level to return.
     * @return ArrayList
     */
    public function getMenu($level = 1)
    {
        if (class_exists(ContentController::class)) {
            $controller = ContentController::singleton();
            return $controller->getMenu($level);
        }
    }

    public function Menu($level)
    {
        return $this->getMenu();
    }

    public function init()
    {
        parent::init();

        // Check for subsites and add support
        if (class_exists(Subsite::class)) {
            $subsite = Subsite::currentSubsite();

            if ($subsite && $subsite->Theme) {
                SSViewer::add_themes([$subsite->Theme]);
            }

            if ($subsite && i18n::getData()->validate($subsite->Language)) {
                i18n::set_locale($subsite->Language);
            }
        }

        $session = $this
            ->getRequest()
            ->getSession();

        $estimate = Estimate::get()
            ->byID($session->get("Checkout.EstimateID"));

        if ($estimate && $estimate instanceof Estimate) {
            $this->estimate = $estimate;
        }
    }

    /**
     * Initial login/details screen for the checkout
     * 
     */
    public function index()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }
        
        // If we have turned off login, or member logged in
        $login_form = null;
        $customer_form = $this->CustomerForm();
        $request = $this->getRequest();
        $config = SiteConfig::current_site_config();
        $member = Security::getCurrentUser();

        if (!$member && ($config->CheckoutLoginForm || !$config->CheckoutAllowGuest)) {
            try {
                $security = Injector::inst()->get(Security::class);
                $link = $security->Link('login');
                $auth = null;
                $auth_list = $security->getApplicableAuthenticators();
                $i = 0;

                foreach ($auth_list as $key => $value) {
                    if ($i == 0) {
                        $name = $key;
                        $auth = $value;
                    }
                    $i++;
                }

                $handler = $auth->getLoginHandler(Controller::join_links(
                    $link,
                    $name
                ));

                $login_form = $handler->LoginForm();

                $login_form
                    ->Fields()
                    ->add(
                        HiddenField::create("BackURL")
                            ->setValue($this->Link())
                    );

                $login_form
                    ->setTemplate("\SilverCommerce\Checkout\Forms\CheckoutLoginForm");
            } catch (Exception $e) {
                return $this->httpError(500);
            }
        }

        // If we have just logged in, and we came from the
        // shopping cart, merge defined estimate with the
        // logged in users estimate
        if (class_exists(ShoppingCartFactory::class) && $member) {
            $estimate = $this->getEstimate();
            $cart_estimate = ShoppingCartFactory::create()->getCurrent();

            if (!$estimate || ($cart_estimate->ID != $estimate->ID)) {
                $this->setEstimate($cart_estimate);
            }
        }

        // Update customer form with current estimate
        $customer_form->loadDataFrom($this->getEstimate());

        $this->customise([
            'Title'     => _t('SilverCommerce\Checkout.Checkout', "Checkout"),
            "LoginForm" => $login_form,
            "Form"      => $customer_form
        ]);

        $this->extend("onBeforeIndex");

        return $this->render();
    }

    /**
     * Allowing user to select postage
     *
     * @return array
     */
    public function postage()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }
        
        $config = SiteConfig::current_site_config();
        $member = Security::getCurrentUser();
        $estimate = $this->getEstimate();
        
        // Check permissions for guest checkout
        if (!$member && !$config->CheckoutAllowGuest) {
            return $this->redirect($this->Link());
        }

        if (!$estimate->isDeliverable()) {
            return $this->redirect($this->Link('payment'));
        }

        $this->customise([
            'Form' => $this->PostageForm()
        ]);

        $this->extend("onBeforePostage");

        return $this->render();
    }

    /**
     * Action that gets called before we interface with our payment
     * method.
     *
     * This action is responsible for setting up an order and
     * saving it into the database (as well as a session) and then
     * generates the relevent payment form using omnipay.
     *
     * @param $request Current request object
     */
    public function payment()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }

        $estimate = $this->getEstimate();

        // If estimate does not have a shipping address, restart checkout 
        if (empty(trim($estimate->BillingAddress))) {
            return $this->redirect($this->Link());
        }

        // If estimate is deliverable and has no billing details,
        // restart checkout
        if ($estimate->isDeliverable() && empty(trim($estimate->DeliveryAddress))) {
            return $this->redirect($this->Link());
        }

        // Check if payment ID set and corresponds
        try {
            $gateway_form = $this->GatewayForm();
            $payment_form = $this->PaymentForm();

            $this->customise([
                "GatewayForm" => $gateway_form,
                "PaymentForm" => $payment_form
            ]);

            $this->extend("onBeforePayment");

            return $this->render();   
        } catch (Exception $e) {
            return $this->httpError(
                400,
                $e->getMessage()
            );
        }
    }

    /**
     * Deal with rendering a completion message to the end user
     *
     * @return string
     */
    public function complete()
    {
        $session = $this
            ->getRequest()
            ->getSession();
        
        $site = SiteConfig::current_site_config();
        $id = $this->request->param('ID');
        $error = ($id == "error") ? true : false;

        if ($error) {
            $return = [
                'Title' => _t('SilverCommerce\Checkout.OrderProblem', 'There was a problem with your order'),
                'Content' => $site->dbobject("PaymentFailerContent")
            ];
        } else {
            $return = [
                'Title' => _t('SilverCommerce\Checkout.ThankYouForOrder', 'Thank you for your order'),
                'Content' => $site->dbobject("PaymentSuccessContent")
            ];
        }

        // Add the paid order data to our completed page
        $return["Invoice"] = $this->getEstimate();

        $this->customise($return);

        // Extend our completion process, to allow for custom
        // completion actions
        $this->extend("onBeforeComplete");

        // Clear our session data
        if (!$error && isset($_SESSION)) {
            $session->clear('Checkout.PaymentMethodID');
            $session->clear('Checkout.EstimateID');
        }

        // If we are using the shopping cart, clear that as well
        if (class_exists(ShoppingCartFactory::class)) {
            ShoppingCartFactory::create()->delete();
        }

        return $this->render();
    }

    /**
     * Special function to be loaded when no estimate is
     * available
     *
     * @return string
     */
    public function noestimate()
    {
        // If the shoppingcart module is installed we should
        // redirect to it when estimate is lost.
        if (class_exists(ShoppingCartFactory::class)) {
            $shopping_cart = ShoppingCartFactory::create()->getCurrent();
            return $this->redirect($shopping_cart->Link());
        }

        $this->customise([
            'Title' => _t(
                'Checkout.OrderProblem',
                'There was a problem with your order'
            ),
            'Content' => _t(
                'Checkout.NoEstimateContent',
                'Their was a problem retrieveing the details of your order, please return to the shopping cart and try again.'
            )
        ]);

        $this->extend("onBeforeNoEstimate");

        return $this->render();
    }

    /**
     * Form to capture the customers details
     *
     * @return CustomerDetailsForm
     */
    public function CustomerForm()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }

        $session = $this
            ->getRequest()
            ->getSession();

        $member = Security::getCurrentUser();
        $contact = ($member) ? $member->Contact() : $member;

        $form = CustomerDetailsForm::create(
            $this,
            'CustomerForm',
            $this->getEstimate()
        );

        $data = $session->get("Checkout." . $form->FormName() . ".data");
        
        if (is_array($data)) {
            $form->loadDataFrom($data);
        } elseif ($contact) {
            // Fill email, phone, etc
            $form->loadDataFrom($contact);
            
            // Then fill with Address info
            if($contact->DefaultLocation()) {
                $form->loadDataFrom($contact->DefaultLocation());
            }
        }

        $this->extend("updateCustomerForm", $form);

        return $form;
    }

    /**
     * Form to find postage options and allow user to select payment
     *
     * @return Form
     */
    public function PostageForm()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }

        $estimate = $this->getEstimate();

        $form = PostageForm::create(
            $this,
            "PostageForm",
            $estimate,
            $estimate->SubTotal,
            $estimate->TotalWeight,
            $estimate->TotalItems,
            $estimate->DeliveryCountry,
            $estimate->DeliveryCounty
        );

        $form->setLegend(_t('SilverCommerce\Checkout.Postage', "Postage"));

        $proceed_action = $form->Actions()->fieldByName("action_doSetPostage");

        if (isset($proceed_action)) {
            $fields = $form->Fields();
            $fields->push(HiddenField::create(
                "CompleteURL",
                null,
                $this->Link("payment")
            ));

            $proceed_action
                ->setTitle(_t(
                    'SilverCommerce\Checkout.PayNow',
                    "Pay Now"
                ))->removeExtraClass("btn-secondary")
                ->addExtraClass("btn-success");
        }

        // Extension call
        $this->extend("updatePostageForm", $form);

        return $form;
    }

        /**
     * Generate a gateway form to select available gateways
     * 
     * @return Form
     */
    public function GatewayForm()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }

        $actions = FieldList::create();

        try {
            // Get available payment methods and setup payment
            $payment_methods = GatewayInfo::getSupportedGateways();
            $reverse = array_reverse($payment_methods);
            $gateway = $this->getPaymentMethod();

            $payment_field = OptionsetField::create(
                'PaymentMethodID',
                _t('SilverCommerce\Checkout.PaymentSelection', 'Please choose how you would like to pay'),
                $payment_methods
            )->setValue($gateway);

            $actions
                ->add(FormAction::create(
                    'doUpdatePayment',
                    _t('SilverCommerce\Checkout.ChangeGateway', 'Change Payment Type')
                )->addExtraClass('checkout-action-next'));
        } catch (Exception $e) {
            $payment_field = ReadonlyField::create(
                "PaymentMethodID",
                _t('SilverCommerce\Checkout.PaymentSelection', 'Please choose how you would like to pay'),
                $e->getMessage()
            );
        } 

        $form = Form::create(
            $this,
            "GatewayForm",
            FieldList::create($payment_field),
            $actions,
            RequiredFields::create(array(
                "PaymentMethodID"
            ))
        );

        $this->extend("updateGatewayForm", $form);

        return $form;
    }

    /**
     * Generate a payment form using omnipay scafold
     *
     * @return Form
     */
    public function PaymentForm()
    {
        // If no estimate found, generate error
        if (!$this->hasEstimate()) {
            return $this->redirect($this->Link("noestimate"));
        }
        
        $factory = new GatewayFieldsFactory($this->getPaymentMethod());

        $form = Form::create(
            $this,
            "PaymentForm",
            $factory->getFields(),
            FieldList::create(
                FormAction::create(
                    "doSubmitPayment",
                    _t("SilverCommerce\Checkout.PayNow", "Pay Now")
                )->addExtraClass("btn btn-success btn-block")
                ->setUseButtonTag(true)
            )
        );

        $this->extend("updatePaymentForm", $form);

        return $form;
    }

    /**
     * Update the selected payment gateway
     *
     * @param array $data
     * @param Form $form
     * @return redirect
     */
    public function doUpdatePayment($data, $form)
    {
        $session = $this
            ->getRequest()
            ->getSession();

        $session->set(
            "Checkout.PaymentMethodID",
            $data["PaymentMethodID"]
        );        
        
        return $this->redirectBack();        
    }

    public function doSubmitPayment($data, $form)
    {
        $session = $this
            ->getRequest()
            ->getSession();
        
        $order = $this->getEstimate();
        $order = $order->convertToInvoice();

        $config = SiteConfig::current_site_config();

        // Get thre digit currency code
        $number_format = new NumberFormatter(
            $config->SiteLocale,
            NumberFormatter::CURRENCY
        );
        $currency_code = $number_format
            ->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        
        // Map our order data to an array to omnipay
        $omnipay_data = [];
        $omnipay_map = $this->config()->omnipay_map;
        
        foreach ($omnipay_map as $inv_key => $op_key) {
            if (!empty($order->{$inv_key})) {
                $omnipay_data[$op_key] = $order->{$inv_key};
            }
        }
        
        $omnipay_data = array_merge($omnipay_data, $data);

        // Set a description for this payment
        $omnipay_data["description"] = _t(
            "Order.PaymentDescription",
            "Payment for Order: {ordernumber}",
            ['ordernumber' => $order->FullRef]
        );

        // Create the payment object. We pass the desired success and failure URLs as parameter to the payment
        $payment = Payment::create()
            ->init(
                $this->getPaymentMethod(),
                MathsHelper::round($order->Total, 2),
                $currency_code
            )->setSuccessUrl($this->AbsoluteLink('complete'))
            ->setFailureUrl(Controller::join_links(
                $this->AbsoluteLink('complete'),
                "error"
            ));
        
        // Map order ID & save to generate an ID
        $payment->InvoiceID = $order->ID;
        $payment->write();

        // Add an extension before we finalise the payment
        // so we can overwrite our data
        $this->extend("onBeforeSubmit", $payment, $order, $omnipay_data);

        $service = ServiceFactory::create()
            ->getService($payment, ServiceFactory::INTENT_PAYMENT);

        $response = ServiceFactory::create()
            ->getService($payment, ServiceFactory::INTENT_PAYMENT)
            ->initiate($omnipay_data);

        $this->extend("onBeforeRedirect", $payment, $order, $response);

        return $response->redirectOrRespond();
    }

    /**
     * Generate a random number based on the current time, a random int
     * and a third int that can be passed as a param.
     * 
     * @param $int integer that can make the number "more random"
     * @param $length Length of the string
     * @return Int
     */
    public static function getRandomNumber($int = 1, $length = 16)
    {
        return substr(md5(time() * rand() * $int), 0, $length);
    }
}
