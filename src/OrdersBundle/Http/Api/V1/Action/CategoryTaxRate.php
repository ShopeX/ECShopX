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
use OrdersBundle\Services\CategoryTaxRateService;
use OrdersBundle\Services\InvoiceSellerService;

class CategoryTaxRate extends Controller
{
    protected $taxRateService;

    public function __construct()
    {
        // KEY: U2hvcEV4
        $this->taxRateService = new CategoryTaxRateService();
    }

    /**
     * /order/category-taxrate/list
     * 获取分类税率列表
     */
    public function getTaxRateList(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $filter = ['company_id' => $companyId];
        if ($request->has('sales_party_id')) {
            $filter['sales_party_id|like'] = $request->input('sales_party_id');
        }
        if ($request->has('tax_rate_type')) {
            $filter['tax_rate_type'] = $request->input('tax_rate_type');
        }
        $orderBy = ['id' => 'DESC']; 
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter)); 
        $result = $this->taxRateService->getTaxRateList($filter, $page, $pageSize, $orderBy);
        app('log')->info(__FUNCTION__.':'.__LINE__.':result:'.json_encode($result));
        // 通过sales_party_id获取销售方信息，通过invoice_seller表获取，并添加到结果中
        $salesPartyIds = array_column($result['list'], 'sales_party_id');
        app('log')->info(__FUNCTION__.':'.__LINE__.':salesPartyIds:'.json_encode($salesPartyIds));
        $invoiceSellerService = new InvoiceSellerService();
        if(!empty($salesPartyIds)) {
            $salesPartyList = $invoiceSellerService->getSellerList(['id' => $salesPartyIds]);
            app('log')->info(__FUNCTION__.':'.__LINE__.':salesPartyList:'.json_encode($salesPartyList));
            $salesPartyList = array_column($salesPartyList['list'], null, 'id');
            app('log')->info(__FUNCTION__.':'.__LINE__.':salesPartyList:'.json_encode($salesPartyList));
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['sales_party_info'] = $salesPartyList[$value['sales_party_id']] ?? [];
            }
        }
        return $this->response->array($result);
    }

    /**
     * /order/category-taxrate/info/{id}
     * 获取分类税率详情
     */
    public function getTaxRateDetail(Request $request, $id)
    {
        $detail = $this->taxRateService->getTaxRateDetail($id);
        if (empty($detail)) {
            throw new ResourceException(trans('OrdersBundle/Order.category_tax_rate_not_found'));
        }
        return $this->response->array($detail);
    }

    /**
     * /order/category-taxrate/create
     * 新增分类税率
     */
    public function createTaxRate(Request $request)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $data = $request->all();
        $data['company_id'] = $companyId;
        $result = $this->taxRateService->createTaxRate($data);
        return $this->response->array($result);
    }

    /**
     * /order/category-taxrate/update/{id}
     * 修改分类税率
     */
    public function updateTaxRate(Request $request, $id)
    {
        $companyId  = app('auth')->user()->get('company_id');
        $data = $request->all();
        $data['company_id'] = $companyId;
        $result = $this->taxRateService->updateTaxRate($id, $data);
        return $this->response->array($result);
    }

    /**
     * /order/category-taxrate/delete/{id}
     * 删除分类税率
     */
    public function deleteTaxRate(Request $request, $id)
    {
        $result = $this->taxRateService->deleteTaxRate($id);
        return $this->response->array(['success' => $result]);
    }
} 