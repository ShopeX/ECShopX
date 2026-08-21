<?php

declare(strict_types=1);

namespace Tests\EmployeePurchaseBundle;

use EmployeePurchaseBundle\Services\NormalOrderService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;
use ReflectionMethod;

/**
 * 内购订单（order_class=employee_purchase）虚拟门店 distributor_id 筛选展开 — 精简 TC-01～TC-06
 * 计划：.tasks/plans/employee-purchase-virtual-store-filter.md
 *
 * 内购 getOrderList 需复用父类虚拟门店展开逻辑，并为订单注入订单级 distributor_info
 * （distributor_id=0 → 自营总店信息；distributor_id>0 → 店铺信息）。
 */
class EmployeePurchaseVirtualStoreFilterTest extends \TestCase
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
     * TC-01：父类虚拟门店展开方法必须由 private 放开为 protected，供内购子类复用。
     * #given 父类 AbstractNormalOrder 的展开方法
     * #when 反射检查可见性
     * #then 应为 protected（内购子类可 $this-> 调用）
     */
    public function testExpandVirtualStoreDistributorFilterIsProtected(): void
    {
        $ref = new ReflectionMethod(AbstractNormalOrder::class, 'expandVirtualStoreDistributorFilter');

        $this->assertTrue(
            $ref->isProtected(),
            'expandVirtualStoreDistributorFilter 应为 protected 以供内购子类调用'
        );
        $this->assertFalse(
            $ref->isPrivate(),
            'expandVirtualStoreDistributorFilter 不应继续为 private'
        );
    }

    /**
     * TC-02：内购 getOrderList 查库前必须调用 expandVirtualStoreDistributorFilter。
     * #given 内购订单列表方法
     * #when 检查方法体
     * #then 必须调用虚拟门店筛选展开
     */
    public function testGetOrderListInvokesExpandVirtualStoreDistributorFilter(): void
    {
        $body = $this->getOrderListBody();

        $this->assertStringContainsString(
            'expandVirtualStoreDistributorFilter',
            $body,
            '内购 getOrderList 必须在查库前调用 expandVirtualStoreDistributorFilter'
        );
    }

    /**
     * TC-03：内购 getOrderList 必须写入订单级 distributor_info（精确断言，避免命中 sale_salesman_distributor_info）。
     * #given 内购订单列表方法
     * #when 检查方法体
     * #then 必须注入订单级 distributor_info
     */
    public function testGetOrderListInjectsOrderLevelDistributorInfo(): void
    {
        $body = $this->getOrderListBody();

        $this->assertStringContainsString(
            "\$result['list'][\$k]['distributor_info']",
            $body,
            '内购 getOrderList 必须注入订单级 distributor_info'
        );
    }

    /**
     * TC-04：订单级 distributor_info 按订单 distributor_id 映射（0 → 自营总店，>0 → 店铺）。
     * #given 内购订单列表方法
     * #when 检查方法体
     * #then 必须按 $v['distributor_id'] 取店铺信息
     */
    public function testGetOrderListMapsDistributorInfoByOrderDistributorId(): void
    {
        $body = $this->getOrderListBody();

        $this->assertStringContainsString(
            "\$storeData[\$v['distributor_id']]",
            $body,
            '内购 getOrderList 必须按订单 distributor_id 映射 distributor_info'
        );
    }

    private function getOrderListBody(): string
    {
        return $this->methodBody(NormalOrderService::class, 'getOrderList');
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
