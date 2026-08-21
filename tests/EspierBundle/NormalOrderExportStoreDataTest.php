<?php

declare(strict_types=1);

namespace Tests\EspierBundle;

use EspierBundle\Services\Export\NormalOrderExportService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;
use ReflectionMethod;

/**
 * 普通订单导出店铺信息补齐 — distributor_id=0 自营总店 store_name/store_code
 * 计划：.tasks/plans/employee-purchase-virtual-store-filter.md
 *
 * 导出链路对 order_type=normal 走 NormalOrderExportService::getLists，需为 distributor_id=0
 * 补齐自营总店信息，避免 array_filter 过滤 0 后 store_name/store_code 缺失。
 */
class NormalOrderExportStoreDataTest extends \TestCase
{
    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    /**
     * TC-01：导出 getLists 必须补齐 distributor_id=0 的自营总店信息。
     * #given 导出店铺信息处理
     * #when 检查 getLists 方法体
     * #then 必须调用 getDistributorSelfSimpleInfo
     */
    public function testGetListsFetchesSelfStoreInfoForZeroDistributor(): void
    {
        $body = $this->getListsBody();

        $this->assertStringContainsString(
            'getDistributorSelfSimpleInfo',
            $body,
            '导出 getLists 必须调用 getDistributorSelfSimpleInfo 补齐自营总店信息'
        );
    }

    /**
     * TC-02：导出 getLists 必须初始化 $storeData，避免无店铺时未定义。
     * #given 仅 distributor_id=0（array_filter 过滤后为空）
     * #when 检查 getLists 方法体
     * #then 必须初始化 $storeData = []
     */
    public function testGetListsInitializesStoreData(): void
    {
        $body = $this->getListsBody();

        $this->assertStringContainsString(
            '$storeData = []',
            $body,
            '导出 getLists 必须初始化 $storeData = []'
        );
    }

    /**
     * TC-03：导出 getLists 必须写入 $storeData[0]，供 distributor_id=0 订单使用。
     * #given 导出店铺信息处理
     * #when 检查 getLists 方法体
     * #then 必须写入 $storeData[0]
     */
    public function testGetListsFillsZeroStoreData(): void
    {
        $body = $this->getListsBody();

        $this->assertStringContainsString(
            '$storeData[0]',
            $body,
            '导出 getLists 必须写入 $storeData[0] 自营总店信息'
        );
    }

    /**
     * TC-04：导出查询路径（getOrderItemCount / getOrderItemList）已挂载虚拟门店展开。
     * #given 导出查询走普通订单服务
     * #when 检查两个查询入口方法体
     * #then 均必须调用 expandVirtualStoreDistributorFilter
     */
    public function testExportQueryPathsApplyVirtualStoreExpansion(): void
    {
        foreach (['getOrderItemCount', 'getOrderItemList'] as $method) {
            $body = $this->methodBody(AbstractNormalOrder::class, $method);
            $this->assertStringContainsString(
                'expandVirtualStoreDistributorFilter',
                $body,
                sprintf('%s 必须调用 expandVirtualStoreDistributorFilter', $method)
            );
        }
    }

    private function getListsBody(): string
    {
        return $this->methodBody(NormalOrderExportService::class, 'getLists');
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
