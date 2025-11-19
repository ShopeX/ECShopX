<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use OrdersBundle\Entities\SubOrders;

class SubOrdersRepository extends EntityRepository
{
    public $table = 'sub_orders';

    public function create($params)
    {
        $subOrderEntity = new SubOrders();
        $subOrder = $this->setSubOrderData($subOrderEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($subOrder);
        $em->flush();

        $result = [
            'order_id' => $subOrder->getOrderId(),
            'company_id' => $subOrder->getCompanyId(),
            'item_id' => $subOrder->getItemId(),
            'label_id' => $subOrder->getLabelId(),
        ];

        return $result;
    }

    private function setSubOrderData($subOrderEntity, $postdata)
    {
        if (isset($postdata['order_id'])) {
            $subOrderEntity->setOrderId($postdata['order_id']);
        }
        if (isset($postdata['company_id'])) {
            $subOrderEntity->setCompanyId($postdata['company_id']);
        }
        if (isset($postdata['item_id'])) {
            $subOrderEntity->setItemId($postdata['item_id']);
        }
        if (isset($postdata['item_name'])) {
            $subOrderEntity->setItemName($postdata['item_name']);
        }
        if (isset($postdata['label_id'])) {
            $subOrderEntity->setLabelId($postdata['label_id']);
        }
        if (isset($postdata['label_name'])) {
            $subOrderEntity->setLabelName($postdata['label_name']);
        }
        if (isset($postdata['num'])) {
            $subOrderEntity->setNum($postdata['num']);
        }
        if (isset($postdata['is_not_limit_num'])) {
            $subOrderEntity->setIsNotLimitNum($postdata['is_not_limit_num']);
        }

        if (isset($postdata['limit_time'])) {
            $subOrderEntity->setLimitTime($postdata['limit_time']);
        }
        if (isset($postdata['label_price'])) {
            $subOrderEntity->setLabelPrice($postdata['label_price']);
        }

        return $subOrderEntity;
    }

    /**
     * 获取子订单列表
     */
    public function list($filter, $orderBy = ['created' => 'DESC'], $pageSize = 100, $page = 1)
    {
        $subOrdersList = $this->findBy($filter, $orderBy, $pageSize, $pageSize * ($page - 1));

        $newSubOrdersList = [];
        foreach ($subOrdersList as $v) {
            $newSubOrdersList[] = [
                'order_id' => $v->getOrderId(),
                'company_id' => $v->getCompanyId(),
                'item_id' => $v->getItemId(),
                'item_name' => $v->getItemName(),
                'label_id' => $v->getLabelId(),
                'label_name' => $v->getLabelName(),
                'label_price' => $v->getLabelPrice(),
                'num' => $v->getNum(),
                'is_not_limit_num' => $v->getIsNotLimitNum(),
                'limit_time' => $v->getLimitTime(),
                'created' => $v->getCreated(),
                'updated' => $v->getUpdated(),
            ];
        }
        $total = $this->getEntityManager()
                      ->getUnitOfWork()
                      ->getEntityPersister($this->getEntityName())
                      ->count($filter);
        $res['total_count'] = intval($total);
        $res['list'] = $newSubOrdersList;
        return $res;
    }
}
