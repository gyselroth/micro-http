<?php

declare(strict_types=1);

/**
 * Micro
 *
 * @author      Raffael Sahli <sahli@gyselroth.net>
 * @copyright   Copryright (c) 2015-2017 gyselroth GmbH (https://gyselroth.com)
 * @license     MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Http;

use Micro\Http\Router\Route;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionMethod;

class Router
{
    /**
     * Requested route.
     *
     * @var string
     */
    protected $path;

    /**
     * HTTP verb.
     *
     * @var string
     */
    protected $verb;

    /**
     * Installed routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * DI container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Init router.
     *
     * @param LoggerInterface    $logger
     * @param ContainerInterface $container
     * @param array              $request
     */
    public function __construct(LoggerInterface $logger, ?array $request = null, ?ContainerInterface $container = null)
    {
        $this->logger = $logger;
        $this->container = $container;

        if (null === $request) {
            $request = $_SERVER;
        }

        if (isset($request['PATH_INFO'])) {
            $this->setPath($request['PATH_INFO']);
        }

        if (isset($request['REQUEST_METHOD'])) {
            $this->setVerb($request['REQUEST_METHOD']);
        }
    }

    /**
     * Add route to the beginning of the routing table.
     *
     * @param Route $route
     *
     * @return Router
     */
    public function prependRoute(Route $route): self
    {
        array_unshift($this->routes, $route);
        $route->setRouter($this);

        return $this;
    }

    /**
     * Add route to the end of the routing table.
     *
     * @param Route $route
     *
     * @return Router
     */
    public function appendRoute(Route $route): self
    {
        $this->routes[] = $route;
        $route->setRouter($this);

        return $this;
    }

    /**
     * Clear routing table.
     *
     * @return Router
     */
    public function clearRoutingTable(): self
    {
        $this->routes = [];

        return $this;
    }

    /**
     * Get active routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Set HTTP verb.
     *
     * @param string $verb
     *
     * @return Router
     */
    public function setVerb(string $verb): self
    {
        $this->verb = strtolower($verb);

        return $this;
    }

    /**
     * Get http verb.
     *
     * @return string
     */
    public function getVerb(): string
    {
        return $this->verb;
    }

    /**
     * Set routing path.
     *
     * @param string $path
     *
     * @return Router
     */
    public function setPath(string $path): self
    {
        $path = rtrim(trim($path), '/');
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Execute router.
     *
     * @return bool
     */
    public function run(): bool
    {
        $this->logger->info('execute requested route ['.$this->path.']', [
            'category' => get_class($this),
        ]);

        try {
            $response = null;
            $match = false;
            foreach ($this->routes as $key => $route) {
                if ($route->match()) {
                    $callable = $route->getCallable($this->container);

                    if (is_callable($callable)) {
                        $match = true;
                        $this->logger->info('found matching route, execute ['.$route->getClass().'::'.$callable[1].']', [
                            'category' => get_class($this),
                        ]);

                        $params = $this->getParams($route->getClass(), $callable[1], $route->getParams());
                        $response = call_user_func_array($callable, $params);

                        if (!$route->continueAfterMatch()) {
                            break;
                        }
                    } else {
                        $this->logger->debug('found matching route ['.$route->getClass().'::'.$callable[1].'], but callable was not found', [
                            'category' => get_class($this),
                        ]);
                    }
                } else {
                    $this->logger->debug('requested path ['.$this->path.'] does not match route ['.$route->getPath().']', [
                        'category' => get_class($this),
                    ]);
                }
            }

            if (false === $match) {
                throw new Exception($this->verb.' '.$this->path.' could not be routed, no matching routes found');
            }
            if ($response instanceof Response) {
                $this->logger->info('send http response ['.$response->getCode().']', [
                        'category' => get_class($this),
                    ]);

                $response->send();
            } else {
                $this->logger->debug('callback did not return a response, route exectuted successfully', [
                        'category' => get_class($this),
                    ]);
            }

            return true;
        } catch (\Exception $e) {
            return $this->sendException($e);
        }
    }

    /**
     * Sends a exception response to the client.
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    public function sendException(\Exception $exception): bool
    {
        $message = $exception->getMessage();
        $class = get_class($exception);

        $msg = [
            'error' => $class,
            'message' => $message,
            'code' => $exception->getCode(),
        ];

        if (defined("$class::HTTP_CODE")) {
            $http_code = $class::HTTP_CODE;
        } else {
            $http_code = 500;
        }

        $this->logger->error('uncaught exception '.$message.']', [
            'category' => get_class($this),
            'exception' => $exception,
        ]);

        (new Response())
            ->setCode($http_code)
            ->setBody($msg)
            ->send();

        return true;
    }

    /**
     * Build method name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function _buildMethodName(string $name): string
    {
        $result = $this->verb;
        $split = explode('-', $name);
        foreach ($split as $part) {
            $result .= ucfirst($part);
        }

        return $result;
    }

    /**
     * Check if method got params and combine these with
     * $_REQUEST.
     *
     * @param string $class
     * @param string $method
     * @param array  $parsed_params
     *
     * @return callable
     */
    protected function getParams(string $class, string $method, array $parsed_params): array
    {
        try {
            $return = [];
            $meta = new ReflectionMethod($class, $method);
            $params = $meta->getParameters();
            $json_params = [];

            if (isset($_SERVER['CONTENT_TYPE']) && 'application/json' === $_SERVER['CONTENT_TYPE']) {
                $body = file_get_contents('php://input');
                if (!empty($body)) {
                    $json_params = json_decode($body, true);
                } else {
                    $parts = explode('&', $_SERVER['QUERY_STRING']);
                    if (!empty($parts)) {
                        $json_params = json_decode(urldecode($parts[0]), true);
                    }
                }
                if (null === $json_params) {
                    throw new Exception('invalid json input given');
                }

                $request_params = array_merge($json_params, $parsed_params);
            } else {
                $request_params = array_merge($parsed_params, $_REQUEST);
            }

            foreach ($params as $param) {
                if ($optional = $param->isOptional()) {
                    $default = $param->getDefaultValue();
                } else {
                    $default = null;
                }

                if (isset($request_params[$param->name]) && '' !== $request_params[$param->name]) {
                    if (is_bool($default)) {
                        if ('false' === $request_params[$param->name]) {
                            $return[$param->name] = false;
                        } else {
                            $return[$param->name] = (bool) $request_params[$param->name];
                        }
                    } elseif (is_int($default)) {
                        $return[$param->name] = (int) $request_params[$param->name];
                    } elseif (is_array($default)) {
                        $return[$param->name] = (array) $request_params[$param->name];
                    } else {
                        $return[$param->name] = $request_params[$param->name];
                    }
                } elseif (isset($json_params[$param->name])) {
                    $return[$param->name] = $json_params[$param->name];
                } else {
                    $return[$param->name] = $default;
                }

                if (null === $return[$param->name] && false === $optional) {
                    throw new Exception('misssing required parameter '.$param->name);
                }
            }

            return $return;
        } catch (ReflectionException $e) {
            throw new Exception('misssing or invalid required request parameter');
        }
    }
}
