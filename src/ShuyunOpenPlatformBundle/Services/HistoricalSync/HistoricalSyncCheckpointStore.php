<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

/**
 * 文件 checkpoint：storage/shuyun_historical_sync/{company_id}/{step}.json
 */
final class HistoricalSyncCheckpointStore
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function read(int $companyId, string $step): ?string
    {
        $path = $this->path($companyId, $step);
        if (! is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return null;
        }
        $cursor = $data['cursor'] ?? null;

        return is_string($cursor) ? $cursor : null;
    }

    public function write(int $companyId, string $step, string $cursor): void
    {
        $dir = dirname($this->path($companyId, $step));
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Cannot create checkpoint directory: '.$dir);
        }
        $payload = json_encode(['cursor' => $cursor, 'updated_at' => time()], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            throw new \RuntimeException('Cannot encode checkpoint JSON.');
        }
        if (file_put_contents($this->path($companyId, $step), $payload) === false) {
            throw new \RuntimeException('Cannot write checkpoint: '.$this->path($companyId, $step));
        }
    }

    public function clear(int $companyId, string $step): void
    {
        $path = $this->path($companyId, $step);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(int $companyId, string $step): string
    {
        $safeStep = preg_replace('/[^a-z0-9_-]/', '', strtolower($step)) ?? $step;

        return rtrim($this->basePath, '/').'/'.$companyId.'/'.$safeStep.'.json';
    }
}
