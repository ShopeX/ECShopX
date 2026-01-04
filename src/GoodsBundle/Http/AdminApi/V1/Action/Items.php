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

namespace GoodsBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use DistributionBundle\Services\DistributorItemsService;
use GoodsBundle\Traits\ItemSearchFilter;
use PromotionsBundle\Traits\CheckPromotionsValid;
use SalespersonBundle\Services\SalespersonItemsShelvesService;
use CompanysBundle\Traits\GetDefaultCur;

class Items extends BaseController
{
    use CheckPromotionsValid;
    use ItemSearchFilter;
    use GetDefaultCur;
    /**
     * @SWG\Get(
     *     path="/wxapp/goods/items",
     *     summary="获取商品列表",
     *     tags={"商品"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="item_id", type="string"),
     *                     @SWG\Property(property="item_name", type="string"),
     *                     @SWG\Property(property="brief", type="string"),
     *                     @SWG\Property(property="price", type="string"),
     *                     @SWG\Property(property="market_price", type="string"),
     *                     @SWG\Property(property="intro", type="string"),
     *                     @SWG\Property(property="pics", type="string"),
     *                     @SWG\Property(property="company_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsList(request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $authInfo = $this->auth->user();
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $params['company_id'] = $authInfo['company_id'];
        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }
        $result['total_count'] = 0;
        $result['list'] = [];
        $inputData['item_type'] = $inputData['item_type'] ?? 'services';
        if ($inputData['item_type'] == 'services') {
            $inputData['approve_status'] = 'offline_sale,onsale';
        }
        $params = $this->getShopFilter($inputData, $authInfo);
        //  导购端无需查询上下架商品
        // if (isset($params['is_can_sale'])) unset($params['is_can_sale']);
        // if (isset($params['approve_status'])) unset($params['approve_status']);

        if (!$params) {
            return $this->response->array($result);
        }
        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $params['distributor_id'] = $request->input('distributor_id', 0);
        if ($request->input('goodsSort') == 1) {
            $orderBy['sales'] = 'desc';
        } elseif ($request->input('goodsSort') == 2) {
            $orderBy['price'] = 'desc';
        } elseif ($request->input('goodsSort') == 3) {
            $orderBy['price'] = 'asc';
        } elseif ($request->input('goodsSort') == 4) {
            $orderBy['created'] = 'desc';
        } else {
            $orderBy['sort'] = 'desc';
        }
        $itemsService = new ItemsService();
        $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);

        $userId = $request->get('user_id', 0);
        if ($result['list']) {
            // 计算会员价
            $result = $itemsService->getItemsListMemberPrice($result, $userId, $params['company_id']);
        }
        $result = $itemsService->getItemsListActityTag($result, $params['company_id']);
        $result['cur'] = $this->getCur($params['company_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/itemsinfo",
     *     summary="获取商品详情",
     *     tags={"商品"},
     *     description="获取商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="goods_id", in="query", description="当商品id", type="integer" ),
     *     @SWG\Parameter( name="item_id", in="query", description="货品id", type="integer" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="item_id", type="string"),
     *                     @SWG\Property(property="item_name", type="string"),
     *                     @SWG\Property(property="brief", type="string"),
     *                     @SWG\Property(property="price", type="string"),
     *                     @SWG\Property(property="market_price", type="string"),
     *                     @SWG\Property(property="intro", type="string"),
     *                     @SWG\Property(property="pics", type="string"),
     *                     @SWG\Property(property="company_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsDetail(Request $request)
    {
        $authInfo = $this->auth->user();

        $company_id = $authInfo['company_id'];
        $woa_appid = $authInfo['woa_appid'] ?? '';
        $userId = $request->get('user_id', 0);

        $itemsService = new ItemsService();
        // 如果传入goods_id那么则通过，goods_id获取到item_id
        // 防止链接中的item_id已经失效
        $goodsId = $request->input('goods_id', 0);
        $item_id = $request->input('item_id', 0);
        if ($goodsId) {
            $tempItemInfo = $itemsService->getInfo(['goods_id' => $goodsId, 'audit_status' => 'approved', 'is_default' => true, 'company_id' => $company_id]);

            if ($tempItemInfo) {
                $item_id = $tempItemInfo['item_id'];
            }
        } else {
            $tempItemInfo = $itemsService->getInfo(['item_id' => $item_id, 'audit_status' => 'approved', 'company_id' => $company_id]);
            if (!$tempItemInfo) {
                return $this->response->array(['item_id' => 0]);
            }
        }

        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->response->array(['item_id' => 0]);
        }

        $distributorId = $request->input('distributor_id', 0);
        if ($distributorId == 'undefined') {
            $distributorId = 0;
        }

        $promotionActivityData = $this->getCurrentActivityByItemId($company_id, $item_id, $distributorId);
        // 当前商品在进行活动
        $limitItemIds = array();
        if ($promotionActivityData) {
            $limitItemIds = !in_array($promotionActivityData['activity_type'], ['limited_buy']) ? array_column($promotionActivityData['list'], 'item_id') : [];
        }

        if ($limitItemIds && !in_array($item_id, $limitItemIds)) {
            $item_id = $limitItemIds[0];
        }

        //如果有分销商id。则获取店铺商品详情
        if ($distributorId) {
            $distributorItemsService = new DistributorItemsService();
            $result = $distributorItemsService->getValidDistributorItemInfo($company_id, $item_id, $distributorId, $woa_appid, $limitItemIds, true);
        } else {
            $result = $itemsService->getItemsDetail($item_id, $woa_appid, $limitItemIds, $company_id);
        }

        if (!$result) {
            return $this->response->array(['item_id' => 0]);
        }

        // 计算会员价
        $result = $itemsService->getItemsMemberPriceByUserId($result, $userId, $company_id);


        $result['promoter_price'] = (($result['promoter_price'] ?? 0) >= 1) ? $result['promoter_price'] : 0;
        //获取系统货币默认配置
        $result['cur'] = $this->getCur($company_id);
        $result['store'] = $result['item_total_store'] ?? $result['store'];

        //营销标签
        $itemsService = new ItemsService();
        $itemList['list'][0] = $result;
        $itemList = $itemsService->getItemsListActityTag($itemList, $authInfo['company_id']);
        $result = $itemList['list'][0];

        if ($result['promotion_activity'] ?? 0) {
            $salespersonItemsShelvesService = new SalespersonItemsShelvesService();
            $itemsShelves = $salespersonItemsShelvesService->getItemsShelves($company_id, $result['promotion_activity'][0]['promotion_id'], $result['promotion_activity'][0]['tag_type']);
            $result['promotion_activity'][0]['activity_name'] = $itemsShelves['activity_name'] ?? '未知活动';
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/promotion/items",
     *     summary="获取商品列表",
     *     tags={"商品"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Parameter( name="activity_type", in="query", description="活动类型:full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,group:拼团,seckill:秒杀,package:打包,limited_time_sale:限时特惠", type="integer" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="item_id", type="string"),
     *                     @SWG\Property(property="item_name", type="string"),
     *                     @SWG\Property(property="brief", type="string"),
     *                     @SWG\Property(property="price", type="string"),
     *                     @SWG\Property(property="market_price", type="string"),
     *                     @SWG\Property(property="intro", type="string"),
     *                     @SWG\Property(property="pics", type="string"),
     *                     @SWG\Property(property="company_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getPromotionItemList(Request $request)
    {
        $authInfo = $this->auth->user();
        // 店铺 需要开启：
        $count = (new DistributorService())->count([
            'company_id' => $authInfo['company_id'],
            'distributor_id' => $authInfo['distributor_id'],
            'is_valid' => 'true',
        ]);
        if (!$count) {
            return $this->response->array([]);
        }

        $inputData = $request->all('activity_type', 'sort', 'keywords', 'category');
        $inputData['distributor_id'] = $authInfo['distributor_id'];
        $itemsService = new ItemsService();
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        if ($inputData['activity_type'] ?? 0) {
            $filter = [
                'company_id' => $authInfo['company_id'],
                'distributor_id' => $authInfo['distributor_id'],
                'activity_type' => $inputData['activity_type'],
                'keywords' => $inputData['keywords'],
            ];
            $SalespersonItemsShelvesService = new SalespersonItemsShelvesService();
            $result = $SalespersonItemsShelvesService->getItemList($filter, $page, $pageSize, $inputData['sort'] ?? []);
        } else {
            $inputData['item_type'] = $inputData['item_type'] ?? 'normal';
            if ($inputData['item_type'] == 'services') {
                $inputData['approve_status'] = 'offline_sale,onsale';
            }
            // 默认 支持展示
            $isCanShow = $request->input('is_can_show', 1);
            $params = $this->getShopFilter($inputData, $authInfo, $isCanShow);
            if (!$params) {
                return $this->response->array([]);
            }
            $params['distributor_id'] = $authInfo['distributor_id'];
            $orderBy = [];
            if ($inputData['sort'] ?? 0) {
                $sort = explode('-', $inputData['sort']);
                switch ($sort[0]) {
                    case 'created':
                        $orderBy = ['created' => $sort[1]];
                        break;
                    case 'price':
                        $orderBy = ['price' => $sort[1]];
                        break;
                    case 'profit':
                        $orderBy = ['profit_fee' => $sort[1]];
                        break;
                    case 'sales':
                        $orderBy = ['sales' => $sort[1]];
                        break;
                }
            }
            $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);
        }
        $result = $itemsService->getItemsListActityTag($result, $authInfo['company_id']);
        return $this->response->array($result);
    }
}
