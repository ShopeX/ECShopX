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
use App\Http\Controllers\Controller as BaseController;
use PromotionsBundle\Services\CouponGiveErrorLogService;
use PromotionsBundle\Services\CouponGiveLogService;
use PromotionsBundle\Services\PromotionActivity;

class GivePromotions extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/promotions/activity/give",
     *     summary="后台发放优惠券",
     *     tags={"营销"},
     *     description="后台发放优惠券",
     *     operationId="checkActiveValidNum",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="userids", in="query", description="活动类型名称", required=true, type="string"),
     *     @SWG\Parameter( name="couponsids", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="boolean",
     *             ),
     *          ),
     *     ),
     * )
     */
    public function give(Request $request)
    {
        $users = $request->input('userids');
        $coupons = $request->input('couponsids');
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = app('auth')->user()->get('distributor_id');

        if (app('auth')->user()->get('operator_type') == 'staff') {
            $sender = '员工-'.app('auth')->user()->get('username').'-'.app('auth')->user()->get('mobile');
        } else {
            $sender = app('auth')->user()->get('username');
        }

        $sourceFrom = '商城后台发放';
        $promotionActivity = new PromotionActivity();
        $promotionActivity->scheduleGiveToJob($companyId, $sender, $coupons, $users, $sourceFrom, $distributorId);
        return $this->response->array(['data' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/activity/give",
     *     summary="优惠券发放日志",
     *     tags={"营销"},
     *     description="优惠券发放日志",
     *     operationId="getGiveLog",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数，默认1", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数，默认20", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="151", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="give_id", type="string", example="170", description="优惠券赠送失败记录id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                          @SWG\Property( property="sender", type="string", example="欢迎", description="发送者"),
     *                          @SWG\Property( property="number", type="string", example="4", description="赠送数量"),
     *                          @SWG\Property( property="error", type="string", example="0", description="失败数量"),
     *                          @SWG\Property( property="created", type="string", example="1611901529", description=""),
     *                       ),
     *                  ),
     *          ),
     *          ),
     *     ),
     * )
     */
    public function getGiveLog(Request $request)
    {
        $params['company_id'] = app('auth')->user()->get('company_id');
        $distributor_id = app('auth')->user()->get('distributor_id');
        if (!empty($distributor_id)) {
            $params['distributor_id'] = $distributor_id;
        }
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $couponGiveLogService = new CouponGiveLogService();
        $result = $couponGiveLogService->getCouponGiveLogList($params, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/activity/give/{id}",
     *     summary="优惠券赠送失败记录",
     *     tags={"营销"},
     *     description="优惠券赠送失败记录",
     *     operationId="getGiveErrorLog",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数，默认1", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数，默认20", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="8", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="give_log_id", type="string", example="2317", description="优惠券赠送失败记录id"),
     *                          @SWG\Property( property="give_id", type="string", example="161", description="优惠券赠送失败记录id"),
     *                          @SWG\Property( property="uid", type="string", example="20391", description="赠送用户id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                          @SWG\Property( property="card_id", type="string", example="585", description="赠送优惠券id"),
     *                          @SWG\Property( property="note", type="string", example="领取的优惠券失败，库存不足了", description="失败原因记录"),
     *                          @SWG\Property( property="created", type="string", example="1611302477", description=""),
     *                          @SWG\Property( property="title", type="string", example="", description="卡券名"),
     *                          @SWG\Property( property="username", type="string", example="", description="姓名"),
     *                          @SWG\Property( property="mobile", type="string", example="", description="用户手机号"),
     *                       ),
     *                  ),
     *          ),
     *         ),
     *     ),
     * )
     */
    public function getGiveErrorLog($id, Request $request)
    {
        $params['give_id'] = $id;
        $params['company_id'] = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $couponGiveErrorLogService = new CouponGiveErrorLogService();
        $result = $couponGiveErrorLogService->getCouponGiveErrorLogList($params, $page, $pageSize);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        foreach ($result['list'] as $key => $value) {
            if ($datapassBlock) {
                if (isset($value['mobile']) && $value['mobile'] != 'null') {
                    $result['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
                if (isset($value['username']) && $value['username'] != 'null') {
                    $result['list'][$key]['username'] = data_masking('truename', (string) $value['username']);
                }
            }
        }
        return $this->response->array($result);
    }
}
