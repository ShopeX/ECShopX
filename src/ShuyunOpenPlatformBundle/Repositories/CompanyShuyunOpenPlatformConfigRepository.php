<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Repositories;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;

class CompanyShuyunOpenPlatformConfigRepository extends EntityRepository
{
    public function findOneByAuthValue(string $authValue): ?CompanyShuyunOpenPlatformConfig
    {
        return $this->findOneBy(['auth_value' => $authValue]);
    }

    public function findOneByAppId(string $appId): ?CompanyShuyunOpenPlatformConfig
    {
        return $this->findOneBy(['app_id' => $appId]);
    }

    public function findOneByCompanyId(int $companyId): ?CompanyShuyunOpenPlatformConfig
    {
        return $this->findOneBy(['company_id' => $companyId]);
    }

    /**
     * 数云回调 body 仅带 platCode、无 query appId 时，按「身份注册」配置的 plat_code 解析租户（trim + 大小写不敏感）。
     *
     * @return list<CompanyShuyunOpenPlatformConfig>
     */
    public function findAllEnabledByNormalizedPlatCode(string $platCode): array
    {
        $normalized = strtolower(trim($platCode));
        if ($normalized === '') {
            return [];
        }

        $q = $this->createQueryBuilder('c')
            ->where('c.is_enabled = 1')
            ->andWhere('c.plat_code IS NOT NULL')
            ->andWhere('LOWER(TRIM(c.plat_code)) = :p')
            ->setParameter('p', $normalized)
            ->getQuery();

        /** @var list<CompanyShuyunOpenPlatformConfig> $rows */
        $rows = $q->getResult();

        return $rows;
    }

    /**
     * 定时刷新候选：已启用、具备 app_id 与 access_token、未 isOverDue=1（避免无效 HTTP）。
     *
     * @return list<CompanyShuyunOpenPlatformConfig>
     */
    public function findEligibleForScheduledRefresh(): array
    {
        $q = $this->createQueryBuilder('c')
            ->where('c.is_enabled = 1')
            ->andWhere('c.app_id IS NOT NULL')
            ->andWhere('c.app_id != :emptyApp')
            ->andWhere('c.access_token IS NOT NULL')
            ->andWhere('c.access_token != :emptyTok')
            ->andWhere('(c.is_over_due IS NULL OR c.is_over_due != :overdue)')
            ->setParameter('emptyApp', '')
            ->setParameter('emptyTok', '')
            ->setParameter('overdue', '1')
            ->getQuery();

        /** @var list<CompanyShuyunOpenPlatformConfig> $r */
        $r = $q->getResult();

        return $r;
    }

    public function save(CompanyShuyunOpenPlatformConfig $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * 数云 Token 回调专用落库：单条 UPDATE + 短事务，降低与定时刷新/后管保存等并发时的持锁时间；遇锁等待超时、死锁等可重试错误时指数退避重试。
     *
     * 无主键 id 的实体（极少见）仍走 {@see save()}。
     */
    public function saveTokenCallbackRowWithRetry(CompanyShuyunOpenPlatformConfig $entity): void
    {
        $id = $entity->getId();
        if ($id === null) {
            $this->save($entity);

            return;
        }

        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $maxAttempts = max(1, (int) config('shuyun_open_platform.token_callback_save_max_attempts', 6));
        $accessToken = $entity->getAccessToken();
        $isOverDue = $entity->getIsOverDue();
        $appId = $entity->getAppId();
        $updated = time();

        $sql = 'UPDATE company_shuyun_open_platform_config SET access_token = ?, is_over_due = ?, app_id = ?, updated = ? WHERE id = ?';

        $attempt = 0;
        while (true) {
            ++$attempt;
            $conn->beginTransaction();
            try {
                $conn->executeStatement($sql, [$accessToken, $isOverDue, $appId, $updated, $id]);
                $conn->commit();
                break;
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                if (!$this->isRetryableLockException($e) || $attempt >= $maxAttempts) {
                    throw $e;
                }
                $this->logTokenCallbackSaveRetry($entity, $attempt, $maxAttempts, $e);
                usleep($this->tokenCallbackRetryDelayMicroseconds($attempt));
            }
        }

        $entity->setUpdated($updated);
        try {
            $em->refresh($entity);
        } catch (\Throwable) {
        }
    }

    /**
     * 单事务 persist + flush（T6 网关成功后原子落库，避免业务层调用受保护的 getEntityManager）。
     */
    public function saveInTransaction(CompanyShuyunOpenPlatformConfig $entity): void
    {
        $this->getEntityManager()->wrapInTransaction(
            function (EntityManagerInterface $em) use ($entity): void {
                $em->persist($entity);
            }
        );
    }

    private function isRetryableLockException(\Throwable $e): bool
    {
        if ($e instanceof RetryableException) {
            return true;
        }
        $p = $e->getPrevious();
        while ($p instanceof \Throwable) {
            if ($p instanceof RetryableException) {
                return true;
            }
            $p = $p->getPrevious();
        }

        return false;
    }

    private function tokenCallbackRetryDelayMicroseconds(int $attempt): int
    {
        $base = max(5_000, (int) config('shuyun_open_platform.token_callback_save_retry_base_usleep', 50_000));
        $cap = max($base, (int) config('shuyun_open_platform.token_callback_save_retry_max_usleep', 800_000));
        $delay = (int) min($cap, $base * (2 ** ($attempt - 1)));
        try {
            $delay += random_int(0, min(100_000, max(0, $delay)));
        } catch (\Throwable) {
        }

        return $delay;
    }

    private function logTokenCallbackSaveRetry(CompanyShuyunOpenPlatformConfig $entity, int $attempt, int $maxAttempts, \Throwable $e): void
    {
        try {
            if (!\function_exists('app') || !app()->bound('log')) {
                return;
            }
            $log = app()->make('log')->channel('shuyun_open_platform');
            if (!$log instanceof LoggerInterface) {
                return;
            }
            $log->warning('数云 token 回调落库遇可重试锁错误，将重试', [
                'company_id' => $entity->getCompanyId(),
                'config_id' => $entity->getId(),
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable) {
        }
    }
}
