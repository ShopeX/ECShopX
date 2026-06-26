<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;

/**
 * 数云线下权益：将批次内一行转为本地券实例。
 * 生产默认 {@see ShuyunOfflineBenefitKaquanIssuer}；联调可配置 `shuyun_open_platform.offline_benefit_issuer=stub`。
 */
interface ShuyunOfflineBenefitItemIssuerInterface
{
    public function issue(
        ShuyunOfflineBenefitSendBatch $batch,
        ShuyunOfflineBenefitSendItem $item
    ): ShuyunOfflineBenefitIssueResult;
}
