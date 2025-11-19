<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\UpdateResourceFailedException;
use ReservationBundle\Entities\DefaultWorkShift;

class DefaultWorkShiftRepository extends EntityRepository
{
    public $table = "reservation_default_work_shift";

    public function create(array $paramsdata)
    {
        $defaultWorkShift = new DefaultWorkShift();
        $defaultWorkShift->setCompanyId($paramsdata['company_id']);
        $defaultWorkShift->setShopId($paramsdata['shop_id']);
        $defaultWorkShift->setWorkShiftData($paramsdata['work_shift_data']);
        $em = $this->getEntityManager();
        $em->persist($defaultWorkShift);
        $em->flush();
        return $this->getColumnNamesData($defaultWorkShift);
    }

    public function update($filter, array $paramsdata)
    {
        $defaultWorkShift = $this->findOneBy($filter);
        if (!$defaultWorkShift) {
            $paramsdata['company_id'] = $filter['company_id'];
            $paramsdata['shop_id'] = $filter['shop_id'];
            return $this->create($paramsdata);
        }
        $defaultWorkShift->setWorkShiftData($paramsdata['work_shift_data']);

        $em = $this->getEntityManager();
        $em->persist($defaultWorkShift);
        $em->flush();
        return $this->getColumnNamesData($defaultWorkShift);
    }

    public function delete($filter)
    {
        $defaultWorkShift = $this->findOneBy($filter);
        if (!$defaultWorkShift) {
            throw new UpdateResourceFailedException('修改默认排班不存在');
        }
        $em = $this->getEntityManager();
        $em->remove($defaultWorkShift);
        return $em->flush($defaultWorkShift);
    }

    public function getList($filter)
    {
        $result = [];
        $defaultWorkShift = $this->findBy($filter);
        if ($defaultWorkShift) {
            foreach ($defaultWorkShift as $k => $workShift) {
                $result[$k] = $this->getColumnNamesData($defaultWorkShift);
            }
        }
        return $result;
    }

    public function get($filter)
    {
        $result = [];
        $defaultWorkShift = $this->findOneBy($filter);
        if ($defaultWorkShift) {
            $result = $this->getColumnNamesData($defaultWorkShift);
        }
        return $result;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        $result = $entity->getWorkShiftData();

        //$result['shop_id'] = $entity->getShopId();
        //$result['company_id'] = $entity->getCompanyId();
        return $result;
    }
}
