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
use EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService;
use EmployeePurchaseBundle\Services\ActivityItemsService;
use EmployeePurchaseBundle\Support\ActivityListDisplayStatusQuery;
use EspierBundle\Jobs\ExportFileJob;
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
     *     @SWG\Property( property="is_passphrase_enabled", type="boolean", description="是否开启口令通道"),
     *     @SWG\Property( property="passphrase_enterprises", type="array", description="口令绑定企业；每项含 participate_quota、passphrase_limitfee(分)、passphrase_code 等；额度在各行配置，活动上无总额字段。enterprise 与单条企业详情接口一致(含邮箱 SMTP 字段、distributor_name 等)", @SWG\Items(type="object")),
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
     *     @SWG\Parameter( name="is_passphrase_enabled", in="formData", description="是否开启口令通道 0/1", type="integer", required=false),
     *     @SWG\Parameter( name="passphrase_enterprises", in="formData", description="口令企业(JSON 数组)：每项 enterprise_id、participate_quota、passphrase_limitfee(分)、passphrase_code；别名 quota、code、limit_fee。开启口令时必填，校验通过后写入 employee_purchase_activity_passphrase_enterprises", type="string", required=false),
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
        $params = $request->all('name', 'title', 'pages_template_id', 'pic', 'list_pic', 'share_pic', 'enterprise_id', 'display_time', 'employee_begin_time', 'employee_end_time', 'employee_limitfee', 'if_relative_join', 'invite_limit', 'relative_begin_time', 'relative_end_time', 'if_share_limitfee', 'relative_limitfee', 'minimum_amount', 'close_modify_hours_after_activity', 'price_display_config', 'is_discount_description_enabled', 'discount_description', 'is_passphrase_enabled', 'passphrase_enterprises');
        $params['company_id'] = $companyId;
        // 处理布尔值参数：支持字符串 'true'/'1' 和整数 1/0
        $params['if_relative_join'] = isset($params['if_relative_join']) && ($params['if_relative_join'] === 'true' || $params['if_relative_join'] === '1' || $params['if_relative_join'] === 1 || $params['if_relative_join'] === true) ? 1 : 0;
        $params['if_share_limitfee'] = isset($params['if_share_limitfee']) && ($params['if_share_limitfee'] === 'true' || $params['if_share_limitfee'] === '1' || $params['if_share_limitfee'] === 1 || $params['if_share_limitfee'] === true) ? 1 : 0;
        $params['is_discount_description_enabled'] = isset($params['is_discount_description_enabled']) && ($params['is_discount_description_enabled'] === 'true' || $params['is_discount_description_enabled'] === '1' || $params['is_discount_description_enabled'] === 1 || $params['is_discount_description_enabled'] === true) ? 1 : 0;
        $params['is_passphrase_enabled'] = isset($params['is_passphrase_enabled']) && ($params['is_passphrase_enabled'] === 'true' || $params['is_passphrase_enabled'] === '1' || $params['is_passphrase_enabled'] === 1 || $params['is_passphrase_enabled'] === true) ? 1 : 0;
        $rules = [
            'name' => ['required', '请输入活动名称'],
            'title' => ['required', '请输入活动标题'],
            'pages_template_id' => ['required', '请选择活动首页关联模版'],
            'pic' => ['required', '请上传活动图片'],
            'list_pic' => ['required', '请上传活动列表海报'],
            'share_pic' => ['required', '请上传活动分享图片'],
            'enterprise_id' => ['required', '请选择参与企业'],
            'display_time' => ['required', '请选择活动预热时间'],
            'employee_begin_time' => ['required', '请选择员工购买开始时间'],
            'employee_end_time' => ['required', '请选择员工购买结束时间'],
            'employee_limitfee' => ['exclude_if:is_passphrase_enabled,1|required|integer|min:1', '员工可使用额度须大于 0（单位：分）'],
            'invite_limit' => ['required_if:if_relative_join,1', '请输入员工可邀请亲友人数上限'],
            'relative_begin_time' => ['required_if:if_relative_join,1', '请选择亲友购买开始时间'],
            'relative_end_time' => ['required_if:if_relative_join,1', '请选择亲友购买结束时间'],
            'if_share_limitfee' => ['required_if:if_relative_join,1', '请选择亲友是否共享员工额度'],
            'relative_limitfee' => ['exclude_if:is_passphrase_enabled,1|exclude_unless:if_relative_join,1|exclude_if:if_share_limitfee,1|required|integer|min:1', '亲友可使用额度须大于 0（单位：分）'],
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
     *     @SWG\Parameter( name="status", in="query", description="活动展示态筛选，支持单个或多项（逗号分隔或重复参数）。可选：not_started,warm_up,ongoing,pending,cancel,over；多项为 OR 关系", type="string"),
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
        $params = $request->all('page', 'pageSize', 'name', 'display_time_begin', 'buy_time_begin', 'buy_time_end', 'enterprise_id', 'distributor_id');
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
        $statusOr = ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull($request->input('status'));
        if ($statusOr !== null) {
            $filter['status|or'] = $statusOr;
        }

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getActivityList($filter, '*', $page, $pageSize);

        $statsMap = [];
        if (!empty($result['list'])) {
            $activityIdsForStats = array_map('intval', array_column($result['list'], 'id'));
            $behaviorStatsService = new ActivityEnterpriseBehaviorLogService();
            $statsMap = $behaviorStatsService->getAggregatedStatsTotalsByActivityIds($companyId, $activityIdsForStats);
        }

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

            $aid = (int) ($result['list'][$key]['id'] ?? 0);
            $st = $statsMap[$aid] ?? [
                'scan_count' => 0,
                'scan_user_count' => 0,
                'passphrase_verify_user_count' => 0,
                'bind_user_count' => 0,
                'order_user_count' => 0,
            ];
            $result['list'][$key]['scan_count'] = $st['scan_count'];
            $result['list'][$key]['scan_user_count'] = $st['scan_user_count'];
            $result['list'][$key]['passphrase_verify_user_count'] = $st['passphrase_verify_user_count'];
            $result['list'][$key]['bind_user_count'] = $st['bind_user_count'];
            $result['list'][$key]['order_user_count'] = $st['order_user_count'];
        }

        return $this->response->array($result);
    }

    /**
     * 导出活动下参与企业的扫码落地页小程序码链接（Excel）
     *
     * @param int $activityId
     */
    public function downloadActivityQrcode($activityId, Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = (int) $authInfo['company_id'];
        $distributorScopeId = null;
        if (($authInfo['operator_type'] ?? '') == 'distributor') {
            $distributorScopeId = (int) $authInfo['distributor_id'];
        }

        $filter = [
            'activity_id' => (int) $activityId,
            'company_id' => $companyId,
            'operator_id' => (int) ($authInfo['operator_id'] ?? 0),
        ];
        if ($distributorScopeId !== null) {
            $filter['distributor_id'] = $distributorScopeId;
        }

        // 提前校验活动与权限，避免提交无效导出任务
        $activitiesService = new ActivitiesService();
        $activitiesService->buildActivityEnterpriseQrcodeExportRows(
            $companyId,
            (int) $activityId,
            $distributorScopeId
        );

        $gotoJob = (new ExportFileJob(
            'employee_purchase_activity_qrcode',
            $companyId,
            $filter,
            $filter['operator_id']
        ))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return response()->json(['status' => true]);
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
        $result['is_passphrase_enabled'] = !empty($result['is_passphrase_enabled']) ? 'true' : 'false';
        $result['passphrase_enterprises'] = $activitiesService->getPassphraseEnterpriseList($filter['company_id'], $activityId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/employeepurchase/activity/{activityId}/enterprise-behavior-stats",
     *     summary="活动各企业行为流水聚合统计",
     *     tags={"内购"},
     *     description="基于 employee_purchase_activity_enterprise_behavior_log 实时聚合：扫码次数/人数、口令验证成功人数(UV，含 result_status=success；失败尝试不计入)、绑定人数、下单人数（order 行为 UV，内购支付成功写入，与绑定渠道无关）；行集合为活动参与企业",
     *     operationId="getActivityEnterpriseBehaviorStats",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property( property="enterprise_id", type="integer" ),
     *                         @SWG\Property( property="enterprise_name", type="string" ),
     *                         @SWG\Property( property="enterprise_sn", type="string" ),
     *                         @SWG\Property( property="logo", type="string" ),
     *                         @SWG\Property( property="scan_count", type="integer", description="扫码次数(PV)" ),
     *                         @SWG\Property( property="scan_user_count", type="integer", description="扫码人数(UV)" ),
     *                         @SWG\Property( property="passphrase_verify_user_count", type="integer", description="口令验证成功人数(UV)，失败流水不计入" ),
     *                         @SWG\Property( property="bind_user_count", type="integer" ),
     *                         @SWG\Property( property="order_user_count", type="integer", description="内购支付成功写入的 order 行为用户数(UV)" ),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityEnterpriseBehaviorStats($activityId, Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = (int) $authInfo['company_id'];
        $distributorScopeId = null;
        if (($authInfo['operator_type'] ?? '') == 'distributor') {
            $distributorScopeId = (int) $authInfo['distributor_id'];
        }

        $service = new ActivityEnterpriseBehaviorLogService();
        $result = $service->getAggregatedStatsForAdmin($companyId, (int) $activityId, $distributorScopeId);

        return $this->response->array($result);
    }

    /**
     * 下载活动企业行为统计（Excel）
     *
     * @param int $activityId
     */
    public function downloadActivityEnterpriseBehaviorStats($activityId, Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = (int) $authInfo['company_id'];
        $distributorScopeId = null;
        if (($authInfo['operator_type'] ?? '') == 'distributor') {
            $distributorScopeId = (int) $authInfo['distributor_id'];
        }

        $filter = [
            'activity_id' => (int) $activityId,
            'company_id' => $companyId,
            'operator_id' => (int) ($authInfo['operator_id'] ?? 0),
        ];
        if ($distributorScopeId !== null) {
            $filter['distributor_id'] = $distributorScopeId;
        }

        // 提前校验活动与统计可访问性，避免提交无效导出任务
        $activitiesService = new ActivitiesService();
        $activityFilter = [
            'company_id' => $companyId,
            'id' => (int) $activityId,
        ];
        if ($distributorScopeId !== null) {
            $activityFilter['distributor_id'] = $distributorScopeId;
        }
        $activitiesService->getInfo($activityFilter);
        $service = new ActivityEnterpriseBehaviorLogService();
        $service->getAggregatedStatsForAdmin($companyId, (int) $activityId, $distributorScopeId);

        $gotoJob = (new ExportFileJob(
            'employee_purchase_activity_scan_stats',
            $companyId,
            $filter,
            $filter['operator_id']
        ))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return response()->json(['status' => true]);
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
     *     @SWG\Parameter( name="is_passphrase_enabled", in="formData", description="是否开启口令通道 0/1", type="integer", required=false),
     *     @SWG\Parameter( name="passphrase_enterprises", in="formData", description="传此字段则整表替换口令企业数据(JSON 数组)，结构同创建；关闭口令时会清空口令企业表", type="string", required=false),
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
        $params = $request->all('name', 'title', 'pages_template_id', 'pic', 'list_pic', 'share_pic', 'enterprise_id', 'display_time', 'employee_begin_time', 'employee_end_time', 'employee_limitfee', 'if_relative_join', 'invite_limit', 'relative_begin_time', 'relative_end_time', 'if_share_limitfee', 'relative_limitfee', 'minimum_amount', 'close_modify_hours_after_activity', 'price_display_config', 'is_discount_description_enabled', 'discount_description', 'is_passphrase_enabled', 'passphrase_enterprises');
        // 与 createActivity 一致：JSON 常传整数 1/0 或布尔，勿仅用 === '1'（否则 if_relative_join 恒为 0，亲友配置保存后不生效）
        $params['if_relative_join'] = isset($params['if_relative_join']) && ($params['if_relative_join'] === 'true' || $params['if_relative_join'] === '1' || $params['if_relative_join'] === 1 || $params['if_relative_join'] === true) ? 1 : 0;
        $params['if_share_limitfee'] = isset($params['if_share_limitfee']) && ($params['if_share_limitfee'] === 'true' || $params['if_share_limitfee'] === '1' || $params['if_share_limitfee'] === 1 || $params['if_share_limitfee'] === true) ? 1 : 0;
        $params['is_discount_description_enabled'] = isset($params['is_discount_description_enabled']) && ($params['is_discount_description_enabled'] === 'true' || $params['is_discount_description_enabled'] === '1' || $params['is_discount_description_enabled'] === 1 || $params['is_discount_description_enabled'] === true) ? 1 : 0;
        $params['__passphrase_sync'] = 'none';
        if (array_key_exists('passphrase_enterprises', $params)) {
            $params['__passphrase_sync'] = 'replace';
        }
        if (array_key_exists('is_passphrase_enabled', $params)) {
            $params['is_passphrase_enabled'] = isset($params['is_passphrase_enabled']) && ($params['is_passphrase_enabled'] === 'true' || $params['is_passphrase_enabled'] === '1' || $params['is_passphrase_enabled'] === 1 || $params['is_passphrase_enabled'] === true) ? 1 : 0;
            if (!$params['is_passphrase_enabled']) {
                $params['__passphrase_sync'] = 'clear';
            }
        }
        if (!array_key_exists('is_passphrase_enabled', $params)) {
            $existingActivity = (new ActivitiesService())->getInfo(['company_id' => $companyId, 'id' => (int) $activityId]);
            $params['is_passphrase_enabled'] = !empty($existingActivity['is_passphrase_enabled']) ? 1 : 0;
        }
        $rules = [
            'name' => ['required', '请输入活动名称'],
            'title' => ['required', '请输入活动标题'],
            'pages_template_id' => ['required', '请选择活动首页关联模版'],
            'pic' => ['required', '请上传活动图片'],
            'list_pic' => ['required', '请上传活动列表海报'],
            'share_pic' => ['required', '请上传活动分享图片'],
            'enterprise_id' => ['required', '请选择参与企业'],
            'display_time' => ['required', '请选择活动预热时间'],
            'employee_begin_time' => ['required', '请选择员工购买开始时间'],
            'employee_end_time' => ['required', '请选择员工购买结束时间'],
            'employee_limitfee' => ['exclude_if:is_passphrase_enabled,1|required|integer|min:1', '员工可使用额度须大于 0（单位：分）'],
            'invite_limit' => ['required_if:if_relative_join,1', '请输入员工可邀请亲友人数上限'],
            'relative_begin_time' => ['required_if:if_relative_join,1', '请选择亲友购买开始时间'],
            'relative_end_time' => ['required_if:if_relative_join,1', '请选择亲友购买结束时间'],
            'if_share_limitfee' => ['required_if:if_relative_join,1', '请选择亲友是否共享员工额度'],
            'relative_limitfee' => ['exclude_if:is_passphrase_enabled,1|exclude_unless:if_relative_join,1|exclude_if:if_share_limitfee,1|required|integer|min:1', '亲友可使用额度须大于 0（单位：分）'],
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
        $params = $request->all('page', 'pageSize', 'activity_id', 'main_cat_id', 'category', 'item_name', 'item_bn', 'shelf_status');
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

        if (array_key_exists('shelf_status', $params) && $params['shelf_status'] !== '' && $params['shelf_status'] !== null) {
            $filter['shelf_status'] = (int) $params['shelf_status'];
        }

        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getActivityItemList($filter, $page, $pageSize, true, false);
        return $this->response->array($result);
    }

    public function exportActivityItems(Request $request)
    {
        $params = $request->all('activity_id', 'main_cat_id', 'category', 'item_name', 'item_bn', 'item_id', 'shelf_status');
        $error = validator_params($params, [
            'activity_id' => ['required|integer', '活动ID必填'],
            'item_id' => ['sometimes|array', '商品ID格式错误'],
            'item_id.*' => ['integer|min:1', '商品ID格式错误'],
        ]);
        if ($error) {
            throw new ResourceException($error);
        }

        $authInfo = app('auth')->user()->get();
        $companyId = (int) $authInfo['company_id'];
        $filter = [
            'company_id' => $companyId,
            'activity_id' => (int) $params['activity_id'],
            'operator_id' => (int) ($authInfo['operator_id'] ?? 0),
        ];
        $activityFilter = ['company_id' => $companyId, 'id' => $filter['activity_id']];
        if (($authInfo['operator_type'] ?? '') == 'distributor') {
            $filter['distributor_id'] = (int) $authInfo['distributor_id'];
            $activityFilter['distributor_id'] = $filter['distributor_id'];
        } else {
            $filter['distributor_id'] = (int) $request->get('distributor_id', 0);
        }

        $activitiesService = new ActivitiesService();
        $activitiesService->getInfo($activityFilter);
        $itemsCategoryService = new ItemsCategoryService();
        if (!empty($params['main_cat_id'])) {
            $mainCatId = is_array($params['main_cat_id']) ? end($params['main_cat_id']) : $params['main_cat_id'];
            $filter['main_cat_id'] = $itemsCategoryService->getMainCatChildIdsBy($mainCatId, $companyId);
        }
        if (!empty($params['category'])) {
            $filter['category'] = $itemsCategoryService->getItemsCategoryIds($params['category'], $companyId);
        }
        if (!empty($params['item_name'])) {
            $filter['item_name'] = $params['item_name'];
        }
        if (!empty($params['item_bn'])) {
            $filter['item_bn'] = $params['item_bn'];
        }
        if (!empty($params['item_id'])) {
            $filter['item_id'] = array_values(array_unique(array_map('intval', $params['item_id'])));
        }
        if (array_key_exists('shelf_status', $params) && $params['shelf_status'] !== '' && $params['shelf_status'] !== null) {
            $filter['shelf_status'] = (int) $params['shelf_status'];
        }

        $result = $activitiesService->getActivityItemList($filter, 1, 1, true, false);
        if (empty($result['total_count'])) {
            throw new ResourceException('导出有误,暂无数据导出');
        }

        $gotoJob = (new ExportFileJob(
            'employee_purchase_activity_items',
            $companyId,
            $filter,
            $filter['operator_id']
        ))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return response()->json(['status' => true]);
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
        $params = $request->all('activity_id', 'item_id', 'activity_price', 'activity_store', 'limit_fee', 'limit_num', 'sort', 'shelf_status', 'all');
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

        if (isset($params['sort']) && $params['sort'] !== '') {
            $data['sort'] = $params['sort'];
        }

        if (array_key_exists('shelf_status', $params) && $params['shelf_status'] !== '' && $params['shelf_status'] !== null) {
            $shelfStatus = (int) $params['shelf_status'];
            if (!in_array($shelfStatus, [0, 1], true)) {
                throw new ResourceException('上下架状态无效');
            }
            $data['shelf_status'] = $shelfStatus;
        }

        if (!$data) {
            throw new ResourceException('更新内容不能为空');
        }

        $allSpec = $request->get('all', 0);
        $allSpec = $allSpec === 'true' || $allSpec === '1' || $allSpec === 1;

        $activitiesService = new ActivitiesService();
        if ($allSpec && array_key_exists('shelf_status', $data)) {
            $activitiesService->updateActivityItemsByGoods($filter, $data);
        } else {
            $activitiesService->updateActivityItems($filter, $data);
        }

        if (array_key_exists('shelf_status', $data)) {
            $activityItemsService = new ActivityItemsService();
            $activityItemsService->storeActivityItemsCategory($filter['company_id'], $filter['activity_id']);
        }

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

    /**
     * @SWG\Post(
     *     path="/employeepurchase/passphrase-codes/generate",
     *     summary="批量生成口令编码",
     *     tags={"内购"},
     *     description="8 位数字+英文大小写。新建活动可不传 activity_id：与本公司下已有口令去重，企业须为本公司（及店铺可见）内购企业；编辑活动可传 activity_id：与该活动已保存口令去重，企业须为活动参与企业",
     *     operationId="generatePassphraseCodes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             required={"enterprise_ids"},
     *             @SWG\Property( property="activity_id", type="integer", description="活动ID，新建可不传或传0；不传则按公司维度去重" ),
     *             @SWG\Property( property="enterprise_ids", type="array", description="企业ID列表", @SWG\Items(type="integer") ),
     *             @SWG\Property( property="count", type="integer", description="每个企业生成条数，默认1，最大50" ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property( property="enterprise_id", type="integer" ),
     *                         @SWG\Property( property="passphrase_codes", type="array", @SWG\Items(type="string") ),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function generatePassphraseCodes(Request $request)
    {
        return $this->doGeneratePassphraseCodes($request, 0);
    }

    /**
     * @SWG\Post(
     *     path="/employeepurchase/activity/{activityId}/passphrase-codes/generate",
     *     summary="批量生成口令编码（路径带活动ID，兼容旧调用）",
     *     tags={"内购"},
     *     description="与 POST /employeepurchase/passphrase-codes/generate 相同，activityId 以路径为准（忽略 body 内 activity_id）",
     *     operationId="generatePassphraseCodesByActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activityId", in="path", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             required={"enterprise_ids"},
     *             @SWG\Property( property="enterprise_ids", type="array", @SWG\Items(type="integer") ),
     *             @SWG\Property( property="count", type="integer" ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property( property="enterprise_id", type="integer" ),
     *                         @SWG\Property( property="passphrase_codes", type="array", @SWG\Items(type="string") ),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function generatePassphraseCodesByActivity($activityId, Request $request)
    {
        return $this->doGeneratePassphraseCodes($request, (int) $activityId);
    }

    /**
     * @param int $activityIdFromPath 大于 0 时优先使用路径活动ID
     */
    private function doGeneratePassphraseCodes(Request $request, $activityIdFromPath)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = (int) $authInfo['company_id'];
        $distributorScopeId = null;
        if (($authInfo['operator_type'] ?? '') == 'distributor') {
            $distributorScopeId = (int) $authInfo['distributor_id'];
        }

        $bodyActivity = $request->input('activity_id');
        if ($bodyActivity === null || $bodyActivity === '') {
            $bodyActivityId = 0;
        } else {
            $bodyActivityId = (int) $bodyActivity;
        }
        $activityId = $activityIdFromPath > 0 ? $activityIdFromPath : $bodyActivityId;

        $rawIds = $request->input('enterprise_ids');
        if (is_string($rawIds)) {
            $decoded = json_decode($rawIds, true);
            $enterpriseIds = is_array($decoded) ? $decoded : array_filter(explode(',', $rawIds));
        } elseif (is_array($rawIds)) {
            $enterpriseIds = $rawIds;
        } else {
            $enterpriseIds = [];
        }

        $count = $request->input('count', 1);
        if ($count === null || $count === '') {
            $count = 1;
        }
        $count = (int) $count;

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->generatePassphraseCodesForEnterprises(
            $companyId,
            $enterpriseIds,
            $count,
            $activityId,
            $distributorScopeId
        );

        return $this->response->array($result);
    }
}
