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

use PromotionsBundle\Services\UserBargainService;

use Dingo\Api\Exception\ResourceException;

class UserBargains extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/promotion/userbargain",
     *     summary="参与砍价活动",
     *     tags={"营销"},
     *     description="参与砍价活动",
     *     operationId="createUserBargain",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="bargain_id", type="string", example="43", description="砍价活动ID"),
     *                  @SWG\Property( property="item_name", type="string", example="微信助力砍价专用", description="商品名称"),
     *                  @SWG\Property( property="mkt_price", type="string", example="9900", description="市场价格,单位为‘分’"),
     *                  @SWG\Property( property="price", type="string", example="5000", description="销售金额,单位为‘分’"),
     *                  @SWG\Property( property="cutprice_num", type="string", example="3", description="砍价次数"),
     *                  @SWG\Property( property="cutprice_range", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="cut", type="string", example="988", description=""),
     *                          @SWG\Property( property="used", type="string", example="0", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="cutdown_amount", type="string", example="0", description="已砍金额,单位为‘分’"),
     *                  @SWG\Property( property="is_ordered", type="string", example="false", description="是否已下单"),
     *                  @SWG\Property( property="created", type="string", example="1611569225", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611569225", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createUserBargain(Request $request)
    {
        $validator = app('validator')->make($request->input(), [
            'bargain_id' => 'required',
        ]);
        $authInfo = $request->get('auth');
        $bargainId = $request->input('bargain_id');

        $userBargainService = new UserBargainService();
        $result = $userBargainService->createUserBargain($authInfo, $bargainId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/userbargain",
     *     summary="获取用户参加砍价活动详情",
     *     tags={"营销"},
     *     description="获取用户参加砍价活动详情",
     *     operationId="getUserBargain",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="参与活动用户id", required=false, type="string"),
     *     @SWG\Parameter( name="has_order", in="query", description="是否获取订单", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="bargain_info", type="object",
     *                          @SWG\Property( property="bargain_id", type="string", example="43", description="砍价活动ID"),
     *                          @SWG\Property( property="title", type="string", example="超绝茄子砍价", description="活动名称"),
     *                          @SWG\Property( property="ad_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdtmicAt4yB9TY93OBlRubpPk6CQhVOYZPzuZEcQEBvibqxwjFqszl2uPYLzYty6oIGxv6AyG5X99Ew/0?wx_fmt=jpeg", description="活动广告图"),
     *                          @SWG\Property( property="item_name", type="string", example="微信助力砍价专用", description="商品名称"),
     *                          @SWG\Property( property="item_pics", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/Hw4SsicubkrdgG6icibvyUTIsSsRw7k1QPx5PHqljSnfCPY3MGV4Q7YyTHdKwvMmDibV7dy33vRuKNAm8uxehysSibg/0?wx_fmt=gif", description="商品图片"),
     *                          @SWG\Property( property="item_intro", type="string", example="", description="商品详情"),
     *                          @SWG\Property( property="mkt_price", type="string", example="9900", description="市场价格,单位为‘分’"),
     *                          @SWG\Property( property="price", type="string", example="5000", description="销售金额,单位为‘分’"),
     *                          @SWG\Property( property="limit_num", type="string", example="100", description="商品限购数量"),
     *                          @SWG\Property( property="order_num", type="string", example="0", description="已购买数量"),
     *                          @SWG\Property( property="bargain_rules", type="string", example="123", description="规则描述"),
     *                          @SWG\Property( property="bargain_range", type="object",
     *                                  @SWG\Property( property="min", type="string", example="0", description=""),
     *                                  @SWG\Property( property="max", type="string", example="0", description=""),
     *                          ),
     *                          @SWG\Property( property="people_range", type="object",
     *                                  @SWG\Property( property="min", type="string", example="1", description="最小砍价人数"),
     *                                  @SWG\Property( property="max", type="string", example="5", description="最大砍价人数"),
     *                          ),
     *                          @SWG\Property( property="min_price", type="string", example="100", description="每个人最少能看的价钱,单位为‘分’"),
     *                          @SWG\Property( property="begin_time", type="string", example="1610640000", description="开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1611936000", description="结束时间"),
     *                          @SWG\Property( property="share_msg", type="string", example="123", description="分享内容"),
     *                          @SWG\Property( property="help_pics", type="array",
     *                              @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkreDZrghuGAXq3g3licgEueUpChb9WiafJbicGvByicJsMVK8SFx6XEXcoyIq8EweCfffc05R5TmRscXWg/0?wx_fmt=png", description="翻牌图片"),
     *                          ),
     *                          @SWG\Property( property="created", type="string", example="1610698728", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1610698728", description=""),
     *                          @SWG\Property( property="is_expired", type="string", example="false", description="是否过期"),
     *                          @SWG\Property( property="item_id", type="string", example="5427", description="商品id"),
     *                          @SWG\Property( property="left_micro_second", type="string", example="366773000", description="剩余描述*1000"),
     *                  ),
     *                  @SWG\Property( property="user_bargain_info", type="object",
     *                          @SWG\Property( property="bargain_id", type="string", example="43", description="砍价活动ID"),
     *                          @SWG\Property( property="item_name", type="string", example="微信助力砍价专用", description="商品名称"),
     *                          @SWG\Property( property="mkt_price", type="string", example="9900", description="市场价格,单位为‘分’"),
     *                          @SWG\Property( property="price", type="string", example="5000", description="销售金额,单位为‘分’"),
     *                          @SWG\Property( property="cutprice_num", type="string", example="3", description="砍价次数"),
     *                          @SWG\Property( property="cutprice_range", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="cut", type="string", example="988", description=""),
     *                                  @SWG\Property( property="used", type="string", example="0", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="cutdown_amount", type="string", example="0", description="已砍金额,单位为‘分’"),
     *                          @SWG\Property( property="is_ordered", type="string", example="false", description="是否已下单"),
     *                          @SWG\Property( property="created", type="string", example="1611569225", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611569225", description=" | 修改时间"),
     *                  ),
     *                  @SWG\Property( property="user_info", type="object",
     *                          @SWG\Property( property="source_from", type="string", example="default", description="来源类型 default默认"),
     *                          @SWG\Property( property="need_transfer", type="string", example="false", description="是否禁用。0:不用迁移或迁移完成；1:需要迁移"),
     *                          @SWG\Property( property="created", type="string", example="1585214784", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1600683285", description=""),
     *                          @SWG\Property( property="nickname", type="string", example="小不点", description="用户昵称"),
     *                          @SWG\Property( property="sex", type="string", example="2", description="性别。0 未知 1 男 2 女"),
     *                          @SWG\Property( property="city", type="string", example="徐汇", description="市"),
     *                          @SWG\Property( property="country", type="string", example="中国", description="国家名称"),
     *                          @SWG\Property( property="province", type="string", example="上海", description="省"),
     *                          @SWG\Property( property="language", type="string", example="zh_CN", description="语言"),
     *                          @SWG\Property( property="headimgurl", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/DYAIOgq83eq8VpxG4Wrw3z2CocEu3gzRGSojONROor35ib9LjnhibtZcviagtGJ9Ct4h4DayBaaoryliat8DUeM1pw/132", description="用户头像url"),
     *                  ),
     *                  @SWG\Property( property="bargain_log", type="object",
     *                          @SWG\Property( property="total_count", type="string", example="0", description="总条数"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getUserBargain(Request $request)
    {
        $authInfo = $request->get('auth');
        $postdata = $request->input();
        $validator = app('validator')->make($postdata, [
            'bargain_id' => 'required',
            // 'user_id' => 'required',
        ], [
            'bargain_id.*' => trans('PromotionsBundle.bargain_activity_id_required'),
            // 'user_id.*' => trans('PromotionsBundle.bargain_user_id_required'),
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $hasOrder = (isset($postdata['has_order']) && $postdata['has_order'] == "true") ? true : false;

        $bargainId = $postdata['bargain_id'];
        $companyId = $authInfo['company_id'];
        $userId = $postdata['user_id'] ?? ($authInfo['user_id'] ?? 0);

        $userBargainService = new UserBargainService();
        $result = $userBargainService->getBargainInfo($companyId, $bargainId, $userId, $hasOrder);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/promotion/bargainlog",
     *     summary="参与砍价日志",
     *     tags={"营销"},
     *     description="参与砍价日志",
     *     operationId="createBargainLog",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="参与砍价人id", required=true, type="string"),
     *     @SWG\Parameter( name="open_id", in="query", description="助力人员open_id", required=true, type="string"),
     *     @SWG\Parameter( name="nickname", in="query", description="助力人员昵称", required=true, type="string"),
     *     @SWG\Parameter( name="headimgurl", in="query", description="助力人员头像url", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="bargain_id", type="string"),
     *                     @SWG\Property(property="user_id", type="string"),
     *                     @SWG\Property(property="open_id", type="string"),
     *                     @SWG\Property(property="nickname", type="string"),
     *                     @SWG\Property(property="headimgurl", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createBargainLog(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->input();
        $params['company_id'] = $authInfo['company_id'];
        $params['authorizer_appid'] = $authInfo['woa_appid'] ?? '';
        $params['wxa_appid'] = $authInfo['wxapp_appid'] ?? '';
        if ($authInfo['user_id'] ?? 0) {
            $params['open_id'] = $params['open_id'] ?? ($authInfo['open_id'] ?? '');
            $params['nickname'] = $params['nickname'] ?? ($authInfo['nickname'] ?? '');
            $params['headimgurl'] = $params['headimgurl'] ?? ($authInfo['headimgurl'] ?? '');
        }

        $validator = app('validator')->make($params, [
            'bargain_id' => 'required',
            'user_id' => 'required',
            'open_id' => 'required',
            // 'nickname' => 'required',
            // 'headimgurl' => 'required',
        ], [
            'bargain_id.*' => trans('PromotionsBundle.bargain_activity_id_required'),
            'user_id.*' => trans('PromotionsBundle.bargain_user_id_required'),
            'open_id.*' => trans('PromotionsBundle.wechat_openid_required'),
            // 'nickname.*' => '微信昵称必填',
            // 'headimgurl.*' => '微信头像必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $userBargainService = new UserBargainService();
        $result = $userBargainService->createBargainLog($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/bargainfriendwxappcode",
     *     summary="获取砍价分享小程序码",
     *     tags={"营销"},
     *     description="获取砍价分享小程序码",
     *     operationId="getBargainFriendWxaCode",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="base64Image", type="string")
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getBargainFriendWxaCode(Request $request)
    {
        // ShopEx EcShopX Core Module
        $authInfo = $request->get('auth');
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'bargain_id' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.miniprogram_qrcode_params_error'), $validator->errors());
        }
        $wxapp_appid = isset($authInfo['wxapp_appid']) ? $authInfo['wxapp_appid'] : '';
        if (!$wxapp_appid) {
            throw new ResourceException(trans('PromotionsBundle.miniprogram_qrcode_params_error_simple'));
        }
        $userBargainService = new UserBargainService();
        $result = $userBargainService->getBargainFriendWxaCode($wxapp_appid, $authInfo['user_id'], $params['bargain_id']);
        $base64 = 'data:image/jpg;base64,' . base64_encode($result);
        $res = ['base64Image' => $base64];
        return $this->response->array($res);
    }
}
