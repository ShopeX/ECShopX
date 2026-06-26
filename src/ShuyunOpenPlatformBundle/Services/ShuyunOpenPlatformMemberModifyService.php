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

final class ShuyunOpenPlatformMemberModifyService
{
    public const GATEWAY_ACTION_MEMBER_MODIFY = 'shuyun.loyalty.member.modify';
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

    /** @param array<string,mixed> $distributorRow @param array<string,mixed> $changes */
    public function modifySingle(int $companyId, array $distributorRow, string $memberId, array $changes): bool
    {
        $memberId = trim($memberId);
        if ($memberId === '') {
            throw new \InvalidArgumentException('id is required for member.modify.');
        }
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable for member.modify.');
        }
        [$platCode, $platformHeader] = $this->resolvePlat($config, $companyId, $distributorRow);
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

        $payload = array_merge(['id' => $memberId, 'platCode' => $platCode, 'shopId' => $shopId], $changes);

        try {
            $client->putJson(self::GATEWAY_ACTION_MEMBER_MODIFY, $payload, $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun member.modify failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun member.modify failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun member.modify failed: '.$e->getMessage(), 0, $e);
        }

        return true;
    }

    /** @param array<string,mixed> $distributorRow */
    private function resolvePlat(CompanyShuyunOpenPlatformConfig $config, int $companyId, array $distributorRow): array
    {
        return ['OFFLINE', 'offline'];
    }

    private function normalizeShopIdForPlat(string $shopId, string $platCode): string
    {
        return $shopId;
    }
}
