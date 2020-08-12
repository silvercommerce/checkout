<?php

namespace SilverCommerce\Checkout\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverCommerce\OrdersAdmin\Forms\GridField\ReadOnlyGridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;

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
                null,
                $this->owner->Payments(),
                $config = GridFieldConfig_RecordEditor::create()
            )
        );

        $config
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->removeComponentsByType(GridFieldDeleteAction::class);
    }
}
