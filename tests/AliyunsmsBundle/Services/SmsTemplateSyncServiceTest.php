<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Services;

use AliyunsmsBundle\Repositories\TemplateRepository;
use AliyunsmsBundle\Services\SmsTemplateSyncService;
use PHPUnit\Framework\TestCase;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class SmsTemplateSyncServiceTest extends TestCase
{
    private function makeService(
        AliyunSmsClient $client,
        TemplateRepository $templateRepository,
        ?callable $hasSceneAssociation = null,
        ?callable $hasActiveTask = null
    ): SmsTemplateSyncService {
        return new SmsTemplateSyncService(
            static function (int $companyId) use ($client): AliyunSmsClient {
                return $client;
            },
            $templateRepository,
            $hasSceneAssociation ?? static fn (int $companyId, int $templateId): bool => false,
            $hasActiveTask ?? static fn (int $companyId, int $templateId): bool => false
        );
    }

    public function testSyncCreatesTemplateWithUnassignedSceneId(): void
    {
        $companyId = 38;
        $templateCode = 'SMS_100001';

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsTemplateList')->willReturn([
            'Code' => 'OK',
            'SmsTemplateList' => [
                ['TemplateCode' => $templateCode],
            ],
        ]);
        $client->method('getSmsTemplate')->willReturn([
            'Code' => 'OK',
            'TemplateCode' => $templateCode,
            'TemplateName' => '订单通知模板',
            'TemplateType' => 1,
            'TemplateContent' => '您的订单${order_no}已发货',
            'RelatedSignName' => '商派软件',
            'TemplateStatus' => 1,
            'AuditInfo' => ['RejectInfo' => ''],
        ]);

        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $payload) use ($companyId, $templateCode): bool {
                return $payload['company_id'] === $companyId
                    && $payload['template_code'] === $templateCode
                    && $payload['scene_id'] === 0
                    && $payload['status'] === 1;
            }));
        $repository->method('lists')->willReturn(['total_count' => 0, 'list' => []]);

        $service = $this->makeService($client, $repository);
        $result = $service->syncTemplatesFromAliyun($companyId);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['deleted']);
    }

    public function testSyncKeepsExistingSceneIdOnUpdate(): void
    {
        $companyId = 38;
        $templateCode = 'SMS_100002';
        $existing = [
            'id' => 12,
            'company_id' => $companyId,
            'template_code' => $templateCode,
            'scene_id' => 56,
            'status' => 2,
        ];

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsTemplateList')->willReturn([
            'Code' => 'OK',
            'SmsTemplateList' => [
                ['TemplateCode' => $templateCode],
            ],
        ]);
        $client->method('getSmsTemplate')->willReturn([
            'Code' => 'OK',
            'TemplateCode' => $templateCode,
            'TemplateName' => '物流通知模板',
            'TemplateType' => 1,
            'TemplateContent' => '包裹${package_no}运输中',
            'RelatedSignName' => '商派软件',
            'TemplateStatus' => 1,
            'AuditInfo' => ['RejectInfo' => ''],
        ]);

        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects($this->once())
            ->method('updateOneBy')
            ->with(
                ['id' => 12, 'company_id' => $companyId],
                $this->callback(function (array $payload): bool {
                    return $payload['scene_id'] === 56 && $payload['status'] === 1;
                })
            );
        $repository->method('lists')->willReturn(['total_count' => 1, 'list' => [$existing]]);

        $service = $this->makeService($client, $repository);
        $result = $service->syncTemplatesFromAliyun($companyId);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
    }

    public function testSyncDeletesLocalOrphanWithoutReferencesEvenWhenPending(): void
    {
        $companyId = 38;
        $orphan = [
            'id' => 77,
            'company_id' => $companyId,
            'template_code' => 'SMS_ORPHAN',
            'scene_id' => 0,
            'status' => 0,
        ];

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsTemplateList')->willReturn([
            'Code' => 'OK',
            'SmsTemplateList' => [],
        ]);

        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects($this->once())->method('deleteById')->with(77);
        $repository->method('lists')->willReturn(['total_count' => 1, 'list' => [$orphan]]);

        $service = $this->makeService($client, $repository);
        $result = $service->syncTemplatesFromAliyun($companyId);

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, $result['skipped']);
    }

    public function testSyncSkipsLocalOrphanWhenSceneReferenced(): void
    {
        $companyId = 38;
        $orphan = [
            'id' => 78,
            'company_id' => $companyId,
            'template_code' => 'SMS_REFERENCED',
            'scene_id' => 0,
            'status' => 1,
        ];

        $client = $this->createMock(AliyunSmsClient::class);
        $client->method('querySmsTemplateList')->willReturn([
            'Code' => 'OK',
            'SmsTemplateList' => [],
        ]);

        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects($this->never())->method('deleteById');
        $repository->method('lists')->willReturn(['total_count' => 1, 'list' => [$orphan]]);

        $service = $this->makeService(
            $client,
            $repository,
            static fn (int $cid, int $templateId): bool => $templateId === 78
        );
        $result = $service->syncTemplatesFromAliyun($companyId);

        $this->assertSame(0, $result['deleted']);
        $this->assertSame(1, $result['skipped']);
    }
}
