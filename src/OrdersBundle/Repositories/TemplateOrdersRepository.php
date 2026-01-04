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
use OrdersBundle\Entities\TemplateOrders;

class TemplateOrdersRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'template_orders';

    /**
     * 新增模版订单
     */
    public function create($params)
    {
        // ShopEx EcShopX Core Module
        $templateOrdersEnt = new TemplateOrders();

        $templateOrdersEnt->setCompanyId($params['company_id']);
        $templateOrdersEnt->setOperatorId($params['operator_id']);
        $templateOrdersEnt->setTemplateName($params['template_name']);
        $templateOrdersEnt->setTotalFee($params['total_fee']);
        $templateOrdersEnt->setOrderStatus($params['order_status']);
        $templateOrdersEnt->setCreateTime(time());
        $templateOrdersEnt->setUpdateTime(time());

        $em = $this->getEntityManager();
        $em->persist($templateOrdersEnt);
        $em->flush();
        $result = [
            'template_orders_id' => $templateOrdersEnt->getTemplateOrdersId(),
            'company_id' => $templateOrdersEnt->getCompanyId(),
            'operator_id' => $templateOrdersEnt->getOperatorId(),
            'template_name' => $templateOrdersEnt->getTemplateName(),
            'total_fee' => $templateOrdersEnt->getTotalFee(),
            'order_status' => $templateOrdersEnt->getOrderStatus(),
            'created' => $templateOrdersEnt->getCreateTime(),
            'updated' => $templateOrdersEnt->getUpdateTime(),
        ];

        return $result;
    }

    public function getTemplateOrderslist(array $filter, $orderBy = ['create_time' => 'DESC'], $page = 1, $pageSize = 100)
    {
        $list = $this->findBy($filter, $orderBy, $pageSize, $pageSize * ($page - 1));
        foreach ($list as $v) {
            $value = normalize($v);
            $data[] = $value;
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $data;

        return $res;
    }

    public function getByTemplateName($companyId, $templateName)
    {
        return $this->findOneBy(['company_id' => $companyId, 'template_name' => $templateName]);
    }
}
