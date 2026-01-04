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

namespace CompanysBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\Shops\ProtocolService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use Swagger\Annotations as SWG;
use CompanysBundle\Services\CompanysService;
use DistributionBundle\Services\DistributorService;

class Shops extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/shops/wxshops/{wx_shop_id}",
     *     summary="获取单个微信门店详情",
     *     tags={"企业"},
     *     description="获取单个微信门店详情",
     *     operationId="getWxShopsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="path",
     *         description="微信门店id，非微信方的mp_poi_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_shop_id", type="string"),
     *                     @SWG\Property(property="mp_poi_id", type="string"),
     *                     @SWG\Property(property="pic_list", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="credential", type="string"),
     *                     @SWG\Property(property="company_name", type="string"),
     *                     @SWG\Property(property="qualification_list", type="string"),
     *                     @SWG\Property(property="card_id", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="is_domestic", type="integer"),
     *                     @SWG\Property(property="country", type="string"),
     *                     @SWG\Property(property="city", type="string"),
     *                     @SWG\Property(property="is_direct_store", type="integer")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxShopsDetail($wx_shop_id)
    {
        $validator = app('validator')->make(['wx_shop_id' => $wx_shop_id], [
            'wx_shop_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取门店详情出错.', $validator->errors());
        }
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->getShopsDetail($wx_shop_id);
        if ($result['company_id']) {
            $result['base_setting'] = $shopsService->getWxShopsSetting($result['company_id']);
        } else {
            $result['base_setting'] = [
                'logo' => '',
                'intro' => '',
            ];
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/shops/wxshops",
     *     summary="获取微信门店列表",
     *     tags={"企业"},
     *     description="获取微信门店列表",
     *     operationId="getWxShopsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="lng",
     *         in="query",
     *         description="当前位置经度",
     *         type="number",
     *     ),
     *     @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         description="当前位置纬度",
     *         type="number",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="query",
     *         description="门店id",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_shop_id", type="string"),
     *                     @SWG\Property(property="mp_poi_id", type="string"),
     *                     @SWG\Property(property="pic_list", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="credential", type="string"),
     *                     @SWG\Property(property="company_name", type="string"),
     *                     @SWG\Property(property="qualification_list", type="string"),
     *                     @SWG\Property(property="card_id", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="is_domestic", type="integer"),
     *                     @SWG\Property(property="country", type="string"),
     *                     @SWG\Property(property="city", type="string"),
     *                     @SWG\Property(property="is_direct_store", type="integer")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxShopsList(request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取微信门店列表出错.', $validator->errors());
        }

        $filter = $request->input();
        $shopsService = new ShopsService(new WxShopsService());
        $page = $filter['page'];
        $pageSize = 500;//$filter['pageSize'];

        $authInfo = $request->get('auth');
        $params = [
            'company_id' => $authInfo['company_id'],
            'expired_at|gt' => time(),
            'is_open' => 1,
        ];

        $params['distributor_id'] = $request->input('distributor_id', 0);
        $result = $shopsService->getShopsList($params, $page, $pageSize);

        if (isset($filter['lat'], $filter['lng']) && $filter['lng'] && $filter['lat']) {
            $current_lat = $filter['lat'];
            $current_lng = $filter['lng'];

            foreach ($result['list'] as &$v) {
                $v['distance'] = round($this->distance($current_lat, $current_lng, $v['lat'], $v['lng']));

                if ($v['distance'] > 1000) {
                    $v['distance_show'] = round($v['distance'] / 1000, 1);
                    $v['distance_unit'] = 'km';
                } else {
                    $v['distance_show'] = $v['distance'];
                    $v['distance_unit'] = 'm';
                }
            }
            usort($result['list'], function ($a, $b) {
                if ($a['distance'] == $b['distance']) {
                    return 0;
                }
                return ($a['distance'] < $b['distance']) ? -1 : 1;
            });
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/shops/getNearestWxShops",
     *     summary="获取最近门店",
     *     tags={"企业"},
     *     description="获取最近门店",
     *     operationId="getNearestWxShops",
     *     @SWG\Parameter(
     *         name="lng",
     *         in="query",
     *         description="当前位置经度",
     *         type="number",
     *     ),
     *     @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         description="当前位置纬度",
     *         type="number",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="wx_shop_id", type="string"),
     *                     @SWG\Property(property="mp_poi_id", type="string"),
     *                     @SWG\Property(property="pic_list", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="credential", type="string"),
     *                     @SWG\Property(property="company_name", type="string"),
     *                     @SWG\Property(property="qualification_list", type="string"),
     *                     @SWG\Property(property="card_id", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getNearestWxShops(request $request)
    {
        // ID: 53686f704578
        $validator = app('validator')->make($request->all(), [
            'lng' => 'sometimes|required|between:-180.0,180.0',
            'lat' => 'sometimes|required|between:-90.0,90.0',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('经纬度范围错误.', $validator->errors());
        }

        $filter = $request->input();

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['expired_at|gt'] = time();
        $shopsService = new ShopsService(new WxShopsService());
        if (!$filter['lat'] && !$filter['lng']) {
            $defaultShop = $shopsService->getDefaultShop($params['company_id']);
            if (!$defaultShop['list']) {
                $defaultShop = $shopsService->getShopsList($params, 1, 1);
            }
            return $defaultShop['list'];
        }
        $shopsList = $shopsService->getShopsList($params, 1, 500);
        $min = [];
        $result = [];
        if ($shopsList['list']) {
            foreach ($shopsList['list'] as $k => &$v) {
                $distance = $this->distance($filter['lat'], $filter['lng'], $v['lat'], $v['lng']);
                $v['distance'] = round($distance);
                $min[$k] = $v['distance'];

                if ($v['distance'] > 1000) {
                    $v['distance_show'] = round($v['distance'] / 1000, 1);
                    $v['distance_unit'] = 'km';
                } else {
                    $v['distance_show'] = $v['distance'];
                    $v['distance_unit'] = 'm';
                }
            }
            $min_key = array_search(min($min), $min);
            $result = $shopsList['list'][$min_key];
        }
        return $this->response->array($result);
    }

    /**
     * 根据经纬度计算距离
     *
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @return string
     */
    private function distance($lat1, $lng1, $lat2, $lng2)
    {
        // ID: 53686f704578
        if (!is_numeric($lat1) || !is_numeric($lng1)) {
            return 0;
        }
        if (!$lat1 || !$lng1) {
            return 0;
        }
        $dx = $lng1 - $lng2; // 经度差值
        $dy = $lat1 - $lat2; // 纬度差值
        $b = ($lat1 + $lat2) / 2.0; // 平均纬度
        $Lx = deg2rad($dx) * 6367000.0 * cos(deg2rad($b)); // 东西距离
        $Ly = 6367000.0 * deg2rad($dy); // 南北距离
        return sqrt($Lx * $Lx + $Ly * $Ly);  // 用平面的矩形对角距离公式计算总距离
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/shops/info",
     *     tags={"企业"},
     *     summary="商城站点信息 - 获取",
     *     description="获取商场站点的基本信息",
     *     operationId="getBaseInfo",
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"intro", "logo", "brand_name","protocol"},
     *               @SWG\Property(property="intro", type="string", example="12312312311", description="商城简介"),
     *               @SWG\Property(property="logo", type="string", example="https://bbctest.aixue7.com/image/1/2021/03/03/66f45604a617b6a5fdf909c62589edd96MjhykV8gN3EnquFLAmDkdzU73rmaSYG", description="商城logo"),
     *               @SWG\Property(property="brand_name", type="string", example="总部1", description="商城名称"),
     *               @SWG\Property(property="protocol", type="object", description="协议内容",required={"member_register","privacy"},
     *                    @SWG\Property(property="member_register", type="string", example="用户注册协议的标题", description="用户注册协议的标题"),
     *                    @SWG\Property(property="privacy", type="string", example="隐私政策协议的标题", description="隐私政策协议的标题"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getBaseInfo(Request $request)
    {
        // middleware中添加的参数获取方式
        $authInfo = $request->get('auth');
        // 公司id
        $companyId = (int)$authInfo['company_id'];

        $service = new WxShopsService();
        $data = $service->getWxShopsSetting($companyId);
        $protocolData = (new ProtocolService($companyId))->get([ProtocolService::TYPE_MEMBER_REGISTER, ProtocolService::TYPE_PRIVACY]);
        $data["protocol"] = [
            ProtocolService::TYPE_MEMBER_REGISTER => (string)($protocolData[ProtocolService::TYPE_MEMBER_REGISTER]["title"] ?? ""),
            ProtocolService::TYPE_PRIVACY => (string)($protocolData[ProtocolService::TYPE_PRIVACY]["title"] ?? ""),
        ];
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/shops/setting",
     *     summary="获取门店通用配置信息",
     *     tags={"企业"},
     *     description="获取门店通用配置信息",
     *     operationId="getWxShopsSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="brand_name", type="string", description="品牌名称"),
     *                 @SWG\Property(property="logo", type="string", description="门店通用logo"),
     *                 @SWG\Property(property="intro", type="string", description="门店通用简介"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxShopsSetting(Request $request)
    {
        $domain = $request->get('domain', '');
        $companyId = config('common.system_main_companys_id');
        if ( !empty($domain) ) {
            $companysService = new CompanysService();
            $companyInfo = $companysService->getCompanyInfoByDomain($domain);
            if ( $companyInfo['company_id'] ?? '' ) {
                $companyId = $companyInfo['company_id'] ?? 0;
            }
        }
        if ( !$companyId ) {// 获取默认品牌ID
            return $this->response->array([]);
        }
        // 获取自营店信息
        $shopsService = new ShopsService(new WxShopsService());
        $data = $shopsService->getWxShopsSetting($companyId);
        // 获取总店信息
        $selfDistributorInfo = (new DistributorService())->getDistributorSelf($companyId, true);
        if (!empty($selfDistributorInfo) && $data) {
            $data["brand_name"] = $selfDistributorInfo["name"] ?? "";
            $data["logo"] = $selfDistributorInfo["logo"] ?? "";
        }
        return $this->response->array($data);
    }
}
