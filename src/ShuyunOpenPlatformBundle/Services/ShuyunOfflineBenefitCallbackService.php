<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use KaquanBundle\Repositories\DiscountCardsRepository;
use ShuyunOpenPlatformBundle\Jobs\ProcessShuyunOfflineBenefitSendBatchJob;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;

/**
 * 数云线下权益入站回调业务：create 影子落库；single/batch 落批次与明细（幂等键 company_id + request_id）。
 *
 * 权益创建（方案 A）：`data.benefitId` = 商城券模板 {@see \KaquanBundle\Entities\DiscountCards::card_id} 字符串，与 {@see ShuyunOfflineBenefit::local_card_id} 一致；按 `benefitName` 与券模板 `title` 精确匹配（仅正常状态券）。
 */
class ShuyunOfflineBenefitCallbackService
{
    private const BATCH_SEND_KIND_SINGLE = 'single';

    private const BATCH_SEND_KIND_BATCH = 'batch';

    private const MAX_BATCH_CUSTOMERS = 1000;

    public function __construct(
        private ShuyunOfflineBenefitRepository $benefitRepository,
        private ShuyunOfflineBenefitSendBatchRepository $batchRepository,
        private ShuyunOfflineBenefitSendItemRepository $sendItemRepository,
        private Dispatcher $dispatcher,
        private ShuyunOfflineBenefitSendBatchProcessor $sendBatchProcessor,
        private DiscountCardsRepository $discountCardsRepository,
    ) {
    }

    /**
     * 落库权益影子；返回 `benefitId`（券模板 card_id 字符串），供创建回调 HTTP 响应 {@see ShuyunOfflineBenefitCallbackController::create} 填入 `data.benefitId`。
     *
     * @param  array<string, mixed>  $body
     */
    public function create(int $companyId, array $body): string
    {
        $benefitName = $this->stringFromBody($body, 'benefitName');
        if ($benefitName === null || $benefitName === '') {
            throw new \InvalidArgumentException('benefitName required');
        }

        $title = trim($benefitName);
        $candidates = $this->discountCardsRepository->findAllActiveByCompanyIdAndExactTitle($companyId, $title);
        if ($candidates === []) {
            throw new \InvalidArgumentException('NO_MATCHING_COUPON_TEMPLATE');
        }
        if (\count($candidates) > 1) {
            throw new \InvalidArgumentException('AMBIGUOUS_COUPON_TEMPLATE_MATCH');
        }

        $card = $candidates[0];
        $cardId = $card->getCardId();
        if ($cardId === null || (int) $cardId <= 0) {
            throw new \InvalidArgumentException('INVALID_COUPON_TEMPLATE');
        }

        $benefitId = (string) (int) $cardId;

        $startTime = $this->stringFromBody($body, 'startTime');
        $endTime = $this->stringFromBody($body, 'endTime');
        if ($startTime === null || $startTime === '' || $endTime === null || $endTime === '') {
            throw new \InvalidArgumentException('startTime and endTime required');
        }

        $effectiveStart = $this->parseDateTimeToUnix($startTime);
        $effectiveEnd = $this->parseDateTimeToUnix($endTime);
        if ($effectiveStart === null || $effectiveEnd === null) {
            throw new \InvalidArgumentException('invalid startTime or endTime');
        }

        $clientId = $this->stringFromBody($body, 'clientId');
        $getStartRaw = $this->stringFromBody($body, 'getStartTime');
        $getEndRaw = $this->stringFromBody($body, 'getEndTime');

        $blobKeys = ['conditionGroup', 'limitShops', 'limitCustomers', 'actionType', 'actionValue', 'quantity', 'canGiftGiving', 'limitExchangeNum'];
        $blob = [];
        foreach ($blobKeys as $k) {
            if (\array_key_exists($k, $body)) {
                $blob[$k] = $body[$k];
            }
        }
        $conditionJson = $blob !== [] ? json_encode($blob, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;

        $existing = $this->benefitRepository->findOneByCompanyAndBenefitId($companyId, $benefitId);
        $entity = $existing ?? new ShuyunOfflineBenefit();
        $entity->setCompanyId($companyId);
        $entity->setBenefitId($benefitId);
        $entity->setLocalCardId((int) $cardId);
        if ($clientId !== null) {
            $entity->setClientId($clientId);
        }
        $entity->setBenefitName($benefitName);
        $entity->setEffectiveStart($effectiveStart);
        $entity->setEffectiveEnd($effectiveEnd);
        $entity->setClaimStart($getStartRaw !== null && $getStartRaw !== '' ? $this->parseDateTimeToUnix($getStartRaw) : null);
        $entity->setClaimEnd($getEndRaw !== null && $getEndRaw !== '' ? $this->parseDateTimeToUnix($getEndRaw) : null);
        $entity->setConditionLimitsJson($conditionJson);

        $this->benefitRepository->save($entity);

        return $benefitId;
    }

    /**
     * 单笔发送：同步履约（本请求内完成发券与批次汇总）；HTTP 返回 `data.batchId`（请求 `requestId`）与 `data.benefitCode`（成功非空，失败为空并在 message 带原因）。
     *
     * @param  array<string, mixed>  $body
     * @return array{batchId: string, benefitCode: string, message: string}
     */
    public function singleSend(int $companyId, array $body): array
    {
        $requestId = $this->stringFromBody($body, 'requestId');
        if ($requestId === null || $requestId === '') {
            throw new \InvalidArgumentException('requestId required');
        }

        $benefitId = $this->stringFromBody($body, 'benefitId');
        if ($benefitId === null || $benefitId === '') {
            throw new \InvalidArgumentException('benefitId required');
        }

        $customerId = $this->stringFromBody($body, 'customerId');
        if ($customerId === null || $customerId === '') {
            throw new \InvalidArgumentException('customerId required');
        }

        if (!$this->benefitExists($companyId, $benefitId)) {
            throw new \InvalidArgumentException('benefit not found');
        }

        $existing = $this->batchRepository->findOneByCompanyAndRequestId($companyId, $requestId);
        if ($existing !== null) {
            $reloaded = $this->finishSingleSendBatchIfPending($existing);

            return $this->buildSingleSendHttpPayload($reloaded ?? $existing);
        }

        $this->batchRepository->runInTransaction(function (EntityManagerInterface $em) use ($companyId, $benefitId, $requestId, $body, $customerId): void {
            $batch = new ShuyunOfflineBenefitSendBatch();
            $batch->setCompanyId($companyId);
            $batch->setRequestId($requestId);
            $batch->setBenefitId($benefitId);
            $batch->setSendKind(self::BATCH_SEND_KIND_SINGLE);
            $batch->setStatus('pending');
            $this->applyOptionalSendFields($batch, $body);

            $item = new ShuyunOfflineBenefitSendItem();
            $item->setBatch($batch);
            $item->setCustomerId($customerId);
            $item->setStatus('FAILURE');

            $em->persist($batch);
            $em->persist($item);
        });

        $batch = $this->batchRepository->findOneByCompanyAndRequestId($companyId, $requestId);
        if ($batch === null) {
            throw new \RuntimeException('single send: batch missing after persist');
        }

        $batchPk = $batch->getId();
        if ($batchPk === null) {
            throw new \RuntimeException('single send: batch id missing after persist');
        }

        $this->sendBatchProcessor->process($batchPk);

        $batch = $this->batchRepository->findOneByCompanyAndRequestId($companyId, $requestId);
        if ($batch === null) {
            throw new \RuntimeException('single send: batch missing after process');
        }

        return $this->buildSingleSendHttpPayload($batch);
    }

    /**
     * 幂等重试：若历史批次仍为 pending，则在本请求内继续履约（例如首次请求中途异常未走到 process）。
     */
    private function finishSingleSendBatchIfPending(ShuyunOfflineBenefitSendBatch $batch): ?ShuyunOfflineBenefitSendBatch
    {
        if ($batch->getStatus() !== 'pending') {
            return null;
        }

        $batchPk = $batch->getId();
        if ($batchPk === null) {
            return null;
        }

        $this->sendBatchProcessor->process($batchPk);

        return $this->batchRepository->find($batchPk) ?? $batch;
    }

    /**
     * @return array{batchId: string, benefitCode: string, message: string}
     */
    private function buildSingleSendHttpPayload(ShuyunOfflineBenefitSendBatch $batch): array
    {
        $batchId = $batch->getRequestId();
        $status = $batch->getStatus();

        if ($status === 'pending' || $status === 'processing') {
            return [
                'batchId' => $batchId,
                'benefitCode' => '',
                'message' => '异步发放处理中，请稍后重试本接口或依赖数云明细推送获取结果',
            ];
        }

        $benefitCode = '';
        if ($status === 'done') {
            foreach ($this->sendItemRepository->findByBatch($batch) as $item) {
                $code = $item->getBenefitCode();
                if ($code !== null && $code !== '') {
                    $benefitCode = $code;
                    break;
                }
            }
        }

        $message = '发送成功';
        if ($status === 'done' && $benefitCode === '') {
            $fail = $this->firstFailureReasonOnBatch($batch);
            $message = $fail !== '' ? $fail : '发券失败';
        }

        return [
            'batchId' => $batchId,
            'benefitCode' => $benefitCode,
            'message' => $message,
        ];
    }

    /**
     * 批量发送：HTTP 仅回 `data.batchId`（与请求 `requestId` 一致）；每人成败与券码由 Job 完成后 **send.report.push.v2** / **send.result.detail.push.v2** 同步数云。
     *
     * @param  array<string, mixed>  $body
     * @return array{batchId: string, message: string}
     */
    public function batchSend(int $companyId, array $body): array
    {
        $requestId = $this->stringFromBody($body, 'requestId');
        if ($requestId === null || $requestId === '') {
            throw new \InvalidArgumentException('requestId required');
        }

        $benefitId = $this->stringFromBody($body, 'benefitId');
        if ($benefitId === null || $benefitId === '') {
            throw new \InvalidArgumentException('benefitId required');
        }

        $customerList = $body['customerList'] ?? null;
        if (!\is_array($customerList)) {
            throw new \InvalidArgumentException('customerList must be array');
        }
        if (\count($customerList) === 0) {
            throw new \InvalidArgumentException('customerList must not be empty');
        }
        if (\count($customerList) > self::MAX_BATCH_CUSTOMERS) {
            throw new \InvalidArgumentException('customerList exceeds '.self::MAX_BATCH_CUSTOMERS);
        }

        if (!$this->benefitExists($companyId, $benefitId)) {
            throw new \InvalidArgumentException('benefit not found');
        }

        $existing = $this->batchRepository->findOneByCompanyAndRequestId($companyId, $requestId);
        if ($existing !== null) {
            return $this->buildBatchSendHttpPayload($existing);
        }

        $this->batchRepository->runInTransaction(function (EntityManagerInterface $em) use ($companyId, $benefitId, $requestId, $body, $customerList): void {
            $batch = new ShuyunOfflineBenefitSendBatch();
            $batch->setCompanyId($companyId);
            $batch->setRequestId($requestId);
            $batch->setBenefitId($benefitId);
            $batch->setSendKind(self::BATCH_SEND_KIND_BATCH);
            $batch->setStatus('pending');
            $this->applyOptionalSendFields($batch, $body);

            $em->persist($batch);

            foreach ($customerList as $cid) {
                if (!\is_string($cid) && !\is_int($cid)) {
                    throw new \InvalidArgumentException('customerList entries must be string or int');
                }
                $cidStr = (string) $cid;
                if ($cidStr === '') {
                    throw new \InvalidArgumentException('empty customerId in customerList');
                }
                $item = new ShuyunOfflineBenefitSendItem();
                $item->setBatch($batch);
                $item->setCustomerId($cidStr);
                $item->setStatus('FAILURE');
                $em->persist($item);
            }
        });

        $this->dispatcher->dispatch(
            (new ProcessShuyunOfflineBenefitSendBatchJob($companyId, $requestId))->onQueue('slow')
        );

        $batch = $this->batchRepository->findOneByCompanyAndRequestId($companyId, $requestId);
        if ($batch === null) {
            throw new \RuntimeException('batch send: batch missing after persist');
        }

        return $this->buildBatchSendHttpPayload($batch);
    }

    /**
     * @return array{batchId: string, message: string}
     */
    private function buildBatchSendHttpPayload(ShuyunOfflineBenefitSendBatch $batch): array
    {
        $batchId = $batch->getRequestId();
        $status = $batch->getStatus();

        if ($status === 'pending' || $status === 'processing') {
            return [
                'batchId' => $batchId,
                'message' => '异步批量发放处理中，请稍后重试或依赖数云明细推送获取结果',
            ];
        }

        if ($status === 'done') {
            $fail = (int) ($batch->getFailureCount() ?? 0);
            $ok = (int) ($batch->getSuccessCount() ?? 0);
            if ($fail === 0) {
                return ['batchId' => $batchId, 'message' => '发送成功'];
            }
            $firstFail = $this->firstFailureReasonOnBatch($batch);

            return [
                'batchId' => $batchId,
                'message' => $firstFail !== '' ? $firstFail : sprintf('发放完成：成功%d笔，失败%d笔', $ok, $fail),
            ];
        }

        return ['batchId' => $batchId, 'message' => '发送成功'];
    }

    private function firstFailureReasonOnBatch(ShuyunOfflineBenefitSendBatch $batch): string
    {
        foreach ($this->sendItemRepository->findByBatch($batch) as $item) {
            if ($item->getStatus() === 'FAILURE') {
                $r = $item->getFailReason();
                if ($r !== null && $r !== '') {
                    return $r;
                }
            }
        }

        return '';
    }

    private function benefitExists(int $companyId, string $benefitId): bool
    {
        $row = $this->benefitRepository->findOneByCompanyAndBenefitId($companyId, $benefitId);
        if ($row === null) {
            return false;
        }
        $local = $row->getLocalCardId();
        if ($local === null || $local <= 0) {
            return false;
        }

        return (string) $local === $benefitId;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function applyOptionalSendFields(ShuyunOfflineBenefitSendBatch $batch, array $body): void
    {
        $sendTimeRaw = $this->stringFromBody($body, 'sendTime');
        if ($sendTimeRaw !== null && $sendTimeRaw !== '') {
            $batch->setSendTime($this->parseDateTimeToUnix($sendTimeRaw));
        }

        $expireTimeRaw = $this->stringFromBody($body, 'expireTime');
        if ($expireTimeRaw !== null && $expireTimeRaw !== '') {
            $batch->setExpireTime($this->parseDateTimeToUnix($expireTimeRaw));
        }

        $remark = $this->stringFromBody($body, 'sendRemark');
        if ($remark !== null) {
            $batch->setSendRemark($remark);
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function stringFromBody(array $body, string $key): ?string
    {
        if (!\array_key_exists($key, $body)) {
            return null;
        }
        $v = $body[$key];
        if ($v === null) {
            return null;
        }
        if (\is_scalar($v)) {
            return (string) $v;
        }

        return null;
    }

    private function parseDateTimeToUnix(string $value): ?int
    {
        $t = strtotime($value);
        if ($t === false) {
            return null;
        }

        return $t;
    }
}
