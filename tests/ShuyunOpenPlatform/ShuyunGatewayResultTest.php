<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Gateway\ShuyunGatewayResult;

class ShuyunGatewayResultTest extends TestCase
{
    /** @see .tasks/plans/shuyun-open-platform-core.md TC-RSP-01 */
    public function testSuccessPackage(): void
    {
        $json = '{"code":10000,"data":{"x":1},"msg":"ok"}';
        $r = ShuyunGatewayResult::fromJsonString($json);
        $this->assertTrue($r->isSuccess());
        $this->assertSame(['x' => 1], $r->getData());
        $this->assertSame(10000, $r->getCode());
        $this->assertSame('ok', $r->getMsg());
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-RSP-02 */
    public function testBusinessFailureParsedWithoutThrowingFromResult(): void
    {
        $json = '{"code":40001,"data":null,"msg":"bad"}';
        $r = ShuyunGatewayResult::fromJsonString($json);
        $this->assertFalse($r->isSuccess());
        $this->assertSame(40001, $r->getCode());
        $this->assertSame('bad', $r->getMsg());
    }

    public function testUsesMessageFieldWhenMsgEmpty(): void
    {
        $json = '{"code":10999,"data":null,"msg":"","message":"缺少 Gateway-Access-Token"}';
        $r = ShuyunGatewayResult::fromJsonString($json);
        $this->assertFalse($r->isSuccess());
        $this->assertSame('缺少 Gateway-Access-Token', $r->getMsg());
    }

    public function testMsgPreferredOverMessageWhenBothPresent(): void
    {
        $json = '{"code":40001,"data":null,"msg":"主提示","message":"次要"}';
        $r = ShuyunGatewayResult::fromJsonString($json);
        $this->assertSame('主提示', $r->getMsg());
    }

    public function testArrayMessageIsNormalizedWithoutTypeWarning(): void
    {
        $json = '{"code":14000,"data":null,"message":{"field":"mobile","detail":"invalid"}}';
        $r = ShuyunGatewayResult::fromJsonString($json);
        $this->assertFalse($r->isSuccess());
        $this->assertSame('{"field":"mobile","detail":"invalid"}', $r->getMsg());
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-RSP-03 */
    public function testInvalidJsonThrows(): void
    {
        $this->expectException(ShuyunGatewayJsonException::class);
        ShuyunGatewayResult::fromJsonString('not json');
    }

    /** @see .tasks/plans/shuyun-open-platform-core.md TC-RSP-04 — 由 Client 侧抛 HTTP 异常；此处仅覆盖空 body 非 JSON 路径 */
    public function testEmptyStringIsInvalidJson(): void
    {
        $this->expectException(ShuyunGatewayJsonException::class);
        ShuyunGatewayResult::fromJsonString('');
    }
}
