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

final class ShuyunOpenPlatformMemberInfoQueryService
{
    public const GATEWAY_ACTION_MEMBER_INFO_QUERY = 'shuyun.loyalty.enhance.member.post';
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

    /** @param array<string,mixed> $distributorRow */
    public function querySingle(
        int $companyId,
        array $distributorRow,
        string $memberId,
        bool $forceOfflinePlat = false
    ): array
    {
        $memberId = trim($memberId);
        if ($memberId === '') {
            throw new \InvalidArgumentException('id is required for member query.');
        }
        $payload = $this->basePayload($companyId, $distributorRow, $memberId, $forceOfflinePlat);
        Log::channel(self::LOG_CHANNEL)->info('Shuyun member.query request prepared.', [
            'company_id' => $companyId,
            'member_id' => $memberId,
            'distributor_id' => $distributorRow['distributor_id'] ?? null,
            'distributor_self' => $distributorRow['distributor_self'] ?? null,
            'force_offline_plat' => $forceOfflinePlat,
            'plat_code' => $payload['platCode'],
            'platform' => $payload['platform'],
            'shop_id' => $payload['shopId'],
        ]);

        try {
            $result = $payload['client']->postJson(self::GATEWAY_ACTION_MEMBER_INFO_QUERY, [
                'id' => $memberId,
                'platCode' => $payload['platCode'],
                'shopId' => $payload['shopId'],
            ], $payload['token'], $payload['platform']);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun member.query failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun member.query failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun member.query failed: '.$e->getMessage(), 0, $e);
        }

        return (array) $result->getData();
    }

    /** @param array<string,mixed> $distributorRow */
    private function basePayload(
        int $companyId,
        array $distributorRow,
        string $memberId,
        bool $forceOfflinePlat
    ): array
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable for member action.');
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

        return [
            'shopId' => $shopId,
            'platCode' => $platCode,
            'platform' => $platformHeader,
            'client' => $client,
            'token' => $token !== null && $token !== '' ? $token : null,
            'id' => $memberId,
        ];
    }

    /** @param array<string,mixed> $distributorRow */
    private function resolvePlat(
        CompanyShuyunOpenPlatformConfig $config,
        int $companyId,
        array $distributorRow,
        bool $forceOfflinePlat
    ): array
    {
        return ['OFFLINE', 'offline'];
    }

    private function normalizeShopIdForPlat(string $shopId, string $platCode): string
    {
        return $shopId;
    }
}
