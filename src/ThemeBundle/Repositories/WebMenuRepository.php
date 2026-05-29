<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ThemeBundle\Entities\WebMenu;

class WebMenuRepository extends EntityRepository
{
    public function findActiveByCompanyAndKey(int $companyId, string $key): ?WebMenu
    {
        return $this->findOneBy([
            'companyId' => $companyId,
            'key'         => $key,
            'status'      => 1,
        ]);
    }

    public function findOneByIdAndCompany(int $id, int $companyId): ?WebMenu
    {
        return $this->findOneBy(['id' => $id, 'companyId' => $companyId]);
    }

    public function findActiveByCompanyAndId(int $companyId, int $id): ?WebMenu
    {
        return $this->findOneBy([
            'id' => $id,
            'companyId' => $companyId,
            'status' => 1,
        ]);
    }

    /**
     * @return WebMenu[]
     */
    public function findPageByCompany(int $companyId, int $page = 1, int $pageSize = 20, ?string $name = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.companyId = :cid')
            ->setParameter('cid', $companyId);
        $this->applyNameFilter($qb, $name);

        return $qb->orderBy('m.id', 'DESC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();
    }

    public function countByCompany(int $companyId, ?string $name = null): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.companyId = :cid')
            ->setParameter('cid', $companyId);
        $this->applyNameFilter($qb, $name);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyNameFilter($qb, ?string $name): void
    {
        $name = $name !== null ? trim($name) : '';
        if ($name !== '') {
            $qb->andWhere('m.name LIKE :menuName')->setParameter('menuName', '%' . $name . '%');
        }
    }

    public function existsDuplicateKey(int $companyId, string $key, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.companyId = :cid AND m.key = :k')
            ->setParameter('cid', $companyId)
            ->setParameter('k', $key);
        if ($excludeId !== null) {
            $qb->andWhere('m.id != :eid')->setParameter('eid', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
