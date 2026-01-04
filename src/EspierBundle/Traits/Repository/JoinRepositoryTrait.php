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

namespace EspierBundle\Traits\Repository;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;

/**
 * 连表相关的操作
 */
trait JoinRepositoryTrait
{
    /**
     * 追加join连接
     * @param QueryBuilder $queryBuilder query对象
     * @param string $mainTableName 主表
     * @param string $joinTableName 从表
     * @param array $conditionArray 主表与从表的连接条件
     * @return void
     */
    protected function appendJoin(QueryBuilder $queryBuilder, EntityRepository $mainTableRepository, EntityRepository $joinTableRepository, array $conditionArray): void
    {
        if (!property_exists($mainTableRepository, "table") || !property_exists($joinTableRepository, "table")) {
            throw new \Exception("操作失败！表名不存在");
        }

        if (empty($conditionArray)) {
            throw new \Exception("操作失败！连接条件不能为空！");
        }

        $mainTableName = $mainTableRepository->table;
        $joinTableName = $joinTableRepository->table;

        $condition = "";
        foreach ($conditionArray as $mainTableColumn => $joinTableColumn) {
            $condition .= sprintf("%s.%s = %s.%s AND ", $mainTableName, $mainTableColumn, $joinTableName, $joinTableColumn ?? $mainTableColumn);
        }
        $condition = trim($condition, "AND ");
        $queryBuilder->leftJoin($mainTableName, $joinTableName, $joinTableName, $condition);
    }
}
