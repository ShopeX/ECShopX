<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;

class ShuyunOfflineBenefitSendBatchRepository extends EntityRepository
{
    public function findOneByCompanyAndRequestId(int $companyId, string $requestId): ?ShuyunOfflineBenefitSendBatch
    {
        return $this->findOneBy(['company_id' => $companyId, 'request_id' => $requestId]);
    }

    public function save(ShuyunOfflineBenefitSendBatch $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @template T
     *
     * @param  callable(EntityManagerInterface): T  $callback
     * @return T
     */
    public function runInTransaction(callable $callback): mixed
    {
        return $this->getEntityManager()->wrapInTransaction($callback);
    }
}
