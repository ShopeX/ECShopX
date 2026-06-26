<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitGatewayActions;

/** @see .tasks/plans/shuyun-offline-benefit-coupon.md §7 T1 */
class ShuyunOfflineBenefitGatewayActionsTest extends \TestCase
{
    public function testInboundActionConstantsMatchPlan(): void
    {
        $this->assertSame('shuyun.offline.benefit.create', ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_OFFLINE_BENEFIT_CREATE);
        $this->assertSame('shuyun.offline.benefit.single.send', ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_OFFLINE_BENEFIT_SINGLE_SEND);
        $this->assertSame('shuyun.offline.benefit.batch.send', ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_OFFLINE_BENEFIT_BATCH_SEND);
    }

    public function testOutboundV2ActionConstantsMatchPlan(): void
    {
        $this->assertSame('shuyun.offline.benefit.send.report.push.v2', ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_REPORT_PUSH_V2);
        $this->assertSame('shuyun.offline.benefit.send.result.detail.push.v2', ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_SEND_RESULT_DETAIL_PUSH_V2);
        $this->assertSame('shuyun.offline.benefit.result.push.v2', ShuyunOfflineBenefitGatewayActions::GATEWAY_ACTION_RESULT_PUSH_V2);
    }
}
