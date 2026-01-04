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

namespace OrdersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\InvoiceSellerService;

class InvoiceSeller extends Controller
{
    protected $sellerService;

    public function __construct()
    {
        $this->sellerService = new InvoiceSellerService();
    }

    /**
     * /order/invoice-seller/list
     * 获取销售方列表
     */
    public function getSellerList(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $filter = ['company_id' => $companyId];
        if ($request->has('seller_company_name')) {
            $filter['seller_company_name|like'] = $request->input('seller_company_name');
        }
        if ($request->has('seller_tax_no')) {
            $filter['seller_tax_no'] = $request->input('seller_tax_no');
        }

        $orderBy = ['id' => 'DESC'];
        $result = $this->sellerService->getSellerList($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * /order/invoice-seller/info/{id}
     * 获取销售方详情
     */
    public function getSellerDetail(Request $request, $id)
    {
        $detail = $this->sellerService->getSellerDetail($id);
        if (empty($detail)) {
            throw new ResourceException(trans('OrdersBundle/Order.seller_not_found'));
        }
        return $this->response->array($detail);
    }

    /**
     * /order/invoice-seller/create
     * 新增销售方
     */
    public function createSeller(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $data = $request->all();
        $data['company_id'] = $companyId;
        $result = $this->sellerService->createSeller($data);
        return $this->response->array($result);
    }

    /**
     * /order/invoice-seller/update/{id}
     * 修改销售方
     */
    public function updateSeller(Request $request, $id)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $data = $request->all();
        $result = $this->sellerService->updateSeller($id, $data);
        return $this->response->array($result);
    }
} 