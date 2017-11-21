# Micro (Yet another PHP library)
...but no shit

[![Build Status](https://travis-ci.org/gyselroth/micro-http.svg?branch=master)](https://travis-ci.org/gyselroth/micro-http)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gyselroth/micro-http/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gyselroth/micro-http/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/gyselroth/micro-http.svg)](https://packagist.org/packages/gyselroth/micro-http)
[![GitHub release](https://img.shields.io/github/release/gyselroth/micro-http.svg)](https://github.com/gyselroth/micro-http/releases)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/gyselroth/micro-http/master/LICENSE)

## Description

## Requirements
The library is only >= PHP7.1 compatible.

## Download
The package is available at packagist: https://packagist.org/packages/gyselroth/micro-http

To install the package via composer execute:
```
composer require gyselroth/micro-http
```

## HTTP (\Micro\Http)

#### Initialize router
The http router requires an array with http headers, usually this is $_SERVER and a PSR-3 compatible logger.

```php
$router = new \Micro\Http\Router(array $server, \Psr\Log\LoggerInterface $logger)
```

#### Adding routes

```php
$router = (new \Micro\Http\Router($_SERVER, $logger))
  ->clearRoutingTable()
  ->addRoute(new \Micro\Http\Router\Route('/api/v1/user', 'MyApp\Rest\v1\User'))
  ->addRoute(new \Micro\Http\Router\Route('/api/v1/user/{uid:#([0-9a-z]{24})#}', 'MyApp\Rest\v1\User'))
  ->addRoute(new \Micro\Http\Router\Route('/api/v1$', 'MyApp\Rest\v1\Rest'))
  ->addRoute(new \Micro\Http\Router\Route('/api/v1', 'MyApp\Rest\v1\Rest'))
  ->addRoute(new \Micro\Http\Router\Route('/api$', 'MyApp\Rest\v1\Rest'));
  ->run(array $controller_params);
```

The router tries to map a request to the first matching route in his routing table. The request gets mappend to a class and method. Optional parameters/query string gets automatically submitted to the final controller class.

Given the routing table above and the following final controller class:

```php
namespace MyApp\Rest\v1;

class User
{
    /**
     * GET http://localhost/api/v1/user/540f1fc9a641e6eb708b4618/attributes
     * GET http://localhost/api/v1/user/attributes?uid=540f1fc9a641e6eb708b4618
     */
    public function getAttributes(string $uid=null): \Micro\Http\Response
    {

    }

    /**
     * GET http://localhost/api/v1/user/540f1fc9a641e6eb708b4618
     * GET http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
     */
    public function get(string $uid=null): \Micro\Http\Response
    {

    }

    /**
     * POST http://localhost/api/v1/user/540f1fc9a641e6eb708b4618/password / POST body password=1234
     * POST http://localhost/api/v1/user/password?uid=540f1fc9a641e6eb708b4618 / POST body password=1234
     * POST http://localhost/api/v1/user/password / POST body password=1234, uid=540f1fc9a641e6eb708b4618
     */
    public function postPassword(string $uid, string $password): \Micro\Http\Response
    {

    }

    /**
     * DELETE http://localhost/api/v1/user/540f1fc9a641e6eb708b4618/mail
     * DELETE http://localhost/api/v1/user/mail?uid=540f1fc9a641e6eb708b4618
     */
    public function deleteMail(string $uid=null): \Micro\Http\Response
    {

    }

    /**
     * DELETE http://localhost/api/v1/540f1fc9a641e6eb708b4618/mail
     * DELETE http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
     */
    public function delete(string $uid=null): \Micro\Http\Response
    {

    }

    /**
     * HEAD http://localhost/api/v1/user/540f1fc9a641e6eb708b4618
     * HEAD http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
     */
    public function headExists(string $uid=null): \Micro\Http\Response
    {

    }
}
```

#### Response
Each endpoint needs to return a Response object to the router.

```php
/**
 * HEAD http://localhost/api/v1/user/540f1fc9a641e6eb708b4618
 * HEAD http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
 */
public function headExists(string $uid=null): \Micro\Http\Response
{
  if(true) {
    return (new \Micro\Http\Response())->setCode(200)->setBody('user does exists');
  } else {
    return (new \Micro\Http\Response())->setCode(404);  
  }
}
```
