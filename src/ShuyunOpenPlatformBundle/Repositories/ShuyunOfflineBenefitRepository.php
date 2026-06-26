<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefit;

class ShuyunOfflineBenefitRepository extends EntityRepository
{
    public function findOneByCompanyAndBenefitId(int $companyId, string $benefitId): ?ShuyunOfflineBenefit
    {
        return $this->findOneBy(['company_id' => $companyId, 'benefit_id' => $benefitId]);
    }

    /**
     * 单笔/批量发放回调：body 常仅有 benefitId，用于在全库按权益 ID 反查租户（唯一时可替代 query appId）。
     *
     * @return list<ShuyunOfflineBenefit>
     */
    public function findAllByBenefitId(string $benefitId): array
    {
        if ($benefitId === '') {
            return [];
        }

        /** @var list<ShuyunOfflineBenefit> $rows */
        $rows = $this->findBy(['benefit_id' => $benefitId]);

        return $rows;
    }

    public function save(ShuyunOfflineBenefit $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
