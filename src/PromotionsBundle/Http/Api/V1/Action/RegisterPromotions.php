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

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Services\RegisterPromotionsService;

// use Dingo\Api\Exception\UpdateResourceFailedException;
// use Dingo\Api\Exception\DeleteResourceFailedException;
// use Dingo\Api\Exception\StoreResourceFailedException;

class RegisterPromotions extends Controller
{
    /**
     * @SWG\Post(
     *     path="/promotions/register",
     *     summary="注册引导营销配置",
     *     tags={"营销"},
     *     description="注册引导营销配置",
     *     operationId="saveRegisterPromotionsConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="id", type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启", required=true, type="string"),
     *     @SWG\Parameter( name="ad_title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="ad_pic", in="query", description="广告图片URL", required=true, type="string"),
     *     @SWG\Parameter( name="register_jump_path", in="query", description="注册引导跳转路径 object", required=false, type="string"),
     *     @SWG\Parameter( name="promotions_value", in="query", description="促销方案", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function saveRegisterPromotionsConfig(Request $request)
    {
        $registerPromotionsService = new RegisterPromotionsService();

        $companyId = app('auth')->user()->get('company_id');
        $data = [
            'id' => $request->input('id', 0),
            'is_open' => $request->input('is_open', 'false'),
            'ad_title' => $request->input('ad_title', ''),
            'ad_pic' => $request->input('ad_pic', ''),
            'promotions_value' => $request->input('promotions_value', ''),
            'register_type' => $request->input('register_type', ''),
            'register_jump_path' => $request->input('register_jump_path', []),
        ];

        $registerPromotionsService->saveRegisterPromotionsConfig($companyId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/register",
     *     summary="获取注册引导营销配置",
     *     tags={"营销"},
     *     description="获取注册引导营销配置",
     *     operationId="getRegisterPromotionsConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="register_type", in="query", description="类型 general:基础注册促销 membercard:赠送（付费会员卡）", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="2", description="注册促销活动ID"),
     *                  @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                  @SWG\Property( property="register_type", type="string", example="general", description="促销类型。可选值有 general-普通;membercard-赠送（付费会员卡）"),
     *                  @SWG\Property( property="ad_title", type="string", example="注册享大礼", description="注册引导广告标题"),
     *                  @SWG\Property( property="ad_pic", type="string", example="http://bbctest.aixue7.com/1/2019/12/12/75d22c27eece5bc99c289c9855289a88VtpE4lLXqgL8JpwprRUMescPgOQMGBTp", description="注册引导图片"),
     *                  @SWG\Property( property="register_jump_path", type="string", example="", description="注册引导跳转路径"),
     *                  @SWG\Property( property="promotions_value", type="object",
     *                          @SWG\Property( property="items", type="array",
     *                              @SWG\Items( type="string", example="901", description="服务类商品id"),
     *                          ),
     *                          @SWG\Property( property="itemsList", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="key", type="string", example="5186", description="商品id"),
     *                                  @SWG\Property( property="label", type="string", example="测试001", description="商品名称"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="coupons", type="array", description="注册送优惠券数据",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="card_id", type="string", example="315", description="赠送优惠券id"),
     *                                  @SWG\Property( property="count", type="string", example="1", description="数量"),
     *                                  @SWG\Property( property="title", type="string", example="新", description="卡券名"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="staff_coupons", type="array",description="员工激活礼送优惠券数据",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="card_id", type="string", example="590", description="赠送优惠券id"),
     *                                  @SWG\Property( property="count", type="string", example="1", description="数量"),
     *                                  @SWG\Property( property="title", type="string", example="代金10元", description="卡券名"),
     *                               ),
     *                          ),
     *                  ),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getRegisterPromotionsConfig(Request $request)
    {
        $registerPromotionsService = new RegisterPromotionsService();

        $companyId = app('auth')->user()->get('company_id');
        $registerType = $request->get('register_type');

        $data = $registerPromotionsService->getRegisterPromotionsConfig($companyId, $registerType);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/point",
     *     summary="获取注册积分配置",
     *     tags={"营销"},
     *     description="获取注册积分配置",
     *     operationId="getRegisterPointConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="类型 point:注册送积分配置", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                  @SWG\Property( property="type", type="string", example="point", description="类型 point:注册送积分配置"),
     *                  @SWG\Property( property="point", type="string", example="0", description="积分个数"),
     *                  @SWG\Property( property="rebate", type="string", example="0", description="注册上级积分"),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getRegisterPointConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->all('type');
        $registerPromotionsService = new RegisterPromotionsService();
        $result = $registerPromotionsService->getRegisterPointConfig($companyId, $data['type']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/point",
     *     summary="注册积分配置",
     *     tags={"营销"},
     *     description="注册积分配置",
     *     operationId="getRegisterPointConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="formData", description="类型 point:注册送积分配置", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="formData", description="是否开启", required=true, type="string"),
     *     @SWG\Parameter( name="point", in="formData", description="积分数", required=true, type="string"),
     *     @SWG\Parameter( name="rebate", in="formData", description="注册上级积分", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function saveRegisterPointConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->all('is_open', 'type', 'point', 'rebate');
        $rules = [
            'point' => ['required|integer', trans('PromotionsBundle.register_point_must_integer')],
            'rebate' => ['required|integer', trans('PromotionsBundle.register_rebate_must_integer')],
        ];

        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $registerPromotionsService = new RegisterPromotionsService();
        $registerPromotionsService->saveRegisterPointConfig($companyId, $data['type'], $data);
        return $this->response->array(['status' => true]);
    }
}
