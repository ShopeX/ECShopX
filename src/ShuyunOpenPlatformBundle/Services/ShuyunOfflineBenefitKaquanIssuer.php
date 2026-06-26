<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;

/**
 * 数云线下权益：按影子权益 {@see ShuyunOfflineBenefit::local_card_id} 调用 Kaquan 发券（Q4）。
 */
final class ShuyunOfflineBenefitKaquanIssuer implements ShuyunOfflineBenefitItemIssuerInterface
{
    public const SOURCE_FROM = '数云线下权益发放';

    public const LOG_CHANNEL = 'shuyun_open_platform';

    public function __construct(
        private ShuyunOfflineBenefitRepository $benefitRepository,
        private ShuyunOfflineBenefitCouponGrantServiceInterface $couponGrant,
        private ShuyunOfflineBenefitIssuingMemberResolverInterface $memberResolver,
    ) {
    }

    public function issue(
        ShuyunOfflineBenefitSendBatch $batch,
        ShuyunOfflineBenefitSendItem $item
    ): ShuyunOfflineBenefitIssueResult {
        $companyId = $batch->getCompanyId();
        $benefit = $this->benefitRepository->findOneByCompanyAndBenefitId($companyId, $batch->getBenefitId());
        if ($benefit === null) {
            return ShuyunOfflineBenefitIssueResult::fail('权益档案不存在');
        }

        $cardId = $benefit->getLocalCardId();
        if ($cardId === null || $cardId <= 0) {
            return ShuyunOfflineBenefitIssueResult::fail('未配置本地券模板(local_card_id)');
        }

        $userId = $this->memberResolver->resolveLocalUserId($companyId, $item->getCustomerId());
        if ($userId === null) {
            return ShuyunOfflineBenefitIssueResult::fail('未找到会员');
        }

        try {
            $row = $this->couponGrant->grantByCardTemplate($companyId, $cardId, $userId, self::SOURCE_FROM);
        } catch (ResourceException $e) {
            return ShuyunOfflineBenefitIssueResult::fail($e->getMessage());
        } catch (\Throwable $e) {
            Log::channel(self::LOG_CHANNEL)->error('Shuyun offline benefit Kaquan issue failed.', [
                'company_id' => $companyId,
                'benefit_id' => $batch->getBenefitId(),
                'card_id' => $cardId,
                'user_id' => $userId,
                'exception' => $e->getMessage(),
            ]);

            return ShuyunOfflineBenefitIssueResult::fail('发券异常');
        }

        $code = isset($row['code']) ? trim((string) $row['code']) : '';
        if ($code === '') {
            return ShuyunOfflineBenefitIssueResult::fail('发券未返回券码');
        }

        return ShuyunOfflineBenefitIssueResult::ok($code, $userId);
    }
}
