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

namespace SystemLinkBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Http\Controllers\Controller as Controller;

use Dingo\Api\Exception\ResourceException;

use GoodsBundle\Services\ItemStoreService;

use GoodsBundle\Services\ItemsService;



use PromotionsBundle\Traits\CheckPromotionsValid;

use GoodsBundle\Entities\Items;

use GoodsBundle\Services\ItemsCategoryService;

use MembersBundle\Services\MemberUploadService;
use GoodsBundle\Services\NormalGoodsUploadService;

class Item extends Controller
{
    use CheckPromotionsValid;

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/uploadUser",
     *     summary="OMS同步会员",
     *     tags={"omeapi"},
     *     description="OMS同步会员",
     *     operationId="uploadUser",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.user.up", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司ID", required=true, type="integer"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="username", in="query", description="姓名", required=true, type="string"),
     *     @SWG\Parameter( name="sex", in="query", description="性别", default="男", required=true, type="string"),
     *     @SWG\Parameter( name="grade_name", in="query", description="会员等级", required=true, type="string"),
     *     @SWG\Parameter( name="created", in="query", description="入会日期(n/j/Y)", default="1/20/2020", required=true, type="string"),
     *     @SWG\Parameter( name="birthday", in="query", description="生日日期(n/j/Y)", default="1/20/2000", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="地址", required=true, type="string"),
     *     @SWG\Parameter( name="email", in="query", description="邮箱", required=true, type="string"),
     *     @SWG\Parameter( name="offline_card_code", in="query", description="原实体卡号", required=false, type="string"),
     *     @SWG\Parameter( name="tags", in="query", description="会员标签(英文逗号分隔)", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function uploadUser(Request $request)
    {
        $params = $request->input();
        $upService = new MemberUploadService();
        $companyId = $params['company_id'];
        $result = $upService->handleRow($companyId, $params);

        return $this->response->array([$result]);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/uploadItem",
     *     summary="OMS上传商品",
     *     tags={"omeapi"},
     *     description="OMS上传商品",
     *     operationId="uploadItem",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.item.up", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司ID", required=true, type="integer"),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", required=true, type="string"),
     *     @SWG\Parameter( name="brief", in="query", description="商品介绍", required=true, type="string"),
     *     @SWG\Parameter( name="price", in="query", description="价格(分)", required=true, type="integer"),
     *     @SWG\Parameter( name="cost_price", in="query", description="成本价(分)", required=true, type="integer"),
     *     @SWG\Parameter( name="market_price", in="query", description="市场价(分)", required=true, type="integer"),
     *     @SWG\Parameter( name="store", in="query", description="库存", required=true, type="integer"),
     *     @SWG\Parameter( name="templates_id", in="query", description="运费模板", default="1", required=true, type="integer"),
     *     @SWG\Parameter( name="item_spec", in="query", description="商品规格", required=false, type="string"),
     *     @SWG\Parameter( name="is_profit", in="query", description="是否支持分润", default="0", required=true, type="integer"),
     *     @SWG\Parameter( name="profit_type", in="query", description="分润类型", default="0", required=false, type="integer"),
     *     @SWG\Parameter( name="profit", in="query", description="拉新分润金额", required=false, type="integer"),
     *     @SWG\Parameter( name="popularize_profit", in="query", description="推广分润金额", required=false, type="integer"),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品编码", required=false, type="string"),
     *     @SWG\Parameter( name="barcode", in="query", description="商品条码", required=false, type="string"),
     *     @SWG\Parameter( name="weight", in="query", description="商品重量(kg)", required=false, type="string"),
     *     @SWG\Parameter( name="item_unit", in="query", description="计重单位", required=false, type="string"),
     *     @SWG\Parameter( name="item_main_category", in="query", description="商品主类目", default="服装->套装->连衣裙", required=true, type="string"),
     *     @SWG\Parameter( name="item_category", in="query", description="商品分类(多个用|分隔)", default="服装->上装->外套", required=true, type="string"),
     *     @SWG\Parameter( name="pics", in="query", description="商品图片(多个用英文逗号分隔)", required=false, type="string"),
     *     @SWG\Parameter( name="videos", in="query", description="商品视频地址", required=false, type="string"),
     *     @SWG\Parameter( name="goods_brand", in="query", description="商品品牌", default="ECshopX", required=false, type="string"),
     *     @SWG\Parameter( name="item_params", in="query", description="商品参数", default="功效:美白提亮|性别:男性", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function uploadItem(Request $request)
    {
        $params = $request->input();
        $upService = new NormalGoodsUploadService();
        $companyId = $params['company_id'];
        $result = $upService->handleRow($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/createCategory",
     *     summary="OMS同步商品分类(暂时不用)",
     *     tags={"omeapi"},
     *     description="OMS同步商品分类(暂时不用)",
     *     operationId="createCategory",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.category.create", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司ID", required=true, type="string"),
     *     @SWG\Parameter( name="form", in="query", description="分类参数", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function createCategory(Request $request)
    {
        $params = $request->input();
        $itemsCategoryService = new ItemsCategoryService();
        //$companyId = app('auth')->user()->get('company_id');
        $companyId = $params['company_id'];

        $result = $itemsCategoryService->saveItemsCategory($params['form'], $companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/createItems",
     *     summary="OMS添加商品",
     *     tags={"omeapi"},
     *     description="OMS添加商品",
     *     operationId="createItems",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="ome.items.create", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="consume_type", in="query", description="核销类型(every,all,notconsume)", required=true, type="integer"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", required=true, type="integer"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司ID", required=true, type="integer"),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", required=true, type="string"),
     *     @SWG\Parameter( name="brief", in="query", description="商品介绍", required=true, type="string"),
     *     @SWG\Parameter( name="price", in="query", description="价格(分)", required=true, type="integer"),
     *     @SWG\Parameter( name="cost_price", in="query", description="成本价(分)", required=true, type="integer"),
     *     @SWG\Parameter( name="market_price", in="query", description="市场价(分)", required=true, type="integer"),
     *     @SWG\Parameter( name="store", in="query", description="库存", required=true, type="integer"),
     *     @SWG\Parameter( name="templates_id", in="query", description="运费模板", default="1", required=true, type="integer"),
     *     @SWG\Parameter( name="item_spec", in="query", description="商品规格", required=false, type="string"),
     *     @SWG\Parameter( name="is_profit", in="query", description="是否支持分润", default="0", required=true, type="integer"),
     *     @SWG\Parameter( name="profit_type", in="query", description="分润类型", default="0", required=false, type="integer"),
     *     @SWG\Parameter( name="profit", in="query", description="拉新分润金额", required=false, type="integer"),
     *     @SWG\Parameter( name="popularize_profit", in="query", description="推广分润金额", required=false, type="integer"),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品编码", required=false, type="string"),
     *     @SWG\Parameter( name="barcode", in="query", description="商品条码", required=false, type="string"),
     *     @SWG\Parameter( name="weight", in="query", description="商品重量(kg)", required=false, type="string"),
     *     @SWG\Parameter( name="item_unit", in="query", description="计重单位", required=false, type="string"),
     *     @SWG\Parameter( name="item_main_category", in="query", description="商品主类目", default="服装->套装->连衣裙", required=true, type="string"),
     *     @SWG\Parameter( name="item_category", in="query", description="商品分类(多个用|分隔)", default="服装->上装->外套", required=true, type="string"),
     *     @SWG\Parameter( name="pics", in="query", description="商品图片(多个用英文逗号分隔)", required=true, type="string"),
     *     @SWG\Parameter( name="videos", in="query", description="商品视频地址", required=false, type="string"),
     *     @SWG\Parameter( name="goods_brand", in="query", description="商品品牌", default="ECshopX", required=false, type="string"),
     *     @SWG\Parameter( name="item_params", in="query", description="商品参数", default="功效:美白提亮|性别:男性", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function createItems(Request $request)
    {
        //$params = $request->all('item_type', 'item_name', 'consume_type', 'brief', 'price', 'market_price', 'pics', 'intro', 'purchase_agreement', 'enable_agreement', 'date_type', 'begin_date', 'end_date', 'fixed_term', 'type_labels');
        $params = $request->input();

        $rules = [
            'consume_type' => ['in:every,all,notconsume', '核销类型参数不正确'],
            'item_name' => ['required', '商品名称必填'],
            'pics' => ['required', '请上传商品图片'],
            'sort' => ['required|integer', '排序值必须为整数'],
        ];

        if (isset($params['item_type']) && $params['item_type'] == 'normal') {
            $rules['templates_id'] = ['required', '运费模板必填'];
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $itemsService = new ItemsService();
        //  $companyId = app('auth')->user()->get('company_id');
        //  $params['company_id'] = $companyId;
        if (isset($params['item_id'])) {
            unset($params['item_id']);
        }

        $result = $itemsService->addItems($params);

        return $this->response->array(['status' => true]);
    }

    //$api->post('/goods/items', ['name' => '添加商品', 'as' => 'goods.items.create', 'uses' => 'Items@createItems']);

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/updateItemStore",
     *     summary="OMS同步商品库存",
     *     tags={"omeapi"},
     *     description="OMS同步商品库存",
     *     operationId="updateItemStore",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.items.quantity.list.update", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="list_quantity", in="query", description="库存信息", default="[{bn:1,quantity:2,memo:3}]", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function updateItemStore(Request $request)
    {
        $params = $request->all();
        app('log')->debug('Item_updateItemStore_params=>:'.var_export($params, 1));

        $rules = [
            'list_quantity' => ['required', '缺少数据'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        extract($params);

        if (!$list_quantity) {
            $this->api_response('fail', "数据有误");
        }

        $itemsService = new ItemsService();

        //获取参与活动中的货品ID
        $activityItems = $this->getActivityItems();

        $activityBns = [];
        if ($activityItems) {
            //获取活动商品BN
            $activityItemList = $itemsService->getItemsList(['item_id' => $activityItems]);

            //取出参与活动中的商品BN
            for ($i = count($activityItemList['list']) - 1;$i >= 0;$activityBns[] = $activityItemList['list'][$i]['item_bn'],$i--);
        }

        $itemStoreService = new ItemStoreService();

        // $list_quantity = json_decode(stripslashes($list_quantity),1);
        $list_quantity = json_decode($list_quantity, 1);
        app('log')->debug('Item_updateItemStore_list_quantity=>:'.var_export($list_quantity, 1));

        // 取出所有要更新的商品BN
        $itemBns = [];
        for ($i = count($list_quantity) - 1;$i >= 0;$itemBns[] = $list_quantity[$i]['bn'],$i--);

        // 根据BN获取商品信息
        $itemList = $itemsService->getItemsList(['item_bn' => $itemBns]);
        app('log')->debug('Item_updateItemStore_list_itemBns=>:'.var_export($itemBns, 1));
        app('log')->debug('Item_updateItemStore_list_itemList=>:'.var_export($itemList, 1));
        if (!$itemList) {
            $this->api_response('fail', "商品不存在");
        }

        //一次性获取要更新库存的商品的BN
        $itemBnList = [];
        foreach ((array)$itemList['list'] as $ival) {
            if (!$ival) {
                continue;
            }
            $ival['item_bn'] = trim($ival['item_bn']);
            $itemBnList[$ival['item_bn']] = [
                'item_id' => $ival['item_id'],
                'company_id' => $ival['company_id'],
                'item_bn' => $ival['item_bn']
            ];
        }

        $noUpdateItem = [];
        $nofundItem = [];
        $failUpdateItem = [];
        foreach ((array)$list_quantity as $value) {
            if (!$value['bn'] || !isset($value['quantity'])) {
                continue;
            }

            $value['bn'] = trim($value['bn']);

            //参与活动中的商品跳过更新库存
            if ($activityBns && in_array($value['bn'], $activityBns)) {
                $noUpdateItem[] = $value['bn'];
                continue;
            }

            //检查商品是否存在
            if (!isset($itemBnList[$value['bn']]) || !$itemBnList[$value['bn']]) {
                $nofundItem[] = trim($value['bn']);
                continue;
            }

            //仅修改普通商品库存
            $result = $itemStoreService->saveItemStore($itemBnList[$value['bn']]['item_id'], $value['quantity']);
            if ($result) {
                $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                $result = $itemsRepository->updateStore($itemBnList[$value['bn']]['item_id'], $value['quantity']);
            }
            if (!$result) {
                $failUpdateItem[] = $value['bn'];
            }
        }

        if ($nofundItem) {
            app('log')->debug('OME-更新库存商品不存在：'.var_export($nofundItem, 1));
        }

        if ($noUpdateItem) {
            app('log')->debug('OME-活动商品暂不更新库存：'.var_export($noUpdateItem, 1));
        }

        if ($failUpdateItem) {
            app('log')->debug('OME-库存更新失败商品：'.var_export($failUpdateItem, 1));
        }

        $this->api_response('true', '操作成功');
    }
}
