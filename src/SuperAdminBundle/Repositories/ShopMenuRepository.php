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

namespace SuperAdminBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use SuperAdminBundle\Entities\ShopMenu;

use Dingo\Api\Exception\ResourceException;

class ShopMenuRepository extends EntityRepository
{
    public $menusList;
    public $isChildrenMenu;

    public $table = 'shop_menu'; // 多语言对应的表名
    public $module = 'shop_menu'; // 多语言对应的模块
    public $primaryKey = 'shopmenu_id'; // 主键，对应data_id
    public $langField = [
        'name'
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new ShopMenu();
        return $entity;
    }

    public function getMenuTree($filter = array(), $isShowParentname = true, $isShowApis = true)
    {
        $repository = getRepositoryLangue(ShopMenu::class);
        $listsData = $repository->lists($filter,"*",1, 1000,['pid' => 'asc','sort' => 'asc'] );
        if ($listsData['total_count'] <= 0) {
            return [
                'tree' => [],
                'list' => [],
            ];
        }

        foreach ($listsData['list'] as $item) {
            if (!$isShowApis) {
                unset($item['apis']);
            }
            $lists[] = $item;
        }
        $menu['tree'] = $this->preMenuTree($lists, 0);
        $menu['list'] = $this->menusList;
        $menuList = array_column($this->menusList, null, 'shopmenu_id');
        foreach ($menu['list'] as &$row) {
            if ($isShowParentname) {
                $row['parent_name'] = isset($menuList[$row['pid']]['name']) ? $menuList[$row['pid']]['name'] : '无';
            }
            $row['isChildrenMenu'] = isset($this->isChildrenMenu[$row['shopmenu_id']]) ? $this->isChildrenMenu[$row['shopmenu_id']] : false;
        }
        return $menu;
    }

    private function preMenuTree($data, $pid = 0, $level = 0)
    {
        $lists = array();
        $isFlag = false;
        foreach ($data as $key => $val) {
            if ($val['pid'] == $pid) {
                if (!$isFlag) {
                    $level++;
                }
                $isFlag = true;

                $val['level'] = $level;
                $this->menusList[] = $val;

                if (!$val['is_show']) {
                    continue;
                }

                $children = $this->preMenuTree($data, $val['shopmenu_id'], $level);
                if ($children) {
                    $val['isChildrenMenu'] = in_array('true', array_column($children, 'is_menu'));
                    $this->isChildrenMenu[$val['shopmenu_id']] = $val['isChildrenMenu'];
                    $val['children'] = $children;
                }
                $lists[] = $val;
            }
        }
        return $lists;
    }



    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ShopMenu();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->delete('shop_menu');

        $qb = $this->_filter($filter, $qb);
        $qb->execute();
        return true;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param array $filter 更新的条件
     */
    public function lists($filter, $cols="*", $page = 1, $pageSize = 1000, $orderBy = ["created" => "DESC"])
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        $data['is_menu'] = isset($data['is_menu']) && $data['is_menu'] == 'false' ? 0 : 1;
        $data['is_show'] = isset($data['is_show']) && $data['is_show'] == 'false' ? 0 : 1;
        $data['disabled'] = isset($data['disabled']) && $data['disabled'] == 'true' ? 1 : 0;

        // 记录 is_show 从 true 变为 false 的日志
        if (isset($data["is_show"])) {
            $oldValue = $entity->getIsShow();
            $newValue = $data['is_show'];
            
            // 只记录从 true 变为 false 的情况
            if ($oldValue === true && $newValue == 0) {
                $this->logIsShowChangeToFalse($entity, $data);
            }
        }

        if (isset($data["shopmenu_id"])) {
            $entity->setShopmenuId($data["shopmenu_id"]);
        }
        if (isset($data["company_id"])) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["name"]) && $data["name"]) {
            $entity->setName($data["name"]);
        }
        if (isset($data["url"]) && $data["url"]) {
            $entity->setUrl($data["url"]);
        }
        //当前字段非必填
        if (isset($data["sort"])) {
            $entity->setSort($data["sort"]);
        }
        //当前字段非必填
        if (isset($data["is_menu"])) {
            $entity->setIsMenu($data["is_menu"]);
        }
        if (isset($data["pid"])) {
            $entity->setPid($data["pid"]);
        }
        //当前字段非必填
        if (isset($data["apis"])) {
            $entity->setApis($data["apis"]);
        }
        if (isset($data["icon"]) && $data["icon"]) {
            $entity->setIcon($data["icon"]);
        }
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data["alias_name"]) && $data["alias_name"]) {
            $entity->setAliasName($data["alias_name"]);
        }
        if (isset($data["version"]) && $data["version"]) {
            $entity->setVersion($data["version"]);
        }
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        return $entity;
    }

    /**
     * 记录 is_show 从 true 变为 false 的日志
     *
     * @param $entity
     * @param $data
     */
    private function logIsShowChangeToFalse($entity, $data)
    {
        try {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => $this->getActionType(),
                'shopmenu_id' => $entity->getShopmenuId() ?: ($data['shopmenu_id'] ?? null),
                'company_id' => $entity->getCompanyId() ?: ($data['company_id'] ?? 0),
                'version' => $entity->getVersion() ?: ($data['version'] ?? 1),
                'alias_name' => $entity->getAliasName() ?: ($data['alias_name'] ?? null),
                'name' => $entity->getName() ?: ($data['name'] ?? null),
                'old_value' => true,
                'new_value' => false,
                'source' => $this->getRequestSource(),
                'source_type' => $this->getSourceType(),
                'user_id' => $this->getUserId(),
                'user_name' => $this->getUserName(),
                'user_email' => $this->getUserEmail(),
                'request_data' => $this->getRequestData($data),
                'ip' => $this->getRequestIp(),
                'user_agent' => $this->getUserAgent(),
                'trace_id' => $this->getTraceId(),
                'stack_trace' => $this->getSimpleStackTrace(),
                'context' => $this->getContext(),
            ];

            $logFile = storage_path('logs/shopmenu_is_show_hidden.log');
            $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // 记录日志失败不影响主流程，静默处理
        }
    }

    /**
     * 获取操作类型
     */
    private function getActionType()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $frame) {
            if (isset($frame['function'])) {
                if ($frame['function'] == 'create') {
                    return 'create';
                }
                if ($frame['function'] == 'updateOneBy' || $frame['function'] == 'updateBy') {
                    return 'update';
                }
            }
        }
        return 'unknown';
    }

    /**
     * 获取请求来源
     */
    private function getRequestSource()
    {
        try {
            if (php_sapi_name() === 'cli') {
                return 'cli';
            }
            $request = app('request');
            if ($request) {
                return $request->method() . ' ' . $request->path();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return 'unknown';
    }

    /**
     * 获取来源类型（手动/自动）
     */
    private function getSourceType()
    {
        // 检查是否为命令行
        if (php_sapi_name() === 'cli') {
            return 'auto';
        }

        // 检查调用堆栈，判断是否为自动操作
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $frame) {
            // 如果是 UpdateMenuListener 调用，标记为自动
            if (isset($frame['class']) && strpos($frame['class'], 'UpdateMenuListener') !== false) {
                return 'auto';
            }
        }

        return 'manual';
    }

    /**
     * 获取用户ID
     */
    private function getUserId()
    {
        try {
            $user = app('auth')->user();
            if ($user) {
                return $user->get('user_id') ?? $user->get('id') ?? null;
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return null;
    }

    /**
     * 获取用户名
     */
    private function getUserName()
    {
        try {
            $user = app('auth')->user();
            if ($user) {
                return $user->get('user_name') ?? $user->get('name') ?? 'system';
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return 'system';
    }

    /**
     * 获取用户邮箱
     */
    private function getUserEmail()
    {
        try {
            $user = app('auth')->user();
            if ($user) {
                return $user->get('email') ?? null;
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return null;
    }

    /**
     * 获取请求数据
     */
    private function getRequestData($data)
    {
        try {
            $request = app('request');
            if ($request) {
                return $request->all();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return $data;
    }

    /**
     * 获取请求IP
     */
    private function getRequestIp()
    {
        try {
            $request = app('request');
            if ($request) {
                return $request->ip();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return null;
    }

    /**
     * 获取用户代理
     */
    private function getUserAgent()
    {
        try {
            $request = app('request');
            if ($request) {
                return $request->userAgent();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return null;
    }

    /**
     * 获取追踪ID
     */
    private function getTraceId()
    {
        try {
            $request = app('request');
            if ($request) {
                return $request->header('X-Trace-Id') ?? uniqid('trace_', true);
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        return uniqid('trace_', true);
    }

    /**
     * 获取简化的调用堆栈
     */
    private function getSimpleStackTrace()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $stack = [];
        foreach ($trace as $frame) {
            if (isset($frame['class']) && isset($frame['function'])) {
                $class = basename(str_replace('\\', '/', $frame['class']));
                $stack[] = $class . '::' . $frame['function'] . '()';
            }
        }
        return implode(' -> ', array_slice($stack, 0, 5));
    }

    /**
     * 获取上下文信息
     */
    private function getContext()
    {
        $context = [
            'is_upload' => false,
            'is_migration' => false,
            'json_file' => null,
        ];

        // 检查调用堆栈
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $frame) {
            if (isset($frame['function'])) {
                if ($frame['function'] == 'uploadMenus') {
                    $context['is_upload'] = true;
                }
                if (isset($frame['class']) && strpos($frame['class'], 'UpdateMenuListener') !== false) {
                    $context['is_migration'] = true;
                }
            }
        }

        return $context;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'shopmenu_id' => $entity->getShopmenuId(),
            'company_id' => $entity->getCompanyId(),
            'name' => $entity->getName(),
            'url' => $entity->getUrl(),
            'sort' => $entity->getSort(),
            'is_menu' => $entity->getIsMenu(),
            'pid' => $entity->getPid(),
            'apis' => $entity->getApis(),
            'icon' => $entity->getIcon(),
            'is_show' => $entity->getIsShow(),
            'alias_name' => $entity->getAliasName(),
            'version' => $entity->getVersion(),
            'disabled' => $entity->getDisabled(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }
}
