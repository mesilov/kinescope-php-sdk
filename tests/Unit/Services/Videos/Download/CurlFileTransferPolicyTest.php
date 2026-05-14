<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos\Download;

use Kinescope\Services\Videos\Download\CurlFileTransferPolicy;
use Kinescope\Services\Videos\Download\FileTransferRequest;
use PHPUnit\Framework\TestCase;

final class CurlFileTransferPolicyTest extends TestCase
{
    public function testBaseOptionsApplyDefaultCurlPreset(): void
    {
        $options = new CurlFileTransferPolicy()->baseOptions();

        $this->assertSame(true, $options[CURLOPT_HTTPGET]);
        $this->assertSame(true, $options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(5, $options[CURLOPT_MAXREDIRS]);
        $this->assertSame(true, $options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(10, $options[CURLOPT_CONNECTTIMEOUT]);
        $this->assertSame(0, $options[CURLOPT_TIMEOUT]);
        $this->assertSame(1024, $options[CURLOPT_LOW_SPEED_LIMIT]);
        $this->assertSame(60, $options[CURLOPT_LOW_SPEED_TIME]);
        $this->assertSame(false, $options[CURLOPT_RETURNTRANSFER]);
        $this->assertSame(false, $options[CURLOPT_HEADER]);
        $this->assertSame(false, $options[CURLOPT_FAILONERROR]);

        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $this->assertSame('http,https', $options[(int) constant('CURLOPT_PROTOCOLS_STR')]);
            $this->assertSame('http,https', $options[(int) constant('CURLOPT_REDIR_PROTOCOLS_STR')]);

            return;
        }

        $allowedProtocols = CURLPROTO_HTTP | CURLPROTO_HTTPS;

        $this->assertSame($allowedProtocols, $options[CURLOPT_PROTOCOLS]);
        $this->assertSame($allowedProtocols, $options[CURLOPT_REDIR_PROTOCOLS]);
    }

    public function testHeaderListContainsOnlyExplicitTransferHeaders(): void
    {
        $policy = new CurlFileTransferPolicy();

        $this->assertSame([], $policy->headersFor(new FileTransferRequest(
            url: 'https://cdn.example.test/video.mp4',
            outputPath: '/tmp/video.mp4.part',
            expectedBytes: null,
        )));

        $this->assertSame(
            ['Range: bytes=0-1023', 'X-Test: yes'],
            $policy->headersFor(new FileTransferRequest(
                url: 'https://cdn.example.test/video.mp4',
                outputPath: '/tmp/video.mp4.part',
                expectedBytes: null,
                headers: ['Range' => 'bytes=0-1023', 'X-Test' => 'yes'],
            )),
        );
    }
}
