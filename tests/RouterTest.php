<?php
namespace Micro\Http\Testsuite;

use \PHPUnit\Framework\TestCase;
use \Micro\Http\Router;
use \Micro\Http\Router\Route;
use \Micro\Http\Testsuite\MockLogger;

class RouterTest extends TestCase
{
    public function testInit()
    {
        $server = [
            'PATH_INFO' => 'index.php/api/my/path',
            'REQUEST_METHOD' => 'PUT',
        ];

        $router = new Router(new MockLogger(), $server);
        $this->assertEquals($router->getPath(), 'index.php/api/my/path');
        $this->assertEquals($router->getVerb(), 'put');
        return $router;
    }

    /**
     * @depends testInit
     */
    public function testVerb($router)
    {
        $router->setVerb('GET');
        $this->assertEquals($router->getVerb(), 'get');
    }


    /**
     * @depends testInit
     */
    public function testPath($router)
    {
        $router->setPath('index.php/api');
        $this->assertEquals($router->getPath(), 'index.php/api');
    }


    /**
     * @depends testInit
     */
    public function testAppendRoute($router)
    {
        $this->assertCount(0, $router->getRoutes());
        $router->appendRoute(new Route('/', 'Controller'));
        $this->assertCount(1, $router->getRoutes());
    }


    /**
     * @depends testInit
     */
    public function testClearRoutes($router)
    {
        $router->clearRoutingTable();
        $this->assertCount(0, $router->getRoutes());
    }
}
