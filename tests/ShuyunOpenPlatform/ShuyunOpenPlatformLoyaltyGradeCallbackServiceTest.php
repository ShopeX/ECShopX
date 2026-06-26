<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use OpenapiBundle\Services\Member\MemberCardGradeService;
use OpenapiBundle\Services\Member\MemberService as OpenapiMemberService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeCallbackService;

class ShuyunOpenPlatformLoyaltyGradeCallbackServiceTest extends \TestCase
{
    public function testThrowsWhenGradeIdMissing(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->expects($this->never())->method('updateDetail');
        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->expects($this->never())->method('find');

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GRADE_ID_REQUIRED');

        $sut->applyGradeChange(1, ['mobile' => '13800138000']);
    }

    public function testThrowsWhenMemberNotResolved(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturn([]);
        $openapi->expects($this->never())->method('updateDetail');

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '12445',
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MEMBER_NOT_FOUND');

        $sut->applyGradeChange(1, ['gradeId' => 12445, 'mobile' => '13900000000', 'id' => '999999145']);
    }

    public function testApplyGradeChangeDelegatesToOpenapiUpdateDetailWithLocalGradeId(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturn([
            'user_id' => 10,
            'grade_id' => '1',
            'mobile' => '13800138000',
        ]);
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 10],
            ['external_id' => '12445'],
        )->willReturn([
            'user_id' => 10,
            'grade_id' => '1',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '12445',
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, ['gradeId' => 12445, 'mobile' => '13800138000', 'id' => '10']);
    }

    public function testSkipsUpdateWhenMemberAlreadyOnTargetGrade(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturn([
            'user_id' => 10,
            'grade_id' => '2',
            'mobile' => '13800138000',
        ]);
        $openapi->expects($this->never())->method('updateDetail');

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '12445',
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $this->assertTrue($sut->applyGradeChange(1, ['gradeId' => 12445, 'mobile' => '13800138000', 'id' => '10']));
    }

    public function testMergesNestedDataPayload(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturn([
            'user_id' => 10,
            'grade_id' => '1',
            'mobile' => '13800138000',
        ]);
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 10],
            ['external_id' => '99'],
        )->willReturn([
            'user_id' => 10,
            'grade_id' => '1',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '88',
            'external_id' => '99',
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'data' => [
                'gradeId' => 99,
                'mobile' => '13800138000',
                'id' => '10',
            ],
        ]);
    }

    public function testRoutingModeRequiresAllFields(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $gradeSvc = $this->createMock(MemberCardGradeService::class);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ROUTING_FIELD_REQUIRED:shopId');

        $sut->applyGradeChange(1, [
            'platCode' => 'X',
            'grade' => '5',
            'id' => '10',
            'partner' => 'p',
            'occurDate' => '2020-01-01',
            'sequence' => 's1',
            'source' => 't',
        ]);
    }

    public function testRoutingModeResolvesMemberByBodyId(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === 10) {
                return [
                    'user_id' => 10,
                    'grade_id' => '1',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 10],
            ['external_id' => '12445'],
        )->willReturn([
            'user_id' => 10,
            'grade_id' => '1',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '12445',
            'promotion_condition' => ['total_consumption' => 1],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'grade' => '12445',
            'expired' => '2020-05-11',
            'partner' => 'shuyun',
            'shopId' => '123456',
            'platCode' => 'TAOBAO',
            'id' => '10',
            'occurDate' => '2018-03-22 10:30:22',
            'sequence' => 'unit-routing-'.uniqid('', true),
            'source' => '客服',
            'desc' => '客服调整等级',
            'omid' => '',
        ]);
    }

    public function testResolvedByLevelWhenPromotionConditionIsJsonString(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === 99) {
                return [
                    'user_id' => 99,
                    'grade_id' => '1',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 99],
            ['grade_id' => '42'],
        )->willReturn([
            'user_id' => 99,
            'grade_id' => '42',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([]);
        $gradeSvc->method('list')->willReturn([
            'list' => [[
                'grade_id' => '42',
                'external_id' => 'shuyun-stable-not-4',
                'promotion_condition' => '{"total_consumption": 4}',
            ]],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'grade' => 4,
            'platCode' => 'NNORMALDTCUAT',
            'id' => '99',
            'partner' => 'SHUYUN',
            'shopId' => '2',
            'occurDate' => '2026-05-14 13:52:20',
            'sequence' => '35',
            'source' => 'UPGRADE',
        ]);
    }

    public function testResolvedByLevelPassesGradeIdToUpdateDetail(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === 10) {
                return [
                    'user_id' => 10,
                    'grade_id' => '1',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 10],
            ['grade_id' => '7'],
        )->willReturn([
            'user_id' => 10,
            'grade_id' => '7',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([]);
        $gradeSvc->method('list')->willReturn([
            'list' => [[
                'grade_id' => '7',
                'external_id' => '999',
                'promotion_condition' => ['total_consumption' => 3],
            ]],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'grade' => '3',
            'platCode' => 'X',
            'id' => '10',
            'expired' => '1',
            'partner' => 'p',
            'shopId' => '1',
            'occurDate' => '2020-01-01',
            'sequence' => 'lvl-'.uniqid('', true),
            'source' => 't',
        ]);
    }

    public function testRoutingModeSkipsUpdateWhenGradeAlreadyMatches(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === 10) {
                return [
                    'user_id' => 10,
                    'grade_id' => '7',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->never())->method('updateDetail');

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([]);
        $gradeSvc->method('list')->willReturn([
            'list' => [[
                'grade_id' => '7',
                'external_id' => '999',
                'promotion_condition' => ['total_consumption' => 3],
            ]],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $this->assertTrue($sut->applyGradeChange(1, [
            'grade' => '3',
            'platCode' => 'X',
            'id' => '10',
            'expired' => '1',
            'partner' => 'p',
            'shopId' => '1',
            'occurDate' => '2020-01-01',
            'sequence' => 'lvl-skip-'.uniqid('', true),
            'source' => 't',
        ]));
    }

    public function testOfflinePlatCodeUsesNumericIdWithoutSuffixStrip(): void
    {
        $resolvedUid = random_int(800000, 899999);

        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter) use ($resolvedUid): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === $resolvedUid) {
                return [
                    'user_id' => $resolvedUid,
                    'grade_id' => '1',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => $resolvedUid],
            ['external_id' => '12445'],
        )->willReturn([
            'user_id' => $resolvedUid,
            'grade_id' => '2',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '12445',
            'promotion_condition' => ['total_consumption' => 1],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'id' => (string) $resolvedUid,
            'memberId' => 'unknown-card',
            'shopId' => '76',
            'platCode' => 'OFFLINE',
            'grade' => '12445',
            'created' => '2026-05-06 10:41:40',
            'version' => '2026-05-06 10:41:40',
            'changeType' => 'UPGRADE',
        ]);
    }

    public function testRoutingModeMapsSimulatorFieldAliases(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === 10) {
                return [
                    'user_id' => 10,
                    'grade_id' => '1',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 10],
            ['external_id' => '12445'],
        )->willReturn([
            'user_id' => 10,
            'grade_id' => '2',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '12445',
            'promotion_condition' => ['total_consumption' => 1],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'id' => '10',
            'shopId' => '76',
            'platCode' => 'NNORMALDTCDEV2',
            'gradeName' => '普通会员',
            'grade' => '12445',
            'memberId' => 'ignored',
            'created' => '2026-05-06 10:41:40',
            'version' => '2026-05-06 10:41:40',
            'changeType' => 'UPGRADE',
        ]);
    }

    public function testRoutingModeAcceptsShuyunCapturedBodyWithoutExpiredFields(): void
    {
        $openapi = $this->createMock(OpenapiMemberService::class);
        $openapi->method('find')->willReturnCallback(static function (array $filter): array {
            if (isset($filter['user_id']) && (int) $filter['user_id'] === 50) {
                return [
                    'user_id' => 50,
                    'grade_id' => '1',
                    'mobile' => '13800138000',
                ];
            }

            return [];
        });
        $openapi->expects($this->once())->method('updateDetail')->with(
            ['company_id' => 1, 'user_id' => 50],
            ['external_id' => '1'],
        )->willReturn([
            'user_id' => 50,
            'grade_id' => '2',
            'mobile' => '13800138000',
        ]);

        $gradeSvc = $this->createMock(MemberCardGradeService::class);
        $gradeSvc->method('find')->willReturn([
            'grade_id' => '2',
            'external_id' => '1',
            'promotion_condition' => ['total_consumption' => 1],
        ]);

        $sut = new ShuyunOpenPlatformLoyaltyGradeCallbackService($openapi, $gradeSvc);
        $sut->applyGradeChange(1, [
            'gradeName' => '店铺客户',
            'platCode' => 'NNORMALDTCUAT',
            'created' => '2026-05-14 14:59:26',
            'changeType' => 'KEEPING',
            'grade' => 1,
            'id' => '50',
            'shopId' => '2',
            'version' => '1778741966927684342',
            'memberId' => 101873966032,
        ]);
    }
}
