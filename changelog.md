# Log of changes

This file logs changes to the SilverCommerce Checkout module

## 1.0.0

Initial stable release

## 1.0.1

* Add additional extension hooks

## 1.0.2

* Only merge shopping carts on login
* Ensure only shopping carts are merged on login

## 1.1.0

* Improvements to estimate save process
* Change some private methods on CustomeDetailsForm to protected (and rename).
* Break CustomerDetailsForm::doContinue out into sub methods.

## 1.1.1

* Added fix when saving CustomerDetailForm into estimate

## 1.1.2

* Simplify CustomDetailForm template (and allow additional fields to be added more easily)

## 1.1.3

* Correctly link show price and tax checkbox and order summary

## 1.1.4

* Added Tax switching to order summary unit price
* Add additional extension hook to payment submission process

## 1.1.5

* After delivery address is selected, re-calculate tax

## 1.2.0

* Switch to a simple stepped checkout layout (allowing custom request handlers to be added to the checkout in any order)
* Use a custom version of OmniPay Manual gateway to handle zero value transactions.
* Ensure customer details form loads default values correctly 