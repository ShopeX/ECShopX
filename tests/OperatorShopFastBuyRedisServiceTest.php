<?php

/**
 * store-ops-buy-now-cloud-stock：店务立即购买 Redis 分桶
 * 与小程序 fastbuy TTL/分桶思路一致，key 独立。
 */

use CompanysBundle\Services\OperatorShopFastBuyRedisService;

class OperatorShopFastBuyRedisServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $bucket = [];
        $redis = Mockery::mock();
        $redis->shouldReceive('setex')->andReturnUsing(static function ($key, $ttl, $value) use (&$bucket) {
            if ($ttl !== 600) {
                throw new InvalidArgumentException('shop fastbuy TTL must be 600');
            }
            $bucket[$key] = $value;

            return true;
        });
        $redis->shouldReceive('get')->andReturnUsing(static function ($key) use (&$bucket) {
            return $bucket[$key] ?? false;
        });
        $this->app->instance('redis', $redis);
    }

    public function testBuildKeyUsesShopFastBuyPrefixAndDistinctDimensions(): void
    {
        $svc = new OperatorShopFastBuyRedisService();
        $k1 = $svc->buildKey(1, 2, 3);
        $k2 = $svc->buildKey(1, 2, 4);
        $this->assertStringStartsWith('shop_fastbuy:', $k1);
        $this->assertNotSame($k1, $k2);
    }

    public function testGetRoundTrip(): void
    {
        $svc = new OperatorShopFastBuyRedisService();
        $svc->set(1, 1, 1, ['item_id' => 99, 'num' => 3]);
        $out = $svc->get(1, 1, 1);
        $this->assertSame(99, $out['item_id']);
        $this->assertSame(3, $out['num']);
        $this->assertSame(0, $out['cart_id']);
    }
}
