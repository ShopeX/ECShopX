<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Gateway;

use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;

/**
 * 开放网关 JSON 包：成功码 10000（见公共错误代码文档）。
 */
final class ShuyunGatewayResult
{
    public const SUCCESS_CODE = 10000;

    private function __construct(
        private int $code,
        private mixed $data,
        private string $msg
    ) {
    }

    public static function fromJsonString(string $json): self
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ShuyunGatewayJsonException('Invalid JSON: '.$e->getMessage(), 0, $e);
        }
        if (!is_array($decoded) || !array_key_exists('code', $decoded)) {
            throw new ShuyunGatewayJsonException('Missing code in gateway response.');
        }

        $code = (int) $decoded['code'];
        $data = $decoded['data'] ?? null;
        $msg = self::normalizeGatewayMessage($decoded);

        return new self($code, $data, $msg);
    }

    public function isSuccess(): bool
    {
        return $this->code === self::SUCCESS_CODE;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    /**
     * 数云网关部分错误体用 message 而非 msg（见公共错误代码文档 10999 等）。
     *
     * @param  array<string, mixed>  $decoded
     */
    private static function normalizeGatewayMessage(array $decoded): string
    {
        foreach (['msg', 'message'] as $key) {
            if (!isset($decoded[$key])) {
                continue;
            }
            $s = self::normalizeGatewayMessageValue($decoded[$key]);

            if ($s !== '') {
                return $s;
            }
        }

        return '';
    }

    private static function normalizeGatewayMessageValue(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                return trim($json);
            }
        }

        return '';
    }
}
