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

namespace GoodsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Entities\Operators;
use CompanysBundle\Services\EmployeeService;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsMedicineService;
use GoodsBundle\Services\MultiLang\MultiLangService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use GoodsBundle\Services\ItemsService;
use Illuminate\Validation\Rule;
use PointBundle\Services\PointMemberRuleService;
use PopularizeBundle\Services\SettingService;
use SupplierBundle\Services\ItemsCommissionService;
use SupplierBundle\Services\SupplierItemsService;
use SupplierBundle\Services\SupplierService;
use WechatBundle\Services\WeappService;
use GoodsBundle\Services\ItemStoreService;
use GoodsBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemsRecommendService;
use GoodsBundle\Services\KeywordsService;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorItemsService;
use TbItemsBundle\Services\TbInterItemsService;

class Items extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/goods/items",
     *     summary="添加商品",
     *     tags={"商品"},
     *     description="添加商品",
     *     operationId="createItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型：normal实物类商品，service服务类商品", required=false, type="string" ),
     *     @SWG\Parameter( name="special_type", in="query", description="商品特殊类型 drug 处方药 normal 普通商品", required=false, type="string" ),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", required=true, type="string" ),
     *     @SWG\Parameter( name="brief", in="query", description="简介", required=false, type="string" ),
     *     @SWG\Parameter( name="tax_rate", in="query", description="税率", required=false, type="string" ),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品编号", required=false, type="string" ),
     *     @SWG\Parameter( name="weight", in="query", description="商品重量", required=false, type="string" ),
     *     @SWG\Parameter( name="volume", in="query", description="商品体积", required=false, type="string" ),
     *     @SWG\Parameter( name="barcode", in="query", description="商品条形码", required=false, type="string" ),
     *     @SWG\Parameter( name="item_unit", in="query", description="商品计量单位", required=false, type="string" ),
     *     @SWG\Parameter( name="rebate", in="query", description="单个分销金额，以分为单位", required=false, type="string" ),
     *     @SWG\Parameter( name="price", in="query", description="价格", required=false, type="string" ),
     *     @SWG\Parameter( name="market_price", in="query", description="原价", required=false, type="string" ),
     *     @SWG\Parameter( name="cost_price", in="query", description="成本价", required=false, type="string" ),
     *     @SWG\Parameter( name="store", in="query", description="商品库存", required=false, type="string" ),
     *     @SWG\Parameter( name="brand_id", in="query", description="品牌id", required=true, type="integer" ),
     *     @SWG\Parameter( name="templates_id", in="query", description="运费模板id", required=true, type="integer" ),
     *     @SWG\Parameter( name="approve_status", in="query", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售", required=false, type="string" ),
     *     @SWG\Parameter( name="item_main_cat_id", in="query", description="商品主类目", required=true, type="integer" ),
     *     @SWG\Parameter( name="item_category[]", in="query", description="商品分类", required=true, type="integer" ),
     *     @SWG\Parameter( name="is_gift", in="query", description="是否赠品", required=false, type="boolean" ),
     *     @SWG\Parameter( name="crossborder_tax_rate", in="query", description="跨境税率，百分比，小数点2位", required=false, type="string" ),
     *     @SWG\Parameter( name="origincountry_id", in="query", description="产地国id", required=false, type="integer" ),
     *     @SWG\Parameter( name="taxstrategy_id", in="query", description="税费策略id", required=false, type="integer" ),
     *     @SWG\Parameter( name="taxation_num", in="query", description="计税单位份数", required=false, type="integer" ),

     *     @SWG\Parameter( name="type", in="query", description="是否海外购商品1:是，0:否", required=false, type="integer" ),
     *     @SWG\Parameter( name="is_profit", in="query", description="是否支持分润", required=false, type="boolean" ),
     *     @SWG\Parameter( name="tdk_content", in="query", description="tdk详情", required=false, type="string" ),
     *     @SWG\Parameter( name="spec_items", in="query", description="多规格商品sku", required=false, type="string" ),
     *     @SWG\Parameter( name="sort", in="query", description="排序编号", required=false, type="integer" ),
     *     @SWG\Parameter( name="pics[]", in="query", description="图片", required=true, type="string" ),
     *     @SWG\Parameter( name="pics_create_qrcode[]", in="query", description="图片是否生成小程序码 数组 和pics的key做对应 true:是 false:否", required=true, type="string" ),
     *     @SWG\Parameter( name="videos", in="query", description="视频", required=false, type="string" ),
     *     @SWG\Parameter( name="video_pic_url", in="query", description="视频封面", required=false, type="string" ),
     *     @SWG\Parameter( name="intro", in="query", description="图文详情", required=false, type="string" ),
     *     @SWG\Parameter( name="purchase_agreement", in="query", description="购买协议", required=false, type="string" ),
     *     @SWG\Parameter( name="enable_agreement", in="query", description="是否开启购买协议", required=false, type="boolean" ),
     *     @SWG\Parameter( name="type_labels", in="query", description="商品关联类型、数值属性", required=false, type="string" ),
     *     @SWG\Parameter( name="is_point", in="query", description="开启积分兑换 true false", required=false, type="string" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Parameter( name="point", in="query", description="积分个数", required=false, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function createItems(Request $request)
    {
        //$params = $request->all('item_type', 'item_name', 'consume_type', 'brief', 'price', 'market_price', 'pics', 'intro', 'purchase_agreement', 'enable_agreement', 'date_type', 'begin_date', 'end_date', 'fixed_term', 'type_labels');
        $params = $request->input();
        $params['origincountry_id'] = $request->input('origincountry_id', 0);
        $params['taxstrategy_id'] = $request->input('taxstrategy_id', 0);
        $params['taxation_num'] = $request->input('taxation_num', 0);
        $distributor_id = $request->input('distributor_id');
        $params['operator_type'] = app('auth')->user()->get('operator_type');

        $rules = [
            'consume_type' => ['in:every,all,notconsume', '核销类型参数不正确'],
            'item_name' => ['required', '商品名称必填'],
            'pics' => ['required', '请上传商品图片'],
            'sort' => ['required|integer', '排序值必须为整数'],
        ];

        if (isset($params['item_type']) && $params['item_type'] == 'normal') {
            $rules['templates_id'] = ['required', '运费模板必填'];
            $rules['brand_id'] = ['required', '请选择品牌'];
            if ($params['operator_type'] == 'supplier') {
                $rules['audit_status'] = ['required', '审核状态不能为空'];
            } else {
                $rules['item_category'] = ['required', '请选择销售分类'];
            }
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $companyId = app('auth')->user()->get('company_id');
        $supplierId = app('auth')->user()->get('operator_id');

        $params['company_id'] = $companyId;
        $params['supplier_id'] = 0;
        if (isset($params['item_id'])) {
            unset($params['item_id']);
        }

        if ($params['operator_type'] == 'supplier') {
            $params['supplier_id'] = $supplierId;
            if ($params['audit_status'] != 'processing') {
                $params['audit_status'] = 'submitting';
            }
        } else {
            $params['audit_status'] = 'approved';//非供应商添加的商品，默认成审核状态
        }
        $itemsService = new ItemsService();
        if ($params['supplier_id']) {
            //供应商商品
            $SupplierItemsService = new SupplierItemsService();
            $result = $SupplierItemsService->addItems($params);
        } else {
            //平台商品

            $result = $itemsService->addItems($params);
        }
//        $service = new MultiLangService();
//        $service->addMultiLangByParams($result['item_id'],$params,'items');

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/goods/items/{item_id}",
     *     summary="更新商品",
     *     tags={"商品"},
     *     description="更新商品",
     *     operationId="updateItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型：normal实物类商品，service服务类商品", required=false, type="string" ),
     *     @SWG\Parameter( name="special_type", in="query", description="商品特殊类型 drug 处方药 normal 普通商品", required=false, type="string" ),
     *     @SWG\Parameter( name="item_name", in="query", description="商品名称", required=true, type="string" ),
     *     @SWG\Parameter( name="brief", in="query", description="简介", required=false, type="string" ),
     *     @SWG\Parameter( name="tax_rate", in="query", description="税率", required=false, type="string" ),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品编号", required=false, type="string" ),
     *     @SWG\Parameter( name="weight", in="query", description="商品重量", required=false, type="string" ),
     *     @SWG\Parameter( name="volume", in="query", description="商品体积", required=false, type="string" ),
     *     @SWG\Parameter( name="barcode", in="query", description="商品条形码", required=false, type="string" ),
     *     @SWG\Parameter( name="item_unit", in="query", description="商品计量单位", required=false, type="string" ),
     *     @SWG\Parameter( name="rebate", in="query", description="单个分销金额，以分为单位", required=false, type="string" ),
     *     @SWG\Parameter( name="price", in="query", description="价格", required=true, type="string" ),
     *     @SWG\Parameter( name="market_price", in="query", description="原价", required=false, type="string" ),
     *     @SWG\Parameter( name="cost_price", in="query", description="成本价", required=false, type="string" ),
     *     @SWG\Parameter( name="store", in="query", description="商品库存", required=false, type="string" ),
     *     @SWG\Parameter( name="brand_id", in="query", description="品牌id", required=true, type="integer" ),
     *     @SWG\Parameter( name="templates_id", in="query", description="运费模板id", required=true, type="integer" ),
     *     @SWG\Parameter( name="approve_status", in="query", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售", required=false, type="string" ),
     *     @SWG\Parameter( name="item_main_cat_id", in="query", description="商品主类目", required=true, type="integer" ),
     *     @SWG\Parameter( name="item_category[]", in="query", description="商品分类", required=true, type="integer" ),
     *     @SWG\Parameter( name="is_gift", in="query", description="是否赠品", required=false, type="boolean" ),
     *     @SWG\Parameter( name="crossborder_tax_rate", in="query", description="跨境税率，百分比，小数点2位", required=false, type="string" ),
     *     @SWG\Parameter( name="origincountry_id", in="query", description="产地国id", required=false, type="integer" ),
     *     @SWG\Parameter( name="taxstrategy_id", in="query", description="税费策略id", required=false, type="integer" ),
     *     @SWG\Parameter( name="taxation_num", in="query", description="计税单位份数", required=false, type="integer" ),

     *     @SWG\Parameter( name="type", in="query", description="是否海外购商品1:是，0:否", required=false, type="integer" ),
     *     @SWG\Parameter( name="is_profit", in="query", description="是否支持分润", required=false, type="boolean" ),
     *     @SWG\Parameter( name="tdk_content", in="query", description="tdk详情", required=false, type="string" ),
     *     @SWG\Parameter( name="spec_items", in="query", description="多规格商品sku", required=false, type="string" ),
     *     @SWG\Parameter( name="sort", in="query", description="排序编号", required=false, type="integer" ),
     *     @SWG\Parameter( name="pics[]", in="query", description="图片", required=true, type="string" ),
     *     @SWG\Parameter( name="pics_create_qrcode[]", in="query", description="图片是否生成小程序码 数组 和pics的key做对应 true:是 false:否", required=true, type="string" ),
     *     @SWG\Parameter( name="videos", in="query", description="视频", required=false, type="string" ),
     *     @SWG\Parameter( name="video_pic_url", in="query", description="视频封面", required=false, type="string" ),
     *     @SWG\Parameter( name="intro", in="query", description="图文详情", required=false, type="string" ),
     *     @SWG\Parameter( name="purchase_agreement", in="query", description="购买协议", required=false, type="string" ),
     *     @SWG\Parameter( name="enable_agreement", in="query", description="是否开启购买协议", required=false, type="boolean" ),
     *     @SWG\Parameter( name="type_labels", in="query", description="商品关联类型、数值属性", required=false, type="string" ),
     *     @SWG\Parameter( name="is_point", in="query", description="开启积分兑换 true false", required=false, type="string" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Parameter( name="point", in="query", description="积分个数", required=false, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function updateItems($item_id, Request $request)
    {
        $params = $request->input();
        $params['item_id'] = $item_id;
        $params['origincountry_id'] = $request->input('origincountry_id', 0);
        $params['taxstrategy_id'] = $request->input('taxstrategy_id', 0);
        $params['taxation_num'] = $request->input('taxation_num', 0);
        $isSupplierGoods = $request->input('supplier_id', 0);
        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $rules = [
            'item_id' => ['required|integer|min:1', '请确认您所编辑的商品是否存在'],
            'consume_type' => ['in:every,all,notconsume', '核销类型参数不正确'],
            'item_name' => ['required', '商品名称必填'],
            'sort' => ['required|integer', '排序值必须为整数'],
            'pics' => ['required', '请上传商品图片'],
        ];

        if (isset($params['item_type']) && $params['item_type'] == 'normal') {
            $rules['templates_id'] = ['required', '运费模板必填'];
            $rules['brand_id'] = ['required', '请选择品牌'];
            if ($isSupplierGoods) {
                $rules['audit_status'] = ['required', '审核状态不能为空'];
            } else {
                $rules['item_category'] = ['required', '请选择销售分类'];
            }
        }
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['authorizer_appid'] = app('auth')->user()->get('authorizer_appid');
        $params['company_id'] = $companyId;

        #$pointMemberRuleService = new PointMemberRuleService();
        #$params['point'] = isset($params['point']) && $params['point'] ? $params['point']: $pointMemberRuleService->moneyToPoint($companyId, $params['price']);

        if($params['operator_type'] == 'supplier'){
            // 供应商提交审核 / 更新商品
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
            if ($params['audit_status'] != 'processing') {
                $params['audit_status'] = 'submitting';
            }
            $SupplierItemsService = new SupplierItemsService();
            $result = $SupplierItemsService->addItems($params);
        } elseif ($isSupplierGoods) {
            // 平台审核 供应商商品
            $SupplierItemsService = new SupplierItemsService();
            if ($params['audit_status'] == 'approved') {
                $result = $SupplierItemsService->addItems($params);
            }
            $result = $SupplierItemsService->reviewGoods($params, $item_id);
        } else {
            //平台，店铺商品。只更新商品主表，不需要更新供应商表数据
            $itemsService = new ItemsService();
            $result = $itemsService->addItems($params);
        }

//        $service = new MultiLangService();
//        $service->addMultiLangByParams($result['item_id'],$params,'items');

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/goods/set_commission_ratio",
     *     summary="设置商品佣金费率",
     *     tags={"商品"},
     * )
     */
    public function setCommissionRatio(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $goods_id = $request->input('goods_id', 0);
        $commission_ratio = floatval($request->input('commission_ratio', 0));
        if ($commission_ratio > 100 or $commission_ratio < 0) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.commission_ratio_invalid'));
        }
        $commission_ratio = round($commission_ratio, 2) * 100;

        $filter = [
            'company_id' => $company_id,
            'goods_id' => $goods_id,
        ];
        $itemsCommissionService = new ItemsCommissionService();
        if ($itemsCommissionService->repository->count($filter)) {
            $itemsCommissionService->repository->updateOneBy($filter, ['commission_ratio' => $commission_ratio]);
        } else {
            $itemsCommissionService->repository->create([
                'company_id' => $company_id,
                'goods_id' => $goods_id,
                'item_id' => 0,
                'commission_ratio' => $commission_ratio,
            ]);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/items/{item_id}",
     *     summary="删除商品",
     *     tags={"商品"},
     *     description="删除商品",
     *     operationId="deleteItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Response( response=204, description="成功返回结构"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteItems($item_id, Request $request)
    {
        $params['item_id'] = $item_id;
        $validator = app('validator')->make($params, [
            'item_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.delete_item_error'), $validator->errors());
        }
        $company_id = app('auth')->user()->get('company_id');
        $operator_type = app('auth')->user()->get('operator_type');
        if($operator_type == 'supplier') { 
            $itemsService = new SupplierItemsService();
        }else {
            $itemsService = new ItemsService();
        }
        $params = [
            'item_id' => $item_id,
            'company_id' => $company_id,
        ];
        $distributorId = $request->get('distributor_id', 0);
        $params['distributor_id'] = $distributorId;
        $result = $itemsService->deleteItems($params);

        return $this->response->array([
            'status' => $result,
        ]);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/items/{item_id}/response",
     *     summary="删除商品(返回删除状态)",
     *     tags={"商品"},
     *     description="删除商品",
     *     operationId="deleteItems",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Response( response=204, description="成功返回结构"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteItemsResponseData($item_id, Request $request)
    {
        $params['item_id'] = $item_id;
        $validator = app('validator')->make($params, [
            'item_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.delete_item_error'), $validator->errors());
        }
        $company_id = app('auth')->user()->get('company_id');

        $params = [
            'item_id' => $item_id,
            'company_id' => $company_id,
        ];
        $distributorId = $request->get('distributor_id', 0);
        $params['distributor_id'] = $distributorId;

        $itemsService = new ItemsService();
        $result = $itemsService->deleteItems($params);

        return $this->response->array([
            'status' => $result,
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/goods/items/{item_id}",
     *     summary="获取商品详情",
     *     tags={"商品"},
     *     description="获取商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="item_id", type="string", example="5030", description="商品id"),
     *                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                  @SWG\Property( property="store", type="string", example="978", description="商品库存"),
     *                  @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                  @SWG\Property( property="sales", type="string", example="null", description="商品销量"),
     *                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                  @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位"),
     *                  @SWG\Property( property="rebate_conf", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                  @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                  @SWG\Property( property="goods_id", type="string", example="5030", description="商品集合ID"),
     *                  @SWG\Property( property="brand_id", type="string", example="1350", description="品牌id"),
     *                  @SWG\Property( property="item_name", type="string", example="分摊低金额测试2", description="商品名称"),
     *                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                  @SWG\Property( property="item_bn", type="string", example="S5FD81EE6AA1DF", description="商品编码"),
     *                  @SWG\Property( property="brief", type="string", example="", description=""),
     *                  @SWG\Property( property="price", type="string", example="1", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                  @SWG\Property( property="goods_brand", type="string", example="测试498", description="商品品牌"),
     *                  @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                  @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                  @SWG\Property( property="regions_id", type="string", example="null", description="地区id(DC2Type:json_array)"),
     *                  @SWG\Property( property="brand_logo", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="sort", type="string", example="0", description="商品排序"),
     *                  @SWG\Property( property="templates_id", type="string", example="1", description="运费模板id"),
     *                  @SWG\Property( property="is_default", type="string", example="true", description=""),
     *                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                  @SWG\Property( property="default_item_id", type="string", example="5030", description="默认商品ID"),
     *                  @SWG\Property( property="pics", type="array",
     *                      @SWG\Items( type="string", example="http://bbctest.aixue7.com/image/1/2020/09/09/96fc8edccb64e946db67bdabc429b6fb25A1ucQJFYJgr9TwXVNMIlBfEeC0Ymq5", description=""),
     *                  ),
     *                  @SWG\Property( property="pics_create_qrcode", type="array", description="图片是否生成小程序码",
     *                      @SWG\Items( type="string", example="true", description=""),
     *                  ),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                  @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                  @SWG\Property( property="item_category", type="array",
     *                      @SWG\Items( type="string", example="1603", description=""),
     *                  ),
     *                  @SWG\Property( property="rebate_type", type="string", example="default", description="分佣计算方式"),
     *                  @SWG\Property( property="weight", type="string", example="10", description="商品重量"),
     *                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                  @SWG\Property( property="tax_rate", type="string", example="0", description="税率, 百分之～/100"),
     *                  @SWG\Property( property="created", type="string", example="1607999206", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1608882229", description="修改时间"),
     *                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                  @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                  @SWG\Property( property="purchase_agreement", type="string", example="", description="购买协议"),
     *                  @SWG\Property( property="intro", type="string", example="助力测试......", description="图文详情"),
     *                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                  @SWG\Property( property="profit_type", type="string", example="0", description=""),
     *                  @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                  @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                  @SWG\Property( property="type", type="string", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                  @SWG\Property( property="tdk_content", type="string", example="{'title':'1','mate_description':'2','mate_keywords':'3,3'}", description="tdk详情"),
     *                  @SWG\Property( property="itemId", type="string", example="5030", description=""),
     *                  @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                  @SWG\Property( property="itemName", type="string", example="分摊低金额测试2", description=""),
     *                  @SWG\Property( property="itemBn", type="string", example="S5FD81EE6AA1DF", description=""),
     *                  @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                  @SWG\Property( property="item_main_cat_id", type="string", example="5", description=""),
     *                  @SWG\Property( property="type_labels", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_pics", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_spec_desc", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_images", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attribute_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attr_values_custom", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_category_main", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="3", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="3", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="测试类目122", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="测试类目122", description=""),
     *                          @SWG\Property( property="parent_id", type="string", example="0", description="父级id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="3", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="11111", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="1", description="商品分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="12", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="4", description=""),
     *                                  @SWG\Property( property="category_id", type="string", example="4", description="分类id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="category_name", type="string", example="测试类目1-1", description="分类名称"),
     *                                  @SWG\Property( property="label", type="string", example="测试类目1-1", description="地区名称"),
     *                                  @SWG\Property( property="parent_id", type="string", example="3", description="父级id, 0为顶级"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                  @SWG\Property( property="path", type="string", example="3,4", description="路径"),
     *                                  @SWG\Property( property="sort", type="string", example="22222222222222", description="排序"),
     *                                  @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                                  @SWG\Property( property="goods_params", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="goods_spec", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="category_level", type="string", example="2", description="商品分类等级"),
     *                                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="15.56", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                                  @SWG\Property( property="children", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="5", description=""),
     *                                          @SWG\Property( property="category_id", type="string", example="5", description="商品分类id"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                          @SWG\Property( property="category_name", type="string", example="测试类目1-1-1", description="分类名称"),
     *                                          @SWG\Property( property="label", type="string", example="测试类目1-1-1", description="地区名称"),
     *                                          @SWG\Property( property="parent_id", type="string", example="4", description="父级id, 0为顶级"),
     *                                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                          @SWG\Property( property="path", type="string", example="3,4,5", description="路径"),
     *                                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                                          @SWG\Property( property="goods_params", type="string", example="2827", description="商品参数"),
     *                                          @SWG\Property( property="goods_spec", type="array",
     *                                              @SWG\Items( type="string", example="1346", description=""),
     *                                          ),
     *                                          @SWG\Property( property="category_level", type="string", example="3", description="商品分类等级"),
     *                                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                          @SWG\Property( property="crossborder_tax_rate", type="string", example="15.4", description="跨境税率，百分比，小数点2位"),
     *                                          @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                                          @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                                          @SWG\Property( property="level", type="string", example="2", description=""),
     *                                       ),
     *                                  ),
     *                                  @SWG\Property( property="level", type="string", example="1", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="level", type="string", example="0", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="videos_url", type="string", example="", description=""),
     *                  @SWG\Property( property="distributor_sale_status", type="string", example="true", description=""),
     *                  @SWG\Property( property="item_total_store", type="string", example="978", description=""),
     *                  @SWG\Property( property="distributor_info", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="origincountry_name", type="string", example="", description="产地国名称"),
     *                  @SWG\Property( property="origincountry_img_url", type="string", example="", description="产地国国旗"),
     *                  @SWG\Property( property="cross_border_tax", type="string", example="0", description="商品跨境税费"),
     *                  @SWG\Property( property="item_params_list", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="tagList", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="recommend_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsDetail($item_id, Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');

        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_detail_error'), $validator->errors());
        }
        $authorizer_appid = app('auth')->user()->get('authorizer_appid');

        $company = (new CompanysActivationEgo())->check($company_id);
        $operatorType = app('auth')->user()->get('operator_type');
        $distributor_id = $request->input('distributor_id', 0);
        $operate_source = $request->input('operate_source', 'platform');

        //用来区分平台端查看供应商商品
        $page_from = $request->input('page_from', '');

        if ($company['product_model'] == 'standard' && $operatorType == 'distributor' && $distributor_id > 0) {
            $distributorItemsService = new DistributorItemsService();
            $result = $distributorItemsService->getValidDistributorItemInfo($company_id, $item_id, $distributor_id, $authorizer_appid);
        } else {
            if ($operate_source == 'supplier' && !$page_from){
                $itemsService = new SupplierItemsService();
            } else {
                $itemsService = new ItemsService();
            }
            $result = $itemsService->getItemsDetail($item_id, $authorizer_appid, [], $company_id);
            if (!$result) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_exist'));
            }
        }
        // dd($result);

        if ($company_id != $result['company_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_info_error'));
        }

        $result['item_params_list'] = [];
        if (isset($result['attribute_ids']) && $result['attribute_ids']) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrList = $itemsAttributesService->getAttrList(array('attribute_id' => $result['attribute_ids']), 1, 100, ['attribute_id' => 'asc']);
            // dd($attrList);
            if($attrList['list']){
                foreach ($attrList['list'] as $row) {
                    if ($row['attribute_type'] == 'item_params') {
                        $result['item_params_list'][] = $row;
                    } elseif ($row['attribute_type'] == 'item_spec') {
                        foreach ($row['attribute_values']['list'] as &$attrVal) {
                            $attrVal['custom_attribute_value'] = $result['attr_values_custom'][$attrVal['attribute_value_id']] ?? null;
                        }
                        $result['item_spec_list'][] = $row;
                    }
                }
            }
            unset($result['attribute_ids']);
            unset($result['attr_values_custom']);
        }

        if ($operate_source == 'platform' && $result) {
            //获取推荐商品列表
            $itemsRecommendService = new ItemsRecommendService();
            $recommendFilter = [
                'main_item_id' => $item_id,
                'company_id' => $company_id,
            ];
            $items_recommend_list = $itemsRecommendService->getLists($recommendFilter);
            $result['recommend_items'] = $items_recommend_list ?? [];
            if ($result['recommend_items']) {
                foreach ($result['recommend_items'] as $key => $value) {
                    $value['itemId'] = $value['item_id'];
                    $value['itemName'] = $value['item_name'];
                    $result['recommend_items'][$key] = $value;
                }
            }
            //获取推荐商品列表 end

            //获取商品标签
            $itemsTagService = new ItemsTagsService();
            $tagFilter['item_id'] = $item_id;
            $tagFilter['company_id'] = $company_id;
            $tagList = $itemsTagService->getListTags($tagFilter);
            $result['tagList'] = $tagList['list'];
            //获取商品标签 end
        }

        foreach ($result['spec_items'] as $idx => $sp){
            usort($sp['item_spec'], function ($a, $b) {
                return $b['spec_id'] <=> $a['spec_id'];
            });
            $custom_spec_ids = [];
            $custom_spec_names = [];
            foreach ($sp['item_spec'] as $item_spec) {
                $custom_spec_ids[] = $item_spec['spec_value_id'];
                $custom_spec_names[] = $item_spec['spec_value_name'];
            }
            $custom_spec_ids = implode('-', $custom_spec_ids);
            $custom_spec_names = implode('、', $custom_spec_names);
            $sp['custom_spec_id'] = $custom_spec_ids;
            $sp['custom_spec_name'] = $custom_spec_names;
            $result['spec_items'][$idx] = $sp;
        }

        //获取translation
//        $result['translation'] = (new MultiLangService())->getTranslation($result['item_id'],'items');
        //后台也改成替换
        $result = (new MultiLangService())->getTranslationByLang($result,$result['item_id'],'items');
        if (!is_array($result['intro'])) {
            json_decode($result['intro']);

            if (json_last_error() === JSON_ERROR_NONE) {
                $result['intro'] = json_decode($result['intro'], true);
            }
        }
        
        // $result['intro'] = json_decode($result['intro'], true);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/warning_store",
     *     summary="设置商品预警库存",
     *     tags={"商品"},
     *     description="设置商品预警库存",
     *     operationId="setItemWarningStore",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="store",
     *         in="query",
     *         description="预警库存数量",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         required=false,
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
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function setItemWarningStore(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'store' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.min_stock_warning'));
        }

        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->get('distributor_id', 0);

        $operator_type = app('auth')->user()->get('operator_type');
        if($operator_type == 'supplier'){
            $supplier_id = app('auth')->user()->get('operator_id');
            $SupplierItemsService = new SupplierItemsService();
            $SupplierItemsService->setWarningStore($companyId, $inputData['store'], $supplier_id);
        } else {
            $itemStoreService = new ItemStoreService();
            $itemStoreService->setWarningStore($companyId, $inputData['store'], $distributorId);
        }

        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Post(
     *     path="/goods/setItemsTemplate",
     *     summary="更新商品运费模板",
     *     tags={"商品"},
     *     description="更新商品运费模板",
     *     operationId="setItemsTemplate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="templates_id",
     *         in="formData",
     *         description="运费模板id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id[0]",
     *         in="formData",
     *         description="商品id",
     *         required=true,
     *         type="string"
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
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function setItemsTemplate(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'templates_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_select_shipping_template'));
        }
        $companyId = app('auth')->user()->get('company_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $params['templates_id'] = $inputData['templates_id'];
        $params['company_id'] = $companyId;
        $params['item_id'] = $inputData['item_id'];
        if ($operator_type == 'supplier') {
            $SupplierItemsService = new SupplierItemsService();
            $SupplierItemsService->setItemsTemplate($params);
            // $supplierItemsService = new SupplierItemsService();
            // $supplierItemsService->setItemsTemplate($params);
        } else {
            $itemsService = new ItemsService();
            $itemsService->setItemsTemplate($params);
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/goods/setItemsSort",
     *     summary="更新商品排序",
     *     tags={"商品"},
     *     description="更新商品排序",
     *     operationId="setItemsSort",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="formData",
     *         description="排序编号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="formData",
     *         description="商品id",
     *         required=true,
     *         type="string"
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
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function setItemsSort(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'sort' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_fill_sort_number'));
        }
        $companyId = app('auth')->user()->get('company_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $params['company_id'] = $companyId;
        $params['item_id'] = $inputData['item_id'];
        $operate_source = $request->input('operate_source', '');
        if($operate_source == 'supplier'){
            $itemsService = new SupplierItemsService();
        }else{
            $itemsService = new ItemsService();
        }
        $itemsService->setItemsSort($params, $inputData['sort']);

        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\Post(
      *     path="/goods/rebateconf",
      *     summary="保存商品分销配置",
      *     tags={"商品"},
      *     description="保存商品分销配置",
      *     operationId="updateItemsRebateConf",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="rebateConf", in="query", description="商品id", required=true, type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="array",
      *                 @SWG\Items(
      *                     type="object",
      *                     @SWG\Property(property="status", type="integer"),
      *                 )
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function updateItemsRebateConf(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $rebateConf = $request->input('rebateConf');
        if ($rebateConf) {
            $rebateConf = json_decode($rebateConf, true);
        }

        $itemsService = new ItemsService();

        foreach ($rebateConf as $row) {
            $filter['item_id'] = $row['item_id'];
            unset($row['item_id']);

            // $rebateTask = $row['rebate_task'];
            // array_multisort( array_column( $rebateTask, 'filter' ), SORT_ASC, $rebateTask );
            // $row['rebate_task'] = $rebateTask;

            $params = $row;
            $itemsService->simpleUpdateBy($filter, ['rebate_conf' => json_encode($params), 'rebate_type' => $request->input('rebate_type', 'default')]);
        }
        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\Put(
      *     path="/goods/audit/items",
      *     summary="审核店铺商品",
      *     tags={"商品"},
      *     description="审核店铺商品",
      *     operationId="auditItems",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="goods_id", in="query", description="商品id", required=true, type="string"),
      *     @SWG\Parameter( name="audit_status", in="query", description="审核状态", required=true, type="string"),
      *     @SWG\Parameter( name="audit_reason", in="query", description="拒绝原因", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="status", type="string", example="true", description=""),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function auditItems(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['goods_id'] = $request->input('goods_id');

        $data['audit_status'] = $request->input('audit_status');
        $data['audit_reason'] = $request->input('audit_reason');

        $itemsService = new ItemsService();

        $itemsService->updateBy($filter, $data);
        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\Post(
      *     path="/goods/itemsupdate",
      *     summary="修改商品价格、库存、上下架状态",
      *     tags={"商品"},
      *     description="修改商品价格、库存、上下架状态",
      *     operationId="updateItemsPriceStoreStatus",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="string"),
      *     @SWG\Parameter( name="price", in="query", description="商品价格", required=false, type="string"),
      *     @SWG\Parameter( name="store", in="query", description="商品库存", required=false, type="string"),
      *     @SWG\Parameter( name="status", in="query", description="商品上下架状态", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="status", type="string", example="true", description=""),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */
    public function updateItemsPriceStoreStatus(Request $request)
    {
        $params = [];
        $filter = [];
        $operator_type = app('auth')->user()->get('operator_type');
        if ($request->input('price', null) !== null) {
            $params['price'] = $request->input('price');
            if (!is_numeric($params['price'])) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_price_format_error'));
            }
            $params['price'] = 100 * $params['price'];
        }
        $costPrice = floatval($request->input('cost_price', 0));
        if ($costPrice) {
            $params['cost_price'] = bcmul($costPrice, '100');
        }
        $marketPrice = floatval($request->input('market_price', 0));
        if ($marketPrice) {
            $params['market_price'] = bcmul($marketPrice, '100');
        }
        if ($request->input('store', null) !== null) {
            $params['store'] = (int)$request->input('store');
        }
        if ($request->input('rebate', null) !== null) {
            $params['rebate'] = (int)$request->input('rebate');
            if ($params['rebate'] === 1 && $operator_type == 'distributor') {
                // $params['rebate'] = 2; //店铺自己开启分销  业务员
            }
        }
        if ($request->input('rebate_type', null) !== null) {
            $params['rebate_type'] = $request->input('rebate_type');
        }
        if ($request->input('status', null) !== null) {
            $params['approve_status'] = $request->input('status');
        }

        if ($request->input('is_market', null) !== null) {
            $params['is_market'] = $request->input('is_market');
        }

        if ($request->input('audit_status', null) !== null) {
            $params['audit_status'] = $request->input('audit_status');
            //批量提交审核，只更新待提交的商品
            if ($params['audit_status'] == 'processing') {
                $filter['audit_status'] = ['submitting', 'rejected'];
            }
        }
        if (empty($params)) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.params_error'));
        }

        if ($request->input('goods_id', null)) {
            $filter['goods_id'] = $request->input('goods_id', null);
        } elseif ($request->input('item_id')) {
            $filter['item_id'] = $request->input('item_id');
        } else {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_specify_item'));
        }

//        if (empty($filter)) {
//            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_specified'));
//        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        // $operate_source = $request->input('operate_source', '');
        // if($operate_source == 'platform'){
        //     $itemsService = new ItemsService();
        // }elseif ($operate_source == 'supplier'){
        //     $itemsService = new SupplierItemsService();
        // }

        //供应商和其它端分开操作
        if ($operator_type == 'supplier') {
            //供应商端目前只支持1.批量提交审核，2.批量设置停售和开售
            $SupplierItemsService = new SupplierItemsService();
            $SupplierItemsService->batchUpdateItems($filter, $params);
        } else {
            $itemsService = new ItemsService();
            $itemsService->updateItemsPriceStoreStatus($filter, $params);
        }
        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Post(
     *     path="/goods/setItemsCategory",
     *     summary="更新商品分类",
     *     tags={"商品"},
     *     description="更新商品分类",
     *     operationId="setItemsCategory",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="formData",
     *         description="商品分类id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id[0]",
     *         in="formData",
     *         description="商品id",
     *         required=true,
     *         type="string"
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
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function setItemsCategory(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'item_id' => 'required',
            'category_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_select_item_and_category'));
        }
        $companyId = app('auth')->user()->get('company_id');
        if (!is_array($inputData['category_id']) || is_numeric($inputData['category_id'])) {
            $inputData['category_id'] = [$inputData['category_id']];
        }
        if (!is_array($inputData['item_id']) || is_numeric($inputData['item_id'])) {
            $inputData['item_id'] = [$inputData['item_id']];
        }
        $itemsService = new ItemsRelCatsService();
        $result = $itemsService->setItemsCategory($companyId, $inputData['item_id'], $inputData['category_id']);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/goods/items",
     *     summary="获取商品列表",
     *     tags={"商品"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Parameter( name="keywords", in="query", description="商品名称", type="string" ),
     *     @SWG\Parameter( name="category", in="query", description="商品分类", type="string" ),
     *     @SWG\Parameter( name="item_id[]", in="query", description="商品ID", type="integer" ),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型，services：服务商品，normal: 普通商品", type="string" ),
     *     @SWG\Parameter( name="audit_status", in="query", description="审核状态，approved成功 processing审核中 rejected审核拒绝", type="string" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Parameter( name="price_gt", in="query", description="价格大于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="price_lt", in="query", description="价格小于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="store_gt", in="query", description="库存大于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="store_lt", in="query", description="库存小于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="is_gift", in="query", description="是否赠品:是true;否false", required=false, type="string" ),
     *     @SWG\Parameter( name="is_taobao", in="query", description="是否淘宝商品:是true;否false", required=false, type="string" ),
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
     *                       ),
     *                  ),
     *                  @SWG\Property( property="warning_store", type="string", example="5", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsList(request $request)
    {
        $result = [
            'list' => [],
            'total_count' => 0,
        ];
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_list_error'), $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');

        // 判断是否跨境
        if ($request->input('type') !== null) {
            $params['type'] = $request->input('type');
        }
        $operate_source = $request->input('operate_source', 'platform');
        if($operate_source == 'platform'){
            $itemsService = new ItemsService();
            if (isset($inputData['item_source']) && $inputData['item_source']) {
                if ($inputData['item_source'] == 'supplier') {
                    $params['supplier_id|gte'] = 1;
                    // 供应商商品列表只显示审核通过的
                    $params['audit_status'] = 'approved';
                } else {
                    $params['supplier_id'] = 0;
                }
            }
        } else {
            $itemsService = new SupplierItemsService();
        }

        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $request->input('item_name');
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $request->input('consume_type');
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['is_market']) && $inputData['is_market'] != '') {
            $params['is_market'] = intval($inputData['is_market']);
        }

        $operator_type = app('auth')->user()->get('operator_type');
        if($operator_type == 'supplier'){
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            if (isset($inputData['supplier_id']) && $inputData['supplier_id']) {
                $params['supplier_id'] = $request->input('supplier_id');
            }
        }

        // if (isset($inputData['supplier_goods_bn']) && $inputData['supplier_goods_bn']) {
        //     $params['supplier_goods_bn'] = $request->input('supplier_goods_bn');
        // }
        if (isset($inputData['goods_bn']) && $inputData['goods_bn']) {
            $params['goods_bn'] = $request->input('goods_bn');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($inputData['keywords']);
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            unset($params['item_name|contains']);
            $itemIds = array_column($datalist, 'default_item_id');

            $params['brief|contains'] = trim($inputData['keywords']);
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            unset($params['brief|contains']);
            $itemIds = array_merge($itemIds, array_column($datalist, 'default_item_id'));

            if (!$itemIds) {
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }
        if (isset($inputData['is_gift'])) {
            $params['is_gift'] = ($inputData['is_gift'] == 'true') ? 1 : 0;
        }
        if ((!isset($params['is_gift']) || !$params['is_gift']) && isset($inputData['approve_status']) && $inputData['approve_status']) {
            $params['approve_status'] = $request->input('approve_status');
        }

        if (isset($inputData['is_taobao']) && $inputData['is_taobao']) {
            $params['is_taobao'] = 1;
        }

        $distributorService = new DistributorService();
        $operator_type = app('auth')->user()->get('operator_type');
        if ($operator_type == 'merchant') {
            $merchantId = app('auth')->user()->get('merchant_id');
            $distributorList = $distributorService->getLists(['is_valid' => 'true', 'company_id' => $params['company_id'], 'merchant_id' => $merchantId], 'distributor_id');
            if (!$distributorList) {
                return $this->response->array($result);
            }
            $params['distributor_id'] = array_column($distributorList, 'distributor_id');
        }

        $distributorId = $request->get('distributor_id');
        if ($distributorId == 'all_distributor') {
            // 获取所有有效、禁用的店铺
            $distributorList = $distributorService->getValidAndDisabledDistributor($params['company_id']);
            if (!empty($distributorList)) {
                if ($operator_type != 'merchant') {
                    $params['distributor_id'] = array_column($distributorList, 'distributor_id');
                }
            } else {
                return $this->response->array($result);
            }
        } elseif ($distributorId != null) {
            if ($operator_type == 'merchant') {
                if (is_array($distributorId)) {
                    $params['distributor_id'] = array_intersect($distributorId, $params['distributor_id']);
                } elseif ($distributorId > 0) {
                    if (in_array($distributorId, $params['distributor_id'])) {
                        $params['distributor_id'] = $distributorId;
                    } else {
                        $params['distributor_id'] = [];
                    }
                }
                if (!$params['distributor_id']) {
                    return $this->response->array($result);
                }
            } elseif ($operate_source == 'platform') {
                $params['distributor_id'] = $distributorId;
            }
        } else {
            //todo 平台端只能选择平台商品
            if ($operator_type == 'admin' || $operator_type == 'staff') {
                $params['distributor_id'] = 0;
            }
        }

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        $from_page = $request->get('from_page',false);
        if($from_page && $from_page == "goodsAudit"  ){
            $dId = $request->get('distributor_id',false);
            if(!$dId){
                //$params['distributor_id|gt'] = 0;
                unset($params['distributor_id']);
            }
        }

        if (isset($inputData['audit_status']) && $inputData['audit_status']) {
            //如果不是分销池商品审核
            if ($request->input('audit_status') == 'rebate') {
            } else {
                $params['audit_status'] = $request->input('audit_status');
            }
//            if ($distributorId) {
//                unset($params['distributor_id']);
//                $params['distributor_id|neq'] = 0;
//            }
        }

        // if((!isset($inputData['audit_status']) || !$inputData['audit_status']) && $operator_type != 'supplier'){
        //     $params['audit_status|neq'] = 'submitting';
        // }

        // 店务端的商品上下架状态
        if (!empty($inputData['distributor_approve_status'])) {
            if ($inputData['distributor_approve_status'] == 'onsale') {
                $params['approve_status'] = 'onsale';
                $params['audit_status'] = 'approved';
            } elseif ($inputData['distributor_approve_status'] == 'instock') {
                $params['or']['approve_status|neq'] = 'onsale';
                $params['or']['audit_status|neq'] = 'approved';
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0, 2, 3])) {
            $rebate = $request->input('rebate');
            // 获取分销配置
            $settingService = new SettingService();
            $config = $settingService->getConfig($params['company_id']);
            if ($config['goods'] == 'all') {
                if ($rebate == 0) {
                    return $this->response->array($result);
                }
            } else {
                $params['rebate'] = $rebate;
            }
        }

        if(isset($inputData['supplier_name']) && $inputData['supplier_name']){
            $_filter = [
                'supplier_name|like' => trim($inputData['supplier_name']),
            ];
            $supplierService = new SupplierService();
            $rsSupplier = $supplierService->repository->getLists($_filter);
            if (!$rsSupplier) {
                return $this->response->array($result);
            }
            $params['supplier_id'] = array_column($rsSupplier, 'operator_id');
        }

        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (!$distributorId) {
                unset($params['distributor_id']);
            }
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            if (is_array($inputData['main_cat_id'])) {
                $main_cat_id = array_pop($inputData['main_cat_id']);
            } else {
                $main_cat_id = $inputData['main_cat_id'];
            }
            $itemsCategoryService = new ItemsCategoryService();
            if (is_array($main_cat_id)) {
                $itemCategory = $main_cat_id;
                foreach ($main_cat_id as $catId) {
                    $itemCategory = array_merge($itemCategory, $itemsCategoryService->getMainCatChildIdsBy($catId, $params['company_id']));
                }
            } else {
                $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($main_cat_id, $params['company_id']);
                $itemCategory[] = $main_cat_id;
            }
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            if (is_array($inputData['category'])) {
                $itemCategory = array_pop($inputData['category']);
            } else {
                $itemCategory = $inputData['category'];
            }
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($itemCategory, $params['company_id']);
            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect((array)$params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
            if (!$params['item_id']) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if (isset($inputData['store_status']) &&  $inputData['store_status'] ) {
            $params["store|gt"] = 0;
        }

        if (isset($inputData['store_status']) &&  $inputData['store_status']  == 0) {
            $params["store|lt"] = 1;
        }

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["price|gt"] = bcmul($inputData['price_gt'], 100);
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["price|lt"] = bcmul($inputData['price_lt'], 100);
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        if ($operator_type == 'supplier') {
            $warningStore = $itemsService->getWarningStore($params['company_id'], $params['supplier_id']);
        } else {
            $itemStoreService = new ItemStoreService();
            $warningStore = $itemStoreService->getWarningStore($params['company_id'], $distributorId);
        }
        if (isset($inputData['is_warning']) && $inputData['is_warning'] == 'true') {
            $params['store|lte'] = $warningStore;
            // $itemsService = new ItemsService();
        }

        if (isset($inputData['tag_id']) && $inputData['tag_id']) {
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $params['company_id'], 'tag_id' => $inputData['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $filter['item_id'] = $params['item_id'];
            }
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $page = intval($inputData['page']);
        $pageSize = intval($inputData['pageSize']);
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn|contains'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            unset($params['item_bn|contains']);
            $itemIds = array_column($datalist, 'default_item_id');

            $params['barcode|contains'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            unset($params['barcode|contains']);
            $itemIds = array_merge($itemIds, array_column($datalist, 'default_item_id'));

            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if (isset($inputData['barcode']) && $inputData['barcode']) {
            $params['barcode'] = $inputData['barcode'];

            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');

            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['barcode']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $isGetSkuList = true;
        } else {
            $isGetSkuList = false;
            $params['is_default'] = true;
        }

        // 药品商品筛选
        if (isset($inputData['is_medicine']) && $inputData['is_medicine'] !== '') {
            $params['is_medicine'] = (int)$inputData['is_medicine'];
        }
        if (isset($inputData['is_prescription']) && $inputData['is_prescription'] !== '') {
            $params['is_prescription'] = (int)$inputData['is_prescription'];
        }

        $merchantId = app('auth')->user()->get('merchant_id',0);
        $operatorType = app('auth')->user()->get('operator_type','');

        if ($operatorType == 'merchant' && !$distributorId) {
            $filter_distributor = array();
            $filter_distributor['company_id'] = $params['company_id']; // 商户端只能获取商户的店铺
            $filter_distributor['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
            $distributorService = new DistributorService();
            $distributorList =  $distributorService->lists($filter_distributor,['created' => 'desc'],10000,1,false,"distributor_id");
            $shopIds = array_column($distributorList['list'],'distributor_id');
            $params['distributor_id'] = $shopIds;
        }
        // app('log')->info(':items:salesperson:'.__FUNCTION__.__LINE__.':params:' . json_encode($params));

        $pathSource = $request->get('pathSource',false);
        if($pathSource == '/sellers/popularize/popularizegoods' && !$distributorId){
            $params["distributor_id|gt"] = 0;
            unset($params['distributor_id']);
        }
        if ($isGetSkuList) {
            if (isset($params['item_id']) && $params['item_id']) {
                $params['default_item_id'] = $params['item_id'];
                unset($params['item_id']);
                $pageSize = -1;
            }
            unset($params['distributor_id']);
            $result = $itemsService->getSkuItemsList($params, $page, $pageSize);
        } else {
            $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
            $result = $itemsService->getItemsList($params, $page, $pageSize);
        }
        $result = $itemsService->dealListStore($result);

        $result['warning_store'] = $warningStore;
        $result['filter'] = $params;

        if ($result['list']) {
            //营销标签
            if (method_exists($itemsService, 'getItemsListActityTag')) {
                $result = $itemsService->getItemsListActityTag($result, $params['company_id']);
            }

            $itemIds = array_column($result['list'], 'item_id');

            // $distributorIds = array_column($result['list'], 'distributor_id');
            // $DistributorService = new DistributorService();
            // $filter_d = array('distributor_id' => $distributorIds ,'company_id' =>  $params['company_id'] );
            // $data_distributor = $DistributorService->lists($filter_d, ["created" => "DESC"], 1000, 1);
            // $data_distributor_obj = array_column($data_distributor['list'],null, 'distributor_id');

            //获取商品标签
            $tagFilter = [
                'item_id' => $itemIds,
                'company_id' => $params['company_id'],
            ];
            $itemsTagsService = new ItemsTagsService();
            $tagList = $itemsTagsService->getItemsRelTagList($tagFilter);
            foreach ($tagList as $tag) {
                $newTags[$tag['item_id']][] = $tag;
            }

            $supplier_ids = array_column($result['list'], 'supplier_id');
            $operators = [];
            if($supplier_ids){
                $o_filter = [
                    'operator_id' => $supplier_ids,
                    'company_id' => $params['company_id'],
                ];
                $operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
                $operator = $operatorsRepository->lists($o_filter);
                $operators = array_column($operator['list'], null,'operator_id');
            }

            if ($supplier_ids) {
                $supplierService = new SupplierService();
                $rs = $supplierService->repository->getLists(['operator_id' => $supplier_ids], 'operator_id, supplier_name');
                $supplierData = array_column($rs, null, 'operator_id');
            }

            $itemsCommissionService = new ItemsCommissionService();
            $commissionRatio = $itemsCommissionService->getCommissionRatio(array_column($result['list'], 'goods_id'));

            //获取店铺信息
            $distributorData = [];
            $distributorIds = array_filter(array_column($result['list'], 'distributor_id'));
            if ($distributorIds) {
                $distributorService = new DistributorService();
                $rs = $distributorService->entityRepository->getLists(['distributor_id' => $distributorIds], 'name, distributor_id');
                $distributorData = array_column($rs, null, 'distributor_id');
            }

            $itemsCategoryService = new ItemsCategoryService();
            foreach ($result['list'] as &$value) {
                $value['distributor_id'] = $value['distributor_id'] ?? 0;
                $value['rebate'] = (string)($value['rebate'] ?? '');
                $value['tagList'] = $newTags[$value['item_id']] ?? [];
                $categoryInfo = $itemsCategoryService->getInfoById($value['item_main_cat_id']);
                $value['itemMainCatName'] = $categoryInfo['category_name'] ?? '';
                $value['operator_name'] = $operators[$value['supplier_id']]['username'] ?? '';
                $value['distributor_name'] = $distributorData[$value['distributor_id']] ?? [];
                $value['item_holder'] = $value['supplier_id'] ? 'supplier' : 'self';
                $value['supplier_name'] = $supplierData[$value['supplier_id']]['supplier_name'] ?? '';
                //毛利率=（销售价格/(1+税率）-成本价/(1+税率））/（销售价格/(1+税率）
                // $price = bcdiv($value['price'],'100',10);//销售价格
                // $cost_price = bcdiv($value['cost_price'],'100',10); //成本格
                // $tax_rate = bcdiv($value['tax_rate'],'100',10) + 1; //税率
                if ($value['cost_price'] && ($value['price'] > $value['cost_price'])) {
                    $gross_profit_rate = ($value['price'] - $value['cost_price']) / $value['price'];
                    $value['gross_profit_rate'] = bcmul($gross_profit_rate,'100',2).'%';
                } else {
                    $value['gross_profit_rate'] = '-';
                }

                $cat_arr = [];
                foreach (($value['item_cat_id'] ?? []) as &$v) {
                    $cat_info = $itemsCategoryService->getInfoById($v);
                    if ($cat_info) {
                        $cat_arr[] = '['.$cat_info['category_name'].']';
                    }
                }
                $value['itemCatName'] = $cat_arr;
                $value['commission_ratio'] = $commissionRatio[$value['goods_id']] ?? 0;
            }
        }

        // 药品数据
        $itemsMedicineService = new ItemsMedicineService();
        $result['list'] = $itemsMedicineService->getItemsMedicineData($result['list']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/goods/sku",
     *     summary="获取商品列表",
     *     tags={"商品"},
     *     description="获取商品列表",
     *     operationId="getSkuList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Parameter( name="keywords", in="query", description="商品名称", type="string" ),
     *     @SWG\Parameter( name="category", in="query", description="商品分类", type="string" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer" ),
     *     @SWG\Parameter( name="price_gt", in="query", description="价格大于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="price_lt", in="query", description="价格小于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="store_gt", in="query", description="库存大于指定值", required=false, type="integer" ),
     *     @SWG\Parameter( name="store_lt", in="query", description="库存小于指定值", required=false, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="832", description="商品id"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                          @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                          @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                          @SWG\Property( property="store", type="string", example="91", description="商品库存"),
     *                          @SWG\Property( property="barcode", type="string", example="4902505568404", description="商品条形码"),
     *                          @SWG\Property( property="sales", type="string", example="0", description="商品销量"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                          @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位"),
     *                          @SWG\Property( property="rebate_conf", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                          @SWG\Property( property="is_point", type="string", example="true", description="是否积分兑换 true可以 false不可以"),
     *                          @SWG\Property( property="point", type="string", example="null", description="积分"),
     *                          @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                          @SWG\Property( property="goods_id", type="string", example="832", description="商品集合ID"),
     *                          @SWG\Property( property="brand_id", type="string", example="192", description="品牌id"),
     *                          @SWG\Property( property="item_name", type="string", example="百乐0.7mmJuice百果乐啫喱笔", description="商品名称"),
     *                          @SWG\Property( property="item_unit", type="string", example="支", description="商品计量单位"),
     *                          @SWG\Property( property="item_bn", type="string", example="3510045926", description="商品编号"),
     *                          @SWG\Property( property="brief", type="string", example="LJU-10F-KO荧", description="简介"),
     *                          @SWG\Property( property="price", type="string", example="800", description="商品价格"),
     *                          @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                          @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                          @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                          @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                          @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                          @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                          @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                          @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                          @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                          @SWG\Property( property="regions_id", type="string", example="null", description="地区id(DC2Type:json_array)"),
     *                          @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="templates_id", type="string", example="53", description="运费模板id"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认"),
     *                          @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                          @SWG\Property( property="default_item_id", type="string", example="832", description="默认商品ID"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="http://test.test.com/1/2019/10/11/165f79ad44fa985d2544", description=""),
     *                          ),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                          @SWG\Property( property="date_type", type="string", example="", description="有效期的类型"),
     *                          @SWG\Property( property="item_category", type="string", example="388", description="商品主类目"),
     *                          @SWG\Property( property="rebate_type", type="string", example="default", description="返佣模式"),
     *                          @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                          @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                          @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                          @SWG\Property( property="tax_rate", type="string", example="0", description="税率"),
     *                          @SWG\Property( property="created", type="string", example="1570760498", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1592896708", description="修改时间"),
     *                          @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                          @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                          @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                          @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                          @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                          @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                          @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                          @SWG\Property( property="profit_type", type="string", example="0", description=""),
     *                          @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                          @SWG\Property( property="is_profit", type="string", example="false", description="是否支持分润"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                          @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                          @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                          @SWG\Property( property="type", type="string", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                          @SWG\Property( property="tdk_content", type="string", example="null", description="tdk详情"),
     *                          @SWG\Property( property="itemId", type="string", example="832", description=""),
     *                          @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                          @SWG\Property( property="itemName", type="string", example="百乐0.7mmJuice百果乐啫喱笔", description=""),
     *                          @SWG\Property( property="itemBn", type="string", example="3510045926", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="item_main_cat_id", type="string", example="388", description=""),
     *                          @SWG\Property( property="item_cat_id", type="array",
     *                              @SWG\Items( type="string", example="410", description=""),
     *                          ),
     *                          @SWG\Property( property="type_labels", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="tagList", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="itemMainCatName", type="string", example="中性笔", description=""),
     *                          @SWG\Property( property="itemCatName", type="array",
     *                              @SWG\Items( type="string", example="[热点商品]", description=""),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="warning_store", type="string", example="5", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getSkuList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_list_error'), $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        // 判断是否跨境
        if ($request->input('type') !== null) {
            $params['type'] = $request->input('type');
        }
        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $request->input('item_name');
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $request->input('consume_type');
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($request->input('keywords'));
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        if (isset($inputData['is_gift'])) {
            $params['is_gift'] = ($inputData['is_gift'] == 'true') ? 1 : 0;
        }
        if ((!isset($params['is_gift']) || !$params['is_gift']) && isset($inputData['approve_status']) && $inputData['approve_status']) {
            $params['approve_status'] = $request->input('approve_status');
        }

        $distributorId = $request->get('distributor_id') ?: $request->input('distributor_id', 0);
        $params['distributor_id'] = $distributorId;

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['audit_status']) && $inputData['audit_status']) {
            //如果不是分销池商品审核
            if ($request->input('audit_status') == 'rebate') {
            } else {
                $params['audit_status'] = $request->input('audit_status');
            }
//            if (!$distributorId) {
//                unset($params['distributor_id']);
//                $params['distributor_id|neq'] = 0;
//            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $request->input('rebate');
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (!$distributorId) {
                unset($params['distributor_id']);
            }
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($inputData['main_cat_id'], $params['company_id']);
            $itemCategory[] = $inputData['main_cat_id'];
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $params['company_id']);
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

        $params['item_type'] = $request->input('item_type', 'services');

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["price|gt"] = bcmul($inputData['price_gt'], 100);
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["price|lt"] = bcmul($inputData['price_lt'], 100);
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        $itemStoreService = new ItemStoreService();
        $warningStore = $itemStoreService->getWarningStore($params['company_id'], $distributorId);
        if (isset($inputData['is_warning']) && $inputData['is_warning'] == 'true') {
            $params['store|lte'] = $warningStore;
        }

        if (isset($inputData['tag_id']) && $inputData['tag_id']) {
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $params['company_id'], 'tag_id' => $inputData['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $filter['item_id'] = $params['item_id'];
            }
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $page = intval($inputData['page']);
        $pageSize = intval($inputData['pageSize']);
        $operate_source = $request->input('operate_source', 'platform');
        if($operate_source == 'platform'){
            $itemsService = new ItemsService();
        }elseif ($operate_source == 'supplier'){
            $itemsService = new SupplierItemsService();
        }
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $isGetSkuList = true;
        } else {
            $isGetSkuList = false;
            $params['is_default'] = true;
        }

        if ($isGetSkuList) {
            if (isset($params['item_id']) && $params['item_id']) {
                $pageSize = -1;
            }
            $result = $itemsService->getSkuItemsList($params, $page, $pageSize);
        } else {
            $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
            $result = $itemsService->getItemsList($params, $page, $pageSize);
        }

        $result['warning_store'] = $warningStore;

        if ($result['list']) {
            //营销标签
            $result = $itemsService->getItemsListActityTag($result, $params['company_id']);
            //获取商品标签
            $itemIds = array_column($result['list'], 'item_id');
            $tagFilter = [
                'item_id' => $itemIds,
                'company_id' => $params['company_id'],
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
     * @SWG\Get(
     *     path="/goods/distributionGoodsWxaCodeStream",
     *     summary="获取商品分销二维码",
     *     tags={"商品"},
     *     description="获取商品分销二维码",
     *     operationId="getDistributionGoodsWxaCodeStream",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="分销商id", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema( @SWG\Property( property="data", type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="item_id", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getDistributionGoodsWxaCodeStream(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'item_id' => 'required|min:1',
            'distributor_id' => 'required|min:1',
            // 'wxaappid' => 'required|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_miniapp_code_params_error'), $validator->errors());
        }
        $weappService = new WeappService();
        $companyId = app('auth')->user()->get('company_id');
        $wxaappid = $weappService->getWxappidByTemplateName($companyId);
        if (!$wxaappid) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.miniapp_not_opened'), $validator->errors());
        }
        $operate_source = $request->input('operate_source', '');
        if($operate_source == 'platform'){
            $itemsService = new ItemsService();
        }elseif ($operate_source == 'supplier'){
            $itemsService = new SupplierItemsService();
        }
        $result = $itemsService->getDistributionGoodsWxaCode($wxaappid, $params['item_id'], $params['distributor_id']);
        return response($result)->header('content-type', 'image/jpeg');
    }

    /**
     * @SWG\Post(
     *     path="/goods/itemstoreupdate",
     *     summary="修改商品库存",
     *     tags={"商品"},
     *     description="修改商品库存",
     *     operationId="batchUpdateItemStore",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="items", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function batchUpdateItemStore(Request $request)
    {
        if (!$request->get('items')) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_specified'));
        }
        $companyId = app('auth')->user()->get('company_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $params = $request->get('items');
        if (!is_array($params)) {
            $params = json_decode($params, true);
        }
        $rules = [
            '*.item_id' => ['required', '商品id必填'],
            '*.store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if ($operator_type == 'supplier') {
            $SupplierItemsService = new SupplierItemsService();
            $SupplierItemsService->updateItemsStore($companyId, $params);
        } else {
            $itemsService = new ItemsService();
            $result = $itemsService->updateItemsStore($companyId, $params);
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/goods/itemstatusupdate",
     *     summary="商品状态更改",
     *     tags={"商品"},
     *     description="测试描述",
     *     operationId="batchUpdateItemStatus",
     *     @SWG\Parameter( in="formData", type="string", required=true, name="items", description="商品id（数组）" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="query", description="选中查询信息" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="status", description="状态" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones")))
     * )
     */
    public function batchUpdateItemStatus(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $input_data = $request->input();
        $operate_source = $request->input('operate_source', '');
        if ($operate_source == 'supplier'){
            $itemsService = new SupplierItemsService();
        } else {
            $itemsService = new ItemsService();
        }
        if ($request->get('query')) {
            $query = $input_data['query'];
            if (!is_array($query)) {
                $query = json_decode($query, true);
            }
            $params = $itemsService->exportParams($query, $companyId);
            if ($params === false) {
                return $this->response->array(['status' => true]);
            }
            unset($params['isGetSkuList']);
            $input_data['items'] = $itemsService->getLists($params, 'goods_id');
        } elseif (!$request->get('items')) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_specified'));
        }
        $items = $input_data['items'];

        if (!is_array($items)) {
            $items = json_decode($items, true);
        }
        $rules = [
            'items.*.goods_id' => ['required', '商品id必填'],
            'status' => ['required', '状态必填'],
        ];
        $errorMessage = validator_params($input_data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = $itemsService->updateItemsStatus($companyId, $items, $input_data['status']);
        return $this->response->array(['status' => true]);
    }

    public function getGoodsByCoupon(Request $request, $coupon_id)
    {
        $company_id = app('auth')->user()->get('company_id');

        if (!$coupon_id) {
            $items = [
                'data' => [
                    'total_count' => 0,
                    'list' => []
                ]
            ];
            return $this->response->array($items);
        }

        $item_service = new ItemsService();
        $result = $item_service->getGoodsByCoupon($company_id, $coupon_id);

        return $this->response->array($result);
    }

    /**
    * @SWG\Post(
    *     path="/goods/keywords",
    *     summary="设置关键词",
    *     tags={"商品"},
    *     description="description",
    *     operationId="setKeywords",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="distributor_id", in="query", description="经销商ID,默认0", required=false, type="string"),
    *     @SWG\Parameter( name="id", in="query", description="id 编辑时需要", required=false, type="string"),
    *     @SWG\Parameter( name="content", in="body", description="关键词内容", required=true, type="object",
    *         @SWG\Schema()
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
    *                 )
    *             ),
    *          ),
    *     ),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
    * )
    */
    public function setKeywords(Request $request)
    {
        $input_data = $request->input();
        $validator = app('validator')->make($request->all(), [
            'content' => 'required|string',
            'distributor_id' => 'integer',
            'id' => 'integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.params_required'), $validator->errors());
        }
        $input_data['company_id'] = app('auth')->user()->get('company_id');
        $input_data['distributor_id'] = $input_data['distributor_id'] ?? 0;
        $keywordsService = new KeywordsService();
        $result = $keywordsService->addKeywords($input_data);
        return $this->response->array($result);
    }

    /**
    * @SWG\Get(
    *     path="/goods/keywordsDetail",
    *     summary="获取关键词详情",
    *     tags={"商品"},
    *     description="description",
    *     operationId="getKeyWordsByShop",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="id", in="query", description="id", required=true, type="string"),
    *     @SWG\Response(
    *         response=200,
    *         description="成功返回结构",
    *         @SWG\Schema(
    *             @SWG\Property(
    *                 property="data",
    *                 type="array",
    *                 @SWG\Items(
    *                     type="object",
    *                 )
    *             ),
    *          ),
    *     ),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
    * )
    */

    public function getKeyWordsDetail(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.param_error'), $validator->errors());
        }
        $keywordsService = new KeywordsService();
        $result = $keywordsService->getInfoById($request->get('id'));
        return $this->response->array($result);
    }

    /**
    * @SWG\Get(
    *     path="/goods/keywords",
    *     summary="获取关键词",
    *     tags={"商品"},
    *     description="description",
    *     operationId="getKeywords",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="distributor_id", in="query", description="经销商ID,默认0", required=false, type="string"),
    *     @SWG\Parameter( name="content", in="query", description="内容(模糊匹配)", required=false, type="string"),
    *     @SWG\Parameter( name="page", in="query", description="", required=true, type="string"),
    *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", required=true, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2", description=""),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="content", type="string", example="测试1", description="内容"),
     *                       ),
     *                  ),
     *          ),
     *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
    * )
    */
    public function getKeywords(Request $request)
    {
        $params = $request->input();
        $keywordsService = new KeywordsService();
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $distributor_id = $request->input('distributor_id');
        $content = $request->input('content');
        if ($distributor_id ?? 0) {
            $filter['distributor_id'] = $distributor_id;
        }
        if (isset($content) && $content) {
            $filter['content|contains'] = $content;
        }
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);
        $result = $keywordsService->lists($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
    * @SWG\Delete(
    *     path="/goods/keywords/{id}",
    *     summary="删除关键词",
    *     tags={"商品"},
    *     description="description",
    *     operationId="delKeywords",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="id", in="query", description="id", required=true, type="string"),
    *     @SWG\Response(
    *         response=200,
    *         description="成功返回结构",
    *         @SWG\Schema(
    *             @SWG\Property(
    *                 property="data",
    *                 type="array",
    *                 @SWG\Items(
    *                     type="object",
    *                 )
    *             ),
    *          ),
    *     ),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
    * )
    */

    public function delKeywords($id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;
        $keywordsService = new KeywordsService();
        $result = $keywordsService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/goods/itemsisgiftupdate",
     *     summary="修改商品为赠品",
     *     tags={"商品"},
     *     description="修改商品为赠品",
     *     operationId="batchUpdateItemIsgift",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="items", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态，true：赠品，false：非赠品。", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function batchUpdateItemIsgift(Request $request)
    {
        if (!$request->get('item_id')) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_specified'));
        }
        $companyId = app('auth')->user()->get('company_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $input_data = $request->input();
        $item_id = $input_data['item_id'];
        $rules = [
            'item_id' => ['required', '商品id必填'],
            'status' => ['required|' . Rule::in(['true', 'false']), '状态必填,且必须是 true 或 false '],
        ];
        $errorMessage = validator_params($input_data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if ($operator_type == 'supplier') {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.supplier_not_support_operation'));
            // $supplierItemsService = new SupplierItemsService();
            // $result = $supplierItemsService->batchUpdateItemGift($companyId, $item_id, $input_data['status']);
        }

        $itemsService = new ItemsService();
        $result = $itemsService->batchUpdateItemGift($companyId, $item_id, $input_data['status']);
        return $this->response->array(['status' => true]);
    }


    public function getOnsaleItemsList(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_list_error'), $validator->errors());
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['distributor_id'] = $params['distributor_id'] ?? 0;
        $filter['type'] = 0;
        $filter['is_gift'] = 0;
        $filter['approve_status'] = ['onsale', 'offline_sale'];
        $filter['audit_status'] = 'approved';
        $filter['item_type'] = 'normal';

        if (isset($params['keywords']) && $params['keywords']) {
            $filter['or'] = [
                'item_name|contains' => $params['keywords'],
                'item_bn' => $params['keywords'],
                'barcode' => $params['keywords'],
            ];
        }

        if (isset($params['item_name']) && $params['item_name']) {
            $filter['item_name|contains'] = $params['item_name'];
        }

        if (isset($params['item_bn']) && $params['item_bn']) {
            $filter['item_bn'] = $params['item_bn'];
        }

        if (isset($params['barcode']) && $params['barcode']) {
            $filter['barcode'] = $params['barcode'];
        }

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);

        $itemsService = new ItemsService();
        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        if ($filter['distributor_id'] == 0 || $company['product_model'] == 'platform') {
            $result = $itemsService->getSkuItemsList($filter, $page, $pageSize);
        } else {
            $result = $itemsService->getDistributorSkuItemsList($filter, $page, $pageSize);
        }

        // 如果是推广员不需要计算会员价
        if ($result['list']) {
            // 计算会员价
            $result = $itemsService->getItemsListMemberPrice($result, 0, $filter['company_id']);
        }
        //营销标签
        $result = $itemsService->getItemsListActityTag($result, $filter['company_id']);

        return $this->response->array($result);
    }

    /**
     * 同步药品数据
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function syncMedicine(Request $request)
    {
        $params = $request->all(['goods_id']);
        $rules = [
            'goods_id' => ['required', '请选择要同步的商品'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        if (!is_array($params['goods_id'])) {
            $params['goods_id'] = explode(',', $params['goods_id']);
        }
        $itemsMedicineService = new ItemsMedicineService();
        $itemsMedicineService->syncMedicine($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * 创建商品参数
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function createItemsParams(Request $request)
    {
        $params = $request->all();
        $rules = [
            'attribute_id' => ['required', '必须填写attribute_id'],
            'attribute_value' => ['required', '必须填写attribute_value'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesInfo = $itemsAttributesService->getInfo(['company_id' => $params['company_id'], 'attribute_id' => $params['attribute_id']]);
        if (!$itemsAttributesInfo) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.attribute_not_exist').$params['attribute_id']);
        }
        $data = [];
        if (isset($params['sort'])) {
            $data['sort'] = $params['sort'];
        }
        if (isset($params['image_url'])) {
            $data['image_url'] = $params['image_url'];
        }
        if (isset($params['shop_id'])) {
            $data['shop_id'] = $params['shop_id'];
        }
        $data['attribute_value'] = $params['attribute_value'];
        $filter = [
          'company_id' => $params['company_id'],
          'attribute_id' => $params['attribute_id'],
        ];
        $result = $itemsAttributesService->createItemsAttributesItemParams($filter, $data);

        return $this->response->array(['status' => $result ? true : false]);
    }


    //同步淘宝商品--增量（时间开始--结束时间）
    public function syncTbItems(Request $request)
    {
        $params = $request->all();
        $itemsService = new TbItemsService();
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ]);

        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.get_items_increment_data_error'), $validator->errors());
        }

        $tbInterItemsService = new TbInterItemsService();
        $tbInterItemsService->getTbIncrItems($params);

        return $this->response->array(['status' => true]);
    }
}
