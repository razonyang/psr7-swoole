# Swoole + PSR7

[![Latest Stable Version](https://poser.pugx.org/razonyang/psr7-swoole/v/stable.png)](https://packagist.org/packages/razonyang/psr7-swoole)
[![Total Downloads](https://poser.pugx.org/razonyang/psr7-swoole/downloads.png)](https://packagist.org/packages/razonyang/psr7-swoole)
[![Build Status](https://github.com/razonyang/psr7-swoole/workflows/build/badge.svg)](https://github.com/razonyang/psr7-swoole/actions)
[![Coverage Status](https://coveralls.io/repos/github/razonyang/psr7-swoole/badge.svg?branch=main)](https://coveralls.io/github/razonyang/psr7-swoole?branch=main)

The PSR7 helpers for Swoole.

- Convert `Swoole\Http\Request` to `Psr\Http\Message\ServerRequestInterface`.
- Emit `Psr\Http\Message\ResponseInterface`.

## Installation

```bash
composer require razonyang/psr7-swoole --prefer-dist
```

## Usage

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use RazonYang\Psr7\Swoole\EmitterFactory;
use RazonYang\Psr7\Swoole\ServerRequestFactory;
use Swoole\Coroutine\Http\Server;

use function Swoole\Coroutine\run;

run(function () {
    $serverRequestFactory = new ServerRequestFactory();
    $emitterFactory = new EmitterFactory();
    $psr7Factory =new Psr17Factory();

    $server = new Server('127.0.0.1', 9501, false);
    
    $server->handle('/', function ($request, $response) use ($emitterFactory, $serverRequestFactory, $psr7Factory) {
        $emitter = $emitterFactory->create($response);
        $psrRequest = $serverRequestFactory->create($request);
        $psrResponse = $psr7Factory
            ->createResponse(200)
            ->withBody(Stream::create($psrRequest->getUri()->getPath()));
        $emitter->emit($psrResponse);
    });

    $server->start();
});
```
