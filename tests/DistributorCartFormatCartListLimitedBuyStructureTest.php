<?php

/**
 * DistributorCartObject::formatCartList 限购（limited_buy）checkout 门控结构断言。
 */

use OrdersBundle\Services\Cart\DistributorCartObject;

class DistributorCartFormatCartListLimitedBuyStructureTest extends TestCase
{
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

    private function limitedBuyBlock(string $body): string
    {
        if (!preg_match("/'limited_buy'.*?(?=\'limited_time_sale\')/s", $body, $matches)) {
            $this->fail('limited_buy block not found in formatCartList');
        }

        return $matches[0];
    }

    private function limitedTimeSaleBlock(string $body): string
    {
        if (!preg_match('/\'limited_time_sale\'.*?limitedTimeSaleAct/s', $body, $matches)) {
            $this->fail('limited_time_sale block not found in formatCartList');
        }

        return $matches[0];
    }

    public function testTcStruct1LimitedBuyThrowIncludesIsCheckout(): void
    {
        // #given formatCartList method body
        $body = $this->methodBody(DistributorCartObject::class, 'formatCartList');
        $limitedBuyBlock = $this->limitedBuyBlock($body);

        // #when inspecting limited_buy over-limit throw condition
        $this->assertStringContainsString('超出限购数量', $limitedBuyBlock);

        // #then throw is gated by $isCheckout (aligned with limited_time_sale)
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$num\s*>\s*\$rule\[\'limit\'\]\s*&&\s*\$isCheckout\s*\)/',
            $limitedBuyBlock
        );
    }

    public function testTcStruct2LimitedBuyMetadataStillAssigned(): void
    {
        // #given formatCartList method body
        $body = $this->methodBody(DistributorCartObject::class, 'formatCartList');
        $limitedBuyBlock = $this->limitedBuyBlock($body);

        // #when inspecting limited_buy metadata assignment
        // #then limitedBuy array is still mounted on valid_cart
        $this->assertStringContainsString("\$cartData['valid_cart'][\$key]['limitedBuy']", $limitedBuyBlock);
        $this->assertStringContainsString("'marketing_type' => 'limited_buy'", $limitedBuyBlock);
    }

    public function testTcStruct3LimitedTimeSaleThrowStillIncludesIsCheckout(): void
    {
        // #given formatCartList method body
        $body = $this->methodBody(DistributorCartObject::class, 'formatCartList');
        $limitedTimeSaleBlock = $this->limitedTimeSaleBlock($body);

        // #when inspecting limited_time_sale over-limit throw condition
        $this->assertStringContainsString('超出限购数量', $limitedTimeSaleBlock);

        // #then existing checkout gate on limited_time_sale is preserved
        $this->assertStringContainsString('$isCheckout', $limitedTimeSaleBlock);
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$itemData\[\'limit_num\'\]\s*>\s*0\s*&&\s*\(\$itemData\[\'limit_num\'\]\s*-\s*\$userBuyStore\)\s*<\s*\$cartRow\[\'num\'\]\s*&&\s*\$isCheckout\s*\)/',
            $limitedTimeSaleBlock
        );
    }
}
