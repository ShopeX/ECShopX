<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use DistributionBundle\Repositories\DistributorRepository;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyMemberPointChangeService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyPointChangelogSearchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 计划 T-POINT-01～05：积分 changelog.search / point.change 网关薄封装。
 */
class ShuyunOpenPlatformLoyaltyPointGatewayServicesTest extends \TestCase
{
    /** T-POINT-01 */
    public function testChangelogSearchSuccessParsesTotalsAndList(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ZDY1']);

        $body = [
            'code' => 10000,
            'data' => [
                'pageSize' => 10,
                'totals' => 1,
                'pageNum' => 1,
                'list' => [
                    [
                        'created' => '2022-06-15 09:33:13',
                        'source' => 'OTHER',
                        'changePoint' => 10,
                        'desc' => '调试',
                        'sequence' => '8536884500234014727',
                    ],
                ],
            ],
            'msg' => '',
        ];

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepo(1, 2202520, 1),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $out = $svc->search(1, '999999999', '2202520', 1, 10);

        $this->assertSame(1, $out['totals']);
        $this->assertSame(1, $out['pageNum']);
        $this->assertSame(10, $out['pageSize']);
        $this->assertCount(1, $out['list']);
        $this->assertSame(10, $out['list'][0]['changePoint']);
        $this->assertSame('2022-06-15 09:33:13', $out['list'][0]['created']);

        $req = $container[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('shuyun.loyalty.member.point.changelog.search', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $uri = (string) $req->getUri();
        $this->assertStringContainsString('platCode=OFFLINE', $uri);
        $this->assertStringContainsString('id=999999999', $uri);
        $this->assertStringContainsString('shopId=2202520', $uri);
    }

    /** 店务线下店注册：default_plat 为线上码时仍须 OFFLINE + shop，与 enhance.member 一致 */
    public function testChangelogSearchPhysicalStoreForcesOfflinePlatDespiteDefaultPlat(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'NNORMALDTCUAT']);

        $body = [
            'code' => 10000,
            'data' => [
                'pageSize' => 10,
                'totals' => 0,
                'pageNum' => 1,
                'list' => [],
            ],
            'msg' => '',
        ];

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepo(1, 5, 0),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $out = $svc->search(1, '39', '5', 1, 10);
        $this->assertSame(0, $out['totals']);

        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $uri = (string) $req->getUri();
        $this->assertStringContainsString('platCode=OFFLINE', $uri);
        $this->assertStringContainsString('id=39', $uri);
        $this->assertStringContainsString('shopId=5', $uri);
    }

    /** T-POINT-02 */
    public function testChangelogSearchEmptyListTotalsZero(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $body = [
            'code' => 10000,
            'data' => [
                'pageSize' => 10,
                'totals' => 0,
                'pageNum' => 1,
                'list' => [],
            ],
            'msg' => '',
        ];

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepo(1, 1, 0),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $out = $svc->search(1, 'u1', '1');

        $this->assertSame(0, $out['totals']);
        $this->assertSame([], $out['list']);
        $req = $container[0]['request'];
        $uri = (string) $req->getUri();
        $this->assertStringContainsString('platCode=OFFLINE', $uri);
        $this->assertStringContainsString('shopId=1', $uri);
    }

    /** T-POINT-03 */
    public function testChangelogSearchNonSuccessWrapsRuntimeException(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":11008,"data":null,"msg":"accessToken invalid"}'),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyPointChangelogSearchService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            $this->mockDistributorRepo(1, 1, 0),
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        try {
            $svc->search(1, 'u1', '1');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('point.changelog.search failed', $e->getMessage());
            $this->assertInstanceOf(ShuyunGatewayBusinessException::class, $e->getPrevious());
            $this->assertSame(11008, $e->getPrevious()->getBusinessCode());
        }
    }

    /** T-POINT-04 */
    public function testPointChangeSuccessReturnsDataArray(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'ignored-for-body-plat']);

        $body = [
            'code' => 10000,
            'data' => ['currentPoint' => 1100],
            'msg' => '',
        ];

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyMemberPointChangeService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $payload = [
            'platCode' => 'OFFLINE',
            'id' => 'member001',
            'shopId' => '9301',
            'sequence' => '2026-04-02_ORD1_001',
            'created' => '2026-04-02 15:30:00',
            'source' => 'CONSUME',
            'changePoint' => -100,
            'operator' => 'system',
            'desc' => '订单积分抵扣',
        ];

        $data = $svc->change(1, $payload);

        $this->assertSame(['currentPoint' => 1100], $data);

        $req = $container[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('shuyun.loyalty.member.point.change', $req->getHeaderLine('Gateway-Action-Method'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
    }

    /** T-POINT-05 */
    public function testPointChangeInsufficientPointsBusinessError(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);

        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":14001,"data":null,"msg":"积分不足"}'),
        ]));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = new ShuyunOpenPlatformLoyaltyMemberPointChangeService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            $client,
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        try {
            $svc->change(1, [
                'platCode' => 'OFFLINE',
                'id' => 'm1',
                'shopId' => '1',
                'sequence' => 'seq1',
                'created' => '2026-04-02 10:00:00',
                'source' => 'CONSUME',
                'changePoint' => -99999,
                'operator' => '1',
                'desc' => '扣减',
            ]);
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('point.change failed', $e->getMessage());
            $this->assertInstanceOf(ShuyunGatewayBusinessException::class, $e->getPrevious());
            $this->assertSame(14001, $e->getPrevious()->getBusinessCode());
        }
    }

    public function testPointChangeRequiresPlatCodeInBody(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);

        $svc = new ShuyunOpenPlatformLoyaltyMemberPointChangeService(
            $this->mockConfigRepo(),
            $this->mockShopEligibility(true),
            new Client(['handler' => HandlerStack::create(new MockHandler([])), 'base_uri' => 'http://open-api.test/']),
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );

        $this->expectException(\InvalidArgumentException::class);
        $svc->change(1, ['id' => 'x']);
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

    /**
     * @return array<string, mixed>
     */
    private function distributorRowStub(int $companyId, int $distributorId, int $distributorSelf): array
    {
        return [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'distributor_self' => $distributorSelf,
        ];
    }

    private function mockDistributorRepo(int $companyId, int $distributorId, int $distributorSelf): DistributorRepository
    {
        $row = $this->distributorRowStub($companyId, $distributorId, $distributorSelf);
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
