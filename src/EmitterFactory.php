<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole;

use Swoole\Http\Response;

final class EmitterFactory implements EmitterFactoryInterface
{
    public function __construct(
        private int $bufferSize = 8_388_608,
    ) {
    }

    public function create(Response $response): EmitterInterface
    {
        return new Emitter($response, $this->bufferSize);
    }
}
