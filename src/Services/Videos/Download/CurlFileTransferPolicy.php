<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos\Download;

final readonly class CurlFileTransferPolicy
{
    private const int MAX_REDIRECTS = 5;
    private const int CONNECT_TIMEOUT_SECONDS = 10;
    private const int LOW_SPEED_LIMIT_BYTES = 1024;
    private const int LOW_SPEED_TIME_SECONDS = 60;

    /**
     * @return array<int, mixed>
     */
    public function baseOptions(): array
    {
        $options = [
            CURLOPT_HTTPGET => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_LOW_SPEED_LIMIT => self::LOW_SPEED_LIMIT_BYTES,
            CURLOPT_LOW_SPEED_TIME => self::LOW_SPEED_TIME_SECONDS,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_FAILONERROR => false,
        ];

        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[(int) constant('CURLOPT_PROTOCOLS_STR')] = 'http,https';
            $options[(int) constant('CURLOPT_REDIR_PROTOCOLS_STR')] = 'http,https';

            return $options;
        }

        $allowedProtocols = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        $options[CURLOPT_PROTOCOLS] = $allowedProtocols;
        $options[CURLOPT_REDIR_PROTOCOLS] = $allowedProtocols;

        return $options;
    }

    /**
     * @return list<string>
     */
    public function headersFor(FileTransferRequest $request): array
    {
        $headers = [];

        foreach ($request->headers as $name => $value) {
            $headers[] = sprintf('%s: %s', $name, $value);
        }

        return $headers;
    }
}
