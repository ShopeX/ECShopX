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

namespace SalespersonBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

use GoodsBundle\Entities\ItemsBarcode;
use GoodsBundle\Services\ItemsService;
use SalespersonBundle\Services\SalespersonCartService;
use DistributionBundle\Services\DistributorService;
use SalespersonBundle\Services\SalesPromotionsService;

class SalespersonCartController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/scancodeAddcart",
     *     summary="扫条形码加入购物车",
     *     tags={"导购"},
     *     description="扫条形码加入购物车",
     *     operationId="scanCodeSales",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="barcode", in="query", description="条形码", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="msg", type="string", example="加入购物车成功", description="加购结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function scanCodeSales(Request $request)
    {
        $authInfo = $this->auth->user();
        // 获取条形码信息
        $barcode_ifilter['barcode'] = $request->get('barcode', 0);
        $barcode_ifilter['company_id'] = $authInfo['company_id'];
        $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);
        $barcode_info = $ItemsBarcode->getInfo($barcode_ifilter);

        $ifilter['item_id'] = $barcode_info['item_id'];
        $ifilter['company_id'] = $authInfo['company_id'];
        $itemsService = new ItemsService();
        app('log')->info('扫码查询商品' . var_export($ifilter, 1));
        app('log')->info('扫码查询商品导购' . var_export($authInfo, 1));
        $tempItemInfo = $itemsService->getInfo($ifilter);
        if (!$tempItemInfo) {
            throw new ResourceException('商品找不到.');
        }
        $filter['distributor_id'] = $authInfo['distributor_id'];
        $filter['company_id'] = $authInfo['company_id'];
        if ($filter['company_id'] != $authInfo['company_id']) {
            $distributorService = new DistributorService();
            $filter['distributor_id'] = $distributorService->getDefaultDistributorId($filter['company_id']);
        }
        $filter['item_id'] = $tempItemInfo['item_id'];
        $filter['salesperson_id'] = $authInfo['salesperson_id'];
        $params['num'] = 1;
        $params['is_checked'] = true;
        $isAccumulate = true;
        $salespersonCartService = new SalespersonCartService();
        $result = $salespersonCartService->addCartdata($filter, $params, $isAccumulate);
        if ($result['cart_id'] ?? null) {
            return $this->response->array(['status' => true, 'msg' => '加入购物车成功']);
        }
        return $this->response->array(['status' => false, 'msg' => '加入购物车失败']);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/cartdataadd",
     *     summary="导购员购物车新增",
     *     tags={"导购"},
     *     description="导购员购物车新增",
     *     operationId="cartdataAdd",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="item_id", in="query", description="商品item_id", required=true, type="integer"),
     *     @SWG\Parameter( name="num", in="query", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="is_accumulate", in="query", description="购物车数量更改方式，true:类增， false:覆盖", required=false, type="integer"),
     *     @SWG\Parameter( name="is_checked", in="query", description="是否选中，true:是， false:否", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="cart_id", type="string", example="359", description="购物车ID"),
     *                  @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员id"),
     *                  @SWG\Property( property="item_id", type="string", example="5471", description="商品id"),
     *                  @SWG\Property( property="package_items", type="string", example="", description="关联商品id集合"),
     *                  @SWG\Property( property="num", type="string", example="1", description="商品数量"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                  @SWG\Property( property="is_checked", type="string", example="true", description="购物车是否选中"),
     *                  @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function cartdataAdd(Request $request)
    {
        // Ref: 1996368445
        $authInfo = $this->auth->user();
        if (!($authInfo['salesperson_id'] ?? 0)) {
            throw new ResourceException('您的账号有误');
        }

        $inputParams = $request->all('item_id', 'num', 'is_checked', 'is_accumulate');

        $filter['item_id'] = $request->get('item_id');
        $filter['salesperson_id'] = $authInfo['salesperson_id'];
        $filter['distributor_id'] = $authInfo['distributor_id'];
        $filter['company_id'] = $authInfo['company_id'];
        if ($filter['company_id'] != $authInfo['company_id']) {
            $filter['distributor_id'] = $this->getDefaultDistributorId($filter['company_id']);
        }
        $params['num'] = $request->get('num', 0);
        $params['is_checked'] = $request->get('is_checked', true);
        $isAccumulate = $request->get('is_accumulate', true);
        $salespersonCartService = new SalespersonCartService();
        $result = $salespersonCartService->addCartdata($filter, $params, $isAccumulate);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/cartdatalist",
     *     summary="获取导购员购物车",
     *     tags={"导购"},
     *     description="获取导购员购物车",
     *     operationId="getCartdataList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/SalesCartData"
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getCartdataList(Request $request)
    {
        $authInfo = $this->auth->user();
        if (!($authInfo['salesperson_id'] ?? 0)) {
            throw new ResourceException('您的账号有误');
        }
        $userId = $request->get('user_id', 0);
        $filter['salesperson_id'] = $authInfo['salesperson_id'];
        $filter['distributor_id'] = $authInfo['distributor_id'];
        $filter['company_id'] = $authInfo['company_id'];

        if ($filter['company_id'] != $authInfo['company_id']) {
            $filter['distributor_id'] = $this->getDefaultDistributorId($filter['company_id']);
        }
        $salespersonCartService = new SalespersonCartService();
        $cartData = $salespersonCartService->getCartdataList($filter, $userId);
        return $this->response->array($cartData);
    }


    private function getDefaultDistributorId($companyId)
    {
        // Ref: 1996368445
        $distributorService = new DistributorService();
        $defaultDis = $distributorService->getDefaultDistributor($companyId);
        if ($defaultDis) {
            return $defaultDis['distributor_id'];
        }
        return 0;
    }

    /**
     * @SWG\Put(
     *     path="/salesperson/cartupdate/checkstatus",
     *     summary="修改购物车选中状态",
     *     tags={"导购"},
     *     description="修改购物车选中状态",
     *     operationId="updateCartCheckStatus",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="cart_id", in="query", description="购物车id，可传数组", required=true, type="string"),
     *     @SWG\Parameter( name="is_checked", in="query", description="是否选中 true:选中 false:取消选中", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */

    public function updateCartCheckStatus(Request $request)
    {
        $authInfo = $this->auth->user();
        if (!($authInfo['salesperson_id'] ?? 0)) {
            throw new ResourceException('您的账号有误');
        }

        $filter['cart_id'] = $request->input('cart_id');
        if (!$filter['cart_id']) {
            throw new ResourceException('购物车参数错误');
        }
        $filter['company_id'] = $authInfo['company_id'];
        if (!$request->input('is_checked') || $request->input('is_checked') === 'false') {
            $params['is_checked'] = 0;
        } else {
            $params['is_checked'] = 1;
        }
        $salespersonCartService = new SalespersonCartService();
        $result = $salespersonCartService->updateBy($filter, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/salesPromotion",
     *     summary="获取导购员促销单",
     *     tags={"导购"},
     *     description="获取导购员促销单",
     *     operationId="createSalesPromotion",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/SalesCartData"
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function createSalesPromotion(Request $request)
    {
        $authInfo = $this->auth->user();
        if (!($authInfo['salesperson_id'] ?? 0)) {
            throw new ResourceException('您的账号有误');
        }
        $filter['salesperson_id'] = $authInfo['salesperson_id'];
        $filter['distributor_id'] = $authInfo['distributor_id'];
        $filter['company_id'] = $authInfo['company_id'];
        $salespersonCartService = new SalespersonCartService();
        $cartData = $salespersonCartService->getCartdataList($filter, 0, true);

        if ($cartData['valid_cart'][0]['list'] ?? null) {
            $cartlist = $cartData['valid_cart'][0]['list'];
            $salesPromotionService = new SalesPromotionsService();
            $salePromotion = $salesPromotionService->createSalesPromotions($filter['company_id'], $filter['salesperson_id'], $filter['distributor_id'], $cartlist);
            $cartData['valid_cart'][0]['sales_promotion_id'] = $salePromotion['sales_promotion_id'];
        }
        return $this->response->array($cartData);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/salesperson/cartcount",
     *     summary="获取导购购物车商品数量",
     *     tags={"导购"},
     *     description="获取导购购物车商品数量",
     *     operationId="getCartItemCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getCartItemCount(Request $request)
    {
        $authInfo = $this->auth->user();
        if (!($authInfo['salesperson_id'] ?? 0)) {
            throw new ResourceException('您的账号有误');
        }

        $filter = [
            'company_id' => $authInfo['company_id'],
            'salesperson_id' => $authInfo['salesperson_id'],
        ];
        $salespersonCartService = new SalespersonCartService();
        $result = $salespersonCartService->countCart($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Definition(
     *     definition="SalesCartData",
     *     type="object",
     *     @SWG\Property( property="valid_cart", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_name", type="string", example="视力康眼镜(中兴路店)", description="门店名称 "),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="店铺地址 "),
     *                          @SWG\Property( property="mobile", type="string", example="15988939258", description="手机号"),
     *                          @SWG\Property( property="lat", type="string", example="33.144662", description="地图经度"),
     *                          @SWG\Property( property="lng", type="string", example="117.890888", description="地图纬度"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="店铺ID，如果为0则表示总部"),
     *                          @SWG\Property( property="cart_total_price", type="string", example="1123400800", description="商品总金额，以分为单位"),
     *                          @SWG\Property( property="item_fee", type="string", example="1123400800", description="商品金额，以分为单位"),
     *                          @SWG\Property( property="cart_total_num", type="string", example="9", description="商品数量"),
     *                          @SWG\Property( property="cart_total_count", type="string", example="3", description="总金额，以分为单位"),
     *                          @SWG\Property( property="discount_fee", type="string", example="9", description="订单优惠金额，以分为单位"),
     *                          @SWG\Property( property="total_fee", type="string", example="1123400791", description="应付总金额,以分为单位"),
     *                          @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                          @SWG\Property( property="list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="cart_id", type="string", example="203", description="购物车ID "),
     *                                  @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员id "),
     *                                  @SWG\Property( property="item_id", type="string", example="1070", description="商品id"),
     *                                  @SWG\Property( property="package_items", type="string", example="", description="关联商品id集合"),
     *                                  @SWG\Property( property="num", type="string", example="1", description="购买商品数量"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id，company_id为0的时候表示通用"),
     *                                  @SWG\Property( property="is_checked", type="string", example="1", description="购物车是否选中"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="33", description=" 店铺id "),
     *                                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                                  @SWG\Property( property="shop_id", type="string", example="33", description="门店id "),
     *                                  @SWG\Property( property="is_last_price", type="string", example="false", description="是否最终价格"),
     *                                  @SWG\Property( property="price", type="string", example="1123400000", description="商品价格,单位为‘分’ "),
     *                                  @SWG\Property( property="discount_fee", type="string", example="9", description="订单优惠金额，以分为单位 "),
     *                                  @SWG\Property( property="total_fee", type="string", example="1123399991", description="订单金额，以分为单位 "),
     *                                  @SWG\Property( property="store", type="string", example="997", description="商品库存 "),
     *                                  @SWG\Property( property="market_price", type="string", example="1411200000", description="原价,单位为‘分’"),
     *                                  @SWG\Property( property="brief", type="string", example="暗夜绿色 移动联通电信4G手机 双卡双待", description=" 简洁的描述 "),
     *                                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品 "),
     *                                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                                  @SWG\Property( property="item_name", type="string", example="这是一个非常的iphone 11", description="商品名称 "),
     *                                  @SWG\Property( property="pics", type="string", example="http://bbctest.aixue7.com/1/2020/03/05/...", description=" 商品图片 "),
     *                                  @SWG\Property( property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                                  @SWG\Property( property="parent_id", type="string", example="0", description="父级id, 0为顶级 "),
     *                                  @SWG\Property( property="goods_id", type="string", example="1070", description=" 产品ID "),
     *                                  @SWG\Property( property="user_id", type="string", example="112222", description="用户id "),
     *                                  @SWG\Property( property="item_category", type="string", example="641", description="商品主类目"),
     *                                  @SWG\Property( property="type", type="string", example="0", description="类型"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                                  @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                                  @SWG\Property( property="origincountry_name", type="string", example="", description="产地国名称"),
     *                                  @SWG\Property( property="origincountry_img_url", type="string", example="", description="产地国国旗"),
     *                                  @SWG\Property( property="full_gift_id", type="array",
     *                                      @SWG\Items( type="string", example="187", description="赠品ID"),
     *                                  ),
     *                                  @SWG\Property( property="promotions", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="marketing_id", type="string", example="188", description="促销id "),
     *                                          @SWG\Property( property="marketing_type", type="string", example="full_minus", description="促销类型 | 营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购 | 营销类型: full_discount:满折,full_minus:满减,full_gift:满赠"),
     *                                          @SWG\Property( property="rel_marketing_id", type="string", example="0", description="关联其他营销id"),
     *                                          @SWG\Property( property="marketing_name", type="string", example="test", description="营销活动名称"),
     *                                          @SWG\Property( property="marketing_desc", type="string", example="test", description="营销活动描述"),
     *                                          @SWG\Property( property="start_time", type="string", example="1612108800", description="权益开始时间 "),
     *                                          @SWG\Property( property="end_time", type="string", example="1612281599", description="活动结束时间"),
     *                                          @SWG\Property( property="release_time", type="string", example="null", description="活动发布时间"),
     *                                          @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端 | 0 商家全场可用|1 只能用于pc|2 只能用于wap|3 只能用于app, 使用平台"),
     *                                          @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用 | 适用范围: 1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                                          @SWG\Property( property="use_shop", type="string", example="0", description="适用店铺: 0:全场可用,1:指定店铺可用"),
     *                                          @SWG\Property( property="shop_ids", type="array",
     *                                              @SWG\Items( type="string", example="1", description="适用店铺ID"),
     *                                          ),
     *                                          @SWG\Property( property="valid_grade", type="string", example="null", description="会员级别集合"),
     *                                          @SWG\Property( property="condition_type", type="string", example="quantity", description="营销条件标准 quantity:按总件数, totalfee:按总金额"),
     *                                          @SWG\Property( property="condition_value", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="full", type="string", example="1", description="满额条件"),
     *                                                  @SWG\Property( property="minus", type="string", example="0.01", description="折扣"),
     *                                               ),
     *                                          ),
     *                                          @SWG\Property( property="canjoin_repeat", type="string", example="1", description="是否上不封顶"),
     *                                          @SWG\Property( property="join_limit", type="string", example="0", description="可参与次数"),
     *                                          @SWG\Property( property="free_postage", type="string", example="0", description="是否免邮 | 0 包邮|1 商品"),
     *                                          @SWG\Property( property="promotion_tag", type="string", example="满减", description="促销标签"),
     *                                          @SWG\Property( property="check_status", type="string", example="agree", description="促销状态: non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
     *                                          @SWG\Property( property="reason", type="string", example="null", description="原因 "),
     *                                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品 "),
     *                                          @SWG\Property( property="is_increase_purchase", type="string", example="null", description="开启加价购，满赠时启用"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description=" 公司id，company_id为0的时候表示通用"),
     *                                          @SWG\Property( property="created", type="string", example="1612158539", description="创建时间"),
     *                                          @SWG\Property( property="updated", type="string", example="1612158539", description="  修改时间"),
     *                                          @SWG\Property( property="ad_pic", type="string", example="null", description=" 活动广告图 "),
     *                                          @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                                          @SWG\Property( property="brand_ids", type="string", example="null", description="品牌id集合"),
     *                                          @SWG\Property( property="in_proportion", type="string", example="0", description="是否按比例多次赠送"),
     *                                          @SWG\Property( property="usedCount", type="string", example="0", description="使用次数"),
     *                                          @SWG\Property( property="condition_rules", type="string", example="购买满1件，减0.01元;", description="规则描述"),
     *                                          @SWG\Property( property="start_date", type="string", example="2021-02-01 00:00:00", description="开始时间"),
     *                                          @SWG\Property( property="end_date", type="string", example="2021-02-02 23:59:59", description="有效期结束时间"),
     *                                          @SWG\Property( property="status", type="string", example="ongoing", description="状态"),
     *                                          @SWG\Property( property="last_seconds", type="string", example="123053", description="倒计时"),
     *                                       ),
     *                                  ),
     *                                  @SWG\Property( property="activity_id", type="string", example="188", description="活动ID  "),
     *                                  @SWG\Property( property="activity_info", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="type", type="string", example="full_minus", description="类型"),
     *                                          @SWG\Property( property="id", type="string", example="188", description="活动ID"),
     *                                          @SWG\Property( property="rule", type="string", example="消费满1件，减0.01元,且上不封顶", description="活动规则"),
     *                                          @SWG\Property( property="info", type="string", example="test", description="活动信息"),
     *                                          @SWG\Property( property="discount_fee", type="string", example="9", description="订单优惠金额，以分为单位 "),
     *                                       ),
     *                                  ),
     *                                  @SWG\Property( property="activity_type", type="string", example="full_minus", description="活动类型 | 活动类型 full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,group拼团,seckill秒杀,package打包,limited_time_sale限时特惠"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="used_activity", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="activity_id", type="string", example="188", description="活动ID  "),
     *                                  @SWG\Property( property="activity_name", type="string", example="test", description="活动名称 "),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="used_activity_ids", type="array",
     *                              @SWG\Items( type="string", example="188", description="使用的活动ID"),
     *                          ),
     *                          @SWG\Property( property="activity_grouping", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="cart_ids", type="array",
     *                                      @SWG\Items( type="string", example="203", description="购物车ID"),
     *                                  ),
     *                                  @SWG\Property( property="activity_name", type="string", example="test", description="活动名称 "),
     *                                  @SWG\Property( property="activity_id", type="string", example="188", description="活动ID "),
     *                                  @SWG\Property( property="activity_tag", type="string", example="满减", description="活动标签"),
     *                                  @SWG\Property( property="condition_rules", type="string", example="购买满1件，减0.01元;", description="活动描述"),
     *                                  @SWG\Property( property="discount_fee", type="string", example="9", description="订单优惠金额，以分为单位 "),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="vipgrade_guide_title", type="object",
     *                                  @SWG\Property( property="guide_title_desc", type="string", example="", description="开通vip的引导文本"),
     *                          ),
     *                          @SWG\Property( property="gift_activity", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="discount_desc", type="object",
     *                                          @SWG\Property( property="type", type="string", example="full_gift", description="促销类型"),
     *                                          @SWG\Property( property="id", type="string", example="187", description="促销活动ID"),
     *                                          @SWG\Property( property="rule", type="string", example="消费满1件，送赠品：测试雷诺级 x 1；", description="促销规则"),
     *                                          @SWG\Property( property="info", type="string", example="test", description="促销规则描述"),
     *                                          @SWG\Property( property="discount_fee", type="string", example="100", description="订单优惠金额，以分为单位 "),
     *                                          @SWG\Property( property="max_limit", type="string", example="1", description="可参与次数"),
     *                                  ),
     *                                  @SWG\Property( property="activity_id", type="string", example="187", description="活动ID "),
     *                                  @SWG\Property( property="gifts", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="196", description="ID"),
     *                                          @SWG\Property( property="marketing_id", type="string", example="187", description="促销id "),
     *                                          @SWG\Property( property="item_id", type="string", example="5472", description="商品id "),
     *                                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品 "),
     *                                          @SWG\Property( property="item_name", type="string", example="测试雷诺级", description="商品名称 "),
     *                                          @SWG\Property( property="price", type="string", example="100", description="价格,单位为‘分’ "),
     *                                          @SWG\Property( property="store", type="string", example="111", description="商品库存 "),
     *                                          @SWG\Property( property="gift_num", type="string", example="1", description="赠品数量"),
     *                                          @SWG\Property( property="pics", type="array",
     *                                              @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_png/...", description="图片url"),
     *                                          ),
     *                                          @SWG\Property( property="without_return", type="string", example="false", description="退货无需退回赠品"),
     *                                          @SWG\Property( property="filter_full", type="string", example="null", description="赠品满足所需条件"),
     *                                          @SWG\Property( property="item_spec_desc", type="string", example="12:13111111", description=" 商品规格描述"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id，company_id为0的时候表示通用"),
     *                                          @SWG\Property( property="created", type="string", example="1611889475", description="创建时间"),
     *                                          @SWG\Property( property="updated", type="string", example="1611913262", description=" 修改时间"),
     *                                          @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                                          @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                                          @SWG\Property( property="barcode", type="string", example="314", description="商品条形码"),
     *                                          @SWG\Property( property="sales", type="string", example="1", description=" 销售额"),
     *                                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                                          @SWG\Property( property="rebate", type="string", example="0", description="返佣金额,单位为‘分’"),
     *                                          @SWG\Property( property="rebate_conf", type="array",
     *                                              @SWG\Items( type="string", example="", description="返佣设置"),
     *                                          ),
     *                                          @SWG\Property( property="cost_price", type="string", example="100", description="价格,单位为‘分’"),
     *                                          @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                                          @SWG\Property( property="point", type="string", example="0", description="消费积分 "),
     *                                          @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                                          @SWG\Property( property="goods_id", type="string", example="5470", description=" 商品id"),
     *                                          @SWG\Property( property="brand_id", type="string", example="1228", description="品牌id"),
     *                                          @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                                          @SWG\Property( property="item_bn", type="string", example="26", description="商品编码 "),
     *                                          @SWG\Property( property="brief", type="string", example="", description=" 简洁的描述 "),
     *                                          @SWG\Property( property="market_price", type="string", example="200", description="原价,单位为‘分’"),
     *                                          @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                                          @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                                          @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                                          @SWG\Property( property="volume", type="string", example="4", description="商品体积"),
     *                                          @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                                          @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                                          @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                                          @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                                          @SWG\Property( property="regions_id", type="string", example="null", description="产地地区id"),
     *                                          @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                                          @SWG\Property( property="sort", type="string", example="0", description=" 排序 "),
     *                                          @SWG\Property( property="templates_id", type="string", example="105", description="运费模板id"),
     *                                          @SWG\Property( property="is_default", type="string", example="false", description=" 商品是否为默认商品 "),
     *                                          @SWG\Property( property="nospec", type="string", example="false", description="商品是否为单规格"),
     *                                          @SWG\Property( property="default_item_id", type="string", example="5470", description="默认商品ID | 默认商品id"),
     *                                          @SWG\Property( property="distributor_id", type="string", example="0", description="门店id, 0是所有门店"),
     *                                          @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                                          @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后 | 有效期的类型 | 有效期的类型, DATE_TYPE_FIX_TIME_RANGE: 指定日期范围内，DATE_TYPE_FIX_TERM:固定天数后"),
     *                                          @SWG\Property( property="item_category", type="string", example="1812", description="商品主类目"),
     *                                          @SWG\Property( property="rebate_type", type="string", example="default", description="分佣计算方式 | 返佣模式"),
     *                                          @SWG\Property( property="weight", type="string", example="3", description="商品重量"),
     *                                          @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                                          @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间 "),
     *                                          @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                                          @SWG\Property( property="tax_rate", type="string", example="0", description=" 税率, 百分之～/100 "),
     *                                          @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                                          @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                                          @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                                          @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝 | 跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝 | 审核状态，其中0为审核成功，1为审核失败，2为审核中, 3为待提交审核"),
     *                                          @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                                          @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                                          @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                                          @SWG\Property( property="profit_type", type="string", example="0", description="1 拉新分润 2 推广提成 3 货款 4 补贴 | 分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额) | 分佣计算方式 | 分佣计算方式 0商品不设置默认分润,1按照比例分润,2按照填写金额分润 | 分润类型 1 总部分润 2 自营门店分润 3 加盟门店分润"),
     *                                          @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                                          @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                          @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                                          @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                                          @SWG\Property( property="taxation_num", type="string", example="1", description="计税单位份数"),
     *                                          @SWG\Property( property="type", type="string", example="0", description="类型"),
     *                                          @SWG\Property( property="tdk_content", type="string", example="{title:,mate_description:,mate_keywords:}", description="tdk详情"),
     *                                          @SWG\Property( property="itemId", type="string", example="5472", description="商品ID"),
     *                                          @SWG\Property( property="consumeType", type="string", example="every", description="核销类型"),
     *                                          @SWG\Property( property="itemName", type="string", example="测试雷诺级", description="商品名称"),
     *                                          @SWG\Property( property="itemBn", type="string", example="26", description="货号"),
     *                                          @SWG\Property( property="companyId", type="string", example="1", description="公司ID"),
     *                                          @SWG\Property( property="item_main_cat_id", type="string", example="1812", description="主类目ID"),
     *                                          @SWG\Property( property="type_labels", type="array",
     *                                              @SWG\Items( type="string", example="undefined", description="标签"),
     *                                          ),
     *                                          @SWG\Property( property="item_spec", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="item_id", type="string", example="5472", description="商品id "),
     *                                                  @SWG\Property( property="spec_id", type="string", example="1403", description="规格ID"),
     *                                                  @SWG\Property( property="spec_value_id", type="string", example="2334", description="规格值ID"),
     *                                                  @SWG\Property( property="spec_name", type="string", example="颜色", description="规格名称"),
     *                                                  @SWG\Property( property="spec_custom_value_name", type="string", example="玫瑰红", description="自定义规格值"),
     *                                                  @SWG\Property( property="spec_value_name", type="string", example="红色", description="规格值"),
     *                                                  @SWG\Property( property="item_image_url", type="array",
     *                                                      @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/...", description="图片url"),
     *                                                  ),
     *                                                  @SWG\Property( property="spec_image_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/...", description="规格图片url"),
     *                                               ),
     *                                          ),
     *                                       ),
     *                                  ),
     *                                  @SWG\Property( property="discount_fee", type="string", example="100", description="订单优惠金额，以分为单位 "),
     *                                  @SWG\Property( property="activity_item_ids", type="array",
     *                                      @SWG\Items( type="string", example="1070", description="活动商品ID"),
     *                                  ),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="plus_buy_activity", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="discount_desc", type="object",
     *                                          @SWG\Property( property="type", type="string", example="plus_price_buy", description="活动类型"),
     *                                          @SWG\Property( property="id", type="string", example="186", description="活动ID"),
     *                                          @SWG\Property( property="rule", type="string", example="消费满1件，加价1.00元换购商品", description="活动规则"),
     *                                          @SWG\Property( property="info", type="string", example="10086", description="活动介绍"),
     *                                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位 "),
     *                                          @SWG\Property( property="max_limit", type="string", example="10", description="可参与次数"),
     *                                          @SWG\Property( property="plus_price", type="string", example="1.00", description="加价购金额"),
     *                                  ),
     *                                  @SWG\Property( property="activity_id", type="string", example="186", description="活动ID  "),
     *                                  @SWG\Property( property="plus_buy_items", type="object",
     *                                          @SWG\Property( property="5445", type="object",
     *                                                  @SWG\Property( property="id", type="string", example="195", description="商品ID"),
     *                                                  @SWG\Property( property="marketing_id", type="string", example="186", description="促销id "),
     *                                                  @SWG\Property( property="item_id", type="string", example="5445", description="商品id "),
     *                                                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品 "),
     *                                                  @SWG\Property( property="item_name", type="string", example="111", description="商品名称 | 商品标题 | 订单标题"),
     *                                                  @SWG\Property( property="price", type="string", example="100", description="商品价格 | 价格,单位为‘分’ "),
     *                                                  @SWG\Property( property="store", type="string", example="1", description="商品库存 "),
     *                                                  @SWG\Property( property="gift_num", type="string", example="1", description="赠品数量"),
     *                                                  @SWG\Property( property="pics", type="array",
     *                                                      @SWG\Items( type="string", example="https://bbctest.aixue7.com/image/1/2021/01/19/...", description="图片url"),
     *                                                  ),
     *                                                  @SWG\Property( property="without_return", type="string", example="false", description="退货无需退回赠品"),
     *                                                  @SWG\Property( property="filter_full", type="string", example="null", description="赠品满足所需条件"),
     *                                                  @SWG\Property( property="item_spec_desc", type="string", example="null", description="产品规格描述 | 商品规格描述"),
     *                                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id | 公司ID |  | 企业id | 商户id | 企业ID | 公司company id | company_id | 公司_ID | 公司Id | 公司 company id | 企业company id | 商家id | 公司id，company_id为0的时候表示通用"),
     *                                                  @SWG\Property( property="created", type="string", example="1611474150", description=""),
     *                                                  @SWG\Property( property="updated", type="string", example="1611474150", description=" | 修改时间"),
     *                                                  @SWG\Property( property="plus_price", type="string", example="100", description="加价购金额"),
     *                                                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                                                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                                                  @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                                                  @SWG\Property( property="sales", type="string", example="null", description="商品销量 | 销量 | 销售额"),
     *                                                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                                                  @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位 | 推广商品 1已选择 0未选择 2申请加入 3拒绝 | 返佣金额,单位为‘分’"),
     *                                                  @SWG\Property( property="rebate_conf", type="array",
     *                                                      @SWG\Items( type="string", example="undefined", description="返佣设置"),
     *                                                  ),
     *                                                  @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                                                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                                                  @SWG\Property( property="point", type="string", example="0", description="提现积分 | 积分个数 | 积分 | 消费积分 | 积分兑换价格 | 商品总积分"),
     *                                                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                                                  @SWG\Property( property="goods_id", type="string", example="5445", description="商品集合ID | 产品ID | 商品ID | 关联商品id | 商品id"),
     *                                                  @SWG\Property( property="brand_id", type="string", example="1401", description="品牌id"),
     *                                                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                                                  @SWG\Property( property="item_bn", type="string", example="S600D24E6D5660", description="商品编码 | 商品编号"),
     *                                                  @SWG\Property( property="brief", type="string", example="111", description="图片简介 | 简洁的描述 | 推广店铺描述"),
     *                                                  @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                                                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                                                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                                                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                                                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                                                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                                                  @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                                                  @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                                                  @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                                                  @SWG\Property( property="regions_id", type="string", example="null", description="地区id(DC2Type:json_array) | 地区编号集合(DC2Type:json_array) | 国家行政区划编码组合，逗号隔开 |  | 产地地区id"),
     *                                                  @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                                                  @SWG\Property( property="sort", type="string", example="0", description="商品排序 | 文章排序 | 排序 | 商品属性排序，越大越在前 | 排序，数字越大越靠前"),
     *                                                  @SWG\Property( property="templates_id", type="string", example="105", description="运费模板id"),
     *                                                  @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币 | 门店id | 默认地址, 1:是。2:不是 | 商品是否为默认商品 | 购买引导文本 | 是否是默认门店"),
     *                                                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                                                  @SWG\Property( property="default_item_id", type="string", example="5445", description="默认商品ID | 默认商品id"),
     *                                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id | 店铺id | 经销商ID |  | 上线店铺ID | 关联店铺 | 店铺id,为0时表示该商品为商城商品，否则为店铺自有商品 | 店铺ID | 拉新门店id | 店铺ID, 多个逗号隔开 | 门店id, 0是所有门店 | 门店id | 门店ID | 门店所属店铺ID"),
     *                                                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                                                  @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后 | 有效期的类型 | 有效期的类型, DATE_TYPE_FIX_TIME_RANGE: 指定日期范围内，DATE_TYPE_FIX_TERM:固定天数后"),
     *                                                  @SWG\Property( property="item_category", type="string", example="1733", description="商品主类目"),
     *                                                  @SWG\Property( property="rebate_type", type="string", example="default", description="分佣计算方式 | 返佣模式"),
     *                                                  @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                                                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                                                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间 | 会员到期时间"),
     *                                                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                                                  @SWG\Property( property="tax_rate", type="string", example="0", description="税率 | 税率, 百分之～/100 | 商品税率"),
     *                                                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                                                  @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                                                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                                                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝 | 跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝 | 审核状态，其中0为审核成功，1为审核失败，2为审核中, 3为待提交审核"),
     *                                                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                                                  @SWG\Property( property="is_gift", type="string", example="true", description="是否为赠品"),
     *                                                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                                                  @SWG\Property( property="profit_type", type="string", example="0", description="1 拉新分润 2 推广提成 3 货款 4 补贴 | 分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额) | 分佣计算方式 | 分佣计算方式 0商品不设置默认分润,1按照比例分润,2按照填写金额分润 | 分润类型 1 总部分润 2 自营门店分润 3 加盟门店分润"),
     *                                                  @SWG\Property( property="profit_fee", type="string", example="50", description="分润金额,单位为分 冗余字段"),
     *                                                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                                                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                                                  @SWG\Property( property="taxation_num", type="string", example="1", description="计税单位份数"),
     *                                                  @SWG\Property( property="type", type="string", example="0", description="打印机类型 yilianyun 易连云 | type 1全部数据 2平台数据 3非平台数据 | 商品类型，0普通，1跨境商品，可扩展 | 订单类型，0普通订单,1跨境订单,....其他 | 营销类型: shop:店铺额外积分,birthday:会员生日,item:商品额外积分 | 公告类型。可选值有 notice-公告;helper-店主助手"),
     *                                                  @SWG\Property( property="tdk_content", type="string", example="{title:,mate_description:,mate_keywords:}", description="tdk详情"),
     *                                                  @SWG\Property( property="itemId", type="string", example="5472", description="商品ID"),
     *                                                  @SWG\Property( property="consumeType", type="string", example="every", description="核销类型"),
     *                                                  @SWG\Property( property="itemName", type="string", example="测试雷诺级", description="商品名称"),
     *                                                  @SWG\Property( property="itemBn", type="string", example="26", description="货号"),
     *                                                  @SWG\Property( property="companyId", type="string", example="1", description="公司ID"),
     *                                                  @SWG\Property( property="item_main_cat_id", type="string", example="1812", description="主类目ID"),
     *                                                  @SWG\Property( property="type_labels", type="array",
     *                                                      @SWG\Items( type="string", example="undefined", description="标签"),
     *                                                  ),
     *                                          ),
     *                                  ),
     *                                  @SWG\Property( property="activity_item_ids", type="array",
     *                                      @SWG\Items( type="string", example="5472", description="活动商品ID"),
     *                                  ),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="invalid_cart", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="cart_id", type="string", example="360", description="购物车ID | ID"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员id | 导购id | 导购员 | 导购员ID | 店铺管理员id | 门店人员ID"),
     *                          @SWG\Property( property="item_id", type="string", example="5411", description="商品id | 商品ID | 商品 | 筛选id(按item_type区分为商品ID,标签ID等) | 关联货品id | 限购活动商品id | 活动商品id | 关联商品id | 秒杀活动商品id | 评论id"),
     *                          @SWG\Property( property="package_items", type="string", example="", description="关联商品id集合"),
     *                          @SWG\Property( property="num", type="string", example="1", description="售后数量 | 社区人数 | 限购参加活动次数 | 商品数量 | 购买商品数量 | 数值属性值，例如买了50个次卡，就是填50；赠送了10个经验，就是填10 | 发货数量 | 销售数量 | 预约数量"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id | 公司ID |  | 企业id | 商户id | 企业ID | 公司company id | company_id | 公司_ID | 公司Id | 公司 company id | 企业company id | 商家id | 公司id，company_id为0的时候表示通用"),
     *                          @SWG\Property( property="is_checked", type="string", example="0", description="购物车是否选中"),
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="分销商id | 店铺id | 经销商ID |  | 上线店铺ID | 关联店铺 | 店铺id,为0时表示该商品为商城商品，否则为店铺自有商品 | 店铺ID | 拉新门店id | 店铺ID, 多个逗号隔开 | 门店id, 0是所有门店 | 门店id | 门店ID | 门店所属店铺ID"),
     *                          @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="门店id | 店铺id | 门店ID | 店铺ID，如果为0则表示总部 |  | 店铺id 或者 社区id | 公司门店 id | 公司id"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="is_check_store", type="string", example="true", description="是否检查过库存"),
     * )
     */
}
