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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\RegisterPromotionsService;

class RegisterPromotions extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/register",
     *     summary="获取注册引导营销配置",
     *     tags={"营销"},
     *     description="获取注册引导营销配置,开启中",
     *     operationId="getRegisterPromotionsConfig",
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="company_id", in="header", description="公司company_id(h5端必填)", type="integer"),
     *     @SWG\Parameter( name="register_type", in="query", description="注册营销类型 促销类型。可选值有 general-普通;membercard-付费会员卡;all-全部", default="general", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="id", type="string", description="Id", example="1"),
     *                 @SWG\Property(property="company_id", type="string", description="企业Id", example="1"),
     *                 @SWG\Property(property="is_open", type="string", description="是否开启", example="true"),
     *                 @SWG\Property(property="register_type", type="string", description="注册营销类型 促销类型。可选值有 general-普通;membercard-付费会员卡;", example="general"),
     *                 @SWG\Property(property="ad_title", type="string", description="注册引导广告标题", example="注册享大礼"),
     *                 @SWG\Property(property="ad_pic", type="string", description="注册引导图片", example="http://bbctest.aixue7.com/1/2019/12/12/75d22c27eece5bc99c289c9855289a88VtpE4lLXqgL8JpwprRUMescPgOQMGBTp"),
     *                 @SWG\Property(property="register_jump_path", type="string", description="注册引导跳转路径", example=""),
     *                 @SWG\Property(property="promotions_value", type="object", description="",
     *                     @SWG\Property(property="items", type="string", description="商品id数组"),
     *                     @SWG\Property(property="itemsList", type="array", description="服务商品列表",
     *                         @SWG\Items(
     *                             @SWG\Property(property="key", type="string", description="商品id", example="1"),
     *                             @SWG\Property(property="label", type="string", description="商品名称", example="次卡-不限次"),
     *                         ),
     *                     ),
     *                     @SWG\Property(property="coupons", type="array", description="赠送优惠券",
     *                         @SWG\Items(
     *                             ref="#/definitions/Coupons"
     *
     *                         ),
     *                     ),
     *                     @SWG\Property(property="staff_coupons", type="array", description="注册送优惠券列表",
     *                         @SWG\Items(
     *                             ref="#/definitions/Coupons"
     *                         ),
     *                     ),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    /**
     * @SWG\Definition(
     *     definition="Coupons",
     *     type="object",
     *     @SWG\Property(property="card_id", type="string", description="优惠券id", example="1"),
     *     @SWG\Property(property="count", type="string", description="赠送数量", example="1"),
     *     @SWG\Property(property="title", type="string", description="优惠券标题", example="代金券10元"),
     * )
     */
    public function getRegisterPromotionsConfig(Request $request)
    {
        $registerPromotionsService = new RegisterPromotionsService();
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $type = $request->input('register_type', 'general');
        $data = $registerPromotionsService->getRegisterPromotionsConfig($companyId, $type);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/getMemberCard",
     *     summary="会员领取付费会员卡",
     *     tags={"营销"},
     *     description="会员领取付费会员卡",
     *     operationId="getMembercardPromotions",
     *     @SWG\Parameter( name="authorization", in="header", description="登录token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="object",
     *                     @SWG\Property(property="order_id", type="string", description="订单编号", example="3308682000070032"),
     *                     @SWG\Property(property="vip_grade_id", type="string", description="付费会员等级id", example="1"),
     *                     @SWG\Property(property="lv_type", type="string", description="等级类型,可选值有 vip:普通vip;svip:进阶vip", example="vip"),
     *                     @SWG\Property(property="mobile", type="string", description="会员手机号", example="13000000000"),
     *                     @SWG\Property(property="title", type="string", description="标题", example="一般付费"),
     *                     @SWG\Property(property="price", type="integer", description="价格", example="1"),
     *                     @SWG\Property(property="card_type", type="object", description="类型",
     *                         @SWG\Property(property="name", type="string", description="名称", example="monthly"),
     *                         @SWG\Property(property="price", type="string", description="价格(元)", example="0.01"),
     *                         @SWG\Property(property="day", type="string", description="天数", example="30"),
     *                         @SWG\Property(property="desc", type="string", description="天数描述", example="30天"),
     *                     ),
     *                     @SWG\Property(property="discount", type="integer", description="折扣 8折", example="20"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id", example="1"),
     *                     @SWG\Property(property="source_id", type="string", description="订单来源id", example=""),
     *                     @SWG\Property(property="source_type", type="string", description="订单来源类型", example="receive"),
     *                     @SWG\Property(property="monitor_id", type="string", description="订单监控页面id", example=""),
     *                     @SWG\Property(property="order_status", type="string", description="", example=""),
     *                     @SWG\Property(property="created", type="integer", description="创建时间", example="1611219856"),
     *                     @SWG\Property(property="updated", type="integer", description="更新时间", example="1611219856"),
     *                     @SWG\Property(property="fee_type", type="string", description="货币类型", example="CNY"),
     *                     @SWG\Property(property="fee_rate", type="integer", description="货币费率", example="1"),
     *                     @SWG\Property(property="fee_symbol", type="string", description="货币符号", example="￥"),
     *                 ),
     *             ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getMembercardPromotions(Request $request)
    {
        $registerPromotionsService = new RegisterPromotionsService();
        $authInfo = $request->get('auth');
        try {
            $result = $registerPromotionsService->actionPromotionByCompanyId($authInfo['company_id'], $authInfo['user_id'], $authInfo['mobile'], 'membercard');
        } catch (\Exception $exception) {
            app('log')->debug('新客营销错误' . $exception->getMessage());
        }
        return $this->response->array(['status' => $result ?? false]);
    }
}
