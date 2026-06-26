<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 数云线下权益（回调入站 + 报告/核销出站）Gateway-Action-Method 真源。
 *
 * @see .tasks/plans/shuyun-offline-benefit-coupon.md §3.1、§7 T1
 */
final class ShuyunOfflineBenefitGatewayActions
{
    public const GATEWAY_ACTION_OFFLINE_BENEFIT_CREATE = 'shuyun.offline.benefit.create';

    public const GATEWAY_ACTION_OFFLINE_BENEFIT_SINGLE_SEND = 'shuyun.offline.benefit.single.send';

    public const GATEWAY_ACTION_OFFLINE_BENEFIT_BATCH_SEND = 'shuyun.offline.benefit.batch.send';

    public const GATEWAY_ACTION_SEND_REPORT_PUSH_V2 = 'shuyun.offline.benefit.send.report.push.v2';

    public const GATEWAY_ACTION_SEND_RESULT_DETAIL_PUSH_V2 = 'shuyun.offline.benefit.send.result.detail.push.v2';

    public const GATEWAY_ACTION_RESULT_PUSH_V2 = 'shuyun.offline.benefit.result.push.v2';

    private function __construct()
    {
    }
}
