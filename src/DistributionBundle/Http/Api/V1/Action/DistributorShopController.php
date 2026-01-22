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

namespace DistributionBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorTagsService;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Exception;
use ThirdPartyBundle\Services\Map\MapService;

class DistributorShopController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/shops",
     *     summary="添加门店",
     *     tags={"店铺"},
     *     description="添加门店",
     *     operationId="createShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="门店名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lng",
     *         in="query",
     *         description="纬度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         description="经度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         description="腾讯地图门店的地址",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="banner",
     *         in="query",
     *         description="门店图片，可传多张图片,banner字段是一个json",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="contract_phone",
     *         in="query",
     *         description="联系电话,固定电话需加区号；区号、分机号均用“-”连接",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="hour",
     *         in="query",
     *         description="营业时间，格式11:11-12:12",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_domesitc",
     *         in="query",
     *         description="是否为中国国内门店",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="country",
     *         in="query",
     *         description="非中国国家名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         description="非中国国家城市名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="contact", in="query", description="联系人", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="number"),
     *                     @SWG\Property(property="lng", type="string"),
     *                     @SWG\Property(property="lat", type="string"),
     *                     @SWG\Property(property="address", type="string"),
     *                     @SWG\Property(property="banner", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="add_type", type="integer"),
     *                     @SWG\Property(property="status", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="is_domestic", type="integer"),
     *                     @SWG\Property(property="country", type="string"),
     *                     @SWG\Property(property="city", type="string"),
     *                     @SWG\Property(property="is_direct_store", type="integer")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function createShops(Request $request)
    {
        $regions = [
          0 => 'province',
          1 => 'city',
          2 => 'area',
      ];
        $params = $request->all('name', 'address', 'mobile', 'is_valid', 'regions_id', 'regions', 'contact', 'lng', 'lat', 'hour', 'logo', 'banner', 'auto_sync_goods', 'is_audit_goods', 'contract_phone');
        $params['is_distributor'] = false;
        if (is_array($params['hour'])) {
            $tmp_start = date('H:i', strtotime($params['hour'][0]));
            $tmp_end = date('H:i', strtotime($params['hour'][1]));
            $params['hour'] = $tmp_start . ' - ' . $tmp_end;
        }
        $params['shop_id'] = $request->input('distributor_id', 0);
                $rules = [
            'name' => ['required|between:1,20', trans('DistributionBundle/Controllers/DistributorShopController.name_required_between')],
            'is_valid' => ['required', trans('DistributionBundle/Controllers/DistributorShopController.is_valid_required')],
            'contract_phone' => ['required', trans('DistributionBundle/Controllers/DistributorShopController.contract_phone_required')],
            'mobile' => ['required', trans('DistributionBundle/Controllers/DistributorShopController.mobile_required')],
            'hour' => ['required', trans('DistributionBundle/Controllers/DistributorShopController.hour_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $params['shop_id'] = $request->get('distributor_id');
            unset($params['is_audit_goods']);
            unset($params['auto_sync_goods']);
        }

        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$regions[$k]] = $value;
            }
        }

        $companyId = app('auth')->user()->get('company_id');
        $distributorIds = $request->get('distributorIds');
        if ($distributorIds && ($params['shop_id'] ?? 0) && in_array($params['shop_id'], $distributorIds)) {
            throw new Exception("您没有添加门店的权限", 400500);
        }

        $params['company_id'] = $companyId;

        $distributorService = new DistributorService();
        $data = $distributorService->createDistributor($params);

        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/shops/{distributor_id}",
     *     summary="更新门店",
     *     tags={"店铺"},
     *     description="更新门店",
     *     operationId="updateShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="path",
     *         description="门店id",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="门店名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lng",
     *         in="query",
     *         description="纬度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         description="经度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         description="腾讯地图门店的地址",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="banner",
     *         in="query",
     *         description="门店图片，可传多张图片,banner字段是一个json",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="contract_phone",
     *         in="query",
     *         description="联系电话,固定电话需加区号；区号、分机号均用“-”连接",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="hour",
     *         in="query",
     *         description="营业时间，格式11:11-12:12",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_domesitc",
     *         in="query",
     *         description="是否为中国国内门店",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="country",
     *         in="query",
     *         description="非中国国家名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         description="非中国国家城市名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="contact", in="query", description="联系人", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="integer"),
     *                     @SWG\Property(property="map_poi_id", type="string"),
     *                     @SWG\Property(property="store_name", type="string"),
     *                     @SWG\Property(property="poi_id", type="string"),
     *                     @SWG\Property(property="lng", type="string"),
     *                     @SWG\Property(property="lat", type="string"),
     *                     @SWG\Property(property="address", type="string"),
     *                     @SWG\Property(property="banner", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="add_type", type="integer"),
     *                     @SWG\Property(property="credential", type="string"),
     *                     @SWG\Property(property="company_name", type="string"),
     *                     @SWG\Property(property="qualification_list", type="string"),
     *                     @SWG\Property(property="card_id", type="string"),
     *                     @SWG\Property(property="status", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="is_direct_store", type="integer"),
     *                     @SWG\Property(property="is_domesitc", type="integer"),
     *                     @SWG\Property(property="country", type="string"),
     *                     @SWG\Property(property="city", type="string")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function updateShops($distributor_id, Request $request)
    {
        $regions = [
          0 => 'province',
          1 => 'city',
          2 => 'area',
      ];
        $params = $request->all('name', 'address', 'mobile', 'is_valid', 'regions_id', 'regions', 'contact', 'is_ziti', 'lng', 'lat', 'hour', 'logo', 'banner', 'auto_sync_goods', 'is_audit_goods');
        $params['distributor_id'] = $distributor_id;
        $params['is_distributor'] = false;
        if (is_array($params['hour'])) {
            $tmp_start = date('H:i', strtotime($params['hour'][0]));
            $tmp_end = date('H:i', strtotime($params['hour'][1]));
            $params['hour'] = $tmp_start . ' - ' . $tmp_end;
        }
        if ($request->input('distributor_id', 0)) {
            $params['shop_id'] = $request->input('distributor_id', 0);
        }
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        if (app('auth')->user()->get('operator_type') == 'distributor') {
            unset($params['is_audit_goods']);
            unset($params['auto_sync_goods']);
        }

        $distributorIds = $request->get('distributorIds');
        if ($distributorIds && !in_array($params['shop_id'], $distributorIds)) {
            throw new Exception("您没有此操作的权限", 400500);
        }

        $params = array_filter($params);
                $rules = [
            'distributor_id' => ['required|integer|min:1', trans('DistributionBundle/Controllers/DistributorShopController.distributor_id_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$regions[$k]] = $value;
            }
        }

        if (isset($params['regions']) && count($params['regions']) == 2) {
            $params['area'] = '';
        }
        $distributorService = new DistributorService();
        $data = $distributorService->updateDistributor($distributor_id, $params);

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/dshops/setDefaultShop",
     *     summary="设置默认门店",
     *     tags={"店铺"},
     *     description="设置默认门店",
     *     operationId="setDefaultShop",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="门店id",
     *         required=true,
     *         type="integer",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function setDefaultShop(request $request)
    {
        $distributorId = $request->input('distributor_id');
        if (!$distributorId) {
            return $this->response->error(trans('DistributionBundle/Controllers/DistributorShopController.shop_required'), 411);
        }
        $companyId = app('auth')->user()->get('company_id');
        $distributorService = new DistributorService();
        $result = $distributorService->setDefaultDistributor($companyId, $distributorId, false);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/dshops/setShopStatus",
     *     summary="设置门店状态",
     *     tags={"店铺"},
     *     description="设置门店状态",
     *     operationId="setShopStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="门店id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         description="门店状态",
     *         required=true,
     *         type="integer",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function setShopStatus(request $request)
    {
        $distributor_id = $request->input('distributor_id');

        $distributorIds = $request->get('distributorIds');
        if ($distributorIds && !in_array($distributor_id, $distributorIds)) {
            throw new Exception("您没有此项操作权限", 400500);
        }

        $status = $request->input('status');
        if (!$distributor_id) {
            return $this->response->error(trans('DistributionBundle/Controllers/DistributorShopController.shop_required'), 411);
        }
        $distributorService = new DistributorService();
        $result = $distributorService->openOrClose($distributor_id, $status);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/shops/{distributor_id}",
     *     summary="删除门店",
     *     tags={"店铺"},
     *     description="添加门店",
     *     operationId="deleteShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="path",
     *         description="门店id，非方的mp_poi_id",
     *         required=true,
     *         type="integer",
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
     *                     @SWG\Property(property="distributor_id", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function deleteShops($distributor_id, Request $request)
    {
        $params['distributor_id'] = $distributor_id;
        $validator = app('validator')->make($params, [
            'distributor_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('DistributionBundle/Controllers/DistributorShopController.delete_shop_error'), $validator->errors());
        }
        $distributorService = new DistributorService();
        $distributorIds = $request->get('distributorIds');
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'distributor_id' => $distributor_id,
            'company_id' => $companyId,
            'is_distributor' => false,
        ];
        if ($distributorIds) {
            $info = $distributorService->getInfo($filter);
            if (($info['shop_id'] ?? 0) && in_array(intval($info['shop_id']), $distributorIds)) {
                throw new Exception("您没有此项操作权限", 400500);
            }
        }
        $result = $distributorService->deleteBy($filter);
        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/shops/{distributor_id}",
     *     summary="获取单个门店详情",
     *     tags={"店铺"},
     *     description="获取单个门店详情",
     *     operationId="getShopsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="path",
     *         description="门店id，非方的mp_poi_id",
     *         required=true,
     *         type="integer",
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
     *                     @SWG\Property(property="distributor_id", type="string"),
     *                     @SWG\Property(property="mp_poi_id", type="string"),
     *                     @SWG\Property(property="banner", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="credential", type="string"),
     *                     @SWG\Property(property="company_name", type="string"),
     *                     @SWG\Property(property="qualification_list", type="string"),
     *                     @SWG\Property(property="card_id", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getShopsDetail($distributor_id, Request $request)
    {
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $distributorService = new DistributorService();
        $result = $distributorService->getInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/shops/wxshops",
     *     summary="获取门店列表",
     *     tags={"店铺"},
     *     description="获取门店列表",
     *     operationId="getShopsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="isValid",
     *         in="query",
     *         description="门店是否过期",
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
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
     *                     @SWG\Property(property="distributor_id", type="string"),
     *                     @SWG\Property(property="mp_poi_id", type="string"),
     *                     @SWG\Property(property="banner", type="string"),
     *                     @SWG\Property(property="contract_phone", type="string"),
     *                     @SWG\Property(property="hour", type="string"),
     *                     @SWG\Property(property="credential", type="string"),
     *                     @SWG\Property(property="company_name", type="string"),
     *                     @SWG\Property(property="qualification_list", type="string"),
     *                     @SWG\Property(property="card_id", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getShopsList(request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 1000);

        $companyId = app('auth')->user()->get('company_id');
        $filter['is_distributor'] = false;
        $filter['company_id'] = $companyId;

        if ($request->get('distributor_id') && !$request->get('is_all', false)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        } elseif ($request->get('distributorIds')) {
            $filter['distributor_id'] = $request->get('distributorIds');
        }

        if ($request->input('is_valid')) {
            $filter['is_valid'] = $request->input('is_valid');
        }

        if ($request->input('name')) {
            $filter['name|contains'] = $request->input('name');
        }

        if ($request->input('province')) {
            $filter['province'] = $request->input('province');
        }
        if ($request->input('city')) {
            $filter['city'] = $request->input('city');
        }
        if ($request->input('area')) {
            $filter['area'] = $request->input('area');
        }

        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }

        $distributorTagsService = new DistributorTagsService();
        if ($request->input('tag_id')) {
            $tagFilter = ['company_id' => $filter['company_id'], 'tag_id' => $request->input('tag_id')];
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                $tagFilter['distributor_id'] = $filter['distributor_id'];
            }
            $distributorIds = $distributorTagsService->getDistributorIdsByTagids($tagFilter);
            if (!$distributorIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $filter['distributor_id'] = $distributorIds;
        }

        $distributorService = new DistributorService();
        $data = $distributorService->lists($filter, ["created" => "DESC"], $pageSize, $page);
        $data['tagList'] = [];
        if ($data['list']) {
            //获取商品标签
            $distributorIds = array_column($data['list'], 'distributor_id');
            $tagFilter = [
              'distributor_id' => $distributorIds,
              'company_id' => $filter['company_id'],
          ];
            $tagList = $distributorTagsService->getDistributorRelTagList($tagFilter);
            $tagNewList = [];
            foreach ($tagList as $tag) {
                $newTags[$tag['distributor_id']][] = $tag;
                $tagNewList[$tag['tag_id']] = $tag;
            }
            foreach ($data['list'] as &$value) {
                $value['tagList'] = $newTags[$value['distributor_id']] ?? [];
            }
            $data['tagList'] = array_values($tagNewList);
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/getAreaByAddress",
     *     summary="根据地址获取地区信息",
     *     tags={"店铺"},
     *     description="根据地址获取地区信息",
     *     operationId="getAreaByAddress",
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         description="地址",
     *         required=true,
     *         type="string",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )    
     */
    public function getAreaByAddress(Request $request)
    {
        $address = $request->input('address');
        if (!$address) {
            throw new ResourceException(trans('DistributionBundle/Controllers/Distributor.address_required'), 400);
        }
        
        // 获取公司ID，与UploadDistributor保持一致
        $companyId = app('auth')->user()->get('company_id');
        
        // 使用MapService::make($companyId)获取实例，这样会优先使用数据库配置，与UploadDistributor保持一致
        $mapService = MapService::make($companyId);
        
        // 调用getLatAndLngByPosition方法获取原始响应（与get_latlng_by_address返回格式一致）
        // 传入空region，完整地址作为address
        $area = $mapService->getThirdPartyMapService()->getLatAndLngByPosition([
            "address" => (string)$address
        ]);
        
        return $this->response->array($area);
    }
    
}
