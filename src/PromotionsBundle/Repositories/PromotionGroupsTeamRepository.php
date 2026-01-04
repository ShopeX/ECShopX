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
use Doctrine\Common\Collections\Criteria;
use PromotionsBundle\Entities\PromotionGroupsTeam;

use Dingo\Api\Exception\ResourceException;

class PromotionGroupsTeamRepository extends EntityRepository
{
    public $table = 'promotion_groups_team';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PromotionGroupsTeam();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
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
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    public function updateBySimpleFilter($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        $data['updated'] = time();
        return $conn->update($this->table, $data, $filter);
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception(trans("PromotionsBundle.delete_data_not_exist"));
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new \Exception(trans("PromotionsBundle.delete_data_not_exist"));
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
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

        return $this->getColumnNamesData($entity);
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
     * 增加参团人数
     * @param $itemId 商品id
     * @param $personNum 人数
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateNum($filter, $personNum)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
        }
        $data['join_person_num'] = $entity->getJoinPersonNum() + 1;
        if (1 == $data['join_person_num']) {
            $data['disabled'] = false;
        }
        if ($data['join_person_num'] >= $personNum) {
            $data['team_status'] = 2;
        }
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 参团完成
     * @param $itemId 商品id
     * @param $personNum 人数
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setNum($id, $personNum)
    {
        $itemsEnt = $this->find($id);
        if (!$itemsEnt) {
            return true;
        }
        $itemsEnt->setJoinPersonNum($personNum);
        $itemsEnt->setTeamStatus(2);
        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
    }

    public function getTeamGroupList($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $criteria->select('count(*)')
            ->from($this->table, 'p')
            ->leftJoin('p', 'promotion_groups_activity', 'a', 'p.act_id = a.groups_activity_id');

        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria->andWhere($criteria->expr()->$k($v, $criteria->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                $criteria->andWhere($criteria->expr()->in($field, $value));
            } else {
                $criteria->andWhere($criteria->expr()->eq($field, $criteria->expr()->literal($value)));
            }
        }
        $res['total_count'] = $criteria->execute()->fetchColumn();

        if ($res['total_count']) {
            foreach ($orderBy as $key => $value) {
                $criteria->addOrderBy($key, $value);
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $res['list'] = $criteria->select('p.*,a.robot,a.person_num')->execute()->fetchAll();

        return $res;
    }

    public function getOrderList($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $head_mid = false)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $criteria->select('count(*)')
            ->from($this->table, 'p');
        if ($head_mid) {
            $criteria->leftJoin('p', 'promotion_groups_team_member', 'm', 'p.team_id = m.team_id and p.head_mid = m.member_id');
        } else {
            $criteria->leftJoin('p', 'promotion_groups_team_member', 'm', 'p.team_id = m.team_id');
        }
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere($criteria->expr()->$k($v, $criteria->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere($criteria->expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere($criteria->expr()->eq($field, $criteria->expr()->literal($value)));
            }
        }
        $res['total_count'] = $criteria->execute()->fetchColumn();

        if ($res['total_count']) {
            foreach ($orderBy as $key => $value) {
                $criteria->addOrderBy($key, $value);
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $res['list'] = $criteria->select('p.*,m.order_id,m.member_info')->execute()->fetchAll();

        return $res;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
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
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if (isset($data["team_id"]) && $data["team_id"]) {
            $entity->setTeamId($data["team_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["act_id"]) && $data["act_id"]) {
            $entity->setActId($data["act_id"]);
        }
        if (isset($data["head_mid"]) && $data["head_mid"]) {
            $entity->setHeadMid($data["head_mid"]);
        }
        if (isset($data["begin_time"]) && $data["begin_time"]) {
            $entity->setBeginTime($data["begin_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
        }
        if (isset($data["group_goods_type"])) {
            $entity->setGroupGoodsType($data["group_goods_type"]);
        }
        if (isset($data["join_person_num"])) {
            $entity->setJoinPersonNum($data["join_person_num"]);
        }
        if (isset($data["team_status"]) && $data["team_status"]) {
            $entity->setTeamStatus($data["team_status"]);
        }
        //当前字段非必填
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'team_id' => $entity->getTeamId(),
            'company_id' => $entity->getCompanyId(),
            'act_id' => $entity->getActId(),
            'head_mid' => $entity->getHeadMid(),
            'begin_time' => $entity->getBeginTime(),
            'end_time' => $entity->getEndTime(),
            'join_person_num' => $entity->getJoinPersonNum(),
            'team_status' => $entity->getTeamStatus(),
            'group_goods_type' => $entity->getGroupGoodsType(),
            'disabled' => $entity->getDisabled(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }
}
