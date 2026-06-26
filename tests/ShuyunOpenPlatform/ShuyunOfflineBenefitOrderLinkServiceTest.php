<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitOrderLinkService;
use TestCase;

class ShuyunOfflineBenefitOrderLinkServiceTest extends TestCase
{
    public function testLinkSetsLocalOrderIdWhenRowFound(): void
    {
        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setRequestId('r');
        $batch->setBenefitId('b');
        $batch->setSendKind('single');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('9');
        $item->setStatus('SUCCESS');
        $item->setBenefitCode('MY-CODE');
        $item->setMemberUserId(100);

        $repo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $repo->expects($this->once())->method('findOneUnlinkedSuccessByCompanyUserAndBenefitCode')
            ->with(1, 100, 'MY-CODE')
            ->willReturn($item);
        $repo->expects($this->once())->method('save')->with($item);

        $svc = new ShuyunOfflineBenefitOrderLinkService($repo);
        $svc->linkCouponToOrder(1, 555, 100, 'MY-CODE');

        $this->assertSame(555, $item->getLocalOrderId());
    }

    public function testLinkNoOpWhenNoRow(): void
    {
        $repo = $this->createMock(ShuyunOfflineBenefitSendItemRepository::class);
        $repo->method('findOneUnlinkedSuccessByCompanyUserAndBenefitCode')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $svc = new ShuyunOfflineBenefitOrderLinkService($repo);
        $svc->linkCouponToOrder(1, 555, 100, 'UNKNOWN');
    }
}
