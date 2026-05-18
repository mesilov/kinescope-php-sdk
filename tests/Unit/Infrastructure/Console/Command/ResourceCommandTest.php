<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Infrastructure\Console\Command;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Infrastructure\Console\Command\FolderListCommand;
use Kinescope\Infrastructure\Console\Command\FolderShowCommand;
use Kinescope\Infrastructure\Console\Command\ProjectListCommand;
use Kinescope\Infrastructure\Console\Command\ProjectShowCommand;
use Kinescope\Infrastructure\Console\Command\VideoAssetListCommand;
use Kinescope\Infrastructure\Console\Command\VideoListCommand;
use Kinescope\Infrastructure\Console\Command\VideoShowCommand;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ResourceCommandTest extends TestCase
{
    private const string PROJECT_ID = '11111111-1111-4111-8111-111111111111';
    private const string FOLDER_ID = '22222222-2222-4222-8222-222222222222';
    private const string VIDEO_ID = '33333333-3333-4333-8333-333333333333';

    public function testProjectListFailsWhenApiKeyIsUnavailable(): void
    {
        $tester = $this->commandTester('kinescope:project:list', $this->createMock(ClientInterface::class));

        $previousApiKey = getenv('KINESCOPE_API_KEY');
        putenv('KINESCOPE_API_KEY=');

        try {
            $exitCode = $tester->execute([]);
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

    public function testProjectListRejectsInvalidFormat(): void
    {
        $tester = $this->commandTester('kinescope:project:list', $this->createMock(ClientInterface::class));

        $exitCode = $tester->execute(['--format' => 'xml', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('format must be one of: table, json.', $tester->getDisplay());
    }

    public function testProjectListJsonOutputIsDeterministic(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse($this->paginatedResponse([
                $this->projectPayload(id: self::PROJECT_ID, name: 'Beta', itemsCount: 2, foldersCount: 1),
                $this->projectPayload(id: '44444444-4444-4444-8444-444444444444', name: 'Alpha', itemsCount: 5, foldersCount: 3),
            ])),
        ]);

        $tester = $this->commandTester('kinescope:project:list', $httpClient);
        $exitCode = $tester->execute(['--format' => 'json', '--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('project', $decoded['resource']);
        self::assertSame('Alpha', $decoded['items'][0]['name']);
        self::assertSame(5, $decoded['items'][0]['items_count']);
        self::assertCount(3, $decoded['items'][0]['folders']);
    }

    public function testProjectListTableOutputContainsExpectedColumns(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse($this->paginatedResponse([
                $this->projectPayload(id: self::PROJECT_ID, name: 'Project A', itemsCount: 2, foldersCount: 1),
            ])),
        ]);

        $tester = $this->commandTester('kinescope:project:list', $httpClient);
        $exitCode = $tester->execute(['--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Project A', $tester->getDisplay());
        self::assertStringContainsString('items_count', $tester->getDisplay());
        self::assertStringContainsString('folders', $tester->getDisplay());
    }

    public function testProjectShowRejectsMalformedUuid(): void
    {
        $tester = $this->commandTester('kinescope:project:show', $this->createMock(ClientInterface::class));

        $exitCode = $tester->execute(['project-id' => 'not-a-uuid', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('project-id must be a valid UUID.', $tester->getDisplay());
    }

    public function testProjectShowOutputsJson(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->projectPayload(id: self::PROJECT_ID, name: 'Project A')]),
        ]);

        $tester = $this->commandTester('kinescope:project:show', $httpClient);
        $exitCode = $tester->execute(['project-id' => self::PROJECT_ID, '--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame(self::PROJECT_ID, $decoded['id']);
        self::assertSame('Project A', $decoded['name']);
    }

    public function testProjectShowOutputsCurrentApiProjectFields(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse([
                'data' => [
                    'id' => self::PROJECT_ID,
                    'name' => 'Project A',
                    'items_count' => 242,
                    'size' => 272661569450,
                    'privacy_domains' => ['learn.rarus.ru', 'demo2-learn.rarus.ru'],
                    'privacy_email_domains' => [],
                    'privacy_share' => [],
                    'player_id' => '83073711-4967-48ab-b2a7-0c628f795e0e',
                    'favorite' => false,
                    'folders' => [
                        ['id' => 'folder-1'],
                        ['id' => 'folder-2'],
                        ['id' => 'folder-3'],
                        ['id' => 'folder-4'],
                    ],
                    'created_at' => '2025-09-28T10:35:39.170997Z',
                    'updated_at' => '2026-03-26T13:23:29.567978Z',
                    'encrypted' => true,
                ],
            ]),
        ]);

        $tester = $this->commandTester('kinescope:project:show', $httpClient);
        $exitCode = $tester->execute(['project-id' => self::PROJECT_ID, '--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame(242, $decoded['items_count']);
        self::assertCount(4, $decoded['folders']);
        self::assertSame(272661569450, $decoded['size']);
        self::assertSame(['learn.rarus.ru', 'demo2-learn.rarus.ru'], $decoded['privacy_domains']);
        self::assertSame([], $decoded['privacy_email_domains']);
        self::assertSame('83073711-4967-48ab-b2a7-0c628f795e0e', $decoded['player_id']);
        self::assertFalse($decoded['favorite']);
        self::assertTrue($decoded['encrypted']);
        self::assertArrayNotHasKey('videos_count', $decoded);
        self::assertArrayNotHasKey('folders_count', $decoded);
        self::assertArrayNotHasKey('storage_used', $decoded);
        self::assertArrayNotHasKey('allowed_domains', $decoded);
    }

    public function testProjectShowReturnsFailureWhenProjectIsNotFound(): void
    {
        $httpClient = $this->httpClientReturning([
            new Response(404, ['Content-Type' => 'application/json'], '{"message":"Not Found"}'),
        ]);

        $tester = $this->commandTester('kinescope:project:show', $httpClient);
        $exitCode = $tester->execute(['project-id' => self::PROJECT_ID, '--api-key' => 'test-key']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Project "' . self::PROJECT_ID . '" not found.', $tester->getDisplay());
    }

    public function testFolderListRejectsMissingProjectId(): void
    {
        $tester = $this->commandTester('kinescope:folder:list', $this->createMock(ClientInterface::class));

        $exitCode = $tester->execute(['--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('folder:list requires --project-id.', $tester->getDisplay());
    }

    public function testFolderListJsonOutputValidatesProjectAndListsFolders(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->projectPayload(id: self::PROJECT_ID, name: 'Project A')]),
            $this->jsonResponse($this->paginatedResponse([
                $this->folderPayload(id: self::FOLDER_ID, name: 'Child', path: 'Root / Child'),
            ])),
        ]);

        $tester = $this->commandTester('kinescope:folder:list', $httpClient);
        $exitCode = $tester->execute([
            '--project-id' => self::PROJECT_ID,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('folder', $decoded['resource']);
        self::assertSame(self::PROJECT_ID, $decoded['projectId']);
        self::assertSame(self::FOLDER_ID, $decoded['items'][0]['id']);
        self::assertSame('Root / Child', $decoded['items'][0]['path']);
    }

    public function testFolderShowOutputsJson(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->folderPayload(id: self::FOLDER_ID, name: 'Folder A')]),
        ]);

        $tester = $this->commandTester('kinescope:folder:show', $httpClient);
        $exitCode = $tester->execute([
            'folder-id' => self::FOLDER_ID,
            '--project-id' => self::PROJECT_ID,
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame(self::FOLDER_ID, $decoded['id']);
        self::assertSame(self::PROJECT_ID, $decoded['project_id']);
    }

    public function testFolderShowRejectsMalformedProjectId(): void
    {
        $tester = $this->commandTester('kinescope:folder:show', $this->createMock(ClientInterface::class));

        $exitCode = $tester->execute([
            'folder-id' => self::FOLDER_ID,
            '--project-id' => 'not-a-uuid',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('project-id must be a valid UUID.', $tester->getDisplay());
    }

    public function testVideoListRejectsMissingProjectId(): void
    {
        $tester = $this->commandTester('kinescope:video:list', $this->createMock(ClientInterface::class));

        $exitCode = $tester->execute(['--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('video:list requires --project-id.', $tester->getDisplay());
    }

    public function testVideoListJsonOutputCanIncludeSanitizedAssets(): void
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

        $tester = $this->commandTester('kinescope:video:list', $httpClient);
        $exitCode = $tester->execute([
            '--project-id' => self::PROJECT_ID,
            '--include-assets' => true,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('video', $decoded['resource']);
        self::assertSame(self::PROJECT_ID, $decoded['projectId']);
        self::assertSame('Video A', $decoded['items'][0]['name']);
        self::assertTrue($decoded['items'][0]['assets'][0]['hasUrl']);
        self::assertTrue($decoded['items'][0]['assets'][0]['hasDownloadLink']);
        self::assertTrue($decoded['items'][0]['assets'][0]['downloadable']);
        self::assertArrayNotHasKey('url', $decoded['items'][0]['assets'][0]);
        self::assertArrayNotHasKey('download_link', $decoded['items'][0]['assets'][0]);
        self::assertStringNotContainsString('cdn.example.test', $tester->getDisplay());
    }

    public function testVideoListWithFolderValidatesFolderBelongsToProject(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->folderPayload(id: self::FOLDER_ID, name: 'Folder A')]),
            $this->jsonResponse($this->paginatedResponse([
                $this->videoPayload(id: self::VIDEO_ID, title: 'Video A', projectId: self::PROJECT_ID, folderId: self::FOLDER_ID),
            ])),
        ]);

        $tester = $this->commandTester('kinescope:video:list', $httpClient);
        $exitCode = $tester->execute([
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

    public function testVideoShowOutputsJson(): void
    {
        $httpClient = $this->httpClientReturning([
            $this->jsonResponse(['data' => $this->videoPayload(id: self::VIDEO_ID, title: 'Video A')]),
        ]);

        $tester = $this->commandTester('kinescope:video:show', $httpClient);
        $exitCode = $tester->execute(['video-id' => self::VIDEO_ID, '--api-key' => 'test-key']);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame(self::VIDEO_ID, $decoded['id']);
        self::assertSame('Video A', $decoded['title']);
    }

    public function testVideoShowRejectsMalformedUuid(): void
    {
        $tester = $this->commandTester('kinescope:video:show', $this->createMock(ClientInterface::class));

        $exitCode = $tester->execute(['video-id' => 'not-a-uuid', '--api-key' => 'test-key']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('video-id must be a valid UUID.', $tester->getDisplay());
    }

    public function testVideoAssetListJsonOutputIsSortedAndSanitized(): void
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

        $tester = $this->commandTester('kinescope:video:asset:list', $httpClient);
        $exitCode = $tester->execute([
            'video-id' => self::VIDEO_ID,
            '--format' => 'json',
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);

        $decoded = $this->decodeDisplayJson($tester);
        self::assertSame('video_asset', $decoded['resource']);
        self::assertSame(self::VIDEO_ID, $decoded['videoId']);
        self::assertSame('Video A', $decoded['videoName']);
        self::assertSame('asset-large', $decoded['items'][0]['id']);
        self::assertSame(4096, $decoded['items'][0]['fileSize']);
        self::assertTrue($decoded['items'][0]['hasUrl']);
        self::assertStringNotContainsString('cdn.example.test', $tester->getDisplay());
    }

    public function testVideoAssetListTableOutputContainsVideoTitleAndDownloadAvailability(): void
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

        $tester = $this->commandTester('kinescope:video:asset:list', $httpClient);
        $exitCode = $tester->execute([
            'video-id' => self::VIDEO_ID,
            '--api-key' => 'test-key',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Video: Video A', $tester->getDisplay());
        self::assertStringContainsString('hasDownloadLink', $tester->getDisplay());
        self::assertStringContainsString('yes', $tester->getDisplay());
        self::assertStringNotContainsString('cdn.example.test', $tester->getDisplay());
    }

    private function commandTester(string $commandName, ClientInterface $httpClient): CommandTester
    {
        $app = new Application('test', '1.0.0');

        foreach ($this->commands($httpClient) as $command) {
            $app->addCommand($command);
        }

        return new CommandTester($app->find($commandName));
    }

    /**
     * @return list<Command>
     */
    private function commands(ClientInterface $httpClient): array
    {
        $psr17 = new Psr17Factory();
        $apiClientFactory = ApiClientFactory::create()
            ->withHttpClient($httpClient)
            ->withRequestFactory($psr17)
            ->withStreamFactory($psr17);
        $logger = new NullLogger();

        return [
            new ProjectListCommand($apiClientFactory, $logger),
            new ProjectShowCommand($apiClientFactory, $logger),
            new FolderListCommand($apiClientFactory, $logger),
            new FolderShowCommand($apiClientFactory, $logger),
            new VideoListCommand($apiClientFactory, $logger),
            new VideoShowCommand($apiClientFactory, $logger),
            new VideoAssetListCommand($apiClientFactory, $logger),
        ];
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
        int $itemsCount = 0,
        int $foldersCount = 0,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'items_count' => $itemsCount,
            'folders' => array_fill(0, $foldersCount, ['id' => 'folder-id']),
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
