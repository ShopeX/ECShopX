<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;

class ShuyunOfflineBenefitSendItemRepository extends EntityRepository
{
    /**
     * @return list<ShuyunOfflineBenefitSendItem>
     */
    public function findByBatch(ShuyunOfflineBenefitSendBatch $batch): array
    {
        /** @var list<ShuyunOfflineBenefitSendItem> $r */
        $r = $this->findBy(['batch' => $batch], ['id' => 'ASC']);

        return $r;
    }

    public function findOneByBatchAndCustomerId(ShuyunOfflineBenefitSendBatch $batch, string $customerId): ?ShuyunOfflineBenefitSendItem
    {
        return $this->findOneBy(['batch' => $batch, 'customer_id' => $customerId]);
    }

    /**
     * 商城下单核销券码后：按租户 + 券实例码关联本地订单（{@see ShuyunOfflineBenefitSendItem::local_order_id}）。
     * 优先匹配已写入的 {@see ShuyunOfflineBenefitSendItem::member_user_id}；未写入时仅按码匹配（如 Stub Issuer）。
     */
    public function findOneUnlinkedSuccessByCompanyUserAndBenefitCode(int $companyId, int $userId, string $benefitCode): ?ShuyunOfflineBenefitSendItem
    {
        $code = trim($benefitCode);
        if ($code === '') {
            return null;
        }

        $qb = $this->createQueryBuilder('i')
            ->join('i.batch', 'b')
            ->where('b.company_id = :cid')
            ->andWhere('i.benefit_code = :code')
            ->andWhere('i.status = :st')
            ->andWhere('i.local_order_id IS NULL')
            ->andWhere('(i.member_user_id IS NULL OR i.member_user_id = :uid)')
            ->setParameter('cid', $companyId)
            ->setParameter('code', $code)
            ->setParameter('st', 'SUCCESS')
            ->setParameter('uid', $userId)
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(1);

        /** @var ShuyunOfflineBenefitSendItem|null $one */
        $one = $qb->getQuery()->getOneOrNullResult();

        return $one;
    }

    /**
     * 已关联本地订单、发券成功的数云线下权益行（用于支付成功后推 USED）。
     *
     * @return list<ShuyunOfflineBenefitSendItem>
     */
    public function findSendItemsForOrderPayConsume(int $companyId, int $localOrderId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->join('i.batch', 'b')
            ->where('b.company_id = :cid')
            ->andWhere('i.local_order_id = :oid')
            ->andWhere('i.status = :st')
            ->andWhere('i.benefit_code IS NOT NULL AND i.benefit_code != :empty')
            ->setParameter('cid', $companyId)
            ->setParameter('oid', $localOrderId)
            ->setParameter('st', 'SUCCESS')
            ->setParameter('empty', '')
            ->orderBy('i.id', 'ASC');

        /** @var list<ShuyunOfflineBenefitSendItem> $r */
        $r = $qb->getQuery()->getResult();

        return $r;
    }

    /**
     * 已对数云推过 USED、待推 NOT_USED 的明细（取消返券后）。
     *
     * @return list<ShuyunOfflineBenefitSendItem>
     */
    public function findSendItemsForOrderCancelNotUsed(int $companyId, int $localOrderId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->join('i.batch', 'b')
            ->where('b.company_id = :cid')
            ->andWhere('i.local_order_id = :oid')
            ->andWhere('i.last_consume_status = :used')
            ->setParameter('cid', $companyId)
            ->setParameter('oid', $localOrderId)
            ->setParameter('used', 'USED')
            ->orderBy('i.id', 'ASC');

        /** @var list<ShuyunOfflineBenefitSendItem> $r */
        $r = $qb->getQuery()->getResult();

        return $r;
    }

    public function save(ShuyunOfflineBenefitSendItem $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
