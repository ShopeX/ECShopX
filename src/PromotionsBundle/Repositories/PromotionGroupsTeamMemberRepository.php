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
use PromotionsBundle\Entities\PromotionGroupsTeamMember;

use Dingo\Api\Exception\ResourceException;

class PromotionGroupsTeamMemberRepository extends EntityRepository
{
    public $table = 'promotion_groups_team_member';
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PromotionGroupsTeamMember();
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
     * 获取列表数据
     * @param $filter
     * @param string[] $orderBy
     * @param int $pageSize
     * @param int $page
     * @return array
     */
    public function getLists($filter, $orderBy = ["join_time" => "DESC"], $pageSize = 100, $page = 1)
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

        $lists = [];
        $criteria = $criteria->orderBy($orderBy);
        if ($pageSize > 0) {
            $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
        }
        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }

        return $lists;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["join_time" => "DESC"], $pageSize = 100, $page = 1)
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

    public function getTeamMemberOrderList($filter, $orderBy = ["m.join_time" => "DESC"], $pageSize = 100, $page = 1)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $criteria->select('count(*)')
            ->from($this->table, 'm')
            ->leftJoin('m', 'orders_associations', 'o', 'm.order_id = o.order_id');

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
        $res['list'] = $criteria->select('m.*,o.*')->execute()->fetchAll();

        return $res;
    }

    /**
     * 获取支付时间超出拼团时间的数据
     * @param array $teamIds 拼团的队伍id
     * @return array
     */
    public function getPaymentOverEndTimeList(array $teamIds): array
    {
        if (empty($teamIds)) {
            return [];
        }
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        return $criteria->select("team.act_id,team.end_time,team_member.team_id,team_member.member_id,team_member.order_id, trade.time_expire")
            ->from($this->table, "team_member")
            ->leftJoin("team_member", "trade", "trade", "team_member.order_id = trade.order_id")
            ->leftJoin("team_member", "promotion_groups_team", "team", "team_member.team_id = team.team_id")
            // 交易单必须是交易完成
            ->andWhere($criteria->expr()->eq("trade.trade_state", $criteria->expr()->literal("SUCCESS")))
            // 交易单的交易结束时间 大于 拼团的结束时间
            ->andWhere($criteria->expr()->gt("trade.time_expire", "team.end_time"))
            ->andWhere($criteria->expr()->gt("team_member.member_id", 0))
            ->andWhere($criteria->expr()->eq("team_member.disabled", 0))
            ->andWhere($criteria->expr()->in("team_member.team_id", $teamIds))
            ->execute()
            ->fetchAll();
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
        if (isset($data["member_id"])) {
            $entity->setMemberId($data["member_id"]);
        }
        if (isset($data["join_time"]) && $data["join_time"]) {
            $entity->setJoinTime($data["join_time"]);
        }
        if (isset($data["group_goods_type"])) {
            $entity->setGroupGoodsType($data["group_goods_type"]);
        }
        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["member_info"]) && $data["member_info"]) {
            $entity->setMemberInfo($data["member_info"]);
        }
        //当前字段非必填
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
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
            'member_id' => $entity->getMemberId(),
            'join_time' => $entity->getJoinTime(),
            'order_id' => $entity->getOrderId(),
            'group_goods_type' => $entity->getGroupGoodsType(),
            'member_info' => json_decode($entity->getMemberInfo()),
            'disabled' => $entity->getDisabled(),
        ];
    }
}
