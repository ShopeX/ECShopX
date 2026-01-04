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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\CategoryTaxRate;
use OrdersBundle\Repositories\CategoryTaxRateRepository;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsCategoryService;

class CategoryTaxRateService
{
    /** @var CategoryTaxRateRepository */
    public $repository;

    // 可更新字段
    public $allowFields = [
        'sales_party_id'=>'销售方ID',
        'tax_rate_type'=>'税率分类',
        'category_ids'=>'分类ID数组',
        'invoice_tax_rate'=>'发票税率',
    ];

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(CategoryTaxRate::class);
    }

    public function getTaxRateList($filter, $page = 1, $pageSize = 20, $orderBy = ['id' => 'DESC'])
    {
        return $this->repository->getLists($filter, '*', $page, $pageSize, $orderBy);
    }

    public function getTaxRateDetail($id)
    {
        return $this->repository->getInfoById($id);
    }

    public function createTaxRate($data)
    {
        $this->validateTaxRateData($data);
        $category_ids = $data['category_ids'];

        // category_ids 需转 json 存储
        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $data['category_ids'] = json_encode($data['category_ids'], JSON_UNESCAPED_UNICODE);
        }
        //判断items_category表里面category_ids是否已有不同的invoice_tax_rate_id 
        $itemsCategoryService = new ItemsCategoryService();
        $itemsCategoryList = $itemsCategoryService->getItemsCategoryByInvoiceTaxRateId($data['category_ids'] );
        app('log')->info(__FUNCTION__.':'.__LINE__.':itemsCategoryList:'.json_encode($itemsCategoryList));  
        if ($itemsCategoryList) {
            $category_name = implode(',', array_column($itemsCategoryList, 'category_name'));
            throw new ResourceException(trans('OrdersBundle/Order.category_tax_rate_exists', ['{0}' => $category_name]));
        }
        // 事物开始
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try{
            $res = $this->repository->create($data);
            app('log')->info(__FUNCTION__.':'.__LINE__.':res:'.json_encode($res));
            $invoice_tax_rate_id = $res['id'] ??  0;
            app('log')->info(__FUNCTION__.':'.__LINE__.':invoice_tax_rate_id:'.$invoice_tax_rate_id);
            if(!$invoice_tax_rate_id){
                app('log')->info(__FUNCTION__.':'.__LINE__.':res:'.json_encode($res));
                throw new ResourceException('创建失败');
            }
            //更新items_category表的invoice_tax_rate_id
            app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));
            if(isset($data['category_ids']) && !empty($data['category_ids'])){
                // if( is_array($data['category_ids'])){
                //     $category_ids = $data['category_ids'];
                // }else{
                //     $category_ids = array($data['category_ids']);
                // }
                app('log')->info(__FUNCTION__.':'.__LINE__.':category_ids:'.json_encode($category_ids));
                app('log')->info(__FUNCTION__.':'.__LINE__.':invoice_tax_rate_id:'.$invoice_tax_rate_id);
                $updateData = [
                    'invoice_tax_rate_id' => $invoice_tax_rate_id,
                    'invoice_tax_rate' => $data['invoice_tax_rate'],
                ];
                $itemsCategoryService->updateInvoiceTaxRateId($category_ids, $updateData);
    
            }
            $conn->commit();
            return $res;
        }catch(\Exception $e){
            app('log')->info(__FUNCTION__.':'.__LINE__.':e:'.json_encode($e));
            $conn->rollBack();
            throw $e;
        }
    }

    public function updateTaxRate($id, $data)
    {
        //getItemsCategoryByInvoiceTaxRateId
        $itemsCategoryService = new ItemsCategoryService();
        if(isset($data['category_ids']) && is_array($data['category_ids'])){
            $itemsCategoryList = $itemsCategoryService->getItemsCategoryByInvoiceTaxRateId($data['category_ids'], $id);
        }else{
            $itemsCategoryList = [];
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':id:'.$id);
        app('log')->info(__FUNCTION__.':'.__LINE__.':itemsCategoryList:'.json_encode($itemsCategoryList));  
        if ($itemsCategoryList) {
            $category_name = implode(',', array_column($itemsCategoryList, 'category_name'));
            throw new ResourceException(trans('OrdersBundle/Order.category_tax_rate_exists', ['{0}' => $category_name]));
        }

        //tax_rate_type = "ALL" 时候检查。是不是已经存在
        if ($data['tax_rate_type'] === 'ALL') {
            $filter = [
                'tax_rate_type' => 'ALL',
            ];
            app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
            $existsAll = $this->repository->getInfo($filter);
            app('log')->info(__FUNCTION__.':'.__LINE__.':existsAll:'.json_encode($existsAll));
            // 如果存在，判断id是否相同
            if ($existsAll && $existsAll['id'] != $id) {
                throw new ResourceException('全部分类税率配置已存在');
            }
        }

        //更新items_category表的invoice_tax_rate_id

        $updateData = [
            'invoice_tax_rate_id' => $id,
            'invoice_tax_rate' => $data['invoice_tax_rate'],
        ];
        app('log')->info(__FUNCTION__.':'.__LINE__.':updateData:'.json_encode($updateData));
        // $this->validateTaxRateData($data, $id);
        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            // 先清空旧的分类税率
            $filter = [
                'invoice_tax_rate_id' => $id,
            ];
            $updateDataClear = [
                'invoice_tax_rate_id' => 0,
                'invoice_tax_rate' => 0,
            ];
            app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
            app('log')->info(__FUNCTION__.':'.__LINE__.':updateDataClear:'.json_encode($updateDataClear));
            $res = $itemsCategoryService->itemsCategoryRepository->updateByFilter($filter, $updateDataClear);
            app('log')->info(__FUNCTION__.':'.__LINE__.':res:'.json_encode($res));
            // 再更新新的分类税率
            $itemsCategoryService->updateInvoiceTaxRateId($data['category_ids'], $updateData);
            $data['category_ids'] = json_encode($data['category_ids'], JSON_UNESCAPED_UNICODE);
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));

        $res = $this->repository->updateOneBy(['id'=>$id], $data);
        app('log')->info(__FUNCTION__.':'.__LINE__.':res:'.json_encode($res));
        return $res;
    }

    public function deleteTaxRate($id)
    {
        // 事物开始
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try{
            $data = $this->repository->getInfoById($id);
            app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));
            // 不是ALL的时候，清空items_category表的invoice_tax_rate_id
            if($data['tax_rate_type'] != 'ALL'){
                // 清空items_category表的invoice_tax_rate_id
                $itemsCategoryService = new ItemsCategoryService();
                $category_ids = json_decode($data['category_ids'], true);
                app('log')->info(__FUNCTION__.':'.__LINE__.':category_ids:'.json_encode($category_ids));
                $itemsCategoryService->updateInvoiceTaxRateId($category_ids, ['invoice_tax_rate_id' => 0, 'invoice_tax_rate' => 0]);
            }
            $res = $this->repository->deleteById($id);
            app('log')->info(__FUNCTION__.':'.__LINE__.':res:'.json_encode($res));
            $conn->commit();
        }catch(\Exception $e){
            $conn->rollBack();
            throw $e;
        }
        return $res;
    }

    public function validateTaxRateData($data, $id = null)
    {
        foreach (['sales_party_id','tax_rate_type','invoice_tax_rate'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ResourceException(($this->allowFields[$field] ?? $field) . '不能为空');
            }
        }
        if ($data['tax_rate_type'] === 'SPECIFIED' && (empty($data['category_ids']) || count($data['category_ids']) === 0)) {
            throw new ResourceException(trans('OrdersBundle/Order.category_ids_cannot_be_empty'));
        }
        // 税率格式校验 不需要百分号
        if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $data['invoice_tax_rate'])) {
            throw new ResourceException(trans('OrdersBundle/Order.tax_rate_format_error'));
        }
        $rate = floatval(str_replace('%','',$data['invoice_tax_rate']));
        if ($rate < 0 || $rate > 100) {
            throw new ResourceException(trans('OrdersBundle/Order.tax_rate_range_error'));
        }
        // 唯一性校验
        $filter = [
            'sales_party_id' => $data['sales_party_id'],
            'tax_rate_type' => $data['tax_rate_type'],
        ];
        if ($data['tax_rate_type'] === 'SPECIFIED') {
            $filter['category_ids'] = json_encode($data['category_ids'], JSON_UNESCAPED_UNICODE);
        }
        if ($id) {
            $filter['id|ne'] = $id;
        }
        $exists = $this->repository->findOneBy($filter);
        if ($exists) {
            throw new ResourceException(trans('OrdersBundle/Order.category_tax_rate_already_exists'));
        }
    }
} 