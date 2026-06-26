<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use DistributionBundle\Repositories\DistributorRepository;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * 积分变更记录查询（GET query）。
 * platCode / platform / shopId 须与 {@see ShuyunOpenPlatformMemberInfoQueryService}（enhance.member）及店务 member.register 一致：恒 OFFLINE / offline，shopId 为分销商 id 原值。
 */
final class ShuyunOpenPlatformLoyaltyPointChangelogSearchService
{
    public const GATEWAY_ACTION_POINT_CHANGELOG_SEARCH = 'shuyun.loyalty.member.point.changelog.search';

    public const LOG_CHANNEL = 'shuyun_open_platform';

    private CompanyShuyunOpenPlatformConfigRepository $configRepository;

    private ShuyunOpenPlatformShopSyncService $shopSyncEligibility;

    private DistributorRepository $distributorRepository;

    private ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver;

    private ClientInterface $httpClient;

    private ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $configRepository,
        ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        DistributorRepository $distributorRepository,
        ShuyunOpenPlatformGatewayShopIdResolver $shopIdResolver,
        ClientInterface $httpClient,
        ShuyunOpenPlatformGatewayClientFactory $gatewayClientFactory
    ) {
        $this->configRepository = $configRepository;
        $this->shopSyncEligibility = $shopSyncEligibility;
        $this->distributorRepository = $distributorRepository;
        $this->shopIdResolver = $shopIdResolver;
        $this->httpClient = $httpClient;
        $this->gatewayClientFactory = $gatewayClientFactory;
    }

    /**
     * @return array{totals: int, pageNum: int, pageSize: int, list: list<array<string, mixed>>}
     */
    public function search(
        int $companyId,
        string $memberUserId,
        string $regShopId,
        int $pageNo = 1,
        int $pageSize = 10
    ): array {
        $memberUserId = trim($memberUserId);
        $regShopId = trim($regShopId);
        if ($memberUserId === '' || $regShopId === '') {
            throw new \InvalidArgumentException('id and shopId are required for point.changelog.search.');
        }
        $distributorId = (int) $regShopId;
        if ($distributorId <= 0) {
            throw new \InvalidArgumentException('reg shop id must be a positive members.reg_distributor (distributor_id) for point.changelog.search.');
        }
        $distributorRow = $this->distributorRepository->getInfo([
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ]);
        if (!is_array($distributorRow) || $distributorRow === []) {
            throw new \RuntimeException('Shuyun point.changelog.search: distributor not found for company_id='.$companyId.' distributor_id='.$distributorId.'.');
        }
        $config = $this->configRepository->findOneByCompanyId($companyId);
        if ($config === null || !$this->shopSyncEligibility->isEligible($config)) {
            throw new \RuntimeException('Shuyun open platform config unavailable for point.changelog.search.');
        }
        $forceOfflinePlat = $this->shouldForceOfflinePlatForChangelog($distributorRow);
        [$platCode, $platformHeader] = $this->resolvePlat($companyId, $forceOfflinePlat);
        $resolvedShopId = $this->shopIdResolver->resolve($distributorRow);
        $shopId = $this->normalizeShopIdForPlat($resolvedShopId, $platCode);
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
            'platCode' => $platCode,
            'id' => $memberUserId,
            'shopId' => $shopId,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        try {
            $result = $client->getQuery(self::GATEWAY_ACTION_POINT_CHANGELOG_SEARCH, $query, $tokenStr, $platformHeader);
        } catch (ShuyunGatewayBusinessException $e) {
            throw new \RuntimeException('Shuyun point.changelog.search failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayHttpException $e) {
            throw new \RuntimeException('Shuyun point.changelog.search failed: '.$e->getMessage(), 0, $e);
        } catch (ShuyunGatewayJsonException $e) {
            throw new \RuntimeException('Shuyun point.changelog.search failed: '.$e->getMessage(), 0, $e);
        }

        return $this->normalizeChangelogData($result->getData());
    }

    private function normalizeShopIdForPlat(string $shopId, string $platCode): string
    {
        return $shopId;
    }

    /**
     * @return array{totals: int, pageNum: int, pageSize: int, list: list<array<string, mixed>>}
     */
    private function normalizeChangelogData(mixed $data): array
    {
        if (!is_array($data)) {
            return ['totals' => 0, 'pageNum' => 1, 'pageSize' => 10, 'list' => []];
        }

        return [
            'totals' => (int) ($data['totals'] ?? 0),
            'pageNum' => (int) ($data['pageNum'] ?? 1),
            'pageSize' => (int) ($data['pageSize'] ?? 10),
            'list' => isset($data['list']) && is_array($data['list']) ? $data['list'] : [],
        ];
    }

    /**
     * 与 {@see \MembersBundle\Services\MemberService::shouldForceOfflinePlatForEnhanceQuery} / {@see ShuyunOpenPlatformMemberInfoQueryService::resolvePlat} 对齐。
     *
     * @param  array<string, mixed>  $distributorRow
     */
    private function shouldForceOfflinePlatForChangelog(array $distributorRow): bool
    {
        return (int) ($distributorRow['distributor_self'] ?? 0) !== 1;
    }

    private function resolvePlat(int $companyId, bool $forceOfflinePlat): array
    {
        return ['OFFLINE', 'offline'];
    }
}
