<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos\Download;

use CurlHandle;
use Kinescope\Exception\KinescopeException;

final readonly class CurlFileTransfer implements FileTransferInterface
{
    public function __construct(
        private CurlFileTransferPolicy $policy = new CurlFileTransferPolicy(),
    ) {
    }

    public function transfer(FileTransferRequest $request, ?callable $onProgress = null): FileTransferResult
    {
        $fileHandle = @fopen($request->outputPath, 'wb');

        if ($fileHandle === false) {
            throw new KinescopeException(sprintf('Failed to open file for writing: "%s"', $request->outputPath));
        }

        $curlHandle = curl_init($request->url);

        if ($curlHandle === false) {
            fclose($fileHandle);

            throw new KinescopeException(sprintf('Failed to initialize cURL transfer for URL: "%s"', $request->url));
        }

        $bytesWritten = 0;
        $writeFailure = null;

        try {
            $options = $this->policy->baseOptions();
            $headers = $this->policy->headersFor($request);

            if ($headers !== []) {
                $options[CURLOPT_HTTPHEADER] = $headers;
            }

            $options[CURLOPT_WRITEFUNCTION] = static function (
                CurlHandle $curlHandle,
                string $chunk,
            ) use ($fileHandle, $request, $onProgress, &$bytesWritten, &$writeFailure): int {
                $chunkLength = strlen($chunk);
                $written = @fwrite($fileHandle, $chunk);

                if ($written === false || $written !== $chunkLength) {
                    $writeFailure = sprintf(
                        'Failed to write download chunk to "%s": expected %d bytes, wrote %d bytes.',
                        $request->outputPath,
                        $chunkLength,
                        $written === false ? 0 : $written,
                    );

                    return $written === false ? 0 : $written;
                }

                $bytesWritten += $written;

                if ($onProgress !== null) {
                    $reportedBytes = curl_getinfo($curlHandle, CURLINFO_CONTENT_LENGTH_DOWNLOAD_T);
                    $totalBytes = is_int($reportedBytes) && $reportedBytes >= 0
                        ? $reportedBytes
                        : $request->expectedBytes;

                    $onProgress(new FileTransferProgress(
                        bytesWritten: $bytesWritten,
                        totalBytes: $totalBytes,
                    ));
                }

                return $written;
            };

            if (! curl_setopt_array($curlHandle, $options)) {
                throw new KinescopeException(sprintf('Failed to configure cURL transfer for URL: "%s"', $request->url));
            }

            $success = curl_exec($curlHandle);
            $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);
            $reportedBytes = $this->reportedBytes($curlHandle) ?? $bytesWritten;

            if ($success === false) {
                if ($writeFailure !== null) {
                    throw new KinescopeException($writeFailure);
                }

                throw new KinescopeException(sprintf(
                    'cURL transfer failed for URL "%s": [%d] %s',
                    $request->url,
                    curl_errno($curlHandle),
                    curl_error($curlHandle),
                ));
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new KinescopeException(sprintf(
                    'Download request failed with HTTP status %d for URL "%s".',
                    $statusCode,
                    $request->url,
                ));
            }

            return new FileTransferResult(
                outputPath: $request->outputPath,
                bytesWritten: $bytesWritten,
                reportedBytes: $reportedBytes,
            );
        } finally {
            fclose($fileHandle);
            curl_close($curlHandle);
        }
    }

    private function reportedBytes(CurlHandle $curlHandle): ?int
    {
        $reportedBytes = curl_getinfo($curlHandle, CURLINFO_CONTENT_LENGTH_DOWNLOAD_T);

        if (! is_int($reportedBytes) || $reportedBytes < 0) {
            return null;
        }

        return $reportedBytes;
    }
}
