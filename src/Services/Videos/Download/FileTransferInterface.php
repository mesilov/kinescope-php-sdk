<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos\Download;

interface FileTransferInterface
{
    /**
     * @param (callable(FileTransferProgress): void)|null $onProgress
     */
    public function transfer(FileTransferRequest $request, ?callable $onProgress = null): FileTransferResult;
}
