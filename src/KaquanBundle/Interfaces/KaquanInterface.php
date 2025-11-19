<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Interfaces;

interface KaquanInterface
{
    /**
     * add Kaquan
     *
     * @param Datainfo $dataInfo
     * @return
     */
    public function createKaquan(array $dataInfo, $appId = '');

    /**
     * get KaquanData
     *
     * @param filter $filter
     * @return array
     */
    public function getKaquanDetail($filter);

    /**
     * update Kaquan
     *
     * @param data $dataInfo
     * @return filter
     */
    public function updateKaquan($dataInfo, $appId = '');

    /**
     * delete Kaquan
     *
     * @param filter $filter
     * @return
     */
    public function deleteKaquan($filter, $appId = '');

    /**
     *  Kaquan list
     *
     * @param filter $filter
     * @return
     */
    public function getKaquanList($offset, $count, $filter = []);
}
