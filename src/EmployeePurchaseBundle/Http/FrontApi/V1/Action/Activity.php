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

        $activitiesService = new ActivitiesService();
        $result = $activitiesService->getUserActivities($filter, '*', $page, $pageSize, ['display_time' => 'ASC']);

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
        }
        //根据参数判断，是否需要追加额度
        if(!empty($params['need_aggregate'])){
            $result['list'] = (new MemberActivityAggregateService())->getUserActivityDataList($filter['company_id'],$filter['user_id'],$result['list'],$enterpriseId);
        }

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
        if (isset($activityItemList[$result['item_id']])) {
            $result['activity_price'] = $activityItemList[$result['item_id']]['activity_price'];
            if (!$activity['if_share_store']) {
                $result['store'] = $activityItemList[$result['item_id']]['activity_store'];
            }
        } else {
            $result['store'] = 0;
            $result['approve_status'] = 'instock';
        }

        if (isset($result['nospec']) && ($result['nospec'] === false || $result['nospec'] === 'false') || $result['nospec'] === 0 || $result['nospec'] === '0') {
            foreach ($result['spec_items'] as $key => $item) {
                if (isset($activityItemList[$item['item_id']])) {
                    $result['spec_items'][$key]['activity_price'] = $activityItemList[$item['item_id']]['activity_price'];
                    if (!$activity['if_share_store']) {
                        $result['spec_items'][$key]['store'] = $activityItemList[$item['item_id']]['activity_store'];
                    }
                } else {
                    $result['spec_items'][$key]['store'] = 0;
                    $result['spec_items'][$key]['approve_status'] = 'instock';
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
}
