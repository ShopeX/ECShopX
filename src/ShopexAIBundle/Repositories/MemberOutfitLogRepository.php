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
use ShopexAIBundle\Entities\MemberOutfitLog;

class MemberOutfitLogRepository extends EntityRepository
{
    /**
     * 获取会员今日试衣次数
     *
     * @param int $memberId
     * @return int
     */
    public function getTodayOutfitCount($memberId)
    {
        $today = new \DateTime('today');
        
        return $this->createQueryBuilder('mol')
            ->select('COUNT(mol.id)')
            ->where('mol.member_id = :memberId')
            ->andWhere('mol.created_at >= :today')
            ->setParameter('memberId', $memberId)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 获取会员的试衣记录
     *
     * @param int $memberId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getMemberOutfitLogs($memberId, $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('mol')
            ->where('mol.member_id = :memberId')
            ->setParameter('memberId', $memberId)
            ->orderBy('mol.created_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取会员的试衣记录总数
     *
     * @param int $memberId
     * @return int
     */
    public function countMemberOutfitLogs($memberId)
    {
        return $this->createQueryBuilder('mol')
            ->select('COUNT(mol.id)')
            ->where('mol.member_id = :memberId')
            ->setParameter('memberId', $memberId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 根据请求ID获取试衣记录
     *
     * @param string $requestId
     * @return MemberOutfitLog|null
     */
    public function findByRequestId($requestId)
    {
        return $this->findOneBy(['request_id' => $requestId]);
    }
} 