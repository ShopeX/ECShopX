<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Repositories\DiscountCardsRepository;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;
use ShuyunOpenPlatformBundle\Jobs\ProcessShuyunOfflineBenefitSendBatchJob;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitSendBatchProcessor;
use TestCase;

class ShuyunOfflineBenefitCallbackServiceTest extends TestCase
{
    private function discountCardsRepoReturningOneTemplate(int $cardId): DiscountCardsRepository
    {
        $card = $this->createMock(DiscountCards::class);
        $card->method('getCardId')->willReturn($cardId);

        $repo = $this->createMock(DiscountCardsRepository::class);
        $repo->method('findAllActiveByCompanyIdAndExactTitle')->willReturn([$card]);

        return $repo;
    }

    public function testCreatePersistsNewShadowWithBenefitIdEqualToCardId(): void
    {
        $saved = null;
        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->with(100, '8965421')->willReturn(null);
        $benefitRepo->expects($this->once())->method('save')->willReturnCallback(function (ShuyunOfflineBenefit $e) use (&$saved): void {
            $saved = $e;
        });

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $batchRepo,
            $itemRepo,
            $dispatcher,
            $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class),
            $this->discountCardsRepoReturningOneTemplate(8965421),
        );

        $returnedId = $service->create(100, [
            'benefitName' => '双11',
            'clientId' => '435355',
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
            'getStartTime' => '2018-09-01 00:00:00',
        ]);

        $this->assertSame('8965421', $returnedId);
        $this->assertNotNull($saved);
        $this->assertSame(100, $saved->getCompanyId());
        $this->assertSame('8965421', $saved->getBenefitId());
        $this->assertSame(8965421, $saved->getLocalCardId());
        $this->assertSame('双11', $saved->getBenefitName());
        $this->assertSame('435355', $saved->getClientId());
        $this->assertIsInt($saved->getEffectiveStart());
        $this->assertIsInt($saved->getEffectiveEnd());
    }

    public function testCreateTrimsBenefitNameForTemplateMatch(): void
    {
        $card = $this->createMock(DiscountCards::class);
        $card->method('getCardId')->willReturn(1);

        $discountRepo = $this->createMock(DiscountCardsRepository::class);
        $discountRepo->expects($this->once())->method('findAllActiveByCompanyIdAndExactTitle')->with(100, '双11')->willReturn([$card]);

        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn(null);
        $benefitRepo->expects($this->once())->method('save');

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class),
            $this->createMock(ShuyunOfflineBenefitSendItemRepository::class),
            $this->createMock(Dispatcher::class),
            $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class),
            $discountRepo,
        );

        $this->assertSame('1', $service->create(100, [
            'benefitName' => "  双11  ",
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
        ]));
    }

    public function testCreateThrowsWhenNoCouponTemplateMatchesName(): void
    {
        $discountRepo = $this->createMock(DiscountCardsRepository::class);
        $discountRepo->method('findAllActiveByCompanyIdAndExactTitle')->willReturn([]);

        $service = new ShuyunOfflineBenefitCallbackService(
            $this->createMock(ShuyunOfflineBenefitRepository::class),
            $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class),
            $this->createMock(ShuyunOfflineBenefitSendItemRepository::class),
            $this->createMock(Dispatcher::class),
            $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class),
            $discountRepo,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NO_MATCHING_COUPON_TEMPLATE');
        $service->create(100, [
            'benefitName' => '不存在的券',
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
        ]);
    }

    public function testCreateThrowsWhenMultipleTemplatesMatchTitle(): void
    {
        $c1 = $this->createMock(DiscountCards::class);
        $c2 = $this->createMock(DiscountCards::class);
        $discountRepo = $this->createMock(DiscountCardsRepository::class);
        $discountRepo->method('findAllActiveByCompanyIdAndExactTitle')->willReturn([$c1, $c2]);

        $service = new ShuyunOfflineBenefitCallbackService(
            $this->createMock(ShuyunOfflineBenefitRepository::class),
            $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class),
            $this->createMock(ShuyunOfflineBenefitSendItemRepository::class),
            $this->createMock(Dispatcher::class),
            $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class),
            $discountRepo,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AMBIGUOUS_COUPON_TEMPLATE_MATCH');
        $service->create(100, [
            'benefitName' => '重名券',
            'startTime' => '2018-10-01 00:00:00',
            'endTime' => '2019-10-01 00:00:00',
        ]);
    }

    private function benefitForSend(string $benefitId, int $localCardId): ShuyunOfflineBenefit
    {
        $benefit = new ShuyunOfflineBenefit();
        $benefit->setCompanyId(100);
        $benefit->setBenefitId($benefitId);
        $benefit->setLocalCardId($localCardId);
        $benefit->setBenefitName('x');
        $benefit->setEffectiveStart(1);
        $benefit->setEffectiveEnd(2);

        return $benefit;
    }

    public function testSingleSendCreatesBatchAndItemInTransaction(): void
    {
        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($this->benefitForSend('8965421', 8965421));

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $findCall = 0;
        $batchRepo->method('findOneByCompanyAndRequestId')->willReturnCallback(function () use (&$findCall): ?ShuyunOfflineBenefitSendBatch {
            ++$findCall;
            if ($findCall === 1) {
                return null;
            }
            if ($findCall === 2) {
                $b = new ShuyunOfflineBenefitSendBatch();
                $b->setCompanyId(100);
                $b->setRequestId('req-1');
                $b->setBenefitId('8965421');
                $b->setSendKind('single');
                $b->setStatus('pending');
                $ref = new \ReflectionProperty(ShuyunOfflineBenefitSendBatch::class, 'id');
                $ref->setAccessible(true);
                $ref->setValue($b, 99);

                return $b;
            }
            $done = new ShuyunOfflineBenefitSendBatch();
            $done->setCompanyId(100);
            $done->setRequestId('req-1');
            $done->setBenefitId('8965421');
            $done->setSendKind('single');
            $done->setStatus('done');
            $ref = new \ReflectionProperty(ShuyunOfflineBenefitSendBatch::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($done, 99);

            return $done;
        });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeastOnce())->method('persist');
        $batchRepo->expects($this->once())->method('runInTransaction')->willReturnCallback(function (callable $fn) use ($em) {
            return $fn($em);
        });

        $row = new ShuyunOfflineBenefitSendItem();
        $row->setCustomerId('7895642');
        $row->setBenefitCode('8976680');
        $row->setStatus('SUCCESS');

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->expects($this->once())->method('findByBatch')->willReturn([$row]);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $processor = $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class);
        $processor->expects($this->once())->method('process')->with(99);

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $batchRepo,
            $itemRepo,
            $dispatcher,
            $processor,
            $this->createMock(DiscountCardsRepository::class),
        );
        $payload = $service->singleSend(100, [
            'requestId' => 'req-1',
            'benefitId' => '8965421',
            'customerId' => '7895642',
        ]);

        $this->assertSame('req-1', $payload['batchId']);
        $this->assertSame('8976680', $payload['benefitCode']);
        $this->assertSame('发送成功', $payload['message']);
    }

    public function testSingleSendIdempotentReturnsBenefitCodeWhenBatchDone(): void
    {
        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($this->benefitForSend('8965421', 8965421));

        $existing = new ShuyunOfflineBenefitSendBatch();
        $existing->setCompanyId(100);
        $existing->setRequestId('req-1');
        $existing->setBenefitId('8965421');
        $existing->setSendKind('single');
        $existing->setStatus('done');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('findOneByCompanyAndRequestId')->willReturn($existing);
        $batchRepo->expects($this->never())->method('runInTransaction');

        $row = new ShuyunOfflineBenefitSendItem();
        $row->setCustomerId('7895642');
        $row->setBenefitCode('8976680');
        $row->setStatus('SUCCESS');

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->expects($this->once())->method('findByBatch')->with($existing)->willReturn([$row]);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $processor = $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class);
        $processor->expects($this->never())->method('process');

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $batchRepo,
            $itemRepo,
            $dispatcher,
            $processor,
            $this->createMock(DiscountCardsRepository::class),
        );
        $payload = $service->singleSend(100, [
            'requestId' => 'req-1',
            'benefitId' => '8965421',
            'customerId' => '7895642',
        ]);

        $this->assertSame('req-1', $payload['batchId']);
        $this->assertSame('8976680', $payload['benefitCode']);
        $this->assertSame('发送成功', $payload['message']);
    }

    public function testSingleSendWhenDoneWithFailureUsesFailReasonAsMessage(): void
    {
        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($this->benefitForSend('8965421', 8965421));

        $existing = new ShuyunOfflineBenefitSendBatch();
        $existing->setCompanyId(100);
        $existing->setRequestId('req-1');
        $existing->setBenefitId('8965421');
        $existing->setSendKind('single');
        $existing->setStatus('done');

        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('findOneByCompanyAndRequestId')->willReturn($existing);

        $row = new ShuyunOfflineBenefitSendItem();
        $row->setCustomerId('7895642');
        $row->setBenefitCode(null);
        $row->setStatus('FAILURE');
        $row->setFailReason('库存不足');

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->method('findByBatch')->willReturn([$row]);

        $processor = $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class);
        $processor->expects($this->never())->method('process');

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $batchRepo,
            $itemRepo,
            $this->createMock(Dispatcher::class),
            $processor,
            $this->createMock(DiscountCardsRepository::class),
        );

        $payload = $service->singleSend(100, [
            'requestId' => 'req-1',
            'benefitId' => '8965421',
            'customerId' => '7895642',
        ]);

        $this->assertSame('', $payload['benefitCode']);
        $this->assertSame('库存不足', $payload['message']);
    }

    public function testBatchSendDispatchesJobAndReturnsBatchId(): void
    {
        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($this->benefitForSend('8965421', 8965421));

        $findCall = 0;
        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $batchRepo->method('findOneByCompanyAndRequestId')->willReturnCallback(function () use (&$findCall): ?ShuyunOfflineBenefitSendBatch {
            ++$findCall;
            if ($findCall === 1) {
                return null;
            }
            $b = new ShuyunOfflineBenefitSendBatch();
            $b->setCompanyId(100);
            $b->setRequestId('batch-req-1');
            $b->setBenefitId('8965421');
            $b->setSendKind('batch');
            $b->setStatus('pending');

            return $b;
        });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeastOnce())->method('persist');
        $batchRepo->expects($this->once())->method('runInTransaction')->willReturnCallback(function (callable $fn) use ($em) {
            return $fn($em);
        });

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $itemRepo->expects($this->never())->method('findByBatch');

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch')->with($this->callback(function (object $job): bool {
            return $job instanceof ProcessShuyunOfflineBenefitSendBatchJob
                && $job->companyId === 100
                && $job->requestId === 'batch-req-1';
        }));

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $batchRepo,
            $itemRepo,
            $dispatcher,
            $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class),
            $this->createMock(DiscountCardsRepository::class),
        );
        $payload = $service->batchSend(100, [
            'requestId' => 'batch-req-1',
            'benefitId' => '8965421',
            'customerList' => ['c1', 'c2'],
        ]);

        $this->assertSame('batch-req-1', $payload['batchId']);
        $this->assertStringContainsString('异步', $payload['message']);
    }

    public function testBatchSendRejectsMoreThan1000Customers(): void
    {
        $b = $this->benefitForSend('1', 1);
        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($b);
        $batchRepo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);

        $itemRepo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $service = new ShuyunOfflineBenefitCallbackService(
            $benefitRepo,
            $batchRepo,
            $itemRepo,
            $dispatcher,
            $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class),
            $this->createMock(DiscountCardsRepository::class),
        );
        $list = array_map(static fn (int $i): string => (string) $i, range(1, 1001));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('1000');
        $service->batchSend(100, [
            'requestId' => 'r',
            'benefitId' => '1',
            'customerList' => $list,
        ]);
    }
}
