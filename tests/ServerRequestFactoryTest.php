<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole\Tests;

use Psr\Http\Message\UriInterface;
use RazonYang\Psr7\Swoole\ServerRequestFactory;
use RazonYang\Psr7\Swoole\ServerRequestFactoryInterface;
use RazonYang\Swoole\Unit\RequestBuilder;
use RazonYang\UnitHelper\ReflectionHelper;

class ServerRequestFactoryTest extends TestCase
{
    private string $boundary = 'AaB03x';

    public function messageProvider(): array
    {
        return [
            [
                'GET',
                '/',
                [
                    'Host'            => ['localhost'],
                    'Connection'      => ['keep-alive'],
                    'Pragma'          => ['no-cache'],
                    'Cache-Control'   => ['no-cache'],
                    'Accept'          => ['text/html'],
                    'Accept-Encoding' => ['gzip, deflate, br'],
                    'Cookie'          => ['phpsessid=fcccs2af8673a2f343a61a96551c8523d79ea; username=razonyang'],
                ],
                null,
            ],
            [
                'GET',
                '/users?name=foo&age=18',
                [
                    'Host' => ['localhost'],
                ],
                null,
            ],
            [
                'POST',
                '/users',
                [
                    'Host'           => ['localhost'],
                    'Content-Type'   => ['application/json'],
                    'Content-Length' => ['23'],
                ],
                '{"name":"bar","age":18}',
            ],
            [
                'POST',
                '/upload',
                [
                    'Host' => ['localhost'],
                ],
                null,
                [
                    'foo.txt' => __FILE__,
                    'bar.txt' => \dirname(__DIR__).\DIRECTORY_SEPARATOR.'README.md',
                ],
            ],
        ];
    }

    /**
     * @dataProvider messageProvider
     */
    public function testCreate(
        string $method,
        string $uri,
        array $headers,
        ?string $body,
        array $files = [],
    ): void {
        $cookies = [];
        if (isset($headers['Cookie'])) {
            foreach (explode('; ', $headers['Cookie'][0]) as $str) {
                list($name, $value) = explode('=', $str);
                $cookies[$name] = $value;
            }
        }

        $builder = (new RequestBuilder($method, $uri))
            ->headers($headers);

        if ($body) {
            $builder->body($body);
        } elseif ($files) {
            $builder->multipart([], $files);
        }

        $request = $builder->create();

        $factory = $this->createServerRequestFactory();
        $actual = $factory->create($request);

        $this->assertSame($method, $actual->getMethod());

        foreach ($headers as $name => $values) {
            $this->assertTrue($actual->hasHeader($name));
            $this->assertSame($values, $actual->getHeader($name));
        }

        $this->assertSame($cookies, $actual->getCookieParams());

        if ($body) {
            $this->assertSame($body, $actual->getBody()->__toString());
        }

        if ($files) {
            $this->assertCount(count($files), $actual->getUploadedFiles());
            foreach ($actual->getUploadedFiles() as $file) {
                $this->assertSame(\filesize($file->getClientFilename()), $file->getSize());
                $this->assertSame(\file_get_contents($file->getClientFilename()), $file->getStream()->__toString());
            }
        }
    }

    public function hostProvider(): array
    {
        return [
            [
                [],
                '',
            ],
            [
                [
                    'HTTP_HOST' => 'localhost',
                ],
                'localhost',
            ],
            [
                [
                    'HTTP_HOST' => 'example.com',
                ],
                'example.com',
            ],
            [
                [
                    'SERVER_NAME' => '127.0.0.1',
                ],
                '127.0.0.1',
            ],
        ];
    }

    /**
     * @dataProvider hostProvider
     */
    public function testCreateUriHost(array $server, string $host): void
    {
        $factory = $this->createServerRequestFactory();
        /** @var UriInterface $uri */
        $uri = ReflectionHelper::invokeMethod($factory, 'createUri', $server);

        $this->assertSame($host, $uri->getHost());
    }

    public function schemeProvider(): array
    {
        return [
            [
                [],
                'http',
            ],
            [
                [
                    'HTTPS' => 'off',
                ],
                'http',
            ],
            [
                [
                    'HTTPS' => 'on',
                ],
                'https',
            ],
            [
                [
                    'HTTPS' => true,
                ],
                'https',
            ],
        ];
    }

    /**
     * @dataProvider schemeProvider
     */
    public function testCreateUriScheme(array $server, string $scheme): void
    {
        $factory = $this->createServerRequestFactory();
        /** @var UriInterface $uri */
        $uri = ReflectionHelper::invokeMethod($factory, 'createUri', $server);

        $this->assertSame($scheme, $uri->getScheme());
    }

    public function portProvider(): array
    {
        return [
            [
                [],
                null,
            ],
            [
                [
                    'HTTPS' => 'on',
                ],
                null,
            ],
            [
                [
                    'HTTP_HOST' => 'localhost',
                ],
                null,
            ],
            [
                [
                    'SERVER_PORT' => 9501,
                ],
                9501,
            ],
            [
                [
                    'SERVER_PORT' => 9501,
                    'HTTP_HOST'   => 'localhost:9502',
                ],
                9502,
            ],
        ];
    }

    /**
     * @dataProvider portProvider
     */
    public function testCreateUriPort(array $server, ?int $port): void
    {
        $factory = $this->createServerRequestFactory();
        /** @var UriInterface $uri */
        $uri = ReflectionHelper::invokeMethod($factory, 'createUri', $server);

        $this->assertSame($port, $uri->getPort());
    }

    public function queryProvider(): array
    {
        return [
            [
                [],
                '',
            ],
            [
                [
                    'QUERY_STRING' => '',
                ],
                '',
            ],
            [
                [
                    'QUERY_STRING' => 'foo=bar',
                ],
                'foo=bar',
            ],
            [
                [
                    'QUERY_STRING' => 'foo=bar&fizz=buzz',
                ],
                'foo=bar&fizz=buzz',
            ],
        ];
    }

    /**
     * @dataProvider queryProvider
     */
    public function testCreateUriQuery(array $server, string $query): void
    {
        $factory = $this->createServerRequestFactory();
        /** @var UriInterface $uri */
        $uri = ReflectionHelper::invokeMethod($factory, 'createUri', $server);

        $this->assertSame($query, $uri->getQuery());
    }

    public function pathProvider(): array
    {
        return [
            [
                [
                    'REQUEST_URI' => '/',
                ],
                '/',
            ],
            [
                [
                    'REQUEST_URI' => '/users',
                ],
                '/users',
            ],
            [
                [
                    'REQUEST_URI' => '/users/',
                ],
                '/users/',
            ],
            [
                [
                    'REQUEST_URI' => '/users/1',
                ],
                '/users/1',
            ],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testCreateUriPath(array $server, string $path): void
    {
        $factory = $this->createServerRequestFactory();
        /** @var UriInterface $uri */
        $uri = ReflectionHelper::invokeMethod($factory, 'createUri', $server);

        $this->assertSame($path, $uri->getPath());
    }

    public function protocolProvider(): array
    {
        return [
            ['', '1.1'],
            ['HTTP/1.0', '1.0'],
            ['HTTP/1.1', '1.1'],
            ['HTTP/2', '2'],
        ];
    }

    /**
     * @dataProvider protocolProvider
     */
    public function testParseProtocolVersion(string $protocol, string $version): void
    {
        $factory = $this->createServerRequestFactory();
        $actual = ReflectionHelper::invokeMethod($factory, 'parseProtocolVersion', ['SERVER_PROTOCOL' => $protocol]);

        $this->assertSame($version, $actual);
    }

    public function filesProvider(): array
    {
        $tmpDir = \sys_get_temp_dir();

        return [
            [
                [],
            ],
            [
                [
                    'foo.jpg' => [
                        'name'     => 'foo.jpg',
                        'type'     => 'image/jpeg',
                        'tmp_name' => $tmpDir.\DIRECTORY_SEPARATOR.'swoole.upfile.foo.jpg',
                        'error'    => 0,
                        'size'     => 7,
                        'content'  => 'foo.jpg',
                    ],
                ],
            ],
            [
                [
                    'bar.png' => [
                        'name'     => 'bar.png',
                        'type'     => 'image/png',
                        'tmp_name' => $tmpDir.\DIRECTORY_SEPARATOR.'swoole.upfile.bar.png',
                        'error'    => 0,
                        'size'     => 7,
                        'content'  => 'bar.png',
                    ],
                    'fizz.jpg' => [
                        'name'     => 'fizz.jpg',
                        'type'     => 'image/jpeg',
                        'tmp_name' => $tmpDir.\DIRECTORY_SEPARATOR.'swoole.upfile.fizz.jpg',
                        'error'    => 0,
                        'size'     => 8,
                        'content'  => 'fizz.jpg',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider filesProvider
     */
    public function testParseUploadedFiles(array $files): void
    {
        foreach ($files as $file) {
            @\unlink($file['tmp_name']);
            \file_put_contents($file['tmp_name'], $file['content']);
        }

        $factory = $this->createServerRequestFactory();
        $actual = ReflectionHelper::invokeMethod($factory, 'parseUploadedFiles', $files);

        $this->assertCount(count($files), $actual);
        foreach ($actual as $uploadedFile) {
            /** @var UploadedFileInterface $uploadedFile */
            $file = $files[$uploadedFile->getClientFilename()];
            $this->assertSame($file['name'], $uploadedFile->getClientFilename());
            $this->assertSame($file['size'], $uploadedFile->getSize());
            $this->assertSame($file['error'], $uploadedFile->getError());
            $this->assertSame($file['type'], $uploadedFile->getClientMediaType());
            $this->assertSame($file['size'], $uploadedFile->getStream()->getSize());
            $this->assertSame($file['content'], $uploadedFile->getStream()->__toString());
        }
    }

    private function createServerRequestFactory(): ServerRequestFactoryInterface
    {
        $factory = new ServerRequestFactory();

        return $factory;
    }
}
