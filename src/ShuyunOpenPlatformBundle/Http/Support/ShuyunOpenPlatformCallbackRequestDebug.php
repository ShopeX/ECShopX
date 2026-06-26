<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Support;

use Illuminate\Http\Request;

/**
 * 数云 HTTP 回调联调：入口请求快照（headers/query/body），供 token / 等级等回调复用。
 */
final class ShuyunOpenPlatformCallbackRequestDebug
{
    /**
     * @return array<string, mixed>
     */
    public static function capture(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        $raw = (string) $request->getContent();
        $max = (int) config('shuyun_open_platform.callback_debug_log_body_max_bytes', 65536);
        if ($max > 0 && strlen($raw) > $max) {
            $raw = substr($raw, 0, $max).'...[truncated, callback_debug_log_body_max_bytes='.$max.']';
        }

        return [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'query' => $request->query->all(),
            'headers' => $headers,
            'body_raw' => $raw,
        ];
    }
}
