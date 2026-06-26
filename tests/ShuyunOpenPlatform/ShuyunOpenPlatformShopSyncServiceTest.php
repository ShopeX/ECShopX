<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\DefaultShuyunOpenPlatformShopPlatCodeResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/** @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md A-BODY-*、A-LOG-01 */
class ShuyunOpenPlatformShopSyncServiceTest extends \TestCase
{
    /** @see .tasks/plans/shuyun-open-platform-shop-sync.md A-SYNC-01 */
    public function testSkipsWhenTenantNotEligible(): void
    {
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn(null);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $http, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertFalse($svc->syncShop(1, ['distributor_id' => 9, 'name' => 'N', 'shop_code' => 'S']));
    }

    /** auth_value 空 → 不合格 */
    public function testNotEligibleWhenAuthValueEmpty(): void
    {
        $row = $this->eligibleRow();
        $row->setAuthValue('');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $http, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertFalse($svc->syncShop(1, ['distributor_id' => 9, 'name' => 'N', 'shop_code' => 'S']));
    }

    /** @see .tasks/plans/shuyun-platform-shop-batch-register-api.md A5 / TC-A5 */
    public function testNotEligibleWhenAppSecretEmpty(): void
    {
        $row = $this->eligibleRow();
        $row->setAppSecret('');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->never())->method('request');
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $http, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertFalse($svc->syncShop(1, ['distributor_id' => 9, 'name' => 'N', 'shop_code' => 'S', 'is_valid' => 'true']));
    }

    /** @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md A-BODY-01、A-BODY-02、A-BODY-04；shop_id 取 distributor_id（不用 shop_code） */
    public function testPostsJsonEnvelopeUsesDistributorIdAsShopId(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertTrue($svc->syncShop(1, ['distributor_id' => 99, 'name' => '店A', 'shop_code' => 'SC01']));
        $this->assertNotEmpty($container);
        $req = $container[0]['request'];
        $this->assertGatewayShopBatchMethodAndBody($req, false);
        $payload = json_decode((string) $req->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('av', $payload['tenant_name'] ?? null);
        $this->assertSame('aid', $payload['app_id'] ?? null);
        $this->assertIsArray($payload['shops'] ?? null);
        $shop = $payload['shops'][0];
        $this->assertSame('99-off', $shop['shop_id'] ?? null);
        $this->assertSame('店A', $shop['shop_name'] ?? null);
        $this->assertSame('OFFLINE', $shop['plat_code'] ?? null);
        $this->assertArrayNotHasKey('shopCode', $payload);
        $this->assertSame('tok', $req->getHeaderLine('Gateway-Access-Token'));
        $this->assertSame('offline', $req->getHeaderLine('platform'));
    }

    /** @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md A-BODY-03 */
    public function testShopIdUsesDistributorIdWhenShopCodeEmpty(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertTrue($svc->syncShop(1, ['distributor_id' => 42, 'name' => 'B', 'shop_code' => '']));
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('42-off', $payload['shops'][0]['shop_id'] ?? null);
    }

    /** @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md A-LOG-01 */
    public function testBusinessFailureReturnsFalseWithoutThrowing(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":40001,"data":null,"msg":"bad"}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));
        $this->assertFalse($svc->syncShop(1, ['distributor_id' => 1, 'name' => 'N', 'shop_code' => 'x']));
        $this->assertCount(1, $container);
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
    }

    public function testBuildsOfflineShopWithEnabledStatusWhenLifecycleEnabled(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $ok = $svc->syncShop(1, ['distributor_id' => 88, 'name' => '门店A', 'shop_code' => 'S88', 'is_valid' => 'true']);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $req = $container[0]['request'];
        $this->assertSame('offline', $req->getHeaderLine('platform'));
        $payload = json_decode((string) $req->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('OFFLINE', $payload['shops'][0]['plat_code'] ?? null);
        $this->assertSame('1', $payload['shops'][0]['status'] ?? null);
        $this->assertSame('88-off', $payload['shops'][0]['shop_id'] ?? null);
        $this->assertSame('门店A', $payload['shops'][0]['shop_name'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($req, false);
    }

    public function testBuildsOfflineShopWithClosedStatusWhenIsValidClosed(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $ok = $svc->syncShop(1, ['distributor_id' => 91, 'name' => '闭店', 'shop_code' => 'S91', 'is_valid' => 'closed'], ['OFFLINE']);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2', $payload['shops'][0]['status'] ?? null);
        $this->assertSame('闭店', $payload['shops'][0]['shop_name'] ?? null);
        $this->assertSame('91-off', $payload['shops'][0]['shop_id'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
    }

    public function testBuildsOfflineShopWithDeletedStatusWhenIsValidDelete(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $ok = $svc->syncShop(1, ['distributor_id' => 92, 'name' => '废弃', 'shop_code' => 'S92', 'is_valid' => 'delete'], ['OFFLINE']);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('0', $payload['shops'][0]['status'] ?? null);
        $this->assertSame('废弃', $payload['shops'][0]['shop_name'] ?? null);
        $this->assertSame('92-off', $payload['shops'][0]['shop_id'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
    }

    /** 禁用（is_valid false）：仅一次 OFFLINE shop.batch.register，不调 platform.shop.batch.register */
    public function testBuildsOfflineOnlyWhenLifecycleDisabled(): void
    {
        #given
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => 'ecshop']);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        #when
        $ok = $svc->syncShop(1, ['distributor_id' => 89, 'name' => '门店B', 'shop_code' => 'S89', 'is_valid' => 'false']);

        #then
        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $req0 = $container[0]['request'];
        $this->assertSame('offline', $req0->getHeaderLine('platform'));
        $payload0 = json_decode((string) $req0->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $payload0['shops'] ?? []);
        $this->assertSame('OFFLINE', $payload0['shops'][0]['plat_code'] ?? null);
        $this->assertSame('1', $payload0['shops'][0]['status'] ?? null);
        $this->assertSame('89-off', $payload0['shops'][0]['shop_id'] ?? null);
        $this->assertSame('门店B', $payload0['shops'][0]['shop_name'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($req0, false);
    }

    /** 禁用 + 显式 targetPlatCodes：跳过非 OFFLINE，仅一条 OFFLINE status=1 */
    public function testDisabledWithTargetPlatCodesSkipsOnlinePlat(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $ok = $svc->syncShop(1, ['distributor_id' => 93, 'name' => '禁', 'shop_code' => 'S93', 'is_valid' => 'false'], ['OFFLINE', 'PL']);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $payload['shops'] ?? []);
        $this->assertSame('OFFLINE', $payload['shops'][0]['plat_code'] ?? null);
        $this->assertSame('1', $payload['shops'][0]['status'] ?? null);
        $this->assertSame('禁', $payload['shops'][0]['shop_name'] ?? null);
        $this->assertSame('93-off', $payload['shops'][0]['shop_id'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
    }

    /** 虚拟店默认 target：仅 OFFLINE 一次请求 */
    public function testVirtualShopSyncsOfflineOnlyByDefault(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        $row = $this->eligibleRow();
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $ok = $svc->syncShop(1, [
            'distributor_id' => 101,
            'name' => '虚拟店',
            'shop_code' => 'V101',
            'is_valid' => 'true',
            'distributor_self' => '1',
        ]);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $this->assertSame('offline', strtolower($container[0]['request']->getHeaderLine('platform')));
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('OFFLINE', $payload['shops'][0]['plat_code'] ?? null);
        $this->assertSame('1', $payload['shops'][0]['status'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
    }

    /** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-PLAT-04 */
    public function testEnabledUsesOfflineOnlyWhenCustomPlatUnresolved(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '']);
        $row = $this->eligibleRow();
        $row->setPlatCode('');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $ok = $svc->syncShop(1, ['distributor_id' => 90, 'name' => '仅线下', 'shop_code' => 'S90', 'is_valid' => 'true']);

        $this->assertTrue($ok);
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $payload['shops'] ?? []);
        $this->assertSame('OFFLINE', $payload['shops'][0]['plat_code'] ?? null);
        $this->assertSame('90-off', $payload['shops'][0]['shop_id'] ?? null);
        $this->assertSame('仅线下', $payload['shops'][0]['shop_name'] ?? null);
        $this->assertGatewayShopBatchMethodAndBody($container[0]['request'], false);
    }

    /** OFFLINE 店铺名已带后缀时不重复拼接 */
    public function testOfflineShopNameSuffixNotDuplicated(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '']);
        $row = $this->eligibleRow();
        $row->setPlatCode('');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $container = [];
        $mock = new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);
        $svc = new ShuyunOpenPlatformShopSyncService($repo, new DefaultShuyunOpenPlatformShopPlatCodeResolver(), $client, new ShuyunOpenPlatformGatewayShopIdResolver(), new ShuyunOpenPlatformGatewayClientFactory(null));

        $this->assertTrue($svc->syncShop(1, ['distributor_id' => 90, 'name' => '某店-线下', 'shop_code' => 'S90', 'is_valid' => 'true']));
        $payload = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('某店-线下', $payload['shops'][0]['shop_name'] ?? null);
        $this->assertSame('90-off', $payload['shops'][0]['shop_id'] ?? null);
    }

    /**
     * @param  bool  $expectPlatformShopBatchApi  true → shuyun.base.platform.shop.batch.register + body.app_secret
     */
    private function assertGatewayShopBatchMethodAndBody(RequestInterface $req, bool $expectPlatformShopBatchApi): void
    {
        $line = $req->getHeaderLine('Gateway-Action-Method');
        $payload = json_decode((string) $req->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if ($expectPlatformShopBatchApi) {
            $this->assertSame(ShuyunOpenPlatformShopSyncService::GATEWAY_ACTION_PLATFORM_SHOP_BATCH_REGISTER, $line);
            $this->assertSame('sec', $payload['app_secret'] ?? null);
        } else {
            $this->assertSame(ShuyunOpenPlatformShopSyncService::GATEWAY_ACTION_SHOP_BATCH_REGISTER, $line);
            $this->assertArrayNotHasKey('app_secret', $payload);
        }
    }

    private function eligibleRow(): CompanyShuyunOpenPlatformConfig
    {
        $e = new CompanyShuyunOpenPlatformConfig();
        $e->setCompanyId(1);
        $e->setAuthValue('av');
        $e->setPlatCode('pl');
        $e->setAppId('aid');
        $e->setAppSecret('sec');
        $e->setAccessToken('tok');
        $e->setIsEnabled(1);

        return $e;
    }
}
