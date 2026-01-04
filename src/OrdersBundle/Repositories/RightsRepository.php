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

namespace OrdersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use OrdersBundle\Entities\Rights;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class RightsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'orders_rights';

    /**
     * 添加权益
     */
    public function create($params)
    {
        // ShopEx EcShopX Service Component
        $rightsEnt = new Rights();

        $rightsEnt->setCompanyId($params['company_id']);
        $rightsEnt->setUserId($params['user_id']);
        $rightsEnt->setRightsName($params['rights_name']);
        $rightsEnt->setRightsSubname($params['rights_subname']);
        $rightsEnt->setStartTime($params['start_time']);
        $rightsEnt->setEndTime($params['end_time']);
        $rightsEnt->setRightsFrom($params['rights_from']);

        $isNotLimitNum = $params['is_not_limit_num'] ?? 2;
        $rightsEnt->setIsNotLimitNum($isNotLimitNum);

        $totalNum = ($isNotLimitNum == 2) ? $params['total_num'] : 0;
        $rightsEnt->setTotalNum($totalNum);

        $rightsEnt->setTotalConsumNum($params['total_consum_num']);

        if (isset($params['status'])) {
            $rightsEnt->setStatus($params['status']);
        }

        if (isset($params['operator_desc'])) {
            $rightsEnt->setOperatorDesc($params['operator_desc']);
        }
        $rightsEnt->setMobile($params['mobile']);
        $rightsEnt->setCanReservation($params['can_reservation']);
        $rightsEnt->setLabelInfos(json_encode($params['label_infos']));
        if (isset($params['order_id'])) {
            $rightsEnt->setOrderId($params['order_id']);
        }

        $em = $this->getEntityManager();
        $em->persist($rightsEnt);
        $em->flush();
        $result = [
            'rights_id' => $rightsEnt->getRightsId(),
            'user_id' => $rightsEnt->getUserId(),
            'company_id' => $rightsEnt->getCompanyId(),
            'can_reservation' => $rightsEnt->getCanReservation(),
            'rights_name' => $rightsEnt->getRightsName(),
            'rights_subname' => $rightsEnt->getRightsSubname(),
            'total_num' => $rightsEnt->getTotalNum(),
            'total_consum_num' => $rightsEnt->getTotalConsumNum(),
            'start_time' => $rightsEnt->getStartTime(),
            'end_time' => $rightsEnt->getEndTime(),
            'created' => $rightsEnt->getCreated(),
            'updated' => $rightsEnt->getUpdated(),
            'order_id' => $rightsEnt->getOrderId(),
            'label_infos' => json_decode($rightsEnt->getLabelInfos(), 1),
            'is_not_limit_num' => $rightsEnt->getIsNotLimitNum(),
            'status' => $rightsEnt->getStatus(),
        ];

        return $result;
    }

    /**
     * 更新权益信息
     */
    public function update($rights_id, $params, $type = 'update')
    {
        $rightsEnt = $this->findOneBy(['rights_id' => $rights_id, 'company_id' => $params['company_id']]);

        if (!$rightsEnt) {
            throw new ResourceException("权益不存在");
        }

        if ($type == 'consume') {
            if ($rightsEnt->getEndTime() < time()) {
                throw new ResourceException("核销的权益已过期");
            }
            $isNotLimitNum = $rightsEnt->getIsNotLimitNum();
            $params['consum_num'] = $params['consum_num'] ?? 1;
            switch ($isNotLimitNum) {
                case 1:
                    $totalConsumNum = $rightsEnt->getTotalConsumNum() + $params['consum_num'];
                    $rightsEnt->setTotalConsumNum($totalConsumNum);
                    break;
                case 2:
                    if (($rightsEnt->getTotalNum() - $rightsEnt->getTotalConsumNum()) < $params['consum_num']) {
                        throw new ResourceException("核销的权益数量超过限制");
                    }
                    $totalConsumNum = $rightsEnt->getTotalConsumNum() + $params['consum_num'];
                    $rightsEnt->setTotalConsumNum($totalConsumNum);
                    break;
            }
        }

        if (isset($params['end_time'])) {
            $rightsEnt->setEndTime($params['end_time']);
        }

        if (isset($params['mobile'])) {
            $rightsEnt->setMobile($params['mobile']);
        }

        if (isset($params['user_id'])) {
            $rightsEnt->setUserId($params['user_id']);
        }
        if (isset($params['status'])) {
            $rightsEnt->setStatus($params['status']);
        }

        $em = $this->getEntityManager();
        $em->persist($rightsEnt);
        $em->flush();
        $result = [
            'rights_id' => $rightsEnt->getRightsId(),
            'user_id' => $rightsEnt->getUserId(),
            'company_id' => $rightsEnt->getCompanyId(),
            'can_reservation' => $rightsEnt->getCanReservation(),
            'rights_name' => $rightsEnt->getRightsName(),
            'mobile' => $rightsEnt->getMobile(),
            'rights_subname' => $rightsEnt->getRightsSubname(),
            'total_num' => $rightsEnt->getTotalNum(),
            'total_consum_num' => $rightsEnt->getTotalConsumNum(),
            'start_time' => $rightsEnt->getStartTime(),
            'end_time' => $rightsEnt->getEndTime(),
            'created' => $rightsEnt->getCreated(),
            'updated' => $rightsEnt->getUpdated(),
            'order_id' => $rightsEnt->getOrderId(),
            'label_infos' => json_decode($rightsEnt->getLabelInfos(), 1),
            'is_not_limit_num' => $rightsEnt->getIsNotLimitNum(),
            'status' => $rightsEnt->getStatus(),
        ];

        return $result;
    }

    /**
     * 删除权益
     */
    public function delete($rights_id)
    {
        $delRightsEntity = $this->find($rights_id);
        if (!$delRightsEntity) {
            throw new DeleteResourceFailedException("rights_id={$rights_id}的权益不存在");
        }
        $this->getEntityManager()->remove($delRightsEntity);

        return $this->getEntityManager()->flush($delRightsEntity);
    }

    /**
     * 获取权益详细信息
     */
    public function get($rights_id)
    {
        $rightsEnt = $this->find($rights_id);
        if (!$rightsEnt) {
            throw new ResourceException("rights_id={$rights_id}的权益不存在");
        }
        $result = [
            'rights_id' => $rightsEnt->getRightsId(),
            'user_id' => $rightsEnt->getUserId(),
            'company_id' => $rightsEnt->getCompanyId(),
            'can_reservation' => $rightsEnt->getCanReservation(),
            'rights_name' => $rightsEnt->getRightsName(),
            'rights_subname' => $rightsEnt->getRightsSubname(),
            'total_num' => $rightsEnt->getTotalNum(),
            'total_consum_num' => $rightsEnt->getTotalConsumNum(),
            'is_not_limit_num' => $rightsEnt->getIsNotLimitNum(),
            'status' => $rightsEnt->getStatus(),
            'start_time' => $rightsEnt->getStartTime(),
            'end_time' => $rightsEnt->getEndTime(),
            'created' => $rightsEnt->getCreated(),
            'updated' => $rightsEnt->getUpdated(),
            'order_id' => $rightsEnt->getOrderId(),
            'rights_from' => $rightsEnt->getRightsFrom(),
            'operator_desc' => $rightsEnt->getOperatorDesc(),
            'label_infos' => json_decode($rightsEnt->getLabelInfos(), 1),
        ];

        return $result;
    }

    public function count($filter = [])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(rights_id) as _count');
        $qb->from($this->table);
        $this->_filter($filter, $qb);
        $total = $qb->execute()->fetchColumn();

        return $total;
    }

    public function _filter($filter, &$qb)
    {
        $fixedencryptCol = ['mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt((string) $filter[$col]);
            }
        }
        if (isset($filter['valid']) && $filter['valid'] == 1) {
            $qb->andWhere($qb->expr()->eq('status', $qb->expr()->literal('valid')));
            // $qb->where('total_num>total_consum_num');
            // $qb->andWhere($qb->expr()->gt('end_time', time()));
            unset($filter['valid']);
        }
        if (isset($filter['valid']) && $filter['valid'] == 0) {
            $qb->andWhere($qb->expr()->neq('status', $qb->expr()->literal('valid')));
            // $qb->where('total_num<=total_consum_num');
            // $qb->orWhere($qb->expr()->lt('end_time', time()));
            unset($filter['valid']);
        }

        foreach ($filter as $field => $value) {
            if ($field == "datetime" && is_array($value) && $value) {
                $qb->andWhere($qb->expr()->gt('end_time', $value[0]));
                $qb->andWhere($qb->expr()->lt('end_time', $value[1]));
            } elseif (is_array($value) && $value) {
                $qb->andWhere($qb->expr()->in($field, $value));
            } elseif ($field == "start_time") {
                $qb->andWhere($qb->expr()->lte($field, $value));
            } elseif ($field == "end_time") {
                $qb->andWhere($qb->expr()->gt($field, $value));
            } else {
                if ($value) {
                    $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
                }
            }
        }
    }

    /**
     * 获取权益列表
     */
    public function list($filter, $orderBy = ['created' => 'DESC'], $pageSize = 100, $page = 1)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('*')
           ->from($this->table)
           ->orderBy(key($orderBy), current($orderBy))
           ->setFirstResult(($page - 1) * $pageSize)
           ->setMaxResults($pageSize);

        $this->_filter($filter, $qb);

        $result = $qb->execute()->fetchAll();

        foreach ($result as &$v) {
            $v['label_infos'] = json_decode($v['label_infos'], 1);
            $v['mobile'] = fixeddecrypt($v['mobile']);
        }
        $total = $this->count($filter);
        $res['total_count'] = $total;
        $res['list'] = $result;
        return $res;
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateStatusBy(array $filter, array $data)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $val = ($val === true) ? 1 : 0;
            $qb = $qb->set($key, $val);
        }
        if (isset($filter['is_not_limit_num']) && $filter['is_not_limit_num'] == 2 && $data['status'] == 'invalid') {
            $qb->where('total_num<=total_consum_num');
        }
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%' . $value . '%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb->execute();
    }
}
