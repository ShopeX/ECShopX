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
use OrdersBundle\Entities\RightsLog;
use Dingo\Api\Exception\ResourceException;

class RightsLogRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'orders_rights_log';

    /**
     * 添加权益
     */
    public function create($params)
    {
        // ShopEx EcShopX Service Component
        $rightsLogEnt = new RightsLog();

        $rightsLogEnt->setRightsId($params['rights_id']);
        $rightsLogEnt->setCompanyId($params['company_id']);
        $rightsLogEnt->setRightsName($params['rights_name']);
        $rightsLogEnt->setUserId($params['user_id']);
        $rightsLogEnt->setRightsSubname($params['rights_subname']);
        $rightsLogEnt->setShopId($params['shop_id']);
        $rightsLogEnt->setConsumNum($params['consum_num']);
        $rightsLogEnt->setAttendant($params['attendant']);
        $rightsLogEnt->setSalespersonMobile($params['salesperson_mobile']);
        $rightsLogEnt->setConsumTime(time());

        $em = $this->getEntityManager();
        $em->persist($rightsLogEnt);
        $em->flush();
        $result = [
            'rights_id' => $rightsLogEnt->getRightsId(),
            'company_id' => $rightsLogEnt->getCompanyId(),
            'user_id' => $rightsLogEnt->getUserId(),
            'rights_name' => $rightsLogEnt->getRightsName(),
            'consum_num' => $rightsLogEnt->getConsumNum(),
            'rights_subname' => $rightsLogEnt->getRightsSubname(),
            'attendant' => $rightsLogEnt->getAttendant(),
            'consum_time' => $rightsLogEnt->getConsumTime(),
            'created' => $rightsLogEnt->getCreated(),
            'salesperson_mobile' => $rightsLogEnt->getSalespersonMobile(),
        ];

        return $result;
    }

    /**
     * 获取权益日志详细信息
     */
    public function get($rights_id)
    {
        $rightsLogEnt = $this->find($rights_id);
        if (!$rightsLogEnt) {
            throw new ResourceException("rights_id={$rights_id}的权益不存在");
        }
        $result = [
            'rights_id' => $rightsLogEnt->getRightsId(),
            'company_id' => $rightsLogEnt->getCompanyId(),
            'user_id' => $rightsLogEnt->getUserId(),
            'rights_name' => $rightsLogEnt->getRightsName(),
            'consum_num' => $rightsLogEnt->getConsumNum(),
            'rights_subname' => $rightsLogEnt->getRightsSubname(),
            'attendant' => $rightsLogEnt->getAttendant(),
            'consum_time' => $rightsLogEnt->getConsumTime(),
            'created' => $rightsLogEnt->getCreated(),
            'salesperson_mobile' => $rightsLogEnt->getSalespersonMobile(),
        ];

        return $result;
    }

    public function countLogNum($filter)
    {
        return $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);
    }

    /**
     * 获取权益列表
     */
    public function list($filter, $orderBy = ['created' => 'DESC'], $pageSize = 100, $page = 1)
    {
        $rightsLogList = $this->findBy($filter, $orderBy, $pageSize, $pageSize * ($page - 1));

        $newRightsLogList = [];
        foreach ($rightsLogList as $v) {
            $newRightsLogList[] = [
                'rights_id' => $v->getRightsId(),
                'user_id' => $v->getUserId(),
                'company_id' => $v->getCompanyId(),
                'shop_id' => $v->getShopId(),
                'rights_name' => $v->getRightsName(),
                'consum_num' => $v->getConsumNum(),
                'rights_subname' => $v->getRightsSubname(),
                'attendant' => $v->getAttendant(),
                'consum_time' => $v->getConsumTime(),
                'created' => $v->getCreated(),
                'salesperson_mobile' => $v->getSalespersonMobile(),
            ];
        }
        $total = $this->getEntityManager()
                      ->getUnitOfWork()
                      ->getEntityPersister($this->getEntityName())
                      ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $newRightsLogList;
        return $res;
    }

    //获取指定条件的列表
    public function getList($filter, $orderBy = ['created' => 'DESC'], $pageSize = 100, $page = 1)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $listData['total_count'] = $this->totalNum($filter);

        $offset = ($page - 1) * $pageSize;

        $qb->select('*')
            ->from($this->table)
            ->orderBy('created', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize);
        if ($filter) {
            $this->_filter($filter, $qb);
        }

        $listData['list'] = $qb->execute()->fetchAll();
        if ($listData['list']) {
            foreach ($listData['list'] as $key => $value) {
                $listData['list'][$key]['salesperson_mobile'] = fixeddecrypt($value['salesperson_mobile']);
            }
        }
        return $listData;
    }

    /**
     * [totalNum]
     * @param  array  $filter
     * @return int
     */
    public function totalNum($filter = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * [_filter description]
     * @param  [type] $filter
     * @param  [type] &$qb
     */
    private function _filter($filter, &$qb)
    {
        if (isset($filter['time_start_begin'])) {
            //$filter['time_start_begin'] = $qb->expr()->literal($filter['time_start_begin']);
            $qb->andWhere($qb->expr()->andX(
                $qb->expr()->gte('end_time', $filter['time_start_begin'])
            ));
        }
        if (isset($filter['time_start_end'])) {
            //$filter['time_start_end'] = $qb->expr()->literal($filter['time_start_end']);
            $qb->andWhere($qb->expr()->andX(
                $qb->expr()->lt('end_time', $filter['time_start_end'])
            ));
            unset($filter['time_start_begin'],$filter['time_start_end']);
        }
        if ($filter) {
            foreach ($filter as $key => $filterValue) {
                if (is_array($filterValue)) {
                    array_walk($filterValue, function (&$value) use ($qb) {
                        $value = $qb->expr()->literal($value);
                    });
                } else {
                    $filterValue = $qb->expr()->literal($filterValue);
                }
                $list = explode('|', $key);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->$k($v, $filterValue)
                    ));
                    continue;
                } else {
                    $qb->andWhere($qb->expr()->andX(
                        $qb->expr()->eq($key, $filterValue)
                    ));
                }
            }
        }
    }
}
