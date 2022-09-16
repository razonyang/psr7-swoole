<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole;

use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

interface ServerRequestFactoryInterface
{
    /**
     * Create a PSR-7 server request instance with the given Swoole HTTP request.
     *
     * @return ServerRequestInterface
     */
    public function create(Request $request): ServerRequestInterface;
}
