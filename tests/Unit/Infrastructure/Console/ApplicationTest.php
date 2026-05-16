<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Infrastructure\Console;

use Kinescope\Infrastructure\Console\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function testRegistersBrowseCommandWithoutRemovingVideoInfo(): void
    {
        $application = new Application();

        self::assertTrue($application->has('kinescope:browse'));
        self::assertTrue($application->has('video:info'));
    }
}
