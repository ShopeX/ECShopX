<?php

declare(strict_types=1);

namespace Tests\OpenapiBundle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MembersBundle\Services\MemberService;
use OpenapiBundle\Services\Member\MemberPointService;
use DistributionBundle\Repositories\DistributorRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyPointChangelogSearchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 计划 T-POINT-11：OpenAPI MemberPointService::logList 与小程序/后管同一数云流水决策（D-OAPI-01）。
 */
class MemberPointServiceLogListShuyunSourceTest extends \TestCase
{
    /** T-POINT-11 */
    public function testLogListWhenShuyunEnabledUsesChangelogSearch(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $body = [
            'code' => 10000,
            'data' => [
                'pageSize' => 10,
                'totals' => 1,
                'pageNum' => 1,
                'list' => [
                    [
                        'changePoint' => 5,
                        'created' => '2022-06-15 09:33:13',
                        'source' => 'MARKET',
                        'desc' => '注册送分',
                        'partnerSequence' => '',
                        'recordId' => 386331442,
                        'point' => 105,
                        'operator' => 'system',
                    ],
                ],
            ],
            'msg' => '',
        ];

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $cfgRepo = $this->mockConfigRepo();
        $shopSync = $this->mockShopEligibility(true);

        $changelog = new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $cfgRepo,
            $shopSync,
            $this->mockDistributorRepoForLogList(1, 2202520, 1),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $this->app->instance(ShuyunOpenPlatformLoyaltyPointChangelogSearchService::class, $changelog);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $cfgRepo);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $shopSync);

        $coreMember = $this->createMock(MemberService::class);
        $coreMember->method('isShuyunOpenPlatformMemberEnabled')->willReturn(true);
        $coreMember->method('getMemberInfo')->willReturn([
            'user_id' => 90001,
            'company_id' => 1,
            'reg_distributor' => 2202520,
            'mobile' => '13000000000',
        ]);
        $this->app->instance(MemberService::class, $coreMember);

        $svc = new MemberPointService();
        $result = $svc->logList(['company_id' => 1, 'user_id' => 90001], 1, 20, ['id' => 'DESC']);

        $this->assertSame(1, $result['total_count']);
        $this->assertCount(1, $result['list']);
        $this->assertSame(90001, (int) $result['list'][0]['user_id']);
        $this->assertSame(5, (int) $result['list'][0]['income']);
        $this->assertSame(0, (int) $result['list'][0]['outcome']);
        $this->assertSame('注册送分', (string) $result['list'][0]['point_desc']);
        $this->assertSame('system', (string) $result['list'][0]['operater']);
    }

    private function mockDistributorRepoForLogList(int $companyId, int $distributorId, int $distributorSelf): DistributorRepository
    {
        $row = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'distributor_self' => $distributorSelf,
        ];
        $repo = $this->createMock(DistributorRepository::class);
        $repo->method('getInfo')->willReturnCallback(static function (array $filter) use ($companyId, $distributorId, $row): array {
            if ((int) ($filter['company_id'] ?? 0) === $companyId
                && (int) ($filter['distributor_id'] ?? 0) === $distributorId) {
                return $row;
            }

            return [];
        });

        return $repo;
    }

    private function mockConfigRepo(): CompanyShuyunOpenPlatformConfigRepository
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfig());

        return $cfgRepo;
    }

    private function mockShopEligibility(bool $ok): ShuyunOpenPlatformShopSyncService
    {
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn($ok);

        return $shopEligibility;
    }

    private function eligibleConfig(): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(1);
        $e->setAuthValue('av');
        $e->setAppId('aid');
        $e->setAppSecret('sec');
        $e->setAccessToken('tok');
        $e->setIsEnabled(1);

        return $e;
    }
}
