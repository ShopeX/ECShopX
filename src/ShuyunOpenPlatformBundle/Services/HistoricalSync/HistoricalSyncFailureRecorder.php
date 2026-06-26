<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

final class HistoricalSyncFailureRecorder
{
    /** @var resource|null */
    private $handle;

    public function __construct(private readonly string $filePath)
    {
    }

    public function append(string $step, string $entityKey, string $reason): void
    {
        if ($this->handle === null) {
            $dir = dirname($this->filePath);
            if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Cannot create failures directory: '.$dir);
            }
            $new = ! is_file($this->filePath);
            $this->handle = fopen($this->filePath, 'ab');
            if ($this->handle === false) {
                throw new \RuntimeException('Cannot open failures file: '.$this->filePath);
            }
            if ($new) {
                fputcsv($this->handle, ['step', 'entity_key', 'reason', 'recorded_at']);
            }
        }
        fputcsv($this->handle, [$step, $entityKey, $reason, date('c')]);
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
