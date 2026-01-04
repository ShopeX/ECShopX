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

namespace DistributionBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use DistributionBundle\Services\DistributionService;
use Illuminate\Http\Request;

class DistributionConfig extends Controller
{
    /**
     * @SWG\Get(
     *     path="/distribution/config",
     *     summary="获取分润配置",
     *     tags={"店铺"},
     *     description="获取分润配置",
     *     operationId="getConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
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
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getConfig()
    {
        $distributionService = new DistributionService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $distributionService->getDistributionConfig($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/distribution/config",
     *     summary="保存分润配置",
     *     tags={"店铺"},
     *     description="保存分润配置",
     *     operationId="setConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
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
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function setConfig(Request $request)
    {
        $params = $request->all(
            'distributor.show',
            'distributor.distributor',
            'distributor.seller',
            'distributor.popularize_seller',
            'distributor.distributor_seller',
            'distributor.plan_limit_time'
        );
        $distributionService = new DistributionService();

        $companyId = app('auth')->user()->get('company_id');
        $result = $distributionService->setDistributionConfig($companyId, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/distribution/config/inRule",
     *     summary="保存店铺进店规则",
     *     tags={"店铺"},
     *     description="保存店铺进店规则",
     *     operationId="saveInRule",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="distributor_code", description="店铺码进店" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="shop_assistant", description="导购物料进店" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="shop_assistant_pro", description="专属导购所属店" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="shop_lbs", description="lbs附近店" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="radio_type", description="兜底策略 1 默认点 2介绍面" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="default_shop", description="默认店" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="intro_page", description="介绍页面" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *             @SWG\Items( type="object",
     *                  @SWG\Property( property="distributor_code", type="string", example="2", description="店铺码进店"),
     *                  @SWG\Property( property="shop_assistant", type="string", example="2", description="导购物料进店"),
     *                  @SWG\Property( property="shop_assistant_pro", type="string", example="2", description="专属导购所属店"),
     *                  @SWG\Property( property="shop_lbs", type="string", example="2", description="lbs附近店"),
     *                  @SWG\Property( property="radio_type", type="string", example="2", description="兜底策略 1 默认点 2介绍面"),
     *                  @SWG\Property( property="default_shop", type="string", example="2", description="默认店"),
     *                  @SWG\Property( property="intro_page", type="string", example="2", description="介绍页面"),
     *            ),       
     *          )),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function saveInRule(Request $request)
    {
        $params = $request->all();
        $paramsData = [
            'distributor_code' => [
                'status' => ($params['distributor_code']['status'] === true || $params['distributor_code']['status'] === 'true') ? true : false, // 店铺码进店
                'sort' => $params['distributor_code']['sort'] ?? 1,
            ],
            'shop_assistant' => [
                'status' => ($params['shop_assistant']['status'] === true || $params['shop_assistant']['status'] === 'true') ? true : false, // 导购物料进店
                'express_time' => $params['shop_assistant']['express_time'] ?? 0, // 导购物料进店
                'sort' => $params['shop_assistant']['sort'] ?? 2,
            ], 
            'shop_white' => [
                'status' => ($params['shop_white']['status'] === true || $params['shop_white']['status'] === 'true') ? true : false, // 进入白名会员店,
                'sort' => $params['shop_white']['sort'] ?? 3,
            ],
            'shop_assistant_pro' => [
                'status' => ($params['shop_assistant_pro']['status'] === true || $params['shop_assistant_pro']['status'] === 'true') ? true : false,  // 专属导购所属店
                'sort' => $params['shop_assistant_pro']['sort'] ?? 4,
            ],
            'shop_lbs' => ($params['shop_lbs'] === true || $params['shop_lbs'] === 'true') ? true : false, // lbs附近店
            'radio_type' => (int)$params['radio_type'] ?? 1, // 兜底策略 1 默认点 2介绍面
            'default_shop' => $params['default_shop'] ?? 0, // 默认店
            'intro_page' => $params['intro_page'] ?? '', // 2 介绍页面
        ];
        $distributionService = new DistributionService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $distributionService->setDistributionConfigInRule($companyId, $paramsData);

        return $this->response->array($result);
    }
    
    /**
     * @SWG\Get(
     *     path="/distributor/config/inRule",
     *     summary="获取店铺进店规则",
     *     tags={"店铺"},
     *     description="获取店铺进店规则",
     *     operationId="getInRule",
     *     @SWG\Parameter( in="query", type="string", required=true, name="type", description="验证码类型 login 登录验证码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *             @SWG\Items( type="object",
     *                  @SWG\Property( property="distributor_code", type="string", example="2", description="店铺码进店"),
     *                  @SWG\Property( property="shop_assistant", type="string", example="2", description="导购物料进店"),
     *                  @SWG\Property( property="shop_assistant_pro", type="string", example="2", description="专属导购所属店"),
     *                  @SWG\Property( property="shop_lbs", type="string", example="2", description="lbs附近店"),
     *                  @SWG\Property( property="radio_type", type="string", example="2", description="兜底策略 1 默认点 2介绍面"),
     *                  @SWG\Property( property="default_shop", type="string", example="2", description="默认店"),
     *                  @SWG\Property( property="intro_page", type="string", example="2", description="介绍页面"),
     *            ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones")))
     * )
     */
    public function getInRule()
    {
        $distributionService = new DistributionService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $distributionService->getDistributionConfigInRule($companyId);

        return $this->response->array($result ?? []);
    }

}
