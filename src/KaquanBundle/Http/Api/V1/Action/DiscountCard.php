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

namespace KaquanBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Support\Arr;
use KaquanBundle\Entities\UserDiscount;
use KaquanBundle\Services\DiscountCardService as CardService;
use KaquanBundle\Services\DiscountNewGiftCardService;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\UserDiscountService;

class DiscountCard extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/discountcard#gift",
     *     summary="添加新兑换券",
     *     tags={"卡券"},
     *     description="添加新兑换券",
     *     operationId="createDiscountCardNew",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="card_type", in="query", description="卡券类型 new_gift", type="string", required=true),
     *     @SWG\Parameter(name="title", in="query", description="卡券名称", type="string", required=true),
     *     @SWG\Parameter(name="color", in="query", description="卡券颜色", type="string", required=true),
     *     @SWG\Parameter(name="description", in="query", description="卡券使用说明", type="string", required=true),
     *     @SWG\Parameter(name="quantity", in="query", description="卡券库存数量", type="integer", required=true),
     *     @SWG\Parameter(name="date_type", in="query", description="时间类型[DATE_TYPE_LONG: 长期活动 DATE_TYPE_SHORT: 短期活动]", type="string", required=true),
     *     @SWG\Parameter(name="send_begin_time", in="query", description="发放结束时间", type="integer"),
     *     @SWG\Parameter(name="send_end_time", in="query", description="发放结束时间", type="integer"),
     *     @SWG\Parameter(name="begin_time", in="query", description="开始时间(时间戳或者天数)", type="integer", required=true),
     *     @SWG\Parameter(name="days", in="query", description="天数(大于0)", type="integer"),
     *     @SWG\Parameter(name="end_time", in="query", description="结束时间", type="integer"),
     *     @SWG\Parameter(name="get_limit", in="query", description="每人可领券的数量限制", type="integer"),
     *     @SWG\Parameter(name="receive", in="query", description="是否前台直接领取", type="string"),
     *     @SWG\Parameter(name="grade_ids", in="query", description="指定会员ids [1,2]", type="string"),
     *     @SWG\Parameter(name="vip_grade_ids", in="query", description="指定付费会员ids [1,2]", type="string"),
     *     @SWG\Parameter(name="lock_time", in="query", description="使用商品锁定时间", type="integer"),
     *     @SWG\Parameter(name="kq_status", in="query", description="卡券状态 0:正常 1:暂停 2:关闭", type="integer"),
     *     @SWG\Parameter(name="distributor_ids", in="query", description="指定店铺id列表 [1, 2]", type="string"),
     *     @SWG\Parameter(name="items", in="query", description="指定可兑换商品信息 [{'id': 1, 'limit': 3}, {'id': 2, 'limit': 4}]", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status",
     *                     ref="#/definitions/DiscountCard"
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) ),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/discountcard",
     *     summary="添加优惠券",
     *     tags={"卡券"},
     *     description="添加优惠券",
     *     operationId="createDiscountCard",
     *     @SWG\Parameter(
     *         name="Authorization", in="header", description="JWT验证token", required=true, type="string"
     *     ),
     *    @SWG\Parameter(
     *         name="card_type", in="query", description="卡券类型一下类型选一[ gift,discount,cash,groupon,general_coupon]", type="string", required=true,
     *    ),
     *    @SWG\Parameter(
     *         name="title", in="query", description="卡券名称", type="string", required=true,
     *    ),
     *     @SWG\Parameter(
     *         name="color", in="query", description="卡券颜色", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *        name="description", in="query", description="卡券使用说明", type="string", required=true
     *     ),
     *     @SWG\Parameter(
     *        name="quantity", in="query", description="卡券库存数量", type="integer", required=true
     *     ),
     *     @SWG\Parameter(
     *        name="date_type", in="query", description="时间类型[DATE_TYPE_FIX_TIME_RANGE, DATE_TYPE_FIX_TERM]", type="string", required=true
     *     ),
     *     @SWG\Parameter(
     *        name="begin_time", in="query", description="开始时间(时间戳或者天数)", type="integer", required=true
     *     ),
     *     @SWG\Parameter(
     *        name="days", in="query", description="天数(大于0)", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="end_time", in="query", description="结束时间", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="service_phone", in="query", description="客服电话", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="use_limit", in="query", description="每人可核销的数量限制", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="get_limit", in="query", description="每人可领券的数量限制", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="time_limit_type", in="query", description="日期 周一~周日的英文 多选, json_array数据", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="time_limit_date", in="query", description="时段", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="can_use_with_other_discount", in="query", description="是否与其他类型共享门槛", type="boolean", default=false
     *     ),
     *     @SWG\Parameter(
     *        name="least_cost", in="query", description="代金券专用，表示起用金额（单位为分）", type="integer", default=0
     *     ),
     *     @SWG\Parameter(
     *        name="reduce_cost", in="query", description="代金券专用，表示减免金额。（单位为分）", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="gift", in="query", description="兑换券专用，填写兑换内容的名称", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="use_scenes", in="query", description="卡券核销场景", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="self_consume_code", in="query", description="自助核销验证码", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="discount", in="query", description="折扣券专用，表示打折额度", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="default_detail", in="query", description="优惠券专用，填写优惠详情", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="use_all_shops", in="query", description="是否适用全部门店", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="rel_shops_ids", in="query", description="适用的门店id", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="use_platform", in="query", description="适用平台", type="integer"
     *     ),
     *     @SWG\Parameter(
     *        name="receive", in="query", description="是否前台直接领取", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="useCondition", in="query", description="", type="string"
     *     ),
     *     @SWG\Parameter(
     *        name="store_self", in="query", description="平台版仅支持自营商品【总店】", type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status",
     *                     ref="#/definitions/DiscountCard"
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */

    public function createDiscountCard(Request $request)
    {
        $inputData = $request->all();
        $rules = [
                    'card_type' => ['required', trans('KaquanBundle.card_type_required')],
        'title' => ['required', trans('KaquanBundle.title_required')],
        'color' => ['required', trans('KaquanBundle.color_required')],
        'description' => ['required', trans('KaquanBundle.description_required')],
        'quantity' => ['required|integer|min:1|max:2147483647', trans('KaquanBundle.quantity_range_error')],
        'begin_time' => ['required', trans('KaquanBundle.begin_time_required')],
        'date_type' => ['required', trans('KaquanBundle.date_type_required')],
        'least_cost' => ['numeric', trans('KaquanBundle.least_cost_numeric')],
        'reduce_cost' => ['numeric', trans('KaquanBundle.reduce_cost_numeric')],
        'discount' => ['numeric', trans('KaquanBundle.discount_numeric')],

        ];

        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if ($request->input('date_type') == "DATE_TYPE_FIX_TERM" && !is_numeric($request->input('begin_time'))) {
            throw new ResourceException(trans('KaquanBundle.add_card_validity_error'));
        }
        $companyId = app('auth')->user()->get('company_id');

        // 新版兑换券逻辑判断
        if ($request->input('card_type') == 'new_gift') {
            // 活动类型 长期/短期
            $data = $request->input();
            $data['company_id'] = $companyId;
            $dateType = $request->input('date_type');
            if ($dateType == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                if (app('validator')->make($request->all(), [
                    'send_begin_time' => 'required|integer',
                    'begin_time' => 'required|integer',
                    'days' => 'required|integer'
                ])->fails()) {
                    throw new ResourceException(trans('KaquanBundle.add_card_validity_error'));
                }
            } elseif ($dateType == DiscountNewGiftCardService::DATE_TYPE_SHORT) {
                if (app('validator')->make($request->all(), [
                    'send_begin_time' => 'required|integer',
                    'send_end_time' => 'required|integer',
                    'begin_time' => 'required|integer',
                    'end_time' => 'required|integer',
                ])->fails()) {
                    throw new ResourceException(trans('KaquanBundle.add_card_validity_error'));
                }
            } else {
                throw new ResourceException(trans('KaquanBundle.add_card_type_error'));
            }
            if (app('validator')->make($request->all(), [
                'lock_time' => 'required|integer',
                // 'get_limit' => 'required|integer', 用更新库存接口
                'receive' => 'required',
            ])->fails()) {
                throw new ResourceException(trans('KaquanBundle.add_card_general_error'));
            }
            /** @var DiscountNewGiftCardService $discountCardService */
            $discountCardService = new KaquanService(new DiscountNewGiftCardService());
            $result = $discountCardService->createKaquan($data);
            return $this->response->array(['status' => $result]);
        }

        $postdata = $this->__doParams($request->input(), $companyId);
        $postdata['company_id'] = $companyId;

        $postdata['source_id'] = app('auth')->user()->get('distributor_id');//如果是平台，这里是0
        $postdata['source_type'] = app('auth')->user()->get('operator_type');//如果是平台，这里是admin

        $store_self = $request->input('store_self');
        if ($store_self == "true") {//平台版仅支持自营商品【总店】
            $postdata['use_all_shops'] = false;
            $postdata['distributor_id'] = [0 => "0"];
        } else {//正常的店铺选择
            $distributor_id = $request->get('distributor_id');
            if ($distributor_id) {
                $postdata['distributor_id'] = is_array($distributor_id) ? $distributor_id : explode(',', $distributor_id);
                $postdata['use_all_shops'] = false;
            }
            if (!$postdata['distributor_id'] && $request->get('rel_distributor_ids')) {
                $postdata['distributor_id'] = $request->get('rel_distributor_ids');
                $postdata['use_all_shops'] = false;
            }
        }
        $authorizerAppid = app('auth')->user()->get('authorizer_appid');
        $discountCardService = new KaquanService(new DiscountCardService());
        $result = $discountCardService->createKaquan($postdata, $authorizerAppid);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Patch(
     *     path="/discountcard",
     *     summary="修改卡券内容",
     *     tags={"卡券"},
     *     description="修改卡券内容",
     *     operationId="updateDiscountCard",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="card_id", in="query", description="卡券id", required=true, type="string"),
     *     @SWG\Parameter(name="color", in="query", description="卡券颜色", type="string", required=true),
     *     @SWG\Parameter(name="description",in="query",description="卡券使用说明",type="string",required=true ),
     *     @SWG\Parameter(name="date_type", in="query", description="时间类型[DATE_TYPE_FIX_TIME_RANGE, DATE_TYPE_FIX_TERM]", type="string", required=true),
     *     @SWG\Parameter(name="begin_time", in="query", description="开始时间(时间戳或者天数)", type="integer", required=true),
     *     @SWG\Parameter(name="days", in="query", description="天数(大于0)", type="integer"),
     *     @SWG\Parameter(name="end_time", in="query", description="结束时间", type="integer"),
     *     @SWG\Parameter(name="service_phone", in="query", description="客服电话", type="string"),
     *     @SWG\Parameter(name="get_limit", in="query", description="每人可领券的数量限制", type="integer"),
     *     @SWG\Parameter(name="quentity", in="query", description="领取总数", type="integer"),
     *     @SWG\Parameter(name="receive", in="query", description="是否前台直接领取 true | false", type="string"),
     *     @SWG\Parameter(name="grade_id", in="query", description="会员等级", type="string"),
     *     @SWG\Parameter(name="lock_time", in="query", description="使用兑换券锁定时间", type="integer"),
     *     @SWG\Parameter(name="kq_status", in="query", description="卡券状态 0：普通 1：暂停 2：关闭", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status",
     *                     ref="#/definitions/DiscountCard"
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function updateDiscountCard(Request $request)
    {
        // NOTE: important business logic
        $companyId = app('auth')->user()->get('company_id');

        if ($request->input('card_type') == 'new_gift') {
            $validator = app('validator')->make($request->all(), [
                'card_id' => 'required',
            ]);
            if ($validator->fails()) {
                throw new ResourceException(trans('KaquanBundle.update_card_no_id'), $validator->errors());
            }
            /** @var DiscountNewGiftCardService $discountCardService */
            $discountCardService = new KaquanService(new DiscountNewGiftCardService());
            $data = $request->input();
            $data = Arr::only($data, [
                'card_id',
                'quantity',
                'description',
                'receive',
                'grade_ids',
                'vip_grade_ids',
                'lock_time',
                'kq_status',
                'items',
                'distributor_ids',
                'user_tag_ids'
            ]);
            $data['company_id'] = $companyId;
            $result = $discountCardService->updateKaquan($data);
            return $this->response->array(['status' => $result]);
        }

        $validator = app('validator')->make($request->all(), [
            'color' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('KaquanBundle.update_card_error'), $validator->errors());
        }
        $discountCardService = new KaquanService(new DiscountCardService());
        $postdata = $this->__doParams($request->input(), $companyId);

        $store_self = $request->input('store_self');
        if ($store_self == "true") {//平台版仅支持自营商品【总店】
            $postdata['use_all_shops'] = false;
            $postdata['distributor_id'] = [0 => "0"];
        } else {//正常的店铺选择
            $distributor_id = $request->get('distributor_id');
            if ($distributor_id) {
                $postdata['distributor_id'] = is_array($distributor_id) ? $distributor_id : explode(',', $distributor_id);
                $postdata['use_all_shops'] = false;
            }
            if (!$postdata['distributor_id'] && $request->get('rel_distributor_ids')) {
                $postdata['distributor_id'] = json_decode($request->get('rel_distributor_ids'), 1);
                $postdata['use_all_shops'] = false;
            }
        }

        //获取原有旧数据
        $filter['card_id'] = $postdata['card_id'];
        $filter['company_id'] = $companyId;
        $dataInfo = $discountCardService->getKaquanDetail($filter);

        if ($postdata['date_type'] == "DATE_TYPE_FIX_TIME_RANGE") {
            if ($postdata['date_type'] == $dataInfo['date_type'] && $postdata['begin_time'] > $dataInfo['begin_date']) {
                throw new ResourceException(trans('KaquanBundle.update_card_start_time_error'));
            }
            if ($postdata['date_type'] == $dataInfo['date_type'] && $postdata['end_time'] < $dataInfo['end_date']) {
                throw new ResourceException(trans('KaquanBundle.update_card_end_time_error'));
            }
        }

        $postdata['company_id'] = $companyId;
        $authorizerAppid = app('auth')->user()->get('authorizer_appid');
        $result = $discountCardService->updateKaquan($postdata, $authorizerAppid);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/discountcard",
     *     summary="删除卡券",
     *     tags={"卡券"},
     *     description="删除卡券",
     *     operationId="deleteDiscountCard",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券 id",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function deleteDiscountCard(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'card_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除卡券出错.', $validator->errors());
        }

        $discountCardService = new KaquanService(new DiscountCardService());
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['card_id'] = $request->input('card_id');
        $authorizerAppid = app('auth')->user()->get('authorizer_appid');
        $result = $discountCardService->deleteKaquan($filter, $authorizerAppid);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/discountcard/get",
     *     summary="获取卡券明细",
     *     tags={"卡券"},
     *     description="获取卡券的详细信息",
     *     operationId="getDiscountCardDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券 id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/DiscountCard"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getDiscountCardDetail(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'card_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取卡券的详细信息出错.', $validator->errors());
        }

        $discountCardService = new KaquanService(new DiscountCardService());

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['card_id'] = $request->input('card_id');
        if ($request->get('distributor_id')) {
            $filter['distributor_id'] = ',' . $request->get('distributor_id') . ',';
        }
        $result = $discountCardService->getKaquanDetail($filter, true, true);

        if (!$result) {
            return $this->response->array(['status' => $result]);
        }

        if ($result['time_limit']) {
            $timeLimit = $result['time_limit'];
            unset($result['time_limit']);
            $begin = "";
            $end = "";
            foreach ($timeLimit as $k => $value) {
                $result['time_limit_type'][$k] = $value['type'];
                if (isset($value['begin_hour']) && isset($value['end_hour'])) {
                    if (!$begin && !$end) {
                        $begin = $value['begin_hour'] . ":" . $value['begin_minute'];
                        $end = $value['end_hour'] . ":" . $value['end_minute'];
                        $result['time_limit_date'][1] = ['begin_time' => $begin, 'end_time' => $end];
                        continue;
                    }
                    if ($begin !== $value['begin_hour'] . ":" . $value['begin_minute']) {
                        $begin2 = $value['begin_hour'] . ":" . $value['begin_minute'];
                        $end2 = $value['end_hour'] . ":" . $value['end_minute'];
                        $result['time_limit_date'][2] = ['begin_time' => $begin2, 'end_time' => $end2];
                    }
                }
            }
            if (isset($result['time_limit_type'])) {
                $result['time_limit_type'] = array_values(array_unique($result['time_limit_type']));
            }
        }

        //$result['date_type'] = $result['date_type'];
        $result['begin_time'] = intval($result['begin_date']);
        $result['days'] = isset($result['fixed_term']) ? intval($result['fixed_term']) : 30;
        $result['end_time'] = isset($result['end_date']) ? intval($result['end_date']) : 0;

        if (isset($result['discount']) && $result['discount'] > 0) {
            $result['discount'] = (100 - $result['discount']) / 10;
        }
        if (isset($result['least_cost']) && $result['least_cost']) {
            $result['least_cost'] = $result['least_cost'] / 100;
        }
        if (isset($result['reduce_cost']) && $result['reduce_cost']) {
            $result['reduce_cost'] = $result['reduce_cost'] / 100;
        }
        if (isset($result['most_cost']) && $result['most_cost']) {
            $result['most_cost'] = $result['most_cost'] / 100;
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/discountcard/list",
     *     summary="获取卡券列表",
     *     tags={"卡券"},
     *     description="获取卡券列表信息",
     *     operationId="getDiscountCardList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="status", in="query", description="卡券状态", required=false, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_type", in="query", description="卡券类型", required=false, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="title", in="query", description="卡券标题", required=false, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="from", in="query", description="请求来源", required=false, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_no", in="query", description="当前页数", required=false, type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="page_size", in="query", description="返回列表的数量，可选，默认 20, 取值在 1 到 20 之间", required=false, type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="list", type="array",
     *                      @SWG\Items(ref="#/definitions/DiscountCard")
     *                 ),
     *                 @SWG\Property( property="pagers", type="object",
     *                     @SWG\Property( property="total", type="string", example="591", description="总条数"),
     *                 ),
     *                 @SWG\Property( property="total_count", type="string", example="591", description="总条数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getDiscountCardList(Request $request)
    {
        // NOTE: important business logic
        $from = $request->input('from', 'menu');//请求来源：menu or btn
        $page = $request->input('page_no', 1);
        $count = $request->input('page_size', 20);
        $offset = ($page - 1) * $count;
        $filter = [];

        if ($request->input('status')) {
            $filter['status|in'] = $request->input('status');
        }
        if ($request->input('date_status')) {
            $filter['date_status'] = $request->input('date_status');
        }
        if ($request->input('card_type')) {
            if ($request->input('card_type') == 'all') {
                $filter['card_type'] = ['cash', 'discount', 'new_gift', 'money'];
            } else {
                $filter['card_type|in'] = $request->input('card_type');
            }
        } else {
            $filter['card_type'] = ['cash', 'discount', 'new_gift', 'money'];
        }
        if ($request->input('title')) {
            $filter['title|like'] = $request->input('title');
        }
        $store_self = $request->input('store_self');
        $sourceId = floatval($request->get('distributor_id', 0));//如果是平台，这里是0
        if ($store_self == "true") {//平台版仅支持自营商品【总店】
            $filter['or']['distributor_id|like'] = ',0,';
            $filter['or']['distributor_id|like'] = '%,%';
        } else {
            if ($request->get('distributor_id')) {
                $filter['distributor_id|like'] = ',' . $request->get('distributor_id') . ',';
            }
        }

        if ($request->input('receive')) {
            $filter['receive'] = $request->input('receive');
        }

        if ($from == 'btn') {
            // 如果来源是按钮出发，平台显示所有的券，店铺显示自己的券
            if ($sourceId > 0) {
                $filter['source_id'] = $sourceId;
            }
            $filter['end_date'] = time();//排除已过期的优惠券
        }

        $discountCardService = new KaquanService(new DiscountCardService());
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $discountCardService->getKaquanList($offset, $count, $filter);

        if ($result['list']) {
            $result = $this->__getSourceName($result);//获取店铺名称

            foreach ($result['list'] as $key => $value) {
                if ($value['date_type'] == "DATE_TYPE_FIX_TIME_RANGE" || $value['date_type'] == DiscountNewGiftCardService::DATE_TYPE_SHORT) {
                    $result['list'][$key]['begin_time'] = $value['begin_date'];
                    $result['list'][$key]['end_time'] = $value['end_date'];
                } elseif ($value['date_type'] == "DATE_TYPE_FIX_TERM" || $value['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                    $begin = $value['begin_day_type'] == 0 ? "当" : $value['begin_day_type'];
                    $result['list'][$key]['takeEffect'] = "领取后" . $begin . "天生效," . $value['fixed_term'] . "天有效";
                }
                $result['list'][$key]['operationType'] = "increase";
                $result['list'][$key]['storeValue'] = 1;
                $result['list'][$key]['storePop'] = false;

                if ($value['source_id'] != $sourceId) {
                    if ($value['source_type'] == 'staff' && $sourceId == 0) {
                        $result['list'][$key]['edit_btn'] = 'Y';//平台子账号创建的促销，超管可以编辑
                    } else {
                        $result['list'][$key]['edit_btn'] = 'N';//屏蔽编辑按钮，平台只能编辑自己的促销
                    }
                } else {
                    $result['list'][$key]['edit_btn'] = 'Y';
                }
            }
        }
        return $this->response->array($result);
    }

    private function __getSourceName($result = [])
    {
        $distributorIds = [];
        $sourceName = [
            'distributor' => []
        ];
        foreach ($result['list'] as $v) {
            if ($v['source_type'] == 'distributor') {
                $distributorIds[] = $v['source_id'];
            }
        }
        if ($distributorIds) {
            $distributorService = new DistributorService();
            $rs = $distributorService->getLists(['distributor_id' => $distributorIds], 'distributor_id,name');
            if ($rs) {
                $sourceName['distributor'] = array_column($rs, 'name', 'distributor_id');
            }
        }

        foreach ($result['list'] as $k => $v) {
            $source_name = '';
            if (isset($sourceName[$v['source_type']][$v['source_id']])) {
                $source_name = $sourceName[$v['source_type']][$v['source_id']];
            }
            $result['list'][$k]['source_name'] = $source_name;
        }
        return $result;
    }

    /**
     * @SWG\Get(
     *     path="/effectiveDiscountcard/list",
     *     summary="获取有效卡券列表",
     *     tags={"卡券"},
     *     description="获取有效卡券列表信息",
     *     operationId="getEffectiveDiscountCardList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_no", in="query", description="当前页数", required=false, type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="page_size", in="query", description="返回列表的数量，可选，默认 20, 取值在 1 到 20 之间", required=false, type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="list", type="array",
     *                      @SWG\Items(ref="#/definitions/DiscountCard")
     *                 ),
     *                 @SWG\Property( property="total_count", type="string", example="591", description="总条数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getEffectiveDiscountCardList(Request $request)
    {
        $page = $request->input('page_no', 1);
        $pageSize = $request->input('page_size', 20);
        $filter = [];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['source_id'] = app('auth')->user()->get('distributor_id');
        $filter['date_type'] = ['DATE_TYPE_FIX_TERM', DiscountNewGiftCardService::DATE_TYPE_LONG];
        $filter['end_date'] = time();
        if ($request->input('card_type') != 'all') {
            $filter['card_type'] = $request->input('card_type');
        }
        $discountCardService = new KaquanService(new DiscountCardService());
        $filter['company_id'] = app('auth')->user()->get('company_id');

        $result = $discountCardService->getEffectiveKaquanLists($page, $pageSize, $filter);
        if ($result['list']) {
            foreach ($result['list'] as $key => $value) {
                if ($value['date_type'] == "DATE_TYPE_FIX_TIME_RANGE" || $value['date_type'] == DiscountNewGiftCardService::DATE_TYPE_SHORT) {
                    $result['list'][$key]['begin_time'] = $value['begin_date'];
                    $result['list'][$key]['end_time'] = $value['end_date'];
                } elseif ($value['date_type'] == "DATE_TYPE_FIX_TERM" || $value['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                    $begin = $value['begin_date'] == 0 ? "当" : $value['begin_date'];
                    $result['list'][$key]['takeEffect'] = "领取后" . $begin . "天生效," . $value['fixed_term'] . "天有效";
                }
                $result['list'][$key]['operationType'] = "increase";
                $result['list'][$key]['storeValue'] = 1;
                $result['list'][$key]['storePop'] = false;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/discountcard/couponGrantSetting",
     *     summary="获取优惠券发放管理配置",
     *     tags={"卡券"},
     *     description="获取优惠券发放管理配置",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="coupons", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="limit_cycle", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="grant_per_user_total", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="grant_total", type="string", example="", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones")))
     * )
     */
    public function getCouponCardGrantSetting(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $redis_conn = app('redis')->connection('default');

        $result = $redis_conn -> hgetall('coupongrantset'.$company_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/discountcard/couponGrantSetting",
     *     summary="保存优惠券发放管理配置信息",
     *     tags={"卡券"},
     *     description="保存优惠券发放管理配置信息",
     *     operationId="only",
     *     @SWG\Parameter( in="formData", type="string", required=false, name="coupons", description="暂无字段描述" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="limit_cycle", description="限制周期" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="grant_per_user_total", description="可发放给客户的优惠券数" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="grant_total", description="可发放优惠券总数" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="ok", description="返回数据"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones")))
     * )
     */
    public function setCouponCardGrantSetting(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $redis_conn = app('redis')->connection('default');
        $request_data = $request -> input();
        if (!$request_data['coupons']) {
//            throw new ResourceException('请选择可发放优惠券');
            $request_data['coupons'] = '';
        }

        $data = [
            'coupons' => $request_data['coupons'] ?? '',
            'limit_cycle' => $request_data['limit_cycle'] ?? '',
            'grant_per_user_total' => $request_data['grant_per_user_total'] ?? '',
            'grant_total' => $request_data['grant_total'] ?? '',
        ];
        $result = $redis_conn -> hmset('coupongrantset'.$company_id, $data);

        if ($result) {
            return $this->response->array(['result' => 'ok']);
        } else {
            return $this->response->array(['result' => 'fail']);
        }
    }

    private function __doParams($postdata, $companyId = 0)
    {
        // 除了兑换券其他券目前没有线下使用的场景
        if ($postdata['card_type'] != 'gift') {
            $postdata['use_platform'] = 'mall';
            $postdata['use_scenes'] = 'ONLINE';
        }

        if ($postdata['card_type'] == "cash" && $postdata['useCondition'] == 2 && isset($postdata['least_cost']) && isset($postdata['reduce_cost'])) {
            if ($postdata['least_cost'] <= $postdata['reduce_cost']) {
                throw new ResourceException('代金券减免金额必须小于最低消费金额');
            }
        }
        if ($postdata['card_type'] == "discount" && $postdata['useCondition'] == 2 && isset($postdata['least_cost']) && isset($postdata['most_cost'])) {
            if (!is_numeric($postdata['least_cost'])) {
                throw new ResourceException('折扣券最低消费金额必须为数字');
            }
            if (!is_numeric($postdata['most_cost'])) {
                throw new ResourceException('折扣券最高限额必须为数字');
            }
            if ($postdata['least_cost'] < 0) {
                throw new ResourceException('折扣券最低消费金额必须大于等于0');
            }
            if ($postdata['least_cost'] >= $postdata['most_cost']) {
                throw new ResourceException('折扣券最高限额必须大于最低消费金额');
            }
        }
        if ($postdata['card_type'] == "money" && $postdata['useCondition'] == 2 && isset($postdata['least_cost']) && isset($postdata['reduce_cost'])) {
            if ($postdata['least_cost'] <= $postdata['reduce_cost']) {
                throw new ResourceException('现金券减免金额必须小于最低消费金额');
            }
        }

        if (isset($postdata['use_scenes']) && $postdata['use_scenes']) {
            if ($postdata['use_scenes'] == "SELF" && isset($postdata['self_consume_code'])) {
                $postdata['self_consume_code'] = intval($postdata['self_consume_code']);
            } else {
                $postdata['self_consume_code'] = 0;
            }
        }

        if ($postdata['use_all_shops'] == 'false') {
            if ($postdata['use_platform'] == 'store' && !$postdata['rel_shops_ids']) {
                throw new ResourceException('适用门店必填');
            }
            if ($postdata['use_platform'] == 'mall' && isset($postdata['distributor_ids']) && !$postdata['distributor_ids']) {
                throw new ResourceException('适用店铺必填');
            }
        }

        $postdata['distributor_id'] = isset($postdata['distributor_ids']) && is_array($postdata['distributor_ids']) ? $postdata['distributor_ids'] : [];
        if (isset($postdata['time_limit_type'])) {
            foreach ($postdata['time_limit_type'] as $value) {
                if (isset($postdata['time_limit_date'])) {
                    foreach ($postdata['time_limit_date'] as $date) {
                        list($beginHour, $beginMinute) = explode(':', $date['begin_time']);
                        list($endHour, $endMinute) = explode(':', $date['end_time']);
                        $postdata['time_limit'][] = [
                            'type' => $value,
                            'begin_hour' => $beginHour,
                            'begin_minute' => $beginMinute,
                            'end_hour' => $endHour,
                            'end_minute' => $endMinute,
                        ];
                    }
                } else {
                    $postdata['time_limit'][] = [
                        'type' => $value,
                    ];
                }
            }
            unset($postdata['time_limit_type'], $postdata['time_limit_date']);
        }

        if ($postdata['date_type'] == "DATE_TYPE_FIX_TIME_RANGE" && isset($postdata['begin_time'])) {
            $begin = $postdata['begin_time'];
            $end = $postdata['end_time'];
            if (!$postdata['card_id'] && $end < time()) {
                throw new ResourceException('有效期结束时间必须大于今天');
            }
            if ($begin > $end) {
                throw new ResourceException('有效期结束时间不可小于等于开始时间');
            }
        }
        $postdata['begin_time'] = isset($postdata['begin_time']) && $postdata['begin_time'] ? intval($postdata['begin_time']) : 0;
        if (isset($postdata['end_time']) && $postdata['end_time']) {
            $end = $postdata['end_time'];
            if (!$postdata['card_id'] && $end < time()) {
                throw new ResourceException('结束日期已过期，请重新确认');
            }
        } else {
            $postdata['end_time'] = 0;
        }
        if ($postdata['quantity'] == 0) {
            throw new ResourceException('发放数量至少为1份');
        }
        //优惠券规则ID重复
        if (isset($postdata['card_code']) && !empty($postdata['card_code'])) {
            $filter['card_code'] = $postdata['card_code'];
            $filter['company_id'] = $companyId;
            $card_rule_code = '';
            if (isset($postdata['card_rule_code']) && !empty($postdata['card_rule_code'])) {
                $card_rule_code = $postdata['card_rule_code'];
                $filter['card_rule_code'] = $postdata['card_rule_code'];
            }
            $discountCardService = new DiscountCardService();
            $dataInfo = $discountCardService->discountCardRepository->getList(['card_id,title,card_type,card_code,card_rule_code'], $filter);
            foreach ($dataInfo as $key => $value) {
                if ($card_rule_code == '') {//优惠券规则ID为空的只能有一条
                    if (empty($value['card_rule_code']) == empty($card_rule_code) && $postdata['card_id'] != $value['card_id']) {
                        throw new ResourceException('优惠券规则ID重复');
                    }
                }
                if (empty($value['card_rule_code']) == empty($card_rule_code) && $postdata['card_id'] != $value['card_id']) {
                    throw new ResourceException('优惠券规则ID重复');
                }
            }
        }
        if (!isset($postdata['card_rule_code'])) {
            $postdata['card_rule_code'] = '';
        }
        if (isset($postdata['get_limit']) && $postdata['get_limit'] <= 0) {
            $postdata['get_limit'] = 1;
        }

        if (isset($postdata['use_all_items']) && $postdata['use_all_items'] == 'forbid') {
            $postdata['use_all_items'] = 'false';
        }

        return $postdata;
    }

    /**
     * @SWG\Post(
     *     path="/discountcard/updatestore",
     *     summary="修改卡券库存",
     *     tags={"卡券"},
     *     description="根据 card_id 修改卡券库存",
     *     operationId="updateCardStore",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券 card_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="修改库存方式(increase,reduce)",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="quantity",
     *         in="query",
     *         description="库存数量",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function updateCardStore(Request $request)
    {
        $cardId = $request->input('card_id');
        $store = $request->input('quantity');
        if ($store < 0) {
            throw new ResourceException('修改库存出错,请输入大于0的整数');
        }
        $type = $request->input('type');
        $companyId = app('auth')->user()->get('company_id');
        $kaquanService = new KaquanService(new DiscountCardService());
        $authorizerAppid = app('auth')->user()->get('authorizer_appid');
        $result = $kaquanService->updateStock($type, $cardId, $store, $companyId, $authorizerAppid);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/discountcard/uploadToWechat",
     *     summary="卡券推送至微信",
     *     tags={"卡券"},
     *     description="批量推送卡券至微信",
     *     operationId="uploadToWechatCard",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_ids",
     *         in="query",
     *         description="卡券 card_ids",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="返回数据"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function uploadToWechatCard(Request $request)
    {
        $cardId = $request->input('card_ids');
        if (count($cardId) <= 0) {
            throw new ResourceException('请选择要同步至微信的卡券');
        }
        $companyId = app('auth')->user()->get('company_id');
        $authorizerAppid = app('auth')->user()->get('authorizer_appid');
        $kaquanService = new KaquanService(new DiscountCardService());
        $result = $kaquanService->uploadCard($authorizerAppid, $companyId, $cardId);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/discountcard/listdata",
     *     summary="获取优惠券列表",
     *     tags={"卡券"},
     *     description="获取优惠券列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="card_type", description="卡券类型" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pageSize", description="条数" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="card_id", type="string", example="590", description="卡券id"),
     *                  @SWG\Property( property="title", type="string", example="代金10元", description="标题"),
     *                  @SWG\Property( property="card_type", type="string", example="cash", description="绑卡类型"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones")))
     * )
     */
    public function getEasyDiscountList(Request $request)
    {
        if ($cardType = $request->get('card_type')) {
            $filter['card_type'] = $cardType;
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $orderBy = ['end_date' => 'asc', 'card_id' => 'desc'];
        $kaquanService = new KaquanService(new DiscountCardService());
        $result = $kaquanService->getLists($filter, 'card_id, title, card_type', $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/discountcard/consume",
     *     summary="兑换券核销",
     *     tags={"卡券"},
     *     description="扫码核销兑换券",
     *     @SWG\Parameter( in="query", type="string", required=true, name="distributor_id", description="店铺id" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="code", description="兑换券code" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="", description="状态 true|false"),
     *                  @SWG\Property( property="order_info", type="object", example="", description="status:true 返回订单数据",
     *                      @SWG\Property( property="items", type="array", example="", description="商品信息",
     *                          @SWG\Items( type="object",
     *                              @SWG\Property( property="pic", type="string", description="商品图片"),
     *                              @SWG\Property( property="item_name", type="string", description="商品名"),
     *                              @SWG\Property( property="item_spec_desc", type="string", description="商品格式"),
     *                              @SWG\Property( property="num", type="string", description="商品数量"),
     *                          )
     *                      ),
     *                  ),
     *                  @SWG\Property( property="distributors", type="array", example="", description="status:false 返回店铺列表",
     *                          @SWG\Items( type="object",
     *                           @SWG\Property( property="name", type="string", description="店铺名称"),
     *                           @SWG\Property( property="logo", type="string", description="店铺logo"),
     *                           @SWG\Property( property="is_center", type="string", description="是否总店"),
     *                          )
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones")))
     * )
     */
    public function consumeExCard(Request $request)
    {
        $code = $request->input('code');
        $user = app('auth')->user();
        $companyId = $user->get('company_id');

        list($userCardId, $code) = explode('-', $code, 2);
        if (!$userCardId || !$code) {
            throw new ResourceException('code 错误');
        }

        try {
            $userDiscountService = new UserDiscountService();
            $orderData = $userDiscountService->consumeExCard($companyId, $userCardId, $code, $user, $request->input('distributor_id'));
            return [
                'status' => true,
                'order_info' => $orderData
            ];
        } catch (\Exception $e) {
            // 业务
            if ($e instanceof ResourceException) {
                // 错误处理
                $filter = [
                    'id' => $userCardId,
                    'company_id' => $companyId,
                ];
                $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
                /** @var UserDiscount $userCard */
                $userCard = $userDiscountRepository->get($filter);
                if (!$userCard || $userCard->getStatus() != 10) {
                    throw new ResourceException('核销码不存在或有误，请检查');
                }
                // 获取优惠券可用的店铺列表
                $discountCardService = new KaquanService(new CardService());
                $cardInfo = $discountCardService->getCardInfoById($companyId, $userCard->getCardId());
                $distributorService = new DistributorService();
                $rel_distributor_ids = trim($cardInfo['distributor_id'], ',');
                if (!$rel_distributor_ids) {
                    $data = $distributorService->lists(['company_id' => $companyId], ["created" => "DESC"], 4);
                    $selfInfo = $distributorService->getDistributorSelfSimpleInfo($companyId);
                    $selfInfo['is_center'] = true;
                    array_unshift($data['list'], $selfInfo);
                    $data['total_count'] += 1;
                } else {
                    $filter = [
                        'company_id' => $companyId,
                        'distributor_id' => explode(',', $rel_distributor_ids),
                    ];
                    $data = $distributorService->lists($filter, ["created" => "DESC"], 5);
                }
                return [
                    'status' => false,
                    'message' => $e->getMessage(),
                    'distributors' => $data,
                ];
            }
            throw $e;
        }
    }
}
