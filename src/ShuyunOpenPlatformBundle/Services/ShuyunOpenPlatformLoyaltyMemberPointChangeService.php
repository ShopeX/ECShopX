<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 会员积分变更 shuyun.loyalty.member.point.change（POST JSON）。
 * 请求体字段以 docs 整理表 §5.1 为准；调用方负责 sequence/source/changePoint/desc/operator/created 等。
 */
final class ShuyunOpenPlatformLoyaltyMemberPointChangeService
{
    public const GATEWAY_ACTION_MEMBER_POINT_CHANGE = 'shuyun.loyalty.member.point.change';

    public const LOG_CHANNEL = 'shuyun_open_platform';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->shopSyncEligibility = $shopSyncEligibility;
        $this->httpClient = $httpClient;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * @param  array<string, mixed>  $body  须含 platCode、id、shopId、sequence、created、source、changePoint、operator、desc 等
     * @return array<string, mixed>
     */
    public function change(int $companyId, array $body): array
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable for point.change.');
        }
        $platCodeBody = isset($body['platCode']) ? trim((string) $body['platCode']) : '';
        if ($platCodeBody === '') {
            throw new \InvalidArgumentException('platCode is required in point.change body.');
        }
        $platformHeader = strtolower($platCodeBody);
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
            $result = $client->postJson(self::GATEWAY_ACTION_MEMBER_POINT_CHANGE, $body, $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun point.change failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun point.change failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun point.change failed: '.$e->getMessage(), 0, $e);
        }

        $data = $result->getData();

        return is_array($data) ? $data : [];
    }
}
