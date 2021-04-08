<?php

namespace SilverCommerce\Checkout\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\ORM\DB;

/**
 * Generate a default customers group
 *
 * @author     Mo <morven@ilateral.co.uk>
 * @package    checkout
 * @subpackage extensions
 */
class GroupExtension extends DataExtension
{
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // add an ecommerce customers group (if needed)
        $customers_group = Group::get()->filter("Code", "ecommerce-customers");
        if (!$customers_group->exists()) {
            $customers_group = Group::create();
            $customers_group->Code = 'ecommerce-customers';
            $customers_group->Title = "Ecommerce Customers";
            $customers_group->Sort = 5;
            $customers_group->write();

            DB::alteration_message(
                'Ecommerce customers group created',
                'created'
            );
        }
    }
}
