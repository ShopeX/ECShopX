<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;

/**
 * 联调占位：为每条明细生成确定性伪券码（非真实 Kaquan 发券）。
 */
final class ShuyunOfflineBenefitStubIssuer implements ShuyunOfflineBenefitItemIssuerInterface
{
    public function issue(
        ShuyunOfflineBenefitSendBatch $batch,
        ShuyunOfflineBenefitSendItem $item
    ): ShuyunOfflineBenefitIssueResult {
        $suffix = substr(hash('sha256', $item->getCustomerId().':'.$batch->getBenefitId()), 0, 12);

        return ShuyunOfflineBenefitIssueResult::ok('STUB-'.$suffix);
    }
}
