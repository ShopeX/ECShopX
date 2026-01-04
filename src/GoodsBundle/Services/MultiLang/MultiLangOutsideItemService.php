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

namespace GoodsBundle\Services\MultiLang;

use CompanysBundle\MultiLang\MultiLangItem;
use GoodsBundle\Services\MultiLang\MagicLangTrait;

class MultiLangOutsideItemService
{
    use MagicLangTrait;

    public $multiLangFields = [];

    public $tableName ;
    public $moduleName ;

    public function __construct($tableName, $moduleName, $multiLangFields)
    {
        $this->tableName = $tableName;
        $this->moduleName = $moduleName;
        $this->multiLangFields = $multiLangFields;
    }
    /**
     * 获取多语言数据
     *
     * @param array $data 原始数据
     * @param array $field 多语言字段
     * @return array
     */
    public function getLangData($data, array $field)
    {
        $langBag = [];
        foreach ($data as $key => $value) {
            foreach ($field as $vF) {
                if ($key === $vF . '_lang') {
                    $langBag[] = $value;
                    unset($data[$key]);
                }
            }
        }
        return ['data' => $data, 'langBag' => $langBag];
    }

    public function addMultiLangByParams($id, array $params, string $module)
    {
        $lang = $this->getLang();
        $multiLang = $this->multiLangFields;
        app('log')->debug(__FUNCTION__.__LINE__.'多语言:=>multiLang:'.json_encode($multiLang));
        $langBag = [];
        if (!empty($multiLang)) {
            foreach ($multiLang as $vv) {
                if (isset($params[$vv])) {
                    $langBag[$vv] = $params[$vv];
                } else {
                    $langBag[$vv] = '';
                }
            }
        }
        app('log')->debug(__FUNCTION__.__LINE__.'多语言:=>langBag:'.json_encode($langBag));
        if (!empty($langBag)) {
            $this->addMultiTranslation($id, $langBag, $module,$params['company_id'] ?? 1);
        }
    }

    /**
     * 保存多语言数据
     *
     * @param int $companyId 公司ID
     * @param array $langBag 多语言数据
     * @param string $table 表名
     * @param int $id 数据ID
     * @param string $module 模块名
     * @return void
     */
    public function saveLang(int $companyId, array $langBag, string $table, int $id, string $module)
    {
        $baseInsertData = [
            'company_id' => $companyId,
            'table_name' => $table,
            'module_name' => $module,
        ];

        foreach ($langBag as $k => $v) {
            $field = str_replace("_lang", "", $k);
            foreach ($v as $lang => $vv) {
                $service = new MultiLangItem($lang);
                $insertData = $baseInsertData;
                $insertData['lang'] = $lang;
                $insertData['attribute_value'] = $vv;
                $insertData['field'] = $field;
                $insertData['data_id'] = $id;
                $service->insert($insertData);
            }
        }
    }

    /**
     * 获取指定语言的单条数据
     *
     * @param array $body 原始数据
     * @param array $field 多语言字段
     * @param string $tableName 表名
     * @param string $lang 语言
     * @param int $id 数据ID
     * @param string $module 模块名
     * @return array
     */
    public function getOneLangData(array $body, array $field, string $tableName, string $lang, int $id = 0, string $module = '')
    {
        $fieldStruct = $field;
        $filter = ['table_name' => $tableName, 'field' => $fieldStruct, 'lang' => $lang];
        if (!empty($id)) {
            $filter['data_id'] = $id;
        }
        if (!empty($module)) {
            $filter['module_name'] = $module;
        }

        $content = (new MultiLangItem($lang))->getListByFilter($filter, -1);
        if (empty($content)) {
            return [];
        }
        $structContent = [];
        foreach ($content as $v) {
            $structContent[$v['field']] = $v['attribute_value'];
        }
        foreach ($body as $key => $item) {
            if (!empty($structContent[$key])) {
                $body[$key] = $structContent[$key];
            }
        }
        return $body;
    }

    /**
     * 获取指定语言的列表数据
     *
     * @param array $dataList 原始数据列表
     * @param array $field 多语言字段
     * @param string $tableName 表名
     * @param string $lang 语言
     * @param string $prk 主键
     * @return array
     */
    public function getListAddLang(array $dataList, array $field, string $tableName, string $lang = 'zh-CN', $prk = 'id')
    {
        $ids = array_column($dataList, $prk);
        if (empty($ids)) {
            return $dataList;
        }
        $filter = ['table_name' => $tableName, 'field' => $field,  'data_id' => $ids];
        $service = new MultiLangItem($lang,$tableName);
        $listTmp = $service->getListByFilter($filter, -1);
        if (empty($listTmp)) {
            return $dataList;
        }
        $langStruct = [];
        foreach ($listTmp as $v) {
            $langStruct[$v['data_id']][] = ['field' => $v['field'], 'attribute_value' => $v['attribute_value']];
        }
        foreach ($dataList as $k => $v) {
            $id = $v[$prk];
            if (!empty($langStruct[$id])) {
                foreach ($langStruct[$id] as $langValue) {
                    $field = $langValue['field'];
                    $attrValue = $langValue['attribute_value'] ?? '';
                    if (!empty($attrValue)) {
                        $dataList[$k][$field] = $attrValue;
                    }
                }
            }
        }
        return $dataList;
    }

    /**
     * 更新多语言数据
     *
     * @param array $langBag 多语言数据
     * @param string $tableName 表名
     * @param int $dataId 数据ID
     * @return void
     */
    public function updateLangData(array $langBag, string $tableName, int $dataId)
    {
        $dataField = [];
        foreach ($this->multiLangFields as $field) {
            if(isset($langBag[$field])){
                $dataField[$field] = $langBag[$field];
            }
        }
        if(empty($dataField)){
            return;
        }
        $service = new MultiLangItem($this->getLang(),$tableName);
        foreach ($dataField as $k => $vv) {

//                $field = str_replace("_lang", "", $k);
            $updateData = [
                'attribute_value' => $vv,
            ];
            $filter = [
                'table_name' => $tableName,
                'field' => $k,
                'data_id' => $dataId,
                'lang' => '',
            ];
            $service->updateOrInsert($filter, $updateData);
        }
    }

    /**
     * 获取指定语言的店铺数据（兼容原有方法）
     *
     * @param array $distributor 店铺数据
     * @param string $lang 语言
     * @return array
     */
    public function getDistributorByLang(array $distributor, string $lang): array
    {
        if (empty($distributor) || !isset($distributor['distributor_id'])) {
            return $distributor;
        }

        return $this->getOneLangData($distributor, $this->multiLangFields, $this->tableName, $lang, $distributor['distributor_id'], $this->moduleName);
    }

    /**
     * 批量获取指定语言的店铺列表数据（兼容原有方法）
     *
     * @param array $distributorList 店铺列表
     * @param string $lang 语言
     * @return array
     */
    public function getDistributorListByLang(array $distributorList, string $lang): array
    {
        if (empty($distributorList)) {
            return [];
        }

        return $this->getListAddLang($distributorList, $this->multiLangFields, $this->tableName, $lang, 'distributor_id');
    }

    /**
     * 处理店铺创建时的多语言数据（兼容原有方法）
     *
     * @param int $companyId 公司ID
     * @param array $data 店铺数据
     * @param int $distributorId 店铺ID
     * @return bool
     */
    public function handleCreateMultiLang(int $companyId, array $data, int $distributorId): bool
    {
        $langData = [];
        foreach ($this->multiLangFields as $field) {
            $langKey = $field . '_lang';
            if (isset($data[$langKey]) && is_array($data[$langKey])) {
                $langData[$field] = $data[$langKey];
            }
        }

        if (!empty($langData)) {
            $this->saveLang($companyId, $langData, $this->tableName, $distributorId, $this->moduleName);
            return true;
        }

        return true;
    }

    /**
     * 处理店铺更新时的多语言数据（兼容原有方法）
     *
     * @param array $data 店铺数据
     * @param int $distributorId 店铺ID
     * @return bool
     */
    public function handleUpdateMultiLang(array $data, int $distributorId): bool
    {
        $langData = [];
        foreach ($this->multiLangFields as $field) {
            $langKey = $field . '_lang';
            if (isset($data[$langKey]) && is_array($data[$langKey])) {
                $langData[$field] = $data[$langKey];
            }
        }

        if (!empty($langData)) {
            $this->updateLangData($langData, $this->tableName, $distributorId);
            return true;
        }

        return true;
    }

    public function addMultiTranslation(int $id, array $translation, string $tableName,int $companyId = 1)
    {

        foreach ($translation as $key => $v) {
            $v = is_array($v) ? json_encode($v) : $v;
            $tmp = [
                'company_id'=> $companyId,
                'field' => $key,
//                    'lang' => $lang,
                'attribute_value' => $v,
                'table_name' => $tableName,
                'module_name' => $tableName,
                'data_id' => $id,
                'created'=>time(),
                'updated'=>time(),
            ];
            $service = new MultiLangItem($this->getLang(), $tableName);
            $service->insert($tmp);
        }

    }
}
