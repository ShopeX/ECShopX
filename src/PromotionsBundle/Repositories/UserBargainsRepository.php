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

namespace PromotionsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use PromotionsBundle\Entities\UserBargains;

use Doctrine\Common\Collections\Criteria;

use Dingo\Api\Exception\UpdateResourceFailedException;

class UserBargainsRepository extends EntityRepository
{
    public function create($params)
    {
        $userBargainEntity = new UserBargains();
        $userBargain = $this->setUserBargainData($userBargainEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($userBargain);
        $em->flush();
        $result = $this->getUserBargainData($userBargain);

        return $result;
    }

    public function update($filter, $data)
    {
        $userBargainEntity = $this->findOneBy($filter);
        if (!$userBargainEntity) {
            throw new UpdateResourceFailedException(trans("PromotionsBundle.user_bargain_activity_not_exist", ["bargain_id" => $filter["bargain_id"]]));
        }
        $userBargain = $this->setUserBargainData($userBargainEntity, $data);
        $em = $this->getEntityManager();
        $em->persist($userBargain);
        $em->flush();
        $result = $this->getUserBargainData($userBargain);

        return $result;
    }

    public function get($filter)
    {
        $userBargainEntity = $this->findOneBy($filter);
        $result = [];
        if ($userBargainEntity) {
            $result = $this->getUserBargainData($userBargainEntity);
        }

        return $result;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['created' => 'desc'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);
            $bargainList = [];
            if ($list) {
                foreach ($list as $bargain) {
                    $bargain = $this->getUserBargainData($bargain);
                    $bargainList[] = $bargain;
                }
            }
            $res['list'] = $bargainList;
        }

        return $res;
    }

    private function setUserBargainData($userBargainEntity, $data)
    {
        if (isset($data['company_id']) && $data['company_id']) {
            $userBargainEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['authorizer_appid']) && $data['authorizer_appid']) {
            $userBargainEntity->setAuthorizerAppid($data['authorizer_appid']);
        }
        if (isset($data['wxa_appid']) && $data['wxa_appid']) {
            $userBargainEntity->setWxaAppid($data['wxa_appid']);
        }
        if (isset($data['bargain_id']) && $data['bargain_id']) {
            $userBargainEntity->setBargainId($data['bargain_id']);
        }
        if (isset($data['user_id']) && $data['user_id']) {
            $userBargainEntity->setUserId($data['user_id']);
        }
        if (isset($data['item_name']) && $data['item_name']) {
            $userBargainEntity->setItemName($data['item_name']);
        }
        if (isset($data['mkt_price']) && $data['mkt_price']) {
            $userBargainEntity->setMktPrice($data['mkt_price']);
        }
        if (isset($data['price']) && $data['price']) {
            $userBargainEntity->setPrice($data['price']);
        }
        if (isset($data['cutprice_num']) && $data['cutprice_num']) {
            $userBargainEntity->setCutpriceNum($data['cutprice_num']);
        }
        if (isset($data['cutprice_range']) && $data['cutprice_range']) {
            $userBargainEntity->setCutpriceRange($data['cutprice_range']);
        }
        if (isset($data['cutdown_amount']) && $data['cutdown_amount']) {
            $userBargainEntity->setCutdownAmount($data['cutdown_amount']);
        }
        if (isset($data['is_ordered'])) {
            $userBargainEntity->setIsOrdered($data['is_ordered']);
        }

        return $userBargainEntity;
    }

    public function getUserBargainData($userBargainEntity)
    {
        return [
            'company_id' => $userBargainEntity->getCompanyId(),
            'authorizer_appid' => $userBargainEntity->getAuthorizerAppid(),
            'wxa_appid' => $userBargainEntity->getWxaAppid(),
            'bargain_id' => $userBargainEntity->getBargainId(),
            'user_id' => $userBargainEntity->getUserId(),
            'item_name' => $userBargainEntity->getItemName(),
            'mkt_price' => $userBargainEntity->getMktPrice(),
            'price' => $userBargainEntity->getPrice(),
            'cutprice_num' => $userBargainEntity->getCutpriceNum(),
            'cutprice_range' => $userBargainEntity->getCutpriceRange(),
            'cutdown_amount' => $userBargainEntity->getCutdownAmount(),
            'is_ordered' => $userBargainEntity->getIsOrdered(),
            'created' => $userBargainEntity->getCreated(),
            'updated' => $userBargainEntity->getUpdated(),
        ];
    }
}
