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

class RepositoryInterceptor
{
    private $target; // 原repository
    private $langRepository; // 代理的repository
    public $isLangue = 1; // 是否多语言代理,方式，1.原方法处理 2.直接转到其他方法
    private $commonLangModService;

    public function __construct($target , $langRepository = null)
    {
        $this->target = $target;
        if ($langRepository) {
            $this->langRepository;
        }else {
            $this->langRepository = new \CompanysBundle\Repositories\LangueRepository($target);
        }
        $this->commonLangModService = new CommonLangModService();
    }

    public function create($data) 
    {
        // XXX: review this code
        if ($this->isLangue == 1) {
            $info = $this->target->create($data);
            if (!empty($info)) {
                $data = $this->commonLangModService->getParamsLang($this->target, $data);
                $repository = $this->target;
                $companyId = $info['company_id'] ?? 0;
                $data_id = $info[$repository->primaryKey];
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
                $this->commonLangModService->saveLang( $companyId,$langueData['langBag'], $table, $data_id, $module);
            }
            return $info;
        } elseif($this->isLangue == 2) {
            return $this->langRepository->createLangue($data);
        }
        
        return $this->target->create($data);
    }

    public function updateOneBy(array $filter, array $data)
    {
        if ($this->isLangue == 1) {
            $info = $this->target->updateOneBy($filter, $data);
            if (!empty($info)) {
                $data = $this->commonLangModService->getParamsLang($this->target, $data);
                $repository = $this->target;
                $companyId = $info['company_id'] ?? 0;
                $data_id = $info[$repository->primaryKey];
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
                $this->commonLangModService->updateLangData( $companyId,$langueData['langBag'], $table, $data_id, $module);
            }
            return $info;
        }elseif ($this->isLangue == 2){
            return $this->langRepository->updateOneByLangue($filter, $data);
        }
        
        return $this->target->updateOneBy($filter, $data);
    }

    public function updateBy(array $filter, array $data)
    {
        if ($this->isLangue == 1) {
            $result = $this->target->updateBy($filter, $data);
            if (!empty($result)) {
                $data = $this->commonLangModService->getParamsLang($this->target, $data);
                $repository = $this->target;
                $entityList = $repository->lists($filter);
                if ($entityList['total_count'] > 0) {
                    foreach($entityList['list'] as $info) {
                        $companyId = $info['company_id'] ?? 0;
                        $data_id = $info[$repository->primaryKey];
                        $table = $repository->table;
                        $module = $repository->module;
                        $fieldLangue = $repository->langField;
                        $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
                        $this->commonLangModService->updateLangData( $companyId,$langueData['langBag'], $table, $data_id, $module);
                    }
                }
            }
            return $result;
        }elseif ($this->isLangue == 2) {
            return $this->langRepository->updateByLangue($filter, $data);
        }

        return $this->target->updateBy($filter, $data);
    }

    public function deleteById($id)
    {
        if ($this->isLangue == 1) {
            $result = $this->target->deleteById($id);
            if (!empty($result)) {
                $repository = $this->target;
                $companyId = $info['company_id'] ?? 0;
                $data_id = $id;
                $table = $repository->table;
                $module = $repository->module;
                $this->commonLangModService->deleteLang($companyId, $table, $data_id, $module);
            }
            return $result;
        }elseif ($this->isLangue == 2) {
            return $this->langRepository->deleteByIdLangue($id);
        }

         return $this->target->deleteById($id);
    }

    public function deleteBy($filter)
    {
        if ($this->isLangue == 1) {
            $repository = $this->target;
            $result = $this->target->deleteBy($filter);
            if (!empty($result)) {
                $entityList = $repository->lists($filter);
                if ($entityList['total_count'] > 0) {
                    foreach ($entityList['list'] as $info) {
                        $companyId = $info['company_id'] ?? 0;
                        $data_id = $info[$repository->primaryKey];
                        $table = $repository->table;
                        $module = $repository->module;
                        $this->commonLangModService->deleteLang($companyId, $table, $data_id, $module);
                    }
                }
            }
            return $result;
        } elseif ($this->isLangue == 2) {
            return $this->langRepository->deleteByLangue($filter);
        }

         return $this->target->deleteBy($filter);
    }
    
    public function delete($filter)
    {
         if ($this->isLangue == 1) {
            $repository = $this->target;
            $result = $this->target->delete($filter);
            if (!empty($result)) {
                $entityList = $repository->lists($filter);
                if ($entityList['total_count'] > 0) {
                    foreach ($entityList['list'] as $info) {
                        $companyId = $info['company_id'] ?? 0;
                        $data_id = $info[$repository->primaryKey];
                        $table = $repository->table;
                        $module = $repository->module;
                        $this->commonLangModService->deleteLang($companyId, $table, $data_id, $module);
                    }
                }
            }
            return $result;
        } elseif ($this->isLangue == 2) {
            return $this->langRepository->deleteByLangue($filter);
        }

         return $this->target->delete($filter);
    }

    public function getInfoById($id)
    {
        if ($this->isLangue == 1) {
            $info = $this->target->getInfoById($id);
            if (!empty($info)) {
                $repository = $this->target;
                $data_id = $repository->primaryKey;
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $lang = $this->commonLangModService->getLang();
                $info = $this->commonLangModService->getOneAddLang($info, $fieldLangue, $table, $lang, $data_id, $module);
            }
            return $info;
        }elseif ($this->isLangue == 2) {
            return $this->langRepository->getInfoByIdLangue($id);
        }

        return $this->target->getInfoById($id);
    }

    public function getInfo(array $filter)
    {
        if ($this->isLangue == 1) {
            $filter = $this->filterLang($filter);
            $info = $this->target->getInfo($filter);
            if (!empty($info)) {
                $repository = $this->target;
                $data_id = $repository->primaryKey;
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $lang = $this->commonLangModService->getLang();
                $info = $this->commonLangModService->getOneAddLang($info, $fieldLangue, $table, $lang, $data_id, $module);
            }
            return $info;
        }elseif ($this->isLangue == 2) {
            return $this->langRepository->getInfoLangue($filter);
        }

        return $this->target->getInfo($filter);
    }

    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = [], ...$paramsData)
    {
         if ($this->isLangue == 1) {
            if (empty($orderBy)) {
                // 解决默认排序时间字段不一致，有些是created 有些是create_at , 所以同意走主键desc
                $orderBy = [$this->target->primaryKey => 'desc'];
            }
            $result = $this->target->getLists($filter, $cols, $page, $pageSize, $orderBy, ...$paramsData);
            if (!empty($result)) {
                $repository = $this->target;
                $data_id = $repository->primaryKey;
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $lang = $this->commonLangModService->getLang();
                $lists = $this->commonLangModService->getListAddLang($result, $fieldLangue, $table, $lang, $data_id, $module);
                $result = $lists;
            }
            return $result;
        }elseif ($this->isLangue == 2) {
            return $this->langRepository->getListsLangue($filter, $page, $pageSize, $orderBy);
        }
            
        return $this->target->getLists($filter, $cols, $page, $pageSize, $orderBy, ...$paramsData);
    }

    public function lists($filter = [], $cols="*", $page = 1, $pageSize = 100, $orderBy = [], ...$paramsData)
    {
        if ($this->isLangue == 1) {
            if (empty($orderBy)) {
                // 解决默认排序时间字段不一致，有些是created 有些是create_at , 所以同意走主键desc
                $orderBy = [$this->target->primaryKey => 'desc'];
            }
            $filter = $this->filterLang($filter);
            $result = $this->target->lists($filter, $cols, $page, $pageSize, $orderBy, ...$paramsData);
            if ($result['total_count'] > 0) {
                $repository = $this->target;
                $data_id = $repository->primaryKey;
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $lang = $this->commonLangModService->getLang();
                $lists = $this->commonLangModService->getListAddLang($result['list'], $fieldLangue, $table, $lang, $data_id, $module);
                $result['list'] = $lists;
            }
            return $result;
        }elseif ($this->isLangue == 2) {
            return $this->langRepository->listsLangue($filter,$cols="*", $page, $pageSize, $orderBy);
        }
            
        return $this->target->lists($filter, $cols, $page, $pageSize, $orderBy, ...$paramsData);
    }

    public function filterLang($filter) 
    {
        $repository = $this->target;
        $ns = $this->commonLangModService;
        $prk = $repository->primaryKey;
        $table = $repository->table;
        $module = $repository->module;
        $fieldLangue = $repository->langField;
        $lang = $ns->getLang();
        $prkFilter = $filter[$prk] ?? ''; // 所有过滤字段keys
        foreach ($filter as $key => $value) {
            $dataIdArr = '';
            // 必须是多语言字段
            // 可能存在xx|xx 这种数据, 所以要兼容
            $filterkeys = explode('|', $key);
            if (in_array($filterkeys[0], $fieldLangue)) {
                $dataIdArr = $ns->filterByLang($lang, $key, $value, $table);
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
    
    // repository有些有特殊方法，我们都得兼容，目前没有想好拆分，暂时现在一起
    public function setParams($companyId, $templateName, $pageName, $configName, array $params, $version = 'v1.0.0', $pages_template_id = 0, $prkid = 0, $sortBy = 0)
    {
        if ($this->isLangue == 1) {
            // 这里返回的是主键id,或者是对象数组
            $result = $this->target->setParams($companyId, $templateName, $pageName, $configName, $params, $version, $pages_template_id, $prkid, $sortBy);
            if (!empty($result)) {
                 // 兼容多语言问题，必须得重新保存下自己的数据params里面的id
                $params['id'] = $result;
                $saveData['company_id'] = $companyId;
                $saveData['template_name'] = $templateName;
                $saveData['name'] = $configName;
                $saveData['page_name'] = $pageName;
                $saveData['version'] = $version;
                $saveData['params'] = $params;
                $saveData['pages_template_id'] = $pages_template_id;
                $data = $this->commonLangModService->getParamsLang($this->target, $saveData);
                $repository = $this->target;
                if (is_numeric($result)) {
                    $data_id = $result; // $result;
                }else {
                    $data_id = $result[$repository->primaryKey]; // $result[$repository->primaryKey];
                }
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $langueData = $this->commonLangModService->getLangData($data, $fieldLangue);
                $this->commonLangModService->updateLangData( $companyId,$langueData['langBag'], $table, $data_id, $module);
            }
            return $result;
        }

        return $this->target->setParams($companyId, $templateName, $pageName, $configName, $params, $version, $pages_template_id);
    }

    public function __call($method, $args) {
        return call_user_func_array([$this->target, $method], $args);
    }

}