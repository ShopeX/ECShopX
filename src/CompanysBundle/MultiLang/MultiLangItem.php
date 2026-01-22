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

namespace CompanysBundle\MultiLang;

class MultiLangItem
{
    protected  $lang;

//    private $module = 'item';

    private $table;
    public function __construct(string $lang,string $module = 'item')
    {
        $this->lang = $lang;
        $moduleNew = 'item';
        if($module != 'item'){
            $moduleNew = 'outside_item';
        }
        $tableLang = str_replace('-','',$lang);
        $this->table = $moduleNew."_multi_lang_mod_lang_$tableLang";
    }

    //创建语言表
    public function createTable()
    {

        $table = $this->table;
        $conn = app("registry")->getConnection("default");
        // 判断表是否存在
        $schemaManager = $conn->getSchemaManager();
        if ($schemaManager->tablesExist([$table])) {
            return; // 已存在，跳过创建
        }
        $sql = "
CREATE TABLE {$table} LIKE `multi_lang_mod`;
        ";

        $conn->executeStatement($sql);
    }

    public function insert(array $data): bool
    {
        if(empty($data)){
            return true;
        }
        $table = $this->table;
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        // 构造 SQL 语句
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($c) => "`$c`", $columns)),   // 字段名加反引号
            implode(', ', $placeholders)
        );
        $conn = app("registry")->getConnection("default");
        // 执行插入
        return $conn->executeStatement($sql, $data) > 0;
    }

    public function updateByFilter(array $filter,array $data)
    {
        $table = $this->table;
        // SET 子句：`key` = :key
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "`$key` = :set_$key";
        }

        // WHERE 子句：`field` = :where_field
        $whereClauses = [];
        foreach ($filter as $key => $value) {
            $whereClauses[] = "`$key` = :where_$key";
        }

        // 构建 SQL
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setClauses),
            implode(' AND ', $whereClauses)
        );

        // 合并参数，使用命名前缀避免冲突
        $params = [];
        foreach ($data as $key => $value) {
            $params["set_$key"] = $value;
        }
        foreach ($filter as $key => $value) {
            $params["where_$key"] = $value;
        }
        $conn = app("registry")->getConnection("default");

        return $conn->executeStatement($sql, $params) > 0;

    }

    public function updateOrInsert(array $filter,array $data)
    {
        foreach ($data as $field => $value) {
            $newFilter  = $filter;
            $newFilter['field'] = $filter['field'];
            $info = $this->getListByFilter($newFilter,-1);
            if(empty($info)){
                $insertData = $filter;
                $insertData['field']= $filter['field'];
                $insertData['module_name'] = $filter['table_name'];
                $insertData['attribute_value'] = $value;
                $insertData['company_id'] = $data['company_id'] ?? 1;
                $insertData['created'] = time();
                $this->insert($insertData);
            }else{
                $this->updateByFilter($filter,[$field=>$value]);
            }
        }
    }

    public function getListByFilter(
        array $filter = [],
        int $limit = -1,
        int $page = 1,
        array $orderBy = []
    ): array {
        $table = $this->table;
        $whereClauses = [];
        $params = [];

        // 构造 WHERE 语句（与前面 findByFilter 相同逻辑）
        foreach ($filter as $rawKey => $value) {
            if(is_array($value)){
                if (strpos($rawKey, '|') === false) {
                    $rawKey .= '|in';
                }
            }
            [$field, $operator] = explode('|', $rawKey) + [null, 'eq'];
            $paramKey = 'param_' . uniqid($field . '_', false);

            switch ($operator) {
                case 'eq':
                case '':
                    $whereClauses[] = "`$field` = :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'lt':
                    $whereClauses[] = "`$field` < :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'lte':
                    $whereClauses[] = "`$field` <= :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'gt':
                    $whereClauses[] = "`$field` > :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'gte':
                    $whereClauses[] = "`$field` >= :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'neq':
                    $whereClauses[] = "`$field` != :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'contains':
                    $whereClauses[] = "`$field` LIKE :$paramKey";
                    $params[$paramKey] = '%' . $value . '%';
                    break;
                case 'in':
                    if (!is_array($value) || empty($value)) {
                        break;
                    }
                    $inParams = [];
                    foreach ($value as $i => $v) {
                        $inKey = "{$paramKey}_{$i}";
                        $inParams[] = ":$inKey";
                        $params[$inKey] = $v;
                    }
                    $whereClauses[] = "`$field` IN (" . implode(', ', $inParams) . ")";
                    break;
                case 'null':
                    $whereClauses[] = "`$field` IS NULL";
                    break;
                case 'notnull':
                    $whereClauses[] = "`$field` IS NOT NULL";
                    break;
            }
        }

        // 构造 SQL
        $sql = "SELECT * FROM {$table}";
        if (!empty($whereClauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // 添加 ORDER BY
        if (!empty($orderBy)) {
            $orderClause = [];
            foreach ($orderBy as $col => $dir) {
                $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                $orderClause[] = "`$col` $dir";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClause);
        }

        if($limit !== -1){
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT :_limit OFFSET :_offset";
            $params['_limit'] = $limit;
            $params['_offset'] = $offset;
        }
        // 添加 LIMIT OFFSET（使用 prepare 绑定）

        $conn = app("registry")->getConnection("default");
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $type = is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        $result = $stmt->executeQuery()->fetchAllAssociative();
        return  $result ? $result : [];
    }

    public function getOneByFilter(array $filter = []): array
    {
        $table = $this->table;
        $whereData = $this->_filter($filter);
        $whereClauses = $whereData['whereClauses'];
        $params = $whereData['params'];
        // 构造 SQL
        $sql = "SELECT * FROM {$table}";
        if (!empty($whereClauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $conn = app("registry")->getConnection("default");
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $type = is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        $result = $stmt->executeQuery()->fetchAssociative();
        return  $result ? $result : [];
    }

    public function deleteBy(array $filter)
    {
        $table = $this->table;
        $whereData = $this->_filter($filter);
        $whereClauses = $whereData['whereClauses'];
        $params = $whereData['params'];
        // 构造sql
        $sql = "DELETE FROM {$table}";
        if (!empty($whereClauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }
        $conn = app("registry")->getConnection("default");
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $type = is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }

        return $stmt->executeQuery()->fetchAssociative();    
    }
    
    private function _filter($filter)
    {
        $whereClauses = [];
        $params = [];
        // 构造 WHERE 语句（与前面 findByFilter 相同逻辑）
        foreach ($filter as $rawKey => $value) {
            if(is_array($value)){
                if (strpos($rawKey, '|') === false) {
                    $rawKey .= '|in';
                }
            }
            [$field, $operator] = explode('|', $rawKey) + [null, 'eq'];
            $paramKey = 'param_' . uniqid($field . '_', false);

            switch ($operator) {
                case 'eq':
                case '':
                    $whereClauses[] = "`$field` = :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'lt':
                    $whereClauses[] = "`$field` < :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'lte':
                    $whereClauses[] = "`$field` <= :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'gt':
                    $whereClauses[] = "`$field` > :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'gte':
                    $whereClauses[] = "`$field` >= :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'neq':
                    $whereClauses[] = "`$field` != :$paramKey";
                    $params[$paramKey] = $value;
                    break;
                case 'contains':
                    $whereClauses[] = "`$field` LIKE :$paramKey";
                    $params[$paramKey] = '%' . $value . '%';
                    break;
                case 'in':
                    if (!is_array($value) || empty($value)) {
                        break;
                    }
                    $inParams = [];
                    foreach ($value as $i => $v) {
                        $inKey = "{$paramKey}_{$i}";
                        $inParams[] = ":$inKey";
                        $params[$inKey] = $v;
                    }
                    $whereClauses[] = "`$field` IN (" . implode(', ', $inParams) . ")";
                    break;
                case 'null':
                    $whereClauses[] = "`$field` IS NULL";
                    break;
                case 'notnull':
                    $whereClauses[] = "`$field` IS NOT NULL";
                    break;
            }
        }

        return ['whereClauses' => $whereClauses, 'params' => $params];
    }

}
