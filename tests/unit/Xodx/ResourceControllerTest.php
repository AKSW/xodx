<?php

class Xodx_ResourceControllerTest extends PHPUnit2_Framework_TestCase
{
    protected $resourceController;

    protected function setUp ()
    {
        $app = new Xodx_ApplicationStub();
        $this->resourceController = new Xodx_ResourceController($app);
    }

    public function testImportResource ()
    {
        $resourceUri = 'http://dbpedia.org/resource/Hamburger_SV';
        $this->resourceController->importResource($resourceUri);

        $this->markTestIncomplete('This test needs a mocked LinkedData class');
    }
}
