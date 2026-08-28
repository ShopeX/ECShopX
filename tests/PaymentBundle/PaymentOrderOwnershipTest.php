<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Http\FrontApi\V1\Action\WxappPayment;
use PaymentBundle\Services\PaymentOrderOwnershipGuard;
use PaymentBundle\Services\PaymentService;

/**
 * cnvd-proxy-order-auth-fix：在线支付订单归属（TC-PAY-01/02）
 * 计划：.tasks/plans/cnvd-proxy-order-auth-fix.md
 */
class PaymentOrderOwnershipTest extends \TestCase
{
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    /**
     * TC-PAY-01 / S-A12：order.user_id==auth → 继续（不抛异常）。
     * #given 订单 user_id=100，auth user_id=100
     * #when 校验订单归属
     * #then 不抛异常
     */
    public function testTcPay01MatchingOrderUserIdAllowsPayment(): void
    {
        #given
        $order = ['user_id' => 100, 'order_id' => '3313653000370376'];
        $authInfo = ['user_id' => 100, 'company_id' => 1];

        #when
        PaymentOrderOwnershipGuard::assertBelongsToAuthUser($order, $authInfo);

        #then
        $this->addToAssertionCount(1);
    }

    /**
     * TC-PAY-02 / S-A13、S-A14：order.user_id≠auth → 拒绝。
     * #given 订单 user_id=200，auth user_id=100
     * #when 校验订单归属
     * #then 抛 ResourceException
     */
    public function testTcPay02OrderUserIdMismatchThrowsResourceException(): void
    {
        #given
        $order = ['user_id' => 200, 'order_id' => '3313653000370376'];
        $authInfo = ['user_id' => 100, 'company_id' => 1];

        #when / #then
        try {
            PaymentOrderOwnershipGuard::assertBelongsToAuthUser($order, $authInfo);
            $this->fail('Expected ResourceException when order.user_id does not match auth.user_id');
        } catch (ResourceException $e) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * TC-PAY-03 / S-A16：operator auth 无 user_id → Guard 跳过归属比对（不抛异常）。
     * #given 订单 user_id=200；auth 含 operator_id、company_id，无 user_id
     * #when 校验订单归属
     * #then 不抛异常
     */
    public function testTcPay03OperatorAuthWithoutUserIdDoesNotThrow(): void
    {
        #given
        $order = ['user_id' => 200, 'order_id' => '3313653000370376'];
        $authInfo = ['operator_id' => 42, 'company_id' => 1];

        #when
        PaymentOrderOwnershipGuard::assertBelongsToAuthUser($order, $authInfo);

        #then
        $this->addToAssertionCount(1);
    }

    /**
     * TC-PAY-04 / S-A17：operator auth 无 user_id，他人订单 → Guard 跳过归属比对（不抛异常）。
     * #given 订单 user_id=999（他人）；auth 含 operator_id、company_id，无 user_id
     * #when 校验订单归属
     * #then 不抛异常
     */
    public function testTcPay04OperatorAuthWithOtherOrderUserIdDoesNotThrow(): void
    {
        #given
        $order = ['user_id' => 999, 'order_id' => '3313653000370376'];
        $authInfo = ['operator_id' => 42, 'company_id' => 1];

        #when
        PaymentOrderOwnershipGuard::assertBelongsToAuthUser($order, $authInfo);

        #then
        $this->addToAssertionCount(1);
    }

    /**
     * TC-PAY-05 / S-A20：operator_type=user 且无 user_id、无 operator_id → 拒绝。
     * #given 订单含 user_id；auth 含 operator_type=user、company_id，无 user_id、无 operator_id
     * #when 校验订单归属
     * #then 抛 ResourceException
     */
    public function testTcPay05UserOperatorTypeWithoutUserIdThrowsResourceException(): void
    {
        #given
        $order = ['user_id' => 200, 'order_id' => '3313653000370376'];
        $authInfo = ['operator_type' => 'user', 'company_id' => 1];

        #when / #then
        try {
            PaymentOrderOwnershipGuard::assertBelongsToAuthUser($order, $authInfo);
            $this->fail('Expected ResourceException when operator_type=user without user_id or operator_id');
        } catch (ResourceException $e) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * TC-PAY-01/02 集成：PaymentService::payment 取单后须校验归属。
     */
    public function testPaymentServicePaymentMethodCallsOwnershipGuardAfterGetOrder(): void
    {
        $body = $this->methodBody(PaymentService::class, 'payment');
        $guardPos = strpos($body, 'PaymentOrderOwnershipGuard::assertBelongsToAuthUser');
        $getOrderInfoPos = strpos($body, 'getOrderInfo');
        $this->assertNotFalse($guardPos);
        $this->assertNotFalse($getOrderInfoPos);
        $this->assertLessThan($getOrderInfoPos, $guardPos);
    }

    /**
     * TC-PAY-01/02 集成：WxappPayment::doPayment order_id 分支须校验归属。
     */
    public function testWxappPaymentDoPaymentOrderIdBranchCallsOwnershipGuard(): void
    {
        $body = $this->methodBody(WxappPayment::class, 'doPayment');
        $this->assertStringContainsString('PaymentOrderOwnershipGuard::assertBelongsToAuthUser', $body);
        $this->assertStringContainsString("if (\$request->input('order_id'))", $body);
    }

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
}
