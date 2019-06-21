<?php

namespace SilverStripe\DMS\Tests\Model;



use DMSDocumentController;






use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\DMS\DMS;
use SilverStripe\DMS\Tests\DMSFilesystemTestHelper;
use SilverStripe\DMS\Model\DMSDocument;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;


class DMSEmbargoTest extends SapphireTest
{
    protected static $fixture_file = 'dmsembargotest.yml';

    public function createFakeHTTPRequest($id)
    {
        $r = new HTTPRequest('GET', 'index/'.$id);
        $r->match('index/$ID');
        return $r;
    }

    public function testBasicEmbargo()
    {
        $oldTestMode = DMSDocumentController::$testMode;
        Config::inst()->update(DMS::class, 'folder_name', 'assets/_unit-test-123');

        $doc = DMS::inst()->storeDocument('dms/tests/DMS-test-lorum-file.pdf');
        $doc->CanViewType = 'LoggedInUsers';
        $docID = $doc->write();

        //fake a request for a document
        $controller = new DMSDocumentController();
        DMSDocumentController::$testMode = true;
        $result = $controller->index($this->createFakeHTTPRequest($docID));
        $this->assertEquals($doc->getFullPath(), $result, 'Correct underlying file returned (in test mode)');

        $doc->embargoIndefinitely();

        $this->logInWithPermission('ADMIN');
        $result = $controller->index($this->createFakeHTTPRequest($docID));
        $this->assertEquals($doc->getFullPath(), $result, 'Admins can still download embargoed files');

        $this->logInWithPermission('random-user-group');
        $result = $controller->index($this->createFakeHTTPRequest($docID));
        $this->assertNotEquals(
            $doc->getFullPath(),
            $result,
            'File no longer returned (in test mode) when switching to other user group'
        );

        DMSDocumentController::$testMode = $oldTestMode;
        DMSFilesystemTestHelper::delete('assets/_unit-test-123');
    }

    public function testEmbargoIndefinitely()
    {
        $doc = new DMSDocument();
        $doc->Filename = "DMS-test-lorum-file.pdf";
        $doc->Folder = "tests";
        $doc->write();

        $doc->embargoIndefinitely();
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $doc->clearEmbargo();
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");
    }

    public function testExpireAtDate()
    {
        $doc = new DMSDocument();
        $doc->Filename = "DMS-test-lorum-file.pdf";
        $doc->Folder = "tests";
        $doc->write();

        $doc->expireAtDate(strtotime('-1 second'));
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertTrue($doc->isExpired(), "Document is expired");

        $expireTime = "2019-04-05 11:43:13";
        $doc->expireAtDate($expireTime);
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        DBDatetime::set_mock_now($expireTime);
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertTrue($doc->isExpired(), "Document is expired");
        DBDatetime::clear_mock_now();

        $doc->expireAtDate(strtotime('-1 second'));
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertTrue($doc->isExpired(), "Document is expired");

        $doc->clearExpiry();
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");
    }

    public function testEmbargoUntilDate()
    {
        $doc = new DMSDocument();
        $doc->Filename = "DMS-test-lorum-file.pdf";
        $doc->Folder = "tests";
        $doc->write();

        $doc->embargoUntilDate(strtotime('+1 minute'));
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");

        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $doc->embargoUntilDate(strtotime('-1 second'));
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $embargoTime = "2019-04-05 11:43:13";
        $doc->embargoUntilDate($embargoTime);
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        DBDatetime::set_mock_now($embargoTime);
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        DBDatetime::clear_mock_now();

        $doc->clearEmbargo();
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");
    }

    public function testEmbargoUntilPublished()
    {
        $s1 = $this->objFromFixture(SiteTree::class, 's1');

        $doc = new DMSDocument();
        $doc->Filename = "test file";
        $doc->Folder = "0";
        $dID = $doc->write();

        $s1->DocumentSets()->first()->getDocuments()->add($doc);

        $s1->publish('Stage', 'Live');
        $s1->doPublish();
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $doc->embargoUntilPublished();
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $s1->publish('Stage', 'Live');
        $s1->doPublish();
        $doc = DataObject::get_by_id(DMSDocument::class, $dID);
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $doc->embargoUntilPublished();
        $doc = DataObject::get_by_id(DMSDocument::class, $dID);
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $doc->embargoIndefinitely();
        $doc = DataObject::get_by_id(DMSDocument::class, $dID);
        $this->assertTrue($doc->isHidden(), "Document is hidden");
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $s1->publish('Stage', 'Live');
        $s1->doPublish();
        $doc = DataObject::get_by_id(DMSDocument::class, $dID);
        $this->assertTrue(
            $doc->isHidden(),
            "Document is still hidden because although the untilPublish flag is cleared, the indefinitely flag is there"
        );
        $this->assertTrue($doc->isEmbargoed(), "Document is embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");

        $doc->clearEmbargo();
        $doc = DataObject::get_by_id(DMSDocument::class, $dID);
        $this->assertFalse($doc->isHidden(), "Document is not hidden");
        $this->assertFalse($doc->isEmbargoed(), "Document is not embargoed");
        $this->assertFalse($doc->isExpired(), "Document is not expired");
    }
}
