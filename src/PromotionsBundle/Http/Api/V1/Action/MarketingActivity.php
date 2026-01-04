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

use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsTagsService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Entities\MarketingActivityCategory;
use PromotionsBundle\Services\MarketingActivityService;
use DistributionBundle\Services\DistributorService;
use PromotionsBundle\Traits\CheckMarketingRulesParams;
use PromotionsBundle\Traits\CheckPromotionsRules;

class MarketingActivity extends Controller
{
    use CheckMarketingRulesParams;
    use CheckPromotionsRules;

    public function __construct()
    {
        $this->service = new MarketingActivityService();
    }

    /**
      * @SWG\Post(
      *     path="/marketing/create",
      *     summary="创建满折促销活动",
      *     tags={"营销"},
      *     description="创建满折、满减、满赠、加价购、会员优先购促销活动",
      *     operationId="createMarketingActivity",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_name", in="formData", description="营销活动名称", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_type", in="formData", description="营销类型", required=true, type="string"),
      *     @SWG\Parameter( name="promotion_tag", in="formData", description="营销标签:满折", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_desc", in="formData", description="营销活动描述", required=true, type="string"),
      *     @SWG\Parameter( name="start_time", in="formData", description="活动开始时间", required=true, type="integer"),
      *     @SWG\Parameter( name="end_time", in="formData", description="活动结束时间", required=true, type="integer"),
      *     @SWG\Parameter( name="used_platform", in="formData", description="适用平台 0:全场适用", required=true, type="integer"),
      *     @SWG\Parameter( name="item_ids", in="formData", description="适用商品集合", type="integer"),
      *     @SWG\Parameter( name="use_bound", in="formData", description="适用范围 all:全部商品 goods:指定商品 category:指定分类 tag:指定标签 brand:指定品牌", type="string"),
      *     @SWG\Parameter( name="item_category", in="formData", description="分类id集合，use_bound=category时必填", type="string"),
      *     @SWG\Parameter( name="tag_ids", in="formData", description="标签id集合，use_bound=tag时必填", type="string"),
      *     @SWG\Parameter( name="brand_ids", in="formData", description="品牌id集合，use_bound=brand时必填", type="string"),
      *     @SWG\Parameter( name="use_shop", in="formData", description="适用店铺", type="string"),
      *     @SWG\Parameter( name="shop_ids", in="formData", description="适用店铺集合", type="string"),
      *     @SWG\Parameter( name="valid_grade", in="formData", description="适用会员等级集合", type="string"),
      *     @SWG\Parameter( name="condition_type", in="formData", description="营销条件标准(quantity, totalfee)", type="string"),
      *     @SWG\Parameter( name="condition_value", in="formData", description="营销规则值", required=true, type="string"),
      *     @SWG\Parameter( name="join_limit", in="formData", description="可参与次数", required=true, type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="marketing_id", type="integer"),
      *                 @SWG\Property(property="company_id", type="integer", example="1"),
      *                 @SWG\Property(property="marketing_name", type="integer", example="1", description="营销活动名称"),
      *                 @SWG\Property(property="marketing_desc", type="integer", example="1", description="营销活动描述"),
      *                 @SWG\Property(property="start_time", type="string", example="1"),
      *                 @SWG\Property(property="end_time", type="string", example="1"),
      *                 @SWG\Property(property="used_platform", type="string", example="1", description="适用平台:  0:全场可用,1:只用于pc端,2:小程序端,3:h5端 暂时只支持0"),
      *                 @SWG\Property(property="use_bound", type="string", example="all", description="适用范围 all:全部商品 goods:指定商品 category:指定分类 tag:指定标签 brand:指定品牌"),
      *                 @SWG\Property(property="use_shop", type="string", example="1", description="适用店铺: 0:全场可用,1:指定店铺可用"),
      *                 @SWG\Property(property="shop_ids", type="string", example="1", description="店铺id数组"),
      *                 @SWG\Property(property="valid_grade", type="string", example="1", description="会员级别集合"),
      *                 @SWG\Property(property="condition_type", type="string", example="totalfee", description="营销条件标准  quantity:按总件数, totalfee:按总金额"),
      *                 @SWG\Property(property="marketing_type", type="string", example="1", description="营销类型"),
      *                 @SWG\Property(property="canjoin_repeat", type="string", example="1", description="是否上不封顶"),
      *                 @SWG\Property(property="condition_value", type="object",
      *                     @SWG\Property(property="full", type="string", example="1", description="满x元或满x件"),
      *                         @SWG\Property(property="discount", type="string", example="1", description="折扣比例（%）"),
      *                         ),
      *                 @SWG\Property(property="promotion_tag", type="string", example="满折", description="促销标签"),
      *                 @SWG\Property(property="in_proportion", type="boolean", example="true", description="是否按比例多次赠送"),
      *                 @SWG\Property(property="join_limit", type="string", example="1", description="可参与次数"),
      *                 @SWG\Property(property="created", type="string", example="1"),
      *                 @SWG\Property(property="updated", type="string", example="1"),
      *                 @SWG\Property(property="check_status", type="string", example="agree", description="促销状态:  non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function createMarketingActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        if (isset($params['condition_value']) && !is_array($params['condition_value'])) {
            $params['condition_value'] = json_decode($params['condition_value'], true);
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $distributor_id = $request->get('distributor_id');
            $params['shop_ids'] = [$distributor_id];
        } else {
            if ($params['shop_ids'] ?? null) {
                $params['shop_ids'] = is_array($params['shop_ids']) ? $params['shop_ids'] : json_decode($params['shop_ids'], true);
            } else {
                $params['shop_ids'] = [0];//没有选择店铺，则限定仅平台可用
            }
        }

        if ($params['item_ids'] ?? null) {
            $params['item_ids'] = is_array($params['item_ids']) ? $params['item_ids'] : json_decode($params['item_ids'], true);
        }
        if ($params['use_bound'] == 'all') {
            $params['use_bound'] = 0;
        } elseif ($params['use_bound'] == 'goods' || $params['use_bound'] == 1) {
            $params['use_bound'] = 1;
        } elseif ($params['use_bound'] == 'category' || $params['use_bound'] == 2) {
            $params['use_bound'] = 2;
            if (empty($params['item_category'])) {
                throw new ResourceException(trans('PromotionsBundle.select_main_category'));
            }
            if (is_string($params['item_category'])) {
                $params['item_category'] = json_decode($params['item_category'], true);
            }
        } elseif ($params['use_bound'] == 'tag' || $params['use_bound'] == 3) {
            if (!isset($params['tag_ids']) || empty($params['tag_ids'])) {
                throw new ResourceException(trans('PromotionsBundle.select_tags'));
            }
            if (is_string($params['tag_ids'])) {
                $params['tag_ids'] = json_decode($params['tag_ids'], true);
            }
            $params['use_bound'] = 3;
        } elseif ($params['use_bound'] == 'brand' || $params['use_bound'] == 4) {
            if (!isset($params['brand_ids']) || empty($params['brand_ids'])) {
                throw new ResourceException(trans('PromotionsBundle.select_brand'));
            }
            if (is_string($params['brand_ids'])) {
                $params['brand_ids'] = json_decode($params['brand_ids'], true);
            }
            $params['use_bound'] = 4;
        }

        $params['source_id'] = $authUser['distributor_id'];//如果是平台，这里是0
        $params['source_type'] = $authUser['operator_type'];//如果是平台，这里是admin

        $checkResult = $this->checkAddPromotionData($params);
        if (!$checkResult) {
            throw new ResourceException(trans('PromotionsBundle.parameter_error'));
        }

        $checkResult = $this->checkActivityValidByMarketing($params);
        if (!$checkResult) {
            throw new ResourceException(trans('PromotionsBundle.parameter_error'));
        }
        $result = $this->service->create($params);
        return $this->response->array($result);
    }

    /**
      * @SWG\Delete(
      *     path="/marketing/delete",
      *     summary="删除满折促销活动",
      *     tags={"营销"},
      *     description="删除满折促销活动",
      *     operationId="deleteMarketingActivity",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_id", in="query", description="活动id", type="integer"),
      *     @SWG\Parameter( name="isEnd", in="query", description="是否终止 不传或为false时，删除活动", type="boolean"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function deleteMarketingActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['marketing_id'] = $request->input('marketing_id');
        if (!$request->input('isEnd') || $request->get('isEnd') === 'false') {
            $result['status'] = $this->service->deleteMarketingActivity($filter);
        } else {
            $result['status'] = $this->service->endActivity($filter['company_id'], $filter['marketing_id']);
        }
        return $this->response->array($result);
    }

    /**
      * @SWG\Put(
      *     path="/promotions/marketingActivity/updatestatus",
      *     summary="结束满折促销活动状态",
      *     tags={"营销"},
      *     description="结束满折促销活动状态",
      *     operationId="updateStatus",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_id", in="query", description="活动名称", type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function updateStatus(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $result = $this->service->endActivity($authUser['company_id'], $request->input('marketing_id'));
        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\Put(
      *     path="/marketing/update",
      *     summary="修改满折促销活动",
      *     tags={"营销"},
      *     description="修改满折促销活动",
      *     operationId="updateMarketingActivity",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_id", in="formData", description="营销Id", required=true, type="integer"),
      *     @SWG\Parameter( name="marketing_name", in="formData", description="营销活动名称", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_type", in="formData", description="营销类型", required=true, type="string"),
      *     @SWG\Parameter( name="promotion_tag", in="formData", description="营销标签:满折", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_desc", in="formData", description="营销活动描述", required=true, type="string"),
      *     @SWG\Parameter( name="start_time", in="formData", description="活动开始时间", required=true, type="integer"),
      *     @SWG\Parameter( name="end_time", in="formData", description="活动结束时间", required=true, type="integer"),
      *     @SWG\Parameter( name="used_platform", in="formData", description="适用平台 0:全场适用", required=true, type="integer"),
      *     @SWG\Parameter( name="item_ids", in="formData", description="适用商品集合", type="integer"),
      *     @SWG\Parameter( name="use_bound", in="formData", description="适用范围 all:全部商品 goods:指定商品 category:指定分类 tag:指定标签 brand:指定品牌", type="string"),
      *     @SWG\Parameter( name="item_category", in="formData", description="分类id集合，use_bound=category时必填", type="string"),
      *     @SWG\Parameter( name="tag_ids", in="formData", description="标签id集合，use_bound=tag时必填", type="string"),
      *     @SWG\Parameter( name="brand_ids", in="formData", description="品牌id集合，use_bound=brand时必填", type="string"),
      *     @SWG\Parameter( name="use_shop", in="formData", description="适用店铺", type="string"),
      *     @SWG\Parameter( name="shop_ids", in="formData", description="适用店铺集合", type="string"),
      *     @SWG\Parameter( name="valid_grade", in="formData", description="适用会员等级集合", type="string"),
      *     @SWG\Parameter( name="condition_type", in="formData", description="营销条件标准(quantity, totalfee)", type="string"),
      *     @SWG\Parameter( name="condition_value", in="formData", description="营销规则值", required=true, type="string"),
      *     @SWG\Parameter( name="join_limit", in="formData", description="可参与次数", required=true, type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="marketing_id", type="integer"),
      *                 @SWG\Property(property="company_id", type="integer", example="1"),
      *                 @SWG\Property(property="marketing_name", type="integer", example="1", description="营销活动名称"),
      *                 @SWG\Property(property="marketing_desc", type="integer", example="1", description="营销活动描述"),
      *                 @SWG\Property(property="start_time", type="string", example="1"),
      *                 @SWG\Property(property="end_time", type="string", example="1"),
      *                 @SWG\Property(property="used_platform", type="string", example="1", description="适用平台:  0:全场可用,1:只用于pc端,2:小程序端,3:h5端 暂时只支持0"),
      *                 @SWG\Property(property="use_bound", type="string", example="all", description="适用范围 all:全部商品 goods:指定商品 category:指定分类 tag:指定标签 brand:指定品牌"),
      *                 @SWG\Property(property="use_shop", type="string", example="1", description="适用店铺: 0:全场可用,1:指定店铺可用"),
      *                 @SWG\Property(property="shop_ids", type="string", example="1", description="店铺id数组"),
      *                 @SWG\Property(property="valid_grade", type="string", example="1", description="会员级别集合"),
      *                 @SWG\Property(property="condition_type", type="string", example="totalfee", description="营销条件标准  quantity:按总件数, totalfee:按总金额"),
      *                 @SWG\Property(property="marketing_type", type="string", example="1", description="营销类型"),
      *                 @SWG\Property(property="canjoin_repeat", type="string", example="1", description="是否上不封顶"),
      *                 @SWG\Property(property="condition_value", type="object",
      *                     @SWG\Property(property="full", type="string", example="1", description="满x元或满x件"),
      *                         @SWG\Property(property="discount", type="string", example="1", description="折扣比例（%）"),
      *                         ),
      *                 @SWG\Property(property="promotion_tag", type="string", example="满折", description="促销标签"),
      *                 @SWG\Property(property="in_proportion", type="boolean", example="true", description="是否按比例多次赠送"),
      *                 @SWG\Property(property="join_limit", type="string", example="1", description="可参与次数"),
      *                 @SWG\Property(property="created", type="string", example="1"),
      *                 @SWG\Property(property="updated", type="string", example="1"),
      *                 @SWG\Property(property="check_status", type="string", example="agree", description="促销状态:  non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function updateMarketingActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        if (isset($params['condition_value']) && !is_array($params['condition_value'])) {
            $params['condition_value'] = json_decode($params['condition_value'], true);
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $distributor_id = $request->get('distributor_id');
            $params['shop_ids'] = [$distributor_id];
        } else {
            if ($params['shop_ids'] ?? null) {
                $params['shop_ids'] = is_array($params['shop_ids']) ? $params['shop_ids'] : json_decode($params['shop_ids'], true);
            } else {
                $params['shop_ids'] = [0];//没有选择店铺，则限定仅平台可用
            }
        }

        if ($params['item_ids'] ?? null) {
            $params['item_ids'] = is_array($params['item_ids']) ? $params['item_ids'] : json_decode($params['item_ids'], true);
        }

        if ($params['use_bound'] == 'all') {
            $params['use_bound'] = 0;
        } elseif ($params['use_bound'] == 'goods' || $params['use_bound'] === 1) {
            $params['use_bound'] = 1;
            $params['tag_ids'] = [];
            $params['brand_ids'] = [];
        } elseif ($params['use_bound'] == 'category' || $params['use_bound'] === 2) {
            if (empty($params['item_category'])) {
                throw new ResourceException(trans('PromotionsBundle.select_main_category'));
            }
            if (is_string($params['item_category'])) {
                $params['item_category'] = json_decode($params['item_category'], true);
            }
            $params['use_bound'] = 2;
            $params['tag_ids'] = [];
            $params['brand_ids'] = [];
        } elseif ($params['use_bound'] == 'tag' || $params['use_bound'] === 3) {
            if (!isset($params['tag_ids']) || empty($params['tag_ids'])) {
                throw new ResourceException(trans('PromotionsBundle.select_tags'));
            }
            if (is_string($params['tag_ids'])) {
                $params['tag_ids'] = json_decode($params['tag_ids'], true);
            }
            $params['use_bound'] = 3;
            $params['brand_ids'] = [];
        } elseif ($params['use_bound'] == 'brand' || $params['use_bound'] === 4) {
            if (!isset($params['brand_ids']) || empty($params['brand_ids'])) {
                throw new ResourceException(trans('PromotionsBundle.select_brand'));
            }
            if (is_string($params['brand_ids'])) {
                $params['brand_ids'] = json_decode($params['brand_ids'], true);
            }
            $params['use_bound'] = 4;
            $params['tag_ids'] = [];
        }

        $params['source_id'] = $authUser['distributor_id'];//如果是平台，这里是0
        $params['source_type'] = $authUser['operator_type'];//如果是平台，这里是admin

        $checkResult = $this->checkAddPromotionData($params);
        if (!$checkResult) {
            throw new ResourceException(trans('PromotionsBundle.parameter_error'));
        }

        $checkResult = $this->checkActivityValidByMarketing($params);
        if (!$checkResult) {
            throw new ResourceException(trans('PromotionsBundle.parameter_error'));
        }

        $marketingInfo = $this->service->getInfo(['marketing_id' => $params['marketing_id'] ]);
        if (time() > $marketingInfo['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_ended_cannot_modify'));
        }
        
        $filter['company_id'] = $params['company_id'];
        $filter['marketing_id'] = $params['marketing_id'];
        $result = $this->service->updateActivity($filter, $params);
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/marketing/getinfo",
      *     summary="获取满折促销活动详情",
      *     tags={"营销"},
      *     description="获取满折促销活动详情",
      *     operationId="getMarketingActivityInfo",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_id", in="query", description="活动名称", type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="marketing_id", type="string", example="171", description="营销id"),
     *                  @SWG\Property( property="marketing_type", type="string", example="full_discount", description="营销类型: full_discount:满折,full_minus:满减,full_gift:满赠"),
     *                  @SWG\Property( property="rel_marketing_id", type="string", example="0", description="关联其他营销id"),
     *                  @SWG\Property( property="marketing_name", type="string", example="fff", description="营销活动名称"),
     *                  @SWG\Property( property="marketing_desc", type="string", example="1", description="营销活动描述"),
     *                  @SWG\Property( property="start_time", type="string", example="2020-12-17 00:00:00", description="开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="2021-01-15 16:20:37", description="结束时间"),
     *                  @SWG\Property( property="start_date", type="string", example="2020/12/17 00:00:00", description="开始日期"),
     *                  @SWG\Property( property="end_date", type="string", example="2021/01/15 16:20:37", description="结束日期"),
     *                  @SWG\Property( property="release_time", type="string", example="null", description="活动发布时间"),
     *                  @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端"),
     *                  @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                  @SWG\Property( property="tag_ids", type="array",
     *                      @SWG\Items( type="string", example="98", description="商品标签id"),
     *                  ),
     *                  @SWG\Property( property="brand_ids", type="array",
     *                      @SWG\Items( type="string", example="", description="商品品牌id"),
     *                  ),
     *                  @SWG\Property( property="use_shop", type="string", example="1", description="适用店铺: 0:全场可用,1:指定店铺可用"),
     *                  @SWG\Property( property="shop_ids", type="array",
     *                      @SWG\Items( type="string", example="", description="店铺id"),
     *                  ),
     *                  @SWG\Property( property="valid_grade", type="array",
     *                      @SWG\Items( type="string", example="4", description="会员等级id"),
     *                  ),
     *                  @SWG\Property( property="condition_type", type="string", example="totalfee", description="营销条件标准 quantity:按总件数, totalfee:按总金额"),
     *                  @SWG\Property( property="condition_value", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="full", type="string", example="1000", description="满x元或满x件"),
     *                          @SWG\Property( property="discount", type="string", example="90", description="折扣额度%"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="in_proportion", type="string", example="false", description="是否按比例多次赠送"),
     *                  @SWG\Property( property="canjoin_repeat", type="string", example="false", description="是否上不封顶"),
     *                  @SWG\Property( property="join_limit", type="string", example="10", description="可参与次数"),
     *                  @SWG\Property( property="promotion_tag", type="string", example="满折", description="促销标签"),
     *                  @SWG\Property( property="check_status", type="string", example="agree", description="促销状态: non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
     *                  @SWG\Property( property="reason", type="string", example="null", description="审核失败原因"),
     *                  @SWG\Property( property="created", type="string", example="1608879873", description=""),
     *                  @SWG\Property( property="created_date", type="string", example="2020/12/25 15:04:33", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1610698837", description=""),
     *                  @SWG\Property( property="items", type="array",
     *                      @SWG\Items( type="string", example="", description="适用商品id"),
     *                  ),
     *                  @SWG\Property( property="itemTreeLists", type="array",
     *                      @SWG\Items( type="string", example="", description="适用商品数据"),
     *                  ),
     *                  @SWG\Property( property="storeLists", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="107", description="店铺ID"),
     *                          @SWG\Property( property="address", type="string", example="路镇", description="店铺地址"),
     *                          @SWG\Property( property="name", type="string", example="重庆江北国际机场国内候机楼T2B航站楼", description="店铺名称"),
     *                          @SWG\Property( property="store_name", type="string", example="重庆江北国际机场国内候机楼T2B航站楼", description="店铺名称"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="rel_category_ids", type="array",
     *                      @SWG\Items( type="string", example="", description="关联分类id"),
     *                  ),
     *                  @SWG\Property( property="item_category", type="array",
     *                      @SWG\Items( type="string", example="", description="关联分类"),
     *                  ),
     *                  @SWG\Property( property="rel_tag_ids", type="array",
     *                      @SWG\Items( type="string", example="98", description="关联标签id"),
     *                  ),
     *                  @SWG\Property( property="tag_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="98", description="标签id"),
     *                          @SWG\Property( property="tag_name", type="string", example="树脂", description="标签名称"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="rel_brand_ids", type="array",
     *                      @SWG\Items( type="string", example="", description="关联品牌id"),
     *                  ),
     *                  @SWG\Property( property="brand_list", type="array",
     *                      @SWG\Items( type="string", example="", description="关联品牌数据列表"),
     *                  ),
     *          ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function getMarketingActivityInfo(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['marketing_id'] = $request->input('marketing_id');
        $result = $this->service->getMarketingLists($filter);
        $result = $result['list'][0];
        $result['start_time'] = date('Y-m-d H:i:s', $result['start_time']);
        $result['end_time'] = date('Y-m-d H:i:s', $result['end_time']);
        if ($result['shop_ids'] && array_filter($result['shop_ids'])) {
            $distributorService = new DistributorService();
            $filter = [
                'company_id' => $authUser['company_id'],
                'distributor_id' => array_filter($result['shop_ids']),
            ];
            $result['storeLists'] = $distributorService->lists($filter)['list'];
        }
        //获取分类
        $entityRelCategoryRepository = app('registry')->getManager('default')->getRepository(MarketingActivityCategory::class);
        $relCategory = $entityRelCategoryRepository->lists(['company_id' => $result['company_id'], 'marketing_id' => $result['marketing_id']]);
        if ($relCategory) {
            $categoryIds = array_filter(array_column($relCategory['list'], 'category_id'));
            $result['rel_category_ids'] = $categoryIds;
            $result['item_category'] = $categoryIds;
        }

        $result['rel_tag_ids'] = $result['tag_ids'];

        //获取商品标签
        $itemsTagService = new ItemsTagsService();
        $tagFilter['tag_id'] = $result['tag_ids'];
        $tagFilter['company_id'] = $filter['company_id'];
        $tagList = $itemsTagService->getListTags($tagFilter);
        $result['tag_list'] = $tagList['list'];

        $result['rel_brand_ids'] = $result['brand_ids'];
        //获取品牌
        $itemsAttributesService = new ItemsAttributesService();
        $brandFilter['attribute_id'] = $result['brand_ids'];
        $brandFilter['company_id'] = $filter['company_id'];
        $brandFilter['attribute_type'] = 'brand';

        $brandList = $itemsAttributesService->lists($brandFilter, 1, -1);
        $result['brand_list'] = $brandList['list'];
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/marketing/getlist",
      *     summary="获取满折促销活动列表",
      *     tags={"营销"},
      *     description="获取满折促销活动列表",
      *     operationId="getMarketingActivityList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_id", in="query", description="活动id", type="integer"),
      *     @SWG\Parameter( name="marketing_name", in="query", description="活动名称", type="string"),
      *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", required=true, type="string"),
      *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_type", in="query", description="营销活动类型", type="string"),
      *     @SWG\Parameter( name="item_type", in="query", description="商品类型", type="string"),
      *     @SWG\Parameter( name="status", in="query", description="活动状态", type="string"),
      *     @SWG\Parameter( name="store_id", in="query", description="店铺id", type="string"),
      *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="array",
      *                 @SWG\Items(
      *                     type="object",
      *                     @SWG\Property(property="marketing_id", type="integer"),
      *                     @SWG\Property(property="company_id", type="integer", example="1"),
      *                     @SWG\Property(property="marketing_name", type="integer", example="1"),
      *                     @SWG\Property(property="marketing_desc", type="integer", example="1"),
      *                     @SWG\Property(property="start_time", type="string", example="1"),
      *                     @SWG\Property(property="end_time", type="string", example="1"),
      *                     @SWG\Property(
      *                          property="items",
      *                          type="array",
      *                          @SWG\Items(
      *                               type="object",
      *                               @SWG\Property(property="marketing_id", type="integer"),
      *                               @SWG\Property(property="item_id", type="integer", example="1"),
      *                               @SWG\Property(property="item_title", type="string", example="1"),
      *                               @SWG\Property(property="price", type="integer", example="1"),
      *                               @SWG\Property(property="store", type="integer", example="1"),
      *                               @SWG\Property(property="company_id", type="integer", example="1"),
      *                          )
      *                     ),
      *                     @SWG\Property(property="used_platform", type="string", example="1"),
      *                     @SWG\Property(property="use_bound", type="string", example="1"),
      *                     @SWG\Property(property="use_shop", type="string", example="1"),
      *                     @SWG\Property(property="shop_ids", type="string", example="1"),
      *                     @SWG\Property(property="valid_grade", type="string", example="1"),
      *                     @SWG\Property(property="condition_type", type="string", example="1"),
      *                     @SWG\Property(property="marketing_type", type="string", example="1"),
      *                     @SWG\Property(property="condition_value", type="string", example="1"),
      *                     @SWG\Property(property="promotion_tag", type="string", example="1"),
      *                     @SWG\Property(property="free_postage", type="string", example="1"),
      *                     @SWG\Property(property="join_limit", type="string", example="1"),
      *                     @SWG\Property(property="created", type="string", example="1"),
      *                     @SWG\Property(property="updated", type="string", example="1"),
      *                 )
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function getMarketingActivityList(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        if (!$request->input('marketing_type')) {
            throw new ResourceException('营销类型必填');
        }
        $filter['marketing_type'] = $request->input('marketing_type');
        $input = $request->all('marketing_id', 'marketing_name', 'start_time', 'end_time', 'status');

        if ($input['status']) {
            switch ($input['status']) {
                case "waiting":
                    $filter['start_time|gte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "ongoing":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "end":
                    // $filter['start_time|lte'] = time();
                    $filter['end_time|lte'] = time();
                    break;
            }
        }

        if ($input['marketing_id']) {
            $filter['marketing_id'] = $input['marketing_id'];
        }

        if ($input['marketing_name']) {
            $filter['marketing_name|contains'] = $input['marketing_name'];
        }

        if ($request->input('item_type')) {
            $filter['item_type'] = $request->input('item_type');
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') { //店铺端
            $distributor_id = $request->get('distributor_id');
            $filter['shop_ids|contains'] = ','.$distributor_id.',';
        } else {
          if ($request->input('store_id')) {
              $filter['shop_ids|contains'] = ','.$request->input('store_id').',';
          }
        }

        if (isset($input['start_time'],$input['end_time']) && $input['start_time'] && $input['end_time']) {
            $filter['created|gte'] = $input['start_time'];
            $filter['created|lte'] = $input['end_time'];
        }

        $sourceId = floatval($request->get('distributor_id', 0));//如果是平台，这里是0
        $sourceType = app('auth')->user()->get('operator_type');//如果是平台，这里是admin
        if ($sourceId > 0) {
            switch ($sourceType) {
                case 'distributor'://按店铺ID筛选
                    $filter['source_id'] = $sourceId;
                    $filter['source_type'] = $sourceType;
                    break;
            }
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['marketing_id' => 'desc'];
        $result = $this->service->getMarketingLists($filter, $page, $pageSize, $orderBy, false);
        if ($result['list']) {
            $result = $this->__getSourceName($result);//获取店铺名称

            $memberGrade = $this->service->getMemberGrade($filter['company_id']);
            foreach ($result['list'] as &$value) {
                if ($value['source_id'] != $sourceId) {
                    if ($value['source_type'] == 'staff' && $sourceId == 0) {
                        $value['edit_btn'] = 'Y';//平台子账号创建的促销，超管可以编辑
                    } else {
                        $value['edit_btn'] = 'N';//屏蔽编辑按钮，平台只能编辑自己的促销
                    }
                } else {
                    $value['edit_btn'] = 'Y';
                }
                foreach ($value['valid_grade'] as $k => $key) {
                    if (isset($memberGrade[$key])) {
                        $value['member_grade'][$k] = $memberGrade[$key]['grade_name'];
                    }
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
      *     path="/promotions/marketingActivity/getIteminfo",
      *     summary="获取满折促销活动商品列表",
      *     tags={"营销"},
      *     description="获取满折促销活动商品列表",
      *     operationId="getActivityItemList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="marketing_id", in="query", description="活动名称", type="integer"),
      *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="833", description="id"),
     *                          @SWG\Property( property="marketing_id", type="string", example="185", description="营销id"),
     *                          @SWG\Property( property="item_id", type="string", example="5464", description="商品id"),
     *                          @SWG\Property( property="goods_id", type="string", example="5464", description="商品id"),
     *                          @SWG\Property( property="item_name", type="string", example="test", description="商品名称"),
     *                          @SWG\Property( property="price", type="string", example="100", description="销售金额,单位为‘分’"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkrdmNIwlkD4koutKj8O1MQT6RHC9IwRSrf25D5euwlS0a0lvibsdUiaNmlINbTGt7qMB0gBHiaheq7K8Q/0?wx_fmt=png", description="商品图片"),
     *                          ),
     *                          @SWG\Property( property="promotion_tag", type="string", example="满折", description="促销标签"),
     *                          @SWG\Property( property="store", type="string", example="123", description="商品库存"),
     *                          @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                          ),
     *                          @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                          @SWG\Property( property="point", type="string", example="0", description="积分个数"),
     *                          @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="商品是否为默认商品"),
     *                          @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                          @SWG\Property( property="default_item_id", type="string", example="5464", description="默认商品ID"),
     *                          @SWG\Property( property="itemId", type="string", example="5464", description="商品id"),
     *                          @SWG\Property( property="itemName", type="string", example="test", description="商品名称"),
     *                          @SWG\Property( property="itemBn", type="string", example="S601379DF21D83", description="商品编号"),
     *                          @SWG\Property( property="item_spec_desc", type="string", example="颜色:彩色,颜色:黄,长度:120", description="规格描述"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="activity", type="object",
     *                          @SWG\Property( property="marketing_id", type="string", example="185", description="营销id"),
     *                          @SWG\Property( property="marketing_type", type="string", example="full_discount", description="营销类型: full_discount:满折,full_minus:满减,full_gift:满赠"),
     *                          @SWG\Property( property="marketing_name", type="string", example="test", description="营销活动名称"),
     *                          @SWG\Property( property="marketing_desc", type="string", example="44234", description="营销活动描述"),
     *                  ),
     *          ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function getActivityItemList(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['marketing_id'] = $request->input('marketing_id');
        $result = $this->service->getActivityItemList($filter, $page, $pageSize);
        return $this->response->array($result);
    }
}
