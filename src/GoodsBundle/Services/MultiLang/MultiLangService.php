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
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\MultiLangConfig;
use GoodsBundle\Entities\MultiLangMod;
use GoodsBundle\Repositories\ItemRelAttributesRepository;
use GoodsBundle\Repositories\MultiLangConfigRepository;
use GoodsBundle\Repositories\MultiLangModRepository;

class MultiLangService
{
    private $multiLangAttr = [
        'items' => [
            'item_name', 'brief', 'intro'
        ],
        'items_attributes'=>[
            'attribute_name','attribute_memo'
        ],
        'items_attribute_values'=>[
            'attribute_value'
        ],
        'items_category'=>[
            'category_name'
        ],
        'items_tags'=>[
            'tag_name'
        ],
        'shipping_templates'=>[
            'name'
        ],
        'members_tags'=>[
            'tag_name'
        ],
        'membercard_grade'=>[
            'grade_name','description'
        ],
        'config_request_fields'=>[
            'label','validate_condition','alert_required_message'
        ],
    ];

    private $multiLangPriKey = [
        'items' => 'item_id',
    ];
    use MagicLangTrait;

    /**
     * @var $multiLangModRepository MultiLangModRepository
     */
    private $multiLangModRepository;

    /**
     * @var $multiLangConfigRepository MultiLangConfigRepository
     */
    private $multiLangConfigRepository;

    private $totlaLang = [
        'zh', 'en'
    ];

    public function __construct()
    {
        $this->multiLangConfigRepository = app('registry')->getManager('default')->getRepository(MultiLangConfig::class);
        $this->multiLangModRepository = app('registry')->getManager('default')->getRepository(MultiLangMod::class);

    }

    public function getLangData($data, array $field)
    {
        // Powered by ShopEx EcShopX
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

    public function getLangDataDp($data, array $field)
    {
        $langBag = [];
        foreach ($data as $key => $value) {
            foreach ($field as $vF) {
                if ($key === $vF) {
                    $langBag[] = $value;
                    unset($data[$key]);
                }
            }
        }
        return ['data' => $data, 'langBag' => $langBag];

    }

    public function saveLang(int $companyId, array $langBag, string $table, int $id, string $module)
    {
        $baseInsertData = [
            'company_id' => $companyId,
            'table_name' => $table,
            'module_name' => $module,
//            'lang' => $lang,
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
                //$this->multiLangModRepository->create($insertData);
                $service->insert($insertData);
            }
        }

    }

    public function getOneLangData(array $body, array $field, string $tableName, string $lang, int $id = 0, string $module = '')
    {
//        $fields = $this->multiLangConfigRepository->getLists(['table_name' => $tableName], '*', 1, -1);
//        if (empty($fields)) {
//            return [];
//        }
//        $fieldStruct = array_column($fields, 'field');
        if(empty($field)){
            $fieldStruct = $this->multiLangAttr[$tableName] ?? [];
        }else{
            $fieldStruct = $field;
        }

        $filter = ['table_name' => $tableName, 'field' => $fieldStruct];
        if (!empty($id)) {
            $filter['data_id'] = $id;
        }
        if (!empty($module)) {
            $filter['module_name'] = $module;
        }

        $content = (new MultiLangItem($lang))->getListByFilter($filter, -1);
//        $content = $this->multiLangModRepository->getLists($filter, '*', 1, -1);
        if (empty($content)) {
            return $body;
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

    public function getListAddLang(array $dataList, array $field, string $tableName, string $lang = 'zh', $prk = 'id')
    {
        $ids = array_column($dataList, $prk);
        if (empty($ids)) {
            return $dataList;
        }
        $newField = $field;
        if(!empty($this->multiLangAttr[$tableName])){
            $newField = $this->multiLangAttr[$tableName];
        }
        $filter = ['table_name' => $tableName, 'field' => $newField, 'data_id' => $ids];
        $service = new MultiLangItem($lang);
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

    public function updateLangData(array $langBag, string $tableName, int $dataId)
    {
        $dataField = [];
        if(empty($this->multiLangAttr[$tableName])){
            return;
        }
        foreach ($this->multiLangAttr[$tableName] as $field) {
            if(isset($langBag[$field])){
                $dataField[$field] = $langBag[$field];
            }
        }
        if(empty($dataField)){
            return;
        }
        $service = new MultiLangItem($this->getLang());
        foreach ($dataField as $k => $vv){


//            $field = str_replace("_lang", "", $k);
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

    public function updateLangDataParams(array $langBag, string $tableName, int $dataId)
    {
        $lang = $this->getLang();
        $service = new MultiLangItem($lang);
        foreach ($langBag as $key=>$v){

            $updateData = [
                'attribute_value' => $v,
            ];
            $filter = [
                'table_name' => $tableName,
                'field' => $key,
                'data_id' => $dataId,
                'lang' => $lang,
            ];
            $service->updateByFilter($filter, $updateData);
        }
    }

    //搜索用
    public function filterByLang(string $lang, string $field, string $content, string $tableName)
    {
        $filter = [
            'lang' => $lang,
            'field' => $field,
            'table_name' => $tableName,
            'attribute_value|contains' => $content,
        ];
        $service = new MultiLangItem($lang);
        $list = $service->getListByFilter($filter, -1);
        if (empty($list)) {
            return [];
        }
        return array_column($list, 'data_id');
    }


    public function getItemSpec(array $itemId) //这里是sku级别啊！！！！拿规格值得
    {
        if (empty($itemId)) {
            return [];
        }

        $itemIdStr = implode(',', $itemId);
        $lang = $this->getLang();
        /**
         * @var $itemsRelAttributeRepository ItemRelAttributesRepository
         */
//        $itemsRelAttributeRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $sql = "
        select rel.item_id,ia.attribute_id,ia.attribute_name,iv.attribute_value_id,iv.attribuattribute_valuete_name from items_rel_attributes rel
            join items_attributes ia on ia.attribute_id = rel.attribute_id
            join  items_attribute_values iv on rel.attribute_value_id = iv.attribute_value_id
        where ia.attribute_type = 'item_spec' and item_id in ($itemIdStr)
        ";
        $conn = app('registry')->getConnection('default');

        $ret = $conn->fetchAll($sql);
        $ret = $this->getListAddLang($ret, ['attribute_name'], 'items_attributes', $lang, 'attribute_id');
        $ret = $this->getListAddLang($ret, ['attribuattribute_valuete_name'], 'items_attribute_values', $lang, 'attribute_value_id');
        $tmp = [];
        foreach ($ret as $v) {
            $itemId = $v['item_id'];
            $tmp[$itemId][] = $v;
        }
        return $tmp;
    }


    public function addMultiTranslation(int $id, array $translation, string $tableName,int $companyId = 1)
    {

//        foreach ($translation as $lang => $data) {
//
//        }
        foreach ($translation as $key => $v) {
            $tmp = [
                'field' => $key,
//                'lang' => $lang,
                'attribute_value' => $v,
                'table_name' => $tableName,
                'module_name' => $tableName,
                'data_id' => $id,
                'company_id'=> $companyId,
                'created'=>time(),
                'updated'=>time(),
            ];
            $service = new MultiLangItem($this->getLang());
            $service->insert($tmp);
//                $this->multiLangModRepository->create($tmp);
        }

    }

    public function getTranslation(int $dataId, string $tableName)
    {
        $dataRow = $this->multiLangModRepository->getLists(['data_id' => $dataId, 'table_name' => $tableName]);
        if (empty($dataRow)) {
            return [];
        }
        $retData = [];
        foreach ($dataRow as $vd) {
            $lang = $vd['lang'];
            $retData[$lang][$vd['field']] = $vd['attribute_value'];
        }
        return $retData;
    }

    public function getTranslationByLang(array $params, int $dataId, string $tableName)
    {
        $lang = $this->getLang();
        $service = new MultiLangItem($lang);

        $dataRow = $service->getListByFilter(['data_id' => $dataId, 'table_name' => $tableName]);
//        $dataRow = $this->multiLangModRepository->getLists();
        if (empty($dataRow)) {
            return $params;
        }
        
        foreach ($dataRow as $vd) {
            $field = $vd['field'];
            if(isset($params[$field])) {
                $params[$field] = $vd['attribute_value'];
            }
        }
        return $params;
    }

    public function getTranslationByLangList(array $params, int $dataId, string $tableName)
    {

    }

    public function getSingleLangTranslation(int $dataId, string $tableName, string $lang)
    {
        $service = new MultiLangItem($lang);
        $dataRow = $service->getListByFilter(['data_id' => $dataId, 'table_name' => $tableName]);
        if (empty($dataRow)) {
            return [];
        }
        $retData = [];
        foreach ($dataRow as $vd) {
            $lang = $vd['lang'];
            $retData[$lang][$vd['field']] = $vd['attribute_value'];
        }
        return $retData;
    }

    //更新输出数据格式和多语言对齐
    public function structOutPutData(array $outPutData, array $langBag)
    {
        foreach ($outPutData as $k => $v) {
            if (isset($langBag[$k])) {
                $outPutData[$k] = $langBag[$k];
            }
        }
        return $outPutData;
    }

    public function addMultiLangByParams($id, array $params, string $module)
    {
        $lang = $this->getLang();
        $multiLang = $this->multiLangAttr[$module] ?? [];
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
        if (!empty($langBag)) {
            $this->addMultiTranslation($id, $langBag, $module,$params['company_id'] ?? 1);
        }
    }

    public function updateMultiLangByParams(array $filter, array $params, string $module)
    {
        if(empty($this->multiLangPriKey[$module])){
            return;
        }
        $key = $this->multiLangPriKey[$module];
        if(empty($filter[$key])){
            return;
        }
        $lang = $this->getLang();
        $multiLang = $this->multiLangAttr[$module] ?? [];
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
        if (!empty($langBag)) {
            $this->updateLangDataParams($langBag,$module,$filter[$key]);
        }
    }

    public function translateItemList(array $itemsList,array $colum = ['item_name'])
    {
        $itemsList = $this->getListAddLang($itemsList,$colum,'items',$this->getLang(),'item_id');
        foreach ($itemsList as $i => $vvv){
            $itemsList[$i]['itemName'] = $vvv['item_name'] ?? '';
        }
        return $itemsList;
    }

//    public function updateTranslation(int $id,array $translation,string $table)
//    {
//        foreach ($translation as $lang => $data) {
//            foreach ($data as $key => $v) {
//                $tmp = [
//                    'field' => $key,
//                    'lang' => $lang,
//                    'attribute_value' => $v,
////                    'table_name' => $table,
//                    'module_name' => $table,
////                    'data_id' => $id,
//                ];
//                $filter = [
//                    'data_id' => $id,
//                    'table_name' => $table,
//                ];
//                $this->multiLangModRepository->updateBy($filter, $tmp);
//            }
//        }
//
//    }

}
