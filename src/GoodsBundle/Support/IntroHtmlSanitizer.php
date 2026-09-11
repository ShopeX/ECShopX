<?php

declare(strict_types=1);

namespace GoodsBundle\Support;

class IntroHtmlSanitizer
{
    public static function sanitize(mixed $intro): mixed
    {
        if ($intro === null) {
            return null;
        }

        if (!is_string($intro)) {
            return $intro;
        }

        if ($intro === '') {
            return '';
        }

        $decoded = json_decode($intro, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sanitized = self::sanitizeJsonValue($decoded);

            return json_encode($sanitized, JSON_UNESCAPED_UNICODE);
        }

        return self::sanitizeHtml($intro);
    }

    /**
     * @param array<string|int, mixed> $value
     * @return array<string|int, mixed>
     */
    private static function sanitizeJsonValue(array $value): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = self::sanitizeMixedValue($item);
        }

        return $result;
    }

    private static function sanitizeMixedValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::sanitizeHtml($value);
        }

        if (is_array($value)) {
            return self::sanitizeJsonValue($value);
        }

        return $value;
    }

    private static function sanitizeHtml(string $html): string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        for ($i = 0; $i < 3; $i++) {
            $previous = $html;
            $html = self::stripDangerousHtml($html);
            if ($html === $previous) {
                break;
            }
        }

        return $html;
    }

    private static function stripDangerousHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/<\/script>/i', '', $html) ?? $html;
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/\b(href|src|xlink:href)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*/i', '$1=$2#', $html) ?? $html;

        return $html;
    }
}
