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
use PromotionsBundle\Entities\CouponGiveErrorLog;

use Dingo\Api\Exception\ResourceException;

class CouponGiveErrorLogRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'coupon_give_error_log';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new CouponGiveErrorLog();
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
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        $criteria->select('count(*)')
            ->from($this->table, 'c')
            ->leftJoin('c', 'kaquan_discount_cards', 'k', 'c.card_id = k.card_id');

        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere($criteria->expr()->$k('c.' . $v, $criteria->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere($criteria->expr()->in('c.' . $field, $value));
            } else {
                $criteria = $criteria->andWhere($criteria->expr()->eq('c.' . $field, $criteria->expr()->literal($value)));
            }
        }
        $res['total_count'] = intval($criteria->execute()->fetchColumn());

        if ($res['total_count']) {
            foreach ($orderBy as $key => $value) {
                $criteria->addOrderBy('c.'.$key, $value);
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $res['list'] = $criteria->select('c.*, k.title')->execute()->fetchAll();

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
        if (isset($data["give_id"]) && $data["give_id"]) {
            $entity->setGiveId($data["give_id"]);
        }
        if (isset($data["uid"]) && $data["uid"]) {
            $entity->setUid($data["uid"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["card_id"]) && $data["card_id"]) {
            $entity->setCardId($data["card_id"]);
        }
        if (isset($data["note"]) && $data["note"]) {
            $entity->setNote($data["note"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
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
            'give_log_id' => $entity->getGiveLogId(),
            'give_id' => $entity->getGiveId(),
            'uid' => $entity->getUid(),
            'company_id' => $entity->getCompanyId(),
            'card_id' => $entity->getCardId(),
            'note' => $entity->getNote(),
            'created' => $entity->getCreated(),
        ];
    }
}
