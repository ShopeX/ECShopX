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

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ReservationBundle\Entities\ReservationRecord;
use Doctrine\Common\Collections\Criteria;

class ReservationRecordRepository extends EntityRepository
{
    public $table = "reservation_record";

    public function create($postParams)
    {
        $reservationRecord = $this->__setRecord($postParams);

        $em = $this->getEntityManager();
        $em->persist($reservationRecord);
        $em->flush();

        $data = [
            'record_id' => $reservationRecord->getRecordId(),
            'company_id' => $reservationRecord->getCompanyId(),
            'to_shop_time' => $reservationRecord->getToShopTime(),
            'shop_name' => $reservationRecord->getShopName(),
            'mobile' => $reservationRecord->getMobile(),
            'rights_name' => $reservationRecord->getRightsName(),
            'shop_id' => $reservationRecord->getShopId(),
            'status' => $reservationRecord->getStatus(),
        ];
        return $data;
    }

    public function updateStatus($status, $filter)
    {
        $reservationRecord = $this->findOneBy($filter);
        $reservationRecord->setStatus($status);
        $em = $this->getEntityManager();
        $em->persist($reservationRecord);
        $em->flush();

        $data = [
            'record_id' => $reservationRecord->getRecordId(),
            'company_id' => $reservationRecord->getCompanyId(),
            'to_shop_time' => $reservationRecord->getToShopTime(),
            'shop_name' => $reservationRecord->getShopName(),
            'mobile' => $reservationRecord->getMobile(),
            'rights_name' => $reservationRecord->getRightsName(),
            'shop_id' => $reservationRecord->getShopId(),
            'status' => $reservationRecord->getStatus(),
        ];
        return $data;
    }

    public function get($filter)
    {
        $reservationRecord = $this->findOneBy($filter);
        return $reservationRecord;
    }

    /**
     * 获取预约记录列表
     *
     * @param filter
     * @param pageSize 查询个数
     * @param page 查询页数
     * @param order by
     */
    public function getList($filter, $pageSize = 100, $page = 1, $orderBy = ['agreement_date' => 'DESC', 'to_shop_time' => 'ASC'])
    {
        $data = [];
        $criteria = $this->__filter($filter);
        $criteria = $criteria->orderBy($orderBy)
            ->setFirstResult($pageSize * ($page - 1))
            ->setMaxResults($pageSize);
        $listDatas = $this->matching($criteria);
        foreach ($listDatas as $value) {
            $data[] = normalize($value);
        }
        return $data;
    }

    public function getCount($filter)
    {
        $criteria = $this->__filter($filter);
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        return intval($total);
    }

    private function __filter($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if ($field == "limit_begin_time") {
                $criteria = $criteria->andWhere(Criteria::expr()->gte('agreement_date', $value));
            } elseif ($field == "limit_end_time") {
                $criteria = $criteria->andWhere(Criteria::expr()->lte('agreement_date', $value));
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }
        return $criteria;
    }

    private function __setRecord($postParams)
    {
        $reservationRecord = new ReservationRecord();

        $reservationRecord->setCompanyId($postParams['company_id']);
        $reservationRecord->setShopId($postParams['shop_id']);
        $reservationRecord->setAgreementDate(strtotime($postParams['date_day']));
        $reservationRecord->setBeginTime($postParams['begin_time']);
        $reservationRecord->setEndTime($postParams['end_time']);
        $reservationRecord->setStatus($postParams['status']);
        $reservationRecord->setNum($postParams['num']);

        if ($postParams['begin_time'] && $postParams['date_day']) {
            $toShopTime = strtotime($postParams['date_day'].$postParams['begin_time']);
            $reservationRecord->setToShopTime($toShopTime);
        }

        if (isset($postParams['shop_name'])) {
            $reservationRecord->setShopName($postParams['shop_name']);
        }
        if (isset($postParams['user_id'])) {
            $reservationRecord->setUserId($postParams['user_id']);
        }
        if (isset($postParams['sex'])) {
            $reservationRecord->setSex($postParams['sex']);
        }

        if (isset($postParams['user_name'])) {
            $reservationRecord->setUserName($postParams['user_name']);
        }
        if (isset($postParams['mobile'])) {
            $reservationRecord->setMobile($postParams['mobile']);
        }
        if (isset($postParams['resource_level_id'])) {
            $reservationRecord->setResourceLevelId($postParams['resource_level_id']);
        }
        if (isset($postParams['resource_level_name'])) {
            $reservationRecord->setResourceLevelName($postParams['resource_level_name']);
        }
        if (isset($postParams['label_id'])) {
            $reservationRecord->setLabelId($postParams['label_id']);
        }
        if (isset($postParams['label_name'])) {
            $reservationRecord->setLabelName($postParams['label_name']);
        }
        if (isset($postParams['rights_id'])) {
            $reservationRecord->setRightsId($postParams['rights_id']);
        }
        if (isset($postParams['rights_name'])) {
            $reservationRecord->setRightsName($postParams['rights_name']);
        }
        return $reservationRecord;
    }
}
