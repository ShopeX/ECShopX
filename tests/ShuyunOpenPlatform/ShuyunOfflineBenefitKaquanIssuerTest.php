<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Dingo\Api\Exception\ResourceException;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitCouponGrantServiceInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitIssuingMemberResolverInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitKaquanIssuer;
use TestCase;

class ShuyunOfflineBenefitKaquanIssuerTest extends TestCase
{
    public function testIssueOkSetsCodeAndMember(): void
    {
        $benefit = new ShuyunOfflineBenefit();
        $benefit->setCompanyId(1);
        $benefit->setBenefitId('b1');
        $benefit->setLocalCardId(99);

        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->with(1, 'b1')->willReturn($benefit);

        $memberResolver = $this->createMock(ShuyunOfflineBenefitIssuingMemberResolverInterface::class);
        $memberResolver->method('resolveLocalUserId')->with(1, 'c9')->willReturn(100);

        $grant = $this->createMock(ShuyunOfflineBenefitCouponGrantServiceInterface::class);
        $grant->expects($this->once())->method('grantByCardTemplate')->with(
            1,
            99,
            100,
            ShuyunOfflineBenefitKaquanIssuer::SOURCE_FROM
        )->willReturn(['code' => 'COUPON-XYZ']);

        $issuer = new ShuyunOfflineBenefitKaquanIssuer($benefitRepo, $grant, $memberResolver);

        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setBenefitId('b1');
        $batch->setRequestId('r1');
        $batch->setSendKind('single');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('c9');

        $r = $issuer->issue($batch, $item);
        $this->assertTrue($r->isSuccess());
        $this->assertSame('COUPON-XYZ', $r->getBenefitCode());
        $this->assertSame(100, $r->getMemberUserId());
    }

    public function testIssueFailsWhenMemberNotResolved(): void
    {
        $benefit = new ShuyunOfflineBenefit();
        $benefit->setCompanyId(1);
        $benefit->setBenefitId('b1');
        $benefit->setLocalCardId(1);

        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($benefit);

        $memberResolver = $this->createMock(ShuyunOfflineBenefitIssuingMemberResolverInterface::class);
        $memberResolver->method('resolveLocalUserId')->willReturn(null);

        $grant = $this->createMock(ShuyunOfflineBenefitCouponGrantServiceInterface::class);
        $grant->expects($this->never())->method('grantByCardTemplate');

        $issuer = new ShuyunOfflineBenefitKaquanIssuer($benefitRepo, $grant, $memberResolver);

        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setBenefitId('b1');
        $batch->setRequestId('r1');
        $batch->setSendKind('single');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('not-numeric');

        $r = $issuer->issue($batch, $item);
        $this->assertFalse($r->isSuccess());
        $this->assertStringContainsString('未找到会员', (string) $r->getFailReason());
    }

    public function testIssueFailsWhenLocalCardIdMissing(): void
    {
        $benefit = new ShuyunOfflineBenefit();
        $benefit->setCompanyId(1);
        $benefit->setBenefitId('b1');
        $benefit->setLocalCardId(null);

        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($benefit);

        $memberResolver = $this->createMock(ShuyunOfflineBenefitIssuingMemberResolverInterface::class);
        $memberResolver->expects($this->never())->method('resolveLocalUserId');

        $grant = $this->createMock(ShuyunOfflineBenefitCouponGrantServiceInterface::class);
        $grant->expects($this->never())->method('grantByCardTemplate');

        $issuer = new ShuyunOfflineBenefitKaquanIssuer($benefitRepo, $grant, $memberResolver);

        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setBenefitId('b1');
        $batch->setRequestId('r1');
        $batch->setSendKind('single');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('1');

        $r = $issuer->issue($batch, $item);
        $this->assertFalse($r->isSuccess());
        $this->assertStringContainsString('local_card_id', (string) $r->getFailReason());
    }

    public function testIssueMapsResourceExceptionToFail(): void
    {
        $benefit = new ShuyunOfflineBenefit();
        $benefit->setCompanyId(1);
        $benefit->setBenefitId('b1');
        $benefit->setLocalCardId(5);

        $benefitRepo = $this->createMock(ShuyunOfflineBenefitRepository::class);
        $benefitRepo->method('findOneByCompanyAndBenefitId')->willReturn($benefit);

        $memberResolver = $this->createMock(ShuyunOfflineBenefitIssuingMemberResolverInterface::class);
        $memberResolver->method('resolveLocalUserId')->willReturn(10);

        $grant = $this->createMock(ShuyunOfflineBenefitCouponGrantServiceInterface::class);
        $grant->method('grantByCardTemplate')->willThrowException(new ResourceException('券已领完'));

        $issuer = new ShuyunOfflineBenefitKaquanIssuer($benefitRepo, $grant, $memberResolver);

        $batch = new ShuyunOfflineBenefitSendBatch();
        $batch->setCompanyId(1);
        $batch->setBenefitId('b1');
        $batch->setRequestId('r1');
        $batch->setSendKind('single');

        $item = new ShuyunOfflineBenefitSendItem();
        $item->setBatch($batch);
        $item->setCustomerId('10');

        $r = $issuer->issue($batch, $item);
        $this->assertFalse($r->isSuccess());
        $this->assertStringContainsString('券已领完', (string) $r->getFailReason());
    }
}
