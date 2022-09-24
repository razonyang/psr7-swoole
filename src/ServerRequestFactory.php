<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole;

use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Http\Request;

final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function create(Request $request): ServerRequestInterface
    {
        $server = array_change_key_case($request->server, CASE_UPPER);
        $server['SCRIPT_NAME'] = $this->getScriptName();
        $uri = $this->createUri($server);

        $headers = $request->header??[];

        $cookies = $request->cookie ?? [];
        if ($cookies) {
            $tmp = [];
            foreach ($cookies as $name => $value) {
                $tmp[] = sprintf('%s=%s', $name, $value);
            }
            $headers['cookie'] = implode('; ', $tmp);
        }

        $psrRequest = new ServerRequest(
            $request->getMethod(),
            $uri,
            $headers,
            $request->getContent(),
            $this->parseProtocolVersion($server),
            $server,
        );

        \parse_str($uri->getQuery(), $queryParams);

        return $psrRequest
            ->withQueryParams($queryParams)
            ->withCookieParams($cookies)
            ->withUploadedFiles($this->parseUploadedFiles($request->files??[]));
    }

    private function getScriptName(): string
    {
        global $argv;

        return $argv[0]??'';
    }

    private function createUri(array $server): UriInterface
    {
        $uri = new Uri();

        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            $uri = $uri->withScheme('https');
        } else {
            $uri = $uri->withScheme('http');
        }

        if (isset($server['SERVER_PORT'])) {
            $uri = $uri->withPort((int)$server['SERVER_PORT']);
        } else {
            $uri = $uri->withPort($uri->getScheme() === 'https' ? 443 : 80);
        }

        if (isset($server['HTTP_HOST'])) {
            $parts = explode(':', $server['HTTP_HOST']);
            $uri = count($parts) == 2
                ? $uri->withHost($parts[0])
                    ->withPort((int)$parts[1])
                : $uri->withHost($server['HTTP_HOST']);
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['REQUEST_URI'])) {
            $uri = $uri->withPath($server['REQUEST_URI']);
        }

        if (isset($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }

    private function parseUploadedFiles(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            $uploadedFiles[] = new UploadedFile(
                $file['tmp_name'],
                $file['size'],
                $file['error'],
                $file['name'],
                $file['type'],
            );
        }

        return $uploadedFiles;
    }

    private function parseProtocolVersion(array $server): string
    {
        if (isset($server['SERVER_PROTOCOL']) && \strpos($server['SERVER_PROTOCOL'], 'HTTP/') === 0) {
            return \substr($server['SERVER_PROTOCOL'], 5);
        }

        return '1.1';
    }
}
