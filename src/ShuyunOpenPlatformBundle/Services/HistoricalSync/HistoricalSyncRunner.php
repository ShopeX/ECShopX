<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

use DistributionBundle\Entities\Distributor;
use DistributionBundle\Repositories\DistributorRepository;
use Doctrine\DBAL\Connection;
use Illuminate\Contracts\Bus\Dispatcher;
use MembersBundle\Services\MemberService;
use ShuyunOpenPlatformBundle\Jobs\SyncNormalOrderRefundToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Jobs\SyncNormalOrderTradeToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Jobs\SyncShopToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformCategorySyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberBindPushService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberRegisterService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformProductSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
final class HistoricalSyncRunner
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CompanyShuyunOpenPlatformConfigRepository $configRepository,
        private readonly ShuyunOpenPlatformShopSyncService $shopSyncService,
        private readonly HistoricalSyncCheckpointStore $checkpointStore,
        private readonly ShuyunOpenPlatformCategorySyncService $categorySyncService,
        private readonly ShuyunOpenPlatformProductSyncService $productSyncService,
        private readonly ShuyunOpenPlatformMemberRegisterService $memberRegisterService,
        private readonly ShuyunOpenPlatformMemberBindPushService $memberBindPushService,
        private readonly MemberService $memberService,
        private readonly HistoricalSyncPointBalanceAligner $pointBalanceAligner,
        private readonly HistoricalSyncWechatBindResolver $wechatBindResolver,
        private readonly Dispatcher $dispatcher,
    ) {
    }

    /**
     * @return list<HistoricalSyncStepResult>
     */
    public function run(HistoricalSyncRunOptions $options, ?HistoricalSyncFailureRecorder $failures = null): array
    {
        $this->assertEligible($options->companyId);

        $limiter = $options->rate > 0 ? new HistoricalSyncRateLimiter($options->rate) : null;
        $results = [];
        $pauseAfterShops = in_array(HistoricalSyncSteps::SHOPS, $options->steps, true)
            && count($options->steps) > 1
            && ! $options->assumeCardBound;

        foreach ($options->steps as $step) {
            if ($pauseAfterShops && $step !== HistoricalSyncSteps::SHOPS && ! $options->assumeCardBound) {
                // 仅提示；CLI 层可在此前已确认
            }
            $results[] = match ($step) {
                HistoricalSyncSteps::SHOPS => $this->runShops($options, $limiter, $failures),
                HistoricalSyncSteps::CATEGORIES => $this->runCategories($options, $limiter, $failures),
                HistoricalSyncSteps::PRODUCTS => $this->runProducts($options, $limiter, $failures),
                HistoricalSyncSteps::MEMBERS => $this->runMembers($options, $limiter, $failures),
                HistoricalSyncSteps::ORDERS => $this->runOrders($options, $limiter, $failures),
                HistoricalSyncSteps::REFUNDS => $this->runRefunds($options, $limiter, $failures),
                HistoricalSyncSteps::POINTS => $this->runPoints($options, $limiter, $failures),
                default => new HistoricalSyncStepResult($step, 0, 0, 0, 0),
            };
        }

        return $results;
    }

    private function assertEligible(int $companyId): void
    {
        $cfg = $this->configRepository->findOneByCompanyId($companyId);
        if (! $this->shopSyncService->isEligible($cfg)) {
            throw new \RuntimeException('数云开放网关未就绪（isEligible=false），请先完成配置与 Token。');
        }
    }

    private function runShops(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $rows = $this->fetchDistributorIds($options);
        return $this->countedLoop(HistoricalSyncSteps::SHOPS, $rows, $options, $limiter, $failures, function (array $row) use ($options): bool {
            $distributorId = (int) $row['distributor_id'];
            if ($options->dryRun) {
                return true;
            }
            $job = new SyncShopToShuyunOpenPlatformJob($options->companyId, $distributorId);
            $this->dispatcher->dispatchNow($job);

            return true;
        }, static fn (array $row): string => (string) $row['distributor_id']);
    }

    private function runCategories(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT category_id FROM items_category WHERE company_id = ? AND category_level IN (2, 3) ORDER BY category_id ASC',
            [$options->companyId]
        );
        $rows = $this->applyPaging($rows, $options);

        return $this->countedLoop(HistoricalSyncSteps::CATEGORIES, $rows, $options, $limiter, $failures, function (array $row) use ($options): bool {
            $categoryId = (int) $row['category_id'];
            if ($options->dryRun) {
                return true;
            }

            return $this->categorySyncService->syncCategory($options->companyId, $categoryId);
        }, static fn (array $row): string => (string) $row['category_id']);
    }

    private function runProducts(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $sql = 'SELECT DISTINCT di.distributor_id, i.default_item_id
             FROM distribution_distributor_items di
             INNER JOIN items i ON di.item_id = i.item_id AND i.company_id = di.company_id
             WHERE di.company_id = ? AND i.default_item_id IS NOT NULL AND i.default_item_id > 0';
        $params = [$options->companyId];
        if ($options->defaultItemId > 0) {
            $sql .= ' AND i.default_item_id = ?';
            $params[] = $options->defaultItemId;
        }
        if ($options->distributorId > 0) {
            $sql .= ' AND di.distributor_id = ?';
            $params[] = $options->distributorId;
        }
        $sql .= ' ORDER BY di.distributor_id ASC, i.default_item_id ASC';
        $rows = $this->connection->fetchAllAssociative($sql, $params);
        $rows = $this->applyPaging($rows, $options);

        return $this->countedLoop(HistoricalSyncSteps::PRODUCTS, $rows, $options, $limiter, $failures, function (array $row) use ($options): bool {
            if ($options->dryRun) {
                return true;
            }

            return $this->productSyncService->syncProductByDefaultItem(
                $options->companyId,
                (int) $row['distributor_id'],
                (int) $row['default_item_id']
            );
        }, static fn (array $row): string => $row['distributor_id'].':'.$row['default_item_id']);
    }

    private function runMembers(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT user_id, mobile, reg_distributor, shuyun_open_online_wxapp_sync_at
             FROM members WHERE company_id = ? ORDER BY user_id ASC',
            [$options->companyId]
        );
        $rows = $this->applyPaging($rows, $options);
        $cursor = $options->resume ? $this->checkpointStore->read($options->companyId, HistoricalSyncSteps::MEMBERS) : null;

        $processed = $succeeded = $skipped = $failed = 0;
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $key = (string) $userId;
            if ($cursor !== null && $key <= $cursor) {
                continue;
            }
            ++$processed;
            $limiter?->throttle();

            $mobile = trim((string) ($row['mobile'] ?? ''));
            if (! HistoricalSyncMobileValidator::isValidMainlandMobile($mobile)) {
                ++$skipped;
                $failures?->append(HistoricalSyncSteps::MEMBERS, $key, 'invalid_mobile');
                $this->checkpointStore->write($options->companyId, HistoricalSyncSteps::MEMBERS, $key);
                continue;
            }
            if (! $options->force && $row['shuyun_open_online_wxapp_sync_at'] !== null && (int) $row['shuyun_open_online_wxapp_sync_at'] > 0) {
                ++$skipped;
                $this->checkpointStore->write($options->companyId, HistoricalSyncSteps::MEMBERS, $key);
                continue;
            }

            $distributorId = (int) ($row['reg_distributor'] ?? 0);
            if ($distributorId <= 0) {
                ++$failed;
                $failures?->append(HistoricalSyncSteps::MEMBERS, $key, 'missing_reg_distributor');
                $this->checkpointStore->write($options->companyId, HistoricalSyncSteps::MEMBERS, $key);
                continue;
            }

            if ($options->dryRun) {
                ++$succeeded;
                $this->checkpointStore->write($options->companyId, HistoricalSyncSteps::MEMBERS, $key);
                continue;
            }

            try {
                $distRow = $this->distributorRow($options->companyId, $distributorId);
                $wx = $this->wechatBindResolver->resolve($options->companyId, $userId);
                try {
                    $this->memberRegisterService->registerSingle(
                        $options->companyId,
                        $distRow,
                        (string) $userId,
                        $mobile,
                        $wx['unionid'] ?? null,
                        null,
                        false
                    );
                } catch (\Throwable $registerEx) {
                    if (! HistoricalSyncWechatBindResolver::isRegisterAlreadyExistsError($registerEx)) {
                        throw $registerEx;
                    }
                }
                $this->memberService->syncUserCardCodeFromShuyunEnhanceAfterRegister(
                    $options->companyId,
                    $userId,
                    $distRow,
                    false
                );
                if ($wx !== null) {
                    $this->memberBindPushService->pushSingle(
                        $options->companyId,
                        $distRow,
                        (string) $userId,
                        $wx['unionid'],
                        $wx['open_id']
                    );
                    $this->memberService->updateMemberInfo(
                        ['shuyun_open_online_wxapp_sync_at' => time()],
                        ['user_id' => $userId, 'company_id' => $options->companyId]
                    );
                }
                ++$succeeded;
            } catch (\Throwable $e) {
                ++$failed;
                $failures?->append(HistoricalSyncSteps::MEMBERS, $key, $e->getMessage());
            }
            $this->checkpointStore->write($options->companyId, HistoricalSyncSteps::MEMBERS, $key);
        }

        return new HistoricalSyncStepResult(HistoricalSyncSteps::MEMBERS, $processed, $succeeded, $skipped, $failed);
    }

    private function runOrders(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT order_id FROM orders_normal_orders
             WHERE company_id = ?
               AND user_id > 0 AND total_fee > 0
               AND pay_status = ?
               AND order_class IN (?, ?)
             ORDER BY order_id ASC',
            [$options->companyId, 'PAYED', 'normal', 'shopadmin']
        );
        $rows = $this->applyPaging($rows, $options);

        return $this->countedLoop(HistoricalSyncSteps::ORDERS, $rows, $options, $limiter, $failures, function (array $row) use ($options): bool {
            $orderId = trim((string) $row['order_id']);
            if ($options->dryRun) {
                return true;
            }
            $job = new SyncNormalOrderTradeToShuyunOpenPlatformJob($options->companyId, $orderId);
            $this->dispatcher->dispatchNow($job);

            return true;
        }, static fn (array $row): string => (string) $row['order_id']);
    }

    private function runRefunds(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT refund_bn FROM aftersales_refund WHERE company_id = ? AND refund_status = ? ORDER BY refund_bn ASC',
            [$options->companyId, 'SUCCESS']
        );
        $rows = $this->applyPaging($rows, $options);

        return $this->countedLoop(HistoricalSyncSteps::REFUNDS, $rows, $options, $limiter, $failures, function (array $row) use ($options): bool {
            $refundBn = trim((string) $row['refund_bn']);
            if ($options->dryRun) {
                return true;
            }
            $job = new SyncNormalOrderRefundToShuyunOpenPlatformJob($options->companyId, $refundBn);
            $this->dispatcher->dispatchNow($job);

            return true;
        }, static fn (array $row): string => (string) $row['refund_bn']);
    }

    private function runPoints(
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures
    ): HistoricalSyncStepResult {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT pm.user_id, pm.point, m.reg_distributor, m.mobile
             FROM point_member pm
             INNER JOIN members m ON m.user_id = pm.user_id AND m.company_id = pm.company_id
             WHERE pm.company_id = ? AND pm.point > 0
             ORDER BY pm.user_id ASC',
            [$options->companyId]
        );
        $rows = $this->applyPaging($rows, $options);

        return $this->countedLoop(HistoricalSyncSteps::POINTS, $rows, $options, $limiter, $failures, function (array $row) use ($options): bool {
            $userId = (int) $row['user_id'];
            $distributorId = (int) ($row['reg_distributor'] ?? 0);
            if ($distributorId <= 0) {
                return false;
            }
            if ($options->dryRun) {
                return true;
            }
            $distRow = $this->distributorRow($options->companyId, $distributorId);
            $memberRow = ['user_id' => $userId, 'company_id' => $options->companyId, 'reg_distributor' => $distributorId, 'mobile' => $row['mobile'] ?? ''];

            return $this->pointBalanceAligner->alignIfNeeded(
                $options->companyId,
                $memberRow,
                $distRow,
                (int) $row['point']
            );
        }, static fn (array $row): string => (string) $row['user_id']);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): bool  $handler
     * @param  callable(array<string, mixed>): string  $keyFn
     */
    private function countedLoop(
        string $step,
        array $rows,
        HistoricalSyncRunOptions $options,
        ?HistoricalSyncRateLimiter $limiter,
        ?HistoricalSyncFailureRecorder $failures,
        callable $handler,
        callable $keyFn
    ): HistoricalSyncStepResult {
        $cursor = $options->resume ? $this->checkpointStore->read($options->companyId, $step) : null;
        $processed = $succeeded = $skipped = $failed = 0;

        foreach ($rows as $row) {
            $key = $keyFn($row);
            if ($cursor !== null && $key <= $cursor) {
                continue;
            }
            ++$processed;
            $limiter?->throttle();
            if ($options->dryRun) {
                ++$succeeded;
                $this->checkpointStore->write($options->companyId, $step, $key);
                continue;
            }
            try {
                $ok = $handler($row);
                if ($ok) {
                    ++$succeeded;
                } else {
                    ++$failed;
                    $failures?->append($step, $key, 'handler_returned_false');
                }
            } catch (\Throwable $e) {
                ++$failed;
                $failures?->append($step, $key, $e->getMessage());
            }
            $this->checkpointStore->write($options->companyId, $step, $key);
        }

        return new HistoricalSyncStepResult($step, $processed, $succeeded, $skipped, $failed);
    }

    /** @return list<array<string, mixed>> */
    private function fetchDistributorIds(HistoricalSyncRunOptions $options): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT distributor_id FROM distribution_distributor WHERE company_id = ? ORDER BY distributor_id ASC',
            [$options->companyId]
        );

        return $this->applyPaging($rows, $options);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     *
     * @return list<array<string, mixed>>
     */
    private function applyPaging(array $rows, HistoricalSyncRunOptions $options): array
    {
        if ($options->offset > 0) {
            $rows = array_slice($rows, $options->offset);
        }
        if ($options->limit > 0) {
            $rows = array_slice($rows, 0, $options->limit);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function distributorRow(int $companyId, int $distributorId): array
    {
        $repo = app('registry')->getManager('default')->getRepository(Distributor::class);
        if (! $repo instanceof DistributorRepository) {
            throw new \RuntimeException('Invalid Distributor repository.');
        }
        $row = $repo->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
        if ($row === [] || $row === null) {
            throw new \RuntimeException('Distributor not found: '.$distributorId);
        }

        return $row;
    }
}
