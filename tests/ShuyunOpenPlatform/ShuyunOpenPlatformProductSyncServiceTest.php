<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GoodsBundle\Repositories\ItemRelAttributesRepository;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayClientFactory;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformProductSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-PROD-03、A-PROD-04、A-PROD-05 */
class ShuyunOpenPlatformProductSyncServiceTest extends \TestCase
{
    public function testResolvePrimaryCategoryIdPrefersLevelThreeRelationOverLowerLevelAndItemCategory(): void
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $http = $this->createMock(ClientInterface::class);
        $rel = $this->createMock(\GoodsBundle\Repositories\ItemsRelCatsRepository::class);
        $rel->expects($this->once())
            ->method('lists')
            ->with(['company_id' => 88, 'item_id' => 2001], ['category_id' => 'ASC'], 10000, 1)
            ->willReturn([
                'list' => [
                    ['category_id' => 11],
                    ['category_id' => 33],
                ],
                'total_count' => 2,
            ]);
        $itemsCategory = $this->createMock(ItemsCategoryRepository::class);
        $itemsCategory->expects($this->exactly(2))
            ->method('getInfo')
            ->willReturnCallback(static function (array $filter): array {
                if ((int) ($filter['category_id'] ?? 0) === 11) {
                    return ['category_id' => 11, 'company_id' => 88, 'category_level' => 2];
                }

                if ((int) ($filter['category_id'] ?? 0) === 33) {
                    return ['category_id' => 33, 'company_id' => 88, 'category_level' => 3];
                }

                return [];
            });

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $http, $rel, $itemsCategory);
        $ref = new \ReflectionClass(ShuyunOpenPlatformProductSyncService::class);
        $method = $ref->getMethod('resolvePrimaryCategoryId');
        $method->setAccessible(true);

        $actual = $method->invoke($svc, 88, 2001, ['item_category' => '99']);

        $this->assertSame('33', $actual);
    }

    /** 虚拟店 skus[].status 映射；实体店仍用 is_can_sale */
    public function testMapItemApproveStatusToSkuOnlineIntOnlyOnsaleIsOne(): void
    {
        $ref = new \ReflectionClass(ShuyunOpenPlatformProductSyncService::class);
        /** @var ShuyunOpenPlatformProductSyncService $svc */
        $svc = $ref->newInstanceWithoutConstructor();
        $m = $ref->getMethod('mapItemApproveStatusToSkuOnlineInt');
        $m->setAccessible(true);

        $this->assertSame(1, $m->invoke($svc, 'onsale'));
        $this->assertSame(0, $m->invoke($svc, 'only_show'));
        $this->assertSame(0, $m->invoke($svc, 'offline_sale'));
        $this->assertSame(0, $m->invoke($svc, 'instock'));
        $this->assertSame(0, $m->invoke($svc, ''));
        $this->assertSame(1, $m->invoke($svc, '  onsale  '));
    }

    /** A-PROD-03：50 条商品 OFFLINE 单次 POST（body 长度 50） */
    public function testFiftyProductsSinglePostPerPlatform(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.timeout' => 5.0]);
        config(['shuyun_open_platform.default_plat_code' => '']);

        $row = $this->eligibleConfig();
        $row->setPlatCode('MYPLAT');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);

        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $products = [];
        for ($i = 1; $i <= 50; ++$i) {
            $products[] = $this->minimalProduct((string) $i);
        }

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $client);
        $this->assertTrue($svc->syncValidatedProductPayloads(1, $products));
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
        $body = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(50, $body);
        $this->assertSame('10', $body[0]['category_id'] ?? null);
        $this->assertSame('999', $body[0]['shop_id'] ?? null);
    }

    /** A-PROD-04：51 条 → 每 platform 2 次 POST（50+1） */
    public function testFiftyOneProductsTwoPostsPerPlatform(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => '']);

        $row = $this->eligibleConfig();
        $row->setPlatCode('X');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $products = [];
        for ($i = 1; $i <= 51; ++$i) {
            $products[] = $this->minimalProduct((string) $i);
        }

        $responses = array_fill(0, 2, new Response(200, [], '{"code":10000,"data":null,"msg":""}'));
        $container = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $client);
        $this->assertTrue($svc->syncValidatedProductPayloads(1, $products));
        $this->assertCount(2, $container);
        $bFirst = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $bSecond = json_decode((string) $container[1]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(50, $bFirst);
        $this->assertCount(1, $bSecond);
    }

    /** 虚拟店铺（distributor_self）：仅推自定义 platform，不推 offline，body 不加 offline 后缀 */
    public function testVirtualDistributorPostsCustomPlatOnly(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => '']);

        $row = $this->eligibleConfig();
        $row->setPlatCode('MYPLAT');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $client);
        $this->assertTrue($svc->syncValidatedProductPayloads(1, [$this->minimalProduct('1')], ['distributor_self' => 1]));
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
        $body = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('10', $body[0]['category_id'] ?? null);
        $this->assertSame('999', $body[0]['shop_id'] ?? null);
    }

    /** 虚拟店铺：与实体店相同，仅推 OFFLINE */
    public function testVirtualDistributorUsesOfflinePlatform(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => 'NNORMALDTC']);

        $row = $this->eligibleConfig();
        $row->setPlatCode('');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $client);
        $this->assertTrue($svc->syncValidatedProductPayloads(1, [$this->minimalProduct('1')], ['distributor_self' => 1]));
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
    }

    /** A-PLAT-04：自定义 plat 空则仅 offline */
    public function testOnlyOfflineWhenTenantPlatEmpty(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => '']);

        $row = $this->eligibleConfig();
        $row->setPlatCode('');
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $client);
        $this->assertTrue($svc->syncValidatedProductPayloads(1, [$this->minimalProduct('1')]));
        $this->assertCount(1, $container);
        $this->assertSame('offline', $container[0]['request']->getHeaderLine('platform'));
        $body = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('10', $body[0]['category_id'] ?? null);
        $this->assertSame('999', $body[0]['shop_id'] ?? null);
    }

    /** 数云 product_id 优先 items.goods_id */
    public function testBuildProductBodyUsesGoodsIdAsProductId(): void
    {
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $http = $this->createMock(ClientInterface::class);
        $rel = $this->createMock(\GoodsBundle\Repositories\ItemsRelCatsRepository::class);
        $rel->method('lists')->willReturn([
            'list' => [['category_id' => 33]],
            'total_count' => 1,
        ]);
        $itemsCategory = $this->createMock(ItemsCategoryRepository::class);
        $itemsCategory->method('getInfo')->willReturn([
            'category_id' => 33,
            'company_id' => 88,
            'category_level' => 3,
        ]);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $http, $rel, $itemsCategory);
        $ref = new \ReflectionClass(ShuyunOpenPlatformProductSyncService::class);
        $method = $ref->getMethod('buildProductBodyFromVariants');
        $method->setAccessible(true);

        $body = $method->invoke(
            $svc,
            88,
            15,
            5016,
            [[
                'item_id' => 5016,
                'default_item_id' => 5016,
                'goods_id' => 5000,
                'item_name' => '测试商品',
                'approve_status' => 'onsale',
                'price' => 100,
                'updated' => time(),
                'is_gift' => false,
            ]],
            [5016 => ['item_id' => 5016, 'price' => 100, 'is_can_sale' => true]],
            ['distributor_id' => 15, 'distributor_self' => 0],
        );

        $this->assertIsArray($body);
        $this->assertSame('5000', $body['product_id'] ?? null);
    }

    /** A-PROD-05：无效行跳过，有效行仍 POST */
    public function testSkipsInvalidRowsAndPostsValid(): void
    {
        config(['shuyun_open_platform.base_uri' => 'http://open-api.test']);
        config(['shuyun_open_platform.default_plat_code' => '']);

        $row = $this->eligibleConfig();
        $cfgRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $cfgRepo->method('findOneByCompanyId')->willReturn($row);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $bad = $this->minimalProduct('2');
        unset($bad['category_id']);

        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"code":10000,"data":null,"msg":""}'),
        ]));
        $stack->push(Middleware::history($container));
        $client = new Client(['handler' => $stack, 'base_uri' => 'http://open-api.test/']);

        $svc = $this->makeServiceWithDeps($cfgRepo, $shop, $client);
        $this->assertTrue($svc->syncValidatedProductPayloads(1, [$bad, $this->minimalProduct('9')]));
        $this->assertCount(1, $container);
        $body = json_decode((string) $container[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $body);
        $this->assertSame('9', $body[0]['product_id'] ?? null);
        $this->assertSame('10', $body[0]['category_id'] ?? null);
    }

    private function makeServiceWithDeps(
        CompanyShuyunOpenPlatformConfigRepository $cfgRepo,
        ShuyunOpenPlatformShopSyncService $shop,
        ClientInterface $http,
        ?\GoodsBundle\Repositories\ItemsRelCatsRepository $rel = null,
        ?ItemsCategoryRepository $itemsCategory = null,
    ): ShuyunOpenPlatformProductSyncService {
        $items = $this->createMock(\GoodsBundle\Repositories\ItemsRepository::class);
        $dist = $this->createMock(\DistributionBundle\Repositories\DistributorRepository::class);
        $di = $this->createMock(\DistributionBundle\Repositories\DistributorItemsRepository::class);
        $rel = $rel ?? $this->createMock(\GoodsBundle\Repositories\ItemsRelCatsRepository::class);
        $itemsCategory = $itemsCategory ?? $this->createMock(ItemsCategoryRepository::class);
        $itemRel = $this->createMock(ItemRelAttributesRepository::class);
        $itemRel->method('lists')->willReturn(['list' => [], 'total_count' => 0]);

        return new ShuyunOpenPlatformProductSyncService(
            $cfgRepo,
            $items,
            $dist,
            $di,
            $rel,
            $itemsCategory,
            $itemRel,
            $shop,
            $http,
            new ShuyunOpenPlatformGatewayShopIdResolver(),
            new ShuyunOpenPlatformGatewayClientFactory(null),
        );
    }

    /** @return array<string, mixed> */
    private function minimalProduct(string $productId): array
    {
        return [
            'shop_id' => '999',
            'product_id' => $productId,
            'product_name' => 'n'.$productId,
            'category_id' => '10',
            'modified' => '2020-01-01 00:00:00',
            'status' => 'SY_ONLINE',
            'price' => 1.23,
            'skus' => [
                ['sku_id' => 's'.$productId, 'sku_detail' => ShuyunOpenPlatformProductSyncService::SKU_DETAIL_SINGLE_SPEC],
            ],
        ];
    }

    private function eligibleConfig(): CompanyShuyunOpenPlatformConfig
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
