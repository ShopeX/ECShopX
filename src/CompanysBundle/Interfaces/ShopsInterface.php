<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Interfaces;

interface ShopsInterface
{
    /**
     * Create shops
     *
     * @param  shopInfo  $shopsInfo
     * @return
     */
    public function addShops(array $shopsInfo);

    /**
     * get shopsInfo
     *
     * @param  filter
     * @return array
     */
    public function getShopsDetail($filter);

    /**
     * get shopsList
     *
     * @param  filter
     * @param  page
     * @param  pageSize
     * @param  orderBy
     * @return array
     */
    public function getShopsList($filter, $page, $pageSize, $orderBy);

    /**
     * update shopsInfo
     *
     * @param data
     * @param filter
     * @return
     */
    public function updateShops($data, $filter);

    /**
     * delete shops
     *
     * @param filter
     * @return
     */
    public function deleteShops($filter);
}
