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

namespace EmployeePurchaseBundle\Http\FrontApi\V1\Action;

use EmployeePurchaseBundle\Services\MemberActivityAggregateService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as BaseController;

use EmployeePurchaseBundle\Services\ActivitiesService;
use EmployeePurchaseBundle\Support\ActivityListDisplayStatusQuery;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService;
use EmployeePurchaseBundle\Services\PassphraseVerifiedRedisService;
use GoodsBundle\Services\ItemsCategoryService;
use EmployeePurchaseBundle\Services\ActivityItemsService;
use CompanysBundle\Services\SettingService as ItemSettingService;
use CompanysBundle\Traits\GetDefaultCur;
use GoodsBundle\Services\ItemsService;

class Activity extends BaseController
{
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/is_open",
     *     summary="是否开启内购",
     *     tags={"内购"},
     *     description="是否开启内购",
     *     operationId="isOpen",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="is_open", type="string", example="true"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function isOpen(Request $request)
    {
        $applications = app('authorization')->getApplications();
        $isOpen = $applications['employee_purchase'] ?? false;
        return $this->response->array(['is_open' => $isOpen]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/activities",
     *     summary="获取可参与的活动列表",
     *     tags={"内购"},
     *     description="获取可参与的活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="活动展示态筛选，支持逗号分隔或重复参数，可选 not_started,warm_up,ongoing,pending,cancel,over；多项为 OR；不传则保持原有过滤", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items( type="object",
     *                         @SWG\Property( property="id", type="integer", description="活动ID"),
     *                         @SWG\Property( property="company_id", type="integer", description="公司ID"),
     *                         @SWG\Property( property="name", type="string", description="活动名称"),
     *                         @SWG\Property( property="title", type="string", description="活动标题"),
     *                         @SWG\Property( property="pages_template_id", type="integer", description="活动首页关联模版"),
     *                         @SWG\Property( property="share_pic", type="string", description="活动分享图片"),
     *                         @SWG\Property( property="enterprise_id", type="array", description="参与企业", @SWG\Items(type="integer")),
     *                         @SWG\Property( property="display_time", type="integer", description="活动预热时间"),
     *                         @SWG\Property( property="employee_begin_time", type="integer", description="员工购买开始时间"),
     *                         @SWG\Property( property="employee_end_time", type="integer", description="员工购买结束时间"),
     *                         @SWG\Property( property="employee_limitfee", type="integer", description="员工可使用额度"),
     *                         @SWG\Property( property="if_relative_join", type="boolean", description="亲友是否参与活动"),
     *                         @SWG\Property( property="invite_limit", type="integer", description="员工可邀请亲友人数上限"),
     *                         @SWG\Property( property="relative_begin_time", type="integer", description="亲友购买开始时间"),
     *                         @SWG\Property( property="relative_end_time", type="integer", description="亲友购买结束时间"),
     *                         @SWG\Property( property="if_share_limitfee", type="boolean", description="亲友是否共享员工额度"),
     *                         @SWG\Property( property="relative_limitfee", type="integer", description="亲友可使用额度"),
     *                         @SWG\Property( property="minimum_amount", type="integer", description="订单最低金额"),
     *                         @SWG\Property( property="close_modify_hours_after_activity", type="integer", description="活动结束后多少小时内可以修改收货地址"),
     *                         @SWG\Property( property="created", type="integer", description="创建时间"),
     *                         @SWG\Property( property="status", type="string", description="活动状态"),
     *                         @SWG\Property( property="status_desc", type="string", description="活动状态描述"),
     *                         @SWG\Property( property="is_employee", type="integer", description="是否员工"),
     *                         @SWG\Property( property="is_relative", type="integer", description="是否家属"),
     *                         @SWG\Property( property="rel_enterprise", type="string", description="员工/家属关联的企业"),
     *                         @SWG\Property( property="is_passphrase_enabled", type="integer", description="是否开启口令通道 0/1"),
     *                         @SWG\Property( property="auth_type", type="string", description="当前行关联内购企业认证方式：email/account/mobile/qr_code/no_verify 等"),
     *                         @SWG\Property( property="passphrase_user_verified", type="integer", description="当前登录用户是否已在该活动+本企业下口令校验成功；未开口令为 0"),
     *                     ),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('activity_name','enterprise_id','need_aggregate','activity_id');

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        if (isset($params['activity_name']) && $params['activity_name']) {
            $filter['name|contains'] = $params['activity_name'];
        }
        if (isset($params['activity_id']) && $params['activity_id']) {
            $filter['id'] = $params['activity_id'];
        }
        $enterpriseId = intval($params['enterprise_id']);
        if ($enterpriseId > 0) {
            $filter['enterprise_id'] = $enterpriseId;// 员工企业ID
        } else {
            return $this->response->array(['total_count' => "0", "list" => []]);
        }

        $statusOr = ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull($request->input('status'));
        if ($statusOr !== null) {
            $filter['status|or'] = $statusOr;
        }

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getUserActivities($filter, '*', $page, $pageSize, ['display_time' => 'ASC']);

        $companyId = (int) $filter['company_id'];
        $userId = (int) $filter['user_id'];
        $passphraseVerifiedSvc = new PassphraseVerifiedRedisService();

        $now = time();
        foreach ($result['list'] as $key => $row) {
            if ($row['display_time'] < $now && $row['employee_begin_time'] > $now && $row['relative_begin_time'] > $now && $row['status'] == 'active') {
                $result['list'][$key]['status'] = 'warm_up';
                $result['list'][$key]['status_desc'] = '预热中';
            }
            if (($row['employee_begin_time'] < $now || $row['relative_begin_time'] < $now) && ($row['employee_end_time'] > $now || $row['relative_end_time'] > $now) && $row['status'] == 'active') {
                $result['list'][$key]['status'] = 'ongoing';
                $result['list'][$key]['status_desc'] = '进行中';
            }
            if (($row['employee_begin_time'] < $now || $row['relative_begin_time'] < $now) && ($row['employee_end_time'] > $now || $row['relative_end_time'] > $now) && $row['status'] == 'pending') {
                $result['list'][$key]['status'] = 'pending';
                $result['list'][$key]['status_desc'] = '已暂停';
            }
            $result['list'][$key]['price_display_config'] = json_decode($row['price_display_config'], true);
            $result['list'][$key]['is_discount_description_enabled'] = $row['is_discount_description_enabled'] == 1 ? 'true' : 'false';

            $activityId = (int) ($row['id'] ?? 0);
            $rowEnterpriseId = (int) ($row['enterprise_id'] ?? 0);
            $result['list'][$key]['is_passphrase_enabled'] = !empty($row['is_passphrase_enabled']) ? 1 : 0;
            $result['list'][$key]['auth_type'] = isset($row['auth_type']) && $row['auth_type'] !== null && $row['auth_type'] !== ''
                ? (string) $row['auth_type']
                : '';
            $result['list'][$key]['passphrase_user_verified'] = !empty($row['is_passphrase_enabled']) && $userId > 0 && $activityId > 0 && $rowEnterpriseId > 0
                ? ($passphraseVerifiedSvc->isVerified($companyId, $activityId, $rowEnterpriseId, $userId) ? 1 : 0)
                : 0;
        }
        //根据参数判断，是否需要追加额度
        if(!empty($params['need_aggregate'])){
            $result['list'] = (new MemberActivityAggregateService())->getUserActivityDataList($filter['company_id'],$filter['user_id'],$result['list'],$enterpriseId);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/internal-sale-eligibility",
     *     summary="内购资格校验（当前用户）",
     *     tags={"内购"},
     *     description="按活动+企业校验登录用户是否具备有效内购资格（白名单员工且未禁用，或有效家属）。列表进详情、参与活动、加购等节点按需调用，避免活动列表逐条查库。",
     *     operationId="getInternalSaleEligibility",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="internal_sale_eligible", type="integer", description="1=有资格，0=无（如白名单已删/禁用）"),
     *                 @SWG\Property( property="eligible_as", type="string", description="employee=有效员工白名单，relative=有效家属，none=无"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getInternalSaleEligibility(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('activity_id', 'enterprise_id');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'enterprise_id' => ['required', '企业ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = (int) $authInfo['company_id'];
        $userId = (int) $authInfo['user_id'];
        $activityId = (int) $params['activity_id'];
        $enterpriseId = (int) $params['enterprise_id'];

        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }
        if (!in_array($params['enterprise_id'], $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $employeesService = new EmployeesService();
        $relativesService = new RelativesService();

        $eligibleAs = 'none';
        if ($employeesService->check($companyId, $enterpriseId, $userId)) {
            $eligibleAs = 'employee';
        } elseif ($relativesService->check($companyId, $enterpriseId, $activityId, $userId)) {
            $eligibleAs = 'relative';
        }

        return $this->response->array([
            'internal_sale_eligible' => $eligibleAs !== 'none' ? 1 : 0,
            'eligible_as' => $eligibleAs,
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/activity/detail",
     *     summary="获取活动详情",
     *     tags={"内购"},
     *     description="获取活动详情；勿使用 /activity/{activity_id} 路径——会与 /activity/items 等字面段在 FastRoute 中冲突（BadRouteException）",
     *     operationId="getActivityDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="company_id", in="query", description="公司ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="activity_id", type="integer", description="活动ID"),
     *                 @SWG\Property( property="name", type="string", description="活动名称"),
     *                 @SWG\Property( property="title", type="string", description="活动标题"),
     *                 @SWG\Property( property="pages_template_id", type="integer", description="活动首页关联模版"),
     *                 @SWG\Property( property="share_pic", type="string", description="活动分享图片"),
     *                 @SWG\Property( property="pic", type="string", description="活动图片"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityDetail(Request $request){
        $params = $request->all('activity_id','company_id');
        $rules = [
            'activity_id' => ['required|integer', '活动ID必填'],
            'company_id' => ['required|integer', '公司ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->getInfo(['company_id' => $params['company_id'], 'id' => $params['activity_id']]);
        if(!$activity){
            throw new ResourceException('活动不存在');
        }
        $result['activity_id'] = $activity['id'];
        $result['name'] = $activity['name'];
        $result['title'] = $activity['title'];
        $result['pages_template_id'] = (int) ($activity['pages_template_id'] ?? 0);
        $result['share_pic'] = $activity['share_pic'];
        $result['pic'] = $activity['pic'];
        return $this->response->array($result);
    }
    /**
     * @SWG\GET(
     *     path="/wxapp/employeepurchase/activity/items",
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
        $authInfo = $request->get('auth');

        $params = $request->all('activity_id', 'main_cat_id', 'cat_id', 'category', 'item_name', 'item_bn', 'keywords', 'goodsSort');
        $rules = [
            'activity_id' => ['required|integer', '活动ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $companyId = $authInfo['company_id'];
        $filter['company_id'] = $companyId;
        $filter['activity_id'] = $params['activity_id'];
        $filter['shelf_status'] = 1;

        $distributor_id = $request->get('distributor_id', 0);
        if ($distributor_id > 0) {
            $filter['is_can_sale'] = 1;
        } else {
            $filter['approve_status'] = ['onsale', 'only_show'];
        }

        $itemsCategoryService = new ItemsCategoryService();
        if (isset($params['main_cat_id']) && $params['main_cat_id']) {
            $filter['main_cat_id'] = $itemsCategoryService->getMainCatChildIdsBy($params['main_cat_id'], $companyId);
        }

        if (isset($params['category']) && $params['category']) {
            $filter['cat_id'] = $itemsCategoryService->getItemsCategoryIds($params['category'], $companyId);
        }

        if (isset($params['cat_id']) && $params['cat_id']) {
            $filter['cat_id'] = $itemsCategoryService->getItemsCategoryIds($params['cat_id'], $companyId);
        }

        if (isset($params['item_name']) && $params['item_name']) {
            $filter['item_name'] = $params['item_name'];
        }

        if (isset($params['item_bn']) && $params['item_bn']) {
            $filter['item_bn'] = $params['item_bn'];
        }

        if (isset($params['keywords']) && $params['keywords']) {
            $filter['keywords'] = $params['keywords'];
        }

        if (isset($params['goodsSort']) && $params['goodsSort'] == 1) {
            $orderBy['sales'] = 'desc';
        } elseif (isset($params['goodsSort']) && $params['goodsSort'] == 2) {
            $orderBy['activity_price'] = 'desc';
        } elseif (isset($params['goodsSort']) && $params['goodsSort'] == 3) {
            $orderBy['activity_price'] = 'asc';
        } else {
            $orderBy['sort'] = 'desc';
        }
        $orderBy['item_id'] = 'desc';

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getActivityItemList($filter, $page, $pageSize, false, true, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\GET(
     *     path="/wxapp/employeepurchase/activity/item/{item_id}",
     *     summary="获取活动商品详情",
     *     tags={"内购"},
     *     description="获取活动商品详情",
     *     operationId="getActivityItemDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="integer", required=true),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items( type="object",
     *                         @SWG\Property( property="item_id", type="integer", description="商品ID"),
     *                         @SWG\Property( property="goods_id", type="integer", description="商品ID"),
     *                         @SWG\Property( property="company_id", type="integer", description="公司ID"),
     *                         @SWG\Property( property="activity_price", type="integer", description="活动价"),
     *                         @SWG\Property( property="store", type="integer", description="库存"),
     *                         @SWG\Property( property="limit_fee", type="integer", description="每人限购金额，单位分；0 表示无限购"),
     *                         @SWG\Property( property="limit_num", type="integer", description="每人限购数量；0 表示无限购"),
     *                     ),
     *                 ),
     *             )
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityItemDetail($item_id, Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('enterprise_id', 'activity_id');
        $rules = [
            'enterprise_id' => ['required|integer', '企业ID必填'],
            'activity_id' => ['required|integer', '活动ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $companyId = $authInfo['company_id'];
        $woaAppid = $authInfo['woa_appid'];

        $itemsService = new ItemsService();
        $result = $itemsService->getItemsDetail($item_id, $woaAppid, [], $companyId);
        if (!$result) {
            throw new ResourceException('商品不存在');
        }

        //普通商品，不是活动商品
        $result['activity_type'] = 'employee_purchase';
        $activitiesService = new ActivitiesService;
        $activity = $activitiesService->getInfo(['company_id' => $companyId, 'id' => $params['activity_id']]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }
        if (!in_array($params['enterprise_id'], $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }
        $result['activity_info'] = $activity;

        $activityItemsService = new ActivityItemsService();
        $activityItemList = $activityItemsService->getLists(['company_id' => $companyId, 'activity_id' => $params['activity_id'], 'goods_id' => $result['goods_id']]);
        $activityItemList = array_column($activityItemList, null, 'item_id');
        $isOnShelf = static function ($itemId) use ($activityItemList) {
            if (!isset($activityItemList[$itemId])) {
                return false;
            }

            return (int) ($activityItemList[$itemId]['shelf_status'] ?? 1) === 1;
        };
        if ($isOnShelf($result['item_id'])) {
            $result['activity_price'] = $activityItemList[$result['item_id']]['activity_price'];
            if (!$activity['if_share_store']) {
                $result['store'] = $activityItemList[$result['item_id']]['activity_store'];
            }
            $result['limit_fee'] = (int) ($activityItemList[$result['item_id']]['limit_fee'] ?? 0);
            $result['limit_num'] = (int) ($activityItemList[$result['item_id']]['limit_num'] ?? 0);
        } else {
            $result['store'] = 0;
            $result['approve_status'] = 'instock';
            $result['limit_fee'] = 0;
            $result['limit_num'] = 0;
        }

        if (isset($result['nospec']) && ($result['nospec'] === false || $result['nospec'] === 'false') || $result['nospec'] === 0 || $result['nospec'] === '0') {
            foreach ($result['spec_items'] as $key => $item) {
                if ($isOnShelf($item['item_id'])) {
                    $result['spec_items'][$key]['activity_price'] = $activityItemList[$item['item_id']]['activity_price'];
                    if (!$activity['if_share_store']) {
                        $result['spec_items'][$key]['store'] = $activityItemList[$item['item_id']]['activity_store'];
                    }
                    $result['spec_items'][$key]['limit_fee'] = (int) ($activityItemList[$item['item_id']]['limit_fee'] ?? 0);
                    $result['spec_items'][$key]['limit_num'] = (int) ($activityItemList[$item['item_id']]['limit_num'] ?? 0);
                } else {
                    $result['spec_items'][$key]['store'] = 0;
                    $result['spec_items'][$key]['approve_status'] = 'instock';
                    $result['spec_items'][$key]['limit_fee'] = 0;
                    $result['spec_items'][$key]['limit_num'] = 0;
                }
            }
            $result['store'] = array_sum(array_column($result['spec_items'], 'store'));
        }

        //获取系统货币默认配置
        $result['cur'] = $this->getCur($companyId);

        $result['sales'] = $result['item_total_sales'] ?? $result['sales'];

        $result['rate_status'] = $this->getGoodsRateSettingStatus($result['company_id']);

        //获取库存/销量 显示设置
        $itemSettingService = new ItemSettingService();
        $result['sales_setting'] = $itemSettingService->getItemSalesSetting($companyId)['item_sales_status'];
        $result['store_setting'] = $itemSettingService->getItemStoreSetting($companyId)['item_store_status'];
        $result['distributor_id'] = $activity['distributor_id'];

        // 与 Api Activity::getActivityItemList -> getActivityItemList 一致：主商品 + 规格行多语言
        $localized = $activitiesService->applyMultiLangToActivityItemList([$result]);
        $result = $localized[0];
        if (!is_array($result['intro'])) {
            json_decode($result['intro']);

            if (json_last_error() === JSON_ERROR_NONE) {
                $result['intro'] = json_decode($result['intro'], true);
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\GET(
     *     path="/wxapp/employeepurchase/activity/items/category",
     *     summary="获取活动商品关联的分类",
     *     tags={"内购"},
     *     description="获取活动商品关联的分类",
     *     operationId="getActivityItemCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property( property="id", type="string", example="3", description=""),
     *                     @SWG\Property( property="category_id", type="string", example="3", dscription="商品分类id"),
     *                     @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                     @SWG\Property( property="category_name", type="string", example="测试类目122", d    ecription="分类名称"),
     *                     @SWG\Property( property="label", type="string", example="测试类目122", description=""),
     *                     @SWG\Property( property="parent_id", type="string", example="0", d    ecription="父分类id,顶级为0"),
     *                     @SWG\Property( property="distributor_id", type="string", example="0", d    ecription="分销商id"),
     *                     @SWG\Property( property="path", type="string", example="3", description="路径"),
     *                     @SWG\Property( property="sort", type="string", example="11111", description="排序"),
     *                     @SWG\Property( property="is_main_category", type="string", example="true", d    ecription="是否为商品主类目"),
     *                     @SWG\Property( property="goods_params", type="array",
     *                         @SWG\Items( type="string", example="undefined", description=""),
     *                     ),
     *                     @SWG\Property( property="goods_spec", type="array",
     *                         @SWG\Items( type="string", example="undefined", description=""),
     *                     ),
     *                     @SWG\Property( property="category_level", type="string", example="1", d    ecription="商品分类等级"),
     *                     @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                     @SWG\Property( property="crossborder_tax_rate", type="string", example="12", d    ecription="跨境税率，百分比，小数点2位"),
     *                     @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                     @SWG\Property( property="updated", type="string", example="1606369584", d    ecription="修改时间"),
     *                     @SWG\Property( property="category_code", type="string", example="null", d    ecription="分类编码"),
     *                     @SWG\Property( property="children", type="array",
     *                         @SWG\Items( type="object",
     *                             @SWG\Property( property="id", type="string", example="4", description=""),
     *                             @SWG\Property( property="category_id", type="string", example="4", d    ecription="商品分类id"),
     *                             @SWG\Property( property="company_id", type="string", example="1", d    ecription="公司id"),
     *                             @SWG\Property( property="category_name", type="string", example="测试类目1-1", d   escription="分类名称"),
     *                             @SWG\Property( property="label", type="string", example="测试类目1-1", d    ecription=""),
     *                             @SWG\Property( property="parent_id", type="string", example="3", d    ecription="父分类id,顶级为0"),
     *                             @SWG\Property( property="distributor_id", type="string", example="0", d    ecription="分销商id"),
     *                             @SWG\Property( property="path", type="string", example="3,4", dscription="路径"),
     *                             @SWG\Property( property="sort", type="string", example="22222222222222", d    ecription="排序"),
     *                             @SWG\Property( property="is_main_category", type="string", example="true", d    ecription="是否为商品主类目"),
     *                             @SWG\Property( property="goods_params", type="array",
     *                                 @SWG\Items( type="string", example="undefined", description=""),
     *                             ),
     *                             @SWG\Property( property="goods_spec", type="array",
     *                                 @SWG\Items( type="string", example="undefined", description=""),
     *                             ),
     *                             @SWG\Property( property="category_level", type="string", example="2", d    ecription="商品分类等级"),
     *                             @SWG\Property( property="image_url", type="string", example="", d    ecription="元素配图"),
     *                             @SWG\Property( property="crossborder_tax_rate", type="string", example="15.56", d   escription="跨境税率，百分比，小数点2位"),
     *                             @SWG\Property( property="created", type="string", example="1560927610", d    ecription=""),
     *                             @SWG\Property( property="updated", type="string", example="1606369584", d    ecription="修改时间"),
     *                             @SWG\Property( property="category_code", type="string", example="null", d    ecription="分类编码"),
     *                             @SWG\Property( property="children", type="array",
     *                                 @SWG\Items( type="object",
     *                                     @SWG\Property( property="id", type="string", example="5", dscription=""),
     *                                     @SWG\Property( property="category_id", type="string", example="5", d    ecription="商品分类id"),
     *                                     @SWG\Property( property="company_id", type="string", example="1", d    ecription="公司id"),
     *                                     @SWG\Property( property="category_name", type="string", e    xmple="测试类目1-1-1", description="分类名称"),
     *                                     @SWG\Property( property="label", type="string", example="测试类目1-1-1", d   escription=""),
     *                                     @SWG\Property( property="parent_id", type="string", example="4", d    ecription="父分类id,顶级为0"),
     *                                     @SWG\Property( property="distributor_id", type="string", example="0", d   escription="分销商id"),
     *                                     @SWG\Property( property="path", type="string", example="3,4,5", d    ecription="路径"),
     *                                     @SWG\Property( property="sort", type="string", example="0", d    ecription="排序"),
     *                                     @SWG\Property( property="is_main_category", type="string", eample="true", d    escription="是否为商品主类目"),
     *                                     @SWG\Property( property="goods_params", type="string", example="2827", d   escription="商品参数"),
     *                                     @SWG\Property( property="goods_spec", type="array",
     *                                         @SWG\Items( type="string", example="1346", description=""),
     *                                     ),
     *                                     @SWG\Property( property="category_level", type="string", example="3", d   escription="商品分类等级"),
     *                                     @SWG\Property( property="image_url", type="string", example="", d    ecription="元素配图"),
     *                                     @SWG\Property( property="crossborder_tax_rate", type="string", e    xmple="15.4", description="跨境税率，百分比，小数点2位"),
     *                                     @SWG\Property( property="created", type="string", example="1560927610", d   escription=""),
     *                                     @SWG\Property( property="updated", type="string", example="1606369584", d   escription="修改时间"),
     *                                     @SWG\Property( property="category_code", type="string", example="null", d   escription="分类编码"),
     *                                     @SWG\Property( property="level", type="string", example="2", d    ecription=""),
     *                                 ),
     *                             ),
     *                             @SWG\Property( property="level", type="string", example="1", description=""),
     *                         ),
     *                     ),
     *                     @SWG\Property( property="level", type="string", example="0", description=""),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityItemCategory(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('activity_id', 'main_cat_id', 'cat_id', 'item_name', 'item_bn');
        $rules = [
            'activity_id' => ['required|integer', '活动ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $activityItemsService = new ActivityItemsService();
        $result = $activityItemsService->fetchActivityItemsCategory($authInfo['company_id'], $params['activity_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/employeepurchase/activity/behavior-report",
     *     summary="内购活动行为流水统一上报",
     *     tags={"内购"},
     *     description="同一 URL 支持 scan（扫码/进入）与 passphrase_verify（口令校验）。未登录须传 company_id；请求带有效 JWT 时 company_id、user_id 以登录态为准（勿伪造 user_id）。口令校验成败均写流水并带 result_status。",
     *     operationId="reportActivityBehavior",
     *     @SWG\Parameter( name="Authorization", in="header", description="可选；有效 JWT 时用于 company_id / user_id", required=false, type="string"),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             required={"behavior_type","activity_id","enterprise_id"},
     *             @SWG\Property( property="behavior_type", type="string", enum={"scan","passphrase_verify"}, description="scan=扫码流水；passphrase_verify=口令校验" ),
     *             @SWG\Property( property="company_id", type="integer", description="未登录必填；已登录忽略，以 token 为准" ),
     *             @SWG\Property( property="activity_id", type="integer" ),
     *             @SWG\Property( property="enterprise_id", type="integer" ),
     *             @SWG\Property( property="visitor_key", type="string", description="未登录建议传，便于 UV" ),
     *             @SWG\Property( property="passphrase_code", type="string", description="behavior_type=passphrase_verify 时必填（或与 code 二选一）" ),
     *             @SWG\Property( property="code", type="string" ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="scan 返回 status+log_id；passphrase_verify 返回 verified+log_id，且含 behavior_type",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="behavior_type", type="string" ),
     *                 @SWG\Property( property="log_id", type="integer" ),
     *                 @SWG\Property( property="status", type="boolean", description="仅 scan" ),
     *                 @SWG\Property( property="verified", type="boolean", description="仅 passphrase_verify" ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function reportActivityBehavior(Request $request)
    {
        $params = $request->all(
            'behavior_type',
            'company_id',
            'activity_id',
            'enterprise_id',
            'visitor_key',
            'passphrase_code',
            'code'
        );
        $rules = [
            'behavior_type' => ['required', '行为类型必填'],
            'activity_id' => ['required|integer', '活动ID必填'],
            'enterprise_id' => ['required|integer', '企业ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $auth = $request->get('auth');
        $userId = isset($auth['user_id']) ? (int) $auth['user_id'] : 0;
        if ($userId > 0) {
            $companyId = (int) ($auth['company_id'] ?? 0);
        } else {
            $companyId = isset($params['company_id']) ? (int) $params['company_id'] : 0;
        }
        if ($companyId <= 0) {
            throw new ResourceException('公司ID必填');
        }

        $activityId = (int) $params['activity_id'];
        $enterpriseId = (int) $params['enterprise_id'];
        $visitorKey = isset($params['visitor_key']) && $params['visitor_key'] !== '' ? (string) $params['visitor_key'] : null;

        $behaviorType = trim((string) $params['behavior_type']);
        if ($behaviorType === ActivityEnterpriseBehaviorLogService::BEHAVIOR_SCAN) {
            $logId = $this->writeActivityScanLog($companyId, $activityId, $enterpriseId, $userId > 0 ? $userId : null, $visitorKey);

            return $this->response->array([
                'behavior_type' => ActivityEnterpriseBehaviorLogService::BEHAVIOR_SCAN,
                'status' => true,
                'log_id' => $logId,
            ]);
        }

        if ($behaviorType === ActivityEnterpriseBehaviorLogService::BEHAVIOR_PASSPHRASE_VERIFY) {
            $passphrase = '';
            if (isset($params['passphrase_code']) && $params['passphrase_code'] !== '') {
                $passphrase = (string) $params['passphrase_code'];
            } elseif (isset($params['code']) && $params['code'] !== '') {
                $passphrase = (string) $params['code'];
            }
            if ($passphrase === '') {
                throw new ResourceException('口令必填');
            }

            $payload = $this->verifyActivityPassphraseCore(
                $companyId,
                $activityId,
                $enterpriseId,
                $passphrase,
                $userId > 0 ? $userId : null,
                $visitorKey
            );
            $payload['behavior_type'] = ActivityEnterpriseBehaviorLogService::BEHAVIOR_PASSPHRASE_VERIFY;

            return $this->response->array($payload);
        }

        throw new ResourceException('behavior_type 须为 scan 或 passphrase_verify');
    }

    /**
     * @return array{verified:bool,log_id:int}
     */
    private function verifyActivityPassphraseCore($companyId, $activityId, $enterpriseId, $passphrase, $userId, $visitorKey)
    {
        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (empty($activity)) {
            throw new ResourceException('活动不存在');
        }

        $verified = $activitiesService->isActivityEnterprisePassphraseMatch($activity, $enterpriseId, $passphrase);
        $resultStatus = $verified
            ? ActivityEnterpriseBehaviorLogService::RESULT_SUCCESS
            : ActivityEnterpriseBehaviorLogService::RESULT_FAIL;

        $logService = new ActivityEnterpriseBehaviorLogService();
        $logId = $logService->writeBehaviorLog(
            $companyId,
            $activityId,
            $enterpriseId,
            ActivityEnterpriseBehaviorLogService::BEHAVIOR_PASSPHRASE_VERIFY,
            $userId !== null && $userId > 0 ? $userId : null,
            $visitorKey,
            null,
            null,
            $resultStatus
        );

        if ($verified && $userId !== null && $userId > 0) {
            (new PassphraseVerifiedRedisService())->markVerified($companyId, $activityId, $enterpriseId, (int) $userId, $activity);
        }

        return ['verified' => $verified, 'log_id' => $logId];
    }

    /**
     * @param int|null $userId
     * @param string|null $visitorKey
     * @return int log id
     */
    private function writeActivityScanLog($companyId, $activityId, $enterpriseId, $userId, $visitorKey)
    {
        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (empty($activity)) {
            throw new ResourceException('活动不存在');
        }

        $allowed = $activitiesService->normalizeActivityEnterpriseIds($activity['enterprise_id'] ?? []);
        if (!in_array($enterpriseId, $allowed, true)) {
            throw new ResourceException('企业未参与该活动');
        }

        $logService = new ActivityEnterpriseBehaviorLogService();

        return $logService->writeBehaviorLog(
            $companyId,
            $activityId,
            $enterpriseId,
            ActivityEnterpriseBehaviorLogService::BEHAVIOR_SCAN,
            $userId > 0 ? $userId : null,
            $visitorKey,
            null,
            null
        );
    }
}
