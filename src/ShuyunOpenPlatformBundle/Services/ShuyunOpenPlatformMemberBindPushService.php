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

final class ShuyunOpenPlatformMemberBindPushService
{
    public const GATEWAY_ACTION_MEMBER_BIND_PUSH = 'shuyun.private.bind.push';

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
     */
    public function pushSingle(
        int $companyId,
        array $distributorRow,
        string $platAccount,
        string $unionId,
        string $weixinOpenId
    ): bool {
        $platAccount = trim($platAccount);
        $unionId = trim($unionId);
        $weixinOpenId = trim($weixinOpenId);
        $partner = trim((string) config('shuyun_open_platform.gateway_partner', 'nnormal'));
        if ($platAccount === '') {
            throw new \InvalidArgumentException('platAccount is required for bind.push.');
        }
        if ($unionId === '') {
            throw new \InvalidArgumentException('unionId is required for bind.push.');
        }
        if ($weixinOpenId === '') {
            throw new \InvalidArgumentException('weixinOpenId is required for bind.push.');
        }
        if ($partner === '') {
            throw new \InvalidArgumentException('partner is required for bind.push (configure shuyun_open_platform.gateway_partner).');
        }

        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable or disabled for bind.push.');
        }

        $shopId = $this->shopIdResolver->resolve($distributorRow);
        [$platCode, $platformHeader] = $this->resolvePlatForMemberBindPush($config, $companyId, $distributorRow);

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
            $client->postJson(self::GATEWAY_ACTION_MEMBER_BIND_PUSH, [[
                'platCode' => $platCode,
                'platAccount' => $platAccount,
                'shopId' => $shopId,
                'unionId' => $unionId,
                'weixinOpenId' => $weixinOpenId,
                'partner' => $partner,
            ]], $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun member bind.push failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun member bind.push failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun member bind.push failed: '.$e->getMessage(), 0, $e);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $distributorRow
     * @return array{0: string, 1: string}
     */
    private function resolvePlatForMemberBindPush(CompanyShuyunOpenPlatformConfig $config, int $companyId, array $distributorRow): array
    {
        return ['OFFLINE', 'offline'];
    }
}
