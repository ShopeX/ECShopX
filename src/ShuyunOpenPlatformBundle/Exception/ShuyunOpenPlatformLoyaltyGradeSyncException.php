<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Exception;

/**
 * 等级档案同步失败（整批失败），携带逐条失败明细。
 */
final class ShuyunOpenPlatformLoyaltyGradeSyncException extends \RuntimeException
{
    /**
     * @param  list<array<string, mixed>>  $failures
     */
    public function __construct(
        private array $failures,
        string $message = 'Loyalty grade sync failed.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
