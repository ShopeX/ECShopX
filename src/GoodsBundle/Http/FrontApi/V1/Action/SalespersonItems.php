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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use CompanysBundle\Traits\GetDefaultCur;
use PromotionsBundle\Traits\CheckPromotionsValid;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorItemsService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService;
use SalespersonBundle\Services\SalespersonItemsShelvesService;
use TdksetBundle\Services\TdkGivenService;

class SalespersonItems extends BaseController
{
    use CheckPromotionsValid;
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/salesperson/items",
     *     summary="获取导购货架商品列表",
     *     tags={"商品"},
     *     description="获取导购货架商品列表",
     *     operationId="getItemList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer", required=true ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50", type="integer", required=true ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", type="integer" ),
     *     @SWG\Parameter( name="keywords", in="query", description="搜索关键词", type="string" ),
     *     @SWG\Parameter( name="category", in="query", description="分类ID，只查询当前分类", type="integer" ),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型 normal:普通商品 services:服务商品", type="string" ),
     *     @SWG\Parameter( name="goodsSort", in="query", description="排序方式 1:销量倒序 2:价格倒序 3:价格正序 4:创建时间倒序", type="integer" ),
     *     @SWG\Parameter( name="is_default", in="query", description="是只获取否默认商品，默认true", type="boolean" ),
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
    public function getItemList(request $request)
    {
        //验证参数todo
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
            'distributor_id' => 'sometimes|integer|min:0'
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }

        $authInfo = $this->auth->user();
        if ($inputData['distributor_id'] ?? 0) {
            // 店铺 需要开启：
            $count = (new DistributorService())->count([
                'company_id' => $authInfo['company_id'],
                'distributor_id' => $inputData['distributor_id'],
                'is_valid' => 'true',
            ]);
            if (!$count) {
                return $this->response->array([]);
            }
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

        if (isset($inputData['category']) && $inputData['category']) {
            $params['category_id'] = $inputData['category'];
        }

        if ($request->input('keywords')) {
            $params['item_name'] = trim($request->input('keywords'));
        }
        $params['item_type'] = $request->input('item_type', 'normal');

        $distributor_id = $request->input('distributor_id', false);
        if ($distributor_id && $distributor_id !== 'false') {
            $params['distributor_id'] = $distributor_id;
            $params['is_can_sale'] = true;
        }
        $params['is_gift'] = false;  //非赠品商品
        $params['type|neq'] = 1;// 非跨境商品
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

        if ($request->input('is_default', true) !== 'false' || !$request->input('is_default', true)) {
            $params['is_default'] = true;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $params['category_id'] = $inputData['category'];
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

        if (isset($inputData['category_id']) && $inputData['category_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category_id'], $params['company_id']);
            if (!$ids) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }
        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
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

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/salesperson/itemsinfo",
     *     summary="获取导购货架商品详情",
     *     tags={"商品"},
     *     description="获取导购货架商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="goods_id", in="query", description="当商品id", type="integer" ),
     *     @SWG\Parameter( name="item_id", in="query", required=true, description="货品id", type="integer" ),
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
}
