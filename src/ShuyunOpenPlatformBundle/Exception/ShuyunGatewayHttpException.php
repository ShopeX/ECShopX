<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Exception;

/**
 * HTTP 非 2xx 或空 body 等传输层问题。
 */
final class ShuyunGatewayHttpException extends \RuntimeException
{
    public function __construct(
        private int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
