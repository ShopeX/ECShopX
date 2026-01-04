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

namespace CommunityBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsTagsService;
use CommunityBundle\Services\CommunityItemsService;

class CommunityItems extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/community/items",
     *     summary="添加商品",
     *     tags={"社区团购"},
     *     description="添加商品",
     *     operationId="createItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="goods_id[]", in="query", description="商品ID", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function createItems(Request $request)
    {
        // ShopEx EcShopX Service Component
        $data['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $data['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $data['distributor_id'] = $request->get('distributor_id');
        }

        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'goods_id' => 'required',
        ], [
            'goods_id' => '商品ID必填',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors());
        }
        $data['goods_id'] = $inputData['goods_id'];

        $communityItemsService = new CommunityItemsService();
        $communityItemsService->batchInsert($data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/community/items",
     *     summary="获取社区拼团商品列表",
     *     tags={"社区团购"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", type="string" ),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品编号", type="string" ),
     *     @SWG\Parameter( name="barcode", in="query", description="条形码", type="string" ),
     *     @SWG\Parameter( name="approve_status", in="query", description="商品状态", type="string" ),
     *     @SWG\Parameter( name="brand_id", in="query", description="品牌", type="integer" ),
     *     @SWG\Parameter( name="category", in="query", description="商品分类", type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Parameter( name="in_activity", in="query", description="是否在活动中", required=false, type="boolean" ),
     *     @SWG\Parameter( name="activit_id", in="query", description="活动ID", required=false, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="292", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="5473", description="商品id"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                          @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                          @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                          @SWG\Property( property="store", type="string", example="1", description="商品库存"),
     *                          @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                          @SWG\Property( property="sales", type="string", example="null", description="商品销量"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                          @SWG\Property( property="rebate", type="string", example="0", description="返佣单位为‘分’"),
     *                          @SWG\Property( property="rebate_conf", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                          @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                          @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                          @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                          @SWG\Property( property="goods_id", type="string", example="5473", description="商品集合ID"),
     *                          @SWG\Property( property="brand_id", type="string", example="1228", description="品牌id"),
     *                          @SWG\Property( property="item_name", type="string", example="一级导入测试", description="商品名称"),
     *                          @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                          @SWG\Property( property="item_bn", type="string", example="9812918291vasas", description="商品编码"),
     *                          @SWG\Property( property="brief", type="string", example="", description=""),
     *                          @SWG\Property( property="price", type="string", example="10000", description="商品价格"),
     *                          @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                          @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                          @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                          @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                          @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                          @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                          @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                          @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                          @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                          @SWG\Property( property="regions_id", type="string", example="null", description=""),
     *                          @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="商品排序"),
     *                          @SWG\Property( property="templates_id", type="string", example="105", description="运费模板id"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币"),
     *                          @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                          @SWG\Property( property="default_item_id", type="string", example="5473", description="默认商品ID"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="", description=""),
     *                          ),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                          @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                          @SWG\Property( property="item_category", type="string", example="1733", description="商品主类目"),
     *                          @SWG\Property( property="rebate_type", type="string", example="total_money", description="分佣计算方式"),
     *                          @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                          @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                          @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                          @SWG\Property( property="tax_rate", type="string", example="13", description="税率, 百分之～/100"),
     *                          @SWG\Property( property="created", type="string", example="1612170580", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612170580", description="修改时间"),
     *                          @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                          @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                          @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                          @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                          @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                          @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                          @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                          @SWG\Property( property="profit_type", type="string", example="0", description="分佣计算方式"),
     *                          @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                          @SWG\Property( property="is_profit", type="string", example="false", description="是否支持分润"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                          @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                          @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                          @SWG\Property( property="type", type="string", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                          @SWG\Property( property="tdk_content", type="string", example="", description="tdk详情"),
     *                          @SWG\Property( property="itemId", type="string", example="5473", description=""),
     *                          @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                          @SWG\Property( property="itemName", type="string", example="一级导入测试", description=""),
     *                          @SWG\Property( property="itemBn", type="string", example="9812918291vasas", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="item_main_cat_id", type="string", example="1733", description=""),
     *                          @SWG\Property( property="item_cat_id", type="array",
     *                              @SWG\Items( type="string", example="1817", description=""),
     *                          ),
     *                          @SWG\Property( property="type_labels", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="tagList", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="itemMainCatName", type="string", example="连衣裙", description=""),
     *                          @SWG\Property( property="itemCatName", type="array",
     *                              @SWG\Items( type="string", example="[一级导入测试]", description=""),
     *                          ),
     *                          @SWG\Property( property="min_delivery_num", type="integory", description="起送数量"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getItemsList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }
        $page = intval($inputData['page']);
        $pageSize = intval($inputData['pageSize']);

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $filter['item_name|contains'] = $request->input('keywords');
        }

        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $filter['item_name|contains'] = $request->input('item_name');
        }

        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $filter['item_bn|contains'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($filter, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($filter['item_bn|contains']);
            $filter['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['barcode']) && $inputData['barcode']) {
            $filter['barcode'] = $inputData['barcode'];

            $datalist = $itemsService->getItemsLists($filter, 'default_item_id,item_id');

            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($filter['barcode']);
            $itemIds = array_column($datalist, 'default_item_id');
            if (isset($filter['item_id'])) {
                $filter['item_id'] = array_intersect($filter['item_id'], $itemIds);
            } else {
                $filter['item_id'] = $itemIds;
            }
        }

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $filter['audit_status'] = $request->input('approve_status');
            } else {
                $filter['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['brand_id']) && $inputData['brand_id']) {
            $filter["brand_id"] = $inputData['brand_id'];
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemIds = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $filter['company_id']);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }

            if (isset($filter['item_id'])) {
                $filter['item_id'] = array_intersect($filter['item_id'], $itemIds);
            } else {
                $filter['item_id'] = $itemIds;
            }
        }

        if (isset($inputData['in_activity'])) {
            $filter['in_activity'] = $inputData['in_activity'] === 'true';
        }

        if (isset($inputData['activit_id']) && $inputData['activit_id']) {
            $filter['activit_id'] = $inputData['activit_id'];
        }

        $filter['item_type'] = 'normal';
        $filter['is_default'] = true;

        $communityItemsService = new CommunityItemsService();
        $result = $communityItemsService->getItemsList($filter, $page, $pageSize);
        $result = $itemsService->dealListStore($result);

        if ($result['list']) {
            //获取商品标签
            $itemIds = array_column($result['list'], 'item_id');
            $tagFilter = [
                'item_id' => $itemIds,
                'company_id' => $filter['company_id'],
            ];
            $itemsTagsService = new ItemsTagsService();
            $tagList = $itemsTagsService->getItemsRelTagList($tagFilter);
            foreach ($tagList as $tag) {
                $newTags[$tag['item_id']][] = $tag;
            }

            $itemsCategoryService = new ItemsCategoryService();
            foreach ($result['list'] as &$value) {
                $value['tagList'] = $newTags[$value['item_id']] ?? [];

                $categoryInfo = $itemsCategoryService->getInfoById($value['item_main_cat_id']);
                $value['itemMainCatName'] = $categoryInfo['category_name'] ?? '';

                $cat_arr = [];
                foreach (($value['item_cat_id'] ?? []) as &$v) {
                    $cat_info = $itemsCategoryService->getInfoById($v);
                    if ($cat_info) {
                        $cat_arr[] = '['.$cat_info['category_name'].']';
                    }
                }
                $value['itemCatName'] = $cat_arr;
            }
        }

        return $this->response->array($result);
    }

    /**
      * @SWG\Post(
      *     path="/community/itemMinDeliveryNum",
      *     summary="修改商品起送量",
      *     tags={"社区团购"},
      *     description="修改商品起送量",
      *     operationId="updateItemMinDeliveryNum",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="goods_id", in="query", description="商品id", required=true, type="integer"),
      *     @SWG\Parameter( name="min_delivery_num", in="query", description="起送数量", required=false, type="integer"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="status", type="string", example="true", description=""),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
      * )
      */
    public function updateItemMinDeliveryNum(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'goods_id' => 'required',
            'min_delivery_num' => 'required',
        ], [
            'goods_id' => '商品ID必填',
            'min_delivery_num' => '起送数量必填',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors());
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        $filter['goods_id'] = $inputData['goods_id'];
        $data['min_delivery_num'] = $inputData['min_delivery_num'] > 0 ? $inputData['min_delivery_num'] : 0;
        $communityItemsService = new CommunityItemsService();
        $communityItemsService->updateBy($filter, $data);
        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\Post(
      *     path="/community/itemSort",
      *     summary="修改商品排序编号",
      *     tags={"社区团购"},
      *     description="修改商品排序编号",
      *     operationId="updateItemSort",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="goods_id", in="query", description="商品id", required=true, type="integer"),
      *     @SWG\Parameter( name="sort", in="query", description="排序编号", required=false, type="integer"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="status", type="string", example="true", description=""),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
      * )
      */
    public function updateItemSort(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'goods_id' => 'required',
            'sort' => 'required',
        ], [
            'goods_id' => '商品ID必填',
            'sort' => '序号编号必填',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors());
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        $filter['goods_id'] = $inputData['goods_id'];
        $data['sort'] = $inputData['sort'] > 0 ? $inputData['sort'] : 0;
        $communityItemsService = new CommunityItemsService();
        $communityItemsService->updateBy($filter, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/community/item/{goods_id}",
     *     summary="删除商品",
     *     tags={"社区团购"},
     *     description="删除商品",
     *     operationId="deleteItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="goods_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Response( response=204, description="成功返回结构"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function deleteItem($goods_id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        $filter['goods_id'] = $goods_id;
        $communityItemsService = new CommunityItemsService();

        $filter['in_activity'] = true;
        $result = $communityItemsService->getItemsList($filter, 1, 1);
        if ($result['total_count'] > 0) {
            throw new ResourceException('活动中的商品不能删除');
        }
        unset($filter['in_activity']);

        $communityItemsService->deleteBy($filter);
        return $this->response->array(['status' => true]);
    }
}
