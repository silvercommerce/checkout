<?php

namespace SilverCommerce\Checkout\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverCommerce\OrdersAdmin\Forms\GridField\ReadOnlyGridField;

class InvoiceExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        // Use read only gridfield provided by orders module (to show payments if
        // orders are completed)
        $fields->addFieldToTab(
            'Root.Payments',
            ReadOnlyGridField::create(
                "Payments",
                "",
                $this->owner->Payments(),
                $config = GridFieldConfig_RecordViewer::create()
            )
        );
    }
}