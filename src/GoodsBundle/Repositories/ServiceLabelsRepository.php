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

namespace GoodsBundle\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use GoodsBundle\Entities\ServiceLabels;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class ServiceLabelsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'servicelabels';

    /**
     * 添加会员数值属性
     */
    public function create($params)
    {
        $serviceLabelsEnt = new ServiceLabels();

        $serviceLabelsEnt->setLabelName($params['label_name']);
        $serviceLabelsEnt->setLabelPrice($params['label_price']);
        $serviceLabelsEnt->setLabelDesc($params['label_desc']);
        $serviceLabelsEnt->setServiceType($params['service_type']);
        $serviceLabelsEnt->setCompanyId($params['company_id']);

        $em = $this->getEntityManager();
        $em->persist($serviceLabelsEnt);
        $em->flush();
        $result = [
            'label_id' => $serviceLabelsEnt->getLabelId(),
            'label_name' => $serviceLabelsEnt->getLabelName(),
            'label_price' => $serviceLabelsEnt->getLabelPrice(),
            'label_desc' => $serviceLabelsEnt->getLabelDesc(),
            'service_type' => $serviceLabelsEnt->getServiceType(),
            'company_id' => $serviceLabelsEnt->getCompanyId(),
            'created' => $serviceLabelsEnt->getCreated(),
            'updated' => $serviceLabelsEnt->getUpdated(),
        ];

        return $result;
    }

    /**
     * 更新会员数值属性
     */
    public function update($label_id, $params)
    {
        $serviceLabelsEnt = $this->find($label_id);

        $serviceLabelsEnt->setCompanyId($params['company_id']);
        $serviceLabelsEnt->setLabelName($params['label_name']);
        $serviceLabelsEnt->setLabelPrice($params['label_price']);
        $serviceLabelsEnt->setLabelDesc($params['label_desc']);
        $serviceLabelsEnt->setServiceType($params['service_type']);

        $em = $this->getEntityManager();
        $em->persist($serviceLabelsEnt);
        $em->flush();
        $result = [
            'label_id' => $serviceLabelsEnt->getLabelId(),
            'label_name' => $serviceLabelsEnt->getLabelName(),
            'label_price' => $serviceLabelsEnt->getLabelPrice(),
            'label_desc' => $serviceLabelsEnt->getLabelDesc(),
            'service_type' => $serviceLabelsEnt->getServiceType(),
            'company_id' => $serviceLabelsEnt->getCompanyId(),
            'created' => $serviceLabelsEnt->getCreated(),
            'updated' => $serviceLabelsEnt->getUpdated(),
        ];

        return $result;
    }

    /**
     * 删除会员数值属性
     */
    public function delete($label_id)
    {
        $delServiceLabelsEntity = $this->find($label_id);
        if (!$delServiceLabelsEntity) {
            throw new DeleteResourceFailedException("label_id={$label_id}的会员数值属性不存在");
        }
        $this->getEntityManager()->remove($delServiceLabelsEntity);

        return $this->getEntityManager()->flush($delServiceLabelsEntity);
    }

    /**
     * 获取会员数值属性详细信息
     */
    public function get($label_id)
    {
        $serviceLabelsInfo = $this->find($label_id);
        if (!$serviceLabelsInfo) {
            throw new ResourceException("label_id={$label_id}的会员数值属性不存在");
        }
        $result = [
            'label_id' => $serviceLabelsInfo->getLabelId(),
            'label_name' => $serviceLabelsInfo->getLabelName(),
            'label_price' => $serviceLabelsInfo->getLabelPrice(),
            'label_desc' => $serviceLabelsInfo->getLabelDesc(),
            'service_type' => $serviceLabelsInfo->getServiceType(),
            'company_id' => $serviceLabelsInfo->getCompanyId(),
            'created' => $serviceLabelsInfo->getCreated(),
            'updated' => $serviceLabelsInfo->getUpdated(),
        ];

        return $result;
    }

    /**
     * 获取会员数值属性列表
     */
    public function list($filter, $orderBy = ['label_id' => 'DESC'], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
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
        }
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);
        $newServiceLabelsList = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $item = [
                    'labelId' => $v->getLabelId(),
                    'labelName' => $v->getLabelName(),
                    'serviceType' => $v->getServiceType(),
                    'companyId' => $v->getCompanyId(),
                    'created' => $v->getCreated(),
                    'updated' => $v->getUpdated(),
                    'labelDesc' => $v->getLabelDesc(),
                    'labelPrice' => $v->getLabelPrice(),
                ];
                $newServiceLabelsList[] = $item;
            }
        }
//        $serviceLabelsList = $this->findBy($filter, $orderBy, $pageSize,  $pageSize * ($page - 1));
//        $newServiceLabelsList = [];
//        foreach ($serviceLabelsList as $v) {
//            $newServiceLabelsList[]= normalize($v);
//        }
//        $total = $this->getEntityManager()
//                      ->getUnitOfWork()
//                      ->getEntityPersister($this->getEntityName())
//                      ->count($filter);
//        $res['total_count'] = intval($total);
        $res['list'] = $newServiceLabelsList;
        return $res;
    }
}
