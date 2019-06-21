<?php

namespace SilverStripe\DMS\Tests\ShortCode;





use SilverStripe\Core\Config\Config;
use SilverStripe\DMS\DMS;
use SilverStripe\DMS\Model\DMSDocument;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\DMS\Tools\ShortCodeRelationFinder;
use SilverStripe\Dev\SapphireTest;


class ShortCodeRelationFinderTest extends SapphireTest
{
    use ShortCodeRelationFinder;
    
    protected static $fixture_file = '../dmstest.yml';

    public function testFindInRate()
    {
        Config::inst()->update(DMS::class, 'shortcode_handler_key', 'dms_document_link');

        $d1 = $this->objFromFixture(DMSDocument::class, 'd1');
        $d2 = $this->objFromFixture(DMSDocument::class, 'd2');

        $page1 = new SiteTree();
        $page1->Content = 'Condition:  <a title="document test 1" href="[dms_document_link,id=' . $d1->ID . ']">';
        $page1ID = $page1->write();

        $page2 = new SiteTree();
        $page2->Content = 'Condition:  <a title="document test 2" href="[dms_document_link,id=' . $d2->ID . ']">';
        $page2ID = $page2->write();

        $page3 = new SiteTree();
        $page3->Content = 'Condition:  <a title="document test 1" href="[dms_document_link,id=' . $d1->ID . ']">';
        $page3ID = $page3->write();

        $ids = $this->findPageIDs('UnknownShortcode');
        $this->assertEquals(0, count($ids));

        $ids = $this->findPageIDs($d1->ID);
        $this->assertNotContains($page2ID, $ids);
        $this->assertContains($page1ID, $ids);
        $this->assertContains($page3ID, $ids);
    }
}
