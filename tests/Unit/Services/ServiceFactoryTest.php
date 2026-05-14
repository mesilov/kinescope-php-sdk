<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services;

use Kinescope\Services\ServiceFactory;
use Kinescope\Services\Statistics\Statistics;
use Kinescope\Services\Videos\Videos;
use Kinescope\Tests\Unit\FakeApiClient;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ServiceFactoryTest extends TestCase
{
    public function testStatisticsReturnsSameInstanceOnRepeatedCalls(): void
    {
        $factory = ServiceFactory::withClient(new FakeApiClient());

        $this->assertSame($factory->statistics(), $factory->statistics());
    }

    public function testStatisticsIsConstructedWithFactoryVideosService(): void
    {
        $factory = ServiceFactory::withClient(new FakeApiClient());

        $videos = $factory->videos();
        $statistics = $factory->statistics();

        $this->assertInstanceOf(Statistics::class, $statistics);
        $this->assertSame($videos, $this->extractVideos($statistics));
    }

    private function extractVideos(Statistics $statistics): Videos
    {
        $property = new ReflectionProperty($statistics, 'videos');

        return $property->getValue($statistics);
    }
}
