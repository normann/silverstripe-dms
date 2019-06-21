<?php

namespace SilverStripe\DMS\Tests\Extensions;




use SilverStripe\DMS\Extensions\DMSTaxonomyTypeExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Taxonomy\TaxonomyType;
use SilverStripe\Dev\SapphireTest;



class DMSTaxonomyTypeExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $requiredExtensions = array(
        'TaxonomyType' => array(DMSTaxonomyTypeExtension::class)
    );

    /**
     * Ensure that the configurable list of default records are created
     */
    public function testDefaultRecordsAreCreated()
    {
        Config::inst()->update(DMSTaxonomyTypeExtension::class, 'default_records', array('Food', 'Beverage', 'Books'));

        TaxonomyType::create()->requireDefaultRecords();

        $this->assertDOSContains(
            array(
                array('Name' => 'Food'),
                array('Name' => 'Beverage'),
                array('Name' => 'Books'),
            ),
            TaxonomyType::get()
        );
    }
}
