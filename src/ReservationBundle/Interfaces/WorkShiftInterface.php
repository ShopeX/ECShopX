<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
