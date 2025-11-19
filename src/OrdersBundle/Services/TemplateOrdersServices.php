<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Entities\TemplateOrders;

class TemplateOrdersServices
{
    private $templateOrdersRepository;

    public function __construct()
    {
        $this->templateOrdersRepository = app('registry')->getManager('default')->getRepository(TemplateOrders::class);
    }

    /**
     * 创建模版订单
     */
    public function createTemplateOrders($data)
    {
        //判断模版是否存在

        //获取模版价格
        $data['total_fee'] = 0;

        if ($data['total_fee'] <= 0) {
            $data['order_status'] = 'DONE';
        }

        return $this->templateOrdersRepository->create($data);
    }

    /**
     * 获取模版订单列表
     */
    public function getTemplateOrdersList(array $filter, $page = 1, $pageSize = 100, $orderBy = ['create_time' => 'DESC'])
    {
        return $this->templateOrdersRepository->getTemplateOrderslist($filter, $orderBy, $pageSize, $page);
    }

    /**
     * 根据模版名称获取订单详情
     */
    public function getByTemplateName($companyId, $templateName)
    {
        //判断模版是否存在

        return $this->templateOrdersRepository->getByTemplateName($companyId, $templateName);
    }
}
