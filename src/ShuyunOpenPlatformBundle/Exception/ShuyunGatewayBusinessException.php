<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Exception;

/**
 * 开放网关 HTTP 200 但业务 code ≠ 10000。
 */
final class ShuyunGatewayBusinessException extends \RuntimeException
{
    public function __construct(
        private int $businessCode,
        string $message,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getBusinessCode(): int
    {
        return $this->businessCode;
    }
}
