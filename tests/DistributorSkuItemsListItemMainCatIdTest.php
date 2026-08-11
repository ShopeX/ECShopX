<?php

use DistributionBundle\Services\DistributorItemsService;
use GoodsBundle\Services\MultiLang\MultiLangService;

/**
 * @covers \DistributionBundle\Services\DistributorItemsService::getDistributorSkuItemsList
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DistributorSkuItemsListItemMainCatIdTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-01 / AS-01：仅有 item_category 时映射为 item_main_cat_id。
     * #given list fixture 仅含 item_category='100'，无 item_main_cat_id
     * #when 调用 getDistributorSkuItemsList
     * #then 返回行 item_main_cat_id === '100'
     */
    public function testTc01MapsItemCategoryToItemMainCatId(): void
    {
        #given
        $this->bindMultiLangIdentity();
        $fixture = [['item_id' => 1, 'item_category' => '100']];
        $this->bindMockRegistryWithQueryBuilder($fixture);

        $service = new DistributorItemsService();

        #when
        $result = $service->getDistributorSkuItemsList([
            'company_id' => 1,
            'distributor_id' => 306,
        ]);

        #then
        $this->assertArrayHasKey('item_main_cat_id', $result['list'][0], 'TC-01: row must expose item_main_cat_id');
        $this->assertSame('100', $result['list'][0]['item_main_cat_id'], 'TC-01: item_main_cat_id should equal item_category');
    }

    /**
     * TC-02 / AS-02：无 category 字段时 item_main_cat_id 为空字符串。
     * #given list fixture 无 item_category 与 item_main_cat_id
     * #when 调用 getDistributorSkuItemsList
     * #then 返回行 item_main_cat_id === ''
     */
    public function testTc02MissingCategoryYieldsEmptyItemMainCatId(): void
    {
        #given
        $this->bindMultiLangIdentity();
        $fixture = [['item_id' => 2]];
        $this->bindMockRegistryWithQueryBuilder($fixture);

        $service = new DistributorItemsService();

        #when
        $result = $service->getDistributorSkuItemsList([
            'company_id' => 1,
            'distributor_id' => 306,
        ]);

        #then
        $this->assertArrayHasKey('item_main_cat_id', $result['list'][0], 'TC-02: row must expose item_main_cat_id');
        $this->assertSame('', $result['list'][0]['item_main_cat_id'], 'TC-02: missing category should map to empty string');
    }

    /**
     * TC-03 / AS-02：item_category 为空字符串时 item_main_cat_id 为空字符串。
     * #given list fixture item_category=''
     * #when 调用 getDistributorSkuItemsList
     * #then 返回行 item_main_cat_id === ''
     */
    public function testTc03EmptyCategoryYieldsEmptyItemMainCatId(): void
    {
        #given
        $this->bindMultiLangIdentity();
        $fixture = [['item_id' => 3, 'item_category' => '']];
        $this->bindMockRegistryWithQueryBuilder($fixture);

        $service = new DistributorItemsService();

        #when
        $result = $service->getDistributorSkuItemsList([
            'company_id' => 1,
            'distributor_id' => 306,
        ]);

        #then
        $this->assertArrayHasKey('item_main_cat_id', $result['list'][0], 'TC-03: row must expose item_main_cat_id');
        $this->assertSame('', $result['list'][0]['item_main_cat_id'], 'TC-03: empty category should map to empty string');
    }

    /**
     * TC-04 / AS-03：同时存在 item_main_cat_id 与 item_category 时以 category 为准。
     * #given list fixture 含 item_main_cat_id='9' 与 item_category='100'
     * #when 调用 getDistributorSkuItemsList
     * #then 返回行 item_main_cat_id === '100'
     */
    public function testTc04ItemCategoryOverridesExistingItemMainCatId(): void
    {
        #given
        $this->bindMultiLangIdentity();
        $fixture = [['item_id' => 4, 'item_main_cat_id' => '9', 'item_category' => '100']];
        $this->bindMockRegistryWithQueryBuilder($fixture);

        $service = new DistributorItemsService();

        #when
        $result = $service->getDistributorSkuItemsList([
            'company_id' => 1,
            'distributor_id' => 306,
        ]);

        #then
        $this->assertArrayHasKey('item_main_cat_id', $result['list'][0], 'TC-04: row must expose item_main_cat_id');
        $this->assertSame('100', $result['list'][0]['item_main_cat_id'], 'TC-04: item_category should override existing item_main_cat_id');
    }

    /**
     * TC-05 / AS-04：getDistributorSkuReplace 早退原样返回时仍须映射 item_main_cat_id。
     * #given mock getDistributorSkuReplace 原样返回 list
     * #when 调用 getDistributorSkuItemsList
     * #then 返回行仍含 item_main_cat_id key
     */
    public function testTc05MappingAppliesWhenSkuReplaceReturnsListUnchanged(): void
    {
        #given
        $this->bindMultiLangIdentity();
        $fixture = [['item_id' => 5, 'item_category' => '100']];
        $this->bindMockRegistryWithQueryBuilder($fixture);

        $service = $this->getMockBuilder(DistributorItemsService::class)
            ->onlyMethods(['getDistributorSkuReplace'])
            ->getMock();
        $service->method('getDistributorSkuReplace')->willReturnCallback(
            function ($companyId, $distributorId, $skuList, $isReplaceApprove) {
                return $skuList;
            }
        );

        #when
        $result = $service->getDistributorSkuItemsList([
            'company_id' => 1,
            'distributor_id' => 306,
        ]);

        #then
        $this->assertArrayHasKey('item_main_cat_id', $result['list'][0], 'TC-05: mapping must apply even when replace returns list unchanged');
        $this->assertSame('100', $result['list'][0]['item_main_cat_id'], 'TC-05: item_main_cat_id should be mapped from item_category');
    }

    private function bindMultiLangIdentity(): void
    {
        $mock = \Mockery::mock('overload:' . MultiLangService::class);
        $mock->shouldReceive('getListAddLang')->andReturnUsing(function (array $dataList) {
            return $dataList;
        });
    }

    private function bindMockRegistryWithQueryBuilder(array $listFixture): void
    {
        $expr = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['eq', 'in', 'like', 'isNotNull', 'literal', 'orX', 'andX'])
            ->getMock();
        $expr->method('eq')->willReturn('eq_expr');
        $expr->method('in')->willReturn('in_expr');
        $expr->method('like')->willReturn('like_expr');
        $expr->method('isNotNull')->willReturn('is_not_null_expr');
        $expr->method('literal')->willReturnArgument(0);
        $expr->method('orX')->willReturn('or_x_expr');
        $expr->method('andX')->willReturn('and_x_expr');

        $executeCount = 0;
        $countResult = $this->getMockBuilder(\stdClass::class)->addMethods(['fetchColumn'])->getMock();
        $countResult->method('fetchColumn')->willReturn(max(1, count($listFixture)));

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
        $qb->method('andWhere')->willReturnSelf();
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

        $entityRepo = $this->getMockBuilder(\stdClass::class)->addMethods(['lists'])->getMock();
        $entityRepo->method('lists')->willReturn(['total_count' => 0, 'list' => []]);

        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->willReturn($entityRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager', 'getConnection'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $mockRegistry->method('getConnection')->with('default')->willReturn($conn);

        $this->app->instance('registry', $mockRegistry);
    }
}
