<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Traits\Repository;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Repository的过滤组件
 */
trait FilterRepositoryTrait
{
    /**
     * 将表达式用or连接
     * @param QueryBuilder $queryBuilder
     * @param string ...$expressionList
     * @return string
     */
    public function getOrExpression(QueryBuilder $queryBuilder, string ...$expressionList): string
    {
        return $queryBuilder->expr()->or(...$expressionList);
    }

    /**
     * 获取过滤条件下的表达式
     * @param QueryBuilder $queryBuilder
     * @param string $column
     * @param string|null $symbol 标识符
     * @param mixed $value 值
     * @param string|null $tableAlias 表的别名
     * @return string
     * @throws \Exception
     */
    public function getFilterExpression(QueryBuilder $queryBuilder, string $column, ?string $symbol = null, $value = null, ?string $tableAlias = null): string
    {
        // 设置列名
        $column = $this->appendPrefixTableAliasToColumn($column, $tableAlias);
        // 设置值
        $literalValue = $this->getValue($queryBuilder, $value);
        // 如果值是null，则直接做 NULL 的表达式
        if (is_null($literalValue)) {
            $symbol = "null";
        }

        // 基于不同的符号做不同的表达式
        switch ($symbol) {
            // 等只查询
            case null:
            case "eq":
                return is_array($literalValue) ? $queryBuilder->expr()->in($column, $literalValue) : $queryBuilder->expr()->eq($column, $literalValue);
            // null 查询
            case "null":
                return $queryBuilder->expr()->isNull($column);
            // not null查询
            case "not_null":
                return $queryBuilder->expr()->isNotNull($column);
            // like查询
            case "contains":
            case "like":
                if (is_array($literalValue)) {
                    throw new \Exception("like查询参数有误！");
                }
                return $queryBuilder->expr()->like($column, $queryBuilder->expr()->literal(sprintf("%%%s%%", $value)));
            // 不等于、大小于查询
            default:
                if (is_array($literalValue)) {
                    throw new \Exception("neq、lt、lte、gt、gte的查询参数有误！");
                }
                return $queryBuilder->expr()->$symbol($column, $literalValue);
        }
    }

    /**
     * 获取值
     * @param QueryBuilder $queryBuilder
     * @param mixed $value
     * @return array|int|string|null
     */
    private function getValue(QueryBuilder $queryBuilder, $value)
    {
        if (is_null($value) || is_numeric($value)) {
            return $value;
        } elseif (is_array($value)) {
            array_walk($value, function (&$subItem) use ($queryBuilder) {
                $subItem = $this->getValue($queryBuilder, $subItem);
            });
            return $value;
        } else {
            return $queryBuilder->expr()->literal($value);
        }
    }

    /**
     * 为字段追加表别名
     * @param string $column 字段名称
     * @param string|null $tableAlias 表别名
     * @return string
     */
    public function appendPrefixTableAliasToColumn(string $column, ?string $tableAlias): string
    {
        if (!empty($tableAlias) && strpos($column, ".") === false) {
            return sprintf("%s.%s", $tableAlias, $column);
        }
        return $column;
    }
}
