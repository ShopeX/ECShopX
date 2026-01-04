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

use PromotionsBundle\Services\ExtraPointActivityService;

use Dingo\Api\Exception\StoreResourceFailedException;
use DistributionBundle\Services\DistributorService;

class ExtraPointActivity extends Controller
{
    /**
     * @SWG\Definition(
     * definition="ExtraPointDetail",
     * type="object",
     * @SWG\Property( property="activity_id", type="string", example="3", description="活动ID"),
     * @SWG\Property( property="type", type="string", example="shop", description="营销类型: shop:店铺额外积分,birthday:会员生日,item:商品额外积分"),
     * @SWG\Property( property="title", type="string", example="积分翻倍", description="活动名称"),
     * @SWG\Property( property="begin_time", type="string", example="1611158400", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611331199", description="活动结束时间"),
     * @SWG\Property( property="trigger_condition", type="object",
     *     @SWG\Property( property="trigger_amount", type="string", example="100", description="满足条件(元)"),
     *     @SWG\Property( property="trigger_time", type="object", description="日期数据",
     *         @SWG\Property( property="type", type="string", example="every_week", description="类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段"),
     *         @SWG\Property( property="month", type="string", example="", description="月份"),
     *         @SWG\Property( property="week", type="string", example="4", description="星期值"),
     *         @SWG\Property( property="day", type="string", example="", description="日期"),
     *         @SWG\Property( property="begin_time", type="string", example="", description="开始时间"),
     *         @SWG\Property( property="end_time", type="string", example="", description="结束时间"),
     *     ),
     * ),
     * @SWG\Property( property="condition_value", type="string", example="3", description="优惠配置"),
     * @SWG\Property( property="condition_type", type="string", example="multiple", description="优惠方式: multiple:倍数, plus:增加"),
     * @SWG\Property( property="use_shop", type="string", example="1", description="是否指定店铺适用"),
     * @SWG\Property( property="valid_grade", type="array",
     *     @SWG\Items( type="string", example="4", description="会员等级id"),
     * ),
     * @SWG\Property( property="shopids", type="array",
     *     @SWG\Items( type="string", example="1", description="店铺id"),
     * ),
     * @SWG\Property( property="created", type="string", example="1611216662", description=""),
     * @SWG\Property( property="updated", type="string", example="1611216662", description=""),
     * @SWG\Property( property="begin_date", type="string", example="2021-01-21", description="有效期开始时间"),
     * @SWG\Property( property="end_date", type="string", example="2021-01-22", description="有效期结束时间"),
     * @SWG\Property( property="activity_status", type="string", example="2021-01-22", description="活动状态 ready:未开始，processing:进行中，end:已结束"),
     * )
     */

    /**
     * @SWG\Put(
     *     path="/promotions/extrapoint/invalid",
     *     summary="将当前额外积分活动失效",
     *     tags={"营销"},
     *     description="将当前额外积分活动失效",
     *     operationId="updateStatusInvalid",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/ExtraPointDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateStatusInvalid(Request $request)
    {
        $id = $request->input('activity_id');
        $companyId = app('auth')->user()->get('company_id');
        $extraPointActivityService = new ExtraPointActivityService();
        $filter = [
            'company_id' => $companyId,
            'activity_id' => $id
        ];
        $result = $extraPointActivityService->updateOneBy($filter, ['activity_status' => 'invalid']);
        return $this->response->array(['data' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/extrapoint/create",
     *     summary="创建额外积分活动",
     *     tags={"营销"},
     *     description="创建额外积分活动",
     *     operationId="createActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][type]", in="formData", description="活动触发条件-类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][month]", in="formData", description="活动触发条件-月份", required=false, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][week]", in="formData", description="日期数据,星期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][day]", in="formData", description="日期数据,日期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][begin_time]", in="formData", description="日期数据,开始时间", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][end_time]", in="formData", description="日期数据,结束时间", type="string", required=false),
     *     @SWG\Parameter( name="condition_type", in="formData", description="优惠方式: multiple:倍数, plus:增加", required=true, type="string"),
     *     @SWG\Parameter( name="condition_value", in="formData", description="优惠配置", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_amount", in="formData", description="满足条件（元）", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade", in="formData", description="会员等级集合数组", type="string"),
     *     @SWG\Parameter( name="use_shop", in="formData", description="是否为指定店铺", type="string"),
     *     @SWG\Parameter( name="shop_ids", in="formData", description="店铺id数组", type="string"),
     *     @SWG\Parameter( name="is_forever", in="formData", description="是否永久有效", required=true, type="string"),
     *     @SWG\Parameter( name="begin_time", in="formData", description="活动开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="活动结束时间", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/ExtraPointDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createActivity(Request $request)
    {
        $data = $request->input();
        if (!$data['title'] || mb_strlen($data['title']) > 20) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.activity_name_required_max_20'));
        }
        if (!$data['condition_value']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.set_activity_point_multiplier'));
        }
        if ($data['condition_value'] < 1) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.set_correct_point_multiplier'));
        }
        if (isset($data['is_forever']) && $data['is_forever'] == 'true') {
            $data['begin_time'] = time();
            $data['end_time'] = '5000000000';
            unset($data['is_forever']);
        } else {
            if (!$data['begin_time'] || !$data['end_time']) {
                throw new StoreResourceFailedException(trans('PromotionsBundle.fill_activity_time'));
            }
            $data['begin_time'] = strtotime($data['begin_time']);
            $data['end_time'] = strtotime($data['end_time']);

            if ($data['end_time'] <= time()) {
                throw new StoreResourceFailedException(trans('PromotionsBundle.select_valid_time'));
            }
        }
        $companyId = app('auth')->user()->get('company_id');
        $data['company_id'] = $companyId;
        $extraPointActivityService = new ExtraPointActivityService();
        $result = $extraPointActivityService->createActivity($data);

        return $this->response->array(['data' => $result]);
    }


    /**
     * @SWG\Post(
     *     path="/promotions/extrapoint",
     *     summary="修改额外积分活动",
     *     tags={"营销"},
     *     description="修改额外积分活动",
     *     operationId="updateActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动id", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][type]", in="formData", description="活动触发条件-类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][month]", in="formData", description="活动触发条件-月份", required=false, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][week]", in="formData", description="日期数据,星期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][day]", in="formData", description="日期数据,日期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][begin_time]", in="formData", description="日期数据,开始时间", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][end_time]", in="formData", description="日期数据,结束时间", type="string", required=false),
     *     @SWG\Parameter( name="condition_type", in="formData", description="优惠方式: multiple:倍数, plus:增加", required=true, type="string"),
     *     @SWG\Parameter( name="condition_value", in="formData", description="优惠配置", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_amount", in="formData", description="满足条件（元）", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade", in="formData", description="会员等级集合数组", type="string"),
     *     @SWG\Parameter( name="use_shop", in="formData", description="是否为指定店铺", type="string"),
     *     @SWG\Parameter( name="shop_ids", in="formData", description="店铺id数组", type="string"),
     *     @SWG\Parameter( name="is_forever", in="formData", description="是否永久有效", required=true, type="string"),
     *     @SWG\Parameter( name="begin_time", in="formData", description="活动开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="活动结束时间", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/ExtraPointDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateActivity(Request $request)
    {
        $data = $request->input();
        if (!$data['title'] || mb_strlen($data['title']) > 20) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.activity_name_max_20'));
        }
        if ($data['condition_value'] < 1) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.set_correct_point_multiplier'));
        }
        if (isset($data['is_forever']) && $data['is_forever'] == 'true') {
            $data['begin_time'] = time();
            $data['end_time'] = '5000000000';
            unset($data['is_forever']);
        } else {
            if (!$data['begin_time'] || !$data['end_time']) {
                throw new StoreResourceFailedException(trans('PromotionsBundle.fill_activity_time'));
            }
            $data['begin_time'] = strtotime($data['begin_time']);
            $data['end_time'] = strtotime($data['end_time']);
            if ($data['end_time'] <= time()) {
                throw new StoreResourceFailedException(trans('PromotionsBundle.select_valid_time'));
            }
        }

        $companyId = app('auth')->user()->get('company_id');
        $data['company_id'] = $companyId;

        $extraPointActivityService = new ExtraPointActivityService();
        $result = $extraPointActivityService->createActivity($data);

        return $this->response->array(['data' => $result]);
    }


    /**
     * @SWG\Get(
     *     path="/promotions/extrapoint/lists",
     *     summary="获取活动列表",
     *     tags={"营销"},
     *     description="获取活动列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="begin_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="string", example="3", description="总条数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         ref="#/definitions/ExtraPointDetail"
     *                     ),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $extraPointActivityService = new ExtraPointActivityService();
        $params = $request->input();
        $filter['company_id'] = $companyId;
        if ($params['begin_time'] ?? 0) {
            $filter['begin_time|gte'] = $params['begin_time'];
            $filter['end_time|lte'] = $params['end_time'];
        }
        if ($params['title'] ?? 0) {
            $filter['title|contains'] = $params['title'];
        }

        $orderBy = ["created" => "DESC"];
        $pageSize = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $result = $extraPointActivityService->lists($filter, $orderBy, $pageSize, $page);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/extrapoint/{activity_id}",
     *     summary="获取额外积分详情",
     *     tags={"营销"},
     *     description="获取额外积分详情",
     *     operationId="getActivityInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="path", description="活动ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/ExtraPointDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityInfo(Request $request, $id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = intval($id);

        if (!$id) {
            return $this->response->array([]);
        }

        $extraPointActivityService = new ExtraPointActivityService();
        $result = $extraPointActivityService->getActivityInfo($id);
        if ($result['shop_ids']) {
            $distributorService = new DistributorService();
            $filter = [
                'company_id' => $companyId,
                'distributor_id' => array_filter($result['shop_ids']),
            ];
            $result['storeLists'] = $distributorService->lists($filter)['list'];
        }
        return $this->response->array($result);
    }
}
