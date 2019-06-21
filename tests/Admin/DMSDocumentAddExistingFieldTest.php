<?php

namespace SilverStripe\DMS\Tests\Admin;




use SilverStripe\DMS\Admin\DMSDocumentAddExistingField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\DMS\Model\DMSDocumentSet;
use SilverStripe\Dev\SapphireTest;



class DMSDocumentAddExistingFieldTest extends SapphireTest
{
    /**
     * The constructor should create a tree dropdown field
     */
    public function testFieldContainsTreeDropdownField()
    {
        $field = new DMSDocumentAddExistingField('Test', 'Test');
        $this->assertContainsOnlyInstancesOf(TreeDropdownField::class, $field->getChildren());
        $this->assertSame('PageSelector', $field->getChildren()->first()->getName());
    }

    public function testSetAndGetRecord()
    {
        $record = new DMSDocumentSet;
        $field = new DMSDocumentAddExistingField('Test');
        $field->setRecord($record);
        $this->assertSame($record, $field->getRecord());
    }
}
