<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use ShuyunOpenPlatformBundle\Entities\ShuyunOpenPlatformTrafficAudit;

/**
 * @extends EntityRepository<ShuyunOpenPlatformTrafficAudit>
 */
class ShuyunOpenPlatformTrafficAuditRepository extends EntityRepository
{
    /**
     * @return list<ShuyunOpenPlatformTrafficAudit>
     */
    public function findByCompanyIdOrdered(int $companyId, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.company_id = :cid')
            ->setParameter('cid', $companyId)
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function persistAndFlush(ShuyunOpenPlatformTrafficAudit $entity): void
    {
        $em = $this->getEntityManager();
        if (!$em instanceof EntityManager) {
            throw new \RuntimeException('Expected Doctrine ORM EntityManager instance.');
        }
        $em->persist($entity);
        // 全量 flush 会写出 UoW 内其它已管理实体（如 CompanyShuyunOpenPlatformConfig），易与并发 Token 回调争用行锁。
        // Doctrine ORM 2.x：传入实体则仅同步该实体（及级联插入等）；ORM 3 起将移除该参数，届时需改用独立 EM 或 DBAL 插入。
        $em->flush($entity);
    }
}
