<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Swoole\Http\Response;

final class Emitter implements EmitterInterface
{
    public function __construct(
        private Response $response,
        private int $bufferSize = 8_388_608, // 8MB
    ) {
    }

    public function withBufferSize(int $size)
    {
        $new = clone $this;
        $new->bufferSize = $size;

        return $new;
    }

    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    public function emit(ResponseInterface $response, bool $withoutBody = false)
    {
        $this->response->status($response->getStatusCode(), $response->getReasonPhrase());

        foreach ($response->getHeaders() as $key => $value) {
            $this->response->header($key, $value);
        }

        if (!$withoutBody) {
            $this->emitBody($response->getBody());
        }

        $this->response->end();
    }

    private function emitBody(StreamInterface $body): void
    {
        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            $this->response->write($body->read($this->bufferSize));
        }
    }
}
