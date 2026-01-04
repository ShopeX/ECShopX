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

namespace AdaPayBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use AdaPayBundle\Entities\AdapayMember;

use Dingo\Api\Exception\ResourceException;

class AdapayMemberRepository extends EntityRepository
{
    public $table = "adapay_member";
    public $cols = ['id','app_id','location','pid','is_update','company_id','operator_id','operator_type','email','member_type','gender','nickname','tel_no','user_name','cert_type','cert_id','audit_state','audit_desc','status','error_info','create_time','update_time','valid','is_sms', 'is_created'];
    public $encrypt = ['tel_no','user_name','cert_id'];

    /**
     * 新增
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        // Hash: 0d723eca
        $entity = new AdapayMember();
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
            throw new ResourceException("未查询到个人经销商更新数据");
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
//    public function updateBy(array $filter, array $data)
//    {
//        $conn = app("registry")->getConnection("default");
//        $qb = $conn->createQueryBuilder()->update($this->table);
//        foreach($data as $key=>$val) {
//            $qb = $qb->set($key, $qb->expr()->literal($val));
//        }
//
//        $qb = $this->_filter($filter, $qb);
//
//        return $qb->execute();
//    }
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
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
        // Hash: 0d723eca
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

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            if (method_exists($entity, $fun)) {
                $values[$col] = $entity->$fun();
            }
        }
        return $values;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
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

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
              ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();

        $lists = $this->decrypt($lists ?? []);

        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }

        $lists = $this->decrypt($lists ?? []);

        $result['list'] = $lists ?? [];
        return $result;
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
     * @param array $filter 更新的条件
     * @param array $orderBy
     * @return array
     */
    public function getInfo(array $filter, array $orderBy = [])
    {
        $entity = $this->findOneBy($filter, $orderBy);
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
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(id)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    public function MemberRelCorpLists($filter, $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('adapay_member', 'member')
            ->leftJoin('member', 'adapay_corp_member', 'corp', 'member.id = corp.member_id')
            ->andWhere($criteria->expr()->eq('member.company_id', $criteria->expr()->literal($filter['company_id'])))
            ->andWhere($criteria->expr()->NotIn('member.operator_type', $criteria->expr()->literal('promoter')));

//        if (isset($filter['legal_person']) && $filter['legal_person']) {
//            $criteria->andWhere($criteria->expr()->eq('corp.legal_person', $criteria->expr()->literal($filter['legal_person'])));
//        }
//
//        if (isset($filter['user_name']) && $filter['user_name']) {
//            $criteria->andWhere($criteria->expr()->eq('member.user_name', $criteria->expr()->literal($filter['user_name'])));
//        }

        if (isset($filter['member_type']) && $filter['member_type']) {
            $criteria->andWhere($criteria->expr()->eq('member.member_type', $criteria->expr()->literal($filter['member_type'])));
        }

        if (isset($filter['operator_type']) && $filter['operator_type']) {
            $criteria->andWhere($criteria->expr()->eq('member.operator_type', $criteria->expr()->literal($filter['operator_type'])));
        }

//        if (isset($filter['location']) && $filter['location']) {
//            $criteria->andWhere($criteria->expr()->eq('member.location', $criteria->expr()->literal($filter['location'])));
//        }
        if (isset($filter['keywords']) && $filter['keywords']) {
            $criteria->andWhere(
                $criteria->expr()->orX(
                    $criteria->expr()->eq('corp.legal_person', $criteria->expr()->literal(fixedencrypt($filter['keywords']))),
                    $criteria->expr()->eq('member.user_name', $criteria->expr()->literal(fixedencrypt($filter['keywords']))),
                    $criteria->expr()->eq('member.location', $criteria->expr()->literal($filter['keywords']))
                )
            );
        }

        $list['total_count'] = intval($criteria->execute()->fetchColumn());
        $criteria->select('member.id, member.company_id, member.location, member.user_name, member.member_type, member.operator_type, corp.legal_person');

        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $criteria->addOrderBy("member.$filed", $val);
            }
        }

        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }
        $lists = $criteria->execute()->fetchAll();

        $this->encrypt[] = 'legal_person';
        $encryptCols = $this->encrypt;
        $lists = $this->decrypt($lists ?? [], $encryptCols);

        $list['list'] = $lists;
        return $list;
    }

    public function decrypt($lists = [], $encryptCols = [])
    {
        $encryptCols = $encryptCols ? $encryptCols : $this->encrypt;
        foreach ($lists as $key => $value) {
            foreach ($encryptCols as $col) {
                isset($value[$col]) and $lists[$key][$col] = fixeddecrypt($value[$col]);
            }
        }

        return $lists;
    }
}
