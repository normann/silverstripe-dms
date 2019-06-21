<?php

/**
 * Class DMSDocumentControllerTest
 */
class DMSDocumentControllerTest extends SapphireTest
{
    protected static $fixture_file = 'dmstest.yml';

    /**
     * @var DMSDocumentController
     */
    protected $controller;

    public function setUp()
    {
        parent::setUp();

        Config::inst()->update('DMS', 'folder_name', 'assets/_unit-test-123');
        $this->logInWithPermission('ADMIN');

        $this->controller = $this->getMockBuilder('DMSDocumentController')
            ->setMethods(array('sendFile'))
            ->getMock();
    }

    public function tearDown()
    {
        DMSFilesystemTestHelper::delete('assets/_unit-test-123');
        parent::tearDown();
    }

    /**
     * Test that the download behaviour is either "open" or "download"
     *
     * @param string $behaviour
     * @param string $expectedDisposition
     * @dataProvider behaviourProvider
     */
    public function testDownloadBehaviourOpen($behaviour, $expectedDisposition)
    {
        $self = $this;
        $this->controller->expects($this->once())
            ->method('sendFile')
            ->will(
                $this->returnCallback(function ($path, $mime, $name, $disposition) use ($self, $expectedDisposition) {
                    $self->assertEquals($expectedDisposition, $disposition);
                })
            );

        $openDoc = DMS::inst()->storeDocument('dms/tests/DMS-test-lorum-file.pdf');
        $openDoc->DownloadBehavior = $behaviour;
        $openDoc->clearEmbargo(false);
        $openDoc->write();

        $request = new SS_HTTPRequest('GET', $openDoc->Link());
        $request->match('dmsdocument/$ID');
        $this->controller->index($request);
    }

    /**
     * @return array[]
     */
    public function behaviourProvider()
    {
        return array(
            array('open', 'inline'),
            array('download', 'attachment')
        );
    }
}
