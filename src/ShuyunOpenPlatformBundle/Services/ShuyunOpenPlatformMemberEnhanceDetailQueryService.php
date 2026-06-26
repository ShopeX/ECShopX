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
 * 数云全渠道会员详情（积分、等级、成长值等）GET：`shuyun.loyalty.enhance.member.query.detail`。
 * platCode / platform / shopId 与 {@see ShuyunOpenPlatformMemberInfoQueryService}（enhance.member.post）对齐；`tenant` 使用配置 {@see CompanyShuyunOpenPlatformConfig::getAuthValue()}（与店务同步 body 中 tenant_name 同源）。
 */
final class ShuyunOpenPlatformMemberEnhanceDetailQueryService
{
    public const GATEWAY_ACTION_ENHANCE_MEMBER_QUERY_DETAIL = 'shuyun.loyalty.enhance.member.query.detail';

    public const LOG_CHANNEL = 'shuyun_open_platform';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->shopSyncEligibility = $shopSyncEligibility;
        $this->shopIdResolver = $shopIdResolver;
        $this->httpClient = $httpClient;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * @param  array<string, mixed>  $distributorRow
     *
     * @return array<string, mixed>
     */
    public function queryDetail(
        int $companyId,
        array $distributorRow,
        string $platformAccountId,
        ?string $omniChannelMemberId = null,
        bool $forceOfflinePlat = false
    ): array {
        $platformAccountId = trim($platformAccountId);
        if ($platformAccountId === '') {
            throw new \InvalidArgumentException('id (platform account) is required for enhance.member.query.detail.');
        }
        $omni = $omniChannelMemberId !== null ? trim($omniChannelMemberId) : '';

        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable for enhance.member.query.detail.');
        }
        $tenant = trim((string) ($config->getAuthValue() ?? ''));
        if ($tenant === '') {
            throw new \RuntimeException('Shuyun tenant (auth_value) is empty; cannot call enhance.member.query.detail.');
        }

        [$platCode, $platformHeader] = $this->resolvePlat($config, $companyId, $distributorRow, $forceOfflinePlat);
        $shopId = $this->normalizeShopIdForPlat(
            $this->shopIdResolver->resolve($distributorRow),
            $platCode
        );

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

        $query = [
            'id' => $platformAccountId,
            'platCode' => $platCode,
            'shopId' => $shopId,
            'tenant' => $tenant,
        ];
        if ($omni !== '') {
            $query['memberId'] = $omni;
        }

        Log::channel(self::LOG_CHANNEL)->info('Shuyun enhance.member.query.detail request prepared.', [
            'company_id' => $companyId,
            'platform_account_id' => $platformAccountId,
            'has_member_id' => $omni !== '',
            'distributor_id' => $distributorRow['distributor_id'] ?? null,
            'plat_code' => $platCode,
            'platform' => $platformHeader,
            'shop_id' => $shopId,
        ]);

        try {
            $result = $client->getQuery(self::GATEWAY_ACTION_ENHANCE_MEMBER_QUERY_DETAIL, $query, $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun enhance.member.query.detail failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun enhance.member.query.detail failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun enhance.member.query.detail failed: '.$e->getMessage(), 0, $e);
        }

        return (array) $result->getData();
    }

    /** @param array<string, mixed> $distributorRow */
    private function resolvePlat(
        CompanyShuyunOpenPlatformConfig $config,
        int $companyId,
        array $distributorRow,
        bool $forceOfflinePlat
    ): array {
        return ['OFFLINE', 'offline'];
    }

    private function normalizeShopIdForPlat(string $shopId, string $platCode): string
    {
        return $shopId;
    }
}
