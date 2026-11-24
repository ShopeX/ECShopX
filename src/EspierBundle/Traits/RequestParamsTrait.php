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

namespace EspierBundle\Traits;

trait RequestParamsTrait
{
    /** 分页页码下限 */
    private const PAGE_MIN = 1;

    /** 分页每页条数下限 */
    private const PAGE_SIZE_MIN = 1;

    /** 分页每页条数上限，防止 page_size<=0 全表或过大查询 */
    private const PAGE_SIZE_MAX = 100;

    /**
     * 获取当前页（钳制到 >= 1）
     * @return int
     */
    protected function getPage(): int
    {
        $page = (int) request()->query('page', self::PAGE_MIN);
        return max(self::PAGE_MIN, $page);
    }

    /**
     * 获取当前页需要拿取的数据条数（钳制到 [1, 100]）
     * @return int
     */
    protected function getPageSize(): int
    {
        $pageSize = (int) request()->query('page_size', 10);
        if ($pageSize < self::PAGE_SIZE_MIN) {
            $pageSize = 10;
        }
        return min(self::PAGE_SIZE_MAX, $pageSize);
    }
}
