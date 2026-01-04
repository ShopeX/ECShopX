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

use Doctrine\DBAL\Query\Expression\CompositeExpression;

trait DoctrineArrayFilter
{
    /**
     * literal
     * @param  string|array       $value
     * @param  Doctrine\DBAL\Query\QueryBuilder|Doctrine\ORM\QueryBuilder $qb
     * @return string
     */
    public function literalValue($value, $qb)
    {
        if (!is_array($value)) {
            return $qb->expr()->literal($value);
        }
        array_walk($value, function (&$colVal) use ($qb) {
            $colVal = $qb->expr()->literal($colVal);
        });
        return $value;
    }
    /**
     * 处理字段
     * @param  string $field_expr 字段表达式
     * @param  Doctrine\DBAL\Query\QueryBuilder|Doctrine\ORM\QueryBuilder $qb
     * @return array
     */
    public function getFieldAndExpr($field_expr, $qb)
    {
        $splitStr = '|';
        if (strpos($field_expr, $splitStr) === false) {
            $field = $field_expr;
            $expr = 'eq';
        } else {
            list($field, $expr) = explode('|', $field_expr);
        }
        if (in_array($field, [CompositeExpression::TYPE_AND,CompositeExpression::TYPE_OR])) {
            return [$field,$expr];
        }
        if (method_exists($qb, 'getDQLPart')) {
            $from['alias'] = $qb->getDQLPart('from')[0]->getAlias();
        } elseif (method_exists($qb, 'getQueryPart')) {
            $from = $qb->getQueryPart('from');
        }
        if (isset($from['alias']) && $from['alias']) {
            $field = $from['alias'].'.'.$field;
        }
        return [$field,$expr];
    }
    /**
     * 转化 array filter 为 QueryBuilder
     *
     * @param  array  $filter
     * @param  Doctrine\DBAL\Query\QueryBuilder|Doctrine\ORM\QueryBuilder $qb     [description]
     * @return Doctrine\DBAL\Query\QueryBuilder|Doctrine\ORM\QueryBuilder
     */
    public function filter(array $filter, $qb)
    {
        $where = $this->getCompositeExpression($filter, $qb);
        return $qb->add('where', $where);
    }
    /**
     * 迭代生成表 wehre 达式
     * @param  array  $filter
     * @param  Doctrine\DBAL\Query\QueryBuilder|Doctrine\ORM\QueryBuilder $qb
     * @param  string $type
     * @return Doctrine\DBAL\Query\Expression\CompositeExpression|Doctrine\ORM\Query\Expr
     */
    public function getCompositeExpression(array $filter, $qb, $type = CompositeExpression::TYPE_AND)
    {
        $and = CompositeExpression::TYPE_AND;
        $or = CompositeExpression::TYPE_OR;
        $exprs = [];
        foreach ($filter as $field_expr => $value) {
            list($field, $expr) = $this->getFieldAndExpr($field_expr, $qb);
            if ($expr === $or || $field === $or) {
                $exprs[] = $qb->expr()->orX($this->getCompositeExpression($value, $qb, $or));
            } elseif ($expr === $and || $field === $and) {
                $exprs[] = $qb->expr()->andX($this->getCompositeExpression($value, $qb, $and));
            } elseif (in_array($expr, ['isNull','isNotNull'])) {
                $exprs[] = $qb->expr()->$expr($field);
            } else {
                $exprs[] = $qb->expr()->$expr($field, $this->literalValue($value, $qb));
            }
        }
        if ($type == $and) {
            return $qb->expr()->andX()->addMultiple($exprs);
        } else {
            return $qb->expr()->orX()->addMultiple($exprs);
        }
    }
}
