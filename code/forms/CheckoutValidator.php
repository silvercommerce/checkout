<?php

namespace SilverCommerce\Checkout\Forms;

use SilverStripe\Forms\RequiredFields;

/**
 * Custom validation for our checkout details
 * 
 */
class CheckoutValidator extends RequiredFields
{
    public function php($data)
    {   
        // Find the button that was clicked
        $handler = $this->form->getRequestHandler();
        $action = $handler ? $handler->buttonClicked() : null;
        
        if ($action && $action->actionName() != 'doContinue') {
            $this->removeValidation();
        }

        if (isset($data['DuplicateDelivery']) && $data['DuplicateDelivery'] == 1) {
            $this->removeRequiredField('DeliveryFirstName');
            $this->removeRequiredField('DeliverySurname');
            $this->removeRequiredField('DeliveryAddress1');
            $this->removeRequiredField('DeliveryCity');
            $this->removeRequiredField('DeliveryPostCode');
            $this->removeRequiredField('DeliveryCountry');
        }

        return parent::php($data);
    }
}