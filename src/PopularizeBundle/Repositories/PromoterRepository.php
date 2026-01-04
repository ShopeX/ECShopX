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

namespace PopularizeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use PopularizeBundle\Entities\Promoter;

class PromoterRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // ShopEx EcShopX Business Logic Layer
        $entity = new Promoter();
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
            return true;
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
            return true;
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
            return true;
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
            return true;
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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
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
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            }
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
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        if (isset($data["identity_id"]) && $data["identity_id"]) {
            $entity->setIdentityId($data["identity_id"]);
        }
        if (isset($data["is_subordinates"]) && $data["is_subordinates"]) {
            $entity->setIsSubordinates($data["is_subordinates"]);
        }
        //当前字段非必填
        if (array_key_exists('pid', $data)) {
            $entity->setPid($data["pid"]);
        }
        if (array_key_exists('pmobile', $data)) {
            $entity->setPmobile($data["pmobile"]);
        }
        if (array_key_exists('pname', $data)) {
            $entity->setPname($data["pname"]);
        }
        if (isset($data["promoter_name"]) && $data['promoter_name']) {
            $entity->setPromoterName($data["promoter_name"]);
        }
        if (isset($data["regions_id"]) && $data['regions_id']) {
            $entity->setRegionsId($data["regions_id"]);
        }
        if (isset($data["address"]) && $data['address']) {
            $entity->setAddress($data["address"]);
        }
        if (isset($data["shop_name"])) {
            $entity->setShopName($data["shop_name"]);
        }
        if (isset($data["alipay_account"])) {
            $entity->setAlipayAccount($data["alipay_account"]);
        }
        if (isset($data["alipay_name"])) {
            $entity->setAlipayName($data["alipay_name"]);
        }
        if (isset($data["shop_pic"])) {
            $entity->setShopPic($data["shop_pic"]);
        }
        if (isset($data["brief"])) {
            $entity->setBrief($data["brief"]);
        }
        if (isset($data["grade_level"])) {
            $entity->setGradeLevel($data["grade_level"]);
        }
        if (isset($data["is_promoter"])) {
            $entity->setIsPromoter($data["is_promoter"]);
        }
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
        }
        if (isset($data["is_buy"])) {
            $entity->setIsBuy($data["is_buy"]);
        }
        if (isset($data["reason"])) {
            $entity->setReason($data["reason"]);
        }
        if (isset($data["shop_status"])) {
            $entity->setShopStatus($data["shop_status"]);
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
            'id' => $entity->getId(),
            'promoter_id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'user_id' => $entity->getUserId(),
            'identity_id' => $entity->getIdentityId(),
            'is_subordinates' => $entity->getIsSubordinates(),
            'promoter_name' => $entity->getPromoterName(),
            'regions_id' => $entity->getRegionsId(),
            'address' => $entity->getAddress(),
            'shop_name' => $entity->getShopName(),
            'alipay_name' => $entity->getAlipayName(),
            'shop_pic' => $entity->getShopPic(),
            'brief' => $entity->getBrief(),
            'alipay_account' => $entity->getAlipayAccount(),
            'pid' => intval($entity->getPid()),
            'shop_status' => $entity->getShopStatus(),
            'reason' => $entity->getReason(),
            'pmobile' => $entity->getPmobile(),
            'pname' => $entity->getPname(),
            'grade_level' => $entity->getGradeLevel(),
            'is_promoter' => $entity->getIsPromoter(),
            'disabled' => $entity->getDisabled(),
            'is_buy' => $entity->getIsBuy(),
            'created' => $entity->getCreated(),
        ];
    }

    /**
    * 查询未注销的推广员列表
    * @param $filter
    * @param int $page
    * @param int $pageSize
    * @param array $orderBy
    * @return array
    */
    public function getLists($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        $cols = 'p.*,m.user_id members_user_id';
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('popularize_promoter', 'p')
            ->leftJoin('p', 'members', 'm', 'p.user_id = m.user_id');
        if (isset($filter['company_id'])) {
            $filter['p.company_id'] = $filter['company_id'];
            unset($filter['company_id']);
        }
        if (isset($filter['user_id'])) {
            $filter['p.user_id'] = $filter['user_id'];
            unset($filter['user_id']);
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
        $criteria->andWhere($criteria->expr()->isNotNull('m.user_id'));
        $res['total_count'] = $criteria->execute()->fetchColumn();
        $lists = [];
        if ($res["total_count"]) {
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $criteria->addOrderBy($filed, $val);
                }
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $lists = $criteria->select($cols)->execute()->fetchAll();
        }
        $where['id'] = array_column($lists, 'id');
        $dataLists = $this->lists($where, 1, $pageSize);
        $res["list"] = $dataLists['list'];
        return $res;
    }
}
