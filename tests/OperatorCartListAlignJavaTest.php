<?php

declare(strict_types=1);

use CompanysBundle\Services\OperatorCartService;
use OrdersBundle\Services\CartService;

/**
 * 店务购物车列表行为对齐 ecshopx-java（PJ3174JF-133 / ECX-10747）的结构约束。
 */
class OperatorCartListAlignJavaTest extends PHPUnit\Framework\TestCase
{
    public function testShopOfflineStockClampDoesNotPersistInHandleValidCart(): void
    {
        $body = $this->methodBody(CartService::class, 'HandleValidCart');
        $this->assertStringContainsString("cartType == 'shop_offline'", $body);
        $this->assertMatchesRegularExpression(
            "/cartType == 'shop_offline'[\\s\\S]{0,280}\\\$cartdata\\['num'\\] = \\\$store;/",
            $body
        );
        $this->assertDoesNotMatchRegularExpression(
            "/cartType == 'shop_offline'[\\s\\S]{0,320}operatorCartRepository->updateOneBy\\(\\['cart_id'\\] => \\\$cartdata\\['cart_id'\\], \\['num' => \\\$store\\]\\)/",
            $body
        );
    }

    public function testOperatorCartListPurgesInvalidRowsInsteadOfUncheckOnly(): void
    {
        $body = $this->methodBody(OperatorCartService::class, 'getCartdataList');
        $this->assertStringContainsString('purgeOperatorCartRowsByCartIds', $body);
        $this->assertStringNotContainsString("'is_checked' => 0", $body);
    }

    public function testOperatorCartListPurgesWhenSkuLoadEmpty(): void
    {
        $body = $this->methodBody(OperatorCartService::class, 'getCartdataList');
        $this->assertStringContainsString('purgeOperatorCartRows', $body);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        $lines = file($ref->getFileName());
        return implode('', array_slice($lines, $start - 1, $end - $start + 1));
    }
}
