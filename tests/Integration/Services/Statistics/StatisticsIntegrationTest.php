<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Services\Statistics;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Core\Pagination;
use Kinescope\Enum\VideoStatus;
use Kinescope\Services\Statistics\Statistics;
use Kinescope\Services\Videos\Videos;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
final class StatisticsIntegrationTest extends TestCase
{
    private Videos $videos;

    private Statistics $statistics;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        $apiClient = ApiClientFactory::create()
            ->withCredentials(Credentials::fromString($apiKey))
            ->build();

        $this->videos = new Videos($apiClient);
        $this->statistics = new Statistics($this->videos);
    }

    public function testForAccountMatchesDoneVideoListTotal(): void
    {
        $expectedTotal = $this->videos
            ->list(pagination: new Pagination(1, 1), status: VideoStatus::DONE)
            ->getTotal();

        $dto = $this->statistics->forAccount();

        $this->assertSame($expectedTotal, $dto->videosCount);
        $this->assertGreaterThanOrEqual(0, $dto->getTotalSeconds());
    }

    public function testForAccountDurationIsAtLeastFirstPageDoneDuration(): void
    {
        $firstPage = $this->videos->list(
            pagination: new Pagination(1, 5),
            status: VideoStatus::DONE,
        );
        $sampleDuration = $firstPage->getTotalDuration();

        $dto = $this->statistics->forAccount();

        $this->assertGreaterThanOrEqual($sampleDuration, $dto->getTotalSeconds());
    }

    public function testForProjectMatchesDoneVideoListTotal(): void
    {
        $projectId = getenv('TESTS_STATISTICS_PROJECT_ID');

        if ($projectId === false || $projectId === '') {
            $this->markTestSkipped('TESTS_STATISTICS_PROJECT_ID environment variable not set');
        }

        $expectedTotal = $this->videos
            ->list(pagination: new Pagination(1, 1), projectId: $projectId, status: VideoStatus::DONE)
            ->getTotal();

        $dto = $this->statistics->forProject($projectId);

        $this->assertSame($expectedTotal, $dto->videosCount);
    }

    public function testForFolderMatchesDoneVideoListTotal(): void
    {
        $folderId = getenv('TESTS_STATISTICS_FOLDER_ID');

        if ($folderId === false || $folderId === '') {
            $this->markTestSkipped('TESTS_STATISTICS_FOLDER_ID environment variable not set');
        }

        $expectedTotal = $this->videos
            ->list(pagination: new Pagination(1, 1), folderId: $folderId, status: VideoStatus::DONE)
            ->getTotal();

        $dto = $this->statistics->forFolder($folderId);

        $this->assertSame($expectedTotal, $dto->videosCount);
    }
}
