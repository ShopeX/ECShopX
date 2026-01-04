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

namespace CompanysBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use CompanysBundle\Entities\Resources;

use Dingo\Api\Exception\UpdateResourceFailedException;

class ResourcesRepository extends EntityRepository
{
    public $table = 'resources';

    public function getList($filter, $orderBy = ['expired_at' => 'ASC'], $offset = 0, $limit = 100000)
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

        $resourceList = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($offset)
                ->setMaxResults($limit);
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $resourceList[] = normalize($v);
            }
        }
        $res['list'] = $resourceList;

        return $res;
    }

    public function create($params)
    {
        $resourcesEntity = new Resources();
        $resources = $this->setResourceData($resourcesEntity, $params);
        $em = $this->getEntityManager();
        $em->persist($resources);
        $em->flush();
        $result = $this->getResourceData($resources);
        return $result;
    }

    public function update($resourceId, $data)
    {
        $resourcesEntity = $this->find($resourceId);
        if (!$resourcesEntity) {
            throw new UpdateResourceFailedException("resource_id为{$resourceId}的资源包不存在");
        }
        if ($resourcesEntity->getLeftShopNum() == 0) {
            throw new UpdateResourceFailedException("resource_id为{$resourceId}的资源包店铺限制已达上限");
        }
        $resources = $this->setResourceData($resourcesEntity, $data);
        $em = $this->getEntityManager();
        $em->persist($resources);
        $em->flush();

        $result = $this->getResourceData($resources);

        return $result;
    }

    public function get($filter)
    {
        return $this->findOneBy($filter);
    }

    private function setResourceData($resourcesEntity, $data)
    {
        if (isset($data['company_id'])) {
            $resourcesEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['resource_name'])) {
            $resourcesEntity->setResourceName($data['resource_name']);
        }
        if (isset($data['eid'])) {
            $resourcesEntity->setEid($data['eid']);
        }
        if (isset($data['passport_uid'])) {
            $resourcesEntity->setPassportUid($data['passport_uid']);
        }
        if (isset($data['shop_num'])) {
            $resourcesEntity->setShopNum($data['shop_num']);
        }
        if (isset($data['left_shop_num'])) {
            $resourcesEntity->setLeftShopNum($data['left_shop_num']);
        }
        if (isset($data['source'])) {
            $resourcesEntity->setSource($data['source']);
        }
        if (isset($data['available_days'])) {
            $resourcesEntity->setAvailableDays($data['available_days']);
        }
        if (isset($data['active_at'])) {
            $resourcesEntity->setActiveAt($data['active_at']);
        }
        if (isset($data['expired_at'])) {
            $resourcesEntity->setExpiredAt($data['expired_at']);
        }
        if (isset($data['active_code'])) {
            $resourcesEntity->setActiveCode($data['active_code']);
        }
        if (isset($data['issue_id'])) {
            $resourcesEntity->setIssueId($data['issue_id']);
        }
        if (isset($data['goods_code'])) {
            $resourcesEntity->setGoodsCode($data['goods_code']);
        }
        if (isset($data['product_code'])) {
            $resourcesEntity->setProductCode($data['product_code']);
        }
        return $resourcesEntity;
    }

    public function getResourceData($resourcesEntity)
    {
        return [
            'resource_id' => $resourcesEntity->getResourceId(),
            'resource_name' => $resourcesEntity->getResourceName(),
            'company_id' => $resourcesEntity->getCompanyId(),
            'eid' => $resourcesEntity->getEid(),
            'passport_uid' => $resourcesEntity->getPassportUid(),
            'shop_num' => $resourcesEntity->getShopNum(),
            'left_shop_num' => $resourcesEntity->getLeftShopNum(),
            'source' => $resourcesEntity->getSource(),
            'available_days' => $resourcesEntity->getAvailableDays(),
            'active_at' => $resourcesEntity->getActiveAt(),
            'expired_at' => $resourcesEntity->getExpiredAt(),
            'active_code' => $resourcesEntity->getActiveCode(),
            'issue_id' => $resourcesEntity->getIssueId(),
            'goods_code' => $resourcesEntity->getGoodsCode(),
            'product_code' => $resourcesEntity->getProductCode(),
        ];
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getResourceData($entity);
    }


    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["active_at" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
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

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getResourceData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }
}
