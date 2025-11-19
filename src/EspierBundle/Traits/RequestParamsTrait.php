<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Traits;

trait RequestParamsTrait
{
    /**
     * 获取当前页
     * @return int
     */
    protected function getPage(): int
    {
        return (int)request()->query("page", 1);
    }

    /**
     * 获取当前页需要拿取的数据条数
     * @return int
     */
    protected function getPageSize(): int
    {
        return (int)request()->query("page_size", 10);
    }
}
