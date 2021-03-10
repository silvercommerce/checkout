<?php

namespace SilverCommerce\Checkout\Forms;

use SilverStripe\Forms\RequiredFields;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Custom validation for our checkout details
 */
class CheckoutValidator extends RequiredFields
{

    /**
     * List of fields that represent required billing address fields
     *
     * @var array
     */
    private static $billing_fields = [
        'FirstName',
        'Surname',
        'Address1',
        'City',
        'Country',
        'County',
        'PostCode',
        'Email',
        'PhoneNumber'
    ];

    /**
     * Default field name for password field
     *
     * @var string
     */
    private static $password_field = "Password";

    /**
     * List of fields that represent the delivery address (fields that will be
     * removed it delivery is disabled or the same as shipping)
     *
     * @var array
     */
    private static $delivery_fields = [
        'DeliveryFirstName',
        'DeliverySurname',
        'DeliveryAddress1',
        'DeliveryCity',
        'DeliveryCountry',
        'DeliveryCounty',
        'DeliveryPostCode'
    ];

    /**
     * Dropdown fields used for delivery (that might need to be disabled)
     *
     * @var array
     */
    private static $delivery_dropdown_fields = [
        'DeliveryCountry',
        'DeliveryCounty'
    ];

    /**
     * Is the current order deliverable (requires delivery fields)
     *
     * @var boolean
     */
    protected $deliverable;

        /**
         * Get is the current order deliverable (requires delivery fields)
         *
         * @return boolean
         */
    public function getDeliverable()
    {
        return $this->deliverable;
    }

    /**
     * Set is the current order deliverable (requires delivery fields)
     *
     * @param  bool $deliverable Is the current order deliverable (requires delivery fields)
     * @return self
     */
    public function setDeliverable($deliverable)
    {
        $this->deliverable = $deliverable;

        return $this;
    }

    /**
     * Is the form currently using the location dropdown?
     *
     * @var boolean
     */
    protected $location_dropdown;

    /**
     * Get is the form currently using the location dropdown?
     *
     * @return boolean
     */
    public function getLocationDropdown()
    {
        return $this->location_dropdown;
    }

    /**
     * Set is the form currently using the location dropdown?
     *
     * @param  boolean $location_dropdown Is the form currently using the location dropdown?
     * @return self
     */
    public function setLocationDropdown($location_dropdown)
    {
        $this->location_dropdown = $location_dropdown;

        return $this;
    }

    public function __construct($deliverable = true, $location_dropdown = false)
    {
        $config = SiteConfig::current_site_config();

        $this->deliverable = $deliverable;
        $this->location_dropdown = $location_dropdown;
        $required = [];

        // If we require uses, ensure password is supplied
        if ($config->CheckoutAllowGuest == false) {
            $required[] = $this->config()->password_field;
        }

        // If we aren't using the location dropdown, add required fields
        if (!$this->location_dropdown) {
            foreach ($this->config()->billing_fields as $field) {
                $required[] = $field;
            }
        }

        // If this is deliverable, add required delivery fields
        if ($this->deliverable) {
            foreach ($this->config()->delivery_fields as $field) {
                $required[] = $field;
            }
        }

        parent::__construct($required);
    }

    public function php($data)
    {
        // Find the button that was clicked
        $handler = $this->form->getRequestHandler();
        $action = $handler ? $handler->buttonClicked() : null;
    
        if ($action && $action->actionName() != 'doContinue') {
            $this->removeValidation();
        }

        if (isset($data['DuplicateDelivery']) && $data['DuplicateDelivery'] == 1) {
            foreach ($this->config()->delivery_fields as $field) {
                $this->removeRequiredField($field);

                // account for empty default on dropdowns set by required
                if (in_array($field, $this->config()->delivery_dropdown_fields)) {
                    $form_field = $this->form->Fields()->dataFieldByName($field);

                    if (isset($form_field)) {
                        $form_field->setHasEmptyDefault(true);
                    }
                }
            }
        }

        return parent::php($data);
    }
}
