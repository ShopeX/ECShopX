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
