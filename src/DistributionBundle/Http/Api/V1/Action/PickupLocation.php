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

use DistributionBundle\Services\PickupLocationService;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use Swagger\Annotations as SWG;

class PickupLocation extends Controller
{
    /**
     * @SWG\Post(
     *     path="/pickuplocation",
     *     summary="新增自提点",
     *     tags={"店铺"},
     *     description="新增自提点",
     *     operationId="createPickupLocation",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="自提点名称", required=true, type="string"),
     *     @SWG\Parameter( name="province", in="formData", description="省", required=true, type="string"),
     *     @SWG\Parameter( name="city", in="formData", description="市", required=true, type="string"),
     *     @SWG\Parameter( name="area", in="formData", description="区", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="formData", description="地址", required=true, type="string"),
     *     @SWG\Parameter( name="area_code", in="formData", description="电话区号", required=false, type="string"),
     *     @SWG\Parameter( name="contract_phone", in="formData", description="联系电话", required=true, type="string"),
     *     @SWG\Parameter( name="hours[]", in="formData", description="营业时间", required=true, type="string"),
     *     @SWG\Parameter( name="workdays[]", in="formData", description="重复日期", required=true, type="number"),
     *     @SWG\Parameter( name="wait_pickup_days", in="formData", description="最长预约时间", required=true, type="number"),
     *     @SWG\Parameter( name="latest_pickup_time", in="formData", description="当天最晚自提时间", required=true, type="string"),
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
    public function createPickupLocation(Request $request)
    {
        $params = $request->all('name', 'province', 'city', 'area', 'address', 'area_code', 'contract_phone', 'hours', 'workdays', 'wait_pickup_days', 'latest_pickup_time');

        $rules = [
            'name' => ['required|max:255', trans('DistributionBundle/Controllers/PickupLocation.name_required_max')],
            'province' => ['required', trans('DistributionBundle/Controllers/PickupLocation.province_required')],
            'city' => ['required', trans('DistributionBundle/Controllers/PickupLocation.city_required')],
            'area' => ['required', trans('DistributionBundle/Controllers/PickupLocation.area_required')],
            'address' => ['required|max:255', trans('DistributionBundle/Controllers/PickupLocation.address_required_max')],
            'contract_phone' => ['required', trans('DistributionBundle/Controllers/PickupLocation.contract_phone_required')],
            'hours' => ['required', trans('DistributionBundle/Controllers/PickupLocation.hours_required')],
            'workdays' => ['required', trans('DistributionBundle/Controllers/PickupLocation.workdays_required')],
            'wait_pickup_days' => ['required', trans('DistributionBundle/Controllers/PickupLocation.wait_pickup_days_required')],
            'latest_pickup_time' => ['required', trans('DistributionBundle/Controllers/PickupLocation.latest_pickup_time_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $params['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $params['distributor_id'] = $request->get('distributor_id');
        }

        $pickupLocationService = new PickupLocationService();
        $result = $pickupLocationService->savePickupLocation($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/pickuplocation/{id}",
     *     summary="更新自提点",
     *     tags={"店铺"},
     *     description="更新自提点",
     *     operationId="updatePickupLocation",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="自提点id", required=true, type="number"),
     *     @SWG\Parameter( name="name", in="formData", description="自提点名称", required=true, type="string"),
     *     @SWG\Parameter( name="province", in="formData", description="省", required=true, type="string"),
     *     @SWG\Parameter( name="city", in="formData", description="市", required=true, type="string"),
     *     @SWG\Parameter( name="area", in="formData", description="区", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="formData", description="地址", required=true, type="string"),
     *     @SWG\Parameter( name="area_code", in="formData", description="电话区号", required=false, type="string"),
     *     @SWG\Parameter( name="contract_phone", in="formData", description="联系电话", required=true, type="string"),
     *     @SWG\Parameter( name="hours[]", in="formData", description="营业时间", required=true, type="string"),
     *     @SWG\Parameter( name="workdays[]", in="formData", description="重复日期", required=true, type="number"),
     *     @SWG\Parameter( name="wait_pickup_days", in="formData", description="最长预约时间", required=true, type="number"),
     *     @SWG\Parameter( name="latest_pickup_time", in="formData", description="当天最晚自提时间", required=true, type="string"),
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
    public function updatePickupLocation($id, Request $request)
    {
        // 53686f704578
        $params = $request->all('name', 'province', 'city', 'area', 'address', 'area_code', 'contract_phone', 'hours', 'workdays', 'wait_pickup_days', 'latest_pickup_time');

        $rules = [
            'name' => ['required|max:255', trans('DistributionBundle/Controllers/PickupLocation.name_required_max')],
            'province' => ['required', trans('DistributionBundle/Controllers/PickupLocation.province_required')],
            'city' => ['required', trans('DistributionBundle/Controllers/PickupLocation.city_required')],
            'area' => ['required', trans('DistributionBundle/Controllers/PickupLocation.area_required')],
            'address' => ['required|max:255', trans('DistributionBundle/Controllers/PickupLocation.address_required_max')],
            'contract_phone' => ['required', trans('DistributionBundle/Controllers/PickupLocation.contract_phone_required')],
            'hours' => ['required', trans('DistributionBundle/Controllers/PickupLocation.hours_required')],
            'workdays' => ['required', trans('DistributionBundle/Controllers/PickupLocation.workdays_required')],
            'wait_pickup_days' => ['required', trans('DistributionBundle/Controllers/PickupLocation.wait_pickup_days_required')],
            'latest_pickup_time' => ['required', trans('DistributionBundle/Controllers/PickupLocation.latest_pickup_time_required_current')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $params['id'] = $id;
        $params['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $params['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $params['distributor_id'] = $request->get('distributor_id');
        }

        $pickupLocationService = new PickupLocationService();
        $result = $pickupLocationService->savePickupLocation($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/pickuplocation/{id}",
     *     summary="获取自提点详情",
     *     tags={"店铺"},
     *     description="获取自提点详情",
     *     operationId="getPickupLocationInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="自提点id", required=true, type="number"),
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
    public function getPickupLocationInfo($id, Request $request)
    {
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        $pickupLocationService = new PickupLocationService();
        $result = $pickupLocationService->getInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/pickuplocation/list",
     *     summary="获取自提点列表",
     *     tags={"店铺"},
     *     description="获取自提点列表",
     *     operationId="getPickupLocationList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="number"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", required=false, type="number"),
     *     @SWG\Parameter( name="name", in="query", description="自提点名称", required=false, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省", required=false, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="市", required=false, type="string"),
     *     @SWG\Parameter( name="area", in="query", description="区", required=false, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="地址", required=false, type="string"),
     *     @SWG\Parameter( name="rel_distributor_id", in="query", description="关联店铺ID", required=false, type="number"),
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
    public function getPickupLocationList(Request $request)
    {
        $params = $request->all('name', 'province', 'city', 'area', 'address', 'rel_distributor_id');

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if (isset($params['rel_distributor_id']) && $params['rel_distributor_id']) {
            $filter['rel_distributor_id'] = $params['rel_distributor_id'];
            unset($filter['distributor_id']); //总部
        }

        if (isset($params['name']) && $params['name']) {
            $filter['name|contains'] = $params['name'];
        }

        if (isset($params['province']) && $params['province']) {
            $filter['province'] = $params['province'];
        }

        if (isset($params['city']) && $params['city']) {
            $filter['city'] = $params['city'];
        }

        if (isset($params['area']) && $params['area']) {
            $filter['area'] = $params['area'];
        }

        if (isset($params['address']) && $params['address']) {
            $filter['address|contains'] = $params['address'];
        }

        $pickupLocationService = new PickupLocationService();
        $result = $pickupLocationService->lists($filter, '*', $page, $pageSize, ['created' => 'DESC']);

        if ($result['list']) {
            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['distributor_id' => array_column($result['list'], 'rel_distributor_id')], ['distributor_id,name']);
            $distributorList = array_column($distributorList, null, 'distributor_id');
            foreach ($result['list'] as $key => $val) {
                if (isset($distributorList[$val['rel_distributor_id']])) {
                    $result['list'][$key]['rel_distributor_name'] = $distributorList[$val['rel_distributor_id']]['name'];
                }
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/pickuplocation/{id}",
     *     summary="删除自提点",
     *     tags={"店铺"},
     *     description="删除自提点",
     *     operationId="delPickupLocation",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="自提点id", required=true, type="number"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function delPickupLocation($id, Request $request)
    {
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        $pickupLocationService = new PickupLocationService();
        $pickupLocationService->deleteBy($filter);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/pickuplocation/reldistributor",
     *     summary="自提点关联门店",
     *     tags={"店铺"},
     *     description="自提点关联门店",
     *     operationId="relDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id[]", in="formData", description="自提点id", required=true, type="number"),
     *     @SWG\Parameter( name="rel_distributor_id", in="formData", description="关联店铺id", required=true, type="number"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function relDistributor(Request $request)
    {
        $params = $request->all('id', 'rel_distributor_id');

        $rules = [
            'id' => ['required', trans('DistributionBundle/Controllers/PickupLocation.id_required')],
            'rel_distributor_id' => ['required', trans('DistributionBundle/Controllers/PickupLocation.rel_distributor_id_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $pickupLocationService = new PickupLocationService();
        if (is_array($params['id'])) {
            foreach ($params['id'] as $id) {
                $pickupLocationService->relDistributor($companyId, $distributorId, $id, $params['rel_distributor_id']);
            }
        } else {
            $pickupLocationService->relDistributor($companyId, $distributorId, $params['id'], $params['rel_distributor_id']);
        }

        return $this->response->array(['status' => true]);
    }

        /**
     * @SWG\Post(
     *     path="/pickuplocation/reldistributor/cancel",
     *     summary="自提点取消关联门店",
     *     tags={"店铺"},
     *     description="自提点取消关联门店",
     *     operationId="cancelRelDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id[]", in="formData", description="自提点id", required=true, type="number"),
     *     @SWG\Parameter( name="rel_distributor_id", in="formData", description="关联店铺id", required=true, type="number"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function cancelRelDistributor(Request $request)
    {
        $params = $request->all('id', 'rel_distributor_id');

        $rules = [
            'id' => ['required', trans('DistributionBundle/Controllers/PickupLocation.id_required')],
            'rel_distributor_id' => ['required', trans('DistributionBundle/Controllers/PickupLocation.rel_distributor_id_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operatorType == 'distributor') { //店铺端
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        $filter['id'] = $params['id'];
        $filter['rel_distributor_id'] = $params['rel_distributor_id'];

        $pickupLocationService = new PickupLocationService();
        $pickupLocationService->updateBy($filter, ['rel_distributor_id' => 0]);

        return $this->response->array(['status' => true]);
    }
}
