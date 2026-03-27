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

 
// repository类，可以适配多语言，也可以代理多言语repository
namespace CompanysBundle\Services;

use CompanysBundle\Services\CommonLangModService;

class RepositoryLangInterceptor
{
    private $target; // 原repository
    private $commonLangModService;

    public function __construct($target)
    {
        $this->target = $target;
        if (method_exists($this->target, 'setRepositoryLangInterceptor')) {
            $this->target->setRepositoryLangInterceptor($this);
        }
        $this->commonLangModService = new CommonLangModService();
    }

    public function create(...$params)
    {
        $paramsForTarget = $params;
        $originalData = null;
        if (!$this->commonLangModService->isDefaultLang()) {
            $langField = $this->target->langField;
            $dataIndex = count($params) === 1 ? 0 : null;
            if ($dataIndex === null) {
                for ($i = count($params) - 1; $i >= 0; $i--) {
                    if (is_array($params[$i])) {
                        $dataIndex = $i;
                        break;
                    }
                }
            }
            if ($dataIndex !== null && isset($params[$dataIndex]) && is_array($params[$dataIndex])) {
                $originalData = $params[$dataIndex];
                $dataCopy = $this->commonLangModService->stripLangFieldsFromData($params[$dataIndex], $langField);
                $paramsForTarget = $params;
                $paramsForTarget[$dataIndex] = $dataCopy;
            }
        }
        $result = $this->target->create(...$paramsForTarget);
        if (!empty($result)) {
            $langSource = ($originalData !== null) ? array_merge($result, $originalData) : $result;
            $data = $this->commonLangModService->getParamsLang($this->target, $langSource);
            $companyId = $result['company_id'] ?? 0;
            $dataId = $result[$this->target->primaryKey];
            $table = $this->target->table;
            $module = $this->target->module;
            $fieldLangue = $this->target->langField;
            $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
            $this->commonLangModService->saveLang($companyId, $langueData['langBag'], $table, $dataId, $module);
        }
        return $result;
    }

    public function updateOneBy(...$params)
    {
        $paramsForTarget = $params;
        $originalData = null;
        if (!$this->commonLangModService->isDefaultLang() && count($params) >= 2) {
            $langField = $this->target->langField;
            $data = $params[count($params) - 1];
            if (is_array($data)) {
                $originalData = $data;
                $dataCopy = $this->commonLangModService->stripLangFieldsFromData($data, $langField);
                $paramsForTarget = $params;
                $paramsForTarget[count($params) - 1] = $dataCopy;
            }
        }
        $result = $this->target->updateOneBy(...$paramsForTarget);
        if (!empty($result)) {
            $langSource = ($originalData !== null) ? array_merge($result, $originalData) : $result;
            $data = $this->commonLangModService->getParamsLang($this->target, $langSource);
            $companyId = $result['company_id'] ?? 0;
            $dataId = $result[$this->target->primaryKey];
            $table = $this->target->table;
            $module = $this->target->module;
            $fieldLangue = $this->target->langField;
            $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
            $this->commonLangModService->updateLangData($companyId, $langueData['langBag'], $table, $dataId, $module);
        }
        return $result;
    }

    public function updateBy($filter, ...$params)
    {
        $paramsForTarget = $params;
        $originalData = null;
        if (!$this->commonLangModService->isDefaultLang() && isset($params[0]) && is_array($params[0])) {
            $originalData = $params[0];
            $langField = $this->target->langField;
            $dataCopy = $this->commonLangModService->stripLangFieldsFromData($params[0], $langField);
            $paramsForTarget = $params;
            $paramsForTarget[0] = $dataCopy;
        }
        $result = $this->target->updateBy($filter, ...$paramsForTarget);
        if (!empty($filter)) {
            $this->processByFilterInPages($filter, function (array $list) use ($originalData) {
                $table = $this->target->table;
                $module = $this->target->module;
                foreach ($list as $info) {
                    $companyId = $info['company_id'] ?? 0;
                    $dataId = $info[$this->target->primaryKey];
                    $langSource = ($originalData !== null) ? array_merge($info, $originalData) : $info;
                    $data = $this->commonLangModService->getParamsLang($this->target, $langSource);
                    $fieldLangue = $this->target->langField;
                    $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
                    $this->commonLangModService->updateLangData($companyId, $langueData['langBag'], $table, $dataId, $module);
                }
            });
        }
        return $result;
    }

    public function deleteById(...$params)
    {
        $info = $this->target->getInfoById(...$params);
        $companyId = $info['company_id'] ?? 0;
        $result = $this->target->deleteById(...$params);
        if (!empty($info)) {
            $dataId = $info[$this->target->primaryKey];
            $table = $this->target->table;
            $module = $this->target->module;
            $this->commonLangModService->deleteAllLang($companyId, $table, $dataId, $module);
        }
        return $result;
    }

    public function deleteBy($filter)
    {
        $this->processByFilterInPages($filter, function (array $list) {
            $table = $this->target->table;
            $module = $this->target->module;
            foreach ($list as $info) {
                $companyId = $info['company_id'] ?? 0;
                $dataId = $info[$this->target->primaryKey];
                $this->commonLangModService->deleteAllLang($companyId, $table, $dataId, $module);
            }
        });
        return $this->target->deleteBy($filter);
    }
    
    public function delete($filter)
    {
        $this->processByFilterInPages($filter, function (array $list) {
            $table = $this->target->table;
            $module = $this->target->module;
            foreach ($list as $info) {
                $companyId = $info['company_id'] ?? 0;
                $dataId = $info[$this->target->primaryKey];
                $this->commonLangModService->deleteAllLang($companyId, $table, $dataId, $module);
            }
        });
        return $this->target->delete($filter);
    }

    public function getInfoById(...$params)
    {
        $result = $this->target->getInfoById(...$params);
        if (!empty($result)) {
            $prk = $this->target->primaryKey;
            $table = $this->target->table;
            $module = $this->target->module;
            $fieldLangue = $this->target->langField;
            $lang = $this->commonLangModService->getLang();
            $result = $this->commonLangModService->getOneAddLang($result, $fieldLangue, $table, $lang, $prk, $module);
        }
        return $result;
    }

    public function getInfo($filter)
    {
        $filter = $this->filterLang($filter);
        $result = $this->target->getInfo($filter);
        if (!empty($result)) {
            $prk = $this->target->primaryKey;
            $table = $this->target->table;
            $module = $this->target->module;
            $fieldLangue = $this->target->langField;
            $lang = $this->commonLangModService->getLang();
            $result = $this->commonLangModService->getOneAddLang($result, $fieldLangue, $table, $lang, $prk, $module);
        }
        return $result;
    }

    public function getLists($filter, ...$params)
    {
        $result = $this->target->getLists($filter, ...$params);
        if (!empty($result)) {
            $prk = $this->target->primaryKey;
            $table = $this->target->table;
            $module = $this->target->module;
            $fieldLangue = $this->target->langField;
            $lang = $this->commonLangModService->getLang();
            $result = $this->commonLangModService->getListAddLang($result, $fieldLangue, $table, $lang, $prk, $module);
        }
        return $result;
    }

    public function lists($filter, ...$params)
    {
        $filter = $this->filterLang($filter);
        $result = $this->target->lists($filter, ...$params);
        if (!empty($result['total_count']) && $result['total_count'] > 0) {
            $prk = $this->target->primaryKey;
            $table = $this->target->table;
            $module = $this->target->module;
            $fieldLangue = $this->target->langField;
            $lang = $this->commonLangModService->getLang();
            $result['list'] = $this->commonLangModService->getListAddLang($result['list'], $fieldLangue, $table, $lang, $prk, $module);
        }
        return $result;
    }

    /**
     * 仅用 $filter 分页循环：page=1 起，pageSize=500，调用 target->lists 直到当页 list 为空；每页 list 交给 $processPageList 处理。
     * 兼容返回结构 ['list'=>..., 'total_count'=>...]。
     *
     * @param array $filter
     * @param callable $processPageList 接收单页 list 的回调
     */
    private function processByFilterInPages(array $filter, callable $processPageList): void
    {
        $page = 1;
        $pageSize = 500;
        do {
            $result = $this->target->lists($filter, '*', $page, $pageSize);
            $list = $result['list'] ?? [];
            if (empty($list)) {
                break;
            }
            $processPageList($list);
            $page++;
        } while (true);
    }

    public function filterLang($filter) 
    {
        $ns = $this->commonLangModService;
        $prk = $this->target->primaryKey;
        $table = $this->target->table;
        $fieldLangue = $this->target->langField;
        $lang = $ns->getLang();
        $prkFilter = $filter[$prk] ?? 0; // 所有过滤字段主键
        $companyId = $filter['company_id'] ?? 0;
        foreach ($filter as $key => $value) {
            $dataIdArr = [];
            // 必须是多语言字段
            // 可能存在xx|xx 这种数据, 所以要兼容
            $filterkeys = explode('|', $key);
            if (in_array($filterkeys[0], $fieldLangue)) {
                $dataIdArr = $ns->filterByLang($lang, $key, $value, $table, $companyId);
                // 如果存在多语言字段主键，说明可能其他地方使用了主键过滤，我们需要合并掉
                if (!empty($dataIdArr)) {
                    if (!empty($prkFilter)) {
                        $filter[$prk] = array_merge((array)$prkFilter, $dataIdArr);
                    }else{
                        $filter[$prk] = $dataIdArr;
                    }
                    // 移除多语言字段的原始过滤条件，因为已经转换为按主键过滤
                    unset($filter[$key]);
                }
            }
        }
        
        return $filter;
    }

    public function saveLangData($companyId, $dataId, $data)
    {
        $data = $this->commonLangModService->getParamsLang($this->target, $data);
        $table = $this->target->table;
        $module = $this->target->module;
        $fieldLangue = $this->target->langField;
        $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
        $this->commonLangModService->updateLangData($companyId, $langueData['langBag'], $table, $dataId, $module);
        return true;
    }

    public function __call($method, $args) {
        return call_user_func_array([$this->target, $method], $args);
    }

}
