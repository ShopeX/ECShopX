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

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use PromotionsBundle\Services\TurntableService;

class Turntable extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/promotions/turntableconfig",
     *     summary="配置大转盘",
     *     tags={"营销"},
     *     description="配置大转盘",
     *     operationId="setTurntableConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="turntable_open", in="query", description="是否开启大转盘", required=true, type="integer"),
     *     @SWG\Parameter( name="turntable_title", in="query", description="大转盘标题", type="string"),
     *     @SWG\Parameter( name="long_term", in="query", description="是否长期有效", type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", type="integer"),
     *     @SWG\Parameter( name="max_times_day", in="query", description="每天最大抽奖次数", type="integer"),
     *     @SWG\Parameter( name="login_get_times", in="query", description="登陆时获取的次数，-1为不设置", type="integer"),
     *     @SWG\Parameter( name="shopping_full", in="query", description="购物满多少元获取一次抽奖次数，-1为不设置", type="integer"),
     *     @SWG\Parameter( name="clear_times_after_end", in="query", description="活动结束时清空抽奖次数", type="integer"),
     *     @SWG\Parameter( name="prizes", in="query", description="奖项配置，json数组", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function setTurntableConfig(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        //新版处理
        $params = $request->all();
        if(empty($params['prize_data'])){
            throw new ResourceException(trans('PromotionsBundle.prize_setting_required'));
        }
        $prizeData = $params['prize_data'];
        if(!is_array($params['prize_data'])){
            $prizeData = json_decode($params['prize_data'],true);
        }

        $totalCount = 0;
        $isThanks = false;
        foreach ($prizeData as $value){
            $totalCount += $value['prize_probability'];
            if ($value['prize_type'] === 'points') {
                if (!isset($value['prize_value']) || $value['prize_value'] <= 0) {
                    throw new ResourceException(trans('PromotionsBundle.enter_points_value'));
                }
            }
            if ($value['prize_type'] === 'coupon') {
                if (!isset($value['prize_value']) || empty($value['prize_value'])) {
                    throw new ResourceException(trans('PromotionsBundle.select_coupon'));
                }
            }
            if ($value['prize_type'] === 'coupons') {
                if (!isset($value['prize_value'])) {
                    throw new ResourceException(trans('PromotionsBundle.coupon_package_select_at_least_one'));
                }
            }
            if (!isset($value['prize_value']) || $value['prize_probability'] > 100 || $value['prize_probability'] < 1) {
                throw new ResourceException(trans('PromotionsBundle.winning_probability_range_error'));
            }
            if ($value['prize_type'] === 'thanks') {
                $isThanks = true;
            }
        }
        if (!$isThanks) {
            throw new ResourceException(trans('PromotionsBundle.select_at_least_one_thanks_prize'));
        }
        if($totalCount > 100 ){
            throw new ResourceException(trans('PromotionsBundle.probability_total_cannot_exceed_100'));
        }

        if($params['begin_time'] >= $params['end_time']){
            throw new ResourceException(trans('PromotionsBundle.start_time_cannot_greater_equal_end_time'));
        }

//        $turntable_open = $request->input('turntable_open', ''); //是否开启大转盘，1开启，0关闭
//        $turntable_title = $request->input('turntable_title', ''); //转盘标题
//        $long_term = $request->input('long_term', ''); //是否长期有效，1长期有效，0周期有效
//        $start_time = $request->input('start_time', ''); //有效开始时间
//        $end_time = $request->input('end_time', ''); //有效结束时间
//        $max_times_day = $request->input('max_times_day', '-1'); //每日可抽奖次数，-1为不限制
//        $login_get_times = $request->input('login_get_times', '0'); //会员登陆可获得的抽奖次数，0为不设置
//        $shopping_full = $request->input('shopping_full', '-1'); //会员购满多少元赠送一次抽奖次数，-1为不设置
//        $clear_times_after_end = $request->input('clear_times_after_end', '0'); //结束时是否清空抽奖次数
//        $shadow_color = $request->input('shadow_color', ''); //阴影颜色
//        $border_color = $request->input('border_color', ''); //边框颜色
//        $line_color = $request->input('line_color', ''); //分割线颜色
//        $background_img = $request->input('background_img', ''); //背景图
//
//        $prizes = $request->input('prizes', ''); //奖项配置，json数组
//
//        if ($long_term === '' || $long_term === 0) { //长期有效
//            if (!$start_time || !$end_time) {
//                throw new ResourceException('请选择开始/结束日期');
//            }
//        }
//
//        if (!$prizes) {
//            throw new ResourceException('请设置奖项');
//        }
//
//        //验证奖项
//        $prizes = json_decode($prizes, true);
//        if (!in_array(count($prizes), [4,6,8])) {
//            throw new ResourceException('奖项数量不正确');
//        }
//        $isThanks = false;
//        foreach ($prizes as $value) {
//            if ($value['prize_type'] === 'points') {
//                if (!isset($value['prize_value']) || $value['prize_value'] <= 0) {
//                    throw new ResourceException(trans('PromotionsBundle.enter_points_value'));
//                }
//            }
//            if ($value['prize_type'] === 'coupon') {
//                if (!isset($value['prize_value']) || empty($value['prize_value'])) {
//                    throw new ResourceException(trans('PromotionsBundle.select_coupon'));
//                }
//            }
//            if ($value['prize_type'] === 'coupons') {
//                if (!isset($value['prize_value']) || count($value['prize_value']) < 1) {
//                    throw new ResourceException(trans('PromotionsBundle.coupon_package_select_at_least_one'));
//                }
//            }
//            if (!isset($value['prize_value']) || $value['prize_probability'] > 10000 || $value['prize_probability'] < 1) {
//                throw new ResourceException('中奖概率必须在100%到0.01%之间');
//            }
//            if ($value['prize_type'] == 'thanks') {
//                $isThanks = true;
//            }
//        }
//
//        if (!$isThanks) {
//            throw new ResourceException(trans('PromotionsBundle.select_at_least_one_thanks_prize'));
//        }

//        $datas = [
//            'turntable_title' => $turntable_title,
//            'long_term' => $long_term,
//            'start_time' => $start_time,
//            'end_time' => $end_time,
//            'max_times_day' => $max_times_day,
//            'login_get_times' => $login_get_times,
//            'shopping_full' => $shopping_full,
//            'clear_times_after_end' => $clear_times_after_end,
//            'turntable_open' => $turntable_open,
//            'prizes' => json_encode($prizes),
//            'shadow_color' => $shadow_color,
//            'border_color' => $border_color,
//            'line_color' => $line_color,
//            'background_img' => $background_img,
//        ];

        $turntable_service = new TurntableService();
        $params['company_id'] = $company_id;
        $params['prize_data'] = json_encode($prizeData);
        $result = $turntable_service->setTurntableConfig( $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/turntableconfig",
     *     summary="获取大转盘配置",
     *     tags={"营销"},
     *     description="获取大转盘配置",
     *     operationId="getTurntableConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
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
        $company_id = app('auth')->user()->get('company_id');
        $params = $request->all();
        if(empty($params['id'])){
            throw new ResourceException(trans('PromotionsBundle.id_required_error'));
        }
        $turntable_service = new TurntableService();
        $result = $turntable_service->getTurntableConfig($company_id,$params['id']);

        return $this->response->array($result);
    }

    public function getDrawActivityList(Request $request)
    {
        $now = time();
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $limit = $params['pageSize'] ?? 10;
        $service = new TurntableService();
        $filter = [];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        if(!empty($params['activity_id'])){
            $filter['id'] = $params['activity_id'];
        }
        if(!empty($params['activity_name'])){
            $filter['activity_name|like'] = $params['activity_name'];
        }
        //枚举值 notstart 未开始，expire已过期，online，进行中
        if(!empty($params['status'])){
            if($params['status'] === 'notstart'){
                $filter['begin_time|gt'] =$now;
            }

            if($params['status'] === 'expire'){
                $filter['end_time|lt'] =$now;
            }

            if($params['status'] === 'online'){
                $filter['begin_time|lt'] =$now;
                $filter['end_time|gt'] =$now;
            }
        }

        $list = $service->getActivityList($filter,$page,$limit);

        foreach ($list['list'] as $index => $value) {
            //开始时间比当大，未开始
            if($value['begin_time'] > $now){
                $list['list'][$index]['status'] = 'notstart';
            }
            if($value['end_time'] < $now){
                $list['list'][$index]['status'] = 'expire';
            }
            if($value['end_time'] > $now && $value['begin_time'] <= $now){
                $list['list'][$index]['status'] = 'online';
            }
        }
        return $this->response->array($list);
    }


    // /promotions/getturntable
    public function getLuckyDrawDetail(Request $request)
    {
        $params = $request->all();
        if(empty($params['id'])){
            throw new ResourceException(trans('PromotionsBundle.id_not_exist_error'));
        }
        $detail = (new TurntableService())->getDetail($params['id']);
        return $this->response->array($detail);
    }

    // /promotions/getturntable_log/byid
    public function getLogStatistics(Request $request)
    {
        $params = $request->all();
        if(empty($params['activity_id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }
        $page = $params['page'] ?? 1;
        $limit = $params['page_size'] ?? 20;
        $list = (new TurntableService())->getLuckyDrawLogByActId($params['activity_id'],$page,$limit);
        return $this->response->array($list);
    }
// /promotions/getturntable_count/byid
    public function getLogCount(Request $request)
    {
        $params = $request->all();
        if(empty($params['activity_id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }
        $data = (new TurntableService())->getTotalCount($params['activity_id']);
        return $this->response->array($data);
    }

    // /promotions/down_lucky_draw/byid
    public function downLuckyDrawActivity(Request $request)
    {
        $params = $request->all();
        if(empty($params['activity_id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }
        (new TurntableService())->downActvity($params['activity_id']);
        return $this->response->array(['status'=>true]);
    }

// /promotions/down_lucky_draw/export
    public function exportLog(Request $request)
    {
        $params = $request->all();
        if(empty($params['activity_id'])){
            throw new ResourceException(trans('PromotionsBundle.activity_id_required_error'));
        }
        $authdata = app('auth')->user()->get();
        $operator_id = app('auth')->user()->get('operator_id');

        // 是否有权限查看加密数据
        $params['datapass_block'] = $request->get('x-datapass-block');
        $gotoJob = (new ExportFileJob('export_luckdraw_log', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);

    }

}
