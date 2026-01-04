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
