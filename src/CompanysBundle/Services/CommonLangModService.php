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

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;
use CompanysBundle\MultiLang\MultiLangItem;
use GoodsBundle\Services\MultiLang\MagicLangTrait;

class CommonLangModService
{
    use MagicLangTrait;

    // 多语言repository
    private $repository = [];
    // 多语言字段后缀
    private $suffix = '_lang';  

    public $totalLang = [];

    public function __construct()
    {
        $config = config('langue');
        if (empty($config)) {
            throw new ResourceException("不存在的多语言配置,请去config/langue.php配置");
        }
        $this->totalLang = $config;
    }
        
    public function getLangMapRepository($langue = 'zh')
    {
        $service = new MultiLangItem($langue, 'other');
        if (empty($service)) {
            throw new ResourceException("不存在的多语言service:{$langue}");
        }
            
        return $service;
    }
    
    // 获取多语言字段
    public function getFieldByLangue($company_id, $langue, $field, $data_id, $table, $module)
    {
        $repository = $this->getLangMapRepository($langue);
        $filter = [
            'company_id' => $company_id,
            'table_name' => $table,
            'module_name' => $module,
            'field' => $field,
            'data_id' => $data_id,
            'lang' => $langue,
        ];
        $info = $repository->getOneByFilter($filter);

        return $info['attribute_value'] ?? '';
    }

    public function updateDataIdByLangue($company_id, $langue, $table, $module, $data_id, $new_data_id)
    {
        $repository = $this->getLangMapRepository($langue);
        $filter = [
            'company_id' => $company_id,
            'table_name' => $table,
            'module_name' => $module,
            'data_id' => $data_id,
        ];
        $info = $repository->getOneByFilter($filter);
        if (empty($info)) {
            return false;
        }
        $res = $repository->updateByFilter( $filter,
        [
            'data_id' => $new_data_id
        ]);

        return $res ?? false;
    }

    // 获取参数中的多语言字段
    public function getParamsLang($repository, $data)
    {
        $langueField = $repository->langField;
        $data = $this->getParamsLangField($langueField, $data);

        return $data;
    }

    public function getParamsLangField($langueField, $data) {
        $request = app('request');
        // foreach ($langueField as $k=> $val) {
        //     $langueField[$k] = $val.$this->suffix;
        // }
        
        // 获取提交的多语言字段数据
        $paramsData = $request->all(extract($langueField));
        if (!empty($paramsData)) {
            // 这里用原数据覆盖获取数据，可能中间数据请求数据会被调整，如果不存在就用请求数据
            $data = array_merge($paramsData, $data);
        } 

        return $data;
    }

    /**
     * 获取多语言数据字段
     * @param mixed $data  源数据字段
     * @param array $field 多语言数据字段
     * @return array{data: mixed, langBag: array}
     */
    public function getLangData($data,array $fields)
    {
        $langBag = [];
        foreach ($data as $key => $value) {
            foreach ($fields as $vF){
                $keyArr = explode('|', $vF);
                $field = $keyArr[0] ?? '';
                $opts = $keyArr[1] ?? '';
                if($key === $field && !empty($value)){
                    $langBag[$field] = $this->optsHandle($value, $opts ?? '' ) ;
                }
            }
        }

        return ['data'=>$data,'langBag'=>$langBag];
    }

    public function optsHandle($value, $opts)
    {
        switch($opts)
        {
            case 'json':
                $value = json_encode($value);
                break;
            case 'serialize':
                $value = serialize($value);
                break;
        }

        return $value;
    }

    public function outOptsHandle($value, $opts)
    {
        switch($opts)
        {
            case 'json':
                $value = json_decode($value, 1);
                break;
            case 'serialize':
                $value = unserialize($value);
                break;
        }

        return $value;
    }

    /**
     * 保存多语言字段
     * @param int $companyId
     * @param array $langBag 多语言数据字段
     * @param string $table
     * @param int $id   多语言对应表业务
     * @param string $module
     * @return void
     */
    public function saveLang(int $companyId, array $langBag, string $table, int $id, string $module, $langue = '')
    {
        if (empty($langBag)) {
            return false;
        }
        $baseInsertData = [
            'company_id' => $companyId,
            'table_name' => $table,
            'module_name' => $module,
//            'lang' => $lang,
        ];
        if ($langue) {
            $lang = $langue;
        }else {
            $lang = $this->getLang();
        }
        foreach ($langBag as $k => $v) {
            $insertData = $baseInsertData;
            $insertData['lang'] = $lang;
            $insertData['attribute_value'] = $v;
            $insertData['field'] = $k;
            $insertData['data_id'] = $id;
            $insertData['created'] = time();
            $insertData['updated'] = time();
            $repository = $this->getLangMapRepository($lang);
            // $repository->create($insertData);
            $repository->insert($insertData);
        }

        return true;
    }

    public function updateLangData(int $companyId, array $langBag, string $tableName, int $dataId, string $module, $langue = '')
    {
        if (empty($langBag)) {
            return false;
        }
        if ($langue) {
            $lang = $langue;
        }else {
            $lang = $this->getLang();
        }
        foreach ($langBag as $k => $v) {
            $v = is_array($v) ? json_encode($v) : $v;
            $updateData = [
                'attribute_value' => $v,
                'updated' => time(),
            ];
            $filter = [
                'company_id' => $companyId,
                'table_name'=> $tableName,
                'module_name' => $module,
                'field'=> $k,
                'data_id'=> $dataId,
                'lang'=> $lang,
            ];
            $repository = $this->getLangMapRepository($lang);
            // $info = $repository->getInfo($filter);
            $info = $repository->getOneByFilter($filter);
            
            if (empty($info)) {
                 $baseInsertData = [
                    'company_id' => $companyId,
                    'table_name' => $tableName,
                    'module_name' => $module,
        //            'lang' => $lang,
                ];
                $insertData = $baseInsertData;
                $insertData['lang'] = $lang;
                $insertData['attribute_value'] = $v;
                $insertData['field'] = $k;
                $insertData['data_id'] = $dataId;
                $insertData['created'] = time();
                $insertData['updated'] = time();
                // $repository->create($insertData);
                $repository->insert($insertData);
            }else{
                // $repository->updateOneBy($filter,$updateData);
                $repository->updateByFilter($filter,$updateData);
            }
        }

        return true;
    }

    public function deleteLang(int $companyId, string $tableName, int $dataId, string $module)
    {
        $lang = $this->getLang();
        $repository = $this->getLangMapRepository($lang);
        $filter = [
            'company_id' => $companyId,
            'table_name'=> $tableName,
            'module_name' => $module,
            'data_id'=> $dataId,
        ];
        $repository->deleteBy($filter);
        
        return true;
    }

    public function filterByLang(string $lang, string $field, string $content, string $tableName)
    {
        // 处理带操作符的字段名，如 page_name|contains，只取字段名部分
        $fieldParts = explode('|', $field);
        $fieldName = $fieldParts[0];
        
        $filter = [
            'lang'=>$lang,
            'field'=>$fieldName,
            'table_name'=>$tableName,
            'attribute_value|contains'=>$content,
        ];
        $repository = $this->getLangMapRepository($lang);
        $list = $repository->getListByFilter($filter, -1, -1);
        if(empty($list)){
            return  [];
        }
        return array_column($list,'data_id');
    }

    private function fieldHandle($fields)
    {
        $newFields = [];
        $fieldOpts = [];
        if (!empty($fields) && is_array($fields)) {
            foreach($fields as $k => $vF) {
                $keyArr = explode('|', $vF);
                $field = $keyArr[0] ?? '';
                $opts = $keyArr[1] ?? '';
                $newFields[] = $field;
                $fieldOpts[$field] = $opts;
            }
        }

        return ['fields'=>$newFields,'fieldOpts'=>$fieldOpts];
    }

    public function getOneAddLang(array $body, array $fields, string $tableName, string $langue, string $prk = 'id', string $module = '')
    {   
        $id = $body[$prk];
        if(empty($id)){
            return $body;
        }
        $totalLang = $this->totalLang;
        $langMap = [];
        $fieldsArr = $this->fieldHandle($fields);
        $fields = $fieldsArr['fields'];
        $fieldsOpts = $fieldsArr['fieldOpts'];
        foreach($totalLang as $lang) {
            $filter = ['table_name' => $tableName, 'field' => $fields, 'lang' => $lang, 'data_id'=>$id];
            if (!empty($module)) {
                $filter['module_name'] = $module;
            }
            $repository = $this->getLangMapRepository($lang);
            $content = $repository->getListByFilter($filter, -1, -1);
            if(empty($content)){
                continue;
            }
            // 过滤数据字段
            foreach($content as $k => $v){
                if(isset($fieldsOpts[$v['field']]) && !empty($fieldsOpts[$v['field']])){
                    $content[$k]['attribute_value'] = $this->outOptsHandle($v['attribute_value'],$fieldsOpts[$v['field']]);
                }
            }
            $langMap[$lang] = $content;
        }
        if (empty($langMap)) {
            return $body;
        }
        $structContent = [];
        foreach($langMap as $lang => $content) {
            foreach ($content as $v) {
                $structContent[$lang][$v['field']] = $v['attribute_value'];
            }
        }
        foreach($langMap as $lang => $content) {
            foreach ($body as $key => $item) {
                // 把字段复制给原数据字段
                if (!empty($structContent[$lang][$key]) && $lang == $langue) {
                    $body[$key] = $structContent[$lang][$key];
                }
                // 增加多语言字段
                if (!empty($structContent[$lang][$key])) {
                    $body[$key.$this->suffix][$lang] = $structContent[$lang][$key];
                }
            }
        }

        return $body;
    }

    public function getListAddLang(array $dataList,array $fields,string $tableName, string $langue = 'zh-CN', $prk = 'id')
    {
        $ids = array_column($dataList,$prk);
        if(empty($ids)){
            return $dataList;
        }
        $totalLang = $this->totalLang;
        $langMap = [];
        $fieldsArr = $this->fieldHandle($fields);
        $fields = $fieldsArr['fields'];
        $fieldsOpts = $fieldsArr['fieldOpts'];
        foreach($totalLang as $lang) {
            $filter = ['table_name' => $tableName, 'field' => $fields, 'lang' => $lang, 'data_id'=>$ids];
            $repository = $this->getLangMapRepository($lang);
            $listTmp = $repository->getListByFilter($filter, -1, -1);
            if(empty($listTmp)){
                continue;
            }
              // 过滤数据字段
            foreach($listTmp as $k => $v){
                if(isset($fieldsOpts[$v['field']]) && !empty($fieldsOpts[$v['field']])){
                    $listTmp[$k]['attribute_value'] = $this->outOptsHandle($v['attribute_value'],$fieldsOpts[$v['field']]);
                }
            }
            $langMap[$lang] = $listTmp;
        }
        if(empty($langMap)){
            return $dataList;
        }
        $langStruct = [];
        foreach($langMap as $lang => $listTmp) {
            foreach ($listTmp as $v){
                $langStruct[$lang][$v['data_id']][] = ['field'=>$v['field'],'attribute_value'=>$v['attribute_value']];
            }
        }
        foreach($langMap as $lang => $listTmp) {
            foreach ($dataList as $k => $v){
                $id = $v[$prk];
                if(!empty($langStruct[$lang][$id])){
                    foreach ($langStruct[$lang][$id]  as $langValue){
                        $field = $langValue['field'];
                        $attrValue = $langValue['attribute_value'] ?? '';
                        if(!empty($attrValue) && $lang == $langue){
                            $dataList[$k][$field] = $attrValue;
                        }
                        if(!empty($attrValue)){
                            $dataList[$k][$field.$this->suffix][$lang] = $attrValue;
                        }
                    }
                }
            }
        }

        return  $dataList;
    }
    
    // 用于处理多语言redis保存数据
    public function setLangDataIndexLangBak($data,array $fields)
    {
        $oldData = $data;
        $data = $this->getParamsLangField($fields, $data);
        $langBag = [];
        foreach ($data as $key => $value) {
            foreach ($fields as $vF){
                // 有数据需要json存储，或者其他，所以需要知道处理
                $keyArr = explode('|', $vF);
                $field = $keyArr[0] ?? '';
                $opts = $keyArr[1] ?? '';
                if($key === $field && !empty($value)){
                    $langBag[$vF] = $this->optsHandle($value, $opts ?? '' ) ;
                }
            }
        }
        if (!empty($langBag)) {
            $resultLang = [];
            $langue = $this->getLang();
            $resultLang[$langue] = array_merge($oldData, $langBag);
        }

        return !empty($resultLang) ? $resultLang : $oldData;
    }

    public function setLangDataIndexLang($data, $fields = '')
    {
        $langue = $this->getLang();

        return [ $langue => $data ] ;
    }

    public function getLangDataIndexLang($data, $lang = '')
    {
        if (empty($lang)) {
            $lang = $this->getLang();
        }

        return isset($data[$lang]) ? $data[$lang] : [];
    }

}
