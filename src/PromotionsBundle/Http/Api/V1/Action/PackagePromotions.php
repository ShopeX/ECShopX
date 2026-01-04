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

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PromotionsBundle\Services\PackageService;
use DistributionBundle\Services\DistributorService;

class PackagePromotions extends Controller
{
    /**
     * @SWG\Get(
     *     path="/promotions/package",
     *     summary="获取组合商品列表",
     *     tags={"营销"},
     *     description="获取组合商品列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="组合商品状态 all:全部，waiting:待开始，ongoing:进行中，end:已结束", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数，默认1", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页条数，默认10", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="81", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/Package"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $status = $request->input('status');
        $packageService = new PackageService();
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        $sourceId = $request->get('distributor_id', 0);//如果是平台，这里是0
        $sourceType = $authInfo['operator_type'];//如果是平台，这里是admin

        $result = $packageService->getPackageList($companyId, $status, $sourceType, $sourceId, $page, $pageSize);

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
     *     path="/promotions/package/{packageId}",
     *     summary="获取组合商品信息",
     *     tags={"营销"},
     *     description="获取组合商品信息",
     *     operationId="info",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="packageId", in="path", description="组合商品id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/PackageDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function info($packageId, Request $request)
    {
        // Ref: 1996368445
        $companyId = app('auth')->user()->get('company_id');
        $packageService = new PackageService();
        $result = $packageService->getPackageInfo($companyId, $packageId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotions/package",
     *     summary="创建组合商品",
     *     tags={"营销"},
     *     description="创建组合商品",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="package_name", in="formData", description="组合商品名称", required=true, type="string"),
     *     @SWG\Parameter( name="main_item[item_id]", in="formData", description="组合商品主商品id", required=true, type="string"),
     *     @SWG\Parameter( name="main_item[item_price]", in="formData", description="组合商品主商品价格", required=true, type="string"),
     *     @SWG\Parameter( name="items[0][item_id]", in="formData", description="组合商品关联商品id", required=true, type="string"),
     *     @SWG\Parameter( name="items[0][new_price]", in="formData", description="组合商品关联商品价格", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade[0]", in="formData", description="适用会员id", required=true, type="string"),
     *     @SWG\Parameter( name="used_platform", in="formData", description="使用平台 0 全部平台", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/PackageResult"
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
        $params['company_id'] = $authInfo['company_id'];

        if ($messageBag = validation($params, [
            'package_name'            => ['required'],
            'main_items'              => ['required'],
            'main_items.*.item_id'    => ['required'],
            'main_items.*.item_price' => ['required'],
            'items'                   => ['required'],
            'items.*.item_id'         => ['required'],
            'items.*.new_price'       => ['required'],
            'valid_grade'             => ['required'],
            'used_platform'           => ['required'],
            'start_time'              => ['required'],
            'end_time'                => ['required'],
        ], [
            'package_name.required'            => trans('PromotionsBundle.package_name_required'),
            'main_items.required'              => trans('PromotionsBundle.main_items_required'),
            'main_items.*.item_id.required'    => trans('PromotionsBundle.main_item_id_required'),
            'main_items.*.item_price.required' => trans('PromotionsBundle.main_item_price_required'),
            'items.required'                   => trans('PromotionsBundle.applicable_products_required'),
            'items.*.item_id.required'         => trans('PromotionsBundle.product_id_param_missing'),
            'items.*.new_price.required'       => trans('PromotionsBundle.product_price_param_missing'),
            'valid_grade.required'             => trans('PromotionsBundle.applicable_member_required'),
            'used_platform.required'           => trans('PromotionsBundle.platform_usage_required'),
            'start_time.required'              => trans('PromotionsBundle.activity_start_time_required'),
            'end_time.required'                => trans('PromotionsBundle.activity_end_time_required'),
        ])) {
            throw new ResourceException($messageBag->first());
        }

        $params['source_id'] = $authInfo['distributor_id'];//如果是平台，这里是0
        $params['source_type'] = $authInfo['operator_type'];//如果是平台，这里是admin

        $packageService = new PackageService();
        $result = $packageService->createPackagePromotions($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/package/{packageId}",
     *     summary="修改组合商品",
     *     tags={"营销"},
     *     description="修改组合商品",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="packageId", in="path", description="组合商品id", required=true, type="string"),
     *     @SWG\Parameter( name="package_name", in="formData", description="组合商品名称", required=true, type="string"),
     *     @SWG\Parameter( name="main_item[item_id]", in="formData", description="组合商品主商品id", required=true, type="string"),
     *     @SWG\Parameter( name="main_item[item_price]", in="formData", description="组合商品主商品价格", required=true, type="string"),
     *     @SWG\Parameter( name="items[0][item_id]", in="formData", description="组合商品关联商品id", required=true, type="string"),
     *     @SWG\Parameter( name="items[0][new_price]", in="formData", description="组合商品关联商品价格", required=true, type="string"),
     *     @SWG\Parameter( name="valid_grade[0]", in="formData", description="适用会员id", required=true, type="string"),
     *     @SWG\Parameter( name="used_platform", in="formData", description="使用平台 0 全部平台", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="结束时间", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/PackageResult"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function update($packageId, Request $request)
    {
        // Ref: 1996368445
        $authInfo = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authInfo['company_id'];

        $rules = [
            'package_name' => ['required', trans('PromotionsBundle.package_name_required')],
            'main_item.item_id' => ['required', trans('PromotionsBundle.main_item_id_required')],
            'main_item.item_price' => ['required', trans('PromotionsBundle.main_item_price_required')],
            'items.*.item_id' => ['required', trans('PromotionsBundle.product_id_param_missing')],
            'items.*.new_price' => ['required', trans('PromotionsBundle.product_price_param_missing')],
            'valid_grade' => ['array', trans('PromotionsBundle.applicable_member_required')],
            'used_platform' => ['required', trans('PromotionsBundle.platform_usage_required')],
            'start_time' => ['required', trans('PromotionsBundle.activity_start_time_required')],
            'end_time' => ['required', trans('PromotionsBundle.activity_end_time_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $packageService = new PackageService();
        $result = $packageService->updatePackagePromotions($packageId, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotions/limit/package/{packageId}",
     *     summary="取消组合商品活动",
     *     tags={"营销"},
     *     description="取消组合商品活动",
     *     operationId="cancel",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="packageId", in="path", description="组合商品活动id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/PackageResult"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function cancel($packageId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $packageService = new PackageService();
        $result = $packageService->cancelPackagePromotions($packageId, $companyId);
        return $this->response->array($result);
    }
}
