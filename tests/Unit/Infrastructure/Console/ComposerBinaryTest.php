<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Infrastructure\Console;

use PHPUnit\Framework\TestCase;

final class ComposerBinaryTest extends TestCase
{
    public function testComposerExportsNamedKinescopeBinaryOnly(): void
    {
        $rootDir = dirname(__DIR__, 4);
        $composerJson = json_decode(
            (string) file_get_contents($rootDir . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        self::assertSame(['bin/kinescope'], $composerJson['bin'] ?? null);
        self::assertFileExists($rootDir . '/bin/kinescope');
        self::assertFileDoesNotExist($rootDir . '/bin/console');
    }

    public function testComposerRequiresMbstringExtension(): void
    {
        $rootDir = dirname(__DIR__, 4);
        $composerJson = json_decode(
            (string) file_get_contents($rootDir . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        self::assertSame('*', $composerJson['require']['ext-mbstring'] ?? null);
    }
}
