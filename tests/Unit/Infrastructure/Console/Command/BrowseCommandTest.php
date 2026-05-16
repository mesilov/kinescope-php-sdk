<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Infrastructure\Console\Command;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Infrastructure\Console\Command\BrowseCommand;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BrowseCommandTest extends TestCase
{
    private const string PROJECT_ID = '11111111-1111-4111-8111-111111111111';
    private const string FOLDER_ID = '22222222-2222-4222-8222-222222222222';
    private const string VIDEO_ID = '33333333-3333-4333-8333-333333333333';

    public function testFailsWhenApiKeyIsUnavailable(): void
    {
        $tester = $this->registerAndGetTester($this->buildCommand($this->createMock(ClientInterface::class)));

        $previousApiKey = getenv('KINESCOPE_API_KEY');
        putenv('KINESCOPE_API_KEY=');

        try {
            $exitCode = $tester->execute(['resource' => 'projects']);
        } finally {
            if ($previousApiKey === false) {
                putenv('KINESCOPE_API_KEY');
            } else {
                putenv('KINESCOPE_API_KEY=' . $previousApiKey);
            }
        }

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('API key not provided', $tester->getDisplay());
    }

    public function testRejectsInvalidResource(): void
    {
        $tester = $this->registerAndGetTester($this->buildCommand($this->createMock(ClientInterface::class)));

        $exitCode = $tester->execute(['resource' => 'unknown', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('resource must be one of: projects, folders, videos, assets.', $tester->getDisplay());
    }

    public function testRejectsInvalidFormat(): void
    {
        $tester = $this->registerAndGetTester($this->buildCommand($this->createMock(ClientInterface::class)));

        $exitCode = $tester->execute(['resource' => 'projects', '--format' => 'xml', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('format must be one of: table, json.', $tester->getDisplay());
    }

    public function testRejectsMalformedUuid(): void
    {
        $tester = $this->registerAndGetTester($this->buildCommand($this->createMock(ClientInterface::class)));

        $exitCode = $tester->execute(['resource' => 'folders', '--project-id' => 'not-a-uuid', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('project-id must be a valid UUID.', $tester->getDisplay());
    }

    public function testRejectsMissingRequiredSelector(): void
    {
        $tester = $this->registerAndGetTester($this->buildCommand($this->createMock(ClientInterface::class)));

        $exitCode = $tester->execute(['resource' => 'videos', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('videos requires --project-id.', $tester->getDisplay());
    }

    public function testRejectsIncompatibleSelector(): void
    {
        $tester = $this->registerAndGetTester($this->buildCommand($this->createMock(ClientInterface::class)));

        $exitCode = $tester->execute([
            'resource' => 'projects',
            '--video-id' => self::VIDEO_ID,
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('projects does not accept --video-id.', $tester->getDisplay());
    }

    public function testProjectsJsonOutputIsDeterministic(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse($this->paginatedResponse([
                $this->projectPayload(id: self::PROJECT_ID, name: 'Beta', videosCount: 2, foldersCount: 1),
                $this->projectPayload(id: '44444444-4444-4444-8444-444444444444', name: 'Alpha', videosCount: 5, foldersCount: 3),
            ])),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute(['resource' => 'projects', '--format' => 'json', '--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('projects', $decoded['resource']);
        self::assertSame('Alpha', $decoded['items'][0]['name']);
        self::assertSame(5, $decoded['items'][0]['videosCount']);
        self::assertSame(3, $decoded['items'][0]['foldersCount']);
    }

    public function testProjectsTableOutputContainsExpectedColumns(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse($this->paginatedResponse([
                $this->projectPayload(id: self::PROJECT_ID, name: 'Project A', videosCount: 2, foldersCount: 1),
            ])),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute(['resource' => 'projects', '--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Project A', $tester->getDisplay());
        self::assertStringContainsString('videosCount', $tester->getDisplay());
        self::assertStringContainsString('foldersCount', $tester->getDisplay());
    }

    public function testFoldersJsonOutputValidatesProjectAndListsFolders(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->projectPayload(id: self::PROJECT_ID, name: 'Project A')]),
            $this->jsonResponse($this->paginatedResponse([
                $this->folderPayload(id: self::FOLDER_ID, name: 'Child', path: 'Root / Child'),
            ])),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute([
            'resource' => 'folders',
            '--project-id' => self::PROJECT_ID,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('folders', $decoded['resource']);
        self::assertSame(self::PROJECT_ID, $decoded['projectId']);
        self::assertSame(self::FOLDER_ID, $decoded['items'][0]['id']);
        self::assertSame('Root / Child', $decoded['items'][0]['path']);
    }

    public function testVideosJsonOutputCanIncludeSanitizedAssets(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse($this->paginatedResponse([
                $this->videoPayload(
                    id: self::VIDEO_ID,
                    title: 'Video A',
                    projectId: self::PROJECT_ID,
                    folderId: self::FOLDER_ID,
                    assets: [
                        $this->assetPayload(
                            id: 'asset-1',
                            fileSize: 1024,
                            url: 'https://cdn.example.test/video.mp4',
                            downloadLink: 'https://cdn.example.test/download.mp4'
                        ),
                    ],
                ),
            ])),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute([
            'resource' => 'videos',
            '--project-id' => self::PROJECT_ID,
            '--include-assets' => true,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('videos', $decoded['resource']);
        self::assertSame(self::PROJECT_ID, $decoded['projectId']);
        self::assertSame('Video A', $decoded['items'][0]['name']);
        self::assertTrue($decoded['items'][0]['assets'][0]['hasUrl']);
        self::assertTrue($decoded['items'][0]['assets'][0]['hasDownloadLink']);
        self::assertTrue($decoded['items'][0]['assets'][0]['downloadable']);
        self::assertArrayNotHasKey('url', $decoded['items'][0]['assets'][0]);
        self::assertArrayNotHasKey('download_link', $decoded['items'][0]['assets'][0]);
        self::assertStringNotContainsString('cdn.example.test', $tester->getDisplay());
    }

    public function testVideosWithFolderValidateFolderBelongsToProject(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->folderPayload(id: self::FOLDER_ID, name: 'Folder A')]),
            $this->jsonResponse($this->paginatedResponse([
                $this->videoPayload(id: self::VIDEO_ID, title: 'Video A', projectId: self::PROJECT_ID, folderId: self::FOLDER_ID),
            ])),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute([
            'resource' => 'videos',
            '--project-id' => self::PROJECT_ID,
            '--folder-id' => self::FOLDER_ID,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame(self::FOLDER_ID, $decoded['folderId']);
        self::assertSame(self::FOLDER_ID, $decoded['items'][0]['folderId']);
    }

    public function testAssetsJsonOutputIsSortedAndSanitized(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse([
                'data' => $this->videoPayload(
                    id: self::VIDEO_ID,
                    title: 'Video A',
                    assets: [
                        $this->assetPayload(id: 'asset-small', fileSize: 1024, downloadLink: null),
                        $this->assetPayload(
                            id: 'asset-large',
                            fileSize: 4096,
                            url: 'https://cdn.example.test/large.mp4',
                            downloadLink: 'https://cdn.example.test/large-download.mp4'
                        ),
                    ],
                ),
            ]),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute([
            'resource' => 'assets',
            '--video-id' => self::VIDEO_ID,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('assets', $decoded['resource']);
        self::assertSame(self::VIDEO_ID, $decoded['videoId']);
        self::assertSame('Video A', $decoded['videoName']);
        self::assertSame('asset-large', $decoded['items'][0]['id']);
        self::assertSame(4096, $decoded['items'][0]['fileSize']);
        self::assertSame(0.0, $decoded['items'][0]['fileSizeMb']);
        self::assertTrue($decoded['items'][0]['hasUrl']);
        self::assertStringNotContainsString('cdn.example.test', $tester->getDisplay());
    }

    public function testAssetsTableOutputContainsVideoTitleAndDownloadAvailability(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse([
                'data' => $this->videoPayload(
                    id: self::VIDEO_ID,
                    title: 'Video A',
                    assets: [
                        $this->assetPayload(id: 'asset-1', fileSize: 1024, downloadLink: 'https://cdn.example.test/download.mp4'),
                    ],
                ),
            ]),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute([
            'resource' => 'assets',
            '--video-id' => self::VIDEO_ID,
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Video: Video A', $tester->getDisplay());
        self::assertStringContainsString('hasDownloadLink', $tester->getDisplay());
        self::assertStringContainsString('yes', $tester->getDisplay());
        self::assertStringNotContainsString('cdn.example.test', $tester->getDisplay());
    }

    public function testInvalidFolderProjectRelationshipReturnsInvalid(): void
    {
        $httpClient = $this->httpClientReturning([
            new Response(404, ['Content-Type' => 'application/json'], '{"message":"Not Found"}'),
        ]);

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute([
            'resource' => 'videos',
            '--project-id' => self::PROJECT_ID,
            '--folder-id' => self::FOLDER_ID,
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString(
            sprintf('Folder %s does not belong to project %s.', self::FOLDER_ID, self::PROJECT_ID),
            $tester->getDisplay()
        );
    }

    /**
     * @param list<Response> $responses
     */
    private function httpClientReturning(array $responses): ClientInterface
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnOnConsecutiveCalls(...$responses);

        return $httpClient;
    }

    private function buildCommand(ClientInterface $httpClient): BrowseCommand
    {
        $psr17 = new Psr17Factory();
        $apiClientFactory = ApiClientFactory::create()
            ->withHttpClient($httpClient)
            ->withRequestFactory($psr17)
            ->withStreamFactory($psr17);

        return new BrowseCommand($apiClientFactory, new NullLogger());
    }

    private function registerAndGetTester(BrowseCommand $command): CommandTester
    {
        $app = new Application('test', '1.0.0');
        $app->addCommand($command);

        return new CommandTester($command);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeDisplayJson(CommandTester $tester): array
    {
        $decoded = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function paginatedResponse(array $items): array
    {
        return [
            'data' => $items,
            'meta' => [
                'pagination' => [
                    'total' => count($items),
                    'page' => 1,
                    'per_page' => 100,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload): Response
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            (string) json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function projectPayload(
        string $id,
        string $name,
        int $videosCount = 0,
        int $foldersCount = 0,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'videos_count' => $videosCount,
            'folders_count' => $foldersCount,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function folderPayload(string $id, string $name, ?string $path = null): array
    {
        return [
            'id' => $id,
            'project_id' => self::PROJECT_ID,
            'name' => $name,
            'parent_id' => null,
            'videos_count' => 3,
            'path' => $path,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
        ];
    }

    /**
     * @param list<array<string, mixed>> $assets
     *
     * @return array<string, mixed>
     */
    private function videoPayload(
        string $id,
        string $title,
        string $projectId = self::PROJECT_ID,
        ?string $folderId = null,
        array $assets = [],
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'status' => 'done',
            'duration' => 120,
            'project_id' => $projectId,
            'folder_id' => $folderId,
            'assets' => $assets,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assetPayload(
        string $id,
        int $fileSize,
        ?string $url = null,
        ?string $downloadLink = null,
    ): array {
        return [
            'id' => $id,
            'video_id' => self::VIDEO_ID,
            'quality' => '720p',
            'resolution' => '1280x720',
            'bitrate' => 1500,
            'file_size' => $fileSize,
            'codec' => 'h264',
            'url' => $url,
            'download_link' => $downloadLink,
        ];
    }
}
