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

use PromotionsBundle\Services\ActiveArticlesService;
use PromotionsBundle\Services\PromotionActivity;

use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use WechatBundle\Traits\AuthorizerWxapp;

class ActivityPromotions extends Controller
{
    use AuthorizerWxapp;

    /**
     * @SWG\Definition(
     * definition="ActivityDetail",
     * type="object",
     * @SWG\Property( property="activity_id", type="string", example="3", description="活动ID"),
     * @SWG\Property( property="activity_status", type="string", example="valid", description="活动状态 valid:有效 invalid:无效"),
     * @SWG\Property( property="status", type="string", example="ready", description="活动状态"),
     * @SWG\Property( property="title", type="string", example="会员日送优惠券", description="活动名称"),
     * @SWG\Property( property="begin_time", type="string", example="2021-03-01", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="2021-03-10", description="活动结束时间"),
     * @SWG\Property( property="is_forever", type="boolean", example=false, description="是否永久有效"),
     * @SWG\Property( property="trigger_condition", type="object",
     * @SWG\Property( property="trigger_time", type="object", description="日期数据",
     *     @SWG\Property( property="type", type="string", example="every_week", description="类型 every_year:每年，every_month:每月，every_week:每周"),
     *     @SWG\Property( property="month", type="string", example="", description="月份"),
     *     @SWG\Property( property="week", type="string", example="4", description="星期值"),
     *     @SWG\Property( property="day", type="string", example="", description="日期"),
     *     ),
     * ),
     * @SWG\Property( property="discount_config", type="object",
     *     @SWG\Property(
     *         property="coupons", type="object", description="每个等级赠送的优惠券数据",
     *         @SWG\Property(
     *             property="4", type="array", description="等级id",
     *             @SWG\Items( type="object",
     *                 @SWG\Property(property="id", type="string", example="1", description="id"),
     *                 @SWG\Property(property="count", type="string", example="1", description="赠送数量"),
     *                 @SWG\Property(property="name", type="string", example="品牌折扣券", description="优惠券名称"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Property(
     *         property="goods", type="object", description="每个等级赠送的服务类商品数据",
     *         @SWG\Property(
     *             property="4", type="array", description="等级id",
     *             @SWG\Items( type="object",
     *                 @SWG\Property(property="id", type="string", example="1", description="id"),
     *                 @SWG\Property(property="count", type="string", example="1", description="赠送数量"),
     *                 @SWG\Property(property="name", type="string", example="品牌折扣券", description="商品名称"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Property(property="version", type="string", example="1", description="版本"),
     * ),
     * @SWG\Property( property="sms_isopen", type="string", example="false", description="是否开启短信通知"),
     * @SWG\Property( property="sms_params", type="object",
     *     @SWG\Property(property="app_name", type="string", example="", description="小程序名称"),
     * ),
     * @SWG\Property( property="created", type="string", example="2021-02-01 11:09:22", description=""),
     * @SWG\Property( property="updated", type="string", example="2021-02-01 11:09:22", description=""),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/promotions/activity/validNum",
     *     summary="检查当前营销活动的有效数量",
     *     tags={"营销"},
     *     description="检查当前营销活动是否可以添加",
     *     operationId="checkActiveValidNum",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_type", in="query", description="活动类型 member_birthday:生日关怀 member_upgrade:会员升级 member_vip_upgrade:付费会员升级 member_anniversary:入会周年 member_day:会员日", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function checkActiveValidNum(Request $request)
    {
        $activityType = $request->input('activity_type');
        $companyId = app('auth')->user()->get('company_id');

        $promotionActivity = new PromotionActivity();
        $result = $promotionActivity->checkActiveValidNum($companyId, $activityType);

        return $this->response->array(['data' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/activity/invalid",
     *     summary="将当前活动失效",
     *     tags={"营销"},
     *     description="将当前活动失效",
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
     *                 ref="#/definitions/ActivityDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateStatusInvalid(Request $request)
    {
        $activityId = $request->input('activity_id');
        $companyId = app('auth')->user()->get('company_id');

        $promotionActivity = new PromotionActivity();
        $filter = [
            'company_id' => $companyId,
            'activity_id' => $activityId
        ];
        $result = $promotionActivity->updateOneBy($filter, ['activity_status' => 'invalid']);
        return $this->response->array(['data' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/activity/create",
     *     summary="创建营销活动",
     *     tags={"营销"},
     *     description="创建营销活动",
     *     operationId="checkActiveValidNum",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_type", in="formData", description="活动类型 member_birthday:生日关怀 member_upgrade:会员升级 member_vip_upgrade:付费会员升级 member_anniversary:入会周年 member_day:会员日", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][type]", in="formData", description="日期数据,类型 every_year:每年，every_month:每月，every_week:每周", type="string", required=true),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][month]", in="formData", description="日期数据,月份", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][week]", in="formData", description="日期数据,星期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][day]", in="formData", description="日期数据,日期", type="string", required=false),
     *     @SWG\Parameter( name="discount_config[coupons][4][0][id]", in="formData", description="活动优惠信息 会员等级对应要送的优惠券信息id", required=true, type="string"),
     *     @SWG\Parameter( name="discount_config[coupons][4][0][count]", in="formData", description="活动优惠信息 会员等级对应要送的优惠券信息数量", required=true, type="string"),
     *     @SWG\Parameter( name="discount_config[coupons][4][0][name]", in="formData", description="活动优惠信息 会员等级对应要送的优惠券信息名称", required=true, type="string"),
     *     @SWG\Parameter( name="discount_config[goods][4][0][id]", in="formData", description="活动优惠信息 会员等级对应要送的服务类商品信息id", required=true, type="string"),
     *     @SWG\Parameter( name="discount_config[goods][4][0][count]", in="formData", description="活动优惠信息 会员等级对应要送的服务类商品信息数量", required=true, type="string"),
     *     @SWG\Parameter( name="discount_config[goods][4][0][name]", in="formData", description="活动优惠信息 会员等级对应要送的服务类商品信息名称", required=true, type="string"),
     *     @SWG\Parameter( name="sms_isopen", in="formData", description="是否开启短信提示", required=true, type="string"),
     *     @SWG\Parameter( name="sms_params[app_name]", in="formData", description="短信参数 小程序名称", required=false, type="string"),
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
     *                 ref="#/definitions/ActivityDetail"
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
            throw new StoreResourceFailedException(trans('PromotionsBundle.activity_name_max_20'));
        }

        if (isset($data['is_forever']) && $data['is_forever'] == 'true') {
            $data['begin_time'] = time();
            $data['end_time'] = '5000000000';
            unset($data['is_forever']);
        } else {
            if (!$data['begin_time'] || !$data['end_time']) {
                throw new StoreResourceFailedException(trans('PromotionsBundle.fill_activity_time'));
            }

            if ($data['end_time'] <= time()) {
                throw new StoreResourceFailedException(trans('PromotionsBundle.select_valid_time'));
            }
        }

        if (isset($data['sms_isopen'])) {
            if (!is_string($data['sms_isopen'])) {
                $data['sms_isopen'] = $data['sms_isopen'] ? 'true' : 'false';
            }
        } else {
            $data['sms_isopen'] = 'false';
        }

        $companyId = app('auth')->user()->get('company_id');
        $data['company_id'] = $companyId;

        $promotionActivity = new PromotionActivity();
        $result = $promotionActivity->createActivity($data);

        return $this->response->array(['data' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/activity/lists",
     *     summary="获取活动列表",
     *     tags={"营销"},
     *     description="获取活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_status", in="query", description="活动状态", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="string", example="1", description="总条数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(ref="#/definitions/ActivityDetail")
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $activityStatus = $request->input('activity_status');
        $companyId = app('auth')->user()->get('company_id');

        $promotionActivity = new PromotionActivity();

        if ($activityStatus == 'valid') {
            $filter = [
                'company_id' => $companyId,
                'end_time|gt' => time(),
                'activity_status' => 'valid'
            ];
        } else {
            $filter = [
                'company_id' => $companyId,
                'activity_status' => 'invalid',
            ];
        }

        $orderBy = ["created" => "DESC"];
        $pageSize = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $result = $promotionActivity->lists($filter, $orderBy, $pageSize, $page);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/activearticle",
     *     summary="添加活动文章",
     *     tags={"营销"},
     *     description="添加活动文章",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="article_title", in="query", description="文章标题", required=true, type="string"),
     *     @SWG\Parameter( name="article_subtitle", in="query", description="文章副标题", required=false, type="string"),
     *     @SWG\Parameter( name="article_content", in="query", description="文章内容", required=true, type="string"),
     *     @SWG\Parameter( name="article_cover", in="query", description="文章封面", required=true, type="string"),
     *     @SWG\Parameter( name="directional_url", in="query", description="跳转链接", required=true, type="string"),
     *     @SWG\Parameter( name="is_show", in="query", description="是否展示,1展示 0不展示", required=false, type="string"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *                 description="状态"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function saveActiveArticle(Request $request)
    {
        $requestData = $request->all();
        $companyId = app('auth')->user()->get('company_id');

        $rule = [
            'article_title' => ['required|max:200', trans('PromotionsBundle.article_title_required_max_200')],
            'article_content' => ['required', trans('PromotionsBundle.article_content_required')],
            'article_cover' => ['required', trans('PromotionsBundle.cover_required')],
            'directional_url' => ['required', trans('PromotionsBundle.redirect_url_required')],
        ];
        $error = validator_params($requestData, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        $data = [
            'article_title' => $requestData['article_title'],
            'article_content' => $requestData['article_content'],
            'article_cover' => $requestData['article_cover'],
            'directional_url' => $requestData['directional_url'],
            'article_subtitle' => $requestData['article_subtitle'] ?? '',
            'is_show' => $requestData['is_show'] ?? 1,
            'sort' => $requestData['sort'] ?? 0,
            'company_id' => $companyId
        ];
        $activeArticlesService = new ActiveArticlesService();
        $result = $activeArticlesService->saveActiveArticle($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/activearticle/lists",
     *     summary="获取活动文章列表",
     *     tags={"营销"},
     *     description="获取活动文章列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="6", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description="id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                          @SWG\Property( property="article_title", type="string", example="2020年8月1日-8日大促提醒", description="活动文章标题"),
     *                          @SWG\Property( property="article_subtitle", type="string", example="2020年8月1日-8日领取活动", description="文章副标题"),
     *                          @SWG\Property( property="article_content", type="string", example="内容", description="文章内容"),
     *                          @SWG\Property( property="article_cover", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdYj0vHb58T6H7GkDhEoPMnzWy1w7MCzdBZKtc1ziac7tdUx4saPbIcrhaPoh6ibPt05yolY851Ds4A/0?wx_fmt=jpeg", description="封面"),
     *                          @SWG\Property( property="directional_url", type="string", example="/pages/vip/vipgrades", description="跳转地址,转json"),
     *                          @SWG\Property( property="is_show", type="string", example="1", description="是否展示,1展示 0不展示"),
     *                          @SWG\Property( property="is_delete", type="string", example="0", description="是否已删除,1已删除 0未删除"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                          @SWG\Property( property="created", type="string", example="1590979196", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1590979196", description=""),
     *                       ),
     *                  ),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActiveArticleList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $title = trim($request->input('article_title', ''));
        $subtitle = trim($request->input('article_subtitle', ''));
        $content = trim($request->input('article_content', ''));
        $updateStart = trim($request->input('update_start', ''));
        $updateEnd = trim($request->input('update_end', ''));

        $filter = [
            'company_id' => $companyId,
            'is_delete' => 0
        ];
        if ($title) {
            $filter['article_title|contains'] = $title;
        }
        if ($subtitle) {
            $filter['article_subtitle|contains'] = $subtitle;
        }
        if ($content) {
            $filter['article_content'] = $content;
        }
        if ($updateStart) {
            $filter['updated|gte'] = $updateStart;
        }
        if ($updateEnd) {
            $filter['updated|lte'] = $updateEnd;
        }

        $orderBy = [
            'sort' => 'DESC',
            'id' => 'DESC'
        ];

        $activeArticlesService = new ActiveArticlesService();
        $result = $activeArticlesService->getActiveArticle($filter, $page, $pageSize, $orderBy);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/activearticle/1",
     *     summary="获取活动文章详情",
     *     tags={"营销"},
     *     description="获取活动文章详情",
     *     operationId="getActiveArticleDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="4", description="id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                  @SWG\Property( property="article_title", type="string", example="2020年8月1日-8日大促提醒", description="文章标题 | 活动文章标题"),
     *                  @SWG\Property( property="article_subtitle", type="string", example="2020年8月1日-8日领取活动", description="文章副标题"),
     *                  @SWG\Property( property="article_content", type="string", example="活动规则告知，2020年8月1日-8日领取上官方小程序商城，消费满88元，消费者就可以领取20元代金券。无门槛哦！！！", description="文章内容"),
     *                  @SWG\Property( property="article_cover", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdYj0vHb58T6H7GkDhEoPMnzWy1w7MCzdBZKtc1ziac7tdUx4saPbIcrhaPoh6ibPt05yolY851Ds4A/0?wx_fmt=jpeg", description="封面"),
     *                  @SWG\Property( property="directional_url", type="string", example="/pages/vip/vipgrades", description="跳转地址,转json"),
     *                  @SWG\Property( property="is_show", type="string", example="true", description="是否展示,1展示 0不展示"),
     *                  @SWG\Property( property="is_delete", type="string", example="false", description="是否已删除,1已删除 0未删除"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="1590979196", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1590979196", description=""),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActiveArticleDetail(Request $request, $id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = intval($id);

        if (!$id) {
            return $this->response->array([]);
        }

        $filter = [
            'company_id' => $companyId,
            'id' => $id,
            'is_delete' => 0
        ];
        $activeArticlesService = new ActiveArticlesService();
        $result = $activeArticlesService->getActiveArticleDetail($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/activearticle",
     *     summary="修改活动文章",
     *     tags={"营销"},
     *     description="修改活动文章",
     *     operationId="updateActiveArticle",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="要修改的文章id", required=true, type="string"),
     *     @SWG\Parameter( name="article_title", in="query", description="文章标题", required=true, type="string"),
     *     @SWG\Parameter( name="article_subtitle", in="query", description="文章副标题", required=false, type="string"),
     *     @SWG\Parameter( name="article_content", in="query", description="文章内容", required=true, type="string"),
     *     @SWG\Parameter( name="article_cover", in="query", description="文章封面", required=true, type="string"),
     *     @SWG\Parameter( name="directional_url", in="query", description="跳转链接", required=true, type="string"),
     *     @SWG\Parameter( name="is_show", in="query", description="是否展示,1展示 0不展示", required=false, type="string"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *                 description="状态"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateActiveArticle(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = intval($request->input('id', 0));
        $requestData = $request->all();

        if (!$id) {
            throw new ResourceException(trans('PromotionsBundle.please_select_article'));
        }
        $rule = [
            'article_title' => ['required|max:200', trans('PromotionsBundle.article_title_required_max_200')],
            'article_content' => ['required', trans('PromotionsBundle.article_content_required')],
            'article_cover' => ['required', trans('PromotionsBundle.cover_required')],
            'directional_url' => ['required', trans('PromotionsBundle.redirect_url_required')],
        ];
        $error = validator_params($requestData, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        $data = [
            'article_title' => $requestData['article_title'],
            'article_content' => $requestData['article_content'],
            'article_cover' => $requestData['article_cover'],
            'directional_url' => $requestData['directional_url'],
            'article_subtitle' => $requestData['article_subtitle'] ?? '',
            'is_show' => $requestData['is_show'] ?? 1,
            'sort' => $requestData['sort'] ?? 0,
            'company_id' => $companyId,
            'is_delete' => 0
        ];
        $filter = [
            'company_id' => $companyId,
            'id' => $id
        ];

        $activeArticlesService = new ActiveArticlesService();
        $result = $activeArticlesService->updateActiveArticle($filter, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotions/activearticle",
     *     summary="删除活动文章",
     *     tags={"营销"},
     *     description="删除活动文章",
     *     operationId="updateActiveArticle",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="要删除的文章id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *                 description="状态"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function deleteActiveArticle($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = intval($id);

        if (!$id) {
            throw new ResourceException(trans('PromotionsBundle.please_select_article'));
        }

        $filter = [
            'company_id' => $companyId,
            'id' => $id,
            'is_delete' => 0
        ];

        $activeArticlesService = new ActiveArticlesService();
        $result = $activeArticlesService->deleteActiveArticle($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/liverooms",
     *     summary="获取直播、视频回放列表",
     *     tags={"营销"},
     *     description="获取直播、视频回放列表。接口文档的返回结果为直播列表",
     *     operationId="getLiveRooms",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="模板名称", required=false, type="string"),
     *     @SWG\Parameter( name="wxapp_id", in="query", description="小程序appid", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码 默认为1", required=false, type="string"),
     *     @SWG\Parameter( name="roomid", in="query", description="直播间id,查看回放列表时必填", required=false, type="string"),
     *     @SWG\Parameter( name="action", in="query", description="查看回放列表时，传get_replay", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="name", type="string", example="直播房间名", description="直播间名称"),
     *                          @SWG\Property( property="cover_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/donnRWvvtUsNpYibMd9Cib92zWHXYzuKmZbHTsiczoHE9l3ADicvD1kOOboicWGUsicpLx2bdL8diaKbq3TJ0qibNWwsKw/0", description="直播间图片"),
     *                          @SWG\Property( property="start_time", type="string", example="1604473876", description="直播间开始时间，列表按照start_time降序排列"),
     *                          @SWG\Property( property="end_time", type="string", example="1604474821", description="直播计划结束时间"),
     *                          @SWG\Property( property="anchor_name", type="string", example="里斯", description="主播名"),
     *                          @SWG\Property( property="roomid", type="string", example="1", description="直播间ID"),
     *                          @SWG\Property( property="goods", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="cover_img", type="string", example="http://http:\/\/mmbiz.qpic.cn\/mmbiz_jpg\/Rl1RuuhdstSfZa8EEljedAYcbtX3Ejpdl2et1tPAQ37bdicnxoVialDLCKKDcPBy8Iic0kCiaiaalXg3EbpNKoicrweQ\/0?wx_fmt=jpeg", description="商品封面图链接"),
     *                                  @SWG\Property( property="url", type="string", example="pages/index/index.html", description="商品小程序路径"),
     *                                  @SWG\Property( property="price", type="string", example="1889", description="商品价格（分）"),
     *                                  @SWG\Property( property="name", type="string", example="茶杯", description="商品名称"),
     *                                  @SWG\Property( property="price2", type="string", example="0", description="商品价格，使用方式看price_type"),
     *                                  @SWG\Property( property="price_type", type="string", example="1", description="价格类型，1：一口价（只需要传入price，price2不传） 2：价格区间（price字段为左边界，price2字段为右边界，price和price2必传） 3：显示折扣价（price字段为原价，price2字段为现价， price和price2必传）"),
     *                                  @SWG\Property( property="goods_id", type="string", example="256", description="商品id"),
     *                                  @SWG\Property( property="third_party_appid", type="string", example="wx3d0fae56402d8a81", description="第三方商品appid ,当前小程序商品则为空"),
     *                              ),
     *                          ),
     *                          @SWG\Property( property="live_status", type="string", example="103", description="直播间状态。101：直播中，102：未开始，103已结束，104禁播，105：暂停，106：异常，107：已过期"),
     *                          @SWG\Property( property="share_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/donnRWvvtUsNpYibMd9Cib92zWHXYzuKmZXRxRJnKv95Iot35qfaHecIXUZwicdua6MBPZ9ZFfATMkV87sxbkd5XQ/0", description="直播间分享图链接"),
     *                          @SWG\Property( property="live_type", type="string", example="0", description="直播类型，1 推流 0 手机直播"),
     *                          @SWG\Property( property="close_like", type="string", example="0", description="是否关闭点赞 【0：开启，1：关闭】（若关闭，观众端将隐藏点赞按钮，直播开始后不允许开启）"),
     *                          @SWG\Property( property="close_goods", type="string", example="0", description="是否关闭货架 【0：开启，1：关闭】（若关闭，观众端将隐藏商品货架，直播开始后不允许开启）"),
     *                          @SWG\Property( property="close_comment", type="string", example="0", description="是否关闭评论 【0：开启，1：关闭】（若关闭，观众端将隐藏评论入口，直播开始后不允许开启）"),
     *                          @SWG\Property( property="close_kf", type="string", example="1", description="是否关闭客服 【0：开启，1：关闭】 默认关闭客服（直播开始后允许开启）"),
     *                          @SWG\Property( property="close_replay", type="string", example="1", description="是否关闭回放 【0：开启，1：关闭】默认关闭回放（直播开始后允许开启）"),
     *                          @SWG\Property( property="is_feeds_public", type="string", example="1", description="是否开启官方收录，1 开启，0 关闭"),
     *                          @SWG\Property( property="creater_openid", type="string", example="oO54hQFeIbZV1f9wyY6T0cob05Uc", description="创建者openid"),
     *                          @SWG\Property( property="feeds_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/donnRWvvtUsNpYibMd9Cib92zWHXYzuKmZqTicia13icyfJXORib0CBKV5vibuaCvtyXN09xibR2ElwBaQbQJL9qtJCLDQ/0", description="官方收录封面"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="1", description="拉取房间总数"),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getLiveRooms(Request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'template_name' => 'required_without:wxapp_id',
            'wxapp_id' => 'required_without:template_name',
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:10',
            // 'roomid'=>'sometimes|required|min:0',
            // 'action'=>'sometimes|required|in:get_replay',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.get_list_error'), $validator->errors());
        }

        $authorizerAppId = $request->input('wxapp_id');
        if (empty($authorizerAppId)) {
            //根据模板名称获取授权绑定的小城appid
            $companyId = app('auth')->user()->get('company_id');
            $templateName = $request->input('template_name');
            $authorizerAppId = $this->getAuthorizerAppId($templateName, $companyId);
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);
        $roomid = $request->input('roomid', '');
        $action = $request->input('action', '');
        $promotionActivity = new PromotionActivity();
        $list = $promotionActivity->getliveRoomsList($authorizerAppId, $page, $pageSize, $roomid, $action);

        return $this->response->array($list);
    }
}
