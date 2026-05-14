<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos\Download;

use Closure;
use DirectoryIterator;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Videos\Download\CurlFileTransfer;
use Kinescope\Services\Videos\Download\FileTransferProgress;
use Kinescope\Services\Videos\Download\FileTransferRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class CurlFileTransferTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
    }

    public function testTransferWritesExpectedFileContentAndReportsProgress(): void
    {
        $payload = str_repeat('direct-transfer-', 4096);
        $server = $this->startStaticServer(['video.mp4' => $payload]);
        $outputDir = $this->createTempDir();
        $outputPath = $outputDir . '/video.mp4.part';
        $progress = [];

        try {
            $result = new CurlFileTransfer()->transfer(
                request: new FileTransferRequest(
                    url: $server['url'] . '/video.mp4',
                    outputPath: $outputPath,
                    expectedBytes: strlen($payload),
                ),
                onProgress: static function (FileTransferProgress $transferProgress) use (&$progress): void {
                    $progress[] = $transferProgress;
                },
            );

            $this->assertSame($payload, file_get_contents($outputPath));
            $this->assertSame($outputPath, $result->outputPath);
            $this->assertSame(strlen($payload), $result->bytesWritten);
            $this->assertNotEmpty($progress);

            $lastProgress = $progress[array_key_last($progress)];
            $this->assertSame(strlen($payload), $lastProgress->bytesWritten);
            $this->assertSame(strlen($payload), $lastProgress->totalBytes);
            $this->assertSame(100.0, $lastProgress->percent());
        } finally {
            $server['stop']();
            $this->filesystem->remove([$server['root'], $outputDir]);
        }
    }

    public function testTransferFailsOnHttpErrorStatus(): void
    {
        $server = $this->startStaticServer([]);
        $outputDir = $this->createTempDir();

        try {
            $this->expectException(KinescopeException::class);
            $this->expectExceptionMessage('HTTP status 404');

            new CurlFileTransfer()->transfer(new FileTransferRequest(
                url: $server['url'] . '/missing.mp4',
                outputPath: $outputDir . '/missing.mp4.part',
                expectedBytes: null,
            ));
        } finally {
            $server['stop']();
            $this->filesystem->remove([$server['root'], $outputDir]);
        }
    }

    public function testTransferFailsOnTransportError(): void
    {
        $outputDir = $this->createTempDir();
        $port = $this->reservePort();

        try {
            $this->expectException(KinescopeException::class);
            $this->expectExceptionMessage('cURL transfer failed');

            new CurlFileTransfer()->transfer(new FileTransferRequest(
                url: sprintf('http://127.0.0.1:%d/video.mp4', $port),
                outputPath: $outputDir . '/video.mp4.part',
                expectedBytes: null,
            ));
        } finally {
            $this->filesystem->remove($outputDir);
        }
    }

    public function testTransferFailsWhenOutputCannotBeOpened(): void
    {
        $this->expectException(KinescopeException::class);
        $this->expectExceptionMessage('Failed to open file for writing');

        new CurlFileTransfer()->transfer(new FileTransferRequest(
            url: 'http://127.0.0.1/video.mp4',
            outputPath: sys_get_temp_dir() . '/kinescope-sdk-missing-dir/video.mp4.part',
            expectedBytes: null,
        ));
    }

    public function testTransferFailsOnShortWrite(): void
    {
        if (! file_exists('/dev/full')) {
            $this->markTestSkipped('/dev/full is required to simulate a short write.');
        }

        $payload = str_repeat('x', 8192);
        $server = $this->startStaticServer(['video.mp4' => $payload]);

        try {
            $this->expectException(KinescopeException::class);
            $this->expectExceptionMessage('Failed to write download chunk');

            new CurlFileTransfer()->transfer(new FileTransferRequest(
                url: $server['url'] . '/video.mp4',
                outputPath: '/dev/full',
                expectedBytes: strlen($payload),
            ));
        } finally {
            $server['stop']();
            $this->filesystem->remove($server['root']);
        }
    }

    public function testDirectTransferHasDifferentDiskFootprintThanBufferedBaseline(): void
    {
        $payload = str_repeat('f', 1_048_576);
        $server = $this->startStaticServer(['video.mp4' => $payload]);
        $baselineDir = $this->createTempDir();
        $directDir = $this->createTempDir();
        $url = $server['url'] . '/video.mp4';

        try {
            $baselineOutput = $baselineDir . '/video.mp4.part';
            $bufferedResponseBodyPath = $baselineDir . '/response-body.tmp';
            $body = file_get_contents($url);

            $this->assertIsString($body);

            file_put_contents($bufferedResponseBodyPath, $body);
            file_put_contents($baselineOutput, $body);

            $directOutput = $directDir . '/video.mp4.part';
            $directResult = new CurlFileTransfer()->transfer(new FileTransferRequest(
                url: $url,
                outputPath: $directOutput,
                expectedBytes: strlen($payload),
            ));

            $this->assertSame(strlen($payload), filesize($baselineOutput));
            $this->assertSame(strlen($payload), filesize($directOutput));
            $this->assertSame(strlen($payload), $directResult->bytesWritten);
            $this->assertGreaterThanOrEqual(strlen($payload), filesize($bufferedResponseBodyPath));
            $this->assertSame(0, $this->nonOutputBytes($directDir, $directOutput));
        } finally {
            $server['stop']();
            $this->filesystem->remove([$server['root'], $baselineDir, $directDir]);
        }
    }

    /**
     * @param array<string, string> $files
     *
     * @return array{url: string, root: string, stop: Closure(): void}
     */
    private function startStaticServer(array $files): array
    {
        $root = $this->createTempDir();
        file_put_contents($root . '/__health__', 'ok');

        foreach ($files as $name => $contents) {
            file_put_contents($root . '/' . $name, $contents);
        }

        $port = $this->reservePort();
        $process = proc_open(
            [PHP_BINARY, '-S', sprintf('127.0.0.1:%d', $port), '-t', $root],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $root,
        );

        if (! is_resource($process)) {
            $this->fail('Failed to start local PHP server.');
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $stop = static function () use ($process, $pipes): void {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            proc_terminate($process);
            proc_close($process);
        };

        $healthUrl = sprintf('http://127.0.0.1:%d/__health__', $port);

        for ($attempt = 0; $attempt < 50; $attempt++) {
            if (@file_get_contents($healthUrl) === 'ok') {
                return [
                    'url' => sprintf('http://127.0.0.1:%d', $port),
                    'root' => $root,
                    'stop' => $stop,
                ];
            }

            usleep(100_000);
        }

        $stop();
        $this->fail('Local PHP server did not become ready.');
    }

    private function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $this->filesystem->mkdir($dir);

        return $dir;
    }

    private function nonOutputBytes(string $directory, string $outputPath): int
    {
        $bytes = 0;

        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot() || $file->getPathname() === $outputPath) {
                continue;
            }

            $bytes += $file->getSize();
        }

        return $bytes;
    }

    private function reservePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

        if (! is_resource($server)) {
            $this->fail(sprintf('Failed to reserve a TCP port: %s', $error));
        }

        $name = stream_socket_get_name($server, false);
        fclose($server);

        if ($name === false) {
            $this->fail('Failed to read reserved TCP port.');
        }

        $separatorPosition = strrpos($name, ':');

        if ($separatorPosition === false) {
            $this->fail(sprintf('Failed to parse reserved TCP port from "%s".', $name));
        }

        return (int) substr($name, $separatorPosition + 1);
    }
}
