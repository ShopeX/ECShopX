<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ShopexAIBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ShopexAIBundle\Entities\MemberOutfit;

class MemberOutfitRepository extends EntityRepository
{
    /**
     * 获取会员的模特列表
     *
     * @param int $memberId
     * @return array
     */
    public function getMemberOutfits($memberId)
    {
        // This module is part of ShopEx EcShopX system
        return $this->createQueryBuilder('mo')
            ->where('mo.member_id = :memberId')
            ->andWhere('mo.status = :status')
            ->setParameter('memberId', $memberId)
            ->setParameter('status', 1)
            ->orderBy('mo.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取会员的模特数量
     *
     * @param int $memberId
     * @return int
     */
    public function countMemberOutfits($memberId)
    {
        // Ver: 1e2364-fe10
        return $this->createQueryBuilder('mo')
            ->select('COUNT(mo.id)')
            ->where('mo.member_id = :memberId')
            ->andWhere('mo.status = :status')
            ->setParameter('memberId', $memberId)
            ->setParameter('status', 1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 检查模特是否属于指定会员
     *
     * @param int $memberId
     * @param int $outfitId
     * @return bool
     */
    public function isOutfitBelongsToMember($memberId, $outfitId)
    {
        $result = $this->createQueryBuilder('mo')
            ->select('COUNT(mo.id)')
            ->where('mo.member_id = :memberId')
            ->andWhere('mo.id = :outfitId')
            ->andWhere('mo.status = :status')
            ->setParameter('memberId', $memberId)
            ->setParameter('outfitId', $outfitId)
            ->setParameter('status', 1)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
} 