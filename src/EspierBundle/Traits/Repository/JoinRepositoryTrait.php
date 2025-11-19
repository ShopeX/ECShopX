<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
