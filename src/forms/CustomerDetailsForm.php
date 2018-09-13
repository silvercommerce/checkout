<?php

namespace SilverCommerce\Checkout\Forms;

use Locale;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\Form;
use SilverStripe\Security\Group;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Security\Security;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverCommerce\ContactAdmin\Model\ContactLocation;
use SilverCommerce\GeoZones\Forms\RegionSelectionField;
use ilateral\SilverStripe\Users\Control\RegisterController;

/**
 * Form for collecting customer details and assigning them to
 * an estimate
 *
 * @package checkout
 */
class CustomerDetailsForm extends Form
{
    /**
     * Estimate associateed with this form.
     *
     * @var Estimate
     */
    protected $estimate;

    public function getEstimate() {
        return $this->estimate;
    }

    public function setEstimate(Estimate $estimate) {
        $this->estimate = $estimate;
        return $this;
    }

    /**
     * Contact associateed with this form.
     *
     * @var Contact
     */
    protected $contact;

    public function getContact() {
        return $this->contact;
    }

    public function setContact(Contact $contact) {
        $this->contact = $contact;
        return $this;
    }

    public function __construct($controller, $name = "CustomerDetailsForm", Estimate $estimate)
    {
        $member = Security::getCurrentUser();
        $contact = ($member) ? $member->Contact() : null;
        $config = SiteConfig::current_site_config();
        $session = $this->getSession();

        $this->setController($controller);
        $this->setName($name);
        $this->setEstimate($estimate);

        if (!empty($contact)) {
            $this->setContact($contact);
        }

        $fields = FieldList::create();

        // Set default form parameters
        $data = $session->get("FormInfo.{$this->FormName()}.settings"); 

        $new_billing = false;
        $same_shipping = 1;
        $new_shipping = false;
        
        if (isset($data['NewBilling'])) {
            $new_billing = $data['NewBilling'];
        }
        
        if (isset($data['DuplicateDelivery'])) {
            $same_shipping = $data['DuplicateDelivery'];
        }

        if (isset($data['NewShipping'])) {
            $new_shipping = $data['NewShipping'];
        }

        // Setup billing fields
        if (!$new_billing && $contact && $contact->Locations()->exists()) {
            $fields->merge($this->getBillingDropdownFields());
        } else {
            $fields->merge($this->getBillingFields());
        }

        // If cart is deliverable, add shipping detail fields
        if ($estimate->isDeliverable()) {
            if (!$new_shipping && $contact && $contact->Locations()->count() > 1) {
                $fields->merge($this->getDeliveryDropdownFields());
            } else {
                $fields->merge($this->getDeliveryFields());
            }

            $fields->push(
                CheckboxField::create(
                    'DuplicateDelivery',
                    _t('SilverCommerce\Checkout.DeliverHere', 'Deliver to this address?')
                )->setValue($same_shipping)
            );
        }

        // If we have turned off login, or member logged in
        if (($config->CheckoutLoginForm || !$config->CheckoutAllowGuest) && !$member) {
            if ($config->CheckoutAllowGuest == true) {
                $register_title = _t('SilverCommerce\Checkout.CreateAccountOptional', 'Create Account (Optional)');
            } else {
                $register_title = _t('SilverCommerce\Checkout.CreateAccountRequired', 'Create Account (Required)');                
            }

            $fields->add(
                CompositeField::create(
                    HeaderField::create(
                        'CreateAccountHeader',
                        $register_title,
                        3
                    ),
                    $pw_field = ConfirmedPasswordField::create("Password")
                        ->setAttribute('formnovalidate',true)
                )->setName("PasswordFields")
            );

            if ($config->CheckoutLoginForm && !$member && $config->CheckoutAllowGuest) {
                $pw_field->setCanBeEmpty(true);
            }
        }

        parent::__construct(
            $controller,
            $name, 
            $fields,
            FieldList::create(
                FormAction::create(
                    'doContinue',
                    _t('SilverCommerce\Checkout.Continue', 'Continue')
                )->addExtraClass('checkout-action-next')
            ),
            $this->getRequiredFields($data)
        );

        if (is_array($data)) {
            $this->loadDataFrom($data);
        }

        if ($contact && $contact->Locations()->exists()) {
            $this->loadDataFrom($contact->DefaultLocation());
        }
    }

    /**
     * Generate a FieldList of billing address fields
     * 
     * @return FieldList
     */
    public function getBillingFields()
    {
        $contact = $this->getContact();

        $fields = FieldList::create(
            CompositeField::create(

                // Personal details fields
                CompositeField::create(
                    TextField::create(
                        'FirstName',
                        _t('SilverCommerce\Checkout.FirstName', 'First Name(s)')
                    ),
                    TextField::create(
                        'Surname',
                        _t('SilverCommerce\Checkout.Surname', 'Surname')
                    ),
                    TextField::create(
                        "Company",
                        _t('SilverCommerce\Checkout.Company', "Company")
                    )->setRightTitle(_t(
                        "SilverCommerce\Checkout.Optional",
                        "Optional"
                    )),
                    EmailField::create(
                        'Email',
                        _t('SilverCommerce\Checkout.Email', 'Email')
                    ),
                    TextField::create(
                        'PhoneNumber',
                        _t('SilverCommerce\Checkout.Phone', 'Phone Number')
                    )
                )->setName("PersonalFields"),

                // Address details fields
                $address_fields = CompositeField::create(
                    TextField::create(
                        'Address1',
                        _t('SilverCommerce\Checkout.Address1', 'Address Line 1')
                    ),
                    TextField::create(
                        'Address2',
                        _t('SilverCommerce\Checkout.Address2', 'Address Line 2')
                    )->setRightTitle(_t(
                        "SilverCommerce\Checkout.Optional",
                        "Optional"
                    )),
                    TextField::create(
                        'City',
                        _t('SilverCommerce\Checkout.City', 'City')
                    ),
                    DropdownField::create(
                        'Country',
                        _t('SilverCommerce\Checkout.Country', 'Country'),
                        i18n::getData()->getCountries(),
                        Locale::getRegion(i18n::get_locale())
                    ),
                    RegionSelectionField::create(
                        "County",
                        _t('SilverCommerce\Checkout.Region', "County/State"),
                        "Country"
                    ),
                    TextField::create(
                        'PostCode',
                        _t('SilverCommerce\Checkout.PostCode', 'Post Code')
                    )
                )->setName("AddressFields")
            )->setName("BillingFields")
            ->setColumnCount(2)
        );

        // Add a save address for later checkbox if a user is logged in
        if (!empty($contact)) {
            $address_fields->push(CheckboxField::create(
                "SaveBillingAddress",
                _t('SilverCommerce\Checkout.SaveAddress', 'Save Address')
            ));

            if ($contact && $contact->Locations()->exists()) {
                $address_fields->push(FormAction::create(
                    'doUseSavedBilling',
                    _t(
                        'SilverCommerce\Checkout.UseSavedAddress',
                        'Use Saved Address'
                    )
                )->addextraClass('btn btn-link')
                ->setAttribute('formnovalidate',true));
            }
        }

        return $fields;
    }

    /**
     * Generate a FieldList of billing fields if the user has saved addresses
     * 
     * @return FieldList
     */
    public function getBillingDropdownFields()
    {
        $contact = $this->getContact();

        return FieldList::create(
            CompositeField::create(
                DropdownField::create(
                    'BillingAddress',
                    _t(
                        'SilverCommerce\Checkout.BillingAddress',
                        'Billing Address'
                    ),
                    $contact->Locations()
                ),
                FormAction::create(
                    'doAddNewBilling',
                    _t(
                        'SilverCommerce\Checkout.NewAddress',
                        'Use different address'
                    )
                )->addextraClass('btn btn-link')
                ->setAttribute('formnovalidate',true)
            )->setName("BillingFields")
        );
    }

    /**
     * Generate a FieldList of delivery address fields
     * 
     * @return FieldList
     */
    public function getDeliveryFields()
    {
        $contact = $this->getContact();

        $fields = FieldList::create(
            CompositeField::create(
                CompositeField::create(
                    TextField::create(
                        'DeliveryCompany',
                        _t('SilverCommerce\Checkout.Company', 'Company')
                    )->setRightTitle(_t(
                        "SilverCommerce\Checkout.Optional",
                        "Optional"
                    )),
                    TextField::create(
                        'DeliveryFirstName',
                        _t('SilverCommerce\Checkout.FirstName', 'First Name(s)')
                    ),
                    TextField::create(
                        'DeliverySurname',
                        _t('SilverCommerce\Checkout.Surname', 'Surname')
                    )
                )->setName("PersonalFields"),

                $address_fields = CompositeField::create(
                    TextField::create(
                        'DeliveryAddress1',
                        _t('SilverCommerce\Checkout.Address1', 'Address Line 1')
                    ),
                    TextField::create(
                        'DeliveryAddress2',
                        _t('SilverCommerce\Checkout.Address2', 'Address Line 2')
                    )->setRightTitle(_t("SilverCommerce\Checkout.Optional", "Optional")),
                    TextField::create(
                        'DeliveryCity',
                        _t('SilverCommerce\Checkout.City', 'City')
                    ),
                    DropdownField::create(
                        'DeliveryCountry',
                        _t('SilverCommerce\Checkout.Country', 'Country'),
                        i18n::getData()->getCountries(),
                        Locale::getRegion(i18n::get_locale())
                    ),
                    RegionSelectionField::create(
                        "DeliveryCounty",
                        _t('SilverCommerce\Checkout.Region', "County/State"),
                        "DeliveryCountry"
                    ),
                    TextField::create(
                        'DeliveryPostCode',
                        _t('SilverCommerce\Checkout.PostCode', 'Post Code')
                    )
                )->setName("AddressFields")
            )->setName("DeliveryFields")
            ->setColumnCount(2)
        );

        // Add a save address for later checkbox if a user is logged in
        if ($contact) {
            $address_fields->push(CheckboxField::create(
                "SaveShippingAddress",
                _t('SilverCommerce\Checkout.SaveAddress', 'Save Address')
            ));
        }

        if ($contact && $contact->Locations()->count() > 1) {
            $address_fields->push(FormAction::create(
                'doUseSavedShipping',
                _t(
                    'SilverCommerce\Checkout.UseSavedAddress',
                    'Use Saved Address'
                )
            )->addextraClass('btn btn-link')
            ->setAttribute('formnovalidate',true));
        }

        return $fields;
    }

    /**
     * Generate a FieldList containing fields for if the user
     * has existing locations
     * 
     * @return FieldList
     */
    public function getDeliveryDropdownFields()
    {
        $contact = $this->getContact();

        return FieldList::create(
            CompositeField::create(
                DropdownField::create(
                    'ShippingAddress',
                    _t(
                        'SilverCommerce\Checkout.DeliveryAddress',
                        'Delivery Address'
                    ),
                    $contact->Locations()
                ),
                FormAction::create(
                    'doAddNewShipping',
                    _t(
                        'SilverCommerce\Checkout.UseDifferentAddress',
                        'Use Different Address'
                    )
                )->addextraClass('btn btn-link')
                ->setAttribute('formnovalidate',true)
            )->setName("DeliveryFields")
        );
    }

    public function getRequiredFields($data)
    {
        $contact = $this->getContact();
        $estimate = $this->getEstimate();
        $deliverable = true;

        if (is_array($data) && isset($data['NewBilling'])) {
            $new_billing = $data['NewBilling'];
        } else {
            $new_billing = false;
        }

        if ($new_billing || !$contact || ($contact && !$contact->Locations()->exists())) {
            $billing_dropdown = false;
        } else {
            $billing_dropdown = true;
        }
        
        $deliverable = $estimate->isDeliverable();

        return CheckoutValidator::create($deliverable, $billing_dropdown);
    }

    public function doAddNewBilling($data) 
    {
        $data['NewBilling'] = true;
        return $this->doUpdateForm($data);        
    }

    public function doAddNewShipping($data) 
    {
        $data['NewShipping'] = true;
        return $this->doUpdateForm($data);        
    }

    public function doUseSavedBilling($data) 
    {
        $data['NewBilling'] = false;
        return $this->doUpdateForm($data);        
    }

    public function doUseSavedShipping($data) 
    {
        $data['NewShipping'] = false;
        return $this->doUpdateForm($data);
    }

    public function doUpdateForm($data) 
    {
        if (!isset($data['DuplicateDelivery'])) {
            $data['DuplicateDelivery'] = 0;
        }

        $session = $this->getSession();
        $session->set("FormInfo.{$this->FormName()}.settings", $data);
        
        return $this->getController()->redirectBack();
    }

    /**
     * Method used to save all data to an order and redirect to the order
     * summary page
     *
     * @param $data Form data
     *
     * @return Redirect
     */
    public function doContinue($data)
    {
        $member = Security::getCurrentUser();
        $config = SiteConfig::config();
        $estimate = $this->getEstimate();
        $session = $this->getSession();

        $invalid_keys = [
            "ID",
            "ClassName",
            "RecordClassName",
            "LastEdited",
            "Created",
            "ContactID"
        ];

        // If we are using a saved address, push to estimate
        if (!isset($data['Address1']) && isset($data['BillingAddress'])) {
            $billing_address = ContactLocation::get()
                ->byID($data['BillingAddress']);

            if (isset($billing_address)) {
                foreach ($billing_address->toMap() as $key => $value) {
                    if (!in_array($key, $invalid_keys)) {
                        $estimate->{$key} = $value;
                    }
                }
            }
        }

        // If we are using the billing details for delivery, duplicate them
        if (isset($data['DuplicateDelivery']) && $data['DuplicateDelivery'] == 1) {
            // Find any submitted data that is delivery and copy the data from
            // the standard data
            foreach ($data as $key => $value) {
                if (strpos($key, "Delivery") !== false) {
                    $non_del_key = str_replace("Delivery", "", $key);

                    if (isset($data[$non_del_key]) && !empty($data[$non_del_key])) {
                        $data[$key] = $data[$non_del_key];
                    }
                }
            }
        } elseif (!isset($data['DeliveryAddress1']) && isset($data['ShippingAddress'])) {
            $delivery_address = ContactLocation::get()
                ->byID($data['ShippingAddress']);

            if (isset($delivery_address)) {
                foreach ($delivery_address->toMap() as $key => $value) {
                    if (!in_array($key, $invalid_keys)) {
                        $key = "Delivery" . $key;
                        $estimate->{$key} = $value;
                    }
                }
            }
        }

        // If the user is selected 
        if (!$member && isset($data['Password']) && isset($data['Password']["_Password"])  && !empty($data['Password']["_Password"])) {
            $member = $this->registerUser($data);

            if (!$member) {
                return $this
                    ->getController()
                    ->redirectBack();
            } else {
                $data['SaveBillingAddress'] = true;
            }
        }

        // Update current form with any new data
        $this->loadDataFrom($data);
        $this->saveInto($estimate);

        if ($member) {
            $contact = $member->Contact();
            $estimate->CustomerID = $contact->ID;

            if (isset($data['SaveBillingAddress']) && $data['SaveBillingAddress'] == 1) {
                $this->save_billing_address($data);
            }

            if (isset($data['SaveShippingAddress']) && $data['SaveShippingAddress'] == 1) {
                $this->save_shipping_address($data);
            }
        }

        $estimate->write();

        $session->clear("FormInfo.{$this->FormName()}.settings");

        $url = $this
            ->getController()
            ->Link("postage");

        return $this
            ->getController()
            ->redirect($url);
    }

    /**
     * Register an existing user with the system and return (or return false on failier)
     * 
     * @return Member|null
     */
    public function registerUser($data)
    {
        $session = $this->getSession();

        $member = Member::get()
                ->filter("Email", $data["Email"])
                ->first();

        // Check if a user already exists
        if ($member) {
            $this->sessionMessage(
                "Sorry, an account already exists with those details.",
                "bad"
            );

            // Load errors into session and post back
            $session->set("Form.{$this->FormName()}.data", $data);
            $session->set("FormInfo.{$this->FormName()}.settings", $data);
            return false;
        }

        $member = Member::create();

        // If we have the users module installed, tap into its
        // registration process, else use the built in process.
        if (class_exists(RegisterController::class)) {
            $member->Register($data);
            $member->write();
        } else {
            $this->saveInto($member);
            $member->write();

            // Add member to the customers group
            $group = Group::get()->find("Code", "ecommerce-customers");
            
            if ($group) {
                $group->Members()->add($member);
                $group->write();
            }

            // Login this new member temporarily
            $identityStore = Injector::inst()
                ->get(IdentityStore::class);
            $identityStore->logIn(
                $member,
                false,
                $this->getRequest()
            );
        }
    
        if (!$member) {
            return false;
        }

        return $member;
    }

    /**
     * If the flag has been set from the provided array, create a new
     * address and assign to the current user.
     *
     * @param $data Form data submitted
     */
    private function save_billing_address($data)
    {
        $member = Security::getCurrentUser();
        
        // If the user ticked "save address" then add to their account
        if ($member && array_key_exists('SaveBillingAddress', $data) && $data['SaveBillingAddress'] == 1) {
            // First save the details to the users account if they aren't set
            // We don't save email, as this is used for login
            $member->FirstName = ($member->FirstName) ? $member->FirstName : $data['FirstName'];
            $member->Surname = ($member->Surname) ? $member->Surname : $data['Surname'];
            $member->write();

            $contact = $member->Contact();
            $this->saveInto($contact);
            $contact->write();

            $address = ContactLocation::create();
            $this->saveInto($address);
            $address->ContactID = $contact->ID;
            $address->write();
        }
    }

    private function save_shipping_address($data)
    {
        $member = Security::getCurrentUser();

        // If the user ticked "save address" then add to their account
        if ($member && array_key_exists('SaveShippingAddress', $data) && $data['SaveShippingAddress'] == 1) {
            // First save the details to the users account if they aren't set
            // We don't save email, as this is used for login
            $member->FirstName = ($member->FirstName) ? $member->FirstName : $data['FirstName'];
            $member->Surname = ($member->Surname) ? $member->Surname : $data['Surname'];
            $member->write();

            $contact = $member->Contact();

            $address = ContactLocation::create();
            $address->Company = $data['DeliveryCompany'];
            $address->FirstName = $data['DeliveryFirstName'];
            $address->Surname = $data['DeliverySurname'];
            $address->Address1 = $data['DeliveryAddress1'];
            $address->Address2 = $data['DeliveryAddress2'];
            $address->City = $data['DeliveryCity'];
            $address->PostCode = $data['DeliveryPostCode'];
            $address->County = $data['DeliveryCounty'];
            $address->Country = $data['DeliveryCountry'];
            $address->ContactID = $contact->ID;
            $address->write();
        }
    }
}
