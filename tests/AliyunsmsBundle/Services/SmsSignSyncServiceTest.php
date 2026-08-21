<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Services;

use AliyunsmsBundle\Repositories\SignRepository;
use AliyunsmsBundle\Services\SmsSignMapper;
use AliyunsmsBundle\Services\SmsSignSyncService;
use PHPUnit\Framework\TestCase;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-02, TC-03, TC-04, TC-05, TC-05b, TC-05c, TC-17 */
class SmsSignSyncServiceTest extends TestCase
{
    private function makeService(
        AliyunSmsClient $client,
        SignRepository $signRepository,
        ?SmsSignMapper $mapper = null,
        ?callable $hasSceneAssociation = null,
        ?callable $hasActiveTask = null
    ): SmsSignSyncService {
        return new SmsSignSyncService(
            static function (int $companyId) use ($client): AliyunSmsClient {
                return $client;
            },
            $signRepository,
            $mapper ?? new SmsSignMapper(),
            $hasSceneAssociation ?? static fn (int $companyId, int $signId): bool => false,
            $hasActiveTask ?? static fn (int $companyId, int $signId): bool => false
        );
    }

    /**
     * TC-02: sync 两页 List（pageSize=50）应翻页并全部 upsert。
     */
    public function testTc02SyncTwoPagesListUpsertsAll(): void
    {
        // #given
        $companyId = 100;
        $page1Names = array_map(static fn (int $i): string => 'SignPage1_' . $i, range(1, 50));
        $page2Names = array_map(static fn (int $i): string => 'SignPage2_' . $i, range(1, 10));
        $allNames = array_merge($page1Names, $page2Names);

        $client = $this->createMock(AliyunSmsClient::class);
        $client->expects($this->exactly(2))
            ->method('querySmsSignList')
            ->withConsecutive([1, 50], [2, 50])
            ->willReturnOnConsecutiveCalls(
                [
                    'Code' => 'OK',
                    'TotalCount' => 60,
                    'SmsSignList' => array_map(static fn (string $name): array => ['SignName' => $name], $page1Names),
                ],
                [
                    'Code' => 'OK',
                    'TotalCount' => 60,
                    'SmsSignList' => array_map(static fn (string $name): array => ['SignName' => $name], $page2Names),
                ]
            );

        $getSmsSignMap = [];
        foreach ($allNames as $name) {
            $getSmsSignMap[] = [
                ['sign_name' => $name],
                [
                    'Code' => 'OK',
                    'SignName' => $name,
                    'SignStatus' => 1,
                    'Remark' => 'remark',
                    'ThirdParty' => false,
                    'QualificationId' => 'q1',
                ],
            ];
        }
        $client->expects($this->exactly(60))
            ->method('getSmsSign')
            ->willReturnMap($getSmsSignMap);

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')->willReturn([]);
        $signRepository->expects($this->exactly(60))->method('create');
        $signRepository->method('lists')->willReturn(['total_count' => 0, 'list' => []]);

        $service = $this->makeService($client, $signRepository);

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(60, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['deleted']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame([], $result['errors']);
    }

    /**
     * TC-03: 同名已存在时云端 status 变化应 update 并保留本地 sign_source。
     */
    public function testTc03ExistingSignUpdatesWhenCloudStatusChanges(): void
    {
        // #given
        $companyId = 100;
        $signName = 'ExistingSign';

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsSignList')->willReturn([
            'Code' => 'OK',
            'SmsSignList' => [['SignName' => $signName]],
        ]);
        $client->method('getSmsSign')->willReturn([
            'Code' => 'OK',
            'SignName' => $signName,
            'SignStatus' => 2,
            'Reason' => ['RejectInfo' => '审核失败原因'],
            'Remark' => 'new remark',
            'ThirdParty' => true,
            'QualificationId' => 'q-new',
        ]);

        $localExisting = [
            'id' => 10,
            'company_id' => $companyId,
            'sign_name' => $signName,
            'sign_source' => '1',
            'status' => 1,
            'reason' => '',
            'third_party' => 0,
            'qualification_id' => 'q-old',
            'remark' => 'old remark',
        ];

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->expects($this->never())->method('create');
        $signRepository->expects($this->once())
            ->method('updateOneBy')
            ->with(
                ['id' => 10, 'company_id' => $companyId],
                $this->callback(function (array $payload) use ($companyId, $signName): bool {
                    return $payload['company_id'] === $companyId
                        && $payload['sign_name'] === $signName
                        && $payload['status'] === 2
                        && $payload['reason'] === '审核失败原因'
                        && $payload['remark'] === 'new remark'
                        && $payload['third_party'] === 1
                        && $payload['qualification_id'] === 'q-new'
                        && $payload['sign_source'] === '1';
                })
            );
        $signRepository->method('lists')->willReturn(['total_count' => 1, 'list' => [$localExisting]]);

        $service = $this->makeService($client, $signRepository);

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['deleted']);
    }

    /**
     * TC-04: 拉取 INIT 签名应入库 status=0。
     */
    public function testTc04InitAuditStatusStoredAsZero(): void
    {
        // #given
        $companyId = 100;
        $signName = 'InitSign';

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsSignList')->willReturn([
            'Code' => 'OK',
            'SmsSignList' => [['SignName' => $signName, 'AuditStatus' => 'AUDIT_STATE_INIT']],
        ]);
        $client->method('getSmsSign')->willReturn([
            'Code' => 'OK',
            'SignName' => $signName,
            'AuditStatus' => 'AUDIT_STATE_INIT',
            'Remark' => 'init remark',
        ]);

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')->willReturn([]);
        $signRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $payload): bool {
                return $payload['status'] === 0 && $payload['sign_name'] === 'InitSign';
            }));
        $signRepository->method('lists')->willReturn(['total_count' => 0, 'list' => []]);

        $service = $this->makeService($client, $signRepository);

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
    }

    /**
     * TC-05: 本地独有且无关联应仅删本地，不调云端 DeleteSmsSign。
     */
    public function testTc05OrphanDeletedLocallyWithoutCloudDelete(): void
    {
        // #given
        $companyId = 100;
        $orphan = [
            'id' => 99,
            'company_id' => $companyId,
            'sign_name' => 'OrphanSign',
            'status' => 1,
        ];

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsSignList')->willReturn([
            'Code' => 'OK',
            'SmsSignList' => [],
        ]);
        $client->expects($this->never())->method('getSmsSign');
        if (method_exists($client, 'deleteSmsSign')) {
            $client->expects($this->never())->method('deleteSmsSign');
        }

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('lists')->willReturn(['total_count' => 1, 'list' => [$orphan]]);
        $signRepository->expects($this->once())->method('deleteById')->with(99);

        $service = $this->makeService($client, $signRepository);

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, $result['skipped']);
    }

    /**
     * TC-05b: 本地独有但有 scene 关联应跳过删除。
     */
    public function testTc05bOrphanWithSceneSkipped(): void
    {
        // #given
        $companyId = 100;
        $orphan = [
            'id' => 88,
            'company_id' => $companyId,
            'sign_name' => 'SceneOrphan',
            'status' => 1,
        ];

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsSignList')->willReturn([
            'Code' => 'OK',
            'SmsSignList' => [],
        ]);

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('lists')->willReturn(['total_count' => 1, 'list' => [$orphan]]);
        $signRepository->expects($this->never())->method('deleteById');

        $service = $this->makeService(
            $client,
            $signRepository,
            null,
            static fn (int $cid, int $sid): bool => $sid === 88
        );

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(0, $result['deleted']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('场景', $result['errors'][0]['message']);
    }

    /**
     * TC-05c: 本地独有 status=0 且无关联仍应删本地（绕过手动审核中门控）。
     */
    public function testTc05cOrphanStatusZeroStillDeleted(): void
    {
        // #given
        $companyId = 100;
        $orphan = [
            'id' => 77,
            'company_id' => $companyId,
            'sign_name' => 'AuditingOrphan',
            'status' => 0,
        ];

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsSignList')->willReturn([
            'Code' => 'OK',
            'SmsSignList' => [],
        ]);

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('lists')->willReturn(['total_count' => 1, 'list' => [$orphan]]);
        $signRepository->expects($this->once())->method('deleteById')->with(77);

        $service = $this->makeService($client, $signRepository);

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, $result['skipped']);
    }

    /**
     * TC-17: GetSmsSign 单条失败时该条记 failed，其它继续。
     */
    public function testTc17GetSmsSignSingleFailureContinuesOthers(): void
    {
        // #given
        $companyId = 100;
        $okSign = 'OkSign';
        $failSign = 'FailSign';

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsSignList')->willReturn([
            'Code' => 'OK',
            'SmsSignList' => [
                ['SignName' => $okSign],
                ['SignName' => $failSign],
            ],
        ]);
        $client->method('getSmsSign')->willReturnCallback(function (array $params) use ($okSign, $failSign) {
            if ($params['sign_name'] === $failSign) {
                throw new \RuntimeException('get sign failed');
            }

            return [
                'Code' => 'OK',
                'SignName' => $okSign,
                'SignStatus' => 1,
            ];
        });

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')->willReturn([]);
        $signRepository->expects($this->once())->method('create');
        $signRepository->method('lists')->willReturn(['total_count' => 0, 'list' => []]);

        $service = $this->makeService($client, $signRepository);

        // #when
        $result = $service->syncSignsFromAliyun($companyId);

        // #then
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame($failSign, $result['errors'][0]['sign_name']);
        $this->assertSame('get sign failed', $result['errors'][0]['message']);
    }
}
