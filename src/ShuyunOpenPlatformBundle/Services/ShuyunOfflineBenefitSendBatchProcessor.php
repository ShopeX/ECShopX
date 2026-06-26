<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;

/**
 * 线下权益发送批次：Issuer 履约 → 汇总 → 调用数云报告/明细 V2（Job 内执行）。
 */
class ShuyunOfflineBenefitSendBatchProcessor
{
    public const LOG_CHANNEL = 'shuyun_open_platform';

    public function __construct(
        private ShuyunOfflineBenefitSendBatchRepository $batchRepository,
        private ShuyunOfflineBenefitSendItemRepository $itemRepository,
        private ShuyunOfflineBenefitItemIssuerInterface $issuer,
        private ShuyunOfflineBenefitReportService $reportService,
    ) {
    }

    public function process(int $batchId): void
    {
        $batch = $this->batchRepository->find($batchId);
        if (!$batch instanceof ShuyunOfflineBenefitSendBatch) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun offline benefit send: batch not found.', [
                'batch_id' => $batchId,
            ]);

            return;
        }

        if ($batch->getStatus() !== 'pending') {
            return;
        }

        $batch->setStatus('processing');
        $this->batchRepository->save($batch);

        $items = $this->itemRepository->findByBatch($batch);
        $success = 0;
        $failure = 0;
        $now = time();

        foreach ($items as $item) {
            if (!$item instanceof ShuyunOfflineBenefitSendItem) {
                continue;
            }

            $result = $this->issuer->issue($batch, $item);
            $item->setSendTime($now);
            if ($result->isSuccess()) {
                $item->setStatus('SUCCESS');
                $item->setBenefitCode($result->getBenefitCode());
                $item->setFailReason(null);
                $muid = $result->getMemberUserId();
                if ($muid !== null) {
                    $item->setMemberUserId($muid);
                }
                ++$success;
            } else {
                $item->setStatus('FAILURE');
                $item->setBenefitCode(null);
                $item->setFailReason($result->getFailReason() ?? 'ISSUE_FAILED');
                ++$failure;
            }

            $sendReason = $item->getSendReason();
            if ($sendReason === null || $sendReason === '') {
                $remark = $batch->getSendRemark();
                if ($remark !== null && $remark !== '') {
                    $item->setSendReason($remark);
                }
            }

            $this->itemRepository->save($item);
        }

        $total = \count($items);
        $batch->setTotalCount($total);
        $batch->setSuccessCount($success);
        $batch->setFailureCount($failure);
        $batch->setStatus('done');
        $this->batchRepository->save($batch);

        $platform = strtolower(trim((string) config('shuyun_open_platform.offline_benefit_gateway_platform', 'offline')));
        if ($platform === '') {
            $platform = 'offline';
        }

        $detailRows = $this->buildDetailRows($batch, $items, $now);
        $maxCycles = max(1, (int) config('shuyun_open_platform.offline_benefit_report_push_max_cycles', 3));

        $okSummary = false;
        $okDetail = false;

        for ($cycle = 0; $cycle < $maxCycles; ++$cycle) {
            if (!$okSummary) {
                $okSummary = $this->reportService->pushSendReportV2(
                    $batch->getCompanyId(),
                    $platform,
                    $batch->getBenefitId(),
                    $batch->getRequestId(),
                    $total,
                    $success,
                    $failure
                );
            }

            if (!$okDetail) {
                $okDetail = $this->reportService->pushSendResultDetailV2(
                    $batch->getCompanyId(),
                    $platform,
                    $detailRows
                );
            }

            if ($okSummary && $okDetail) {
                $batch->setReportPushedAt(time());
                $batch->setReportLastError(null);
                $this->batchRepository->save($batch);

                return;
            }

            $batch->setReportRetryCount($batch->getReportRetryCount() + 1);
            $batch->setReportLastError(sprintf(
                'offline_benefit_report_push_failed cycle=%d/%d summary=%s detail=%s',
                $cycle + 1,
                $maxCycles,
                $okSummary ? '1' : '0',
                $okDetail ? '1' : '0'
            ));
            $this->batchRepository->save($batch);
        }
    }

    /**
     * @param  list<ShuyunOfflineBenefitSendItem>  $items
     * @return list<array<string, mixed>>
     */
    private function buildDetailRows(ShuyunOfflineBenefitSendBatch $batch, array $items, int $fallbackTime): array
    {
        $rows = [];
        foreach ($items as $item) {
            if (!$item instanceof ShuyunOfflineBenefitSendItem) {
                continue;
            }
            $ts = $item->getSendTime() ?? $fallbackTime;
            $sendTimeStr = date('Y-m-d H:i:s', $ts);
            $sendReason = $item->getSendReason() ?? $batch->getSendRemark() ?? '';

            $row = [
                'requestId' => $batch->getRequestId(),
                'benefitId' => $batch->getBenefitId(),
                'customerId' => $item->getCustomerId(),
                'benefitCode' => $item->getBenefitCode() ?? '',
                'sendTime' => $sendTimeStr,
                'sendReason' => $sendReason,
                'status' => $item->getStatus(),
            ];
            if ($item->getStatus() === 'FAILURE') {
                $row['failReason'] = $item->getFailReason() ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
