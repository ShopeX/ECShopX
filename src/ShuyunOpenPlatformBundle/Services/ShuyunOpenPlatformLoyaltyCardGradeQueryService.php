<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 查询数云忠诚度会员卡等级（shuyun.loyalty.card.grade.query）。
 */
final class ShuyunOpenPlatformLoyaltyCardGradeQueryService
{
    public const GATEWAY_ACTION_LOYALTY_CARD_GRADE_QUERY = 'shuyun.loyalty.card.grade.query';

    public const LOG_CHANNEL = 'shuyun_open_platform';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver $shopIdResolver;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver $shopIdResolver,
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
     * @param  array<string, mixed>  $virtualDistributorRow  虚拟店 distribution_distributor 行
     * @return array<string, mixed>|null
     */
    public function queryGradeCard(int $companyId, array $virtualDistributorRow): ?array
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            return null;
        }

        try {
            $shopId = $this->shopIdResolver->resolveShopIdQueryValue($virtualDistributorRow);
        } catch (\InvalidArgumentException $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun loyalty card grade query: invalid virtual distributor shopId source.', [
                'company_id' => $companyId,
                'distributor_id' => $virtualDistributorRow['distributor_id'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        [$platCode, $platformHeader] = $this->resolvePlatForGradeQuery($config, $companyId, $virtualDistributorRow);
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

        $result = $client->getQuery(self::GATEWAY_ACTION_LOYALTY_CARD_GRADE_QUERY, [
            'shopId' => $shopId,
            'platCode' => $platCode,
        ], $tokenStr, $platformHeader);

        $data = $result->getData();

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $virtualDistributorRow
     * @return array{0: string, 1: string}
     */
    private function resolvePlatForGradeQuery(CompanyShuyunOpenPlatformConfig $config, int $companyId, array $virtualDistributorRow): array
    {
        return ['OFFLINE', 'offline'];
    }
}
