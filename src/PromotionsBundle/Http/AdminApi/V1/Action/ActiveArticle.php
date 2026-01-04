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

namespace PromotionsBundle\Http\AdminApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PromotionsBundle\Services\ActiveArticlesService;
use WechatBundle\Services\WeappService;

class ActiveArticle extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotions/activearticle/lists",
     *     summary="获取活动文章列表",
     *     tags={"营销"},
     *     description="获取活动文章列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=false, type="string", default="1" ),
     *     @SWG\Parameter( name="page_size", in="query", description="每页条数", required=false, type="string", default="10" ),
     *     @SWG\Parameter( name="article_title", in="query", description="文章标题", required=false, type="string" ),
     *     @SWG\Parameter( name="article_subtitle", in="query", description="文章副标题", required=false, type="string" ),
     *     @SWG\Parameter( name="article_content", in="query", description="文章内容", required=false, type="string" ),
     *     @SWG\Parameter( name="update_start", in="query", description="更新时间的开始时间", required=false, type="string" ),
     *     @SWG\Parameter( name="update_end", in="query", description="更新时间的结束时间", required=false, type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="5", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description="id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="article_title", type="string", example="2020年8月1日-8日大促提醒", description="活动文章标题"),
     *                          @SWG\Property( property="article_subtitle", type="string", example="2020年8月1日-8日领取活动", description="文章副标题"),
     *                          @SWG\Property( property="article_content", type="string", example="活动规则告知，2020年8月1日-8日领取上官方小程序商城，消费满88元，消费者就可以领取20元代金券。无门槛哦！！！", description="文章内容"),
     *                          @SWG\Property( property="article_cover", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdYj0vHb58T6H7GkDhEoPMnzWy1w7MCzdBZKtc1ziac7tdUx4saPbIcrhaPoh6ibPt05yolY851Ds4A/0?wx_fmt=jpeg", description="封面"),
     *                          @SWG\Property( property="directional_url", type="string", example="/pages/item/espier-detail?id=5470", description="跳转地址,转json"),
     *                          @SWG\Property( property="is_show", type="string", example="1", description="是否展示,1展示 0不展示"),
     *                          @SWG\Property( property="is_delete", type="string", example="0", description="是否已删除,1已删除 0未删除"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                          @SWG\Property( property="created", type="string", example="1590979196", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1590979196", description=""),
     *                       ),
     *                  ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActiveArticleList(Request $request)
    {
        $authInfo = $this->auth->user();
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $title = trim($request->input('article_title', ''));
        $subtitle = trim($request->input('article_subtitle', ''));
        $content = trim($request->input('article_content', ''));
        $updateStart = trim($request->input('update_start', ''));
        $updateEnd = trim($request->input('update_end', ''));

        $filter = [
            'company_id' => $authInfo['company_id'],
            'is_show' => 1,
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
     *     path="/wxapp/promotions/activearticle/{id}",
     *     summary="获取活动文章详情",
     *     tags={"营销"},
     *     description="获取活动文章详情",
     *     operationId="getActiveArticleDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="id", in="path", description="活动文章id", required=true, type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="id", type="string", example="7", description="id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                  @SWG\Property( property="article_title", type="string", example="西装买一送一", description="活动文章标题"),
     *                  @SWG\Property( property="article_subtitle", type="string", example="测试", description="文章副标题"),
     *                  @SWG\Property( property="article_content", type="string", example="测试", description="文章内容"),
     *                  @SWG\Property( property="article_cover", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkre4SsqeJKcShn3CyCQc3L52zM5jHpUo4hkicCiby1qmz5g5XpAIPg5JMFxgNcHUoCtg9vLT7QbzibP2w/0?wx_fmt=png", description="封面"),
     *                  @SWG\Property( property="directional_url", type="string", example="/pages/item/espier-detail?id=5042", description="跳转地址,转json"),
     *                  @SWG\Property( property="is_show", type="string", example="true", description="是否展示,1展示 0不展示"),
     *                  @SWG\Property( property="is_delete", type="string", example="false", description="是否已删除,1已删除 0未删除"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="1599646088", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1599646088", description=""),
     *                  @SWG\Property( property="wxappAppid", type="string", example="wx912913df9fef6ddd", description="appid"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActiveArticleDetail($id, Request $request)
    {
        $authInfo = $this->auth->user();
        $id = intval($id);

        if (!$id) {
            return $this->response->array([]);
        }

        $filter = [
            'company_id' => $authInfo['company_id'],
            'id' => $id,
            'is_show' => 1,
            'is_delete' => 0
        ];
        $activeArticlesService = new ActiveArticlesService();
        $result = $activeArticlesService->getActiveArticleDetail($filter);
        $weappService = new WeappService();
        $wxappAppid = $weappService->getWxappidByTemplateName($authInfo['company_id'], 'yykweishop');
        $result['wxappAppid'] = $wxappAppid;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotions/activearticleforward",
     *     summary="导购员转发记录",
     *     tags={"营销"},
     *     description="导购员转发记录",
     *     operationId="forwardActiveArticle",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="article_id", in="query", description="活动文章id", required=true, type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function forwardActiveArticle(Request $request)
    {
        $authInfo = $request->get('auth');
        $activeArticleId = intval($request->input('article_id', 0));

        if (!$activeArticleId) {
            throw new ResourceException('活动必选');
        }

        //导购员转发记录
        $activeArticleService = new ActiveArticlesService();
        $activeArticleService->addForwardLog($authInfo['company_id'], $authInfo['salesperson_id'], $activeArticleId);
        $result = ['status' => true];
        return $this->response->array($result);
    }
}
