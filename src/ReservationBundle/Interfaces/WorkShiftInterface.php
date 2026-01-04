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

namespace ReservationBundle\Interfaces;

/**
 * Class 交易单处理接口
 */
interface WorkShiftInterface
{
    /**
     * [createData 创建数据]
     * @param  array  $data
     * @return array
     */
    public function createData(array $data);

    /**
     * [updateData 更新数据]
     * @param  array $filter
     * @param  array  $options
     * @return array
     */
    public function updateData(array $filter, array $options);

    /**
     * [deleteData 删除数据]
     * @param  array $filter
     * @return
     */
    public function deleteData(array $filter);

    /**
     * [getList 数据列表]
     * @param  array  $filter
     * @param  integer $page
     * @param  integer $limit
     * @param  string  $orderBy
     * @return array
     */
    public function getList(array $filter, $page = 1, $limit = 10, $orderBy = '');

    /**
     * [get 单条数据]
     * @param  array $filter
     * @return array
     */
    public function get(array $filter);
}
