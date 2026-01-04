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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Items;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use GoodsBundle\Services\ItemStoreService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Services\NormalGoodsStoreUploadService;
use DistributionBundle\Entities\Distributor;
use PromotionsBundle\Traits\CheckPromotionsValid;

class Item extends Controller
{
    use CheckPromotionsValid;

    /**
     * @SWG\Get(
     *     path="/ecx.product.sku_list",
     *     summary="商品列表查询",
     *     tags={"商品"},
     *     description="商品列表查询",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.product.sku_list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="条数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_begin", description="查询商品更新开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="查询商品更新结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="page", type="string", example="2", description="页码"),
     *                  @SWG\Property( property="page_size", type="string", example="3", description="条数"),
     *                  @SWG\Property( property="sku_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="name", type="string", example="环保测试", description="名称"),
     *                          @SWG\Property( property="sku_id", type="string", example="S5F3241246948F", description="货号"),
     *                          @SWG\Property( property="is_self", type="boolean", example="true", description="是否自营"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="448", description="总条数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function list(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $params = $request->all();

        $rules = [
            'page' => ['required|integer|min:1', 'page参数必填'],
            'page_size' => ['required|integer', 'page_size参数必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage, null, 'E0001');
        }

        $filter = [
            'company_id' => $companyId,
            'item_type' => 'normal',
        ];

        if (isset($params['time_begin'], $params['time_end']) && $params['time_begin'] && $params['time_end']) {
            if (strtotime($params['time_begin']) > strtotime($params['time_end'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if (isset($params['time_begin']) && $params['time_begin']) {
            $filter['updated|gte'] = strtotime($params['time_begin']);
        }
        if (isset($params['time_end']) && $params['time_end']) {
            $filter['updated|lte'] = strtotime($params['time_end']);
        }

        $page = ($params['page'] < 1) ? 1 : $params['page'];
        $pageSize = ($params['page_size'] > 2000) ? 2000 : $params['page_size'];
        $pageSize = ($params['page_size'] <= 0) ? 100 : $params['page_size'];

        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

        $result = $itemsRepository->list($filter, ['item_id' => 'DESC'], $pageSize, $page, ['item_id','item_name','item_bn', 'distributor_id']);

        $itemResult = [];
        $itemResult['page'] = $page;
        $itemResult['page_size'] = $pageSize;

        if (empty($result['list'])) {
            $itemResult['sku_list'] = $result['list'];
            $itemResult['total_count'] = $result['total_count'];

            $this->api_response('true', "", $itemResult, 'E0000');
        }

        foreach ($result['list'] as $key => $value) {
            $itemResult['sku_list'][$key]['name'] = $value['item_name'];
            $itemResult['sku_list'][$key]['sku_id'] = $value['item_bn'];
            $itemResult['sku_list'][$key]['is_self'] = $value['distributor_id'] == 0;
        }
        $itemResult['total_count'] = $result['total_count'];

        $this->api_response('true', "", $itemResult, 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.product.stock_update",
     *     summary="库存同步",
     *     tags={"商品"},
     *     description="库存同步",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.product.stock_update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sku_list", description="商品信息:json_array [{sku_id货号 stock库存 shop_code 店铺编码}]" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="not_find_sku", type="array", @SWG\Items(type="number")),
     *              @SWG\Property(property="not_update_sku", type="array", @SWG\Items(type="number")),
     *              @SWG\Property(property="update_error", type="array",
     *                  @SWG\Items(
     *                      type="object",
     *                      @SWG\Property(property="sku_id", type="string", description="策略id"),
     *                      @SWG\Property(property="shop_code", type="string", description="门店编码"),
     *                      @SWG\Property(property="error", type="string", description="错误描述"),
     *                  )
     *              ),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateItemStore(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $params = $request->all('sku_list');
        $params['sku_list'] = json_decode($params['sku_list'], true);
        if (!$params['sku_list'] || !is_array($params['sku_list'])) {
            throw new ErrorException(ErrorCode::SERVICE_PARAMS_FORMAT_ERROR, '商品信息格式错误');
        }
        foreach ($params['sku_list'] as $sku) {
            if (!is_array($sku)) {
                throw new ErrorException(ErrorCode::SERVICE_PARAMS_FORMAT_ERROR, '商品信息格式错误');
            }
        }
        $rules = [
            'sku_list.*.sku_id' => ['required', '商品信息的货号必填'],
            'sku_list.*.stock' => ['required|integer|min:0|max:999999999', '商品信息的库存为0-999999999的整数'],
            // 'sku_list.*.shop_code' => ['required', '商品信息的店铺编码必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $itemsService = new ItemsService();

        //获取参与活动中的货品ID
        //更新店铺库存，不做活动商品排除 2022-02 ---yixiaotang
        $activityItems = [];//$this->getActivityItems();
        $activityBns = [];
        if ($activityItems) {
            //获取活动商品BN
            $activityItemList = $itemsService->getItemsList(['item_id' => $activityItems]);

            //取出参与活动中的商品BN
            for ($i = count($activityItemList['list']) - 1;$i >= 0;$activityBns[] = $activityItemList['list'][$i]['item_bn'],$i--);
        }

        $itemStoreService = new ItemStoreService();

        $normalGoodsStoreUploadService = new NormalGoodsStoreUploadService();

        // 取出所有要更新的商品BN
        $itemBns = [];

        for ($i = count($params['sku_list']) - 1;$i >= 0;$itemBns[] = $params['sku_list'][$i]['sku_id'],$i--);
        // 根据BN获取商品信息
        $itemList = $itemsService->getItemsList(['item_bn' => $itemBns]);
        if (!$itemList['list']) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }
        //一次性获取要更新库存的商品的BN
        $itemBnList = [];
        foreach ((array)$itemList['list'] as $ival) {
            if (!$ival) {
                continue;
            }
            $itemBnList[$ival['item_bn']] = [
                'item_id' => $ival['item_id'],
                'company_id' => $ival['company_id'],
                'item_bn' => $ival['item_bn']
            ];
        }

        $notUpdateSku = [];
        $notfindSku = [];
        $updateError = [];
        foreach ((array)$params['sku_list'] as $value) {
            if (!$value['sku_id'] || !isset($value['stock'])) {
                continue;
            }

            //参与活动中的商品跳过更新库存
            if ($activityBns && in_array($value['sku_id'], $activityBns)) {
                $notUpdateSku[] = $value['sku_id'];
                continue;
            }

            //检查商品是否存在
            if (!isset($itemBnList[$value['sku_id']]) || !$itemBnList[$value['sku_id']]) {
                $notfindSku[] = trim($value['sku_id']);
                continue;
            }
            //店铺编码不为空时修改店铺库存
            if (isset($value['shop_code']) && $value['shop_code']) {
                try {
                    $distributorInfo = app('registry')->getManager('default')->getRepository(Distributor::class)->getInfo(['company_id' => $companyId, 'shop_code' => $value['shop_code']]);

                    if (!($distributorInfo['distributor_id'] ?? 0)) {
                        $updateError[] = ['sku_id' => $value['sku_id'], 'shop_code' => $value['shop_code'], 'error' => '门店不存在'];
                        continue;
                    }

                    $shopUpateData = ['distributor_id' => $distributorInfo['distributor_id'], 'item_bn' => $value['sku_id'], 'store' => $value['stock']];
                    $shopUpateResult = $normalGoodsStoreUploadService->handleRow($companyId, $shopUpateData);
                    if (!$shopUpateResult) {
                        $updateError[] = ['sku_id' => $value['sku_id'], 'shop_code' => $value['shop_code'], 'error' => '更新失败'];
                        continue;
                    }
                } catch (\Exception $e) {
                    $updateError[] = ['sku_id' => $value['sku_id'], 'shop_code' => $value['shop_code'], 'error' => $e->getMessage()];
                    continue;
                }
            } else {
                //仅修改普通商品库存
                $result = $itemStoreService->saveItemStore($itemBnList[$value['sku_id']]['item_id'], $value['stock']);
                if ($result) {
                    $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                    $result = $itemsRepository->updateStore($itemBnList[$value['sku_id']]['item_id'], $value['stock']);
                }
                if (!$result) {
                    $updateError[] = ['sku_id' => $value['sku_id'], 'error' => '更新失败'];
                    continue;
                }
            }
        }
        if ($notfindSku) {
            app('log')->debug('openapi-更新库存商品不存在：'.var_export($notfindSku, 1));
        }

        if ($notUpdateSku) {
            app('log')->debug('openapi-活动商品暂不更新库存：'.var_export($notUpdateSku, 1));
        }

        if ($updateError) {
            app('log')->debug('openapi-库存更新失败商品：'.var_export($updateError, 1));
        }

        $result = [
            'not_find_sku' => $notfindSku,
            'not_update_sku' => $notUpdateSku,
            'update_error' => $updateError,
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.product.goods_list",
     *     summary="商品列表查询",
     *     tags={"商品"},
     *     description="商品列表查询",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.product.goods_list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="条数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_begin", description="查询商品更新开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="查询商品更新结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="page", type="string", example="2", description="页码"),
     *                  @SWG\Property( property="page_size", type="string", example="3", description="条数"),
     *                  @SWG\Property( property="total_count", type="string", example="448", description="总条数"),
     *                  @SWG\Property( property="goods_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_name", type="string", example="环保测试", description="货品名称"),
     *                          @SWG\Property( property="item_bn", type="string", example="", description="货号"),
     *                          @SWG\Property( property="item_price", type="string", example="", description="货品价格"),
     *                          @SWG\Property( property="goods_bn", type="string", example="S5F3241246948F", description="货品id"),
     *                          @SWG\Property( property="approve_status", type="string", example="S5F3241246948F", description="上下架状态"),
     *                          @SWG\Property( property="pic", type="string", example="S5F3241246948F", description="图片"),
     *                          @SWG\Property( property="sku", type="string", example="S5F3241246948F", description="sku属性"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function goodsList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 100);
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $filter['company_id'] = $companyId;

        $params = $request->all();
        if (isset($params['time_begin'], $params['time_end']) && $params['time_begin'] && $params['time_end']) {
            if (strtotime($params['time_begin']) > strtotime($params['time_end'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if (isset($params['time_begin']) && $params['time_begin']) {
            $filter['updated|gte'] = strtotime($params['time_begin']);
        }
        if (isset($params['time_end']) && $params['time_end']) {
            $filter['updated|lte'] = strtotime($params['time_end']);
        }

        $orderBy = [];
        $cols = ['item_id', 'item_bn', 'item_name','pics','price', 'approve_status', 'distributor_id'];
        $lists = $itemsRepository->list($filter, $orderBy, $pageSize, $page, $cols);
        $result = [
            'page' => $page,
            'page_size' => $pageSize,
            'total_count' => $lists['total_count'] ?? 0,
            'goods_list' => [],
        ];
        if (!($lists['list'] ?? null)) {
            $this->api_response('true', "ok", $result, 'E0000');
        }
        $itemIds = array_column($lists['list'], 'item_id');
        $itemRelAttributespository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $itemRelAttList = $itemRelAttributespository->lists(['company_id' => $companyId, 'item_id' => $itemIds]);
        $skuArr = [];
        if ($itemRelAttList['list'] ?? null) {
            foreach ($itemRelAttList['list'] as $value) {
                $skuArr[$value['item_id']][] = $value['custom_attribute_value'] ?? '';
            }
        }

        foreach ($lists['list'] as $v) {
            $result['goods_list'][] = [
                'item_name' => strval($v['item_name'] ?? ''),
                'item_price' => intval($v['price'] ?? 0),
                'pic' => strval($v['pics'][0] ?? ''),
                'sku' => ($skuArr[$v['item_id']] ?? null) ? implode(' ', $skuArr[$v['item_id']]) : '',
                'goods_bn' => intval($v['item_id'] ?? ''),
                'item_bn' => strval($v['item_bn'] ?? ''),
                'approve_status' => strval($v['approve_status'] ?? ''),
                'is_self' => $v['distributor_id'] == 0,
            ];
        }
        $this->api_response('true', "ok", $result, 'E0000');
    }
}
