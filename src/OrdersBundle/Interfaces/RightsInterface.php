<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Interfaces;

interface RightsInterface
{
    /**
     * 新增权益
     */
    public function addRights($companyId, array $params);

    /**
     * 核销权益
     */
    public function consumeRights($companyId, array $params);

    /**
     * 冻结权益
     */
    public function freezeRights($companyId, array $params);

    /**
     * 获取权益详情
     */
    public function getRightsDetail($rightsId);

    /**
     * 获取权益列表
     */
    public function getRightsList(array $filter, $page, $pageSize, $orderBy);
}
