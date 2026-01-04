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

namespace GoodsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsMedicineService;
use GoodsBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemTaxRateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GoodsBundle\Services\ItemsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorItemsService;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\KaquanService;
use OrdersBundle\Entities\Cart;
use PointBundle\Services\PointMemberRuleService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use CompanysBundle\Traits\GetDefaultCur;
use PopularizeBundle\Services\SettingService;
use MembersBundle\Services\MemberItemsFavService;
use PromotionsBundle\Services\MemberPriceService;
use PopularizeBundle\Services\PromoterGoodsService;
use GoodsBundle\Services\KeywordsService;
use TdksetBundle\Services\TdkGivenService;
use DistributionBundle\Services\DistributorService;
use SalespersonBundle\Services\SalespersonService;

class Items extends BaseController
{
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/items/{item_id}",
     *     summary="获取商品详情",
     *     tags={"商品"},
     *     description="获取商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
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
     *                   @SWG\Property(property="company_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsDetail($item_id, request $request)
    {
        $authInfo = $request->get('auth');
        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_detail_error'), $validator->errors());
        }

        //如果有分销商id。则判断分销商是否关联该商品
        $company_id = $authInfo['company_id'];
        $distributorItems = [];
        $woa_appid = $authInfo['woa_appid'];
        if ($request->input('distributor_id')) {
            $distributor_id = $request->input('distributor_id');
            $distributorItemsService = new DistributorItemsService();
            $result = $distributorItemsService->getValidDistributorItemInfo($company_id, $item_id, $distributor_id, $woa_appid);
            if (!$result) {
                $itemsService = new ItemsService();
                $result = $itemsService->getItemsDetail($item_id, $woa_appid);
                // 店铺不可销售
                $result['distributor_sale_status'] = true;
            } else {
                // 店铺可销售
                $result['distributor_sale_status'] = false;
            }
        } else {
            $itemsService = new ItemsService();
            $result = $itemsService->getItemsDetail($item_id, $woa_appid);
            // 如果没有店铺ID，可以销售
            $result['distributor_sale_status'] = false;
        }

        // if($result['is_market'] == 0){
        //     $result['approve_status'] = 'instock';
        // }
        if ($company_id != $result['company_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_info_error'));
        }

        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result['group_activity'] = $promotionGroupsActivityService->getGroupBeginInfoByItemsId($item_id, time(), time()) ?: null;
        $result['groups_list'] = null;
        if (isset($result['group_activity']['rig_up']) && true == $result['group_activity']['rig_up']) {
            $promotionGroupsTeamService = new PromotionGroupsTeamService();
            $filter = [
                'p.act_id' => $result['group_activity']['groups_activity_id'],
                'p.company_id' => $authInfo['company_id'],
                'p.team_status' => 1,
                'p.disabled' => false,
            ];
            $result['groups_list'] = $promotionGroupsTeamService->getGroupsTeamByItems($filter, 1, 4);
        }
        if ($result['group_activity']) {
            $result['group_activity']['pics'] = $result['pics'];
            $result['group_activity']['item_name'] = $result['item_name'];
            $result['group_activity']['brief'] = $result['brief'];
            $result['group_activity']['price'] = $result['price'];
        }

        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $result['seckill_activity'] = $promotionSeckillActivityService->getSeckillInfoByItemsId($company_id, $item_id);
        if ($result['seckill_activity']) {
            $result['seckill_activity']['pics'] = $result['pics'];
            $result['seckill_activity']['item_name'] = $result['item_name'];
            $result['seckill_activity']['brief'] = $result['brief'];
            $result['seckill_activity']['price'] = $result['price'];
            $result['seckill_activity']['store'] = $result['store'];
            $result['seckill_activity']['act_price'] = $result['seckill_activity']['activity_price'];
        }
        //不知作用的代码，暂时注释
        //$result['intro_url'] = app('Dingo\Api\Routing\UrlGenerator')->version('v1')->action('GoodsBundle\Http\FrontApi\V1\Action\Items@getItemsIntro', $item_id);

        //获取系统货币默认配置
        $result['cur'] = $this->getCur($company_id);

        $settingService = new SettingService();
        $config = $settingService->getConfig($result['company_id']);
        if ($config['popularize_ratio']['type'] == 'profit') {
            $ratio = $config['popularize_ratio']['profit']['first_level']['ratio'];
            $result['promoter_price'] = bcdiv(bcmul(bcsub($result['price'], $result['cost_price']), $ratio), 100, 2);
            $result['promoter_price'] = ($result['promoter_price'] > 0) ? $result['promoter_price'] : 0;
        } else {
            $ratio = $config['popularize_ratio']['order_money']['first_level']['ratio'];
            $result['promoter_price'] = bcdiv(bcmul($result['price'], $ratio), 100);
        }

        if ($result['seckill_activity'] && !$result['seckill_activity']['is_activity_rebate']) {
            $result['promoter_price'] = 0;
        }
        if ($result['group_activity']) {
            $result['promoter_price'] = 0;
        }

        // 获取药品数据
        $result = (new ItemsMedicineService())->getItemsMedicineData([$result])[0];
        foreach ($result['spec_items'] as $idx => $sp){
            usort($sp['item_spec'], function ($a, $b) {
                return $b['spec_id'] <=> $a['spec_id'];
            });
            $result['spec_items'][$idx] = $sp;
        }
        $result['itemName'] = $result['item_name'];

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/goods/shopitems",
     *     summary="获取小店上架商品列表",
     *     tags={"商品"},
     *     description="获取小店上架商品列表",
     *     operationId="getShopItemsList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面,获取商品列表的初始偏移位置，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="pageSize", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="keywords", description="搜索关键字" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shop_user_id", description="userId" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="goodsSort", description="排序方式" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_id", description="分类ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="4"),
     *                          @SWG\Property( property="item_type", type="string", example="normal"),
     *                          @SWG\Property( property="consume_type", type="string", example="every"),
     *                          @SWG\Property( property="is_show_specimg", type="string", example="false"),
     *                          @SWG\Property( property="store", type="string", example="11"),
     *                          @SWG\Property( property="barcode", type="string", example="123312"),
     *                          @SWG\Property( property="sales", type="string", example="null"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale"),
     *                          @SWG\Property( property="rebate", type="string", example="1"),
     *                          @SWG\Property( property="rebate_conf", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="cost_price", type="string", example="0"),
     *                          @SWG\Property( property="is_point", type="string", example="null"),
     *                          @SWG\Property( property="point", type="string", example="0"),
     *                          @SWG\Property( property="item_source", type="string", example="mall"),
     *                          @SWG\Property( property="goods_id", type="string", example="4"),
     *                          @SWG\Property( property="brand_id", type="string", example="2"),
     *                          @SWG\Property( property="item_name", type="string", example="12"),
     *                          @SWG\Property( property="item_unit", type="string", example="12"),
     *                          @SWG\Property( property="item_bn", type="string", example="S600A68874169B"),
     *                          @SWG\Property( property="brief", type="string", example="222"),
     *                          @SWG\Property( property="price", type="string", example="1"),
     *                          @SWG\Property( property="market_price", type="string", example="0"),
     *                          @SWG\Property( property="special_type", type="string", example="normal"),
     *                          @SWG\Property( property="goods_function", type="string", example="null"),
     *                          @SWG\Property( property="goods_series", type="string", example="null"),
     *                          @SWG\Property( property="volume", type="string", example="null"),
     *                          @SWG\Property( property="goods_color", type="string", example="null"),
     *                          @SWG\Property( property="goods_brand", type="string", example="null"),
     *                          @SWG\Property( property="item_address_province", type="string", example=""),
     *                          @SWG\Property( property="item_address_city", type="string", example=""),
     *                          @SWG\Property( property="regions_id", type="string", example="null"),
     *                          @SWG\Property( property="brand_logo", type="string", example="null"),
     *                          @SWG\Property( property="sort", type="string", example="0"),
     *                          @SWG\Property( property="templates_id", type="string", example="1"),
     *                          @SWG\Property( property="is_default", type="string", example="true"),
     *                          @SWG\Property( property="nospec", type="string", example="true"),
     *                          @SWG\Property( property="default_item_id", type="string", example="1"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="/storage/image/5/2021/01/22/806f237f814cf392b393bde2af858212QQ20210111-162931.png"),
     *                          ),
     *                          @SWG\Property( property="distributor_id", type="string", example="0"),
     *                          @SWG\Property( property="company_id", type="string", example="1"),
     *                          @SWG\Property( property="enable_agreement", type="string", example="false"),
     *                          @SWG\Property( property="date_type", type="string", example=""),
     *                          @SWG\Property( property="item_category", type="string", example="56"),
     *                          @SWG\Property( property="rebate_type", type="string", example="default"),
     *                          @SWG\Property( property="weight", type="string", example="0"),
     *                          @SWG\Property( property="begin_date", type="string", example="0"),
     *                          @SWG\Property( property="end_date", type="string", example="0"),
     *                          @SWG\Property( property="fixed_term", type="string", example="0"),
     *                          @SWG\Property( property="tax_rate", type="string", example="0"),
     *                          @SWG\Property( property="created", type="string", example="1611294855"),
     *                          @SWG\Property( property="updated", type="string", example="1611294959"),
     *                          @SWG\Property( property="video_type", type="string", example="local"),
     *                          @SWG\Property( property="videos", type="string", example="null"),
     *                          @SWG\Property( property="video_pic_url", type="string", example="null"),
     *                          @SWG\Property( property="audit_status", type="string", example="approved"),
     *                          @SWG\Property( property="audit_reason", type="string", example="null"),
     *                          @SWG\Property( property="is_gift", type="string", example="false"),
     *                          @SWG\Property( property="is_package", type="string", example="false"),
     *                          @SWG\Property( property="profit_type", type="string", example="0"),
     *                          @SWG\Property( property="profit_fee", type="string", example="0"),
     *                          @SWG\Property( property="is_profit", type="string", example="true"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example=""),
     *                          @SWG\Property( property="origincountry_id", type="string", example="0"),
     *                          @SWG\Property( property="taxstrategy_id", type="string", example="0"),
     *                          @SWG\Property( property="taxation_num", type="string", example="0"),
     *                          @SWG\Property( property="type", type="string", example="0"),
     *                          @SWG\Property( property="tdk_content", type="string", example=""),
     *                          @SWG\Property( property="itemId", type="string", example="4"),
     *                          @SWG\Property( property="consumeType", type="string", example="every"),
     *                          @SWG\Property( property="itemName", type="string", example="12"),
     *                          @SWG\Property( property="itemBn", type="string", example="S600A68874169B"),
     *                          @SWG\Property( property="companyId", type="string", example="1"),
     *                          @SWG\Property( property="item_main_cat_id", type="string", example="56"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones")))
     * )
     */
    public function getShopItemsList(Request $request)
    {
        $authInfo = $request->get('auth');

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $shopUserId = $request->input('shop_user_id', '');
        $categoryId = intval($request->input('category_id', 0));
        $isSalesmanPage = intval($request->input('isSalesmanPage', 0));

        $filter['user_id'] = $shopUserId ? $shopUserId : $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];

        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);
        if ($config['goods'] == 'all') {
            $filter['is_all_goods'] = true;
        }

        $promoterGoodsService = new PromoterGoodsService();
        $list = $promoterGoodsService->lists($filter);
        if (!$list['total_count']) {
            return $this->response->array(['total_count' => 0, 'list' => []]);
        }

        if ($request->input('keywords')) {
            $params['item_name|contains'] = trim($request->input('keywords'));
        }

        if ($request->input('distributor_id')) {
            $params['distributor_id'] = $request->input('distributor_id');
        }
        if( isset($isSalesmanPage) &&   $isSalesmanPage == 1 )  {
            // $shopUserId
            $salespersonService = new SalespersonService();
            $filter_sales['company_id'] = $authInfo['company_id'];
            $filter_sales['user_id']    = $shopUserId;
            $filter_sales['is_valid']   = 'true';

            $listdata = $salespersonService->getSalespersonList($filter_sales, 1, 500);
            $dIds = array_column($listdata['list'],'shop_id');
            // foreach($dIds as $kd => $vd){

            // }
            $distributorService = new DistributorService();
            $orderBy = [
                "distributor_id" => "DESC"
            ];
            $d_filter = [
                'is_open_salesman' => 1,
                'distributor_id'=>$dIds ,
                'company_id'=>$authInfo['company_id'],
            ];
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:dids:". json_encode($d_filter));

            if(isset($params['distributor_id']) && $params['distributor_id'] ){
                if(in_array($params['distributor_id'],$dIds)  ){
                    $d_filter['distributor_id'] =  $params['distributor_id'];
                }else{
                    $d_filter['distributor_id'] = false;
                }
            }
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:dids:". json_encode($d_filter));

            if(!$d_filter['distributor_id']){
                $result = array('total_count'=> 0 ,'list'=>array(),'nosalestore'=> 1);
                return $this->response->array($result);
            }
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:distributorid:". json_encode($d_filter));

            // 查询店铺信息
            $distributorLists = $distributorService->lists($d_filter, $orderBy, 100, 1, false,'distributor_id,distance');
            $dIds = array_column($distributorLists['list'],'distributor_id');
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:distributorid:". json_encode($dIds));

            if(!$dIds){
                $result = array('total_count'=> 0 ,'list'=>array(),'nosalestore'=> 2);
                return $this->response->array($result);
            }
            $params['distributor_id'] = $dIds;

        }



        if ($categoryId) {
            $itemRelCatsParams['company_id'] = $authInfo['company_id'];

            $itemsCategoryService = new ItemsCategoryService();

            // $catsAll = $itemsCategoryService->lists(['company_id'=>$authInfo['company_id'], 'path|startsWith'=>$categoryId.','], ["created" => "DESC"], -1);
            $catsAll = $itemsCategoryService->lists(['company_id' => $authInfo['company_id'], 'path|like' => $categoryId.',%'], ["created" => "DESC"], -1);

            $itemRelCatsParams['category_id'] = [$categoryId];

            foreach ($catsAll['list'] as $cat) {
                array_push($itemRelCatsParams['category_id'], $cat['category_id']);
            }

            $categoryIds = $itemsCategoryService->getItemsCategoryIds($categoryId, $authInfo['company_id']);
            $itemRelCatsParams['category_id'] = $categoryIds;

            $itemsRelCatsService = new ItemsRelCatsService();
            $itemsRelCatsList = $itemsRelCatsService->lists($itemRelCatsParams);
            $params['item_id'] = [];
            for ($i = 0; $i < $itemsRelCatsList['total_count']; $i++) {
                array_push($params['item_id'], $itemsRelCatsList['list'][$i]['item_id']);
            }
        }

        if ($request->input('goodsSort') == 1) {
            $orderBy['sales'] = 'desc';
        } elseif ($request->input('goodsSort') == 2) {
            $orderBy['price'] = 'desc';
        } elseif ($request->input('goodsSort') == 3) {
            $orderBy['price'] = 'asc';
        } elseif ($request->input('goodsSort') == 4) {
            $orderBy['created'] = 'desc';
        } elseif ($request->input('goodsSort') == 5) {
            $orderBy['store'] = 'desc';
        } else {
            $orderBy['sort'] = 'desc';
        }
        $orderBy['item_id'] = 'desc';

        $params['company_id'] = $authInfo['company_id'];
        $params['goods_id'] = [];
        for ($i = 0; $i < count($list['list']); $i++) {
            array_push($params['goods_id'], $list['list'][$i]['goods_id']);
        }

        if ($config['goods'] == 'select') {
            $params['rebate'] = 1;
        }
        $params['approve_status'] = ['onsale', 'only_show'];
        $params['is_default'] = 1;
        $params['goods_id'] = array_values(array_unique($params['goods_id']));
//        dd($params);
        $itemsService = new ItemsService();
        $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);

        $result = $itemsService->getItemsListMemberPrice($result, $authInfo['user_id'], $params['company_id']);

        //营销标签
        $result = $itemsService->getItemsListActityTag($result, $params['company_id']);

        $result['goods_total'] = $result['total_count'];//兼容前端

        $distributorService = new DistributorService();
        $distributorItemsService = new DistributorItemsService();
        foreach ($result['list'] as $key => $item) {
            if ($item['store'] > 0) {
                continue;
            }

            if ($item['distributor_id'] > 0) {
                $item1 = $distributorItemsService->getInfo(['item_id' => $item['goods_id'], 'distributor_id' => $item['distributor_id']]);
                if ($item1 && !$item1['is_total_store']) {
                    $item2 = $distributorItemsService->getList(['goods_id' => $item['goods_id'], 'store|gt' => 0, 'distributor_id' => $item['distributor_id'], 'is_can_sale' => true], '*', 1, 1);
                    if ($item2) {
                        $result['list'][$key]['store'] = $item2[0]['store'];
                    }
                }
                continue;
            }

            $item3 = $itemsService->getLists(['goods_id' => $item['goods_id'], 'store|gt' => 0, 'approve_status' => 'onsale'], '*', 1, 1);
            if ($item3) {
                $result['list'][$key]['store'] = $item3[0]['store'];
            }
        }

        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/wxapp/goods/items",
     *     summary="获取商品列表",
     *     tags={"商品"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer", required=true ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer", required=true ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", type="integer" ),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", type="integer" ),
     *     @SWG\Parameter( name="goods_id", in="query", description="产品ID，多规格商品不同规格的goods_id是一样的", type="integer" ),
     *     @SWG\Parameter( name="promoter_shop_id", in="query", description="分销店铺ID", type="integer" ),
     *     @SWG\Parameter( name="promoter_onsale", in="query", description="是否开启分销", type="boolean" ),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", type="string" ),
     *     @SWG\Parameter( name="keywords", in="query", description="搜索关键词", type="string" ),
     *     @SWG\Parameter( name="main_category", in="query", description="主类目ID", type="integer" ),
     *     @SWG\Parameter( name="tag_id", in="query", description="标签ID", type="integer" ),
     *     @SWG\Parameter( name="item_params[][attribute_id]", in="query", description="商品属性", type="array", items={"type", "integer"}, collectionFormat="multi" ),
     *     @SWG\Parameter( name="item_params[][attribute_value_id]", in="query", description="商品属性值", type="array", items={"type", "integer"}, collectionFormat="multi" ),
     *     @SWG\Parameter( name="regions_id", in="query", description="地区ID,逗号分隔", type="string" ),
     *     @SWG\Parameter( name="start_price", in="query", description="最小金额", type="number" ),
     *     @SWG\Parameter( name="end_price", in="query", description="最大金额", type="number" ),
     *     @SWG\Parameter( name="brand_id", in="query", description="品牌ID", type="integer" ),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型", type="string" ),
     *     @SWG\Parameter( name="goodsSort", in="query", description="排序方式", type="integer" ),
     *     @SWG\Parameter( name="is_default", in="query", description="是只获取否默认商品", type="boolean" ),
     *     @SWG\Parameter( name="is_tdk", in="query", description="是否获取tdk信息，0不获取,1获取", type="number" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="商品总数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="item_id", type="integer", description="商品ID"),
     *                         @SWG\Property(property="item_type", type="string", description="商品类型"),
     *                         @SWG\Property(property="consume_type", type="string", description="核销类型"),
     *                         @SWG\Property(property="is_show_specimg", type="boolean", description="详情页是否显示规格图片"),
     *                         @SWG\Property(property="store", type="integer", description="商品库存"),
     *                         @SWG\Property(property="barcode", type="string", description="条形吗"),
     *                         @SWG\Property(property="sales", type="integer", description="销量"),
     *                         @SWG\Property(property="approve_status", type="string", description="商品状态"),
     *                         @SWG\Property(property="rebate", type="integer", description="推广商品 1已选择 0未选择 2申请加入 3拒绝"),
     *                         @SWG\Property(property="rebate_conf", type="string", description="分佣计算方式"),
     *                         @SWG\Property(property="cost_price", type="integer", description="成本价"),
     *                         @SWG\Property(property="is_point", type="boolean", description="是否积分兑换"),
     *                         @SWG\Property(property="point", type="integer", description="积分个数"),
     *                         @SWG\Property(property="item_source", type="string", description="品来源:mall:主商城，distributor:店铺自有"),
     *                         @SWG\Property(property="goods_id", type="integer", description="产品ID"),
     *                         @SWG\Property(property="item_name", type="string", description="商品名称"),
     *                         @SWG\Property(property="item_unit", type="string", description="商品计量单位"),
     *                         @SWG\Property(property="item_bn", type="string", description="商品编号"),
     *                         @SWG\Property(property="brief", type="string", description="简洁的描述"),
     *                         @SWG\Property(property="price", type="integer", description="销售价"),
     *                         @SWG\Property(property="market_price", type="integer", description="原价"),
     *                         @SWG\Property(property="special_type", type="string", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                         @SWG\Property(property="goods_function", type="string", description="商品功能"),
     *                         @SWG\Property(property="goods_series", type="string", description="商品系列"),
     *                         @SWG\Property(property="volume", type="number", description="商品体积"),
     *                         @SWG\Property(property="goods_color", type="string", description="商品颜色"),
     *                         @SWG\Property(property="goods_brand", type="string", description="商品品牌"),
     *                         @SWG\Property(property="item_address_province", type="string", description="产地省"),
     *                         @SWG\Property(property="item_address_city", type="string", description="产地市"),
     *                         @SWG\Property(property="regions_id", type="string", description="产地地区id"),
     *                         @SWG\Property(property="brand_logo", type="string", description="品牌图片"),
     *                         @SWG\Property(property="sort", type="integer", description="商品排序"),
     *                         @SWG\Property(property="templates_id", type="integer", description="运费模板id"),
     *                         @SWG\Property(property="is_default", type="boolean", description="商品是否为默认商品"),
     *                         @SWG\Property(property="nospec", type="string", description="商品是否为单规格"),
     *                         @SWG\Property(property="default_item_id", type="integer", description="默认商品ID"),
     *                         @SWG\Property(property="pics", type="array", description="图片", @SWG\Items()),
     *                         @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                         @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                         @SWG\Property(property="item_category", type="integer", description="商品主类目"),
     *                         @SWG\Property(property="weight", type="number", description="商品重量"),
     *                         @SWG\Property(property="created", type="integer", description="创建时间"),
     *                         @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                         @SWG\Property(property="videos", type="string", description="视频"),
     *                         @SWG\Property(property="video_pic_url", type="string", description="视频封面图"),
     *                         @SWG\Property(property="origincountry_id", type="integer", description="产地国id"),
     *                         @SWG\Property(property="type", type="integer", description="商品类型，0普通，1跨境商品，可扩展"),
     *                         @SWG\Property(property="itemId", type="integer", description="商品ID"),
     *                         @SWG\Property(property="consumeType", type="string", description="核销类型"),
     *                         @SWG\Property(property="itemName", type="string", description="商品名称"),
     *                         @SWG\Property(property="itemBn", type="string", description="商品编号"),
     *                         @SWG\Property(property="companyId", type="integer", description="公司ID"),
     *                         @SWG\Property(property="item_main_cat_id", type="integer", description="商品主类目"),
     *                         @SWG\Property(property="is_can_sale", type="boolean", description="是否在本店可售"),
     *                         @SWG\Property(property="goods_can_sale", type="boolean", description="商品是否可售，有一个sku可售，那么商品就可售"),
     *                         @SWG\Property(property="promoter_price", type="string", description="预计获得佣金"),
     *                         @SWG\Property(property="promoter_point", type="string", description="预计获得返佣积分"),
     *                         @SWG\Property(property="commission_type", type="string", description="佣金返回形式：money 金额 point 积分"),
     *                         @SWG\Property(
     *                             property="type_labels",
     *                             type="array",
     *                             @SWG\Items(
     *                                 type="object",
     *                                 @SWG\Property(property="itemId", type="integer", description="商品ID"),
     *                                 @SWG\Property(property="labelId", type="integer", description="数值属性ID"),
     *                                 @SWG\Property(property="labelName", type="string", description="数值属性名称"),
     *                                 @SWG\Property(property="numType", type="string", description="会员数值属性类型，plus：加，minux：减，multiple：乘"),
     *                                 @SWG\Property(property="num", type="integer", description="数值属性值，例如买了50个次卡，就是填50；赠送了10个经验，就是填10"),
     *                                 @SWG\Property(property="companyId", type="integer", description="公司ID"),
     *                                 @SWG\Property(property="created", type="integer", description="创建时间"),
     *                                 @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                                 @SWG\Property(property="limitTime", type="integer", description="有效期，例如30天"),
     *                                 @SWG\Property(property="labelPrice", type="integer", description="价格,单位为‘分’"),
     *                                 @SWG\Property(property="isNotLimitNum", type="integer", description="限制核销次数,1:不限制；2:限制"),
     *                             )
     *                         ),
     *                         @SWG\Property(property="origincountry_name", type="string", description="产地国家名称"),
     *                         @SWG\Property(property="origincountry_img_url", type="string", description="产地国家图标"),
     *                         @SWG\Property(
     *                             property="distributor_info",
     *                             type="object",
     *                             @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
     *                             @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                             @SWG\Property(property="mobile", type="string", description="店铺手机号"),
     *                             @SWG\Property(property="address", type="string", description="店铺地址"),
     *                             @SWG\Property(property="name", type="string", description="店铺名称"),
     *                             @SWG\Property(property="created", type="integer", description="创建时间"),
     *                             @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                             @SWG\Property(property="is_valid", type="string", description="店铺是否有效"),
     *                             @SWG\Property(property="province", type="string", description="省"),
     *                             @SWG\Property(property="city", type="string", description="市"),
     *                             @SWG\Property(property="area", type="string", description="区"),
     *                             @SWG\Property(property="regions_id", type="array", description="国家行政区划编码组合", @SWG\Items()),
     *                             @SWG\Property(property="regions", type="array", description="地区名称组合", @SWG\Items()),
     *                             @SWG\Property(property="contact", type="string", description="联系人名称"),
     *                             @SWG\Property(property="child_count", type="integer", description="child_count"),
     *                             @SWG\Property(property="shop_id", type="integer", description="门店id"),
     *                             @SWG\Property(property="is_default", type="integer", description="默认店铺"),
     *                             @SWG\Property(property="is_ziti", type="boolean", description="是否支持自提"),
     *                             @SWG\Property(property="lng", type="string", description="腾讯地图纬度"),
     *                             @SWG\Property(property="lat", type="string", description="腾讯地图经度"),
     *                             @SWG\Property(property="hour", type="string", description="营业时间"),
     *                             @SWG\Property(property="auto_sync_goods", type="boolean", description="自动同步总部商品"),
     *                             @SWG\Property(property="logo", type="string", description="店铺logo"),
     *                             @SWG\Property(property="banner", type="string", description="店铺banner"),
     *                             @SWG\Property(property="is_audit_goods", type="boolean", description="是否审核店铺商品"),
     *                             @SWG\Property(property="is_delivery", type="boolean", description="是否支持配送"),
     *                             @SWG\Property(property="shop_code", type="string", description="店铺号"),
     *                             @SWG\Property(property="review_status", type="boolean", description="入驻审核状态，0未审核，1已审核"),
     *                             @SWG\Property(property="source_from", type="integer", description="店铺来源，1管理端添加，2小程序申请入驻"),
     *                             @SWG\Property(property="distributor_self", type="integer", description="是否是总店配置"),
     *                             @SWG\Property(property="is_distributor", type="boolean", description="是否是主店铺"),
     *                             @SWG\Property(property="contract_phone", type="string", description="其他联系方式"),
     *                             @SWG\Property(property="is_domestic", type="integer", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                             @SWG\Property(property="is_direct_store", type="integer", description="是否为直营店 1:直营店,2:非直营店"),
     *                             @SWG\Property(property="wechat_work_department_id", type="integer", description="企业微信的部门ID"),
     *                             @SWG\Property(property="regionauth_id", type="integer", description="区域id"),
     *                             @SWG\Property(property="is_open", type="string", description="是否开启分账"),
     *                             @SWG\Property(property="rate", type="integer", description="平台服务费率"),
     *                         ),
     *                         @SWG\Property(
     *                             property="select_tags_list",
     *                             type="array",
     *                             @SWG\Items()
     *                         ),
     *                         @SWG\Property(
     *                             property="brand_list",
     *                             type="object",
     *                             @SWG\Property(property="total_count", type="integer", description="品牌总数"),
     *                             @SWG\Property(
     *                                 property="list",
     *                                 type="array",
     *                                 @SWG\Items(
     *                                     type="object",
     *                                     @SWG\Property(property="attribute_id", type="integer", description="商品属性id"),
     *                                     @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                                     @SWG\Property(property="shop_id", type="integer", description="店铺ID，如果为0则表示总部"),
     *                                     @SWG\Property(property="attribute_type", type="string", description="商品属性类型"),
     *                                     @SWG\Property(property="attribute_name", type="string", description="商品属性名称"),
     *                                     @SWG\Property(property="attribute_memo", type="string", description="商品属性备注"),
     *                                     @SWG\Property(property="attribute_sort", type="integer", description="商品属性排序，越大越在前"),
     *                                     @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
     *                                     @SWG\Property(property="is_show", type="integer", description="是否用于筛选"),
     *                                     @SWG\Property(property="is_image", type="integer", description="属性是否需要配置图片"),
     *                                     @SWG\Property(property="image_url", type="string", description="图片"),
     *                                     @SWG\Property(property="created", type="integer", description="创建时间"),
     *                                     @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                                     @SWG\Property(property="attribute_code", type="string", description="oms 规格编码"),
     *                                 ),
     *                             ),
     *                         ),
     *                         @SWG\Property(
     *                             property="cur",
     *                             type="object",
     *                             @SWG\Property(property="id", type="string", description="货币汇率ID"),
     *                             @SWG\Property(property="company_id", type="string", description="公司id"),
     *                             @SWG\Property(property="currency", type="string", description="货币英文缩写"),
     *                             @SWG\Property(property="title", type="string", description="货币描述"),
     *                             @SWG\Property(property="symbol", type="string", description="货币符号"),
     *                             @SWG\Property(property="rate", type="string", description="货币汇率(与人民币)"),
     *                             @SWG\Property(property="is_default", type="string", description="是否默认货币"),
     *                             @SWG\Property(property="use_platform", type="string", description="适用端"),
     *                         ),
     *                        @SWG\Property( property="tdk_data", type="object",
     *                             @SWG\Property( property="title", type="string", example="123ttt,测试w,测试w,测试商城", description="标题"),
     *                             @SWG\Property( property="mate_description", type="string", example="测试w,123ttt,测试w,测试商城", description="描述"),
     *                             @SWG\Property( property="mate_keywords", type="string", example="123ttt,测试w,测试w,测试商城", description="关键字"),
     *                        ),
     *                     )
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsList(Request $request)
    {
        $authInfo = $request->get('auth');

        //验证参数todo
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
//            'distributor_id' => 'sometimes|integer|min:0'
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_list_error'), $validator->errors());
        }

        $params['company_id'] = $authInfo['company_id'];
        // $params['is_market'] = 1;
        if (is_array($request->input('item_id'))) {
            $params['item_id'] = $request->input('item_id');
        }

        if ($request->input('goods_id')) {
            $params['goods_id'] = $request->input('goods_id');
        }

        if ($request->input('promoter_shop_id') && $request->input('promoter_onsale', false)) {
            $promoterGoodsService = new PromoterGoodsService();
            $goodsIds = $promoterGoodsService->lists(['user_id' => $request->input('promoter_shop_id'), 'company_id' => $authInfo['company_id']], 'goods_id');
            if ($goodsIds) {
                $params['goods_id'] = array_column($goodsIds['list'], 'goods_id');
            } else {
                $result['total_count'] = 0;
                $result['list'] = [];
                return $this->response->array($result);
            }
        }
        

        $approveStatusArr = [];
        if (isset($inputData['approve_status'])) {
            app('log')->info("\n".__FUNCTION__."-".__LINE__.":inputData:approve_status:". json_encode($inputData['approve_status']));

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
        app('log')->info("\n".__FUNCTION__."-".__LINE__.":input:approve_status:params:". json_encode($params));

        $params['audit_status'] = 'approved';

        if (isset($inputData['category']) && $inputData['category']) {
            $params['category_id'] = $inputData['category'];
        }

        if ($request->input('keywords')) {
            if (have_special_char($request->input('keywords'))) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.special_char_not_supported'));
            }
            $keywords = trim($request->input('keywords'));
            $params['or'] = [
                'item_name|contains' => $keywords,
                'item_bn|contains' => $keywords,
            ];

            //支持按标签搜索
            $itemsTagsService = new ItemsTagsService();
            $tagIds = $itemsTagsService->getListTags(['tag_name' => $keywords]);
            if ($tagIds['list']) {
                $itemIds = $itemsTagsService->getItemIdsByTagids(['tag_id' => array_column($tagIds['list'], 'tag_id')], 1, 100);
                if ($itemIds) {
                    $params['or']['item_id'] = $itemIds;
                }
            }
        }

        if ($request->input('item_name')) {
            $params['item_name'] = trim($request->input('item_name'));
        }

        if ($request->input('main_category')) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($request->input('main_category'), $params['company_id']);
            $itemCategory[] = intval($request->input('main_category'));
            $params['item_category'] = $itemCategory;
        }

        if ($request->input('tag_id')) {
            $params['tag_id'] = $request->input('tag_id');
        }

        if ($request->input('item_params')) {
            $params['item_params'] = $request->input('item_params');
        }

        if ($request->input('regions_id')) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }

        if ($request->input('start_price', 0)) {
            $params['price|gte'] = $request->input('start_price') * 100;
        }

        if ($request->input('end_price', 0)) {
            $params['price|lte'] = $request->input('end_price') * 100;
        }

        if ($request->input('brand_id', 0)) {
            $params['brand_id'] = $request->input('brand_id');
        }

        if (in_array($request->input('type'), ['0', '1'])) {
            $params['type'] = intval($request->input('type'));
        }

        $params['item_type'] = $request->input('item_type', 'services');

        $distributor_id = $request->input('distributor_id', false);
        $distributor_ids = explode(',',$distributor_id);
        if(count($distributor_ids) > 1){
            $distributor_id = $distributor_ids;
        }
        if ($distributor_id && $distributor_id !== 'false') {
            $params['distributor_id'] = $distributor_id;
            $params['is_can_sale'] = true;
        }

        if (isset($params['lng']) &&  $params['lng'] && isset($params['lat']) && $params['lat']) {
            $distributorService = new DistributorService();
            $d_filter = [
                'lng'=>$params['lng'],
                'lat'=>$params['lat']
            ];
            $orderBy = [
                "distance" => "ASC",
                "distributor_id" => "DESC"
            ];
            // 查询店铺信息
            $distributorLists = $distributorService->lists($d_filter, $orderBy, 100, 1, false,'distributor_id,distance');
            if($distributorLists['list']){
                $params['distributor_id'] = array_unique(array_column($distributorLists['list'],'distributor_id'));
            }
        }

        // 无店铺的参数
        if ($distributor_id === '0') {
            $params['distributor_id'] = $distributor_id;
        }
        $params['is_gift'] = 0;  //非赠品商品
        if ($request->input('goodsSort') == 1) {
            $orderBy['sales'] = 'desc';
        } elseif ($request->input('goodsSort') == 2) {
            $orderBy['price'] = 'desc';
        } elseif ($request->input('goodsSort') == 3) {
            $orderBy['price'] = 'asc';
        } elseif ($request->input('goodsSort') == 4) {
            $orderBy['created'] = 'desc';
        } elseif ($request->input('goodsSort') == 5) {
            $orderBy['store'] = 'desc';
        } else {
            $orderBy['sort'] = 'desc';
        }
        $orderBy['item_id'] = 'desc';

        $settingService = new SettingService();
        $config = $settingService->getConfig($params['company_id']);
        if (isset($inputData['is_promoter']) && $inputData['is_promoter']) {
            $params['distributor_id'] = 0;
            if ($config['goods'] == 'select') {
                $params['rebate'] = 1;
            }
        }

        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            if (is_array($inputData['rebate_type'])) {
                $params['rebate_type'] = $inputData['rebate_type'];
            } else {
                $params['rebate_type'] = explode(',', $inputData['rebate_type']);
            }
        }

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];

        if ($request->input('is_default', true) !== 'false' || !$request->input('is_default', true)) {
            $params['is_default'] = true;
        }

        if (isset($inputData['category_id']) && $inputData['category_id']) {
            $params['category_id'] = $inputData['category_id'];
        }

        if (isset($params['category_id']) && $params['category_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $params['category_id'] = $itemsCategoryService->getItemsCategoryIds($params['category_id'], $params['company_id']);
        }

        //优惠券商品列表参数
        if (isset($inputData['card_id']) && $inputData['card_id']) {
            $discountCardService = new KaquanService(new DiscountCardService());
            $cardParams = $discountCardService->getKaquanItems(['card_id' => $inputData['card_id'], 'company_id' => $params['company_id']], true);
            $cardInfo = $cardParams['card_info'];
            unset($cardParams['card_info']);
            $params = array_merge($params, $cardParams);
            if ($cardInfo['card_type'] == 'new_gift') {
                if ($distributor_id) {
                    $shop_ids = trim($cardInfo['distributor_id'], ',');
                    if ($shop_ids && !in_array($distributor_id, explode(',', $shop_ids))) {
                        $params['item_id'] = -1;
                    }
                }
            }
        }

        $itemsService = new ItemsService();
        // 处理查询条件
        $params = $itemsService->__formateGetListFilter($params);
        if (!isset($params['is_default'])) {
            $pageSize = -1;
            $result = $itemsService->getSkuItemsList($params, $page, $pageSize);
        } else {
            if( isset($inputData['isSalesmanPage']) &&   $inputData['isSalesmanPage'] == 1 )  {
                if (isset($inputData['store_status']) &&  $inputData['store_status'] ) {
                    $params["store|gt"] = 0;
                }

                if (isset($inputData['store_status']) &&  $inputData['store_status'] === '0') {
                    $params["store|lt"] = 1;
                }
                $params["rebate"] = 1;
                $params['item_type'] = 'normal';

                if($authInfo['user_id']){
                    $salespersonService = new SalespersonService();
                    $filter_sales['company_id'] = $authInfo['company_id'];
                    $filter_sales['user_id']    = $authInfo['user_id'];
                    $filter_sales['is_valid']   = 'true';

                    $listdata = $salespersonService->getSalespersonList($filter_sales, 1, 500);
                    $dIds = array_column($listdata['list'],'shop_id');
                    // foreach($dIds as $kd => $vd){

                    // }
                    $distributorService = new DistributorService();
                    $orderBy = [
                        "distributor_id" => "DESC"
                    ];
                    $d_filter = [
                        'is_open_salesman' => 1,
                        'distributor_id'=>$dIds ,
                        'company_id'=>$authInfo['company_id'],
                    ];
                    app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:dids:". json_encode($d_filter));

                    if(isset($inputData['distributor_id']) && $inputData['distributor_id'] ){
                        if(in_array($inputData['distributor_id'],$dIds)  ){
                            $d_filter['distributor_id'] =  $inputData['distributor_id'];
                        }
                    }
                    app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:dids:". json_encode($d_filter));

                    if(!$d_filter['distributor_id']){
                        $result = array('total_count'=> 0 ,'list'=>array(),'nosalestore'=> 1);
                        return $this->response->array($result);
                    }
                    app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:distributorid:". json_encode($d_filter));

                    // 查询店铺信息
                    $distributorLists = $distributorService->lists($d_filter, $orderBy, 100, 1, false,'distributor_id,distance');
                    $dIds = array_column($distributorLists['list'],'distributor_id');
                    app('log')->debug("\n".__FUNCTION__."-".__LINE__.":d_filter:distributorid:". json_encode($dIds));

                    if(!$dIds){
                        $result = array('total_count'=> 0 ,'list'=>array(),'nosalestore'=> 2);
                        return $this->response->array($result);
                    }

                    if($distributor_id){
                        if(in_array($distributor_id,    $dIds )) {
                             $params['distributor_id'] = $distributor_id;
                        }else{
                             $result = array('total_count'=> 0 ,'list'=>array(),'nosalestore'=> 2);
                             return $this->response->array($result);
                        }
                    }else{
                        $params['distributor_id'] = $dIds;
                    }

                }else{
                    $params['distributor_id'] = '';
                }
            }

            $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);
        }

        if (isset($inputData['is_promoter']) && $inputData['is_promoter'] && $result['list']) {
            $pointMemberRuleService = new PointMemberRuleService($params['company_id']);
            $commissionType = $config['commission_type'] ?? 'money';

            foreach ($result['list'] as &$itemInfo) {
                $itemInfo = $this->__replaceItempPomoterPrice($itemInfo, $config);
                $itemInfo['promoter_point'] = $pointMemberRuleService->moneyToPointSend($itemInfo['promoter_price']);
                $itemInfo['commission_type'] = $commissionType;
            }
        } else {
            // 如果是推广员不需要计算会员价
            if ($result['list']) {
                // 计算会员价
                $result = $itemsService->getItemsListMemberPrice($result, $authInfo['user_id'], $params['company_id']);
            }
            //营销标签
            $result = $itemsService->getItemsListActityTag($result, $params['company_id']);
        }

        $memberFavItemsId = [];
        $cartList = [];
        if ($result['list'] && isset($authInfo['user_id']) && $authInfo['user_id']) {
            $memberItemsFavService = new MemberItemsFavService();
            $memberFavItems = $memberItemsFavService->lists(['user_id' => $authInfo['user_id'], 'item_id' => array_column($result['list'], 'item_id')], 1, $pageSize);
            $memberFavItemsId = array_column($memberFavItems['list'], 'item_id');

            $filter = [
                'company_id' => $params['company_id'],
                'user_id' => $authInfo['user_id'],
                'item_id' => array_column($result['list'], 'item_id'),
                'shop_id' => $request->input('distributor_id', 0)
            ];
            $cartEntityRepository = app('registry')->getManager('default')->getRepository(Cart::class);
            $cartList = $cartEntityRepository->lists($filter);
            $cartList = array_column($cartList['list'] , null,'item_id');
        }

        $ItemTaxRateService = new ItemTaxRateService($params['company_id']);
        foreach ($result['list'] as $key => $row) {
            $result['list'][$key]['cart_num'] = 0;
            if(isset($cartList[$row['item_id']])){
                $result['list'][$key]['cart_num'] = $cartList[$row['item_id']]['num'] ?? 0;
            }
            $result['list'][$key]['is_fav'] = false;
            if (in_array($row['item_id'], $memberFavItemsId)) {
                $result['list'][$key]['is_fav'] = true;
            }
            $result['list'][$key]['type'] = strval($row['type']);    // 转换类型为成字符串
            // 替换商品库存为总部和门店总库存
            if ($inputData['isShopScreen'] ?? 0) {
                $result['list'][$key]['store'] += $row['logistics_store'] ?? 0;
            }
            // if($row['is_market'] == 0){
            //     $result['list'][$key]['approve_status'] = 'instock';
            // }
            // 判断是否跨境，如果是，获取税费税率
            if ($row['type'] == '1') {
                $tax_calculation = 'price';                       // 计税
                $tax_calculation_price = $row['price'];         // 计税价格

                // 是否有会员价格，如果有覆盖计税价格
                if (!empty($row['member_price'])) {
                    $tax_calculation = 'member_price';                   // 计税
                    $tax_calculation_price = $row['member_price'];
                }
                // 是否有活动价格，如果有覆盖计税价格
                if (!empty($row['activity_price'])) {
                    $tax_calculation = 'activity_price';                   // 计税
                    $tax_calculation_price = $row['activity_price'];
                }

                $ItemTaxRate = $ItemTaxRateService->getItemTaxRate($row['item_id'], $tax_calculation_price);      // 税率信息
                $cross_border_tax = bcdiv(bcdiv(bcmul($tax_calculation_price, bcmul($ItemTaxRate['tax_rate'], 100)), 100), 100);  // 税费计算
                $result['list'][$key]['cross_border_tax'] = $cross_border_tax;  // 税费
                $result['list'][$key]['cross_border_tax_rate'] = $ItemTaxRate['tax_rate'];  // 税率
                $result['list'][$key][$tax_calculation] = bcadd($row[$tax_calculation], $cross_border_tax); // 含税价格(列表显示的价格)
                if ($tax_calculation == 'activity_price') {
                    $result['list'][$key]['promotion_activity'][count($result['list'][$key]['promotion_activity']) - 1]['activity_price'] = $result['list'][$key][$tax_calculation];
                }
            } else {
                $result['list'][$key]['cross_border_tax'] = 0;  // 税费
                $result['list'][$key]['cross_border_tax_rate'] = 0; // 税率
            }
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

    public function getItemsFilter(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter = $request->input('filter');
        $filter['company_id'] = $authInfo['company_id'];
        $itemsService = new ItemsService();
        $result = $itemsService->getItemsFilter($filter);
        if ($result['main_category_ids']) {
            $categoryService = new CategoryService();
            $result['main_category_list'] = $categoryService->getTopByChildrenId($result['main_category_ids']);
        }
        if ($result['distributor_ids']) {
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getDistributionNameListByDistributorId($filter['company_id'], $result['distributor_ids'], 1, 10);
            $result['distributor_list'] = $distributorList;
        }
        return $this->response->array($result);
    }

    private function __replaceItempPomoterPrice($itemInfo, $config)
    {
        // TODO: optimize this method
        $itemsRebateConf = [];
        $rebateConf = $itemInfo['rebate_conf'];
        $ratio = 0;
        if (isset($rebateConf['value']['first_level']) && $rebateConf['value']['first_level'] > 0) {
            // 如果商品有配置分销的固定金额
            if (isset($rebateConf['type']) && $rebateConf['type'] == 'money') {
                $itemInfo['promoter_price'] = bcmul($rebateConf['value']['first_level'], 100);
                return $itemInfo;
            }

            // 如果是按照比例返佣
            if (isset($rebateConf['type']) && $rebateConf['type'] == 'ratio' && $config['popularize_ratio']['type'] == $rebateConf['ratio_type']) {
                $ratio = $rebateConf['value']['first_level'];
            }
        }

        if ($config['popularize_ratio']['type'] == 'profit') {
            // 按照利润
            if (!$ratio) {
                $ratio = $config['popularize_ratio']['profit']['first_level']['ratio'];
            }
            $itemInfo['promoter_price'] = bcdiv(bcmul(bcsub($itemInfo['price'], $itemInfo['cost_price']), $ratio), 100, 2);
            $itemInfo['promoter_price'] = ($itemInfo['promoter_price'] > 0) ? $itemInfo['promoter_price'] : 0;
        } else {
            if (!$ratio) {
                $ratio = $config['popularize_ratio']['order_money']['first_level']['ratio'];
            }
            $itemInfo['promoter_price'] = bcdiv(bcmul($itemInfo['price'], $ratio), 100);
        }
        $itemInfo['promoter_price'] = ($itemInfo['promoter_price'] >= 1) ? intval($itemInfo['promoter_price']) : 0;
        return $itemInfo;
    }

    // 商品详情，直接返回页面
    public function getItemsIntro($item_id, Request $request)
    {
        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_intro_error'), $validator->errors());
        }
        $authInfo = $request->get('auth');
        $woa_appid = $authInfo['woa_appid'];
        $itemsService = new ItemsService();
        $result = $itemsService->getItemsDetail($item_id, $woa_appid);
        return response($result['intro'])->header('content-type', 'text/html');
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/items/{item_id}/fav",
     *     summary="获取商品收藏情况",
     *     tags={"商品"},
     *     description="获取商品收藏情况（暂时作废）",
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
     *                     @SWG\Property(property="status", type="boolean"),
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
     *     path="/wxapp/goods/memberprice/{item_id}",
     *     summary="获取商品会员价",
     *     tags={"商品"},
     *     description="获取商品会员价",
     *     operationId="getMemberPriceList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( in="path", type="string", required=true, name="item_id", description="商品id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="5031", description="商品id"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                          @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                          @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                          @SWG\Property( property="store", type="string", example="100", description="库存"),
     *                          @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                          @SWG\Property( property="sales", type="string", example="null", description="销量"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                          @SWG\Property( property="rebate", type="string", example="0", description=""),
     *                          @SWG\Property( property="rebate_conf", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                          @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                          @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                          @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                          @SWG\Property( property="goods_id", type="string", example="5031", description="商品集合ID"),
     *                          @SWG\Property( property="brand_id", type="string", example="1345", description="品牌id"),
     *                          @SWG\Property( property="item_name", type="string", example="dermGO SENSITIVE敏感肌改善抗衰精华30ml", description="商品名称"),
     *                          @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                          @SWG\Property( property="item_bn", type="string", example="S5F7FF8A28B501", description="商品编码"),
     *                          @SWG\Property( property="brief", type="string", example="", description=""),
     *                          @SWG\Property( property="price", type="string", example="52803", description="价格,单位为‘分’"),
     *                          @SWG\Property( property="market_price", type="string", example="65000", description="原价,单位为‘分’"),
     *                          @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                          @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                          @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                          @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                          @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                          @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                          @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                          @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                          @SWG\Property( property="regions_id", type="string", example="null", description="产地地区id"),
     *                          @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="templates_id", type="string", example="1", description="运费模板id"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认"),
     *                          @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                          @SWG\Property( property="default_item_id", type="string", example="5031", description="默认商品id"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="http://bbctest.aixue7.com/image/1/2020/09/09/96fc8edccb64e946db67bdabc429b6fb25A1ucQJFYJgr9TwXVNMIlBfEeC0Ymq5", description=""),
     *                          ),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                          @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE: 指定日期范围内，DATE_TYPE_FIX_TERM:固定天数后"),
     *                          @SWG\Property( property="item_category", type="string", example="1173", description="商品主类目"),
     *                          @SWG\Property( property="rebate_type", type="string", example="default", description="返佣模式"),
     *                          @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                          @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                          @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                          @SWG\Property( property="tax_rate", type="string", example="0", description="税率, 百分之～/100"),
     *                          @SWG\Property( property="created", type="string", example="1610521344", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1610529635", description="修改时间"),
     *                          @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                          @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                          @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                          @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                          @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                          @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                          @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                          @SWG\Property( property="profit_type", type="string", example="0", description="分润类型"),
     *                          @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                          @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="origincountry_id", type="string", example="19", description="产地国id"),
     *                          @SWG\Property( property="taxstrategy_id", type="string", example="13", description="税费策略id"),
     *                          @SWG\Property( property="taxation_num", type="string", example="1", description="计税单位份数"),
     *                          @SWG\Property( property="type", type="string", example="1", description=""),
     *                          @SWG\Property( property="tdk_content", type="string", example="{'title':'','mate_description':'','mate_keywords':''}", description="tdk详情"),
     *                          @SWG\Property( property="itemId", type="string", example="5031", description=""),
     *                          @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                          @SWG\Property( property="itemName", type="string", example="dermGO SENSITIVE敏感肌改善抗衰精华30ml", description=""),
     *                          @SWG\Property( property="itemBn", type="string", example="S5F7FF8A28B501", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="item_main_cat_id", type="string", example="1173", description=""),
     *                          @SWG\Property( property="type_labels", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="memberGrade", type="object",
     *                                  @SWG\Property( property="vipGrade", type="object",
     *                                          @SWG\Property( property="1", type="object",
     *                                                  @SWG\Property( property="vip_grade_id", type="string", example="1", description="付费会员卡等级ID"),
     *                                                  @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     *                                                  @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型,可选值有 vip:普通vip;svip:进阶vip"),
     *                                                  @SWG\Property( property="mprice", type="string", example="0", description=""),
     *                                          ),
     *                                          @SWG\Property( property="2", type="object",
     *                                                  @SWG\Property( property="vip_grade_id", type="string", example="2", description="付费会员卡等级ID"),
     *                                                  @SWG\Property( property="grade_name", type="string", example="超级付费1", description="等级名称"),
     *                                                  @SWG\Property( property="lv_type", type="string", example="svip", description="等级类型,可选值有 vip:普通vip;svip:进阶vip"),
     *                                                  @SWG\Property( property="mprice", type="string", example="29600", description=""),
     *                                          ),
     *                                  ),
     *                                  @SWG\Property( property="grade", type="object",
     *                                          @SWG\Property( property="4", type="object",
     *                                                  @SWG\Property( property="vip_grade_id", type="string", example="4", description="付费会员卡等级ID"),
     *                                                  @SWG\Property( property="grade_name", type="string", example="普通会员", description="等级名称"),
     *                                                  @SWG\Property( property="mprice", type="string", example="52803", description=""),
     *                                          ),
     *                                          @SWG\Property( property="8", type="object",
     *                                                  @SWG\Property( property="vip_grade_id", type="string", example="8", description="费会员卡等级ID"),
     *                                                  @SWG\Property( property="grade_name", type="string", example="高级会员", description="等级名称"),
     *                                                  @SWG\Property( property="mprice", type="string", example="29600", description=""),
     *                                          ),
     *                                          @SWG\Property( property="26", type="object",
     *                                                  @SWG\Property( property="vip_grade_id", type="string", example="26", description="付费会员卡等级ID"),
     *                                                  @SWG\Property( property="grade_name", type="string", example="尊贵会员", description="等级名称"),
     *                                                  @SWG\Property( property="mprice", type="string", example="0", description=""),
     *                                          ),
     *                                          @SWG\Property( property="27", type="object",
     *                                                  @SWG\Property( property="vip_grade_id", type="string", example="27", description="付费会员卡等级ID"),
     *                                                  @SWG\Property( property="grade_name", type="string", example="黄金会员", description="等级名称"),
     *                                                  @SWG\Property( property="mprice", type="string", example="0", description=""),
     *                                          ),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getMemberPriceList($item_id, Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $params['item_id'] = $item_id;
        $validator = app('validator')->make($params, [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_member_price_detail_error'), $validator->errors());
        }

        $params['company_id'] = $companyId;

        $memberPriceService = new MemberPriceService();

        $result = $memberPriceService->getMemberPriceList($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/keywords",
     *     summary="获取热门关键词",
     *     tags={"商品"},
     *     description="获取热门关键词",
     *     operationId="getKeywords",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2", description="自行更改字段描述"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="content", type="string", example="测试1", description="内容"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="content", type="array",
     *                      @SWG\Items( type="string", example="测试1", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getKeywords(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $validator = app('validator')->make($request->all(), [
            'distributor_id' => 'integer',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.param_error'), $validator->errors());
        }
        $filter['company_id'] = $companyId;
        $distributor_id = $request->input('distributor_id', 0);
        $filter['distributor_id'] = $distributor_id;
        $keywordsService = new KeywordsService();
        $result = $keywordsService->getByShop($filter);
        $result['content'] = array_column($result['list'], 'content');
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/checkshare/items",
     *     summary="检查是否可以分享",
     *     tags={"商品"},
     *     description="检查当前会员是否可以分享",
     *     operationId="checkShare",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *                  @SWG\Property( property="msg", type="string", example="当前等级无法分享", description="分享限制提示语"),
     *                  @SWG\Property( property="page", type="string", example="pages/index", description="提示后跳转页面路径"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function checkShare(Request $request)
    {
        $authInfo = $request->get('auth');
        $itemsService = new ItemsService();
        $result = $itemsService->checkUserItemShare($authInfo['company_id'], $authInfo['user_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/share/items/{item_id}",
     *     summary="获取商品分享的数据",
     *     tags={"商品"},
     *     description="获取商品分享的数据",
     *     operationId="getShareInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="item_id", type="string", example="1", description="商品id"),
     *                  @SWG\Property( property="item_name", type="string", example="商品名称", description="商品名称"),
     *                  @SWG\Property( property="brief", type="string", example="商品简介", description="商品简介"),
     *                  @SWG\Property( property="price", type="string", example="1000", description="销售价 单位：分"),
     *                  @SWG\Property( property="pics", type="string", example="", description="图片数组 包含字段 url:图片链接 isCode:是否生成分享码(bool)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getShareInfo($item_id, Request $request)
    {
        $itemsService = new ItemsService();
        $authInfo = $request->get('auth');
        $filter = [
            'company_id' => $authInfo['company_id'],
            'item_id' => $item_id,
            'audit_status' => 'approved',
        ];
        $cols = ['item_id', 'item_name', 'brief', 'price', 'pics', 'pics_create_qrcode'];
        /** @var array $itemInfo */
        $itemInfo = $itemsService->getSimpleInfo($filter, $cols);
        if (!$itemInfo) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_exist_or_offline'));
        }
        // 处理图片数据
        $pics = [];
        if (!empty($itemInfo['pics'])) {
            foreach ($itemInfo['pics'] as $key => $pic_url) {
                $isCode = $itemInfo['pics_create_qrcode'][$key] ?? false;
                $pics[$key] = [
                    'url' => $pic_url,
                    'isCode' => $isCode == 'true' ? true : false,
                ];
            }
        }

        $itemInfo['pics'] = $pics;
        unset($itemInfo['itemId'], $itemInfo['consumeType'], $itemInfo['itemName'], $itemInfo['itemBn'], $itemInfo['companyId'], $itemInfo['item_main_cat_id'], $itemInfo['nospec'], $itemInfo['pics_create_qrcode']);
        return $this->response->array($itemInfo);
    }
}
