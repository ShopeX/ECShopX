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

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\LimitSaleItemUploadService;
use DistributionBundle\Services\DistributorService;

class LimitPromotions extends Controller
{
    /**
     * @SWG\Get(
     *     path="/promotions/limit",
     *     summary="获取限购活动列表",
     *     tags={"营销"},
     *     description="获取限购活动列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="限购商品状态 waiting:未开始 ongoing:进行中 end:已结束", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数,默认1", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页条数,默认20", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="67", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/LimitBase"
     *                      ),
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        // Built with ShopEx Framework
        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $status = $request->input('status');
        $limitService = new LimitService();
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $sourceId = $request->get('distributor_id', 0);//如果是平台，这里是0
        $sourceType = $authInfo['operator_type'];//如果是平台，这里是admin

        $result = $limitService->getLimitList($companyId, $status, $sourceType, $sourceId, $page, $pageSize);

        $result = $this->__getSourceName($result);//获取店铺名称

        if ($result['list']) {
            foreach ($result['list'] as &$value) {
                if ($value['source_id'] != $sourceId) {
                    if ($value['source_type'] == 'staff' && $sourceId == 0) {
                        $value['edit_btn'] = 'Y';//平台子账号创建的促销，超管可以编辑
                    } else {
                        $value['edit_btn'] = 'N';//屏蔽编辑按钮，平台只能编辑自己的促销
                    }
                } else {
                    $value['edit_btn'] = 'Y';
                }
            }
        }

        return $this->response->array($result);
    }

    private function __getSourceName($result = [])
    {
        $distributorIds = [];
        $sourceName = [
            'distributor' => []
        ];
        foreach ($result['list'] as $v) {
            if ($v['source_type'] == 'distributor') {
                $distributorIds[] = $v['source_id'];
            }
        }
        if ($distributorIds) {
            $distributorService = new DistributorService();
            $rs = $distributorService->getLists(['distributor_id' => $distributorIds], 'distributor_id,name');
            if ($rs) {
                $sourceName['distributor'] = array_column($rs, 'name', 'distributor_id');
            }
        }

        foreach ($result['list'] as $k => $v) {
            $source_name = '';
            if (isset($sourceName[$v['source_type']][$v['source_id']])) {
                $source_name = $sourceName[$v['source_type']][$v['source_id']];
            }
            $result['list'][$k]['source_name'] = $source_name;
        }
        return $result;
    }

    /**
     * @SWG\Get(
     *     path="/promotions/limit_items/{limitId}",
     *     summary="获取限购商品列表",
     *     tags={"营销"},
     *     description="获取限购商品列表",
     *     operationId="list_items",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_bn", in="query", description="商品货号", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数,默认1", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页条数,默认20", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="67", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/LimitBase"
     *                      ),
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getLimitItems($limitId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $limitService = new LimitService();
        $pageSize = intval($request->input('pageSize', 20));
        $page = intval($request->input('page', 1));
        $itemBn = $request->input('item_bn', '');
        $itemType = 'normal';//导入的店铺限购商品
        $result = $limitService->getLimitItemList($companyId, $limitId, $itemType, $itemBn, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotions/limit_items/{limitId}",
     *     summary="删除限购商品",
     *     tags={"营销"},
     *     description="删除限购商品",
     *     operationId="delete_limit_item",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="67", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/LimitBase"
     *                      ),
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function deleteLimitItem($limitId, Request $request)
    {
        $params = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');

        $rules = [
            'item_id' => ['required|integer|min:1', trans('PromotionsBundle.product_id_error')],
            'distributor_id' => ['required|integer|min:1', trans('PromotionsBundle.store_id_error')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $limitService = new LimitService();
        $itemId = $params['item_id'];
        $distributorId = $params['distributor_id'];
        $result = $limitService->deleteLimitItem($params['company_id'], $distributorId, $itemId);

        return $this->response->array([$result]);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/limit_items/{limitId}",
     *     summary="更新限购商品数量",
     *     tags={"营销"},
     *     description="更新限购商品数量",
     *     operationId="update_limit_item",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=true, type="string"),
     *     @SWG\Parameter( name="limit_num", in="query", description="商品限购数量", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="67", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/LimitBase"
     *                      ),
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateLimitItem($limitId, Request $request)
    {
        $params = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');

        $rules = [
            'limit_num' => ['required|integer|min:1', trans('PromotionsBundle.limit_quantity_min_1')],
            'item_id' => ['required|integer|min:1', trans('PromotionsBundle.product_id_error')],
            'distributor_id' => ['required|integer|min:1', trans('PromotionsBundle.store_id_error')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $limitService = new LimitService();
        $limitNum = $params['limit_num'];
        $itemId = $params['item_id'];
        $distributorId = $params['distributor_id'];
        $result = $limitService->updateLimitItem($params['company_id'], $distributorId, $itemId, $limitNum, $limitId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/limit/{limitId}",
     *     summary="获取限购活动信息",
     *     tags={"营销"},
     *     description="获取限购活动信息",
     *     operationId="info",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limitId", in="path", description="限购活动id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/LimitDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function info($limitId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = 0;//默认获取全局限购的商品信息
        $limitService = new LimitService();
        $result = $limitService->getLimitInfo($companyId, $limitId, $distributorId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/limit",
     *     summary="创建限购商品",
     *     tags={"营销"},
     *     description="创建限购商品",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limit_name", in="formData", description="限购活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="limit_type", in="formData", description="限购类型(global, shop)", required=true, type="string"),
     *     @SWG\Parameter( name="day", in="formData", description="购买规则,天数，天数设置0视为此次活动有效期内", required=true, type="string"),
     *     @SWG\Parameter( name="limit", in="formData", description="购买规则,限购数", required=true, type="string"),
     *     @SWG\Parameter( name="items[0]", in="formData", description="限购商品关联商品id", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade[0]", in="formData", description="会员级别id", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade[0]", in="formData", description="use_bound goods:指定商品 category:指定分类 tag:指定标签 brand:指定品牌", required=true, type="string"),
     *     @SWG\Parameter( name="item_category", in="formData", description="分类id集合，use_bound=category时必填", type="string"),
     *     @SWG\Parameter( name="tag_ids", in="formData", description="标签id集合，use_bound=tag时必填", type="string"),
     *     @SWG\Parameter( name="brand_ids", in="formData", description="品牌id集合，use_bound=brand时必填", type="string"),
     *     @SWG\Parameter( name="file", in="query", description="上传的文件", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/LimitResult"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function create(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $params = $request->input();
        $params['limit_type'] = $params['limit_type'] ?? 'global';
        $limitItemNum = $params['total_count'] ?? 0;
        $params['company_id'] = $authInfo['company_id'];

        //店铺限购的时候，这些参数在xlsx文件里设置
        if ($params['limit_type'] == 'shop') {
            $params['day'] = 0;
            $params['limit'] = 1;
            $params['use_bound'] = 'goods_import';
            if ($limitItemNum == 0) {
                throw new ResourceException('请上传活动商品');
            }
        }

        $rules = [
            'limit_name' => ['required', '活动名称必填'],
            //'items.*' => ['required', '商品id参数缺失'],
            'day' => ['required|integer|min:0', '购买间隔天数格式不正确,请输入整数'],
            'limit' => ['required|integer|min:1', trans('PromotionsBundle.purchase_quantity_format_error')],
            'start_time' => ['required_without:is_long', '活动开始时间必填'],
            'end_time' => ['required_without:is_long', '活动结束时间必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);
        $params['items'] = $params['items'] ?? [];
        if ($params['start_time'] < time()) {
            throw new ResourceException('活动生效时间必须大于当前时间');
        }

        $params['source_id'] = $authInfo['distributor_id'];//如果是平台，这里是0
        $params['source_type'] = $authInfo['operator_type'];//如果是平台，这里是admin

        $limitService = new LimitService();
        $result = $limitService->createLimitPromotions($params);

        //更新限购商品，这里改到 saveLimitItems 处理
        /*
        if ($params['limit_type'] == 'shop') {
            $fileObject = $request->file('file');//文件不能超过2w行
            $params['limit_id'] = $result['limit_id'];
            if ($fileObject) {
                $limitSaleItemUploadService = new LimitSaleItemUploadService();
                $limitSaleItemUploadService->handleFile($fileObject, $params);
            }
        }*/

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/limit/{limitId}",
     *     summary="修改限购商品",
     *     tags={"营销"},
     *     description="修改限购商品",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limitId", in="path", description="限购活动id", required=true, type="string"),
     *     @SWG\Parameter( name="limit_name", in="formData", description="限购活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="limit_type", in="formData", description="限购类型(global, shop)", required=true, type="string"),
     *     @SWG\Parameter( name="day", in="formData", description="购买规则,天数，天数设置0视为此次活动有效期内", required=true, type="string"),
     *     @SWG\Parameter( name="limit", in="formData", description="购买规则,限购数", required=true, type="string"),
     *     @SWG\Parameter( name="items[0]", in="formData", description="限购商品关联商品id", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade[0]", in="formData", description="会员级别id", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade[0]", in="formData", description="use_bound goods:指定商品 category:指定分类 tag:指定标签 brand:指定品牌", required=true, type="string"),
     *     @SWG\Parameter( name="item_category", in="formData", description="分类id集合，use_bound=category时必填", type="string"),
     *     @SWG\Parameter( name="tag_ids", in="formData", description="标签id集合，use_bound=tag时必填", type="string"),
     *     @SWG\Parameter( name="brand_ids", in="formData", description="品牌id集合，use_bound=brand时必填", type="string"),
     *     @SWG\Parameter( name="file", in="query", description="上传的文件", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/LimitResult"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function update($limitId, Request $request)
    {
        $params = $request->input();
        $params['limit_type'] = $params['limit_type'] ?? 'global';
        $limitItemNum = $params['total_count'] ?? 0;
        $params['company_id'] = app('auth')->user()->get('company_id');
        $limitService = new LimitService();

        //店铺限购的时候，这些参数在xlsx文件里设置
        if ($params['limit_type'] == 'shop') {
            $params['day'] = 0;
            $params['limit'] = 1;
            $params['use_bound'] = 'goods_import';
            $limitItemNum += $limitService->countLimitItem(['company_id' => $params['company_id'], 'limit_id' => $limitId]);
            if ($limitItemNum == 0) {
                throw new ResourceException('请上传活动商品');
            }
        }

        $rules = [
            'limit_name' => ['required', '活动名称必填'],
            //'items.*' => ['required', '商品id参数缺失'],
            'day' => ['required|integer|min:0', '购买间隔天数格式不正确,请输入整数'],
            'limit' => ['required|integer|min:1', trans('PromotionsBundle.purchase_quantity_format_error')],
            'start_time' => ['required_without:is_long', '活动开始时间必填'],
            'end_time' => ['required_without:is_long', '活动结束时间必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);
        $params['items'] = $params['items'] ?? [];
        if ($params['start_time'] < time()) {
            throw new ResourceException('活动生效时间必须大于当前时间');
        }

        $result = $limitService->updateLimitPromotions($limitId, $params);

        //更新限购商品，这里改到 saveLimitItems 处理
        if ($params['limit_type'] == 'shop') {
            //删除全局限购的数据
            $filter = [
                'company_id' => $params['company_id'],
                'limit_id' => $limitId,
                'distributor_id' => 0,
            ];
            $limitService->deleteLimitItemBy($filter);

            //更新所有限购商品的开始和结束时间
            $filter = [
                'company_id' => $params['company_id'],
                'limit_id' => $limitId,
            ];
            $limitService->updateLimitItemTime($filter, $params);

            //删除原来的限购商品
            /*
            $fileObject = $request->file('file');//文件不能超过2w行
            $params['limit_id'] = $limitId;
            if ($fileObject) {
                $limitSaleItemUploadService = new LimitSaleItemUploadService();
                $limitSaleItemUploadService->handleFile($fileObject, $params);
            }
            */
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/limit_items_save",
     *     summary="保存限购商品",
     *     tags={"营销"},
     *     description="保存限购商品",
     *     operationId="limit_items_save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limit_id", in="query", description="限购活动id", required=true, type="string"),
     *     @SWG\Parameter( name="item_data", in="query", description="商品限购数量(json编码)", required=true, type="string"),
     *     @SWG\Parameter( name="total_count", in="query", description="总数", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码(>=1)", required=true, type="string"),
     *     @SWG\Parameter( name="page_size", in="query", description="分页大小", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/LimitResult"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function saveLimitItems(Request $request)
    {
        $params = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['use_bound'] = 'normal';
        $rules = [
            'limit_id' => ['required', trans('PromotionsBundle.activity_id_required')],
            'item_data' => ['required', '限购数量必须'],
            'total_count' => ['required|integer|min:1', '总数必须'],
            'page' => ['required|integer|min:1', '页码必须'],
            'page_size' => ['required|integer|min:1', '分页大小必须'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['item_data'] = json_decode($params['item_data'], true);
        if (!$params['item_data']) {
            throw new ResourceException('限购数量解析错误！');
        }

        $limitUploadService = new LimitSaleItemUploadService();
        $result = $limitUploadService->saveLimitItems($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotions/limit/cancal/{limitId}",
     *     summary="取消限购商品活动",
     *     tags={"营销"},
     *     description="取消限购商品活动",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limitId", in="path", description="限购活动id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/LimitBase"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function cancel($limitId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $limitService = new LimitService();
        $result = $limitService->cancelLimitPromotions($limitId, $companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/limit_items/upload",
     *     summary="上传限购商品文件",
     *     tags={"营销"},
     *     description="上传限购商品文件",
     *     operationId="upload_limit_items",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="query", description="上传的文件", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/LimitBase"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function uploadLimitItems(Request $request)
    {
        $result = [];
        $companyId = app('auth')->user()->get('company_id');
        $fileObject = $request->file('file');//文件不能超过2w行
        if ($fileObject) {
            $extension = $fileObject->getClientOriginalExtension();
            if ($extension != 'xlsx') {
                throw new ResourceException('请上传 xlsx 文件');
            }
            $limitSaleItemUploadService = new LimitSaleItemUploadService();
            $result = $limitSaleItemUploadService->handleFile($fileObject);
        } else {
            throw new ResourceException('请上传 xlsx 文件');
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/limit_error_desc",
     *     summary="下载商品限购错误信息",
     *     tags={"营销"},
     *     description="下载商品限购错误信息",
     *     operationId="export_error_desc",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limit_id", in="query", description="限购活动id", required=true, type="string"),
     *     @SWG\Parameter( name="file_name", in="query", description="文件名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(required={"data"},
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items(type="object",required={"name","file"},
     *                     @SWG\Property(property="name", type="string", description="文件名称"),
     *                     @SWG\Property(property="file", type="string", description="文件的二进制内容"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function exportErrorDesc(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $limitId = $request->input('limit_id');
        $fileName = $request->input('file_name');
        if (empty($fileName)) {
            throw new ResourceException("文件名称不能为空！");
        }

        $limitUploadService = new LimitSaleItemUploadService();
        $response = [
            'name' => $fileName . '.xlsx', //no extention needed
            'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64," . base64_encode($limitUploadService->exportErrorDesc($limitId, $fileName, $companyId))
        ];
        return response()->json($response);
    }
}
