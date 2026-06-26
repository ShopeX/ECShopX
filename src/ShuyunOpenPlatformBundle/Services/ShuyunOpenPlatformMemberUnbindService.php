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

final class ShuyunOpenPlatformMemberUnbindService
{
    public const GATEWAY_ACTION_MEMBER_UNBIND = 'shuyun.loyalty.member.unbind';
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
    public function unbindSingle(
        int $companyId,
        array $distributorRow,
        string $memberId,
        bool $forceOfflinePlat = false
    ): bool
    {
        $memberId = trim($memberId);
        if ($memberId === '') {
            throw new \InvalidArgumentException('id is required for member.unbind.');
        }
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable for member.unbind.');
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

        try {
            $client->postJson(self::GATEWAY_ACTION_MEMBER_UNBIND, [
                'id' => $memberId,
                'platCode' => $platCode,
                'shopId' => $shopId,
            ], $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun member.unbind failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun member.unbind failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun member.unbind failed: '.$e->getMessage(), 0, $e);
        }

        return true;
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
