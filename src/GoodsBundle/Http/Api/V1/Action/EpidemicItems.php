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

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use EspierBundle\Jobs\ExportFileJob;
use OrdersBundle\Services\OrderEpidemicService;

class EpidemicItems extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/goods/epidemicItems/list",
     *     summary="疫情商品配置列表",
     *     tags={"商品"},
     *     description="疫情商品配置列表",
     *     operationId="epidemicItemsList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="店铺id  不传默认取全部" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页码  不传默认1" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="页数  不传默认10" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="5373", description="item_id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="store", type="string", example="100", description="库存"),
     *                          @SWG\Property( property="item_bn", type="string", example="S5FDB03E490C8E", description="编码"),
     *                          @SWG\Property( property="barcode", type="string", example="123456", description="条码"),
     *                          @SWG\Property( property="item_name", type="string", example="多规格测试", description="商品名称"),
     *                          @SWG\Property( property="nospec", type="string", example="false", description="商品是否为单规格"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function epidemicItemsList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->input('distributor_id', -1);
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $filter = [
            'company_id' => $companyId,
            'is_epidemic' => 1,
        ];
        if ($distributorId > -1) {
            $filter['distributor_id'] = $distributorId;
        }
        $columns = ['item_id','company_id','store','item_bn','barcode','item_name'];
        $itemsService = new ItemsService();
        $result = $itemsService->list($filter, [], $pageSize, $page, $columns);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/goods/epidemicRegister/list",
     *     summary="疫情订单登记列表",
     *     tags={"商品"},
     *     description="疫情订单登记列表",
     *     operationId="epidemicRegisterList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="店铺id  不传默认取全部" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页码  不传默认1" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="页数  不传默认10" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="order_time_start", description="开始时间" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="order_time_end", description="结束时间" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="id"),
     *                          @SWG\Property( property="order_id", type="string", example="1", description="订单ID"),
     *                          @SWG\Property( property="user_id", type="string", example="1", description="用户ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="distributor_id", type="string", example="1", description="店铺ID"),
     *                          @SWG\Property( property="name", type="string", example="1", description="登记姓名"),
     *                          @SWG\Property( property="mobile", type="string", example="1", description="登记手机号"),
     *                          @SWG\Property( property="cert_id", type="string", example="1", description="身份证号"),
     *                          @SWG\Property( property="temperature", type="string", example="1", description="温度"),
     *                          @SWG\Property( property="job", type="string", example="1", description="职业"),
     *                          @SWG\Property( property="symptom", type="string", example="1", description="症状"),
     *                          @SWG\Property( property="symptom_des", type="string", example="1", description="症状描述"),
     *                          @SWG\Property( property="is_risk_area", type="string", example="1", description="是否去过中高风险地区"),
     *                          @SWG\Property( property="order_time", type="string", example="1", description="下单时间"),
     *                          @SWG\Property( property="created", type="string", example="1", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="null", description="修改时间"),
     *                          @SWG\Property( property="distributor_name", type="string", example="普天信息产业园测试1", description="店铺名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function epidemicRegisterList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $orderTimeStart = $request->input('order_time_start', '');
        $orderTimeEnd = $request->input('order_time_end', '');
        $filter = [
            'company_id' => $companyId,
        ];

        $distributorId = $request->get('distributor_id');
        $operator_type = app('auth')->user()->get('operator_type');
        if ($operator_type == 'staff') {
            if (!is_null($distributorId)) {
                $filter['distributor_id'] = $distributorId;
            } else {
                $distributorIds = app('auth')->user()->get('distributor_ids');
                if ($distributorIds) {
                    $distributorIds = array_column($distributorIds, 'distributor_id');
                    $filter['distributor_id'] = $distributorIds;
                }
            }
        } else {
            if (!is_null($distributorId)) {
                $filter['distributor_id'] = $distributorId;
            }
        }

        if ($orderTimeStart && $orderTimeEnd) {
            $filter['order_time|gte'] = $orderTimeStart;
            $filter['order_time|lte'] = $orderTimeEnd;
        }
        $cols = ['id', 'order_id', 'user_id', 'company_id', 'distributor_id', 'name', 'mobile', 'created'];
        $orderEpidemicService = new OrderEpidemicService();
        $result = $orderEpidemicService->epidemicRegisterListService($filter, $cols, $page, $pageSize, ['created' => 'DESC']);
        $datapassBlock = $request->get('x-datapass-block');

        if ($datapassBlock) {
            foreach ($result['list'] as &$value) {
                $value['name'] = data_masking('truename', $value['name']);
                $value['mobile'] = data_masking('mobile', $value['mobile']);
            }
        }

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/goods/epidemicRegister/export",
     *     summary="疫情防控登记导出",
     *     tags={"商品"},
     *     description="疫情防控登记导出",
     *     operationId="exportEpidemicRegisterData",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="店铺id  不传默认取全部" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="order_time_start", description="开始时间" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="order_time_end", description="结束时间" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     * )
     */
    public function exportEpidemicRegisterData(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $orderTimeStart = $request->input('order_time_start', '');
        $orderTimeEnd = $request->input('order_time_end', '');
        $filter = [
            'company_id' => $companyId,
        ];

        $distributorId = $request->get('distributor_id');
        $operator_type = app('auth')->user()->get('operator_type');
        if ($operator_type == 'staff') {
            if (!is_null($distributorId)) {
                $filter['distributor_id'] = $distributorId;
            } else {
                $distributorIds = app('auth')->user()->get('distributor_ids');
                if ($distributorIds) {
                    $distributorIds = array_column($distributorIds, 'distributor_id');
                    $filter['distributor_id'] = $distributorIds;
                }
            }
        } else {
            if (!is_null($distributorId)) {
                $filter['distributor_id'] = $distributorId;
            }
        }

        if ($orderTimeStart && $orderTimeEnd) {
            $filter['order_time|gte'] = $orderTimeStart;
            $filter['order_time|lte'] = $orderTimeEnd;
        }
        $operator_id = app('auth')->user()->get('operator_id');
        $type = 'epidemic_register';
        $orderEpidemicService = new OrderEpidemicService();
        $count = $orderEpidemicService->count($filter);

        if ($count <= 0) {
            throw new Resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new Resourceexception("导出有误，当前导出数据为 $count 条，最高导出 15000 条数据");
        }
        $filter['datapass_block'] = $request->get('x-datapass-block');
        $gotoJob = (new ExportFileJob($type, $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return response()->json(['status' => true]);
    }
}
