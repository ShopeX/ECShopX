<?php

use CommunityBundle\Entities\CommunityItems;
use CommunityBundle\Repositories\CommunityItemsRepository;

/**
 * @covers \CommunityBundle\Repositories\CommunityItemsRepository::joinItemsListForStandardStore
 */
final class CommunityItemsStandardStoreJoinTest extends TestCase
{
    private const STORE_DISTRIBUTOR_ID = 306;

    /** @var array<int, mixed> */
    private array $capturedAndWheres = [];

    /**
     * TC-S1 / A-S1：池内 + 门店关联 + 总部默认 SKU onsale → 返回
     * #given 总部默认 SKU onsale、门店按 goods_id 已关联
     * #when 调用 joinItemsListForStandardStore
     * #then 返回该商品；SQL 含 EXISTS distribution_distributor_items（goods_id + distributor_id）
     */
    public function testTcS1ReturnsItemWhenInPoolStoreLinkedDefaultSkuOnsale(): void
    {
        #given
        $fixture = [[
            'item_id' => 1001,
            'goods_id' => 501,
            'company_id' => 1,
            'distributor_id' => 0,
            'is_default' => true,
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
            'item_type' => 'normal',
            'min_delivery_num' => 1,
            'sort' => 0,
        ]];
        $this->bindMockRegistryForStandardStoreJoin($fixture, 1);
        $repository = $this->createRepository();
        $filter = $this->standardFilter();

        #when
        $result = $repository->joinItemsListForStandardStore(
            $filter,
            self::STORE_DISTRIBUTOR_ID,
            1,
            10
        );

        #then
        $this->assertSame(1, $result['total_count'], 'TC-S1: total_count should be 1');
        $this->assertCount(1, $result['list'], 'TC-S1: list should contain the default SKU row');
        $this->assertSame(1001, $result['list'][0]['item_id'], 'TC-S1: should return default SKU item_id');
        $this->assertTrue((bool) $result['list'][0]['is_default'], 'TC-S1: returned row should be default SKU');
        $this->assertStringContainsString(
            'distribution_distributor_items',
            $this->andWhereSql(),
            'TC-S1: SQL must EXISTS-check distribution_distributor_items'
        );
        $this->assertStringContainsString(
            'goods_id',
            $this->andWhereSql(),
            'TC-S1: store link must be goods_id level'
        );
        $this->assertStringContainsString(
            (string) self::STORE_DISTRIBUTOR_ID,
            $this->andWhereSql(),
            'TC-S1: EXISTS must bind store distributor_id'
        );
    }

    /**
     * TC-S1b / A-S1b：门店关联 + 总部默认 SKU instock → 不返回
     * #given 门店已关联但总部默认 SKU 为 instock（非 onsale）
     * #when 调用 joinItemsListForStandardStore（filter 含 approve_status=onsale）
     * #then 不返回；仍要求总部默认 SKU 上架
     */
    public function testTcS1bDoesNotReturnWhenDefaultSkuInstockDespiteStoreLink(): void
    {
        #given
        $this->bindMockRegistryForStandardStoreJoin([], 0);
        $repository = $this->createRepository();
        $filter = $this->standardFilter();

        #when
        $result = $repository->joinItemsListForStandardStore(
            $filter,
            self::STORE_DISTRIBUTOR_ID,
            1,
            10
        );

        #then
        $this->assertSame(0, $result['total_count'], 'TC-S1b: instock default SKU must not be returned');
        $this->assertSame([], $result['list'], 'TC-S1b: list should be empty');
        $this->assertStringContainsString(
            'onsale',
            $this->andWhereSql(),
            'TC-S1b: must still filter approve_status=onsale on HQ default SKU'
        );
    }

    /**
     * TC-S2 / A-S2：默认 SKU onsale + 门店未关联 → 不返回
     * #given 总部默认 SKU onsale 但在池内，门店无 goods_id 关联
     * #when 调用 joinItemsListForStandardStore
     * #then 不返回
     */
    public function testTcS2DoesNotReturnWhenDefaultSkuOnsaleButStoreNotLinked(): void
    {
        #given
        $this->bindMockRegistryForStandardStoreJoin([], 0);
        $repository = $this->createRepository();
        $filter = $this->standardFilter();

        #when
        $result = $repository->joinItemsListForStandardStore(
            $filter,
            self::STORE_DISTRIBUTOR_ID,
            1,
            10
        );

        #then
        $this->assertSame(0, $result['total_count'], 'TC-S2: unlinked store must yield empty result');
        $this->assertSame([], $result['list'], 'TC-S2: list should be empty');
        $this->assertStringContainsString(
            'distribution_distributor_items',
            $this->andWhereSql(),
            'TC-S2: must require store goods_id association via EXISTS'
        );
    }

    /**
     * TC-S3 / A-S3：仅非默认 SKU 关联 → 仍返回默认行
     * #given 总部默认 SKU onsale；门店仅关联同 goods_id 的非默认 SKU
     * #when 调用 joinItemsListForStandardStore
     * #then 仍返回总部默认 SKU 行（goods_id 级关联）
     */
    public function testTcS3ReturnsDefaultRowWhenOnlyNonDefaultSkuLinkedAtStore(): void
    {
        #given
        $fixture = [[
            'item_id' => 1001,
            'goods_id' => 501,
            'company_id' => 1,
            'distributor_id' => 0,
            'is_default' => true,
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
            'item_type' => 'normal',
            'min_delivery_num' => 1,
            'sort' => 0,
        ]];
        $this->bindMockRegistryForStandardStoreJoin($fixture, 1);
        $repository = $this->createRepository();
        $filter = $this->standardFilter();

        #when
        $result = $repository->joinItemsListForStandardStore(
            $filter,
            self::STORE_DISTRIBUTOR_ID,
            1,
            10
        );

        #then
        $this->assertSame(1, $result['total_count'], 'TC-S3: goods_id-level link should include default SKU row');
        $this->assertCount(1, $result['list'], 'TC-S3: list should contain default SKU');
        $this->assertSame(1001, $result['list'][0]['item_id'], 'TC-S3: must return HQ default SKU item_id');
        $this->assertTrue((bool) $result['list'][0]['is_default'], 'TC-S3: returned row must be is_default=true');
        $this->assertStringContainsString(
            'goods_id',
            $this->andWhereSql(),
            'TC-S3: store association must be goods_id level (not item_id only)'
        );
    }

    /**
     * TC-S4 / A-S4：上架 + 关联且 is_can_sale=0 → 仍返回
     * #given 满足 HQ onsale + 门店 goods_id 关联，门店 is_can_sale=0
     * #when 调用 joinItemsListForStandardStore
     * #then 仍返回；不过滤门店 is_can_sale
     */
    public function testTcS4ReturnsItemWhenStoreLinkedWithIsCanSaleZero(): void
    {
        #given
        $fixture = [[
            'item_id' => 1002,
            'goods_id' => 502,
            'company_id' => 1,
            'distributor_id' => 0,
            'is_default' => true,
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
            'item_type' => 'normal',
            'min_delivery_num' => 1,
            'sort' => 0,
        ]];
        $this->bindMockRegistryForStandardStoreJoin($fixture, 1);
        $repository = $this->createRepository();
        $filter = $this->standardFilter();

        #when
        $result = $repository->joinItemsListForStandardStore(
            $filter,
            self::STORE_DISTRIBUTOR_ID,
            1,
            10
        );

        #then
        $this->assertSame(1, $result['total_count'], 'TC-S4: is_can_sale=0 must not exclude linked goods');
        $this->assertCount(1, $result['list'], 'TC-S4: list should contain the item');
        $this->assertStringNotContainsString(
            'is_can_sale',
            $this->andWhereSql(),
            'TC-S4: must not filter on store is_can_sale'
        );
        $this->assertStringNotContainsString(
            'goods_can_sale',
            $this->andWhereSql(),
            'TC-S4: must not filter on store goods_can_sale'
        );
    }

    /**
     * TC-S7 / A-S7：同一 goods_id 多 SKU 门店关联时 total_count 不重复
     * #given 同一 goods_id 在 distribution_distributor_items 有多条 SKU 行
     * #when 调用 joinItemsListForStandardStore
     * #then total_count 按商品计为 1（EXISTS 去重，非 JOIN 膨胀）
     */
    public function testTcS7TotalCountNotDuplicatedWhenMultipleStoreSkusSameGoodsId(): void
    {
        #given
        $fixture = [[
            'item_id' => 1003,
            'goods_id' => 503,
            'company_id' => 1,
            'distributor_id' => 0,
            'is_default' => true,
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
            'item_type' => 'normal',
            'min_delivery_num' => 1,
            'sort' => 0,
        ]];
        $this->bindMockRegistryForStandardStoreJoin($fixture, 1);
        $repository = $this->createRepository();
        $filter = $this->standardFilter();

        #when
        $result = $repository->joinItemsListForStandardStore(
            $filter,
            self::STORE_DISTRIBUTOR_ID,
            1,
            10
        );

        #then
        $this->assertSame(1, $result['total_count'], 'TC-S7: count must not inflate when multiple store SKU rows share goods_id');
        $this->assertCount(1, $result['list'], 'TC-S7: list should have one default SKU row');
        $sql = $this->andWhereSql();
        $this->assertStringContainsString(
            'distribution_distributor_items',
            $sql,
            'TC-S7: must check store association'
        );
        $this->assertMatchesRegularExpression(
            '/EXISTS\s*\(/i',
            $sql,
            'TC-S7: must use EXISTS to avoid count duplication from JOIN'
        );
    }

    private function standardFilter(int $companyId = 1): array
    {
        return [
            'company_id' => $companyId,
            'distributor_id' => 0,
            'is_default' => true,
            'item_type' => 'normal',
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
        ];
    }

    private function createRepository(): CommunityItemsRepository
    {
        $em = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder(\Doctrine\ORM\Mapping\ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new CommunityItemsRepository($em, $metadata);
    }

    private function andWhereSql(): string
    {
        return implode(' ', array_map(
            static function ($expr) {
                return is_object($expr) && method_exists($expr, '__toString')
                    ? (string) $expr
                    : (string) $expr;
            },
            $this->capturedAndWheres
        ));
    }

    private function bindMockRegistryForStandardStoreJoin(array $listFixture, int $totalCount): void
    {
        $this->capturedAndWheres = [];

        $expr = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['eq', 'in', 'like', 'isNotNull', 'literal', 'orX', 'andX', 'notin', 'lt', 'gt'])
            ->getMock();
        $expr->method('eq')->willReturnCallback(function ($field, $value) {
            $this->capturedAndWheres[] = $field . ' = ' . $value;

            return $field . ' = ' . $value;
        });
        $expr->method('in')->willReturnCallback(function ($field, $value) {
            $this->capturedAndWheres[] = $field . ' IN (' . implode(',', (array) $value) . ')';

            return $field . ' IN (...)';
        });
        $expr->method('like')->willReturn('like_expr');
        $expr->method('isNotNull')->willReturnCallback(function ($field) {
            $this->capturedAndWheres[] = $field . ' IS NOT NULL';

            return $field . ' IS NOT NULL';
        });
        $expr->method('literal')->willReturnCallback(function ($value) {
            if (is_object($value) && method_exists($value, '__toString')) {
                return (string) $value;
            }

            return "'" . $value . "'";
        });
        $expr->method('orX')->willReturn('or_x_expr');
        $expr->method('andX')->willReturn('and_x_expr');
        $expr->method('notin')->willReturn('not_in_expr');
        $expr->method('lt')->willReturn('lt_expr');
        $expr->method('gt')->willReturn('gt_expr');

        $executeCount = 0;
        $countResult = $this->getMockBuilder(\stdClass::class)->addMethods(['fetchColumn'])->getMock();
        $countResult->method('fetchColumn')->willReturn($totalCount);

        $listResult = $this->getMockBuilder(\stdClass::class)->addMethods(['fetchAll'])->getMock();
        $listResult->method('fetchAll')->willReturn($listFixture);

        $qb = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'select', 'from', 'leftJoin', 'andWhere', 'expr', 'execute',
                'addOrderBy', 'setFirstResult', 'setMaxResults',
            ])
            ->getMock();
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('andWhere')->willReturnCallback(function ($expr) use ($qb) {
            $this->capturedAndWheres[] = $expr;

            return $qb;
        });
        $qb->method('expr')->willReturn($expr);
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('execute')->willReturnCallback(function () use (&$executeCount, $countResult, $listResult) {
            $executeCount++;

            return $executeCount === 1 ? $countResult : $listResult;
        });

        $conn = $this->getMockBuilder(\stdClass::class)->addMethods(['createQueryBuilder'])->getMock();
        $conn->method('createQueryBuilder')->willReturn($qb);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getConnection'])->getMock();
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);

        $this->app->instance('registry', $mockRegistry);
    }
}
