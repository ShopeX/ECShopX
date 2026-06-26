<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Jobs;

use EspierBundle\Jobs\Job;
use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitSendBatchProcessor;

/**
 * 异步处理单笔/批量发送回调落库后的发券与数云报告推送。
 *
 * @see .tasks/plans/shuyun-offline-benefit-coupon.md §7 T5
 */
class ProcessShuyunOfflineBenefitSendBatchJob extends Job
{
    public function __construct(
        public int $companyId,
        public string $requestId,
    ) {
    }

    public function handle(): bool
    {
        $ch = ShuyunOfflineBenefitSendBatchProcessor::LOG_CHANNEL;
        Log::channel($ch)->info('Shuyun offline benefit send: job started.', [
            'company_id' => $this->companyId,
            'request_id' => $this->requestId,
        ]);

        $repo = app(ShuyunOfflineBenefitSendBatchRepository::class);
        $batch = $repo->findOneByCompanyAndRequestId($this->companyId, $this->requestId);
        if ($batch === null) {
            Log::channel($ch)->warning('Shuyun offline benefit send: job ended, batch not found.', [
                'company_id' => $this->companyId,
                'request_id' => $this->requestId,
            ]);

            return true;
        }

        $id = $batch->getId();
        if ($id === null) {
            return true;
        }

        app(ShuyunOfflineBenefitSendBatchProcessor::class)->process($id);

        return true;
    }
}
