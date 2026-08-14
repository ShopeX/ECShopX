<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use OrdersBundle\Http\FrontApi\V1\Action\CartController;

/**
 * TC-CART-R01：Cart 七方法统一 resolveActingUserId 结构测。
 * 计划：salesperson-proxy-acting-user-id-unify
 */
class CartControllerProxyAuthGateTest extends \TestCase
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

    private function assertResolveBeforeUserIdOverride(string $methodBody, string $overrideNeedle): void
    {
        $resolvePos = strpos($methodBody, 'resolveActingUserId');
        $this->assertNotFalse(
            $resolvePos,
            'Expected resolveActingUserId in method body'
        );

        $overridePos = strpos($methodBody, $overrideNeedle);
        $this->assertNotFalse(
            $overridePos,
            'Expected user_id override in method body'
        );

        $this->assertLessThan(
            $overridePos,
            $resolvePos,
            'resolveActingUserId must appear before user_id override'
        );
    }

    /**
     * TC-CART-R01 / S-R3/S-R5：七方法均通过 resolveActingUserId 解析 acting user_id，且先于 user_id 覆盖。
     * #given Cart 代客相关七个方法体
     * #when 检查 resolveActingUserId 与 user_id 覆盖顺序
     * #then 均含 resolveActingUserId、无内联 assertCanProxyAsSalesperson，且 resolve 在覆盖之前
     */
    public function testTcCartR01AllSevenMethodsUseResolveBeforeUserIdOverride(): void
    {
        $cases = [
            'addCart' => "\$params['user_id'] = \$actingUserId",
            'getDistributorCartList' => "\$inputData['user_id'] = \$actingUserId",
            'deleteCartData' => "\$filter['user_id'] = \$actingUserId",
            'deleteCartDataBat' => "\$filter['user_id'] = \$actingUserId",
            'updateCartCheckStatus' => "\$filter['user_id'] = \$actingUserId",
            'updateCartNum' => "\$filter['user_id'] = \$actingUserId",
            'getCartItemCount' => "\$filter['user_id'] = \$actingUserId",
        ];

        foreach ($cases as $method => $overrideNeedle) {
            $body = $this->methodBody(CartController::class, $method);

            $this->assertStringContainsString(
                'SalespersonProxyAuthorizationService',
                $body,
                "Expected SalespersonProxyAuthorizationService in {$method}"
            );
            $this->assertStringNotContainsString(
                'assertCanProxyAsSalesperson',
                $body,
                "Expected no inline assertCanProxyAsSalesperson in {$method}"
            );
            $this->assertResolveBeforeUserIdOverride($body, $overrideNeedle);
        }
    }
}
