<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 店铺创建/更新后向数云开放网关同步。
 * 线下 OFFLINE：{@see docs/数云开放平台/数云开放平台接口全量整理（2023-10-01）.md §2.1}（open apiId=44）；
 * 自定义平台：§2.2（open apiId=114）。
 *
 * 非 final：便于 Job 单测对网关结果做替身（{@see \Tests\ShuyunOpenPlatform\SyncShopToShuyunOpenPlatformJobTest}）。
 *
 * @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md
 * @see .tasks/plans/shuyun-platform-shop-batch-register-api.md
 */
class ShuyunOpenPlatformShopSyncService
{
    /** @see docs/数云开放平台/全渠道会员+线下权益对接数云技术文档.md 接口列表序号 1 */
    public const GATEWAY_ACTION_SHOP_BATCH_REGISTER = 'shuyun.base.shop.batch.register';

    /** @see docs/数云开放平台/数云开放平台接口全量整理（2023-10-01）.md §2.2 */
    public const GATEWAY_ACTION_PLATFORM_SHOP_BATCH_REGISTER = 'shuyun.base.platform.shop.batch.register';

    public const LOG_CHANNEL = 'shuyun_open_platform';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ShuyunOpenPlatformShopPlatCodeResolver $platCodeResolver;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ShuyunOpenPlatformShopPlatCodeResolver $platCodeResolver,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->platCodeResolver = $platCodeResolver;
        $this->httpClient = $httpClient;
        $this->gatewayShopIdResolver = $gatewayShopIdResolver;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * @param  array<string, mixed>  $distributorRow  distribution_distributor 行数据（含 name、distributor_id 等；`shop_id` 取 distributor_id）
     * @return bool true 网关业务成功；false 跳过、失败或异常（已记日志）
     */
    public function syncShop(int $companyId, array $distributorRow, array $targetPlatCodes = []): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->isEligible($config)) {
            Log::channel(self::LOG_CHANNEL)->info('Shuyun open platform shop sync: tenant not eligible, skip.', [
                'company_id' => $companyId,
                'distributor_id' => $distributorRow['distributor_id'] ?? null,
                'has_config_row' => $config !== null,
            ]);

            return false;
        }

        try {
            $body = $this->buildShopEnvelope($config, $distributorRow, $targetPlatCodes);
        } catch (\InvalidArgumentException $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun open platform shop sync: cannot resolve gateway shop_id from distributor.', [
                'company_id' => $companyId,
                'distributor_id' => $distributorRow['distributor_id'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
        $baseUri = (string) config('shuyun_open_platform.base_uri');
        $baseUri = $baseUri !== '' ? rtrim($baseUri, '/').'/' : 'http://open-api.shuyun.com/';

        $client = $this->gatewayClientFactory->create(
            (string) $config->getAppId(),
            (string) $config->getAppSecret(),
            $baseUri,
            $this->httpClient,
            $companyId,
        );
        $token = $config->getAccessToken();
        $tokenStr = $token !== null && $token !== '' ? $token : null;
        $chunks = $this->splitShopBodyByPlatformHeader($body);
        if ($chunks === []) {
            return false;
        }

        $attemptedGatewayAction = self::GATEWAY_ACTION_SHOP_BATCH_REGISTER;
        try {
            foreach ($chunks as $chunk) {
                $platformHeader = $chunk['_platform_header'];
                unset($chunk['_platform_header']);
                $gatewayAction = $this->resolveShopBatchRegisterGatewayAction($chunk);
                $attemptedGatewayAction = $gatewayAction;
                $postBody = $this->buildShopBatchRegisterPostBody($gatewayAction, $chunk, $config);
                $client->postJson($gatewayAction, $postBody, $tokenStr, $platformHeader);
            }
        } catch (ShuyunGatewayBusinessException $e) {
            $this->logShopSyncFailure($companyId, $distributorRow, $attemptedGatewayAction, 'ShuyunGatewayBusinessException', $e->getBusinessCode(), $e->getMessage());

            return false;
        } catch (ShuyunGatewayHttpException $e) {
            $this->logShopSyncFailure($companyId, $distributorRow, $attemptedGatewayAction, 'ShuyunGatewayHttpException', $e->getStatusCode(), $e->getMessage());

            return false;
        } catch (ShuyunGatewayJsonException $e) {
            $this->logShopSyncFailure($companyId, $distributorRow, $attemptedGatewayAction, 'ShuyunGatewayJsonException', null, $e->getMessage());

            return false;
        } catch (\Throwable $e) {
            $this->logShopSyncFailure($companyId, $distributorRow, $attemptedGatewayAction, get_class($e), null, $e->getMessage());

            return false;
        }

        return true;
    }

    public function isEligible(?CompanyShuyunOpenPlatformConfig $row): bool
    {
        if ($row === null) {
            return false;
        }
        if (trim((string) ($row->getAuthValue() ?? '')) === '') {
            return false;
        }
        if ($row->getIsEnabled() !== 1) {
            return false;
        }
        $appId = $row->getAppId();
        if ($appId === null || $appId === '') {
            return false;
        }
        $secret = $row->getAppSecret();
        if ($secret === null || $secret === '') {
            return false;
        }
        $tok = $row->getAccessToken();
        if ($tok === null || $tok === '') {
            return false;
        }
        if ($row->getIsOverDue() === '1') {
            return false;
        }

        return true;
    }

    /**
     * 公共头 `platform` 单值，按 `shops[].plat_code` 分组为多次请求；OFFLINE → 头 `offline`，其余 → 小写 platCode。
     *
     * @param  array<string, mixed>  $body  buildShopEnvelope 返回值
     * @return list<array<string, mixed>>  每项含 `_platform_header`（调用方需 unset 后再 POST）
     */
    private function splitShopBodyByPlatformHeader(array $body): array
    {
        $shops = $body['shops'] ?? null;
        if (!is_array($shops) || $shops === []) {
            return [];
        }
        $tenantName = $body['tenant_name'] ?? '';
        $appId = $body['app_id'] ?? '';
        /** @var array<string, list<array<string, mixed>>> $buckets */
        $buckets = [];
        foreach ($shops as $shop) {
            if (!is_array($shop)) {
                continue;
            }
            $raw = trim((string) ($shop['plat_code'] ?? ''));
            $headerPlat = strtoupper($raw) === 'OFFLINE' ? 'offline' : strtolower($raw);
            if ($headerPlat === '') {
                continue;
            }
            if (!isset($buckets[$headerPlat])) {
                $buckets[$headerPlat] = [];
            }
            $buckets[$headerPlat][] = $shop;
        }
        $out = [];
        foreach ($buckets as $header => $list) {
            if ($list === []) {
                continue;
            }
            $out[] = [
                'tenant_name' => $tenantName,
                'app_id' => $appId,
                'shops' => $list,
                '_platform_header' => $header,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $chunkBody  已 unset `_platform_header`，含 tenant_name、app_id、shops
     */
    private function resolveShopBatchRegisterGatewayAction(array $chunkBody): string
    {
        $shops = $chunkBody['shops'] ?? null;
        if (!is_array($shops) || $shops === []) {
            return self::GATEWAY_ACTION_SHOP_BATCH_REGISTER;
        }
        $first = $shops[0];
        if (!is_array($first)) {
            return self::GATEWAY_ACTION_SHOP_BATCH_REGISTER;
        }
        $raw = trim((string) ($first['plat_code'] ?? ''));

        return strtoupper($raw) === 'OFFLINE'
            ? self::GATEWAY_ACTION_SHOP_BATCH_REGISTER
            : self::GATEWAY_ACTION_PLATFORM_SHOP_BATCH_REGISTER;
    }

    /**
     * 自定义平台店铺批量注册（apiId=114）须在 JSON body 中带 app_secret；线下门店接口（apiId=44）不带。
     *
     * @param  array<string, mixed>  $chunkBody  tenant_name、app_id、shops
     * @return array<string, mixed>
     */
    private function buildShopBatchRegisterPostBody(string $gatewayAction, array $chunkBody, CompanyShuyunOpenPlatformConfig $config): array
    {
        if ($gatewayAction !== self::GATEWAY_ACTION_PLATFORM_SHOP_BATCH_REGISTER) {
            return $chunkBody;
        }
        $out = $chunkBody;
        $out['app_secret'] = (string) $config->getAppSecret();

        return $out;
    }

    /**
     * @param  array<string, mixed>  $distributorRow
     * @return array<string, mixed>
     */
    private function buildShopEnvelope(CompanyShuyunOpenPlatformConfig $config, array $distributorRow, array $targetPlatCodes = []): array
    {
        $shop = [
            'shop_id' => $this->gatewayShopIdResolver->resolve($distributorRow),
            'shop_name' => (string) ($distributorRow['name'] ?? ''),
            'plat_code' => $this->platCodeResolver->resolve($config, $distributorRow),
        ];

        $openDate = $this->formatDateYmd($distributorRow['created'] ?? null);
        if ($openDate !== null) {
            $shop['open_date'] = $openDate;
        }
        $modified = $this->formatDateTime($distributorRow['updated'] ?? null);
        if ($modified !== null) {
            $shop['modified'] = $modified;
        }
        $addr = $this->composeAddress($distributorRow);
        if ($addr !== '') {
            $shop['address'] = $addr;
        }
        foreach (['province' => 'state', 'city' => 'city', 'area' => 'district'] as $src => $dst) {
            $v = trim((string) ($distributorRow[$src] ?? ''));
            if ($v !== '') {
                $shop[$dst] = $v;
            }
        }
        $logo = trim((string) ($distributorRow['logo'] ?? ''));
        if ($logo !== '') {
            $shop['shop_logo'] = $logo;
        }
        $intro = trim((string) ($distributorRow['introduce'] ?? ''));
        if ($intro !== '') {
            $shop['shop_desc'] = $intro;
        }

        if ($targetPlatCodes === []) {
            $targetPlatCodes = ['OFFLINE'];
        }
        $shops = $this->buildTargetShops($shop, $distributorRow, $targetPlatCodes);

        return [
            'tenant_name' => trim((string) ($config->getAuthValue() ?? '')),
            'app_id' => (string) $config->getAppId(),
            'shops' => $shops,
        ];
    }

    /**
     * @param  array<string, mixed>  $baseShop
     * @param  array<string, mixed>  $distributorRow
     * @param  array<int, string>  $targetPlatCodes
     * @return array<int, array<string, mixed>>
     */
    private function buildTargetShops(array $baseShop, array $distributorRow, array $targetPlatCodes): array
    {
        $valid = strtolower(trim((string) ($distributorRow['is_valid'] ?? '')));

        $shops = [];
        foreach ($targetPlatCodes as $platCode) {
            $shop = $baseShop;
            $upperPlat = strtoupper(trim((string) $platCode));
            if (($valid === 'false' || $valid === '0') && $upperPlat !== 'OFFLINE') {
                continue;
            }
            $shop['plat_code'] = $upperPlat;
            $status = $this->resolveTargetShopStatusForPlat($valid, $upperPlat);
            if ($status !== null) {
                $shop['status'] = $status;
            }
            $shops[] = $shop;
        }

        return $shops;
    }

    /**
     * 按 `is_valid` 与 `plat_code` 得到该行 {@see shuyun.base.shop.batch.register} `shops[].status`。
     * 禁用态（false/0）：仅会生成 OFFLINE 行（**1**）；非 OFFLINE 目标在 {@see buildTargetShops} 中已跳过，不再配线上 **2**。
     */
    private function resolveTargetShopStatusForPlat(string $validNormalized, string $upperPlatCode): ?string
    {
        if ($validNormalized === 'true' || $validNormalized === '1') {
            return '1';
        }
        if ($validNormalized === 'false' || $validNormalized === '0') {
            return $upperPlatCode === 'OFFLINE' ? '1' : '2';
        }
        if ($validNormalized === 'closed') {
            return '2';
        }
        if ($validNormalized === 'delete') {
            return '0';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $distributorRow
     */
    private function composeAddress(array $distributorRow): string
    {
        $a = trim((string) ($distributorRow['address'] ?? ''));
        $h = trim((string) ($distributorRow['house_number'] ?? ''));
        if ($a === '') {
            return $h;
        }
        if ($h === '') {
            return $a;
        }

        return $a.' '.$h;
    }

    private function formatDateYmd(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            $ts = (int) $v;

            return $ts > 0 ? date('Y-m-d', $ts) : null;
        }
        if (is_string($v)) {
            $ts = strtotime($v);

            return $ts !== false ? date('Y-m-d', $ts) : null;
        }

        return null;
    }

    private function formatDateTime(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            $ts = (int) $v;

            return $ts > 0 ? date('Y-m-d H:i:s', $ts) : null;
        }
        if (is_string($v)) {
            $ts = strtotime($v);

            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $distributorRow
     */
    private function logShopSyncFailure(
        int $companyId,
        array $distributorRow,
        string $gatewayAction,
        string $exceptionClass,
        ?int $code,
        string $message
    ): void {
        Log::channel(self::LOG_CHANNEL)->warning('Shuyun open platform shop sync failed.', [
            'company_id' => $companyId,
            'distributor_id' => $distributorRow['distributor_id'] ?? null,
            'gateway_action' => $gatewayAction,
            'exception_class' => $exceptionClass,
            'code' => $code,
            'message' => mb_substr($message, 0, 500),
            'gateway_access_token_suffix' => $this->tokenSuffixHint($companyId),
        ]);
    }

    private function tokenSuffixHint(int $companyId): ?string
    {
        $cfg = $this->configRepository->findOneByCompanyId($companyId);
        if ($cfg === null) {
            return null;
        }
        $t = $cfg->getAccessToken();
        if ($t === null || $t === '') {
            return null;
        }
        if (strlen($t) > 4) {
            return '***'.substr($t, -4);
        }

        return '***';
    }
}
