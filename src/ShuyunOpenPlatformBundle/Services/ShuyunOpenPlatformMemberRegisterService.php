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

final class ShuyunOpenPlatformMemberRegisterService
{
    public const GATEWAY_ACTION_MEMBER_REGISTER = 'shuyun.loyalty.member.register';

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
     * @param array<string, mixed> $distributorRow
     */
    public function registerSingle(
        int $companyId,
        array $distributorRow,
        string $memberId,
        string $mobile,
        ?string $unionId = null,
        ?string $name = null,
        bool $forceOfflinePlat = false
    ): bool {
        $memberId = trim($memberId);
        $mobile = trim($mobile);
        if ($memberId === '') {
            throw new \InvalidArgumentException('id is required for member.register.');
        }
        if ($mobile === '') {
            throw new \InvalidArgumentException('mobile is required for member.register.');
        }

        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable or disabled for member.register.');
        }

        [$platCode, $platformHeader] = $this->resolvePlatForMemberRegister($config, $companyId, $distributorRow, $forceOfflinePlat);
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

        // body.platCode：固定大写 OFFLINE；请求头 platform 见 postJson 第四参，由网关客户端统一转小写
        $payload = [
            'id' => $memberId,
            'platCode' => strtoupper(trim($platCode)),
            'shopId' => $shopId,
            'mobile' => $mobile,
        ];
        $omid = trim((string) ($unionId ?? ''));
        if ($omid !== '') {
            $payload['omid'] = $omid;
        }
        $nameStr = trim((string) ($name ?? ''));
        if ($nameStr !== '') {
            $payload['name'] = $nameStr;
        }

        try {
            $client->postJson(self::GATEWAY_ACTION_MEMBER_REGISTER, $payload, $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun member.register failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun member.register failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun member.register failed: '.$e->getMessage(), 0, $e);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $distributorRow
     *
     * @return array{0: string, 1: string} [0]=body platCode（大写 OFFLINE）；[1]=公共头 `platform`（`offline`）
     */
    private function resolvePlatForMemberRegister(
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
