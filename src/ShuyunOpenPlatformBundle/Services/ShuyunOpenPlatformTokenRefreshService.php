<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;

/**
 * 数云 Token 刷新：GET open-client `/client/callback/token/{appId}/v2`。
 * 成功仅表示触发数云侧刷新；新 Token 以回调 POST 为准，本服务 **不写** access_token。
 * 每次实际发起的 GET 写入 {@see config/logging.php} 通道 `shuyun_open_platform`，含完整 `request_url`。
 *
 * @see docs/数云开放平台/品牌自研对接流程.md §一
 */
final class ShuyunOpenPlatformTokenRefreshService implements ShuyunOpenPlatformTokenRefreshServiceInterface
{
    private const LOG_CHANNEL = 'shuyun_open_platform';

    private const RESPONSE_PREVIEW_MAX = 800;

    private string $tokenRefreshBaseUri;

    private float $timeoutSeconds;

    private ClientInterface $http;

    public function __construct(string $tokenRefreshBaseUri, float $timeoutSeconds, ClientInterface $http)
    {
        $this->tokenRefreshBaseUri = $tokenRefreshBaseUri;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->http = $http;
    }

    /**
     * 触发刷新。配置未启用、无可用 appId、已标记 isOverDue=1、或 HTTP 非 2xx / 网络失败时返回 false；**从不**修改库内 Token。
     */
    public function triggerRefresh(CompanyShuyunOpenPlatformConfig $config, bool $ignoreEnabledCheck = false): bool
    {
        if (!$ignoreEnabledCheck && $config->getIsEnabled() !== 1) {
            $this->logRefresh('debug', '数云 open-client Token 刷新跳过：is_enabled!=1（后管已关闭数云开放配置）', [
                'company_id' => $config->getCompanyId(),
                'is_enabled' => $config->getIsEnabled(),
            ]);

            return false;
        }

        $appId = $config->getAppId();
        if ($appId === null || $appId === '') {
            $this->logRefresh('debug', '数云 open-client Token 刷新跳过：无 app_id', [
                'company_id' => $config->getCompanyId(),
            ]);

            return false;
        }
        $overDue = $config->getIsOverDue();
        if ($overDue === '1') {
            $this->logRefresh('debug', '数云 open-client Token 刷新跳过：is_over_due=1', [
                'company_id' => $config->getCompanyId(),
                'app_id' => $appId,
            ]);

            return false;
        }

        $requestUrl = $this->buildRefreshUri($appId);
        try {
            $response = $this->http->request('GET', $requestUrl, [
                'timeout' => $this->timeoutSeconds,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            $this->logRefresh('error', '数云 open-client Token 刷新 GET 异常', [
                'request_url' => $requestUrl,
                'company_id' => $config->getCompanyId(),
                'app_id' => $appId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $status = $response->getStatusCode();
        $ok = $status >= 200 && $status < 300;
        $ctx = [
            'request_url' => $requestUrl,
            'company_id' => $config->getCompanyId(),
            'app_id' => $appId,
            'http_status' => $status,
        ];
        if ($ok) {
            $this->logRefresh('info', '数云 open-client Token 刷新 GET 成功', $ctx);
        } else {
            $ctx['response_body_preview'] = $this->truncateResponseBody((string) $response->getBody());
            $this->logRefresh('warning', '数云 open-client Token 刷新 GET 非成功状态', $ctx);
        }

        return $ok;
    }

    public function buildRefreshUri(string $appId): string
    {
        $base = rtrim($this->tokenRefreshBaseUri, '/');

        return $base.'/client/callback/token/'.rawurlencode($appId).'/v2';
    }

    private function truncateResponseBody(string $body): string
    {
        if (strlen($body) <= self::RESPONSE_PREVIEW_MAX) {
            return $body;
        }

        return substr($body, 0, self::RESPONSE_PREVIEW_MAX).'…(truncated)';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logRefresh(string $level, string $message, array $context): void
    {
        try {
            if (!\function_exists('app')) {
                return;
            }
            $app = \app();
            if (!$app->bound('log')) {
                return;
            }
            $app->make('log')->channel(self::LOG_CHANNEL)->log($level, $message, $context);
        } catch (\Throwable $e) {
        }
    }
}
