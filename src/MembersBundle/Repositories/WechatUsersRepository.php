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

namespace MembersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use MembersBundle\Entities\WechatUsers;

use Dingo\Api\Exception\ResourceException;

class WechatUsersRepository extends EntityRepository
{
    public $table = 'members_wechatusers';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new WechatUsers();
        $entity = $this->setWechatUserData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getWechatUserData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setWechatUserData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getWechatUserData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getUserInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getWechatUserData($entity);
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setWechatUserData($entity, $data)
    {
        if (isset($data["company_id"])) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["authorizer_appid"])) {
            $entity->setAuthorizerAppid($data["authorizer_appid"]);
        }
        if (isset($data["open_id"])) {
            $entity->setOpenId($data["open_id"]);
        }
        if (isset($data["nickname"])) {
            $entity->setNickname($data["nickname"]);
        }
        if (isset($data["headimgurl"]) && $data["headimgurl"]) {
            $entity->setHeadimgurl($data["headimgurl"]);
        }
        if (isset($data["inviter_id"])) {
            $entity->setInviterId($data["inviter_id"]);
        }
        if (isset($data["source_from"])) {
            $entity->setSourceFrom($data["source_from"]);
        }
        if (isset($data["unionid"])) {
            $entity->setUnionid($data["unionid"]);
        }
        if (isset($data["need_transfer"])) {
            $entity->setNeedTransfer($data["need_transfer"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getWechatUserData($entity)
    {
        return [
            'company_id' => $entity->getCompanyId(),
            'authorizer_appid' => $entity->getAuthorizerAppid(),
            'open_id' => $entity->getOpenId(),
            'unionid' => $entity->getUnionid(),
            'nickname' => $entity->getNickname(),
            'headimgurl' => $entity->getHeadimgurl(),
            'inviter_id' => $entity->getInviterId(),
            'source_from' => $entity->getSourceFrom(),
            'need_transfer' => $entity->getNeedTransfer(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }

    /**
     * 统计数量
     */
    public function count($filter)
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

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = ["created" => "DESC"])
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
                $lists[] = $this->getWechatUserData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }


    public function getAllLists($filter, $cols = '*')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        return $qb->execute()->fetchAll();
    }


    private function _filter($filter, $qb)
    {
        $fixedencryptCol = ['nickname'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }

        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }


    public function deleteBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->delete($this->table);

        $qb = $this->_filter($filter, $qb);
        return $qb->execute();
    }
}
