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

namespace PointsmallBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsCategoryService;
use PointsmallBundle\Services\ItemsService;
use CompanysBundle\Traits\GetDefaultCur;
use MembersBundle\Services\MemberItemsFavService;
use OrdersBundle\Services\ShippingTemplatesService;
use TdksetBundle\Services\TdkGivenService;

class Items extends BaseController
{
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="wxapp/pointsmall/goods/items/{item_id}",
     *     summary="获取商品详情",
     *     tags={"积分商城"},
     *     description="获取商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="path",
     *         description="商品id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                property="data",
     *                type="object",
     *                ref="#/definitions/GoodsDetail",
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsDetail($item_id, Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $api_from = $authInfo['api_from'];
        $woa_appid = $authInfo['woa_appid'];

        $itemsService = new ItemsService();
        // 如果传入goods_id那么则通过，goods_id获取到item_id
        // 防止链接中的item_id已经失效
        $goodsId = $request->input('goods_id');
        if ($goodsId) {
            $tempItemInfo = $itemsService->getInfo(['goods_id' => $goodsId, 'audit_status' => 'approved', 'is_default' => true, 'company_id' => $company_id]);
            if ($tempItemInfo) {
                $item_id = $tempItemInfo['item_id'];
            } else {
                throw new ResourceException('商品不存在或者已下架');
            }
        } else {
            $tempItemInfo = $itemsService->getInfo(['item_id' => $item_id, 'audit_status' => 'approved', 'company_id' => $company_id]);
            if (!$tempItemInfo) {
                throw new ResourceException('商品不存在或者已下架');
            }
        }

        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->response->array(['item_id' => 0]);
        }

        // 当前商品在进行活动
        $limitItemIds = array();
        $result = $itemsService->getItemsDetail($item_id, $woa_appid, $limitItemIds, $company_id);

        if (!$result) {
            return $this->response->array(['item_id' => 0]);
        }

        //普通商品，不是活动商品
        $result['activity_type'] = 'normal';

        //获取系统货币默认配置
        $result['cur'] = $this->getCur($company_id);

        $shippingTemplatesService = new ShippingTemplatesService();
        $express = $shippingTemplatesService->getInfo($result['templates_id'], $company_id);
        $result['no_post'] = [];
        if ($express['nopost_conf'] ?? []) {
            $result['no_post'] = json_decode($express['nopost_conf'], true);
        }

        $result['store'] = $result['item_total_store'] ?? $result['store'];
        $result['rate_status'] = $this->getGoodsRateSettingStatus($result['company_id']);

        // tdk 信息
        if ($request->input('is_tdk') == 1) {
            $TdkGiven = new TdkGivenService();
            $Tdk_info = $TdkGiven->getInfo('details', $company_id);
            $Tdk_data = $TdkGiven->getData($Tdk_info, $result);

            // 判断商品信息是否带有tdk信息
            if (!empty($result['tdk_content'])) {
                $tdk_content = json_decode($result['tdk_content'], true);
                if (!empty($tdk_content['title'])) {
                    $Tdk_data['title'] = $tdk_content['title'];
                }
                if (!empty($tdk_content['mate_description'])) {
                    $Tdk_data['mate_description'] = $tdk_content['mate_description'];
                }
                if (!empty($tdk_content['mate_keywords'])) {
                    $Tdk_data['mate_keywords'] = $tdk_content['mate_keywords'];
                }
            }
            $result['tdk_data'] = $Tdk_data;
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="wxapp/pointsmall/goods/items",
     *     summary="获取商品列表",
     *     tags={"积分商城"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string"),
     *     @SWG\Parameter(name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer", required=true),
     *     @SWG\Parameter(name="pageSize", in="query", description="每页数量,最大不能超过50", type="integer", required=true),
     *     @SWG\Parameter(name="category", in="query", description="商品分类id", type="integer"),
     *     @SWG\Parameter(name="approve_status", in="query", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售，only_show:前台仅展示;多个状态逗号分隔", type="string"),
     *     @SWG\Parameter(name="keywords", in="query", description="关键词搜索", type="string"),
     *     @SWG\Parameter(name="main_category", in="query", description="商品主类目id", type="integer"),
     *     @SWG\Parameter(name="start_price", in="query", description="积分价格范围筛选-开始", type="string"),
     *     @SWG\Parameter(name="end_price", in="query", description="积分价格范围筛选-结束", type="string"),
     *     @SWG\Parameter(name="brand_id", in="query", description="品牌id", type="integer"),
     *     @SWG\Parameter(name="item_type", in="query", description="商品类型，services：服务商品，normal: 普通商品", type="string"),
     *     @SWG\Parameter(name="goodsSort", in="query", description="排序 1:销量倒序 2:积分价格倒序 3:积分价格正序 4:上新", type="integer"),
     *     @SWG\Parameter(name="is_default", in="query", description="是否默认商品 默认:true 如果传入false则查询所有sku数据 如果是true查询商品数据", type="string"),
     *     @SWG\Parameter( name="is_tdk", in="query", description="是否获取tdk信息，0不获取,1获取", type="number" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="string"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         ref="#/definitions/GoodsList"
     *                         )
     *                 ),
     *                 @SWG\Property(
     *                    property="brand_list",
     *                    type="object",
     *                    @SWG\Property(property="total_count", type="string"),
     *                    @SWG\Property(
     *                        property="list",
     *                        type="array",
     *                        @SWG\Items(
     *                            @SWG\Property(property="attribute_id", type="string", description="Id", example="1"),
     *                            @SWG\Property(property="attribute_name", type="string", description="名称", example="test2"),
     *                            @SWG\Property(property="attribute_sort", type="integer", description="排序", example="1"),
     *                            @SWG\Property(property="is_show", type="string", description="是否用于筛选", example="true"),
     *                            @SWG\Property(property="is_image", type="string", description="否需要配置图片", example="true"),
     *                            @SWG\Property(property="image_url", type="string", description="图片地址", example="http://b-img-cdn.yuanyuanke.cn/image/1/2020/10/27/29c57ca983c4c37b59b5a2ba68d6cecaJwqf2uUBnaQaHCBzv3fjf88GwRPRlBYP"),
     *                        )
     *                 )
     *
     *                 ),
     *                 @SWG\Property(
     *                     property="cur",
     *                     type="object",
     *                     ref="#/definitions/Cur"
     *                 ),
     *                  @SWG\Property( property="tdk_data", type="object",
     *                      @SWG\Property( property="title", type="string", example="123ttt,测试w,测试w,测试商城", description="标题"),
     *                      @SWG\Property( property="mate_description", type="string", example="测试w,123ttt,测试w,测试商城", description="描述"),
     *                      @SWG\Property( property="mate_keywords", type="string", example="123ttt,测试w,测试w,测试商城", description="关键字"),
     *                  ),
     *
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsList(request $request)
    {
        $authInfo = $request->get('auth');

        //验证参数todo
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }

        $params['company_id'] = $authInfo['company_id'];

        $approveStatusArr = [];
        if (isset($inputData['approve_status'])) {
            $inputData['approve_status'] = explode(',', $inputData['approve_status']);
            foreach ($inputData['approve_status'] as $approveStatus) {
                if (in_array($approveStatus, ['onsale', 'only_show'])) {
                    $approveStatusArr[] = $approveStatus;
                }
            }
        }

        if ($approveStatusArr) {
            $params['approve_status'] = $inputData['approve_status'];
        } else {
            $params['approve_status'] = ['onsale', 'only_show'];
        }

        $params['audit_status'] = 'approved';

        if (isset($inputData['category_id']) && $inputData['category_id']) {
            $params['category_id'] = $inputData['category_id'];
        }

        if ($request->input('keywords')) {
            $params['item_name'] = trim($request->input('keywords'));
        }

        if ($request->input('main_category')) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($request->input('main_category'), $params['company_id']);
            $itemCategory[] = intval($request->input('main_category'));
            $params['item_category'] = $itemCategory;
        }

        // if ($request->input('item_params')) {
        //     $params['item_params'] = $request->input('item_params');
        // }

        if ($request->input('start_price', 0)) {
            $params['point|gte'] = $request->input('start_price');
        }

        if ($request->input('end_price', 0)) {
            $params['point|lte'] = $request->input('end_price');
        }

        if ($request->input('brand_id', 0)) {
            $params['brand_id'] = $request->input('brand_id');
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if ($request->input('goodsSort') == 1) {
            $orderBy['sales'] = 'desc';
        } elseif ($request->input('goodsSort') == 2) {
            $orderBy['point'] = 'desc';
        } elseif ($request->input('goodsSort') == 3) {
            $orderBy['point'] = 'asc';
        } elseif ($request->input('goodsSort') == 4) {
            $orderBy['created'] = 'desc';
        } else {
            $orderBy['sort'] = 'desc';
        }
        $orderBy['item_id'] = 'desc';

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];

        if ($request->input('is_default', true) !== 'false' || !$request->input('is_default', true)) {
            $params['is_default'] = true;
        }

        $itemsService = new ItemsService();
        if (!isset($params['is_default'])) {
            $pageSize = -1;
            $result = $itemsService->getSkuItemsList($params, $page, $pageSize);
        } else {
            $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);
        }

        $result['cur'] = $this->getCur($params['company_id']);


        // tdk 信息
        if ($request->input('is_tdk') == 1) {
            $input['keywords'] = $request->input('keywords');
            $input['company_id'] = $params['company_id'];
            $input['category_id'] = $request->input('category_id');

            $TdkGiven = new TdkGivenService();
            $Tdk_list_info = $TdkGiven->getInfo('list', $params['company_id']);
            $Tdk_data = $TdkGiven->getData($Tdk_list_info, $input);
            $result['tdk_data'] = $Tdk_data;
        }

        return $this->response->array($result);
    }

    // 商品详情，直接返回页面(暂时弃用)
    public function getItemsIntro($item_id, request $request)
    {
        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取商品图文详情出错.', $validator->errors());
        }
        $authInfo = $request->get('auth');
        $woa_appid = $authInfo['woa_appid'];
        $itemsService = new ItemsService();
        $result = $itemsService->getItemsDetail($item_id, $woa_appid);
        return response($result['intro'])->header('content-type', 'text/html');
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/pointsmall/goods/items/{item_id}/fav",
     *     summary="获取商品收藏情况",
     *     tags={"积分商城"},
     *     description="获取商品收藏情况",
     *     operationId="getItemsFav",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="path",
     *         description="商品id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="fav", type="boolean", example=1),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsFav($item_id, request $request)
    {
        $authInfo = $request->get('auth');
        $memberItemsFavService = new MemberItemsFavService();
        $filter = [
            'user_id' => $authInfo['user_id'],
            'item_id' => $item_id,
        ];
        $favInfo = $memberItemsFavService->getInfo($filter);
        $result['fav'] = 0;
        if ($favInfo) {
            $result['fav'] = 1;
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="wxapp/pointsmall/lovely/goods/items",
     *     summary="获取猜你喜欢商品列表",
     *     tags={"积分商城"},
     *     description="获取猜你喜欢商品列表,销量前10,排除当前商品",
     *     operationId="getLovelyItemsList",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string"),
     *     @SWG\Parameter(name="item_id", in="query", description="当前商品id", type="integer", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="string"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         ref="#/definitions/GoodsList"
     *                         )
     *                 ),
     *                 @SWG\Property(
     *                    property="brand_list",
     *                    type="object",
     *                    @SWG\Property(property="total_count", type="string"),
     *                    @SWG\Property(
     *                        property="list",
     *                        type="array",
     *                        @SWG\Items(
     *                            @SWG\Property(property="attribute_id", type="string", description="Id", example="1"),
     *                            @SWG\Property(property="attribute_name", type="string", description="名称", example="test2"),
     *                            @SWG\Property(property="attribute_sort", type="integer", description="排序", example="1"),
     *                            @SWG\Property(property="is_show", type="string", description="是否用于筛选", example="true"),
     *                            @SWG\Property(property="is_image", type="string", description="否需要配置图片", example="true"),
     *                            @SWG\Property(property="image_url", type="string", description="图片地址", example="http://b-img-cdn.yuanyuanke.cn/image/1/2020/10/27/29c57ca983c4c37b59b5a2ba68d6cecaJwqf2uUBnaQaHCBzv3fjf88GwRPRlBYP"),
     *                        )
     *                 )
     *
     *                 ),
     *                 @SWG\Property(
     *                     property="cur",
     *                     type="object",
     *                     ref="#/definitions/Cur"
     *                 ),
     *
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getLovelyItemsList(request $request)
    {
        $authInfo = $request->get('auth');

        //验证参数todo
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'item_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }

        $params['company_id'] = $authInfo['company_id'];
        $params['item_id|neq'] = $inputData['item_id'];

        $params['approve_status'] = ['onsale', 'only_show'];
        $params['audit_status'] = 'approved';
        $params['item_type'] = 'normal';

        $orderBy['sales'] = 'desc';
        $orderBy['item_id'] = 'desc';

        $page = 1;
        $pageSize = 10;

        $params['is_default'] = true;

        $itemsService = new ItemsService();
        $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);

        $result['cur'] = $this->getCur($params['company_id']);
        return $this->response->array($result);
    }
}
