<?php

/**
 * 购物车 list.pics：有规格图时优先展示规格图。
 */

use OrdersBundle\Services\CartService;

class CartServiceResolveCartItemPicTest extends TestCase
{
    private function resolveCartItemPic(array $itemRow): string
    {
        return (new CartService())->resolveCartItemPic($itemRow);
    }

    public function testPrefersItemSpecCustomImageOverMainPics(): void
    {
        $url = $this->resolveCartItemPic([
            'pics' => ['https://main.jpg'],
            'item_spec' => [
                ['item_image_url' => 'https://spec-custom.jpg', 'spec_image_url' => 'https://spec-default.jpg'],
            ],
        ]);
        $this->assertSame('https://spec-custom.jpg', $url);
    }

    public function testFallsBackToMainPicsWhenNoSpecImage(): void
    {
        $url = $this->resolveCartItemPic([
            'pics' => ['https://main.jpg'],
            'item_spec' => [['spec_value_name' => '红色']],
        ]);
        $this->assertSame('https://main.jpg', $url);
    }

    public function testHandleValidCartAssignsPicsViaResolveCartItemPic(): void
    {
        $body = (new ReflectionMethod(CartService::class, 'HandleValidCart'))->getFileName();
        $this->assertNotFalse($body);
        $lines = file($body, FILE_IGNORE_NEW_LINES);
        $ref = new ReflectionMethod(CartService::class, 'HandleValidCart');
        $slice = implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
        $this->assertStringContainsString('resolveCartItemPic', $slice);
    }
}
