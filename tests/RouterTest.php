<?php

declare(strict_types=1);

/**
 * Micro
 *
 * @author      Raffael Sahli <sahli@gyselroth.net>
 * @copyright   Copryright (c) 2015-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Http\Testsuite;

use Micro\Http\Router;
use Micro\Http\Router\Route;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversNothing
 */
class RouterTest extends TestCase
{
    public function testInit()
    {
        $server = [
            'PATH_INFO' => 'index.php/api/my/path',
            'REQUEST_METHOD' => 'PUT',
        ];

        $router = new Router($this->createMock(LoggerInterface::class), $server);
        $this->assertSame($router->getPath(), 'index.php/api/my/path');
        $this->assertSame($router->getVerb(), 'put');

        return $router;
    }

    /**
     * @depends testInit
     *
     * @param mixed $router
     */
    public function testVerb($router)
    {
        $router->setVerb('GET');
        $this->assertSame($router->getVerb(), 'get');
    }

    /**
     * @depends testInit
     *
     * @param mixed $router
     */
    public function testPath($router)
    {
        $router->setPath('index.php/api');
        $this->assertSame($router->getPath(), 'index.php/api');
    }

    /**
     * @depends testInit
     *
     * @param mixed $router
     */
    public function testAppendRoute($router)
    {
        $this->assertCount(0, $router->getRoutes());
        $router->appendRoute(new Route('/', 'Controller'));
        $this->assertCount(1, $router->getRoutes());
    }

    /**
     * @depends testInit
     *
     * @param mixed $router
     */
    public function testClearRoutes($router)
    {
        $router->clearRoutingTable();
        $this->assertCount(0, $router->getRoutes());
    }
}
