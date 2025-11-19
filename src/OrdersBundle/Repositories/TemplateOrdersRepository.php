<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
