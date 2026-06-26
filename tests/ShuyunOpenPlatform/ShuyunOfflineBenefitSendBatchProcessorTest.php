<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitIssueResult;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitItemIssuerInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitReportService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitSendBatchProcessor;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitStubIssuer;
use TestCase;

/** @see .tasks/plans/shuyun-offline-benefit-coupon.md §7 T5/T6（B4/B5/B6） */
class ShuyunOfflineBenefitSendBatchProcessorTest extends TestCase
{
    public function testProcessMarksAllSuccessAndCallsReportWithCounts(): void
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(10);
        $batch->setRequestId('req-a');
        $batch->setBenefitId('ben-1');
        $batch->setSendKind('single');
        $batch->setStatus('pending');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('cust-1');
        $item->setStatus('FAILURE');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('find')->with(42)->willReturn($batch);

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->method('findByBatch')->with($batch)->willReturn([$item]);

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->expects($this->once())->method('pushSendReportV2')->with(
            10,
            'offline',
            'ben-1',
            'req-a',
            1,
            1,
            0
        )->willReturn(true);
        $report->expects($this->once())->method('pushSendResultDetailV2')->with(
            10,
            'offline',
            $this->callback(function (array $rows): bool {
                return \count($rows) === 1
                    && $rows[0]['status'] === 'SUCCESS'
                    && $rows[0]['customerId'] === 'cust-1'
                    && str_starts_with((string) $rows[0]['benefitCode'], 'STUB-');
            })
        )->willReturn(true);

        config([
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
            'shuyun_open_platform.offline_benefit_report_push_max_cycles' => 3,
        ]);

        $processor = new ShuyunOfflineBenefitSendBatchProcessor(
            $batchRepo,
            $itemRepo,
            new ShuyunOfflineBenefitStubIssuer(),
            $report
        );
        $processor->process(42);

        $this->assertSame('done', $batch->getStatus());
        $this->assertSame(1, $batch->getTotalCount());
        $this->assertSame(1, $batch->getSuccessCount());
        $this->assertSame(0, $batch->getFailureCount());
        $this->assertNotNull($batch->getReportPushedAt());
        $this->assertSame(0, $batch->getReportRetryCount());
    }

    public function testIssuerMemberUserIdIsPersistedOnSuccess(): void
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(10);
        $batch->setRequestId('req-m');
        $batch->setBenefitId('ben-m');
        $batch->setSendKind('single');
        $batch->setStatus('pending');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('100');
        $item->setStatus('FAILURE');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('find')->with(99)->willReturn($batch);

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->method('findByBatch')->with($batch)->willReturn([$item]);

        $issuer = $this->createMock(ShuyunOfflineBenefitItemIssuerInterface::class);
        $issuer->method('issue')->willReturn(ShuyunOfflineBenefitIssueResult::ok('REAL-CODE', 555));

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->method('pushSendReportV2')->willReturn(true);
        $report->method('pushSendResultDetailV2')->willReturn(true);

        config([
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
            'shuyun_open_platform.offline_benefit_report_push_max_cycles' => 3,
        ]);

        $processor = new ShuyunOfflineBenefitSendBatchProcessor($batchRepo, $itemRepo, $issuer, $report);
        $processor->process(99);

        $this->assertSame(555, $item->getMemberUserId());
        $this->assertSame('REAL-CODE', $item->getBenefitCode());
    }

    public function testProcessAggregatesPartialFailure(): void
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setRequestId('r2');
        $batch->setBenefitId('b2');
        $batch->setSendKind('batch');
        $batch->setStatus('pending');

        $i1 = new ShuyunOfflineBenefitSendItem();
        $i1->setBatch($batch);
        $i1->setCustomerId('ok');
        $i1->setStatus('FAILURE');
        $i2 = new ShuyunOfflineBenefitSendItem();
        $i2->setBatch($batch);
        $i2->setCustomerId('bad');
        $i2->setStatus('FAILURE');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('find')->with(5)->willReturn($batch);

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->method('findByBatch')->willReturn([$i1, $i2]);

        $issuer = $this->createMock(ShuyunOfflineBenefitItemIssuerInterface::class);
        $issuer->method('issue')->willReturnCallback(function ($b, ShuyunOfflineBenefitSendItem $it): ShuyunOfflineBenefitIssueResult {
            return $it->getCustomerId() === 'bad'
                ? ShuyunOfflineBenefitIssueResult::fail('not_member')
                : ShuyunOfflineBenefitIssueResult::ok('CODE-OK');
        });

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->method('pushSendReportV2')->with(1, 'offline', 'b2', 'r2', 2, 1, 1)->willReturn(true);
        $report->method('pushSendResultDetailV2')->willReturn(true);

        config([
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
            'shuyun_open_platform.offline_benefit_report_push_max_cycles' => 3,
        ]);

        $processor = new ShuyunOfflineBenefitSendBatchProcessor($batchRepo, $itemRepo, $issuer, $report);
        $processor->process(5);

        $this->assertSame(2, $batch->getTotalCount());
        $this->assertSame(1, $batch->getSuccessCount());
        $this->assertSame(1, $batch->getFailureCount());
        $this->assertSame('FAILURE', $i2->getStatus());
        $this->assertNull($i2->getBenefitCode());
        $this->assertSame('not_member', $i2->getFailReason());
    }

    public function testReportPushExhaustsMaxCyclesIncrementsRetryAndSetsLastError(): void
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(10);
        $batch->setRequestId('req-fail');
        $batch->setBenefitId('ben-f');
        $batch->setSendKind('single');
        $batch->setStatus('pending');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('c1');
        $item->setStatus('FAILURE');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('find')->with(8)->willReturn($batch);

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->method('findByBatch')->willReturn([$item]);

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->method('pushSendReportV2')->willReturn(false);
        $report->method('pushSendResultDetailV2')->willReturn(false);

        config([
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
            'shuyun_open_platform.offline_benefit_report_push_max_cycles' => 2,
        ]);

        $processor = new ShuyunOfflineBenefitSendBatchProcessor(
            $batchRepo,
            $itemRepo,
            new ShuyunOfflineBenefitStubIssuer(),
            $report
        );
        $processor->process(8);

        $this->assertSame(2, $batch->getReportRetryCount());
        $this->assertNull($batch->getReportPushedAt());
        $this->assertNotNull($batch->getReportLastError());
        $this->assertStringContainsString('cycle=2/2', (string) $batch->getReportLastError());
        $this->assertStringContainsString('summary=0', (string) $batch->getReportLastError());
    }

    public function testReportPushSucceedsAfterOneFailedCycle(): void
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(10);
        $batch->setRequestId('req-retry-ok');
        $batch->setBenefitId('ben-r');
        $batch->setSendKind('single');
        $batch->setStatus('pending');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('c1');
        $item->setStatus('FAILURE');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('find')->with(9)->willReturn($batch);

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->method('findByBatch')->willReturn([$item]);

        $report = $this->createMock(ShuyunOfflineBenefitReportService::class);
        $report->method('pushSendReportV2')->willReturnOnConsecutiveCalls(false, true);
        $report->method('pushSendResultDetailV2')->willReturnOnConsecutiveCalls(false, true);

        config([
            'shuyun_open_platform.offline_benefit_gateway_platform' => 'offline',
            'shuyun_open_platform.offline_benefit_report_push_max_cycles' => 5,
        ]);

        $processor = new ShuyunOfflineBenefitSendBatchProcessor(
            $batchRepo,
            $itemRepo,
            new ShuyunOfflineBenefitStubIssuer(),
            $report
        );
        $processor->process(9);

        $this->assertSame(1, $batch->getReportRetryCount());
        $this->assertNotNull($batch->getReportPushedAt());
        $this->assertNull($batch->getReportLastError());
    }
}
