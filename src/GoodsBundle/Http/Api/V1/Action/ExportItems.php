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
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\DistributorItems;
use EspierBundle\Jobs\ExportFileJob;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use SupplierBundle\Services\SupplierItemsService;
use SupplierBundle\Services\SupplierService;
use WechatBundle\Services\WeappService;
use DistributionBundle\Services\DistributorService;

use Illuminate\Http\Request;

class ExportItems extends BaseController
{
    public const TEMPLATE_NAME = 'yykweishop';

    /**
     * @SWG\Post(
     *     path="/goods/export",
     *     summary="导出商品信息",
     *     tags={"商品"},
     *     description="导出商品信息",
     *     operationId="syncBrand",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="导出id，是个数组", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsData(Request $request)
    {
        // Powered by ShopEx EcShopX
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        /*        $itemService = new ItemsService();
                $params = $itemService->exportParams($inputData, app('auth')->user()->get('company_id'));
                if ($params === false) {
                    $result['list'] = [];
                    $result['total_count'] = 0;
                    return $this->response->array($result);
                }*/

        $params = $this->__getFilter($inputData, $authdata);

        $exportType = $inputData['export_type'] ?? 'items';
        if ($authdata['operator_type'] == 'supplier') {
            $exportType = 'supplier_goods';
        }

        //存储导出操作账号者
        $operator_id = $authdata['operator_id'];

        $gotoJob = (new ExportFileJob($exportType, $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        if (env('APP_ENV') == 'local_dev') {
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatchNow($gotoJob);
        } else {
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        $result['status'] = true;
        return response()->json($result);
    }

    public function exportItemsDataApiReturn(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();


        $filter = $this->__getFilter($inputData, $authdata);

        $exportType = $inputData['export_type'] ?? 'items';
        if ($authdata['operator_type'] == 'supplier') {
            $exportType = 'supplier_goods';
        }

        //存储导出操作账号者
        $operator_id = $authdata['operator_id'];
        //实时导出数据
        $redis = app('redis');
        $randNum = time() . mt_rand(111, 999);
        $redisKey = 'export_key:' . $randNum;
        $filter['export_type'] = $exportType;
        unset($filter['export_type']);
        $redis->set($redisKey, json_encode($filter, 256), 'EX', 86400);
        return $this->response->array(['url' =>  $randNum]);  
      
        return response()->json($result);
    }


    private function __getFilter($inputData, $authdata)
    {
        $params['operator_type'] = $authdata['operator_type'];
        // $params['operator_id'] = $authdata['operator_id'];
        if($params['operator_type'] == 'supplier'){
            $itemsService = new SupplierItemsService();
        } else {
            $itemsService = new ItemsService();
        }

        $params['company_id'] = $authdata['company_id'];
        $params['item_type'] = $inputData['item_type'] ?? 'services';
        foreach ($inputData as $key => $value) {
            switch ($key) {
                case 'item_source':
                    if (in_array($params['operator_type'], ['admin', 'staff'])) {
                        if ($value == 'supplier') {
                            $params['supplier_id|gt'] = 0;//平台导出供应商商品
                            // 供应商商品列表只显示审核通过的，与列表接口保持一致
                            $params['audit_status'] = 'approved';
                        } else {
                            $params['supplier_id'] = 0;//平台导出自营商品
                        }
                    }
                    $params[$key] = trim($value);
                    break;

                case 'keywords':
                case 'item_name':
                    if (!$value) break;
                    $params['item_name|contains'] = trim($value);
                    break;

                case 'brand_id':
                case 'consume_type':
                case 'templates_id':
                case 'rebate_type':
                    if (!$value) break;
                    $params[$key] = trim($value);
                    break;

                case 'regions_id':
                    if (!$value) break;
                    $params[$key] = implode(',', $value);
                    break;

                case 'supplier_name':
                    if (!$value) break;
                    $_filter = [
                        'supplier_name|like' => trim($value),
                    ];
                    $supplierService = new SupplierService();
                    $rsSupplier = $supplierService->repository->getLists($_filter);
                    if ($rsSupplier) {
                        $params['supplier_id'] = array_column($rsSupplier, 'operator_id');
                    } else {
                        throw new resourceexception('没有符合条件的供应商商品');
                    }
                    break;

                case 'nospec':
                    $params[$key] = $value;
                    break;

                case 'main_cat_id'://商品管理分类
                    if (!$value) break;
                    if (is_array($value)) {
                        $value = array_pop($value);
                    }
                    $itemsCategoryService = new ItemsCategoryService();
                    $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($value, $params['company_id']);
                    $itemCategory[] = $value;
                    $params['item_category'] = $itemCategory;
                    break;

                case 'approve_status':
                    if (!$value) break;
                    if (in_array($value, ['processing', 'rejected'])) {
                        $params['audit_status'] = $value;
                    } else {
                        $params['approve_status'] = $value;
                    }
                    break;

                case 'rebate':
                    if (!in_array($value, [0, 1, 2, 3])) break;
                    $params[$key] = $value;
                    break;

                case 'special_type':
                    if (!in_array($value, ['normal', 'drug'])) break;
                    $params[$key] = $value;
                    break;

                case 'category'://商品销售分类
                    if (!$value) break;
                    $itemsCategoryService = new ItemsCategoryService();
                    $ids = $itemsCategoryService->getItemIdsByCatId($value, $params['company_id']);
                    if (!$ids) {
                        throw new resourceexception('指定的分类下没有商品');
                    }
                    if (isset($params['item_id'])) {
                        $params['item_id'] = array_intersect($params['item_id'], $ids);
                        if (!$params['item_id']) {
                            throw new resourceexception('没有符合条件的商品');
                        }
                    } else {
                        $params['item_id'] = $ids;
                    }
                    break;

                case 'store_gt':
                    if (!$value) break;
                    $params['store|gt'] = intval($value);
                    break;

                case 'store_lt':
                    if (!$value) break;
                    $params['store|lt'] = intval($value);
                    break;

                case 'price_gt':
                    if (!$value) break;
                    $params['price|gt'] = bcmul($value, 100);
                    break;

                case 'price_lt':
                    if (!$value) break;
                    $params['price|lt'] = bcmul($value, 100);
                    break;

                case 'tag_id':
                    if (!$value) break;
                    $itemsTagsService = new ItemsTagsService();
                    $filter = ['company_id' => $params['company_id'], 'tag_id' => $value];
                    if (isset($params['item_id']) && $params['item_id']) {
                        $filter['item_id'] = $params['item_id'];
                    }
                    $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
                    if (!$itemIds) {
                        throw new resourceexception('指定的标签下没有商品');
                    }
                    $params['item_id'] = $itemIds;
                    break;
            }
        }

        $distributorService = new DistributorService();
        $distributorId = $inputData['distributor_id'] ?? 0;
        if ($params['operator_type'] == 'merchant') {
            //获取商户下的所有店铺
            // $params['merchant_id'] = $authdata['merchant_id'];
            $distributorList = $distributorService->getLists(['is_valid' => 'true', 'company_id' => $params['company_id'], 'merchant_id' => $authdata['merchant_id']], 'distributor_id');
            $params['distributor_id'] = array_column($distributorList, 'distributor_id');
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
                throw new resourceexception('没有符合条件的店铺');
            }
        } else {
            $params['distributor_id'] = $distributorId;
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (isset($params['distributor_id']) && !$params['distributor_id']) {
                unset($params['distributor_id']);
            }
        } elseif ($params['operator_type'] == 'distributor'){
            $company = (new \CompanysBundle\Ego\CompanysActivationEgo())->check($params['company_id']);
            if ($company['product_model'] == 'standard') {
                $distributorItemsEntityRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
                $ditemlist = $distributorItemsEntityRepository->getList(['distributor_id'=>$params['distributor_id']], 'default_item_id,item_id',1,-1);
                if (!$ditemlist) {
                    throw new resourceexception('不存在店铺商品');
                }
                $params['item_id'] = array_column($ditemlist, 'default_item_id');
                unset($params['distributor_id']);
            }
        }

        //把sku编码转换成spu商品ID
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $paramsTmp['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($paramsTmp, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($paramsTmp['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $params['isGetSkuList'] = true;
        } else {
            $params['isGetSkuList'] = false;
            // $params['is_default'] = true;
        }
        return $params;
    }

    /**
     * @SWG\Post(
     *     path="/goods/tag/export",
     *     summary="导出商品标签信息",
     *     tags={"商品"},
     *     description="导出商品标签信息",
     *     operationId="exportItemsTagData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="导出id，是个数组", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsTagData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        $params['company_id'] = app('auth')->user()->get('company_id');
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
//            unset($params['item_name']);
            $params['item_name|contains'] = trim($request->input('keywords'));
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        $distributorService = new DistributorService();
        $distributorId = $request->get('distributor_id') ?: $request->input('distributor_id', 0);
        if ($authdata['operator_type'] == 'merchant') {
            // $params['merchant_id'] = $authdata['merchant_id'];
            $distributorList = $distributorService->getLists(['is_valid' => 'true', 'company_id' => $params['company_id'], 'merchant_id' => $authdata['merchant_id']], 'distributor_id');
            $params['distributor_id'] = array_column($distributorList, 'distributor_id');

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
                throw new resourceexception('导出有误,暂无数据导出');
            }
        } else {
            $params['distributor_id'] = $distributorId;
        }

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $request->input('rebate');
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (!$params['distributor_id']) {
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

        $itemsService = new ItemsService();
        $operator_type = app('auth')->user()->get('operator_type');
        if($operator_type == 'supplier'){
            $itemsService = new SupplierItemsService();
        }
        $params['operator_type'] = $operator_type;
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

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        if ($authdata['operator_type'] == 'merchant') {
            $params['merchant_id'] = $authdata['merchant_id'];
        }

        $gotoJob = (new ExportFileJob('normal_items_tag', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/code/export",
     *     summary="导出商品码",
     *     tags={"商品"},
     *     description="导出商品码，小程序码或H5二维码",
     *     operationId="exportItemsCodeData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="导出id，是个数组", type="string" ),
     *     @SWG\Parameter( name="export_type", in="query", description="导出类型 wxa:太阳码;h5:H5二维码;", type="string" ),
     *     @SWG\Parameter( name="source", in="query", description="来源 item:商品;distributor:店铺商品;", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsCodeData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        // 使用统一的查询条件构建方法
        $params = $this->__getFilter($inputData, $authdata);

        // 添加业务逻辑参数
        $params['is_default'] = true;
        $inputData['export_type'] ??= 'wxa';
        $params['export_type'] = $inputData['export_type'];
        if ($params['export_type'] == 'wxa') {
            $weappService = new WeappService();
            $wxaappid = $weappService->getWxappidByTemplateName($authdata['company_id'], self::TEMPLATE_NAME);
            if (!$wxaappid) {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.miniapp_not_opened'));
            }
            $params['wxaappid'] = $wxaappid;
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        if ($authdata['operator_type'] == 'merchant') {
            $params['merchant_id'] = $authdata['merchant_id'];
        }

        $gotoJob = (new ExportFileJob('itemcode', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
