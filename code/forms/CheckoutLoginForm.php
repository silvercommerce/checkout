<?php

namespace SilverCommerce\Checkout\Forms;

use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Control\Director;

/**
 * Log-in form for the "member" authentication method that extends the
 * default login method
 *
 * @package checkout
 */
class CheckoutLoginForm extends MemberLoginForm
{

    /**
     * Login form handler method
     *
     * This method is called when the user clicks on "Log in"
     *
     * @param array $data Submitted data
     */
    public function dologin($data)
    {
        if ($this->performLogin($data)) {
            $this->logInUserAndRedirect($data);
        } else {
            $session = $this
                ->getController()
                ->getRequest()
                ->getSession();

            if (array_key_exists('Email', $data)) {
                $session->set('SessionForms.MemberLoginForm.Email', $data['Email']);
                $session->set('SessionForms.MemberLoginForm.Remember', isset($data['Remember']));
            }

            if (isset($_REQUEST['BackURL'])) {
                $backURL = $_REQUEST['BackURL'];
            } else {
                $backURL = null;
            }

            if ($backURL) {
                $session->set('BackURL', $backURL);
            }

            // Show the right tab on failed login
            $loginLink = Director::absoluteURL($this->getController()->Link());

            if ($backURL) {
                $loginLink .= '?BackURL=' . urlencode($backURL);
            }

            return $this
                ->getController()
                ->redirect($loginLink . '#' . $this->FormName() .'_tab');
        }
    }
}
