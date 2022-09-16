<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole;

use Psr\Http\Message\ResponseInterface;

interface EmitterInterface
{
    /**
     * Emits a PSR-7 response.
     *
     * @param ResponseInterface $response
     * @param bool $withoutBody
     */
    public function emit(ResponseInterface $response, bool $withoutBody = false);
}
