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

namespace SalespersonBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use SalespersonBundle\Services\SalespersonService;

class ShopSalespersonController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/distributorlist",
     *     summary="获取导购店铺列表",
     *     tags={"导购"},
     *     description="获取导购店铺列表",
     *     operationId="getDistributorDataList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="store_type", in="query", description="店铺类型", required=true, type="string", default="distributor"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="门店id"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="store_type", type="string", example="distributor", description="店铺类型"),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="店铺地址"),
     *                          @SWG\Property( property="store_name", type="string", example="【店铺】视力康眼镜(中兴路店)", description="店铺名称"),
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺ID"),
     *                          @SWG\Property( property="shop_logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/...", description="店铺Logo图片地址"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getDistributorDataList(Request $request)
    {
        $authInfo = $this->auth->user();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 500);
        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter['company_id'] = $authInfo['company_id'];
        $dIds = [];
        if ($filter['company_id'] == $authInfo['company_id']) {
            $filter['salesperson_id'] = $authInfo['salesperson_id'];
            $filter['store_type'] = $request->get('store_type', 'distributor');
            // 根据店铺名称筛选
            if ($request->get('store_name', '') && trim($request->get('store_name'))) {
                $filter['store_name'] = trim($request->get('store_name'));
            }
            $listdata = $salespersonService->getSalespersonRelShopdata($filter, $page, $pageSize);
            // $dIds = array_column($listdata['list'],'distributor_id');
        }
        // $distributorService = new DistributorService();
        // $distributor = $distributorService->getDefaultDistributor($filter['company_id']);
        // if ($distributor && (!$dIds || !in_array($distributor['distributor_id'],$dIds))) {
        //     $listdata['list'][] = [
        //         'address' => $distributor['address'],
        //         'store_name' => $distributor['name'],
        //         'distributor_id' => $distributor['distributor_id'],
        //         'shop_logo' => $distributor['logo'],
        //         'hour' => $distributor['hour'],
        //     ];
        //     $listdata['total_count'] = 1;
        // }
        return $this->response->array($listdata);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/salesperson/distributor/is_valid",
     *     summary="验证导购员的店铺id是否有效",
     *     tags={"导购"},
     *     description="验证导购员的店铺id是否有效",
     *     operationId="checkDistributorIsValid",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="salesperson_id", in="query", required=true, description="导购id", type="number", ),
     *     @SWG\Parameter( name="distributor_id", in="query", required=true, description="店铺id", type="number", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true, description="校验结果"),
     *
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function checkDistributorIsValid(Request $request)
    {
        $postdata = $request->all('salesperson_id', 'distributor_id');
        $rules = [
            'salesperson_id' => ['required', '导购员id不能为空'],
            'distributor_id' => ['required', '店铺id不能为空'],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $authInfo = $this->auth->user();
        $salespersonService = new SalespersonService();
        $status = $salespersonService->checkDistributorIsValid($authInfo['company_id'], $postdata['salesperson_id'], $postdata['distributor_id']);
        return $this->response->array(['status' => $status]);
    }
}
