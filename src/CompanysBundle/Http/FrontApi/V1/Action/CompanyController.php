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
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use CompanysBundle\Services\SettingService;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\CompanyRelLogisticsServices;
use SystemLinkBundle\Services\MyCoach\H5Service;

class CompanyController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/company/setting",
     *     summary="获取商城配置信息",
     *     tags={"企业"},
     *     description="获取商城配置信息",
     *     operationId="getCompanySetting",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     type="object",
     *                     @SWG\property(property="logo", type="integer", example="1"),
     *                     @SWG\property(property="intro", type="integer", example="1"),
     *                     @SWG\property(property="brand_name", type="integer", example="rmb"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    public function getCompanySetting(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $authInfo = $request->get('auth');
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->getWxShopsSetting($authInfo['company_id']);

        $settingService = new SettingService();
        $setting = $settingService->getInfo(['company_id' => $authInfo['company_id']]);
        $result['customer_switch'] = $setting['customer_switch'] ?? 0;
        //增加店铺隔离参数
        $settingOpenDivided = (new SettingService())->getOpenDistributorDivided($authInfo['company_id']);
        $result['open_divided'] = $settingOpenDivided;
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/setting/weburl",
     *     summary="获取配置外部链接",
     *     tags={"企业"},
     *     description="获取配置外部链接",
     *     operationId="getWebUrlSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="mycoach", type="stirng"),
     *                     @SWG\Property(property="aftersales", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWebUrlSetting(Request $request)
    {
        $default = $request->all('mycoach', 'aftersales');
        $authInfo = $request->get('auth');
        $key = 'webUrlSetting:'. $authInfo['company_id'];
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : $default;
        // 链接加上加密后的mobile
        $mobile = $authInfo['mobile'] ?? '';
        if ($mobile) {
            $h5Service = new H5Service();
            $inputData = $h5Service->getEncryptionMobileUrl($mobile, $inputData);
        }
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/company/logistics/list",
     *     summary="获取启用物流公司列表",
     *     tags={"企业"},
     *     description="获取启用物流公司列表",
     *     operationId="getCompanyLogisticsList",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="corp_id", type="integer", example="1"),
     *                     @SWG\Property(property="corp_code", type="string", example="长宁店"),
     *                     @SWG\Property(property="corp_name", type="string", example="上海徐汇田林路"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="is_enable", type="integer", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function getCompanyLogisticsList(Request $request)
    {
        $inputData = $request->input();
        $filter = [];

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['distributor_id'] = $inputData['distributor_id'] ?? 0;
        $filter['supplier_id'] = $inputData['supplier_id'] ?? 0;

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsList = $companyRelLogisticsServices->getCompanyRelLogisticsList($filter);
        return $this->response->array($companyRelLogisticsList);
    }

    /**
     * @SWG\Get(
     *     path="/traderate/getstatus",
     *     summary="获取评价状态",
     *     tags={"企业"},
     *     description="获取评价状态",
     *     operationId="getRateSettingStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *           @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="rate_status", type="boolean", example="false")
     *           )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getRateSettingStatus(Request $request)
    {
        $authInfo = $request->get('auth');
        $key = 'TradeRateSetting:'. $authInfo['company_id'];
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['rate_status' => false];
        return $this->response->array($inputData);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/nostores/getstatus",
     *     summary="获取非自提无店铺开关状态",
     *     tags={"企业"},
     *     description="获取非自提无店铺开关状态",
     *     operationId="getNostoresStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
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
     *                     @SWG\Property(property="nostores_status", type="bool"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getNostoresStatus(Request $request)
    {
        $authInfo = $request->get('auth');
        $settingService = new SettingService();
        $result = $settingService->getNostoresSetting($authInfo['company_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/company/logistics/enableList",
     *     summary="获取启用物流公司列表",
     *     tags={"企业"},
     *     description="获取启用物流公司列表",
     *     operationId="getLogisticsEnableList",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="corp_code", type="string", example="SF"),
     *                     @SWG\Property(property="corp_name", type="string", example="顺丰快递"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function getLogisticsEnableList(Request $request)
    {
        $inputData = $request->input();
        $filter = [];

        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? -1;
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];

        $companyRelLogisticsServices = new CompanyRelLogisticsServices();
        $companyRelLogisticsList = $companyRelLogisticsServices->lists($filter, $page, $pageSize, [], 'corp_code, corp_name');

        //其他选项
        $other = [[
            "corp_code" => "OTHER",
            "corp_name" => "其他",
        ]];
        $data = array_merge($companyRelLogisticsList['list'], $other);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/setting/itemPrice",
     *     summary="获取小程序价格显示设置",
     *     tags={"企业"},
     *     description="获取小程序价格显示设置",
     *     operationId="getItemPriceSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data",
     *                 type="object",
     *                 @SWG\Property(property="cart_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                 ),
     *                 @SWG\Property(property="order_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                 ),
     *                 @SWG\Property(property="item_page",
     *                     type="object",
     *                     @SWG\Property(property="market_price", type="boolean", description="是否显示原价"),
     *                     @SWG\Property(property="member_price", type="boolean", description="是否显示会员等级价"),
     *                     @SWG\Property(property="svip_price", type="boolean", description="是否显示SVIP价"),
     *                 )
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getItemPriceSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $settingService = new SettingService();
        $result = $settingService->getItemPriceSetting($authInfo['company_id']);

        return $this->response->array($result);
    }
    

    ///wxapp/company/privacy_setting_ck
    public function getPrivacySetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        if (!$companyId) {
            throw new ResourceException("无相关企业信息！");
        }
        $result = (new SettingService())->getPrivacySetting($companyId);
        return $this->response->array($result);
    }
}
