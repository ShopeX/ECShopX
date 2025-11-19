<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use MembersBundle\Entities\ShopRelMember;

class ShopRelMemberService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ShopRelMember::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->entityRepository->$method(...$parameters);
    }

    //根据门店id集合获取会员id，取交集
    public function getUserIdBy($filter, $page = 1, $pageSize = -1)
    {
        $shopIds = $filter['shop_id'] ?? 0;
        if (!$shopIds) {
            return [];
        }
        $userIds = $filter['user_id'] ?? [];
        $companyId = $filter['company_id'] ?? [];

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $shopIds = (array)$shopIds;
        $userIds = (array)$userIds;
        array_walk($shopIds, function (&$colVal) use ($criteria) {
            $colVal = $criteria->expr()->literal($colVal);
        });
        array_walk($userIds, function (&$colVal) use ($criteria) {
            $colVal = $criteria->expr()->literal($colVal);
        });
        $criteria->select('user_id')
            ->from('members_rel_shop')
            ->where($criteria->expr()->in('shop_id', $shopIds));
        if ($companyId) {
            $criteria->andWhere($criteria->expr()->eq('company_id', $companyId));
        }
        if ($userIds) {
            $criteria->andWhere($criteria->expr()->in('user_id', $userIds));
        }
        $criteria->groupBy('user_id')
            ->having('count(user_id) ='.count($shopIds));
        if ($pageSize > 0) {
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $list = $criteria->execute()->fetchAll();
        if (!$list) {
            return [];
        }
        $userIds = array_column($list, 'user_id');
        return $userIds;
    }
}
