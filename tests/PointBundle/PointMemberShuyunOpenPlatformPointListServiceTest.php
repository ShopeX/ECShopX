<?php

declare(strict_types=1);

namespace Tests\PointBundle;

use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MembersBundle\Services\MemberService;
use PointBundle\Services\PointMemberShuyunOpenPlatformPointListService;
use DistributionBundle\Repositories\DistributorRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyPointChangelogSearchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 计划 T-POINT-06～10：PointBundle 积分流水数云分支（列表服务 + Admin 辅助判定）。
 */
class PointMemberShuyunOpenPlatformPointListServiceTest extends \TestCase
{
    /** T-POINT-06 */
    public function testBuildListFromChangelogMapsRowsAndTotals(): void
    {
        $body = [
            'code' => 10000,
            'data' => [
                'pageSize' => 10,
                'totals' => 2,
                'pageNum' => 1,
                'list' => [
                    [
                        'changePoint' => 10,
                        'created' => '2022-06-15 09:33:13',
                        'source' => 'OTHER',
                        'desc' => '调试',
                        'partnerSequence' => 'O-1',
                        'recordId' => 386331442,
                        'point' => 160,
                    ],
                    [
                        'changePoint' => -5,
                        'created' => '2022-06-16 10:00:00',
                        'source' => 'CONSUME',
                        'desc' => '扣减',
                        'partnerSequence' => '',
                        'recordId' => 386331443,
                        'point' => 155,
                    ],
                ],
            ],
            'msg' => '',
        ];

        $changelog = $this->changelogSearchWithJsonBody($body);
        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $changelog,
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $this->createMock(MemberService::class),
        );

        $out = $svc->buildListFromChangelog(1, 999, 9301, 1, 10, null);

        $this->assertSame(2, $out['total_count']);
        $this->assertCount(2, $out['list']);
        $this->assertSame('999', $out['list'][0]['user_id']);
        $this->assertSame(10, $out['list'][0]['income']);
        $this->assertSame(0, $out['list'][0]['outcome']);
        $this->assertSame('income', $out['list'][0]['outin_type']);
        $this->assertSame(160, $out['list'][0]['s_point']);
        $this->assertSame(5, $out['list'][1]['outcome']);
        $this->assertSame('outcome', $out['list'][1]['outin_type']);
    }

    /** T-POINT-06 — outin_type 过滤 */
    public function testBuildListFromChangelogFiltersByOutinType(): void
    {
        $body = [
            'code' => 10000,
            'data' => [
                'totals' => 2,
                'pageNum' => 1,
                'pageSize' => 10,
                'list' => [
                    ['changePoint' => 10, 'created' => '2022-01-01 00:00:00', 'source' => 'X', 'desc' => '', 'point' => 10],
                    ['changePoint' => -3, 'created' => '2022-01-02 00:00:00', 'source' => 'Y', 'desc' => '', 'point' => 7],
                ],
            ],
            'msg' => '',
        ];

        $changelog = $this->changelogSearchWithJsonBody($body);
        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $changelog,
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $this->createMock(MemberService::class),
        );

        $out = $svc->buildListFromChangelog(1, 1, 1, 1, 10, 'outcome');
        $this->assertCount(1, $out['list']);
        $this->assertSame(3, $out['list'][0]['outcome']);
    }

    /** T-POINT-07 */
    public function testAssertEligibleThrowsWhenNotEligible(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfg = $this->eligibleConfigEntity();
        $repo->method('findOneByCompanyId')->with(7)->willReturn($cfg);

        $sync = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $sync->method('isEligible')->with($cfg)->willReturn(false);

        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $this->deadChangelogSearch(),
            $repo,
            $sync,
            $this->createMock(MemberService::class),
        );

        $this->expectException(ResourceException::class);
        $svc->assertEligibleOrThrow(7);
    }

    /** T-POINT-07 — 无配置行 */
    public function testAssertEligibleThrowsWhenConfigMissing(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn(null);

        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $this->deadChangelogSearch(),
            $repo,
            $this->createMock(ShuyunOpenPlatformShopSyncService::class),
            $this->createMock(MemberService::class),
        );

        $this->expectException(ResourceException::class);
        $svc->assertEligibleOrThrow(1);
    }

    /** T-POINT-08：数云会员能力关闭时，门面返回 false（与 Front 先判此开关再达摩/本地一致）。 */
    public function testTPoint08WhenMemberServiceSaysShuyunOffGateIsFalse(): void
    {
        $member = $this->createMock(MemberService::class);
        $member->method('isShuyunOpenPlatformMemberEnabled')->willReturn(false);

        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $this->deadChangelogSearch(),
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $member,
        );

        $this->assertFalse($svc->isShuyunOpenPlatformMemberEnabled(100));
    }

    /** T-POINT-09 */
    public function testTPoint09WhenShuyunOnIsTrue(): void
    {
        $member = $this->createMock(MemberService::class);
        $member->method('isShuyunOpenPlatformMemberEnabled')->willReturn(true);

        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $this->deadChangelogSearch(),
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $member,
        );

        $this->assertTrue($svc->isShuyunOpenPlatformMemberEnabled(1));
    }

    /** T-POINT-10：后管唯一定位单会员 */
    public function testExtractSingleUserIdScalarAndArray(): void
    {
        $this->assertSame(5, PointMemberShuyunOpenPlatformPointListService::extractSingleUserId(['user_id' => 5]));
        $this->assertSame(5, PointMemberShuyunOpenPlatformPointListService::extractSingleUserId(['user_id' => [5]]));
        $this->assertNull(PointMemberShuyunOpenPlatformPointListService::extractSingleUserId(['user_id' => [5, 6]]));
        $this->assertNull(PointMemberShuyunOpenPlatformPointListService::extractSingleUserId([]));
    }

    /** T-POINT-10：日期筛选时走后管本地流水，不调数云分页 */
    public function testHasPointLogDateRangeFilter(): void
    {
        $this->assertTrue(PointMemberShuyunOpenPlatformPointListService::hasPointLogDateRangeFilter(['created|gte' => 1]));
        $this->assertFalse(PointMemberShuyunOpenPlatformPointListService::hasPointLogDateRangeFilter(['company_id' => 1]));
    }

    public function testSearchFailureWrapsResourceException(): void
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":11008,"data":null,"msg":"accessToken invalid"}'),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $changelog = new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepoForPointList(1),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $svc = new PointMemberShuyunOpenPlatformPointListService(
            $changelog,
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $this->createMock(MemberService::class),
        );

        $this->expectException(ResourceException::class);
        $svc->buildListFromChangelog(1, 1, 1, 1, 10, null);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function changelogSearchWithJsonBody(array $body): ShuyunOpenPlatformLoyaltyPointChangelogSearchService
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        return new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepoForPointList(1),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
    }

    private function deadChangelogSearch(): ShuyunOpenPlatformLoyaltyPointChangelogSearchService
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);
        $client = new Client([
            'handler' => HandlerStack::create(new MockHandler([])),
            'base_uri' => 'http://open-api.test/',
        ]);

        return new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepoEligible(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepoForPointList(1),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
    }

    /**
     * 任意正整数 distributor_id 返回非虚拟店，便于列表单测覆盖多种 reg_distributor。
     */
    private function mockDistributorRepoForPointList(int $companyId): DistributorRepository
    {
        $repo = $this->createMock(DistributorRepository::class);
        $repo->method('getInfo')->willReturnCallback(static function (array $filter) use ($companyId): array {
            $cid = (int) ($filter['company_id'] ?? 0);
            $did = (int) ($filter['distributor_id'] ?? 0);
            if ($cid !== $companyId || $did <= 0) {
                return [];
            }

            return [
                'company_id' => $companyId,
                'distributor_id' => $did,
                'distributor_self' => 0,
            ];
        });

        return $repo;
    }

    private function mockConfigRepoEligible(): CompanyShuyunOpenPlatformConfigRepository
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($this->eligibleConfigEntity());

        return $cfgRepo;
    }

    private function mockShopEligibility(bool $ok): ShuyunOpenPlatformShopSyncService
    {
        $shopEligibility = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shopEligibility->method('isEligible')->willReturn($ok);

        return $shopEligibility;
    }

    private function eligibleConfigEntity(): CompanyShuyunOpenPlatformConfig
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
