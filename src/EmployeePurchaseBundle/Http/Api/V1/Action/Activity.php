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

namespace EmployeePurchaseBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Ego\CompanysActivationEgo;

use EmployeePurchaseBundle\Services\ActivitiesService;
use GoodsBundle\Services\ItemsCategoryService;

class Activity extends Controller
{
    /**
     * @SWG\Definition(
     *     definition="ActivityDetail",
     *     type="object",
     *     @SWG\Property( property="id", type="integer", description="活动ID"),
     *     @SWG\Property( property="company_id", type="integer", description="公司ID"),
     *     @SWG\Property( property="name", type="string", description="活动名称"),
     *     @SWG\Property( property="title", type="string", description="活动标题"),
     *     @SWG\Property( property="pages_template_id", type="integer", description="活动首页关联模版"),
     *     @SWG\Property( property="share_pic", type="string", description="活动分享图片"),
     *     @SWG\Property( property="enterprise_id", type="array", description="参与企业", @SWG\Items(type="integer")),
     *     @SWG\Property( property="display_time", type="integer", description="活动预热时间"),
     *     @SWG\Property( property="employee_begin_time", type="integer", description="员工购买开始时间"),
     *     @SWG\Property( property="employee_end_time", type="integer", description="员工购买结束时间"),
     *     @SWG\Property( property="employee_limitfee", type="integer", description="员工可使用额度"),
     *     @SWG\Property( property="if_relative_join", type="boolean", description="亲友是否参与活动"),
     *     @SWG\Property( property="invite_limit", type="integer", description="员工可邀请亲友人数上限"),
     *     @SWG\Property( property="relative_begin_time", type="integer", description="亲友购买开始时间"),
     *     @SWG\Property( property="relative_end_time", type="integer", description="亲友购买结束时间"),
     *     @SWG\Property( property="if_share_limitfee", type="boolean", description="亲友是否共享员工额度"),
     *     @SWG\Property( property="relative_limitfee", type="integer", description="亲友可使用额度"),
     *     @SWG\Property( property="minimum_amount", type="integer", description="订单最低金额"),
     *     @SWG\Property( property="close_modify_hours_after_activity", type="integer", description="活动结束后多少小时内可以修改收货地址"),
     *     @SWG\Property( property="created", type="integer", description="创建时间"),
     *     @SWG\Property( property="updated", type="integer", description="修改时间"),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity",
     *     summary="创建员工内购活动",
     *     tags={"内购"},
     *     description="创建员工内购活动",
     *     operationId="createActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="活动名称", type="string", required=true),
     *     @SWG\Parameter( name="title", in="formData", description="活动标题", type="string", required=true),
     *     @SWG\Parameter( name="pages_template_id", in="formData", description="活动首页关联模版", type="integer", required=true),
     *     @SWG\Parameter( name="share_pic", in="formData", description="活动分享图片", type="string", required=true),
     *     @SWG\Parameter( name="enterprise_id[]", in="formData", description="参与企业", type="integer", required=true),
     *     @SWG\Parameter( name="display_time", in="formData", description="活动预热时间", type="integer", required=true),
     *     @SWG\Parameter( name="employee_begin_time", in="formData", description="员工购买开始时间", type="integer", required=true),
     *     @SWG\Parameter( name="employee_end_time", in="formData", description="员工购买结束时间", type="integer", required=true),
     *     @SWG\Parameter( name="employee_limitfee", in="formData", description="员工可使用额度", type="integer", required=true),
     *     @SWG\Parameter( name="if_relative_join", in="formData", description="亲友是否参与活动", type="integer", required=true),
     *     @SWG\Parameter( name="invite_limit", in="formData", description="员工可邀请亲友人数上限", type="integer", required=false),
     *     @SWG\Parameter( name="relative_begin_time", in="formData", description="亲友购买开始时间", type="integer", required=false),
     *     @SWG\Parameter( name="relative_end_time", in="formData", description="亲友购买结束时间", type="integer", required=false),
     *     @SWG\Parameter( name="if_share_limitfee", in="formData", description="亲友是否共享员工额度", type="integer", required=false),
     *     @SWG\Parameter( name="relative_limitfee", in="formData", description="亲友可使用额度", type="integer", required=false),
     *     @SWG\Parameter( name="minimum_amount", in="formData", description="订单最低金额", type="integer", required=true),
     *     @SWG\Parameter( name="close_modify_hours_after_activity", in="formData", description="活动结束后多少小时内可以修改收货地址", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 ref="#/definitions/ActivityDetail"
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function createActivity(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $distributor_id = $authInfo['distributor_id'];
        $operator_id = $authInfo['operator_id'];
        $params = $request->all('name', 'title', 'pages_template_id', 'pic', 'share_pic', 'enterprise_id', 'display_time', 'employee_begin_time', 'employee_end_time', 'employee_limitfee', 'if_relative_join', 'invite_limit', 'relative_begin_time', 'relative_end_time', 'if_share_limitfee', 'relative_limitfee', 'minimum_amount', 'close_modify_hours_after_activity', 'price_display_config', 'is_discount_description_enabled', 'discount_description');
        $params['company_id'] = $companyId;
        // 处理布尔值参数：支持字符串 'true'/'1' 和整数 1/0
        $params['if_relative_join'] = isset($params['if_relative_join']) && ($params['if_relative_join'] === 'true' || $params['if_relative_join'] === '1' || $params['if_relative_join'] === 1 || $params['if_relative_join'] === true) ? 1 : 0;
        $params['if_share_limitfee'] = isset($params['if_share_limitfee']) && ($params['if_share_limitfee'] === 'true' || $params['if_share_limitfee'] === '1' || $params['if_share_limitfee'] === 1 || $params['if_share_limitfee'] === true) ? 1 : 0;
        $params['is_discount_description_enabled'] = isset($params['is_discount_description_enabled']) && ($params['is_discount_description_enabled'] === 'true' || $params['is_discount_description_enabled'] === '1' || $params['is_discount_description_enabled'] === 1 || $params['is_discount_description_enabled'] === true) ? 1 : 0;
        $rules = [
            'name' => ['required', '请输入活动名称'],
            'title' => ['required', '请输入活动标题'],
            'pages_template_id' => ['required', '请选择活动首页关联模版'],
            'pic' => ['required', '请上传活动图片'],
            'share_pic' => ['required', '请上传活动分享图片'],
            'enterprise_id' => ['required', '请选择参与企业'],
            'display_time' => ['required', '请选择活动预热时间'],
            'employee_begin_time' => ['required', '请选择员工购买开始时间'],
            'employee_end_time' => ['required', '请选择员工购买结束时间'],
            'employee_limitfee' => ['required', '请输入员工可使用额度'],
            'invite_limit' => ['required_if:if_relative_join,1', '请输入员工可邀请亲友人数上限'],
            'relative_begin_time' => ['required_if:if_relative_join,1', '请选择亲友购买开始时间'],
            'relative_end_time' => ['required_if:if_relative_join,1', '请选择亲友购买结束时间'],
            'if_share_limitfee' => ['required_if:if_relative_join,1', '请选择亲友是否共享员工额度'],
            'relative_limitfee' => ['required_if:if_share_limitfee,0', '请填写亲友可使用额度'],
            'minimum_amount' => ['required', '请填写订单最低金额'],
            'close_modify_hours_after_activity' => ['required', '请填写活动结束后多少小时内可以修改收货地址'],
            'price_display_config' => ['required', '请设置活动价格展示'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if ($params['display_time'] > $params['employee_begin_time']) {
            throw new ResourceException('预热时间不能晚于员工开始购买时间');
        }

        if ($params['if_relative_join'] && $params['display_time'] > $params['relative_begin_time']) {
            throw new ResourceException('预热时间不能晚于家属开始购买时间');
        }
        $params['distributor_id'] = $distributor_id;
        $params['operator_id'] = $operator_id;
        $params['price_display_config'] = json_decode($params['price_display_config'], true);
        if ($params['discount_description'] == null) $params['discount_description'] = "";
        $activitiesService = new ActivitiesService();
        $result = $activitiesService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/employeepurchase/activities",
     *     summary="获取员工内购活动列表",
     *     tags={"内购"},
     *     description="获取员工内购活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="活动名称", required=false, type="string"),
     *     @SWG\Parameter( name="display_time_begin", in="query", description="预热时间", type="integer"),
     *     @SWG\Parameter( name="display_time_end", in="query", description="预热时间", type="integer"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="参与企业ID", type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ActivityDetail"
     *                       ),
     *                  ),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'name', 'display_time_begin', 'buy_time_begin', 'buy_time_end', 'enterprise_id', 'status', 'distributor_id');
        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页显示数量最大100'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);
        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $filter = ['company_id' => $companyId];
        if ($authInfo['operator_type'] == 'distributor') {
            $filter['distributor_id'] = $authInfo['distributor_id'];
        } else {
            if (isset($params['distributor_id']) && $params['distributor_id'] != '') {
                $filter['distributor_id'] = $params['distributor_id'];
            }
        }
        if ($params['name']) {
            $filter['name|contains'] = $params['name'];
        }
        if ($params['display_time_begin']) {
            $filter['display_time|gt'] = $params['display_time_begin'];
        }
        if ($params['buy_time_begin']) {
            $filter['buy_time']['begin'] = $params['buy_time_begin'];
        }
        if ($params['buy_time_end']) {
            $filter['buy_time']['end'] = $params['buy_time_end'];
        }
        if ($params['enterprise_id']) {
            $filter['enterprise_id'] = $params['enterprise_id'];
        }
        $now = time();
        if ($params['status']) {
            switch ($params['status']) {
                //未开始
                case 'not_started':
                    $filter['display_time|gt'] = $now;
                    $filter['status'] = 'active';
                    break;
                //预热中
                case 'warm_up':
                    $filter['status'] = 'warm_up';
                    break;
                //进行中
                case 'ongoing':
                    $filter['status'] = 'ongoing';
                    break;
                //已暂停
                case 'pending':
                    $filter['status'] = 'pending';
                    break;
                //已取消
                case 'cancel':
                    $filter['status'] = 'cancel';
                    break;
                //已结束
                case 'over':
                    $filter['status'] = 'over';
            }
        }

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getActivityList($filter, '*', $page, $pageSize);

        foreach ($result['list'] as $key => $row) {
            if ($row['display_time'] > $now && $row['status'] == 'active') {
                $result['list'][$key]['status'] = 'not_started';
                $result['list'][$key]['status_desc'] = '未开始';
            }
            if ($row['display_time'] < $now && $row['employee_begin_time'] > $now && ($row['relative_begin_time'] == 0 || $row['relative_begin_time'] > $now) && $row['status'] == 'active') {
                $result['list'][$key]['status'] = 'warm_up';
                $result['list'][$key]['status_desc'] = '预热中';
            }
            if (($row['employee_begin_time'] < $now || ($row['relative_begin_time'] > 0 && $row['relative_begin_time'] < $now)) && ($row['employee_end_time'] > $now || $row['relative_end_time'] > $now) && $row['status'] == 'active') {
                $result['list'][$key]['status'] = 'ongoing';
                $result['list'][$key]['status_desc'] = '进行中';
            }
            if (($row['employee_begin_time'] < $now || ($row['relative_begin_time'] > 0 && $row['relative_begin_time'] < $now)) && ($row['employee_end_time'] > $now || $row['relative_end_time'] > $now) && $row['status'] == 'pending') {
                $result['list'][$key]['status'] = 'pending';
                $result['list'][$key]['status_desc'] = '已暂停';
            }
            if ($row['status'] == 'cancel') {
                $result['list'][$key]['status'] = 'cancel';
                $result['list'][$key]['status_desc'] = '已取消';
            }
            if (($row['employee_end_time'] < $now && $row['relative_end_time'] < $now) || $row['status'] == 'over') {
                $result['list'][$key]['status'] = 'over';
                $result['list'][$key]['status_desc'] = '已结束';
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/employeepurchase/activity/{activityId}",
     *     summary="获取员工内购活动详情",
     *     tags={"内购"},
     *     description="获取员工内购活动详情",
     *     operationId="getActivityInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/ActivityDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityInfo($activityId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $activityId;
        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getInfo($filter);

        $now = time();
        if ($result['display_time'] > $now && $result['status'] == 'active') {
            $result['status'] = 'not_started';
            $result['status_desc'] = '未开始';
        }
        if ($result['display_time'] < $now && $result['employee_begin_time'] > $now && $result['relative_begin_time'] > $now && $result['status'] == 'active') {
            $result['status'] = 'warm_up';
            $result['status_desc'] = '预热中';
        }
        if (($result['employee_begin_time'] < $now || $result['relative_begin_time'] < $now) && ($result['employee_end_time'] > $now || $result['relative_end_time'] > $now) && $result['status'] == 'active') {
            $result['status'] = 'ongoing';
            $result['status_desc'] = '进行中';
        }
        if (($result['employee_begin_time'] < $now || $result['relative_begin_time'] < $now) && ($result['employee_end_time'] > $now || $result['relative_end_time'] > $now) && $result['status'] == 'pending') {
            $result['status'] = 'pending';
            $result['status_desc'] = '已暂停';
        }
        if ($result['status'] == 'cancel') {
            $result['status'] = 'cancel';
            $result['status_desc'] = '已取消';
        }
        if (($result['employee_end_time'] < $now && $result['relative_end_time'] < $now) || $result['status'] == 'over') {
            $result['status'] = 'over';
            $result['status_desc'] = '已结束';
        }
        $result['is_discount_description_enabled'] = $result['is_discount_description_enabled'] === true ? 'true' : 'false';
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/employeepurchase/activity/{activityId}",
     *     summary="更新员工内购活动",
     *     tags={"内购"},
     *     description="更新员工内购活动",
     *     operationId="updateActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="活动名称", type="string", required=true),
     *     @SWG\Parameter( name="title", in="formData", description="活动标题", type="string", required=true),
     *     @SWG\Parameter( name="pages_template_id", in="formData", description="活动首页关联模版", type="integer", required=true),
     *     @SWG\Parameter( name="share_pic", in="formData", description="活动分享图片", type="string", required=true),
     *     @SWG\Parameter( name="enterprise_id[]", in="formData", description="参与企业", type="integer", required=true),
     *     @SWG\Parameter( name="display_time", in="formData", description="活动预热时间", type="integer", required=true),
     *     @SWG\Parameter( name="employee_begin_time", in="formData", description="员工购买开始时间", type="integer", required=true),
     *     @SWG\Parameter( name="employee_end_time", in="formData", description="员工购买结束时间", type="integer", required=true),
     *     @SWG\Parameter( name="employee_limitfee", in="formData", description="员工可使用额度", type="integer", required=true),
     *     @SWG\Parameter( name="if_relative_join", in="formData", description="亲友是否参与活动", type="integer", required=true),
     *     @SWG\Parameter( name="invite_limit", in="formData", description="员工可邀请亲友人数上限", type="integer", required=false),
     *     @SWG\Parameter( name="relative_begin_time", in="formData", description="亲友购买开始时间", type="integer", required=false),
     *     @SWG\Parameter( name="relative_end_time", in="formData", description="亲友购买结束时间", type="integer", required=false),
     *     @SWG\Parameter( name="if_share_limitfee", in="formData", description="亲友是否共享员工额度", type="integer", required=false),
     *     @SWG\Parameter( name="relative_limitfee", in="formData", description="亲友可使用额度", type="integer", required=false),
     *     @SWG\Parameter( name="minimum_amount", in="formData", description="订单最低金额", type="integer", required=true),
     *     @SWG\Parameter( name="close_modify_hours_after_activity", in="formData", description="活动结束后多少小时内可以修改收货地址", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 ref="#/definitions/ActivityDetail"
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function updateActivity($activityId, Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $distributor_id = $authInfo['distributor_id'];
        $operator_id = $authInfo['operator_id'];
        $params = $request->all('name', 'title', 'pages_template_id', 'pic', 'share_pic', 'enterprise_id', 'display_time', 'employee_begin_time', 'employee_end_time', 'employee_limitfee', 'if_relative_join', 'invite_limit', 'relative_begin_time', 'relative_end_time', 'if_share_limitfee', 'relative_limitfee', 'minimum_amount', 'close_modify_hours_after_activity', 'price_display_config', 'is_discount_description_enabled', 'discount_description',);
        $params['if_relative_join'] = isset($params['if_relative_join']) && ($params['if_relative_join'] === 'true' || $params['if_relative_join'] === '1') ? 1 : 0;
        $params['if_share_limitfee'] = isset($params['if_share_limitfee']) && ($params['if_share_limitfee'] === 'true' || $params['if_share_limitfee'] === '1') ? 1 : 0;
        $params['is_discount_description_enabled'] = isset($params['is_discount_description_enabled']) && ($params['is_discount_description_enabled'] === 'true' || $params['is_discount_description_enabled'] === '1' || $params['is_discount_description_enabled'] === 1 || $params['is_discount_description_enabled'] === true) ? 1 : 0;
        $rules = [
            'name' => ['required', '请输入活动名称'],
            'title' => ['required', '请输入活动标题'],
            'pages_template_id' => ['required', '请选择活动首页关联模版'],
            'pic' => ['required', '请上传活动图片'],
            'share_pic' => ['required', '请上传活动分享图片'],
            'enterprise_id' => ['required', '请选择参与企业'],
            'display_time' => ['required', '请选择活动预热时间'],
            'employee_begin_time' => ['required', '请选择员工购买开始时间'],
            'employee_end_time' => ['required', '请选择员工购买结束时间'],
            'employee_limitfee' => ['required', '请输入员工可使用额度'],
            'invite_limit' => ['required_if:if_relative_join,1', '请输入员工可邀请亲友人数上限'],
            'relative_begin_time' => ['required_if:if_relative_join,1', '请选择亲友购买开始时间'],
            'relative_end_time' => ['required_if:if_relative_join,1', '请选择亲友购买结束时间'],
            'if_share_limitfee' => ['required_if:if_relative_join,1', '请选择亲友是否共享员工额度'],
            'relative_limitfee' => ['required_if:if_share_limitfee,0', '请填写亲友可使用额度'],
            'minimum_amount' => ['required', '请填写订单最低金额'],
            'close_modify_hours_after_activity' => ['required', '请填写活动结束后多少小时内可以修改收货地址'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if ($params['display_time'] > $params['employee_begin_time']) {
            throw new ResourceException('预热时间不能晚于员工开始购买时间');
        }

        if ($params['if_relative_join'] && $params['display_time'] > $params['relative_begin_time']) {
            throw new ResourceException('预热时间不能晚于家属开始购买时间');
        }

        $filter['company_id'] = $companyId;
        $filter['id'] = $activityId;
        $params['distributor_id'] = $distributor_id;
        $params['operator_id'] = $operator_id;
        $params['price_display_config'] = json_decode($params['price_display_config'], true);

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->updateActivity($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/if_share_store",
     *     summary="设置活动是否共享库存",
     *     tags={"内购"},
     *     description="设置活动是否共享库存",
     *     operationId="seIfShareStore",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="if_share_store", in="formData", description="是否共享库存", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function seIfShareStore(Request $request)
    {
        $params = $request->all('activity_id', 'if_share_store');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'if_share_store' => ['required|in:0,1', '请选择是否共享库存'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $params['activity_id'];

        $data['if_share_store'] = $params['if_share_store'];

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->updateBy($filter, $data);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/cancel/{activityId}",
     *     summary="取消内购活动",
     *     tags={"内购"},
     *     description="取消内购活动",
     *     operationId="cancelActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function cancelActivity($activityId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $activityId;

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->cancelActivity($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/suspend/{activityId}",
     *     summary="暂停内购活动",
     *     tags={"内购"},
     *     description="暂停内购活动",
     *     operationId="suspendActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function suspendActivity($activityId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $activityId;

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->suspendActivity($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/active/{activityId}",
     *     summary="重新开始暂停的内购活动",
     *     tags={"内购"},
     *     description="重新开始暂停的内购活动",
     *     operationId="activeActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function activeActivity($activityId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $activityId;

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->activeActivity($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/end/{activityId}",
     *     summary="结束内购活动",
     *     tags={"内购"},
     *     description="结束内购活动",
     *     operationId="endActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function endActivity($activityId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $activityId;

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->endActivity($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/ahead/{activityId}",
     *     summary="提前开始内购活动",
     *     tags={"内购"},
     *     description="提前开始内购活动",
     *     operationId="aheadActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function aheadActivity($activityId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $activityId;

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->aheadActivity($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\GET(
     *     path="/employeepurchase/activity/items",
     *     summary="获取活动商品列表",
     *     tags={"内购"},
     *     description="获取活动商品列表",
     *     operationId="getActivityItemList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="integer", required=true),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="integer", required=true),
     *     @SWG\Parameter( name="main_cat_id", in="query", description="管理分类", type="integer", required=false),
     *     @SWG\Parameter( name="cat_id", in="query", description="销售分类", type="integer", required=false),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", type="integer", required=false),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品编号", type="integer", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items( type="object",
     *                         @SWG\Property( property="activity_id", type="integer", description="活动ID"),
     *                         @SWG\Property( property="item_id", type="integer", description="商品ID"),
     *                         @SWG\Property( property="goods_id", type="integer", description="商品ID"),
     *                         @SWG\Property( property="company_id", type="integer", description="公司ID"),
     *                         @SWG\Property( property="activity_price", type="integer", description="活动价"),
     *                         @SWG\Property( property="activity_store", type="integer", description="活动库存"),
     *                         @SWG\Property( property="limit_fee", type="integer", description="每人限额"),
     *                         @SWG\Property( property="limit_num", type="integer", description="每人限购数量"),
     *                         @SWG\Property( property="sort", type="integer", description="排序"),
     *                         @SWG\Property( property="created", type="integer", description="创建时间"),
     *                         @SWG\Property( property="updated", type="integer", description="更新时间"),
     *                         @SWG\Property( property="item_name", type="string", description="商品名称"),
     *                         @SWG\Property( property="item_bn", type="string", description="商品编号"),
     *                         @SWG\Property( property="nospec", type="string", description="是否单规格"),
     *                         @SWG\Property( property="item_spec_desc", type="string", description="规格描述"),
     *                     ),
     *                 ),
     *             )
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityItemList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'activity_id', 'main_cat_id', 'category', 'item_name', 'item_bn');
        $rules = [
            'activity_id' => ['required|integer', '活动ID必填'],
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页显示数量最大100'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);

        $companyId = app('auth')->user()->get('company_id');
        $filter = ['company_id' => $companyId];
        $filter['activity_id'] = $params['activity_id'];

        $itemsCategoryService = new ItemsCategoryService();
        // 管理分类
        if (isset($params['main_cat_id']) && $params['main_cat_id']) {
            if (is_array($params['main_cat_id'])) {
                $params['main_cat_id'] = array_pop($params['main_cat_id']);
            }
            $filter['main_cat_id'] = $itemsCategoryService->getMainCatChildIdsBy($params['main_cat_id'], $companyId);
        }

        // 销售分类
        if (isset($params['category']) && $params['category']) {
            $filter['category'] = $itemsCategoryService->getItemsCategoryIds($params['category'], $companyId);
        }

        if (isset($params['item_name']) && $params['item_name']) {
            $filter['item_name'] = $params['item_name'];
        }

        if (isset($params['item_bn']) && $params['item_bn']) {
            $filter['item_bn'] = $params['item_bn'];
        }
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getActivityItemList($filter, $page, $pageSize, true, false);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/items",
     *     summary="添加活动商品",
     *     tags={"内购"},
     *     description="添加活动商品",
     *     operationId="addActivityItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="item_id[]", in="formData", description="商品ID", type="integer", required=false),
     *     @SWG\Parameter( name="main_cat_id[]", in="formData", description="管理分类ID", type="integer", required=false),
     *     @SWG\Parameter( name="cat_id[]", in="formData", description="销售分类ID", type="integer", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function addActivityItems(Request $request)
    {
        $params = $request->all('activity_id', 'item_id', 'main_cat_id', 'cat_id');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            // 'item_id' => ['required', '商品ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = app('auth')->user()->get();
        $params['company_id'] = $authInfo['company_id'];
        $company = (new CompanysActivationEgo())->check($params['company_id']);
        $operatorType = $authInfo['operator_type'];
        $distributor_id = $request->input('distributor_id', 0);
        if ($company['product_model'] == 'standard' && $operatorType == 'distributor' && $distributor_id > 0) {
            $params['distributor_id'] = $distributor_id;
        }
        $activitiesService = new ActivitiesService();
        if (isset($params['item_id']) && $params['item_id']) {
            $activitiesService->addActivityItems($params);
        }

        if (isset($params['main_cat_id']) && $params['main_cat_id']) {
            $activitiesService->addActivityItemsByMainCategory($params);
        }

        if (isset($params['cat_id']) && $params['cat_id']) {
            $activitiesService->addActivityItemsByCategory($params);
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/specitems",
     *     summary="选择活动商品规格",
     *     tags={"内购"},
     *     description="选择活动商品规格",
     *     operationId="selectActivitySpecItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="goods_id", in="formData", description="商品ID", type="integer", required=true),
     *     @SWG\Parameter( name="item_id[]", in="formData", description="商品ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function selectActivitySpecItems(Request $request)
    {
        $params = $request->all('activity_id', 'goods_id', 'item_id');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'goods_id' => ['required', '商品ID必填'],
            'item_id' => ['required', '商品ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');

        $activitiesService = new ActivitiesService();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $params['company_id'],
                'activity_id' => $params['activity_id'],
                'goods_id' => $params['goods_id'],
            ];
            $itemList = $activitiesService->itemsEntityRepository->getLists($filter, 'item_id');
            $diff = array_diff(array_column($itemList, 'item_id'), $params['item_id']);
            if ($diff) {
                $filter['item_id'] = $diff;
                $activitiesService->itemsEntityRepository->deleteBy($filter);
            }

            $activitiesService->addActivityItems($params);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/employeepurchase/activity/items",
     *     summary="更新活动商品价格库存等",
     *     tags={"内购"},
     *     description="更新活动商品价格库存等",
     *     operationId="updateActivityItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品ID", type="integer", required=true),
     *     @SWG\Parameter( name="activity_price", in="formData", description="活动价格", type="integer", required=false),
     *     @SWG\Parameter( name="activity_store", in="formData", description="活动库存", type="integer", required=false),
     *     @SWG\Parameter( name="limit_fee", in="formData", description="每人限额", type="integer", required=false),
     *     @SWG\Parameter( name="limit_num", in="formData", description="每人限购数量", type="integer", required=false),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", type="integer", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function updateActivityItems(Request $request)
    {
        $params = $request->all('activity_id', 'item_id', 'activity_price', 'activity_store', 'limit_fee', 'limit_num', 'sort');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'item_id' => ['required', '商品ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['activity_id'] = $params['activity_id'];
        $filter['item_id'] = $params['item_id'];

        $data = [];
        if (isset($params['activity_price']) && $params['activity_price']) {
            $data['activity_price'] = $params['activity_price'];
        }

        if (isset($params['activity_store']) && $params['activity_store']) {
            $data['activity_store'] = $params['activity_store'];
        }

        if (isset($params['limit_fee']) && $params['limit_fee']) {
            $data['limit_fee'] = $params['limit_fee'];
        }

        if (isset($params['limit_num']) && $params['limit_num']) {
            $data['limit_num'] = $params['limit_num'];
        }

        if (isset($params['sort']) && $params['sort']) {
            $data['sort'] = $params['sort'];
        }

        if (!$data) {
            throw new ResourceException('更新内容不能为空');
        }

        $activitiesService = new ActivitiesService();
        $activitiesService->updateActivityItems($filter, $data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/employeepurchase/activity/{activityId}/item/{itemId}",
     *     summary="删除活动商品",
     *     tags={"内购"},
     *     description="删除活动商品",
     *     operationId="deleteActivityItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="itemId", in="path", description="商品ID", type="integer", required=true),
     *     @SWG\Parameter( name="all", in="query", description="商品ID", type="integer", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description=""),
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function deleteActivityItems($activityId, $itemId, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['activity_id'] = $activityId;
        $filter['item_id'] = $itemId;
        $allSpec = $request->get('all', 0);
        $allSpec = $allSpec === 'true' || $allSpec === '1';

        $activitiesService = new ActivitiesService();
        $activitiesService->deleteActivityItems($filter, $allSpec);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/employeepurchase/activity/users",
     *     summary="获取员工内购活动亲友列表",
     *     tags={"内购"},
     *     description="获取员工内购活动亲友列表",
     *     operationId="getActivityUsers",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="employee_mobile", in="query", description="员工手机号", type="string"),
     *     @SWG\Parameter( name="relative_mobile", in="query", description="亲友手机号", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="enterprise_name", type="string", description="企业名称"),
     *                          @SWG\Property( property="enterprise_sn", type="string", description="企业编号"),
     *                          @SWG\Property( property="employee_user_id", type="integer", description="员工用户ID"),
     *                          @SWG\Property( property="employee_mobile", type="string", description="员工手机号"),
     *                          @SWG\Property( property="employee_account", type="string", description="员工账号"),
     *                          @SWG\Property( property="relative_user_id", type="integer", description="亲友用户ID"),
     *                          @SWG\Property( property="relative_mobile", type="string", description="亲友手机号"),
     *                          @SWG\Property( property="created", type="integer", description="绑定时间"),
     *                          @SWG\Property( property="disabled", type="integer", description="是否失效"),
     *                          @SWG\Property( property="aggregate_fee", type="integer", description="使用额度"),
     *                          @SWG\Property( property="employee_username", type="string", description="员工昵称"),
     *                          @SWG\Property( property="relative_username", type="string", description="亲友昵称"),
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityUsers(Request $request)
    {
        $params = $request->all('activity_id', 'employee_mobile', 'relative_mobile', 'page', 'pageSize');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['activity_id'] = $params['activity_id'];

        if (isset($params['employee_mobile']) && $params['employee_mobile']) {
            $filter['employee_mobile'] = $params['employee_mobile'];
        }

        if (isset($params['relative_mobile']) && $params['relative_mobile']) {
            $filter['relative_mobile'] = $params['relative_mobile'];
        }

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getActivityUsers($filter, $page, $pageSize);

        return $this->response->array($result);
    }
}
