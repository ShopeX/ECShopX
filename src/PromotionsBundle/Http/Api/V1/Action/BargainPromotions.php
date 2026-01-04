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

use PromotionsBundle\Services\BargainPromotionsService;

use Dingo\Api\Exception\ResourceException;

class BargainPromotions extends Controller
{
    /**
     * @SWG\Definition(
     * definition="BargainDetail",
     * type="object",
     * @SWG\Property( property="bargain_id", type="string", example="44", description="砍价活动ID"),
     * @SWG\Property( property="title", type="string", example="测试", description="活动名称"),
     * @SWG\Property( property="ad_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkrc2Zn94c3cQqg8Wm872g5MVdWQfWaHDOg1dicTETWsd4rhNl8UToSsESlVicicKgE1jBr7PicWfEhIA6g/0?wx_fmt=png", description="广告图"),
     * @SWG\Property( property="item_name", type="string", example="商品名称", description="商品名称"),
     * @SWG\Property( property="item_pics", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/Hw4SsicubkrdgG6icibvyUTIsSsRw7k1QPx5PHqljSnfCPY3MGV4Q7YyTHdKwvMmDibV7dy33vRuKNAm8uxehysSibg/0?wx_fmt=gif", description="商品图片"),
     * @SWG\Property( property="item_intro", type="string", example="商品详情描述", description="商品详情"),
     * @SWG\Property( property="mkt_price", type="string", example="9900", description="市场价格,单位为‘分’"),
     * @SWG\Property( property="price", type="string", example="1", description="购买价格,单位为‘分’"),
     * @SWG\Property( property="limit_num", type="string", example="100", description="购买限制"),
     * @SWG\Property( property="order_num", type="string", example="0", description="已购买数量"),
     * @SWG\Property( property="bargain_rules", type="string", example="规则描述", description="规则描述"),
     * @SWG\Property( property="bargain_range", type="object",
     *     @SWG\Property( property="min", type="string", example="0", description="助力金额最小"),
     *     @SWG\Property( property="max", type="string", example="0", description="助力金额最大"),
     * ),
     * @SWG\Property( property="people_range", type="object",
     *     @SWG\Property( property="min", type="string", example="1", description="助力人数最小"),
     *     @SWG\Property( property="max", type="string", example="2", description="助力人数最大"),
     * ),
     * @SWG\Property( property="min_price", type="string", example="100", description="每个人最少能砍的价钱,单位为‘分’"),
     * @SWG\Property( property="begin_time", type="string", example="1611590400", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611936000", description="活动结束时间"),
     * @SWG\Property( property="share_msg", type="string", example="test", description="分享内容"),
     * @SWG\Property( property="help_pics", type="array",
     *     @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkreVQUTIIoNmTzT9VKbIyoicAkmzZCuK66He57ZyvjIoBquiarx6GnSDXDXgib3uynRAdrfGcFU0WRLDg/0?wx_fmt=jpeg", description="翻牌图片"),
     * ),
     * @SWG\Property( property="created", type="string", example="1611628248", description=""),
     * @SWG\Property( property="updated", type="string", example="1611628248", description=""),
     * @SWG\Property( property="is_expired", type="string", example="true", description="是否过期"),
     * @SWG\Property( property="item_id", type="string", example="5427", description="商品id"),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/promotions/bargain",
     *     summary="创建助力活动",
     *     tags={"营销"},
     *     description="创建助力活动",
     *     operationId="createBargain",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="ad_pic", in="formData", description="广告图片URL", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="item_name", in="formData", description="商品名称", required=true, type="string"),
     *     @SWG\Parameter( name="item_pics", in="formData", description="商品图片", type="string"),
     *     @SWG\Parameter( name="item_intro", in="formData", description="商品详情", type="string"),
     *     @SWG\Parameter( name="mkt_price", in="formData", description="市场价格", type="integer"),
     *     @SWG\Parameter( name="price", in="formData", description="购买价格", type="integer"),
     *     @SWG\Parameter( name="limit_num", in="formData", description="购买限制", type="integer"),
     *     @SWG\Parameter( name="bargain_rules", in="formData", description="助力规则", type="string"),
     *     @SWG\Parameter( name="bargain_range[min]", in="formData", description="助力金额最小", type="string"),
     *     @SWG\Parameter( name="bargain_range[max]", in="formData", description="助力金额最大", type="string"),
     *     @SWG\Parameter( name="people_range[min]", in="formData", description="助力人数最小", type="string"),
     *     @SWG\Parameter( name="people_range[max]", in="formData", description="助力人数最大", type="string"),
     *     @SWG\Parameter( name="begin_time", in="formData", description="开始时间", type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", type="string"),
     *     @SWG\Parameter( name="share_msg", in="formData", description="分享内容", type="string"),
     *     @SWG\Parameter( name="help_pics[0]", in="formData", description="翻牌图片", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/BargainDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createBargain(Request $request)
    {
        // Powered by ShopEx EcShopX
        $params = $request->input();
        $validator = app('validator')->make(
            $params,
            [
                'title' => 'required',
                'ad_pic' => 'required',
                'item_name' => 'required',
                'item_pics' => 'required',
//                'item_intro'        => 'required',
                'price' => 'required|numeric|min:0',
                'mkt_price' => 'required|numeric|min:0',
                'limit_num' => 'required|numeric|min:0',
                'bargain_rules' => 'required',
                // 'bargain_range.min' => 'required|numeric',
                // 'bargain_range.max' => 'required|numeric|min:0',
                'people_range.min' => 'required|numeric|min:1',
                'people_range.max' => 'required|numeric|min:2',
                // 'min_price'         => 'required|numeric',
                'begin_time' => 'required',
                'end_time' => 'required',
                'share_msg' => 'required',
                'help_pics' => 'required',
            ],
            [
                'title.*' => trans('PromotionsBundle.activity_title_required'),
                'ad_pic.*' => trans('PromotionsBundle.activity_pic_required'),
                'item_name.*' => trans('PromotionsBundle.item_name_required'),
                'item_pics.*' => trans('PromotionsBundle.item_pics_required'),
//                'item_intro'        => '商品详情必填',
                'price.*' => trans('PromotionsBundle.item_price_required_min_0'),
                'mkt_price.*' => trans('PromotionsBundle.item_discount_required_min_0'),
                'limit_num.*' => trans('PromotionsBundle.limit_purchase_quantity_required'),
                'bargain_rules.*' => trans('PromotionsBundle.bargain_rules_required'),
                // 'bargain_range.min' => '最小助力数额必填',
                // 'bargain_range.max' => '最大助力数额必须大于0且必填',
                'people_range.min.*' => trans('PromotionsBundle.min_help_people_required_min_1'),
                'people_range.max.*' => trans('PromotionsBundle.max_help_people_required_min_2'),
                // 'min_price'         => '最少助力金额必填',
                'begin_time.*' => trans('PromotionsBundle.bargain_start_time_required'),
                'end_time.*' => trans('PromotionsBundle.bargain_end_time_required'),
                'share_msg.*' => trans('PromotionsBundle.share_content_required'),
                'help_pics.*' => trans('PromotionsBundle.flip_card_pics_required'),
            ]
        );
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                throw new ResourceException($msg);
            }  
        }
        // if ($params['bargain_range']['min'] >= $params['bargain_range']['max']) {
        //     throw new ResourceException('最大助力金额要大于最低助力金额');
        // }
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['begin_time'] = strtotime($params['begin_time']);
        $params['end_time'] = strtotime($params['end_time']);
        if ($params['begin_time'] >= $params['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.end_time_must_greater_than_start'));
        }
        $params['price'] = intval($params['price'] * 100);
        $params['mkt_price'] = intval($params['mkt_price'] * 100);
        $params['min_price'] = 100; //intval($params['min_price']*100);
        // $params['bargain_range']['min'] = intval($params['bargain_range']['min']*100);
        // $params['bargain_range']['max'] = intval($params['bargain_range']['max']*100);

        $maxCutPrice = $params['mkt_price'] - $params['price'];
        if ($maxCutPrice < 100) {
            throw new ResourceException(trans('PromotionsBundle.price_difference_must_greater_than_1'));
        }
        // if ($params['bargain_range']['max'] > $maxCutPrice) {
        //     throw new ResourceException('最大助力金额为￥' . $maxCutPrice / 100);
        // }
        if ($params['people_range']['max'] <= $params['people_range']['min']) {
            throw new ResourceException(trans('PromotionsBundle.max_help_people_greater_than_min'));
        }
        $bargainService = new BargainPromotionsService();

        $result = $bargainService->createBargain($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotion/bargains",
     *     summary="获取助力活动列表",
     *     tags={"营销"},
     *     description="获取助力活动列表",
     *     operationId="getBargainList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页数，默认1",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，默认50",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="42", description="总条数"),
     *                 @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                      ref="#/definitions/BargainDetail"
     *                      ),
     *                 ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getBargainList(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $params = $request->input();
        if (isset($params['item_name']) && $params['item_name']) {
            $filter['item_name|contains'] = $params['item_name'];
        }
        if (isset($params['title']) && $params['item_name']) {
            $filter['title|contains'] = $params['title'];
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 50);
        $offset = ($page - 1) * $limit;

        $bargainService = new BargainPromotionsService();
        $result = $bargainService->getBargainList($filter, $offset, $limit);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/bargains/{bargain_id}",
     *     summary="获取助力活动详情",
     *     tags={"营销"},
     *     description="获取助力活动详情",
     *     operationId="getBargainDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="bargain_id", in="path", description="活动ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/BargainDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getBargainDetail($bargain_id)
    {
        $validator = app('validator')->make(['bargain_id' => $bargain_id], [
            'bargain_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.get_bargain_detail_error'), $validator->errors());
        }

        $bargainService = new BargainPromotionsService();
        $result = $bargainService->getBargain($bargain_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException(trans('PromotionsBundle.can_only_get_your_bargain_detail'));
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/bargain",
     *     summary="更新助力活动",
     *     tags={"营销"},
     *     description="更新助力活动",
     *     operationId="updateBargain",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="bargain_id", in="formData", description="活动ID", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="ad_pic", in="formData", description="广告图片URL", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="item_name", in="formData", description="商品名称", required=true, type="string"),
     *     @SWG\Parameter( name="item_pics", in="formData", description="商品图片", type="string"),
     *     @SWG\Parameter( name="item_intro", in="formData", description="商品详情", type="string"),
     *     @SWG\Parameter( name="mkt_price", in="formData", description="市场价格", type="integer"),
     *     @SWG\Parameter( name="price", in="formData", description="购买价格", type="integer"),
     *     @SWG\Parameter( name="limit_num", in="formData", description="购买限制", type="integer"),
     *     @SWG\Parameter( name="bargain_rules", in="formData", description="助力规则", type="string"),
     *     @SWG\Parameter( name="bargain_range[min]", in="formData", description="助力金额最小", type="string"),
     *     @SWG\Parameter( name="bargain_range[max]", in="formData", description="助力金额最大", type="string"),
     *     @SWG\Parameter( name="people_range[min]", in="formData", description="助力人数最小", type="string"),
     *     @SWG\Parameter( name="people_range[max]", in="formData", description="助力人数最大", type="string"),
     *     @SWG\Parameter( name="begin_time", in="formData", description="开始时间", type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", type="string"),
     *     @SWG\Parameter( name="share_msg", in="formData", description="分享内容", type="string"),
     *     @SWG\Parameter( name="help_pics[0]", in="formData", description="翻牌图片", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/BargainDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateBargain($bargain_id, Request $request)
    {
        $params = $request->input();
        $params['bargain_id'] = $bargain_id;

        $validator = app('validator')->make(
            $params,
            [
                'bargain_id' => 'required|numeric|min:0',
                'title' => 'required',
                'ad_pic' => 'required',
                'item_name' => 'required',
                'item_pics' => 'required',
                // 'item_intro'        => 'required',
                'price' => 'required|numeric|min:0',
                'mkt_price' => 'required|numeric|min:0',
                'limit_num' => 'required|numeric|min:0',
                'bargain_rules' => 'required',
                // 'bargain_range.min' => 'required|numeric',
                // 'bargain_range.max' => 'required|numeric|min:0',
                'people_range.min' => 'required|numeric|min:1',
                'people_range.max' => 'required|numeric|min:1',
                // 'min_price'         => 'required|numeric',
                'begin_time' => 'required',
                'end_time' => 'required',
                'share_msg' => 'required',
                'help_pics' => 'required',
            ],
            [
                'bargain_id' => trans('PromotionsBundle.missing_activity_id'),
                'title' => trans('PromotionsBundle.activity_title_required'),
                'ad_pic' => trans('PromotionsBundle.activity_pic_required'),
                'item_name' => trans('PromotionsBundle.item_name_required'),
                'item_pics' => trans('PromotionsBundle.item_pics_required'),
                // 'item_intro'        => '商品详情必填',
                'price.*' => trans('PromotionsBundle.item_price_required_min_0'),
                'mkt_price.*' => trans('PromotionsBundle.item_discount_required_min_0'),
                'limit_num.*' => trans('PromotionsBundle.limit_purchase_quantity_required'),
                'bargain_rules' => trans('PromotionsBundle.bargain_rules_required'),
                // 'bargain_range.min' => '最小助力数额必填',
                // 'bargain_range.max' => '最大助力数额必须大于0且必填',
                'people_range.min' => trans('PromotionsBundle.min_help_people_required'),
                'people_range.max' => trans('PromotionsBundle.max_help_people_required_min_0'),
                // 'min_price'         => '最少助力金额必填',
                'begin_time' => trans('PromotionsBundle.bargain_start_time_required'),
                'end_time' => trans('PromotionsBundle.bargain_end_time_required'),
                'share_msg' => trans('PromotionsBundle.share_content_required'),
                'help_pics' => trans('PromotionsBundle.flip_card_pics_required'),
            ]
        );
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                throw new ResourceException($msg);
            }
        }
        // if ($params['bargain_range']['min'] >= $params['bargain_range']['max']) {
        //     throw new ResourceException('最大助力金额要大于最低助力金额');
        // }
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['begin_time'] = strtotime($params['begin_time']);
        $params['end_time'] = strtotime($params['end_time']);
        if ($params['begin_time'] >= $params['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.end_time_must_greater_than_start'));
        }

        $params['price'] = intval($params['price'] * 100);
        $params['mkt_price'] = intval($params['mkt_price'] * 100);
        $params['min_price'] = 100;//intval($params['min_price']*100);
        // $params['bargain_range']['min'] = intval($params['bargain_range']['min']*100);
        // $params['bargain_range']['max'] = intval($params['bargain_range']['max']*100);

        $maxCutPrice = $params['mkt_price'] - $params['price'];
        if ($maxCutPrice < 100) {
            throw new ResourceException(trans('PromotionsBundle.price_difference_must_greater_than_1'));
        }
        // if ($params['bargain_range']['max'] > $maxCutPrice) {
        //     throw new ResourceException('最大助力金额为￥' . $maxCutPrice / 100);
        // }
        if ($params['people_range']['max'] <= $params['people_range']['min']) {
            throw new ResourceException(trans('PromotionsBundle.max_help_people_greater_than_min'));
        }
        $bargainService = new BargainPromotionsService();

        $result = $bargainService->updateBargain($bargain_id, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/bargains/termination/{bargain_id}",
     *     summary="终止助力活动",
     *     tags={"营销"},
     *     description="终止助力活动",
     *     operationId="terminateBargain",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="bargain_id",
     *         in="path",
     *         description="助力活动id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/BargainDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function terminateBargain($bargain_id)
    {
        // 0x53686f704578
        $params['bargain_id'] = $bargain_id;
        $validator = app('validator')->make($params, [
            'bargain_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.terminate_bargain_error'), $validator->errors());
        }
        $bargainService = new BargainPromotionsService();
        $result = $bargainService->terminateBargain($bargain_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotions/bargains/{bargain_id}",
     *     summary="删除助力活动",
     *     tags={"营销"},
     *     description="删除助力活动",
     *     operationId="deleteBargain",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="bargain_id", in="path", description="活动ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function deleteBargain($bargain_id)
    {
        $params['bargain_id'] = $bargain_id;
        $validator = app('validator')->make($params, [
            'bargain_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.delete_bargain_error'), $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $bargainService = new BargainPromotionsService();
        $params = [
            'bargain_id' => $bargain_id,
            'company_id' => $company_id,
        ];
        $result = $bargainService->deleteBargain($params);

        return $this->response->noContent();
    }
}
