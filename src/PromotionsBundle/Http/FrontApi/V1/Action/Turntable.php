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

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use PromotionsBundle\Services\TurntableService;

class Turntable extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/turntable",
     *     summary="参与转盘抽奖",
     *     tags={"营销"},
     *     description="参与转盘抽奖",
     *     operationId="joinTurntable",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="prize_type", type="string", example="thanks", description="奖品类型，points:积分，coupon：优惠券，coupons：优惠券包，thanks：未中奖"),
     *                  @SWG\Property( property="prize_name", type="string", example="谢谢惠顾", description="奖项名称"),
     *                  @SWG\Property( property="prize_describe", type="string", example="恭喜获得测试代金券", description="奖品描述"),
     *                  @SWG\Property( property="prize_probability", type="string", example="8400", description="中奖概率 （1-100）*100"),
     *                  @SWG\Property( property="prize_image", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/Hw4SsicubkrdgG6icibvyUTIsSsRw7k1QPx5PHqljSnfCPY3MGV4Q7YyTHdKwvMmDibV7dy33vRuKNAm8uxehysSibg/0?wx_fmt=gif", description="奖品图片"),
     *                  @SWG\Property( property="prize_url", type="string", example="", description="奖项跳转的url"),
     *                  @SWG\Property( property="prize_bgcolor", type="string", example="", description="奖品背景色"),
     *                  @SWG\Property( property="prize_value", type="string", example="590", description="奖品值"),
     *                  @SWG\Property( property="goods_options", type="array",
     *                      @SWG\Items( type="string", example="", description="商品数据"),
     *                  ),
     *                  @SWG\Property( property="dataForm", type="object",
     *                          @SWG\Property( property="background_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfXrc1ACTW9bgbMx8vtebHmib6vFfG2eyyC8SuHACHY7mwD84GMpzAca5YHXoblY63NJRZop1dYNgQ/0?wx_fmt=jpeg", description="背景图"),
     *                          @SWG\Property( property="shadow_color", type="string", example="rgba(254, 234, 149, 1)", description="背景色"),
     *                          @SWG\Property( property="line_color", type="string", example="rgba(254, 234, 149, 1)", description="分割线颜色"),
     *                          @SWG\Property( property="border_color", type="string", example="rgba(7, 193, 44, 1)", description="边框颜色"),
     *                          @SWG\Property( property="pointer_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdQRGiaoPYvx559elFWNkLq4qGQk9IhTIK5H0lUtbiaJoEbTLbNfVeZ1Ck4K17hvQMt02dASfseYn0w/0?wx_fmt=jpeg", description=""),
     *                          @SWG\Property( property="describe", type="string", example="活动规则描述", description="活动规则描述"),
     *                  ),
     *                  @SWG\Property( property="id", type="string", example="0", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function joinTurntable(Request $request)
    {
        $user_info = $request->get('auth');
        $params = $request->all();
        if(empty($params['activity_id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }

        $turntable_services = new TurntableService();

//        $result = $turntable_services->joinTurntable($user_info);
        $result = $turntable_services->doLuckyDraw($user_info,$params['activity_id']);
        //转调


        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/turntableconfig",
     *     summary="获取大转盘配置",
     *     tags={"营销"},
     *     description="获取大转盘配置详情信息",
     *     operationId="getTurntableConfig",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="clear_times_after_end", type="string", example="0", description="结束时是否清空抽奖次数"),
     *                  @SWG\Property( property="max_times_day", type="integer", example="101", description="每日可抽奖次数，-1为不限制"),
     *                  @SWG\Property( property="login_get_times", type="integer", example="100", description="登陆时获取的次数，-1为不设置"),
     *                  @SWG\Property( property="start_time", type="string", example="1606665600", description="有效开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="1606752000", description="有效结束时间"),
     *                  @SWG\Property( property="long_term", type="string", example="1", description="是否长期有效，1长期有效，0周期有效"),
     *                  @SWG\Property( property="background_img", type="string", example="", description="背景图"),
     *                  @SWG\Property( property="turntable_title", type="string", example="大转盘主题", description="转盘标题"),
     *                  @SWG\Property( property="line_color", type="string", example="", description="分割线颜色"),
     *                  @SWG\Property( property="shopping_full", type="string", example="0.01", description="会员购满多少元赠送一次抽奖次数，-1为不设置"),
     *                  @SWG\Property( property="prizes", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="prize_type", type="string", example="coupon", description="奖品类型，points:积分，coupon：优惠券，coupons：优惠券包"),
     *                          @SWG\Property( property="prize_name", type="string", example="优惠券", description="奖品名称"),
     *                          @SWG\Property( property="prize_describe", type="string", example="恭喜获得测试代金券", description="奖品描述"),
     *                          @SWG\Property( property="prize_probability", type="string", example="8400", description="中奖概率 （1-100）*100"),
     *                          @SWG\Property( property="prize_image", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/Hw4SsicubkrdgG6icibvyUTIsSsRw7k1QPx5PHqljSnfCPY3MGV4Q7YyTHdKwvMmDibV7dy33vRuKNAm8uxehysSibg/0?wx_fmt=gif", description="中奖图片"),
     *                          @SWG\Property( property="prize_url", type="string", example="", description="奖项跳转的url"),
     *                          @SWG\Property( property="prize_bgcolor", type="string", example="", description="奖项背景色"),
     *                          @SWG\Property( property="prize_value", type="string", example="542", description="奖品值"),
     *                          @SWG\Property( property="goods_options", type="array",
     *                              @SWG\Items( type="string", example="", description="商品数据"),
     *                          ),
     *                          @SWG\Property( property="dataForm", type="object",
     *                                  @SWG\Property( property="background_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfXrc1ACTW9bgbMx8vtebHmib6vFfG2eyyC8SuHACHY7mwD84GMpzAca5YHXoblY63NJRZop1dYNgQ/0?wx_fmt=jpeg", description="背景图片"),
     *                                  @SWG\Property( property="shadow_color", type="string", example="rgba(254, 234, 149, 1)", description="阴影颜色"),
     *                                  @SWG\Property( property="line_color", type="string", example="rgba(254, 234, 149, 1)", description="分割线颜色"),
     *                                  @SWG\Property( property="border_color", type="string", example="rgba(7, 193, 44, 1)", description="边框颜色"),
     *                                  @SWG\Property( property="pointer_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdQRGiaoPYvx559elFWNkLq4qGQk9IhTIK5H0lUtbiaJoEbTLbNfVeZ1Ck4K17hvQMt02dASfseYn0w/0?wx_fmt=jpeg", description=""),
     *                                  @SWG\Property( property="describe", type="string", example="活动规则描述", description="活动规则描述"),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="shadow_color", type="string", example="", description="阴影颜色"),
     *                  @SWG\Property( property="turntable_open", type="string", example="1", description="是否开启大转盘"),
     *                  @SWG\Property( property="border_color", type="string", example="", description="边框颜色"),
     *                  @SWG\Property( property="today_times", type="string", example="0", description="用户今日已抽奖次数"),
     *                  @SWG\Property( property="surplus_times", type="string", example="4", description="用户剩余抽奖次数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getTurntableConfig(Request $request)
    {
        $user_info = $request->get('auth');

        $turntable_services = new TurntableService();
        $result = $turntable_services -> getTurntableConfig($user_info['company_id'], $user_info['user_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/loginaddtimes",
     *     summary="登陆赠送抽奖次数",
     *     tags={"营销"},
     *     description="登陆赠送抽奖次数",
     *     operationId="loginAddSurplusTimes",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="result", type="integer", example="100", description="赠送的次数"),
     *
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function loginAddSurplusTimes(Request $request)
    {
        $user_info = $request->get('auth');

        $turntable_services = new TurntableService();
        $result = $turntable_services -> loginAddSurplusTimes($user_info['company_id'], $user_info['user_id']);
        return $this->response->array($result);
    }


    // /wxapp/promotion/getLuckyDrawData
    public function getLuckyDrawActInfo(Request $request)
    {
        $params = $request->all();
        $service = new TurntableService();
        if(empty($params['id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }
        $user_info = $request->get('auth');
        $data = $service->getDetail($params['id']);
        $data['user_info'] = $service->getUserInfo($user_info['user_id']);
        return $this->response->array($data);
    }


    // /wxapp/promotion/getLuckyDrawLog
    public function getTurnLog(Request $request)
    {
        $params = $request->all();
        if(empty($params['id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }
        $user_info = $request->get('auth');
        $service = new TurntableService();
        $page = $params['page'] ?? 1;
        $limit = $params['page_size'] ?? 20;
        $data = $service->getLuckyDrawLog($user_info['user_id'],$params['id'],$page,-1);
        return $this->response->array($data);
    }
}
