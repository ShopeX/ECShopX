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
use MembersBundle\Entities\MembersAssociations;

use Dingo\Api\Exception\ResourceException;

class MembersAssociationsRepository extends EntityRepository
{
    private $table = 'members_associations';

    public function create($params)
    {
        $filter = [
            'user_id' => $params['user_id'],
            'unionid' => $params['unionid'],
            'user_type' => $params['user_type'],
            'company_id' => $params['company_id'],
        ];
        $assoc = $this->findOneBy($filter);
        if (!$assoc) {
            $assocEntity = new MembersAssociations();
            $assoc = $this->setAssocData($assocEntity, $params);

            $em = $this->getEntityManager();
            $em->persist($assoc);
            $em->flush();
        }

        $result = $this->getAssocData($assoc);

        return $result;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function get(array $filter)
    {
        $assocEntity = $this->findOneBy($filter);
        if (!$assocEntity) {
            return [];
        }

        return $this->getAssocData($assocEntity);
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['user_id' => 'DESC'])
    {
        $commonKey = ['user_id','company_id','unionid'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table, 'm')
            ->leftJoin('m', 'members_wechatusers', 'f', 'm.unionid = f.unionid');
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    if (in_array($k, ['like', 'notlike'])) {
                        $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal('%'.$value.'%')));
                    } elseif ($k == 'in') {
                        $qb->andWhere($qb->expr()->$k($v, $value));
                    } else {
                        $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                    }
                    continue;
                } elseif (in_array($field, $commonKey)) {
                    if (is_array($value)) {
                        $qb->andWhere($qb->expr()->in('m.'.$field, $value));
                    } else {
                        $qb->andWhere($qb->expr()->eq('m.'.$field, $qb->expr()->literal($value)));
                    }
                } else {
                    $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
                }
            }
        }
        $res['total_count'] = $qb->execute()->fetchColumn();
        if ($limit > 0) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }
        foreach ($orderBy as $key => $value) {
            if (in_array($key, $commonKey)) {
                $qb->addOrderBy('m.'.$key, $value);
            } else {
                $qb->addOrderBy($key, $value);
            }
        }
        $res['list'] = $qb->select('m.*, f.nickname')->execute()->fetchAll();
        foreach ($res['list'] as $key => $list) {
            $res['list'][$key]['nickname'] = fixeddecrypt($list['nickname']);
        }
        return $res;
    }


    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
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
        return $lists;
    }

    private function setAssocData($assocEntity, $data)
    {
        if (isset($data['user_id'])) {
            $assocEntity->setUserId($data['user_id']);
        }
        if (isset($data['unionid'])) {
            $assocEntity->setUnionid($data['unionid']);
        }
        if (isset($data['company_id'])) {
            $assocEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['user_type'])) {
            $assocEntity->setUserType($data['user_type']);
        }

        return $assocEntity;
    }

    private function getAssocData($assocEntity)
    {
        return [
            'user_id' => $assocEntity->getUserId(),
            'unionid' => $assocEntity->getUnionid(),
            'company_id' => $assocEntity->getCompanyId(),
            'user_type' => $assocEntity->getUserType(),
        ];
    }

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

        $entity = $this->setAssocData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getAssocData($entity);
    }
}
