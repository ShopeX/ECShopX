<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Interfaces;

interface MiniProgramInterface
{
    /**
     * getPages
     *
     * @return
     */
    public function getPages();

    /**
     * generatePath
     *
     * @param  array  $pathInfo
     * @return
     */
    public function generatePath(array $pathInfo);
}
