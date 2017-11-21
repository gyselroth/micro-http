<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Http\Router;

use \Micro\Exception;
use \Micro\Http\Router;
use \Psr\Container\ContainerInterface;

class Route
{
    /**
     * Router
     *
     * @var Router
     */
    protected $router;


    /**
     * Continue propagation if match
     *
     * @var bool
     */
    protected $continue_propagation = false;


    /**
     * Route path
     *
     * @var string
     */
    protected $path;


    /**
     * Class to be instanced if route match was found
     *
     * @var string|object
     */
    protected $class;


    /**
     * Method to be executed if route match was found
     *
     * @var string
     */
    protected $method;


    /**
     * Found request parameters
     *
     * @var array
     */
    protected $params = [];


    /**
     * Instance route
     *
     * @param   string $path
     * @param   string $class
     * @param   string $method
     * @param   array $params
     * @return  void
     */
    public function __construct(string $path, $class, ? string $method = null, array $params = [])
    {
        $this->setPath($path);
        $this->setClass($class);
        $this->setMethod($method);
        $this->setParams($params);
    }


    /**
     * Check if route does match the current http request
     *
     * @return bool
     */
    public function match(): bool
    {
        $regex = preg_replace_callback('#({([A-Z0-9a-z]+)\:\#(.+?)\#})|\{(.+?)\}#', function($match) {
            if (count($match) === 4) {
                return '(?<'.$match[2].'>'.$match[3].'+)';
            } else {
                return '(?<'.end($match).'>\w+)';
            }
        }, $this->path);

        if (preg_match('#^'.$regex.'#', $this->router->getPath(), $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $this->params[$key] = $value;
                }
            }

            return true;
        }

        return false;
    }


    /**
     * Get callable
     *
     * @param  ContainerInterface $container
     * @return array
     */
    public function getCallable(?ContainerInterface $container=null): array
    {
        if (is_object($this->class)) {
            $instance = $this->class;
        } else {
            if($container === null) {
                $instance = new $this->class();
            } else {
                $instance = $container->get($this->class);
            }
        }

        if ($this->method !== null) {
            return [&$instance, $this->method];
        }

        return [&$instance, $this->getMethod()];
    }


    /**
     * Build method name
     *
     * @param   string $name
     * @return  string
     */
    protected function buildMethodName(? string $name) : string
    {
        $result = $this->router->getVerb();

        if ($name !== null) {
            $split = explode('-', $name);
            foreach ($split as $part) {
                $result .= ucfirst($part);
            }
        }

        return $result;
    }


    /**
     * Get http router
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }


    /**
     * Set http router
     *
     * @param  Router $router
     * @return Route
     */
    public function setRouter(Router $router): Route
    {
        $this->router = $router;
        return $this;
    }


    /**
     * Get path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * Set path
     *
     * @param  string $path
     * @return Route
     */
    public function setPath(string $path): Route
    {
        $this->path = (string)$path;
        return $this;
    }


    /**
     * Get class
     *
     * @return string
     */
    public function getClass(): string
    {
        if (is_object($this->class)) {
            return get_class($this->class);
        }

        return $this->class;
    }


    /**
     * Set clas
     *
     * @param   string|object $class
     * @return  Route
     */
    public function setClass($class): Route
    {
        $this->class = $class;
        return $this;
    }


    /**
     * Convert camelCase to dashes
     *
     * @param  string $value
     * @return string
     */
    protected function camelCase2Dashes(string $value): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $value));
    }


    /**
     * Get method
     *
     * @return string
     */
    public function getMethod(): string
    {
        if (substr($this->path, -1) === '$') {
            return $this->router->getVerb();
        }

        $path = $this->router->getPath();
        $action = substr($path, strrpos($path, '/') + 1);

        if (!in_array($action, $this->params)) {
            $this->method = $action;
        }

        $short = $this->camelCase2Dashes(substr($this->class, strrpos($this->class, '\\') + 1));

        if ($this->camelCase2Dashes($short) === $action) {
            $this->method = '';
        }

        return $this->buildMethodName($this->method);
    }


    /**
     * Set method
     *
     * @param   string $method
     * @return  Route
     */
    public function setMethod(? string $method) : Route
    {
        $this->method = $method;
        return $this;
    }


    /**
     * Set params
     *
     * @param   array $params
     * @return  Route
     */
    public function setParams(array $params): Route
    {
        $this->params = $params;
        return $this;
    }


    /**
     * Get translated regex variable values
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }


    /**
     * Continue propagation after match
     *
     * @return bool
     */
    public function continueAfterMatch(): bool
    {
        return $this->continue_propagation;
    }


    /**
     * Halt propagation after match
     *
     * @param  bool $next
     * @return Route
     */
    public function continuePropagation($next = true): Route
    {
        $this->continue_propagation = (bool)$next;
        return $this;
    }
}
