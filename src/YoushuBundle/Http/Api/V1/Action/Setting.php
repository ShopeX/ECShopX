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

namespace YoushuBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use YoushuBundle\Services\YoushuService;
use Swagger\Annotations as SWG;

class Setting extends Controller
{
    /**
     * @SWG\Post(
     *     path="/dataAnalysis/youshu/setting",
     *     summary="腾讯有数参数配置信息保存",
     *     tags={"腾讯有数"},
     *     description="腾讯有数参数配置信息保存",
     *     operationId="youshuSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="merchant_id", in="query", description="商户号", required=true, type="string"),
     *     @SWG\Parameter( name="app_id", in="query", description="有数app id，正式", required=true, type="string"),
     *     @SWG\Parameter( name="app_secret", in="query", description="有数app secret，正式", required=true, type="string"),
     *     @SWG\Parameter( name="api_url", in="query", description="有数后端API URL，正式", required=true, type="string"),
     *     @SWG\Parameter( name="sandbox_app_id", in="query", description="有数app id，沙箱", required=true, type="string"),
     *     @SWG\Parameter( name="sandbox_app_secret", in="query", description="有数app secret，沙箱", required=true, type="string"),
     *     @SWG\Parameter( name="sandbox_api_url", in="query", description="有数后端API URL，沙箱", required=true, type="string"),
     *     @SWG\Parameter( name="weapp_name", in="query", description="小程序名称", required=true, type="string"),
     *     @SWG\Parameter( name="weapp_app_id", in="query", description="小程序app id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object", required={"status"},
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function save(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $id = $request->input('id', '');
        $company_id = app('auth')->user()->get('company_id');
        $merchant_id = $request->input('merchant_id');
        $app_id = $request->input('app_id');
        $app_secret = $request->input('app_secret');
        $api_url = $request->input("api_url", ""); // 后端API URL正式
        $sandbox_app_id = $request->input('sandbox_app_id');
        $sandbox_app_secret = $request->input('sandbox_app_secret');
        $sandbox_api_url = $request->input('sandbox_api_url', "");// 后端API URL沙河
        $weapp_name = $request->input('weapp_name');
        $weapp_app_id = $request->input('weapp_app_id');

        $params = [
            'id' => $id,
            'company_id' => $company_id,
            'merchant_id' => trim($merchant_id),
            'app_id' => trim($app_id),
            'app_secret' => trim($app_secret),
            'api_url' => trim($api_url),
            'sandbox_app_id' => trim($sandbox_app_id),
            'sandbox_app_secret' => trim($sandbox_app_secret),
            'sandbox_api_url' => trim($sandbox_api_url),
            'weapp_name' => trim($weapp_name),
            'weapp_app_id' => trim($weapp_app_id),
        ];

        $service = new YoushuService();
        $result = $service->saveData($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/dataAnalysis/youshu/query",
     *     summary="获取有数设置信息",
     *     tags={"腾讯有数"},
     *     description="获取设置信息",
     *     operationId="getPreAuthUrl",
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
     *         @SWG\Schema(required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",required={"id","company_id","merchant_id","app_id","app_secret","api_url","sandbox_app_id","sandbox_app_secret","sandbox_api_url","weapp_name","weapp_app_id"},
     *                     @SWG\Property(property="id", type="integer", description="主键id"),
     *                     @SWG\Property(property="company_id", type="integer", description="企业id"),
     *                     @SWG\Property(property="merchant_id", type="string", description="商家id"),
     *                     @SWG\Property(property="app_id", type="string", description="正式环境的APP ID"),
     *                     @SWG\Property(property="app_secret", type="string", description="正式环境的APP Secret"),
     *                     @SWG\Property(property="api_url", type="string", description="正式环境的后端API URL"),
     *                     @SWG\Property(property="sandbox_app_id", type="string", description="沙盒环境的APP ID"),
     *                     @SWG\Property(property="sandbox_app_secret", type="string", description="沙盒环境的APP Secret"),
     *                     @SWG\Property(property="sandbox_api_url", type="string", description="沙盒环境的后端API URL"),
     *                     @SWG\Property(property="weapp_name", type="string", description="小程序名称"),
     *                     @SWG\Property(property="weapp_app_id", type="string", description="小程序AppId"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function query()
    {
        $company_id = app('auth')->user()->get('company_id');
        $params['company_id'] = $company_id;

        $service = new YoushuService();
        $result = $service->getInfo($params);
        if (empty($result)) {
            return $this->response->noContent();
        } else {
            return $this->response->array($result);
        }
    }
}
