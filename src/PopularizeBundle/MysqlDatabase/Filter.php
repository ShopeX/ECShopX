<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PopularizeBundle\MysqlDatabase;

// 结果集过滤器

class Filter
{
    private $operator = ["lt","lte","gt","gte","neq","isNull","in","notIn"];
    public function getFilterRecordData($data, $filter, $offset = 0, $limit = null)
    {
        foreach ($filter as $key => $value) {
            $list = explode("|", $key);
            if (count($list) > 1) {
                list($col, $operator) = $list;
                if (!in_array($operator, $this->operator)) {
                    continue;
                }
                $data = array_filter($data, $this->{'operator'.ucfirst($operator)}($col, $value));
            } else {
                $data = array_filter($data, function ($row) use ($key, $value) {
                    return $row[$key] == $value;
                });
            }
        }
        $data = array_values($data);
        //after filter
        $total_count = count($data);
        if ($limit) {
            $data = array_slice($data, $offset, $limit);
        }
        return ['list' => $data, 'total_count' => $total_count];
    }

    private function operatorLt($col, $value)
    {
        return function ($row) use ($col, $value) {
            return $row[$col] < $value;
        };
    }
    private function operatorLte($col, $value)
    {
        return function ($row) use ($col, $value) {
            return $row[$col] <= $value;
        };
    }
    private function operatorGt($col, $value)
    {
        return function ($row) use ($col, $value) {
            return $row[$col] > $value;
        };
    }
    private function operatorGte($col, $value)
    {
        return function ($row) use ($col, $value) {
            return $row[$col] >= $value;
        };
    }
    private function operatorNeq($col, $value)
    {
        return function ($row) use ($col, $value) {
            return $row[$col] != $value;
        };
    }
    private function operatorIsNull($col)
    {
        return function ($row) use ($col) {
            return $row[$col] == null;
        };
    }
    private function operatorIn($col, $value)
    {
        return function ($row) use ($col, $value) {
            return in_array($row[$col], $value);
        };
    }
    private function operatorNotIn($col, $value)
    {
        return function ($row) use ($col, $value) {
            return !in_array($row[$col], $value);
        };
    }
}
