<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Infrastructure\Console;

use Kinescope\Infrastructure\Console\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function testRegistersResourceActionCommandsOnly(): void
    {
        $application = new Application();

        self::assertTrue($application->has('kinescope:project:list'));
        self::assertTrue($application->has('kinescope:project:show'));
        self::assertTrue($application->has('kinescope:folder:list'));
        self::assertTrue($application->has('kinescope:folder:show'));
        self::assertTrue($application->has('kinescope:video:list'));
        self::assertTrue($application->has('kinescope:video:show'));
        self::assertTrue($application->has('kinescope:video:asset:list'));

        self::assertFalse($application->has('video:info'));
        self::assertFalse($application->has('kinescope:browse'));
    }
}
