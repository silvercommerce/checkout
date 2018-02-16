<?php

namespace SilverCommerce\Checkout\Forms;

use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\i18n\i18n;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\ContactAdmin\Model\ContactLocation;
use SilverCommerce\Checkout\Control\Checkout;

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
        $new_billing = isset($data['NewBilling']) ? $data['NewBilling'] : false;
        $same_shipping = isset($data['DuplicateDelivery']) ? $data['DuplicateDelivery'] : 1;
        $new_shipping = isset($data['NewShipping']) ? $data['NewShipping'] : false;

        $billing_fields = $this->getBillingFields($data);
        $delivery_fields = $this->getDeliveryFields($data);

        if ($billing_fields) {
            $fields->add($billing_fields);
        }

        if ($delivery_fields) {
            $fields->add($delivery_fields);
        }

        // If cart is deliverable, add shipping detail fields
        if (!$estimate->isCollection() && $estimate->isDeliverable()) {
            $fields->add(
                CheckboxField::create(
                    'DuplicateDelivery',
                    _t('Checkout.DeliverHere', 'Deliver to this address?')
                )->setValue($same_shipping)
            );
        }

        // If we have turned off login, or member logged in
        if ($config->CheckoutLoginForm && !$member) {
            if ($config->CheckoutAllowGuest == true) {
                $register_title = _t('Checkout.CreateAccountOptional', 'Create Account (Optional)');
            } else {
                $register_title = _t('Checkout.CreateAccountRequired', 'Create Account (Required)');                
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

            if ($config->CheckoutLoginForm && !$member) {
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
                    _t('Checkout.Continue', 'Continue')
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

    public function getBillingFields($data)
    {
        $contact = $this->getContact();
        $return = null;

        if (is_array($data) && isset($data['NewBilling'])) {
            $new_billing = $data['NewBilling'];
        } else {
            $new_billing = false;
        }

        // Is user logged in and has saved addresses
        if (!$new_billing && $contact && $contact->Locations()->exists()) {
            // Generate saved address dropdown
            $return = CompositeField::create(
                DropdownField::create(
                    'BillingAddress',
                    _t('Checkout.BillingAddress','Billing Address'),
                    $contact->Locations()->map()
                ),
                FormAction::create(
                    'doAddNewBilling',
                    _t('Checkout.NewAddress', 'Use different address')
                )->addextraClass('btn btn-primary')
                ->setAttribute('formnovalidate',true)                
            )->setName('SavedBilling');
        } else {
            $return = CompositeField::create(
                // Personal details fields
                CompositeField::create(
                    TextField::create(
                        'FirstName',
                        _t('Checkout.FirstName', 'First Name(s)')
                    ),
                    TextField::create(
                        'Surname',
                        _t('Checkout.Surname', 'Surname')
                    ),
                    TextField::create(
                        "Company",
                        _t('Checkout.Company', "Company")
                    )->setRightTitle(_t("Checkout.Optional", "Optional")),
                    EmailField::create(
                        'Email',
                        _t('Checkout.Email', 'Email')
                    ),
                    TextField::create(
                        'PhoneNumber',
                        _t('Checkout.Phone', 'Phone Number')
                    )
                )->setName("PersonalFields"),

                // Address details fields
                CompositeField::create(
                    TextField::create(
                        'Address1',
                        _t('Checkout.Address1', 'Address Line 1')
                    ),
                    TextField::create(
                        'Address2',
                        _t('Checkout.Address2', 'Address Line 2')
                    )->setRightTitle(_t("Checkout.Optional", "Optional")),
                    TextField::create(
                        'City',
                        _t('Checkout.City', 'City')
                    ),
                    TextField::create(
                        'State',
                        _t('Checkout.StateCounty', 'State/County')
                    ),
                    TextField::create(
                        'PostCode',
                        _t('Checkout.PostCode', 'Post Code')
                    ),
                    DropdownField::create(
                        'Country',
                        _t('Checkout.Country', 'Country'),
                        i18n::getData()->getCountries()
                    )->setEmptyString("")
                )->setName("AddressFields")
            )->setName("BillingFields")
            ->setColumnCount(2);

            // Add a save address for later checkbox if a user is logged in
            if (!empty($contact)) {
                $return->push(CompositeField::create(
                    CheckboxField::create(
                        "SaveBillingAddress",
                        _t('Checkout.SaveBillingAddress', 'Save this address for later')
                    ))->setName("SaveBillingAddressHolder")
                );
            }
        }

        return $return;
    }

    public function getDeliveryFields($data)
    {
        $member = Security::getCurrentUser();
        $contact = $this->getContact();
        $estimate = $this->getEstimate();
        $return = null;

        if (is_array($data) && isset($data['NewShipping'])) {
            $new_shipping = $data['NewShipping'];
        } else {
            $new_shipping = false;
        }

        // If cart is for collection, or not deliverable, add no fields
        if ($estimate->isCollection() || !$estimate->isDeliverable()) {
            return;
        }

        if (!$new_shipping && $contact && $contact->Locations()->count() > 1) {
            $return = CompositeField::create(
                DropdownField::create(
                    'ShippingAddress',
                    _t('Checkout.ShippingAddress','Shipping Address'),
                    $contact->Locations()->map()
                ),
                FormAction::create(
                    'doAddNewShipping',
                    _t('Checkout.NewAddress', 'Use different address')
                )->addextraClass('btn btn-primary')
                ->setAttribute('formnovalidate',true)
            )->setName('SavedShipping');
        } else {
            $return = CompositeField::create(
                CompositeField::create(
                    TextField::create('DeliveryCompany', _t('Checkout.Company', 'Company'))
                        ->setRightTitle(_t("Checkout.Optional", "Optional")),
                    TextField::create('DeliveryFirstName', _t('Checkout.FirstName', 'First Name(s)')),
                    TextField::create('DeliverySurname', _t('Checkout.Surname', 'Surname'))
                )->setName("PersonalFields"),
                $address_fields = CompositeField::create(
                    TextField::create('DeliveryAddress1', _t('Checkout.Address1', 'Address Line 1')),
                    TextField::create('DeliveryAddress2', _t('Checkout.Address2', 'Address Line 2'))
                        ->setRightTitle(_t("Checkout.Optional", "Optional")),
                    TextField::create('DeliveryCity', _t('Checkout.City', 'City')),
                    TextField::create('DeliveryState', _t('Checkout.StateCounty', 'State/County')),
                    TextField::create('DeliveryPostCode', _t('Checkout.PostCode', 'Post Code')),
                    DropdownField::create(
                        'DeliveryCountry',
                        _t('Checkout.Country', 'Country'),
                        i18n::getData()->getCountries()
                    )->setEmptyString("")
                )->setName("AddressFields")
            )->setName("DeliveryFields")
            ->setColumnCount(2);

            if ($contact && $contact->Locations()->exists()) {
                $address_fields->push(
                    FormAction::create(
                        'doUseSavedShipping',
                        _t('Checkout.SavedAddress', 'Use saved address')
                    )->addextraClass('btn btn-primary')
                    ->setAttribute('formnovalidate',true)
                );
            }

            // Add a save address for later checkbox if a user is logged in
            if ($member) {
                $address_fields->push(CheckboxField::create(
                    "SaveShippingAddress",
                    _t('Checkout.SaveShippingAddress', 'Save this address for later')
                ));
            }
        }

        return $return;
    }

    public function getRequiredFields($data)
    {
        $contact = $this->getContact();
        $estimate = $this->getEstimate();
        $config = SiteConfig::current_site_config();
        $return = new CheckoutValidator();

        if (is_array($data) && isset($data['NewBilling'])) {
            $new_billing = $data['NewBilling'];
        } else {
            $new_billing = false;
        }

        if (is_array($data) && isset($data['NewShipping'])) {
            $new_shipping = $data['NewShipping'];
        } else {
            $new_shipping = false;
        }

        if ($config->CheckoutAllowGuest == false) {
            $return->addRequiredField('Password');
        }

        if (!$new_billing && $contact && $contact->Locations()->exists()) {
            $return->addRequiredField('BillingAddress');
        } else {
            $return->appendRequiredFields(new RequiredFields(
                'FirstName',
                'Surname',
                'Address1',
                'City',
                'PostCode',
                'Country',
                'Email',
                'PhoneNumber'
            ));
        }

        if (!$estimate->isCollection() && $estimate->isDeliverable()) {
            if (!$new_shipping && $contact && $contact->Locations()->exists()) {
                $return->addRequiredField('ShippingAddress');
            } else {
                $return->appendRequiredFields(new RequiredFields(
                    'DeliveryFirstName',
                    'DeliverySurname',
                    'DeliveryAddress1',
                    'DeliveryCity',
                    'DeliveryPostCode',
                    'DeliveryCountry'
                ));
            }
        }

        return $return;
    }

    /** ## Form Processing ## **/
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
        $session = $this->getSession();
        
        if (!isset($data['Address1']) && isset($data['BillingAddress'])) {
            $billing_address = ContactLocation::get()->byID($data['BillingAddress']);
            foreach ($billing_address->toMap() as $key => $value) {
                $data[$key] = $value;
            }
        }

        if (isset($data['DuplicateDelivery']) && $data['DuplicateDelivery'] == 1) {
            $data['DeliveryCompany'] = isset($data['Company']) ? $data['Company'] : '';
            $data['DeliveryFirstName'] = isset($data['FirstName']) ? $data['FirstName'] : '';
            $data['DeliverySurname'] = isset($data['Surname']) ? $data['Surname'] : '';
            $data['DeliveryAddress1'] = isset($data['Address1']) ? $data['Address1'] : '';
            $data['DeliveryAddress2'] = isset($data['Address2']) ? $data['Address2'] : '';
            $data['DeliveryCity'] = isset($data['City']) ? $data['City'] : '';
            $data['DeliveryState'] = isset($data['State']) ? $data['State'] : '';
            $data['DeliveryPostCode'] = isset($data['PostCode']) ? $data['PostCode'] : '';
            $data['DeliveryCountry'] = isset($data['Country']) ? $data['Country'] : '';
        } elseif (!isset($data['DeliveryAddress1']) && isset($data['ShippingAddress'])) {
            $shipping_address = ContactLocation::get()->ByID($data['ShippingAddress']);
            foreach ($shipping_address->toMap() as $key => $value) {
                $data['Delivery'.$key] = $value;
            }
        }

        $session->set("FormInfo.{$this->FormName()}.settings",$data);       
        
        if (!$member && ($config->CheckoutAllowguest || isset($data['Password']))) {
            $this->registerUser($data);
        }

        if ($member) {
            $estimate = $this->getEstimate();
            $this->saveInto($estimate);

            foreach ($data as $key => $value) {
                $estimate->{$key} = $value;
            }
            
            if (isset($data['SaveBillingAddress']) && $data['SaveBillingAddress'] == 1) {
                $this->save_billing_address($data);
            }
            if (isset($data['SaveShippingAddress']) && $data['SaveShippingAddress'] == 1) {
                $this->save_shipping_address($data);
            }
        }
            
        $session->set('Checkout.CustomerDetails.data',$data);

        $url = $this
            ->controller
            ->Link("finish");

        return $this
            ->controller
            ->redirect($url);
    }

    public function registerUser($data) 
    {
        $url = $this
            ->getController()
            ->Link("finish");
        $session = $this->getSession();
        $session->set('BackURL',$url);

        $reg_con = Injector::inst()->create('Users_Register_Controller');
        $reg_con->doRegister($data,$this);
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
            $member->Company = ($member->Company) ? $member->Company : $data['Company'];
            $member->PhoneNumber = ($member->PhoneNumber) ? $member->PhoneNumber : $data['PhoneNumber'];
            $member->write();       
            
            $contact = $member->Contact();

            $address = ContactLocation::create();
            $assress->update($data);
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
            $member->Company = ($member->Company) ? $member->Company : $data['Company'];
            $member->PhoneNumber = ($member->PhoneNumber) ? $member->PhoneNumber : $data['PhoneNumber'];
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
            $address->State = $data['DeliveryState'];
            $address->Country = $data['DeliveryCountry'];
            $address->ContactID = $contact->ID;
            $address->write();
        }
    }
}
