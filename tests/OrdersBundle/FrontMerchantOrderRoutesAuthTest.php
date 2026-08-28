<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

/**
 * codex-security-02-high-auth-crypto T11：Front 商户订单路由门控（TC-02-05a..e）
 */
class FrontMerchantOrderRoutesAuthTest extends \TestCase
{
    private function ordersRoutes(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/routes/frontapi/orders.php');
    }

    private function routeLine(string $needle): string
    {
        $content = $this->ordersRoutes();
        $pos = strpos($content, $needle);
        $this->assertNotFalse($pos, "Route containing {$needle} must exist");

        $lineStart = strrpos(substr($content, 0, $pos), "\n");
        $lineEnd = strpos($content, "\n", $pos);
        $this->assertNotFalse($lineEnd);

        return substr($content, $lineStart === false ? 0 : $lineStart + 1, $lineEnd - ($lineStart === false ? 0 : $lineStart + 1));
    }

    /**
     * TC-02-05a / F-048：顾客 JWT 取消配送 → 403（路由挂 frontselfdelivery）
     * #given frontapi/orders.php 取消配送路由
     * #when 检查路由 middleware
     * #then 须包含 frontselfdelivery
     */
    public function testTc0205aCancelDeliveryStaffRouteRequiresSelfDeliveryStaffMiddleware(): void
    {
        #given / #when
        $line = $this->routeLine('/wxapp/order/cancel/deliverystaff');

        #then
        $this->assertStringContainsString('frontselfdelivery', $line);
    }

    /**
     * TC-02-05b / F-049：顾客 JWT 订单打包 → 403（路由挂 frontselfdelivery）
     * #given frontapi/orders.php 打包确认路由
     * #when 检查路由 middleware
     * #then 须包含 frontselfdelivery
     */
    public function testTc0205bConfirmDeliveryPackagRouteRequiresSelfDeliveryStaffMiddleware(): void
    {
        #given / #when
        $line = $this->routeLine('/wxapp/order/deliverypackag/confirm');

        #then
        $this->assertStringContainsString('frontselfdelivery', $line);
    }

    /**
     * TC-02-05c / F-050：顾客 JWT 改物流 → 403（路由挂 frontselfdelivery）
     * #given frontapi/orders.php updateDelivery 路由
     * #when 检查路由 middleware
     * #then 须包含 frontselfdelivery
     */
    public function testTc0205cUpdateDeliveryRouteRequiresSelfDeliveryStaffMiddleware(): void
    {
        #given / #when
        $line = $this->routeLine('/wxapp/order/updateDelivery/{delivery_id}');

        #then
        $this->assertStringContainsString('frontselfdelivery', $line);
    }

    /**
     * TC-02-05d / F-051：顾客 JWT 商户配送 → 403（路由挂 frontselfdelivery）
     * #given frontapi/orders.php delivery 路由
     * #when 检查路由 middleware
     * #then 须包含 frontselfdelivery
     */
    public function testTc0205dDeliveryRouteRequiresSelfDeliveryStaffMiddleware(): void
    {
        #given / #when
        $line = $this->routeLine('/wxapp/order/delivery');

        #then
        $this->assertStringContainsString('frontselfdelivery', $line);
    }

    /**
     * TC-02-05e / F-052：顾客 JWT 发票设置 → 403（front 移除 POST setInvoiceSetting）
     * #given frontapi/orders.php
     * #when 检查是否仍存在顾客可写的 invoice setting POST
     * #then 不得暴露 UserInvoice@setInvoiceSetting POST
     */
    public function testTc0205eFrontRoutesDoNotExposeInvoiceSettingMutation(): void
    {
        #given
        $content = $this->ordersRoutes();

        #then
        $this->assertStringNotContainsString(
            "post('/wxapp/order/invoice/setting'",
            strtolower($content),
            'Tenant invoice setting mutation must not be exposed on front customer routes'
        );
        $this->assertStringNotContainsString(
            'UserInvoice@setInvoiceSetting',
            $content,
            'setInvoiceSetting must not remain on front routes'
        );
    }
}
