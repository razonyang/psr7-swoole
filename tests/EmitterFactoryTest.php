<?php

declare(strict_types=1);

namespace RazonYang\Psr7\Swoole\Tests;

use RazonYang\Psr7\Swoole\EmitterFactory;
use RazonYang\UnitHelper\ReflectionHelper;

class EmitterFactoryTest extends TestCase
{
    public function newProvider(): array
    {
        return [
            [
                1024 * 1024,
                2 * 1024 * 1024,
                4 * 1024 * 1024,
                8 * 1024 * 1024,
            ],
        ];
    }

    /**
     * @dataProvider newProvider
     */
    public function testNew(int $bufferSize): void
    {
        $factory = new EmitterFactory($bufferSize);
        $this->assertSame($bufferSize, ReflectionHelper::getPropertyValue($factory, 'bufferSize'));
    }
}
