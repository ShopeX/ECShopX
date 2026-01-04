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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use SalespersonBundle\Services\SalespersonTaskService;
use WechatBundle\Services\WeappService;

class Wxapp extends Controller
{
    /**
     * @SWG\Get(
     *     path="/ecx.wxapp.qrcode",
     *     summary="获取导购任务二维码",
     *     tags={"微信平台"},
     *     description="获取导购任务二维码",
     *     operationId="getWxCode",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.wxapp.qrcode" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="path_type", description="小程序页面" ),
     *     @SWG\Parameter( in="query", type="string", name="width", description="宽度" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="scene", description="链接参数，如：{“id”:1,”name”:”lu”}" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="base64Image", type="string", description="二维码base64编码数据"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getWxCode(Request $request)
    {
        // ShopEx EcShopX Core Module
        $params = $request->all();
        app('log')->debug('daogouapi - wxapp/wxcode - input : '.var_export([$params], 1));
        if ($params['scene'] ?? '') {
            $params['scene'] = json_decode($params['scene'], true);
        }
        app('log')->debug('daogouapi - wxapp/wxcode - scene : '.print_r($params['scene'], true));
        $rules = [
            'path_type' => ['required|in:index,goods_list,goods_detail,recommend_list,recommend_detail','参数错误'],
            'scene' => ['required', '参数错误'],
            'scene.id' => ['required_if:path_type,goods_detail,recommend_detail|integer|min:1','参数错误'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        $companyId = $request->get('auth')['company_id'];
        $salespersonTaskService = new SalespersonTaskService();
        $qrcode = $salespersonTaskService->getWxQrCode($companyId, $params);
        return Response($qrcode)->header('Content-type', 'image/png');
    }

    /**
     * @SWG\Get(
     *     path="/ecx.wxapp.shoplist",
     *     summary="获取微信店铺数据",
     *     tags={"微信平台"},
     *     description="获取导微信店铺数据",
     *     operationId="getWxShopLists",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.wxapp.qrcode" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="当前页，默认1" ),
     *     @SWG\Parameter( in="query", type="string", name="pageSize", description="分页条数，默认20" ),
     *     @SWG\Parameter( in="query", type="string", name="start_time", description="修改时间区间：开始时间" ),
     *     @SWG\Parameter( in="query", type="string", name="end_time", description="修改时间区间：结束时间" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="errcode", type="string", example="0"),
     *          @SWG\Property( property="errmsg", type="string", example="success"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="76"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="wxShopId", type="string", example="128"),
     *                          @SWG\Property( property="storeName", type="string", example="1234567"),
     *                          @SWG\Property( property="shopBn", type="string", example="6564"),
     *                          @SWG\Property( property="logo", type="string", example="null"),
     *                          @SWG\Property( property="contractPhone", type="string", example="13696344562"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00"),
     *                          @SWG\Property( property="lng", type="string", example="null"),
     *                          @SWG\Property( property="lat", type="string", example="null"),
     *                          @SWG\Property( property="address", type="string", example=""),
     *                          @SWG\Property( property="regions_id", type="string", example=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getWxShopLists(Request $request)
    {
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数不正确.', $validator->errors());
        }
        app('log')->debug('daogouapi - weshop/list - input : '.var_export([$input], 1));
        $distributorService = new DistributorService();
        $page = $input['page'] ?? 1;
        $limit = $input['pageSize'] ?? 20;
        $filter = [];
        if ($input['start_time'] ?? '') {
            $filter['updated|gt'] = strtotime($input['start_time']);
        }
        if ($input['end_time'] ?? '') {
            $filter['updated|lt'] = strtotime($input['end_time']);
        }
        $filter['company_id'] = $request->get('auth')['company_id'];

        $shop_lists = $distributorService->lists($filter, ["created" => "ASC"], $limit, $page);

        if ($shop_lists['list'] ?? '') {
            foreach ($shop_lists['list'] as &$shop_list) {
                $shopinfo = [
                    'wxShopId' => $shop_list['distributor_id'],
                    'storeName' => $shop_list['name'],
                    'shopBn' => $shop_list['shop_code'],
                    'logo' => $shop_list['logo'],
                    'contractPhone' => $shop_list['mobile'],
                    'hour' => $shop_list['hour'],
                    'lng' => $shop_list['lng'],
                    'lat' => $shop_list['lat'],
                    'address' => ($shop_list['province'] ?? '').($shop_list['city'] ?? '').($shop_list['area'] ?? '').($shop_list['address'] ?? ''),
                    // 'regions_id'    => $shop_list['regions_id'] ?? '',// 门店所属区域
                    'is_deleted' => ($shop_list['is_valid'] == 'true') ? '0' : '1'// 门店是否删除
                ];
                $shop_list = $shopinfo;
            }
        }
        $this->api_response('true', 'success', $shop_lists);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.weappid.get",
     *     summary="获取yykweishop模板的appid",
     *     tags={"微信平台"},
     *     description="获取yykweishop模板的appid",
     *     operationId="getWeappId",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.weappid.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="errcode", type="string", example="0"),
     *          @SWG\Property( property="errmsg", type="string", example="success"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="weappid", type="string", example="", description="授权的appid"),
     *                  @SWG\Property( property="template_name", type="string", example="", description="模板名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getWeappId(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $weappService = new WeappService();

        $return['weappid'] = $weappService->getWxappidByTemplateName($companyId, 'yykweishop');
        $return['template_name'] = 'yykweishop';
        $this->api_response('true', 'success', $return);
    }
}
