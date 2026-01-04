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
use MembersBundle\Entities\MembersInfo;

use Dingo\Api\Exception\ResourceException;

class MembersInfoRepository extends EntityRepository
{
    public $table = 'members_info';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new MembersInfo();
        $entity = $this->setMemberInfoData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getMemberInfoData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param array $filter 更新的条件
     * @param array $data 更新的内容
     * @param array $userInfo 会员的老数据
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateOneBy(array $filter, array $data, array &$userInfo = [])
    {
        $filter = $this->fixedencryptCol($filter);
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        } else {
            $userInfo = $this->getMemberInfoData($entity);
        }

        $entity = $this->setMemberInfoData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getMemberInfoData($entity);
    }

    public function getListNotPagination($filter, $cols = '*')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        $list = $qb->execute()->fetchAll();
        foreach ($list as $key => $item) {
            if (isset($item['username']) && $item['username']) {
                $list[$key]['username'] = fixeddecrypt($item['username']);
            }
        }
        return $list;
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $filter = $this->fixedencryptCol($filter);
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setMemberInfoData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getMemberInfoData($entityProp);
        }
        return $result;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $filter = $this->fixedencryptCol($filter);
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getMemberInfoData($entity);
    }

    public function count($filter)
    {
        $filter = $this->fixedencryptCol($filter);
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["user_id" => "DESC"], $pageSize = 100, $page = 1)
    {
        $filter = $this->fixedencryptCol($filter);
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
            $criteria = $criteria->orderBy($orderBy);
            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getMemberInfoData($entity);
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
    private function setMemberInfoData($entity, $data)
    {
        if (isset($data['user_id']) && $data['user_id']) {
            $entity->setUserId($data["user_id"]);
        }

        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data["company_id"]);
        }

        if (isset($data["username"])) {
            $entity->setUsername($data["username"]);
        }

        if (isset($data["name"])) {
            $entity->setName($data["name"]);
        }

        if (isset($data["avatar"])) {
            $entity->setAvatar($data["avatar"]);
        }

        if (isset($data["sex"])) {
            $entity->setSex($data["sex"]);
        }

        if (isset($data["birthday"]) && $data["birthday"]) {
            $entity->setBirthday($data["birthday"]);

            $birthday = explode('-', $data["birthday"]);
            $entity->setYear($birthday[0]);
            $entity->setMonth($birthday[1]);
            $entity->setDay($birthday[2]);
        }

        if (isset($data["address"])) {
            $entity->setAddress($data["address"]);
        }

        if (isset($data["email"])) {
            $entity->setEmail($data["email"]);
        }

        if (isset($data["industry"])) {
            $entity->setIndustry($data["industry"]);
        }

        if (isset($data["income"])) {
            $entity->setIncome($data["income"]);
        }

        if (isset($data["edu_background"])) {
            $entity->setEduBackground($data["edu_background"]);
        }

        if (isset($data["habbit"])) {
            $entity->setHabbit($data["habbit"]);
        }

        if (isset($data['have_consume'])) {
            $entity->setHaveConsume($data['have_consume']);
        }

        if (isset($data['other_params'])) {
            $entity->setOtherParams($data['other_params']);
        }

        if (isset($data['dm_member_id'])) {
            $entity->setDmMemberId($data['dm_member_id']);
        }

        if (isset($data['dm_card_no'])) {
            $entity->setDmCardNo($data['dm_card_no']);
        }

        return $entity;
    }

    public function getListByUserIds($companyId, $userIds): array
    {
        $criteria = Criteria::create();
        $criteria = $criteria->where(Criteria::expr()->eq('company_id', $companyId));
        $criteria = $criteria->andWhere(Criteria::expr()->in('user_id', $userIds));

        $list = [];
        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $list[] = $this->getMemberInfoData($entity);
        }
        return $list;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getMemberInfoData($entity)
    {
        return [
            'user_id' => $entity->getUserId(),
            'company_id' => $entity->getCompanyId(),
            'username' => $entity->getUsername(),
            'name' => $entity->getName(),
            'avatar' => $entity->getAvatar(),
            'sex' => $entity->getSex(),
            'birthday' => $entity->getBirthday(),
            'address' => $entity->getAddress(),
            'email' => $entity->getEmail(),
            'industry' => $entity->getIndustry(),
            'income' => $entity->getIncome(),
            'edu_background' => $entity->getEduBackground(),
            'habbit' => $entity->getHabbit(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'have_consume' => $entity->getHaveConsume(),
            'other_params' => $entity->getOtherParams(),
            'dm_member_id' => $entity->getDmMemberId(),
            'dm_card_no' => $entity->getDmCardNo(),
        ];
    }

    public function getDataList($filter, $cols = "user_id, username", $page = 1, $pageSize = -1)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    private function _filter($filter, $qb)
    {
        $filter = $this->fixedencryptCol($filter);
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
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

    /**
     * 对filter中的部分字段，加密处理
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    private function fixedencryptCol($filter)
    {
        $fixedencryptCol = ['username'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        return $filter;
    }
}
