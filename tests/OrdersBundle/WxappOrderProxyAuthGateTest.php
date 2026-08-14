<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use OrdersBundle\Http\FrontApi\V1\Action\WxappOrder;

/**
 * TC-ORD-01～03、TC-FEE-01：WxappOrder 代客门控结构/接线测试。
 * 计划：.tasks/plans/cnvd-proxy-order-auth-fix.md
 */
class WxappOrderProxyAuthGateTest extends \TestCase
{
    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    /**
     * TC-ORD-R01 / S-R8：createNewOrder 改用 resolveActingUserId 解析 acting user_id。
     * #given createNewOrder 方法体
     * #when 检查 acting user_id 解析与代客订单字段
     * #then resolveActingUserId 赋值 user_id；代客成功时仍设 order_source=salesperson 等
     */
    public function testTcOrdR01CreateNewOrderUsesResolveActingUserId(): void
    {
        $body = $this->methodBody(WxappOrder::class, 'createNewOrder');

        $this->assertStringContainsString('SalespersonProxyAuthorizationService', $body);
        $this->assertStringContainsString('resolveActingUserId', $body);

        $resolvePos = strpos($body, 'resolveActingUserId');
        $assignPos = strpos($body, "\$params['user_id'] = \$actingUserId");
        $this->assertNotFalse($resolvePos, '应调用 resolveActingUserId');
        $this->assertNotFalse($assignPos, '应将 resolve 结果赋给 user_id');
        $this->assertLessThan($assignPos, $resolvePos, 'resolveActingUserId 须在 user_id 赋值之前调用');

        $this->assertStringContainsString("\$authInfo['user_id']", $body);
        $this->assertStringContainsString("\$params['order_source'] = 'salesperson'", $body);
        $this->assertStringContainsString("\$params['salesman_id']", $body);
        $this->assertStringContainsString("\$params['promoter_user_id']", $body);
    }

    /**
     * TC-ORD-02 / S-A2：createNewOrder 将 distributor_id 传入 resolveActingUserId。
     * #given createNewOrder 方法体
     * #when 检查 resolveActingUserId 调用参数
     * #then 传入 distributor_id（有则店匹配）
     */
    public function testTcOrd02CreateNewOrderPassesDistributorIdToResolveActingUserId(): void
    {
        $body = $this->methodBody(WxappOrder::class, 'createNewOrder');

        $this->assertStringContainsString('resolveActingUserId', $body);
        $this->assertStringContainsString("\$params['distributor_id']", $body);
    }

    /**
     * TC-ORD-03 / S-A15：无代客参数时 resolveActingUserId 保持 auth user_id（自购/海报扫码路径）。
     * #given createNewOrder 方法体
     * #when 检查 acting user_id 解析
     * #then 通过 resolveActingUserId 赋值 user_id
     */
    public function testTcOrd03CreateNewOrderKeepsAuthUserIdViaResolveActingUserId(): void
    {
        $body = $this->methodBody(WxappOrder::class, 'createNewOrder');

        $this->assertStringContainsString('resolveActingUserId', $body);
        $this->assertStringContainsString("\$params['user_id'] = \$actingUserId", $body);
        $this->assertStringContainsString("\$authInfo['user_id']", $body);
    }

    /**
     * TC-ORD-R01 / S-R9：getOrderFreightFeeInfo 改用 resolveActingUserId 解析 acting user_id。
     * #given getOrderFreightFeeInfo 方法体
     * #when 检查 acting user_id 解析
     * #then resolveActingUserId 赋值 user_id
     */
    public function testTcOrdR01GetOrderFreightFeeInfoUsesResolveActingUserId(): void
    {
        $body = $this->methodBody(WxappOrder::class, 'getOrderFreightFeeInfo');

        $this->assertStringContainsString('SalespersonProxyAuthorizationService', $body);
        $this->assertStringContainsString('resolveActingUserId', $body);

        $resolvePos = strpos($body, 'resolveActingUserId');
        $assignPos = strpos($body, "\$params['user_id'] = \$actingUserId");
        $this->assertNotFalse($resolvePos, '应调用 resolveActingUserId');
        $this->assertNotFalse($assignPos, '应将 resolve 结果赋给 user_id');
        $this->assertLessThan($assignPos, $resolvePos, 'resolveActingUserId 须在 user_id 赋值之前调用');
    }
}
