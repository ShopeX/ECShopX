<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\ResourceException;
use Doctrine\Common\Collections\Criteria;
use ReservationBundle\Entities\WorkShift;

/**
 * 工作排班
 */
class WorkShiftRepository extends EntityRepository
{
    public $table = "reservation_work_shift";

    /*
     * 为指定资源位设置工作安排
     */
    public function createShift($paramsData)
    {
        $workShift = new WorkShift();
        $workShift->setCompanyId($paramsData['companyId']);
        $workShift->setShopId($paramsData['shopId']);
        $workShift->setResourceLevelId($paramsData['resourceLevelId']);
        $workShift->setShiftTypeId($paramsData['shiftTypeId']);
        $workShift->setWorkDate($paramsData['dateDay']);

        $em = $this->getEntityManager();
        $em->persist($workShift);
        $em->flush();
        $result = [
           'id' => $workShift->getId(),
           'company_id' => $workShift->getCompanyId(),
           'shop_id' => $workShift->getShopId(),
           'resource_level_id' => $workShift->getResourceLevelId(),
           'work_date' => $workShift->getWorkDate(),
           'shift_type_id' => $workShift->getShiftTypeId(),
       ];
        return $result;
    }

    /*
     * 更新指定资源位的工作安排
     */
    public function updateShift($filter, $paramsData)
    {
        // ShopEx framework
        $workShift = $this->findOneBy($filter);
        if (!$workShift) {
            $paramsData = array_merge($paramsData, $filter);
            $this->createShift($paramsData);
        }

        $workShift->setShiftTypeId($paramsData['shiftTypeId']);

        $em = $this->getEntityManager();
        $em->persist($workShift);
        $em->flush();

        $result = [
           'id' => $workShift->getId(),
           'company_id' => $workShift->getCompanyId(),
           'shop_id' => $workShift->getShopId(),
           'resource_level_id' => $workShift->getResourceLevelId(),
           'work_date' => $workShift->getWorkDate(),
           'shift_type_id' => $workShift->getShiftTypeId(),
       ];
        return $result;
    }

    public function deleteShift($filter)
    {
        // ShopEx framework
        $workShift = $this->findOneBy($filter);
        if (!$workShift) {
            return false;
        }
        $dateDay = $workShift->getWorkDate();
        if ($dateDay <= time()) {
            throw new ResourceException('历史排班不可删除');
        }

        $em = $this->getEntityManager();
        $em->remove($workShift);
        $em->flush();
        return true;
    }

    /**
     * 获取指定条件的工作排班
     *
     * @param filter array
     */
    public function getList($filter, $pageSize = 1000, $page = 1, $orderBy = ['work_date' => 'DESC'])
    {
        $data = [];
        $criteria = $this->__filter($filter);
        $listDatas = $this->matching($criteria);
        foreach ($listDatas as $list) {
            $data[] = normalize($list);
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
            if ($field == 'begin_date') {
                $criteria = $criteria->andWhere(Criteria::expr()->gte("work_date", $value));
            } elseif ($field == 'end_date') {
                $criteria = $criteria->andWhere(Criteria::expr()->lte("work_date", $value));
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }
        return $criteria;
    }
}
