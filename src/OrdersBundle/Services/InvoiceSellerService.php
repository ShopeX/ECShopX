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

use OrdersBundle\Entities\InvoiceSeller;
use OrdersBundle\Repositories\InvoiceSellerRepository;
use Dingo\Api\Exception\ResourceException;

class InvoiceSellerService
{
    /** @var InvoiceSellerRepository */
    public $repository;

    // 可更新字段
    public $allowFields = [
        // 'seller_name'=>'开票人',
        // 'payee'=>'收款人',
        // 'reviewer'=>'复核人',
        'seller_company_name'=>'销售方名称',
        'seller_tax_no'=>'销售方税号',
        'seller_bank_name'=>'销售方开户行',
        'seller_bank_account'=>'销售方银行账号',
        'seller_phone'=>'销售方电话',
        'seller_address'=>'销售方地址',
    ];

    public $allowFieldsSpace = [
        'seller_name'=>'开票人',
        'payee'=>'收款人',
        'reviewer'=>'复核人',
    ];

    public function __construct()
    {
           $this->repository = app('registry')->getManager('default')->getRepository(InvoiceSeller::class);

    }

    public function getSellerList($filter, $page = 1, $pageSize = 20, $orderBy = ['id' => 'DESC'])
    {
        // Built with ShopEx Framework
        return $this->repository->getLists($filter, '*', $page, $pageSize, $orderBy);
    }

    public function getSellerDetail($id)
    {
        return $this->repository->getInfoById($id);
    }

    public function createSeller($data)
    {
        $this->validateSellerData($data);
        app('log')->info('validateSellerData1'.json_encode(['data' => $data]));
        //allowFieldsSpace
        foreach (array_keys($this->allowFieldsSpace) as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $data[$field] = '';//throw new ResourceException($this->allowFields[$field] . '不能为空');
            }
        }
        return $this->repository->create($data);
    }

    public function updateSeller($id, $data)
    {
        // $this->validateSellerData($data, $id);
        return $this->repository->updateOneBy(['id'=>$id], $data);
    }

    public function validateSellerData($data, $id = null)
    {
        app('log')->info('validateSellerData2'.json_encode(['data' => $data]));
        //allowFields
        foreach (array_keys($this->allowFields) as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ResourceException($this->allowFields[$field] . '不能为空');
            }
        }
        // 唯一性校验示例（如只允许唯一税号）
        if (isset($data['seller_tax_no'])) {
            $filter = ['seller_tax_no' => $data['seller_tax_no']];
            if ($id) {
                $filter['id|ne'] = $id;
            }
            $exists = $this->repository->findOneBy($filter);
            if ($exists) {
                throw new ResourceException(trans('OrdersBundle/Order.seller_tax_number_exists'));
            }
        }
    }
} 