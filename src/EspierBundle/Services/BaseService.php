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

namespace EspierBundle\Services;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Exceptions\ServiceErrorException;

abstract class BaseService
{
    protected $bind = [];

    /**
     * entity类的类名（包含命名空间）
     * @return string
     */
    abstract public function getEntityClass(): string;

    /**
     * 获取存储类
     * @return EntityRepository
     */
    public function getRepository(string $entityClass = ""): EntityRepository
    {
        try {
            if (empty($entityClass)) {
                $entityClass = $this->getEntityClass();
            }
            if (!isset($this->bind[$entityClass]) || !($this->bind[$entityClass] instanceof EntityRepository)) {
                $this->bind[$entityClass] = app('registry')->getManager('default')->getRepository($entityClass);
            }
            return $this->bind[$entityClass];
        } catch (\Exception $exception) {
            throw new ServiceErrorException($exception);
        }
    }

    /**
     * 创建
     * @param int $companyId 企业id
     * @param array $createData 需要创建的数据
     * @return array 创建的结果
     */
    public function create(array $createData): array
    {
        return $this->getRepository()->create($createData);
    }

    /**
     * 删除
     * @param int $companyId 企业id
     * @param array $filter 过滤条件
     * @return int 被影响的行数
     */
    public function delete(array $filter): int
    {
        return $this->getRepository()->deleteBy($filter);
    }

    /**
     * 更新数据后返回详情信息
     * @param int $companyId 企业id
     * @param array $filter 过滤条件
     * @param array $updateData 更新的数据
     * @return array 更新完成后的新数据
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        return $this->getRepository()->updateOneBy($filter, $updateData);
    }

    /**
     * 跟新数据后返回影响的行数
     * @param int $companyId 企业id
     * @param array $filter 过滤条件
     * @param array $updateData 更新的数据
     * @return int 受影响的行数
     */
    public function updateBatch(array $filter, array $updateData): int
    {
        return $this->getRepository()->updateBy($filter, $updateData);
    }

    /**
     * 查询单条数据
     * @param int $companyId 企业id
     * @param array $filter 过滤条件
     * @return array
     */
    public function find(array $filter): array
    {
        if (method_exists($this->getRepository(), "getInfo")) {
            return $this->getRepository()->getInfo($filter);
        } elseif (method_exists($this->getRepository(), "get")) {
            return $this->getRepository()->get($filter);
        } else {
            $result = $this->list($filter, 1, 1);
            return (array)($result["list"] ?? []);
        }
    }

    /**
     * 查询列表数据
     * @param int $companyId 企业id
     * @param array $filter 过滤条件
     * @param int $page 当前页
     * @param int $pageSize 每页大小
     * @param array $orderBy 排序方式
     * @param string $cols 返回的字段，用英文逗号隔开
     * @param bool $needCountSql true表示回去count一遍查询一共有多少数据，false表示不执行count语句
     * @return array 列表数据
     */
    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        if ($needCountSql) {
            $result = $this->getRepository()->lists($filter, $cols, $page, $pageSize, $orderBy);
        //$result["is_end"] = count($result["list"]) < $pageSize ? 1 : 0;
        } else {
            if (method_exists($this->getRepository(), "getLists")) {
                $result["list"] = $this->getRepository()->getLists($filter, $cols, $page, $pageSize, $orderBy);
                //$result["is_end"] = count($result["list"]) < $pageSize ? 1 : 0;
                $result["total_count"] = 0;
            } elseif (method_exists($this->getRepository(), "lists")) {
                $result = $this->getRepository()->lists($filter, $page, $pageSize, $orderBy);
            //$result["is_end"] = count($result["list"]) < $pageSize ? 1 : 0;
            } else {
                // repository类中只存在查询列表又要统计数量的接口
                return $this->list($filter, $page, $pageSize, $orderBy, $cols, true);
            }
        }
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 整理列表格式
     * @param array $result 外部的结果集
     * @param int $page 当前页
     * @param int $pageSize 每页大小
     */
    public function handlerListReturnFormat(array &$result, int $page, int $pageSize)
    {
        // 设置列表
        if (!isset($result["list"])) {
            $result["list"] = [];
        }
        // 设置总数量
        if (!isset($result["total_count"])) {
            $result["total_count"] = count($result["list"] ?? []);
        }
        // 是否最后一页
        if (!isset($result["is_last_page"])) {
            if ($pageSize > 0) {
                $total_page = ceil($result["total_count"] / $pageSize);
                $result["is_last_page"] = $total_page <= $page ? 1 : 0;
            } else {
                $result["is_last_page"] = 1;
            }
        }
        // 分页相关的信息
        if (!isset($result["pager"])) {
            $result["pager"] = ["page" => $page, "page_size" => $pageSize];
        }
        return $result;
    }

    /**
     * 事务操作
     * @param \Closure $operationFunction 主逻辑业务
     * @param \Closure|null $handlerExceptionErrorFunction 异常处理
     * @param \Closure|null $handlerThrowableErrorFunction 致命操作处理
     * @return mixed 主逻辑中返回的值
     * @throws \Throwable
     */
    public function transaction(\Closure $operationFunction, ?\Closure $handlerExceptionErrorFunction = null, ?\Closure $handlerThrowableErrorFunction = null)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $operationFunction();
            $conn->commit();
            return $result;
        } catch (\Exception $exception) {
            $conn->rollback();
            if (!is_null($handlerExceptionErrorFunction)) {
                $handlerExceptionErrorFunction($exception);
            }
            throw $exception;
        } catch (\Throwable $throwable) {
            $conn->rollback();
            if (!is_null($handlerThrowableErrorFunction)) {
                $handlerThrowableErrorFunction($throwable);
            }
            throw $throwable;
        }
    }

    /**
     * 过滤条件
     * @param array $filter
     * @param QueryBuilder $queryBuilder
     * @param string|null $entityClass entity类名，用于获取表字段
     */
    protected function filter(array $filter, QueryBuilder $queryBuilder, ?string $entityClass = null)
    {
        $propertiesArray = null;
        try {
            // 不去repository对象里拿字段是因为部分老代码存在没有定义字段的问题
            // 基于类做反射
            $ref = new \ReflectionClass($entityClass);
            // 获取类里的所有成员，即表里的字段值
            $properties = $ref->getProperties();
            $propertiesArray = [];
            foreach ($properties as $property) {
                $propertiesArray[$property->getName()] = true;
            }
        } catch (\Exception $exception) {
        }

        foreach ($filter as $column => $value) {
            $columnArray = explode("|", $column, 2);
            if (count($columnArray) == 2) {
                $column = array_shift($columnArray);
                $type = array_shift($columnArray);
                if (is_array($propertiesArray) && !isset($propertiesArray[$column])) {
                    continue;
                }
                switch ($type) {
                    case "internal":
                        $queryBuilder->andWhere($queryBuilder->expr()->eq($column, $value));
                        break;
                    case "like":
                        $queryBuilder->andWhere($queryBuilder->expr()->like($column, sprintf("%%%s%%", $value)));
                        break;
                    default:
                        $queryBuilder->andWhere($queryBuilder->expr()->$type($column, $value));
                }
            } else {
                if (is_array($propertiesArray) && !isset($propertiesArray[$column])) {
                    continue;
                }
                $queryBuilder->andWhere($queryBuilder->expr()->eq($column, is_string($value) ? $queryBuilder->expr()->literal($value) : $value));
            }
        }
    }

    public function groupBy(array $filter, string ...$groupByColumn): array
    {
        $selectColumns = "";
        foreach ($groupByColumn as $column) {
            $selectColumns .= sprintf("%s,", $column);
        }
        if (empty($selectColumns)) {
            return [];
        }
        $selectColumns .= "COUNT(*) AS count";

        // 查询出已上架的店铺商品
        $groupByDistributorItemQuery = app('registry')
            ->getConnection('default')
            ->createQueryBuilder()
            ->select($selectColumns)
            ->from($this->getRepository()->table);
        $this->filter($filter, $groupByDistributorItemQuery, $this->getEntityClass());
        foreach ($groupByColumn as $column) {
            $groupByDistributorItemQuery->groupBy($column);
        }
        return $groupByDistributorItemQuery->execute()->fetchAll();
    }
}
