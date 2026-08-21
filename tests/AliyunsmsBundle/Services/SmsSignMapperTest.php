<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Services;

use AliyunsmsBundle\Services\SmsSignMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-01, A12 */
class SmsSignMapperTest extends TestCase
{
    public function testMapAuditStatusInitToZero(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapAuditStatus('AUDIT_STATE_INIT');

        // #then
        $this->assertSame(0, $status);
    }

    public function testMapAuditStatusPassToOne(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapAuditStatus('AUDIT_STATE_PASS');

        // #then
        $this->assertSame(1, $status);
    }

    public function testMapAuditStatusNotPassToTwo(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapAuditStatus('AUDIT_STATE_NOT_PASS');

        // #then
        $this->assertSame(2, $status);
    }

    public function testMapAuditStatusCancelToTwo(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapAuditStatus('AUDIT_STATE_CANCEL');

        // #then
        $this->assertSame(2, $status);
    }

    public function testMapAuditStatusUnknownThrows(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when #then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AuditStatus: AUDIT_STATE_UNKNOWN');
        $mapper->mapAuditStatus('AUDIT_STATE_UNKNOWN');
    }

    public function testMapSignStatusZeroToZero(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapSignStatus(0);

        // #then
        $this->assertSame(0, $status);
    }

    public function testMapSignStatusOneToOne(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapSignStatus(1);

        // #then
        $this->assertSame(1, $status);
    }

    public function testMapSignStatusTwoToTwo(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapSignStatus(2);

        // #then
        $this->assertSame(2, $status);
    }

    public function testMapSignStatusTenToTwo(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $status = $mapper->mapSignStatus(10);

        // #then
        $this->assertSame(2, $status);
    }

    public function testMapSignStatusUnknownThrows(): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when #then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown SignStatus: 99');
        $mapper->mapSignStatus(99);
    }

    /**
     * @dataProvider thirdPartyTruthyProvider
     *
     * @param mixed $input
     */
    public function testNormalizeThirdPartyTruthy($input): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $value = $mapper->normalizeThirdParty($input);

        // #then
        $this->assertSame(1, $value);
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function thirdPartyTruthyProvider(): iterable
    {
        yield 'bool true' => [true];
        yield 'int 1' => [1];
        yield 'string true' => ['true'];
    }

    /**
     * @dataProvider thirdPartyFalsyProvider
     *
     * @param mixed $input
     */
    public function testNormalizeThirdPartyFalsy($input): void
    {
        // #given
        $mapper = new SmsSignMapper();

        // #when
        $value = $mapper->normalizeThirdParty($input);

        // #then
        $this->assertSame(0, $value);
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function thirdPartyFalsyProvider(): iterable
    {
        yield 'bool false' => [false];
        yield 'int 0' => [0];
        yield 'string false' => ['false'];
    }
}
