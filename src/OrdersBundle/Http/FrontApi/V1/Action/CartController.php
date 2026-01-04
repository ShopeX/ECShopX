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

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CrossBorderBundle\Services\Set as CrossBorderSet;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use OrdersBundle\Services\CartService;
use OrdersBundle\Traits\GetCartTypeServiceTrait;

class CartController extends BaseController
{
    use GetCartTypeServiceTrait;

    /**
     * @SWG\Post(
     *     path="/wxapp/cart",
     *     summary="购物车增加",
     *     tags={"订单"},
     *     description="购物车增加",
     *     operationId="addCart",
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="shop_type", in="query", description="店铺类型：shop,distributor,community",  type="string"),
     *     @SWG\Parameter( name="activity_type", in="query", description="活动类型", type="string"),
     *     @SWG\Parameter( name="items_id[]", in="query", description="组合商品ID", type="array", items={"type", "integer"}, collectionFormat="multi"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", required=true, type="integer"),
     *     @SWG\Parameter( name="num", in="query", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="items[][item_id]", in="query", description="批量添加购物车商品ID", type="array", items={"type", "integer"}, collectionFormat="multi"),
     *     @SWG\Parameter( name="items[][num]", in="query", description="批量添加购物车商品数量", type="array", items={"type", "integer"}, collectionFormat="multi"),
     *     @SWG\Parameter( name="cart_type", in="query", description="购物车类型", type="string"),
     *     @SWG\Parameter( name="isAccumulate", in="query", description="购物车数量是否是累加", type="boolean"),
     *     @SWG\Parameter( name="iscrossborder", in="query", description="是否海外购", type="integer"),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏操作", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="valid_cart",
     *                     type="array",
     *                     description="有效购物车",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="shop_name", type="integer", description="店铺名称"),
     *                         @SWG\Property(property="address", type="integer", description="店铺地址"),
     *                         @SWG\Property(property="mobile", type="integer", description="店铺手机号"),
     *                         @SWG\Property(property="lat", type="integer", description="腾讯地图经度"),
     *                         @SWG\Property(property="lng", type="integer", description="腾讯地图纬度"),
     *                         @SWG\Property(property="hour", type="integer", description="营业时间"),
     *                         @SWG\Property(property="is_ziti", type="integer", description="是否支持自提"),
     *                         @SWG\Property(property="is_delivery", type="integer", description="是否支持配送"),
     *                         @SWG\Property(property="shop_id", type="integer", description="门店ID"),
     *                         @SWG\Property(property="cart_total_price", type="integer", description="计算商品促销之前的购物车总价"),
     *                         @SWG\Property(property="item_fee", type="integer", description="商品原价总金额"),
     *                         @SWG\Property(property="cart_total_num", type="integer", description="购物车商品数量"),
     *                         @SWG\Property(property="cart_total_count", type="integer", description="购物车商品总量"),
     *                         @SWG\Property(property="discount_fee", type="integer", description="优惠总金额"),
     *                         @SWG\Property(property="total_fee", type="integer", description="结算总金额"),
     *                         @SWG\Property(property="member_discount", type="integer", description="会员折扣金额"),
     *                         @SWG\Property(
     *                             property="list",
     *                             type="array",
     *                             description="购物车商品列表",
     *                             @SWG\Items(
     *                                 type="object",
     *                                 @SWG\Property(property="cart_id", type="integer", description="购物车ID"),
     *                                 @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                                 @SWG\Property(property="user_id", type="integer", description="用户ID"),
     *                                 @SWG\Property(property="user_ident", type="string", description="会员ident"),
     *                                 @SWG\Property(property="shop_type", type="string", description="店铺类型"),
     *                                 @SWG\Property(property="shop_id", type="integer", description="店铺id"),
     *                                 @SWG\Property(property="activity_type", type="string", description="活动类型"),
     *                                 @SWG\Property(property="activity_id", type="integer", description="活动id"),
     *                                 @SWG\Property(property="marketing_type", type="string", description="促销类型"),
     *                                 @SWG\Property(property="marketing_id", type="integer", description="促销id"),
     *                                 @SWG\Property(property="item_type", type="string", description="商品类型"),
     *                                 @SWG\Property(property="item_id", type="integer", description="商品id"),
     *                                 @SWG\Property(property="items_id", type="array", description="组合商品关联商品id", @SWG\Items()),
     *                                 @SWG\Property(property="item_name", type="string", description="商品名称"),
     *                                 @SWG\Property(property="pics", type="string", description="图片"),
     *                                 @SWG\Property(property="price", type="integer", description="购买商品价格"),
     *                                 @SWG\Property(property="num", type="integer", description="购买商品数量"),
     *                                 @SWG\Property(property="wxa_appid", type="string", description="小程序appid"),
     *                                 @SWG\Property(property="is_checked", type="boolean", description="购物车是否选中"),
     *                                 @SWG\Property(property="is_plus_buy", type="boolean", description="是加价购商品"),
     *                                 @SWG\Property(property="created", type="integer", description="创建时间"),
     *                                 @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                                 @SWG\Property(property="is_last_price", type="boolean", description="是否为最后固定的价格"),
     *                                 @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
     *                                 @SWG\Property(property="total_fee", type="integer", description="结算金额"),
     *                                 @SWG\Property(property="store", type="integer", description="商品库存"),
     *                                 @SWG\Property(property="market_price", type="integer", description="销售价"),
     *                                 @SWG\Property(property="brief", type="string", description="商品简介"),
     *                                 @SWG\Property(property="approve_status", type="string", description="商品状态"),
     *                                 @SWG\Property(property="item_spec_desc", type="string", description="商品规格"),
     *                                 @SWG\Property(property="parent_id", type="integer", description="组合商品购物车ID"),
     *                                 @SWG\Property(property="goods_id", type="integer", description="产品ID"),
     *                                 @SWG\Property(property="item_category", type="integer", description="商品主类目"),
     *                                 @SWG\Property(property="type", type="integer", description="商品类型，0普通，1跨境商品"),
     *                                 @SWG\Property(property="crossborder_tax_rate", type="string", description="跨境税率，百分比，小数点2位"),
     *                                 @SWG\Property(property="taxstrategy_id", type="integer", description="税费策略id"),
     *                                 @SWG\Property(property="taxation_num", type="integer", description="计税单位份数"),
     *                                 @SWG\Property(property="origincountry_id", type="integer", description="产地国id"),
     *                                 @SWG\Property(property="origincountry_name", type="string", description="产地国称"),
     *                                 @SWG\Property(property="origincountry_img_url", type="string", description="产地国图标"),
     *                                 @SWG\Property(property="discount_desc", type="string", description="优惠描述"),
     *                                 @SWG\Property(property="grade_name", type="string", description="等级名称"),
     *                                 @SWG\Property(property="member_discount", type="integer", description="会员折扣金额"),
     *                                 @SWG\Property(
     *                                     property="activity_info",
     *                                     type="array",
     *                                     description="参会的活动信息",
     *                                     @SWG\Items(
     *                                         type="object",
     *                                         @SWG\Property(property="id", type="integer", description="活动ID"),
     *                                         @SWG\Property(property="type", type="string", description="活动类型"),
     *                                         @SWG\Property(property="info", type="integer", description="活动描述"),
     *                                         @SWG\Property(property="rule", type="integer", description="活动规则"),
     *                                         @SWG\Property(property="discount_fee", type="integer", description="折扣金额"),
     *                                     )
     *                                 )
     *                             )
     *                         ),
     *                         @SWG\Property(
     *                             property="used_activity",
     *                             type="array",
     *                             description="参与的活动列表",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="used_activity_ids",
     *                             type="array",
     *                             description="参与的活动ID列表",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="gift_activity",
     *                             type="array",
     *                             description="参与的满赠活动列表",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="plus_buy_activity",
     *                             type="array",
     *                             description="参与加价购活动列表",
     *                             @SWG\Items()
     *                         ),
     *                     )
     *                 ),
     *                 @SWG\Property(
     *                     property="invalid_cart",
     *                     type="array",
     *                     description="失效购物车列表",
     *                     @SWG\Items()
     *                 ),
     *                 @SWG\Property(property="is_check_store", type="boolean", description="是否检查过库存"),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function addCart(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['promoter_user_id'] = $params['promoter_user_id'] ?? 0;
        $params['wxa_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['shop_id'] = $params['shop_id'] ?? 0;
        $params['activity_type'] = $params['activity_type'] ?? '';
        if (!$params['shop_id']) {
            $params['shop_id'] = $params['distributor_id'] ?? 0;
        }

        if (!$params['activity_type']) {
            $params['activity_type'] = 'normal';  //普通商品
        }

        if ($params['activity_type'] == 'package') {
            if (!isset($params['items_id']) || !$params['items_id']) {
                throw new ResourceException(trans('OrdersBundle/Order.please_select_package_items'));
            }
            $params['items_id'] = is_array($params['items_id']) ? $params['items_id'] : json_decode($params['items_id'], 1);
        }


        if($params['promoter_user_id'] &&  $authInfo['user_id'] == $params['promoter_user_id'] ){
            $params['user_id'] = $params['buy_user_id'] ;
        }

        $cartService = new CartService();
        $result = $cartService->addCart($params);
        if ($result) {
            // 查询购物车信息
            $shopId = $params['shop_id'];
            if (!$shopId || $shopId == 'undefined') {
                $filter['shop_id'] = 0;
            } else {
                $filter['shop_id'] = $shopId;
            }
            $cartType = $params['cart_type'] ?? 'cart';
            $shopType = $request->input('shop_type', 'distributor');
            $result = $cartService->getCartList($authInfo['company_id'], $params['user_id'], $filter['shop_id'], $cartType, $shopType, false, $params['iscrossborder'] ?? 0, $params['isShopScreen'] ?? 0);

            // 清空换购商品
            $cartService->resetPlusBuyCart($params['company_id'], $params['user_id'], $result);

            return $this->response->array($result);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/cart",
     *     summary="购物车列表",
     *     tags={"订单"},
     *     description="购物车列表",
     *     operationId="getCartList",
     *     @SWG\Parameter( name="shop_type", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="shop_id,distributor_id,community_id", required=true, type="string"),
     *     @SWG\Parameter( name="activity_type", in="query", description="例子：community", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getCartList(Request $request)
    {
        $result = [];
        $authInfo = $request->get('auth');
        if (!isset($authInfo['user_id']) || !$authInfo['user_id']) {
            return $this->response->array($result);
        }
        $filter['user_id'] = $authInfo['user_id'];

        $shopId = $request->input('shop_id', 0);
        if (!$shopId) {
            $shopId = $request->input('distributor_id', 0);
        }

        if (!$shopId || $shopId == 'undefined') {
            $filter['shop_id'] = 0;
        } else {
            $filter['shop_id'] = $shopId;
        }

        $cartService = new CartService();
        $cartType = 'cart';
        $shopType = $request->input('shop_type', 'distributor');
        $result = $cartService->getCartList($authInfo['company_id'], $authInfo['user_id'], $filter['shop_id'], $cartType, $shopType);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/cart/list",
     *     summary="多店铺购物车列表",
     *     tags={"订单"},
     *     description="多店铺购物车列表",
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="shop_type", in="query", description="店铺类型：shop,distributor,community",  type="string"),
     *     @SWG\Parameter( name="items[][item_id]", in="query", description="批量添加购物车商品ID", type="array", items={"type", "integer"}, collectionFormat="multi"),
     *     @SWG\Parameter( name="items[][num]", in="query", description="批量添加购物车商品数量", type="array", items={"type", "integer"}, collectionFormat="multi"),
     *     @SWG\Parameter( name="cart_type", in="query", description="购物车类型", type="string"),
     *     @SWG\Parameter( name="iscrossborder", in="query", description="是否海外购", type="integer"),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏操作", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="valid_cart",
     *                     type="array",
     *                     description="有效购物车",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="shop_name", type="integer", description="店铺名称"),
     *                         @SWG\Property(property="address", type="integer", description="店铺地址"),
     *                         @SWG\Property(property="mobile", type="integer", description="店铺手机号"),
     *                         @SWG\Property(property="lat", type="integer", description="腾讯地图经度"),
     *                         @SWG\Property(property="lng", type="integer", description="腾讯地图纬度"),
     *                         @SWG\Property(property="hour", type="integer", description="营业时间"),
     *                         @SWG\Property(property="is_ziti", type="integer", description="是否支持自提"),
     *                         @SWG\Property(property="is_delivery", type="integer", description="是否支持配送"),
     *                         @SWG\Property(property="shop_id", type="integer", description="门店ID"),
     *                         @SWG\Property(property="cart_total_price", type="integer", description="计算商品促销之前的购物车总价"),
     *                         @SWG\Property(property="item_fee", type="integer", description="商品原价总金额"),
     *                         @SWG\Property(property="cart_total_num", type="integer", description="购物车商品数量"),
     *                         @SWG\Property(property="cart_total_count", type="integer", description="购物车商品总量"),
     *                         @SWG\Property(property="discount_fee", type="integer", description="优惠总金额"),
     *                         @SWG\Property(property="total_fee", type="integer", description="结算总金额"),
     *                         @SWG\Property(property="member_discount", type="integer", description="会员折扣金额"),
     *                         @SWG\Property(
     *                             property="list",
     *                             type="array",
     *                             description="购物车商品列表",
     *                             @SWG\Items(
     *                                 type="object",
     *                                 @SWG\Property(property="cart_id", type="integer", description="购物车ID"),
     *                                 @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                                 @SWG\Property(property="user_id", type="integer", description="用户ID"),
     *                                 @SWG\Property(property="user_ident", type="string", description="会员ident"),
     *                                 @SWG\Property(property="shop_type", type="string", description="店铺类型"),
     *                                 @SWG\Property(property="shop_id", type="integer", description="店铺id"),
     *                                 @SWG\Property(property="activity_type", type="string", description="活动类型"),
     *                                 @SWG\Property(property="activity_id", type="integer", description="活动id"),
     *                                 @SWG\Property(property="marketing_type", type="string", description="促销类型"),
     *                                 @SWG\Property(property="marketing_id", type="integer", description="促销id"),
     *                                 @SWG\Property(property="item_type", type="string", description="商品类型"),
     *                                 @SWG\Property(property="item_id", type="integer", description="商品id"),
     *                                 @SWG\Property(property="items_id", type="array", description="组合商品关联商品id", @SWG\Items()),
     *                                 @SWG\Property(property="item_name", type="string", description="商品名称"),
     *                                 @SWG\Property(property="pics", type="string", description="图片"),
     *                                 @SWG\Property(property="price", type="integer", description="购买商品价格"),
     *                                 @SWG\Property(property="num", type="integer", description="购买商品数量"),
     *                                 @SWG\Property(property="wxa_appid", type="string", description="小程序appid"),
     *                                 @SWG\Property(property="is_checked", type="boolean", description="购物车是否选中"),
     *                                 @SWG\Property(property="is_plus_buy", type="boolean", description="是加价购商品"),
     *                                 @SWG\Property(property="created", type="integer", description="创建时间"),
     *                                 @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                                 @SWG\Property(property="is_last_price", type="boolean", description="是否为最后固定的价格"),
     *                                 @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
     *                                 @SWG\Property(property="total_fee", type="integer", description="结算金额"),
     *                                 @SWG\Property(property="store", type="integer", description="商品库存"),
     *                                 @SWG\Property(property="market_price", type="integer", description="销售价"),
     *                                 @SWG\Property(property="brief", type="string", description="商品简介"),
     *                                 @SWG\Property(property="approve_status", type="string", description="商品状态"),
     *                                 @SWG\Property(property="item_spec_desc", type="string", description="商品规格"),
     *                                 @SWG\Property(property="parent_id", type="integer", description="组合商品购物车ID"),
     *                                 @SWG\Property(property="goods_id", type="integer", description="产品ID"),
     *                                 @SWG\Property(property="item_category", type="integer", description="商品主类目"),
     *                                 @SWG\Property(property="type", type="integer", description="商品类型，0普通，1跨境商品"),
     *                                 @SWG\Property(property="crossborder_tax_rate", type="string", description="跨境税率，百分比，小数点2位"),
     *                                 @SWG\Property(property="taxstrategy_id", type="integer", description="税费策略id"),
     *                                 @SWG\Property(property="taxation_num", type="integer", description="计税单位份数"),
     *                                 @SWG\Property(property="origincountry_id", type="integer", description="产地国id"),
     *                                 @SWG\Property(property="origincountry_name", type="string", description="产地国称"),
     *                                 @SWG\Property(property="origincountry_img_url", type="string", description="产地国图标"),
     *                                 @SWG\Property(property="discount_desc", type="string", description="优惠描述"),
     *                                 @SWG\Property(property="grade_name", type="string", description="等级名称"),
     *                                 @SWG\Property(property="member_discount", type="integer", description="会员折扣金额"),
     *                                 @SWG\Property(
     *                                     property="activity_info",
     *                                     type="array",
     *                                     description="参会的活动信息",
     *                                     @SWG\Items(
     *                                         type="object",
     *                                         @SWG\Property(property="id", type="integer", description="活动ID"),
     *                                         @SWG\Property(property="type", type="string", description="活动类型"),
     *                                         @SWG\Property(property="info", type="integer", description="活动描述"),
     *                                         @SWG\Property(property="rule", type="integer", description="活动规则"),
     *                                         @SWG\Property(property="discount_fee", type="integer", description="折扣金额"),
     *                                     )
     *                                 )
     *                             )
     *                         ),
     *                         @SWG\Property(
     *                             property="used_activity",
     *                             type="array",
     *                             description="参与的活动列表",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="used_activity_ids",
     *                             type="array",
     *                             description="参与的活动ID列表",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="gift_activity",
     *                             type="array",
     *                             description="参与的满赠活动列表",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="plus_buy_activity",
     *                             type="array",
     *                             description="参与加价购活动列表",
     *                             @SWG\Items()
     *                         ),
     *                     )
     *                 ),
     *                 @SWG\Property(
     *                     property="invalid_cart",
     *                     type="array",
     *                     description="失效购物车列表",
     *                     @SWG\Items()
     *                 ),
     *                 @SWG\Property(property="is_check_store", type="boolean", description="是否检查过库存"),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getDistributorCartList(Request $request)
    {
        $userDevice = $request->get('user_device');
        $iscrossborder = $request->get('iscrossborder', 0);        // 是否跨境购物车
        $isShopScreen = $request->get('isShopScreen', 0);
        $isNostores = $request->get('isNostores', 2);// 是否关闭前端店铺  0:否 1:是 2:参数未传，不做处理
        $authInfo = $request->get('auth');
        $cartType = $request->input('cart_type', 'cart');
        $shopType = $request->input('shop_type', 'distributor');
        $items = $request->input('items', []);
        $promoter_user_id = $request->input('promoter_user_id', 0);
        $buy_user_id = $request->input('buy_user_id', 0);
        $inputData = array();
        if($promoter_user_id){
            $inputData['promoter_user_id'] = $promoter_user_id;
        }
        $result = [];
        if ((!isset($authInfo['user_id']) || !$authInfo['user_id']) && $cartType != 'offline') {
            return $this->response->array($result);
        }
        $inputData['user_id'] = $authInfo['user_id'];

        if($buy_user_id && $promoter_user_id == $authInfo['user_id']){
            $inputData['user_id'] = $buy_user_id;
        }

        $shopId = $request->input('shop_id', 0);
        if (!$shopId) {
            $shopId = $request->input('distributor_id', 0);
        }

        $cartService = new CartService();
        $result['invalid_cart'] = [];
        $result['valid_cart'] = [];
        if ($cartType == 'offline') {
            if (empty($items)) {
                return $this->response->array($result);
            }

            $cartData = $cartService->getCartList($authInfo['company_id'], $authInfo['user_id'], $shopId, $cartType, $shopType, false, $iscrossborder, $isShopScreen, $userDevice, $items);
            if ($cartData['valid_cart']) {
                $result['valid_cart'][] = $cartData['valid_cart'][0];
            }
            if ($cartData['invalid_cart']) {
                $result['invalid_cart'] = array_merge($result['invalid_cart'], $cartData['invalid_cart']);
            }
        } else {
            $cartFilter = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $inputData['user_id'],
                'shop_type' => $shopType,
                // 'shop_id' => $shopId,
            ];

            if ($isShopScreen || $shopId > 0) {
                $cartFilter['shop_id'] = $shopId;
            } elseif ($isNostores == 1) {
                // 小程序关闭前端店铺状态为1时，查询前端传递的店铺id，前端会传递0
                $cartFilter['shop_id'] = $shopId;
            } elseif ($isNostores == 0) {
                // 小程序关闭前端店铺状态为0时，只查询店铺id>0的商品
                // $cartFilter['shop_id|gt'] = 0;
            }
            $cartList = $cartService->lists($cartFilter);
            if ($cartList['total_count'] === 0) {
                return $this->response->array($result);
            }

            // 如果带店铺过来，怎显示该店铺的购物车 2025年9月18日18:03:26
            if ($shopId) {
                $shopIds = [$shopId];
            }else {
                $shopIds = array_column($cartList['list'], 'shop_id');
                $shopIds = array_unique($shopIds);
            }
            foreach ($shopIds as $shopId) {
                $cartData = $cartService->getCartList($authInfo['company_id'], $inputData['user_id'], $shopId, $cartType, $shopType, false, $iscrossborder, $isShopScreen, $userDevice,[],$inputData);
                if ($cartData['valid_cart']) {
                    $result['valid_cart'][] = $cartData['valid_cart'][0];
                }
                if ($cartData['invalid_cart']) {
                    $result['invalid_cart'] = array_merge($result['invalid_cart'], $cartData['invalid_cart']);
                }
            }
        }
        // 获取跨境设置信息
        $Set = new CrossBorderSet();
        $CrossBorderSet = $Set->getInfo($authInfo['company_id']);
        if (empty($CrossBorderSet['crossborder_show'])) {
            $result['crossborder_show'] = 0;
        } else {
            $result['crossborder_show'] = $CrossBorderSet['crossborder_show'];
        }

        // 总部发货的商品购物车分开显示
        foreach ($result['valid_cart'] as $key => $shopCart) {
            $list = $logisticsList = [];
            foreach ($shopCart['list'] as $item) {
                // 跨境商品
                if ($item['type'] == 1) {
                    $item['total_fee'] = bcadd($item['total_fee'], $item['cross_border_taxation']);
                    if ($item['is_checked']) {
                        $result['valid_cart'][$key]['total_fee'] = bcadd($shopCart['total_fee'], $item['cross_border_taxation']);
                    }
                }

                if ($item['is_logistics'] ?? false) {
                    $logisticsList[] = $item;
                } else {
                    $list[] = $item;
                }
            }
            $result['valid_cart'][$key]['list'] = $list;
            $result['valid_cart'][$key]['logistics_list'] = $logisticsList;
            //加上加价购的商品金额
            if (isset($shopCart['plus_buy_activity'])) {
                foreach ($shopCart['plus_buy_activity'] as $plusBuyActivity) {
                    if (isset($plusBuyActivity['plus_item'])) {
                        $plusItem = $plusBuyActivity['plus_item'];
                        $result['valid_cart'][$key]['total_fee'] = bcadd($result['valid_cart'][$key]['total_fee'], $plusItem['plus_price']);
                        $result['valid_cart'][$key]['item_fee'] = bcadd($result['valid_cart'][$key]['item_fee'], $plusItem['price']);
                        $result['valid_cart'][$key]['discount_fee'] = bcadd($result['valid_cart'][$key]['discount_fee'], bcsub($plusItem['price'], $plusItem['plus_price']));
                    }
                }
            }

            //购物车总计金额不含商品立减优惠，含满减/折的优惠
            if (isset($shopCart['activity_grouping'])) {
                foreach ($shopCart['activity_grouping'] as $activityInfo) {
                    if (in_array($activityInfo['activity_type'], ['full_minus', 'full_discount'])) {
                        $result['valid_cart'][$key]['total_fee'] += $activityInfo['discount_fee'];
                    }
                }
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/cartdel",
     *     summary="购物车删除",
     *     tags={"订单"},
     *     description="购物车删除",
     *     operationId="getCartList",
     *     @SWG\Parameter( name="cart_id", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Response(
     *       response=200,
     *       description="",
     *       @SWG\Schema(
     *         @SWG\Property(
     *           property="data",
     *           description="数据集合",
     *           type="object",
     *           @SWG\Property(property="status", description="删除状态 true 成功 false 失败", type="boolean"),
     *         )
     *       )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */

    public function deleteCartData(Request $request)
    {
        // IDX: 2367340174
        $cartId = $request->input('cart_id');
        $shopId = $request->input('distributor_id');
        if (isset($cartId)) {
            $filter['cart_id'] = $cartId;
        }
        if ($shopId) {
            $filter['shop_id'] = $shopId;
        }
        $buy_user_id = $request->input('buy_user_id',0);

        $authInfo = $request->get('auth');
        $filter['user_id'] = $authInfo['user_id'];
        if($buy_user_id) $filter['user_id'] = $buy_user_id;
        $filter['company_id'] = $authInfo['company_id'];
        $cartService = new CartService();
        $result['status'] = $cartService->deleteBy($filter);
        return $this->response->array($result);
    }



    /**
     * @SWG\Delete(
     *     path="/wxapp/cartdelBat",
     *     summary="购物车删除",
     *     tags={"订单"},
     *     description="购物车删除",
     *     operationId="getCartList",
     *     @SWG\Parameter( name="cart_id", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Response(
     *       response=200,
     *       description="",
     *       @SWG\Schema(
     *         @SWG\Property(
     *           property="data",
     *           description="数据集合",
     *           type="object",
     *           @SWG\Property(property="status", description="删除状态 true 成功 false 失败", type="boolean"),
     *         )
     *       )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */

     public function deleteCartDataBat(Request $request)
     {
         // IDX: 2367340174
         $cartId = $request->input('cart_id');
         if (isset($cartId)) {
             $filter['cart_id'] = $cartId;
         }
         $cartIdList = $request->input('cart_id_list',false);
         if (isset($cartIdList)) {
            $cartIdListData = explode(',',$cartIdList);

            $filter['cart_id'] = $cartIdListData;
         }
         $buy_user_id = $request->input('buy_user_id');

         $authInfo = $request->get('auth');
         if($buy_user_id) $filter['user_id'] = $buy_user_id;
         $filter['company_id'] = $authInfo['company_id'];
         $cartService = new CartService();

         app('log')->info(':cart:'.__FUNCTION__.__LINE__.':del:' . json_encode($filter));

         $result['status'] = $cartService->deleteBy($filter);
         return $this->response->array($result);
     }

    /**
     * @SWG\Put(
     *     path="/wxapp/cartupdate/checkstatus",
     *     summary="修改购物车选中状态",
     *     tags={"订单"},
     *     description="修改购物车选中状态",
     *     operationId="updateCartCheckStatus",
     *     @SWG\Parameter( name="cart_id", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Parameter( name="is_checked", in="query", description="shop_id,distributor_id,community_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="integer", description="状态 1 成功 0 失败"),
     *                 )
     *             ),
     *          ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */

    public function updateCartCheckStatus(Request $request)
    {
        $filter['cart_id'] = $request->input('cart_id');
        if (!$filter['cart_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.cart_param_error'));
        }
        $authInfo = $request->get('auth');
        $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];

        $buy_user_id = $request->input('buy_user_id',0);
        if($buy_user_id) $filter['user_id'] = $buy_user_id;

        if (!$request->input('is_checked') || $request->input('is_checked') === 'false') {
            $params['is_checked'] = false;
        } else {
            $params['is_checked'] = true;
        }
        $cartService = new CartService();
        $result = $cartService->updateBy($filter, $params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/cartupdate/promotion",
     *     summary="修改购物车促销",
     *     tags={"订单"},
     *     description="修改购物车促销",
     *     operationId="updateCartItemPromotion",
     *     @SWG\Parameter( name="cart_id", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="string"),
     *     @SWG\Parameter( name="activity_type", in="query", description="活动类型，community：社区活动，goods_promotion:商品促销", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Property(property="status", type="boolean", description="状态 true 成功"),
     *           ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateCartItemPromotion(Request $request)
    {
        $filter['cart_id'] = $request->input('cart_id');
        if (!$filter['cart_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.cart_param_error'));
        }
        $activityId = $request->input('activity_id');
        if (!$activityId) {
            throw new ResourceException(trans('OrdersBundle/Order.promotion_id_error'));
        }
        $filter['shop_type'] = $request->input('shop_type', 'distributor');   //默认店铺

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        if ($authInfo['wxapp_appid'] ?? '') {
            $filter['wxa_appid'] = $authInfo['wxapp_appid'];
        }

        $cartService = $this->getCartTypeService($filter['shop_type']);
        $result = $cartService->updateCartItemPromotion($filter, $activityId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/cartupdate/num",
     *     summary="修改购物车商品数量(单一)",
     *     tags={"订单"},
     *     description="修改购物车商品数量(单一)",
     *     operationId="updateCartNum",
     *     @SWG\Parameter( name="cart_id", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Parameter( name="is_checked", in="query", description="shop_id,distributor_id,community_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Property(property="activity_id", type="integer", description="活动id"),
     *             @SWG\Property(property="activity_type", type="integer", description="活动类型"),
     *             @SWG\Property(property="cart_id", type="integer", description="购物车id"),
     *             @SWG\Property(property="company_id", type="integer", description="公司id"),
     *             @SWG\Property(property="created", type="integer", description="创建时间"),
     *             @SWG\Property(property="is_checked", type="boolean", description="购物车是否选中"),
     *             @SWG\Property(property="is_plus_buy", type="boolean", description="是加价购商品"),
     *             @SWG\Property(property="item_id", type="string", description="商品id"),
     *             @SWG\Property(property="item_name", type="string", description="商品名称"),
     *             @SWG\Property(property="item_type", type="string", description="商品类型。可选值有 normal 实体类商品;services 服务类商品, normal_gift:实体赠品,services_gift:服务类赠品"),
     *             @SWG\Property(property="items_id", type="string", description="组合商品关联商品id"),
     *             @SWG\Property(property="marketing_id", type="integer", description="促销id"),
     *             @SWG\Property(property="marketing_type", type="integer", description="促销类型"),
     *             @SWG\Property(property="num", type="integer", description="购买商品数量"),
     *             @SWG\Property(property="pics", type="integer", description="图片"),
     *             @SWG\Property(property="price", type="integer", description="购买商品价格 单位分"),
     *             @SWG\Property(property="shop_id", type="integer", description="店铺id 或者 社区id"),
     *             @SWG\Property(property="shop_type", type="integer", description="店铺类型；distributor:店铺，shop:门店，community:社区, mall:商城, drug 药品清单"),
     *             @SWG\Property(property="updated", type="integer", description="修改时间"),
     *             @SWG\Property(property="user_id", type="integer", description="会员id"),
     *             @SWG\Property(property="user_ident", type="integer", description="会员ident,会员信息和session生成的唯一值"),
     *             @SWG\Property(property="wxa_appid", type="integer", description="小程序appid"),
     *           ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateCartNum(Request $request)
    {
        if ($request->input('cart_id')) {
            $cartId = $request->input('cart_id');
        } else {
            throw new ResourceException(trans('OrdersBundle/Order.param_error'));
        }

        // if (!$request->input('num')) {
        //     throw new ResourceException('更新的购物车商品数量必填');
        // }

        $authInfo = $request->get('auth');
        $cartService = new CartService();

        $buy_user_id = $request->input('buy_user_id',0);

        $filter = [
            'cart_id' => $cartId,
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id']
        ];
        if($buy_user_id) $filter['user_id'] = $buy_user_id;

        $cartInfo = $cartService->getInfo($filter);
        if (!$cartInfo) {
            throw new ResourceException(trans('OrdersBundle/Order.data_error'));
        }

        $cartInfo['num'] = $request->input('num', 0);
        $cartInfo['isAccumulate'] = false;
        $cartInfo['isShopScreen'] = $request->get('isShopScreen', 0);
        $result = $cartService->addCart($cartInfo);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/cartupdate/batchnum",
     *     summary="修改购物车商品数量(批量)",
     *     tags={"订单"},
     *     description="修改购物车商品数量(批量)",
     *     operationId="batchUpdateCartNum",
     *     @SWG\Parameter( name="cart", in="query", description="shop,distributor,community", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function batchUpdateCartNum(Request $request)
    {
        $postdata = $request->input();
        $rules = [
            'cart.*.cart_id' => ['required', '购物车id必填'],
            'cart.*.num' => ['required', '购物车商品数量必填'],
        ];
        $errorMessage = validator_params($postdata, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $authInfo = $request->get('auth');
        $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];

        $cartService = new CartService();
        foreach ($postdata['cart'] as $cart) {
            $filter['cart_id'] = $cart['cart_id'];
            if (isset($cart['item_id']) && $cart['item_id']) {
                $filter['item_id'] = $cart['item_id'];
            }
            $params['num'] = $cart['num'];
            $result[] = $cartService->updateOneBy($filter, $params);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/cartcount",
     *     summary="获取会员购物车商品数量",
     *     tags={"订单"},
     *     description="获取会员购物车商品数量",
     *     operationId="getCartItemCount",
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="shop_type", in="query", description="shop、distributor、community、mall", required=true, type="string"),
     *     @SWG\Response(
     *       response=200,
     *       description="",
     *       @SWG\Schema(
     *         @SWG\Property(
     *           property="data",
     *           description="数据集合",
     *           type="object",
     *           @SWG\Property(property="cart_count", description="购物车数量", type="integer"),
     *           @SWG\Property(property="item_count", description="商品数量", type="integer"),
     *         )
     *       )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getCartItemCount(Request $request)
    {
        $iscrossborder = $request->get('iscrossborder', 0);

        $params = $request->all();
        $params['promoter_user_id'] = $params['promoter_user_id'] ?? 0;
        $params['buy_user_id']      = $params['buy_user_id'] ?? 0;

        if (!$request->input('shop_type')) {
            throw new ResourceException(trans('OrdersBundle/Order.param_error'));
        }
        $authInfo = $request->get('auth');
        $cartType = $request->input('cart_type', 'cart');
        $shopId = $request->input('shop_id', 0);
        if (!$shopId) {
            $shopId = $request->input('distributor_id', 0);
        }

        $shopType = $request->input('shop_type', 'distributor');
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'shop_type' => $shopType,
            // 'shop_id' => $shopId,
        ];
        if($params['promoter_user_id'] && $params['buy_user_id']){
            $filter['user_id'] =  $params['buy_user_id'];
            $filter['promoter_user_id'] =  $params['promoter_user_id'];
        }

        $isShopScreen = $request->input('isShopScreen', 0);
        if ($isShopScreen || $shopId > 0) {
            $filter['shop_id'] = $shopId;
        }

        // if (is_numeric($shopId) && $request->input('shop_id', null) !== null) {
        //     $filter['shop_id'] = $shopId;
        // }

        $cartService = new CartService();
        $result = $cartService->countCart($filter, $cartType, $iscrossborder, $isShopScreen);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/cart/check/plusitem",
     *     summary="购物车中选择加价购商品",
     *     tags={"订单"},
     *     description="购物车增加",
     *     operationId="addCart",
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺id", type="string"),
     *     @SWG\Parameter( name="shop_type", in="query", description="店铺类型：shop,distributor,community",  type="string"),
     *     @SWG\Parameter( name="activity_type", in="query", description="活动类型", type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="num", in="query", description="商品数量", required=true, type="string"),
     *     @SWG\Parameter( name="isAccumulate", in="query", description="购物车数量是否是累加", type="boolean"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Property(property="status", type="boolean", description="状态 true 成功 false 失败"),
     *           ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function checkPlusItem(Request $request)
    {
        $authInfo = $request->get('auth');

        $item_id = $request->input('item_id', 0);
        $marketing_id = $request->input('marketing_id');
        $cartService = new CartService();
        $result = $cartService->checkPlusItem($authInfo['company_id'], $authInfo['user_id'], $marketing_id, $item_id);
        return $this->response->array(['status' => $result]);
    }
}
