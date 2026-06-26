<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Support;

/**
 * 需入站 JSON 验签的数云回调：共用 {@see ShuyunOpenPlatformInboundSignedCallbackPreparer}，各模式错误 HTTP 体仍按接口文档区分。
 */
enum ShuyunInboundSignedPrepareMode
{
    /** 会员等级变更（msg/code/success 字符串码） */
    case LoyaltyMemberGradeChange;

    /** 线下权益 create / single / batch（code 数值 + message/msg） */
    case OfflineBenefit;
}
