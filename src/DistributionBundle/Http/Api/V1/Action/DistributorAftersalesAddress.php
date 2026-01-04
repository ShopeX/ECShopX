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
use Dingo\Api\Exception\ResourceException;

use DistributionBundle\Services\DistributorAftersalesAddressService;

class DistributorAftersalesAddress extends Controller
{
    /**
     * @SWG\Post(
     *     path="/distributors/aftersalesaddress",
     *     summary="增加/修改店铺售后地址",
     *     tags={"店铺"},
     *     description="增加/修改店铺售后地址",
     *     operationId="setDistributorAfterSalesAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="number"),
     *     @SWG\Parameter( name="province", in="query", description="售后地址省", required=true, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="售后地址市", required=true, type="string"),
     *     @SWG\Parameter( name="area", in="query", description="售后地址区/县", required=true, type="string"),
     *     @SWG\Parameter( name="regions_id", in="query", description="省市区代码json数组", required=false, type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="省市区json数组", required=false, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="店铺售后详细地址", required=true, type="string"),
     *     @SWG\Parameter( name="address_id", in="query", description="店铺售后地址id，传入时为修改，否则为新增", required=false, type="number"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *                  @SWG\Property( property="result", type="object",
     *                          @SWG\Property( property="address_id", type="string", example="42", description="地址id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="104", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="province", type="string", example="北京市", description="省"),
     *                          @SWG\Property( property="city", type="string", example="北京市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="东城区", description="区"),
     *                          @SWG\Property( property="regions_id", type="string", example="['110000','110100','110101']", description="地区id(DC2Type:json_array)"),
     *                          @SWG\Property( property="regions", type="string", example="['北京市','北京市','东城区']", description="省市区合集(DC2Type:json_array)"),
     *                          @SWG\Property( property="address", type="string", example="1", description="具体地址"),
     *                          @SWG\Property( property="contact", type="string", example="1", description="联系人"),
     *                          @SWG\Property( property="mobile", type="string", example="1", description="手机号"),
     *                          @SWG\Property( property="post_code", type="string", example="null", description="邮政编码"),
     *                          @SWG\Property( property="created", type="string", example="1610013243", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612235646", description="修改时间"),
     *                          @SWG\Property( property="is_default", type="string", example="2", description="默认地址, 1:是。2:不是"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function setDistributorAfterSalesAddress(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributor_ids = $request->input('distributor_id', '');
        $province = $request->input('province', '');
        $city = $request->input('city', '');
        $area = $request->input('area', '');
        $regions_id = $request->input('regions_id', '');
        $regions = $request->input('regions', '');
        $address = $request->input('address', '');
        $mobile = $request->input('mobile', '');
        $contact = $request->input('contact', '');
        $address_id = $request->input('address_id', 0);
        $operatorType = app('auth')->user()->get('operator_type');
        $merchantId = 0;
        if ($operatorType == 'merchant') {
            $merchantId = app('auth')->user()->get('merchant_id', 0);
        }
        $supplierId = 0;
        if ($operatorType == 'supplier') {
            $supplierId = app('auth')->user()->get('operator_id', 0);
        }
        if (!$request->get('set_default')) {
            if ($request->isMethod('post')) {
                $distributor_ids = array_map('intval', json_decode($distributor_ids, true));
            } elseif ($request->isMethod('put')) {
                $distributor_ids = intval($distributor_ids);
            }

            if (!$province) {
                throw new ResourceException(trans('DistributionBundle/Controllers/DistributorAftersalesAddress.select_province'));
            }
            if (!$city) {
                throw new ResourceException(trans('DistributionBundle/Controllers/DistributorAftersalesAddress.select_city'));
            }
            if (!$area) {
                throw new ResourceException(trans('DistributionBundle/Controllers/DistributorAftersalesAddress.select_area'));
            }
            if (!$address) {
                throw new ResourceException(trans('DistributionBundle/Controllers/DistributorAftersalesAddress.enter_address'));
            }
            if (!$mobile) {
                throw new ResourceException(trans('DistributionBundle/Controllers/DistributorAftersalesAddress.enter_mobile'));
            }
            if (!$contact) {
                throw new ResourceException(trans('DistributionBundle/Controllers/DistributorAftersalesAddress.enter_contact'));
            }
        }

        $data = [
            'distributor_id' => $distributor_ids,
            'province' => $province,
            'city' => $city,
            'area' => $area,
            'regions_id' => $regions_id,
            'regions' => $regions,
            'address' => $address,
            'company_id' => $companyId,
            'mobile' => $mobile,
            'contact' => $contact,
            'merchant_id' => $merchantId,
            'return_type' => 'logistics',
            'supplier_id' => $supplierId,
        ];

        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $result = ['status' => false];
        if ($address_id === 0 && $request->isMethod('post')) {
            // 新增地址
            $result = $distributorAftersalesAddressService->setDistributorAfterSalesAddress($data);
        } elseif ($address_id !== 0 && $request->isMethod('put')) {
            if ($request->get('set_default')) {
                $result = $distributorAftersalesAddressService->setDefaultAddress($address_id, $companyId);
            } else {
                // 修改地址
                $filter = [
                    'company_id' => $companyId,
                    'address_id' => $address_id
                ];
                $result = $distributorAftersalesAddressService->updateDistributorAfterSalesAddress($filter, $data);
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributors/aftersalesaddress",
     *     summary="获取店铺售后地址列表",
     *     tags={"店铺"},
     *     description="获取店铺售后地址列表",
     *     operationId="getDistributorAfterSalesAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="number"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页条数", required=true, type="number"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="4", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="address_id", type="string", example="43", description="地址id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="province", type="string", example="北京市", description="省"),
     *                          @SWG\Property( property="city", type="string", example="北京市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="东城区", description="区"),
     *                          @SWG\Property( property="regions_id", type="string", example="['110000','110100','110101']", description="地区id(DC2Type:json_array)"),
     *                          @SWG\Property( property="regions", type="string", example="['北京市','北京市','东城区']", description="省市区合集(DC2Type:json_array)"),
     *                          @SWG\Property( property="address", type="string", example="东城路99号", description="具体地址"),
     *                          @SWG\Property( property="contact", type="string", example="策策", description="联系人名称"),
     *                          @SWG\Property( property="mobile", type="string", example="18988888888", description="手机号"),
     *                          @SWG\Property( property="post_code", type="string", example="null", description="邮政编码"),
     *                          @SWG\Property( property="created", type="string", example="1610013471", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1610013471", description="修改时间"),
     *                          @SWG\Property( property="is_default", type="string", example="1", description="默认地址, 1:是。2:不是"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getDistributorAfterSalesAddress(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $requestData = $request->input();
        $page = $request -> input('page', 1);
        $page_size = $request -> input('page_size', 10);

        $filter = [
            'company_id' => $companyId,
            'return_type' => 'logistics',
        ];
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        } else {
            $filter['supplier_id'] = 0;
        }
        if (isset($requestData['province']) && trim($requestData['province'])) {
            $filter['province|contains'] = $requestData['province'];
        }
        if (isset($requestData['city']) && trim($requestData['city'])) {
            $filter['city|contains'] = $requestData['city'];
        }
        if (isset($requestData['area']) && trim($requestData['area'])) {
            $filter['area|contains'] = $requestData['area'];
        }
        if (isset($requestData['distributor_id']) && $requestData['distributor_id'] != '') {
            $filter['distributor_id'] = $requestData['distributor_id'];
        }

        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $list = $distributorAftersalesAddressService->getDistributorAfterSalesAddress($filter, $page, $page_size);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $list['datapass_block'] = $datapassBlock;
        if ($list['list']) {
            foreach ($list['list'] as $key => $value) {
                if ($datapassBlock) {
                    $list['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $list['list'][$key]['contact'] = data_masking('truename', (string) $value['contact']);
                    $list['list'][$key]['address'] = data_masking('address', (string) $value['address']);
                }
            }
        }
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/distributors/aftersalesaddress/{address_id}",
     *     summary="获取店铺售后地址详情",
     *     tags={"店铺"},
     *     description="获取店铺售后地址详情",
     *     operationId="getDistributorAfterSalesAddressDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="address_id", type="string", example="45", description="地址id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="1", description="分销商id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="province", type="string", example="北京市", description="省"),
     *                  @SWG\Property( property="city", type="string", example="北京市", description="市"),
     *                  @SWG\Property( property="area", type="string", example="东城区", description="区"),
     *                  @SWG\Property( property="regions_id", type="string", example="['110000','110100','110101']", description="地区id(DC2Type:json_array)"),
     *                  @SWG\Property( property="regions", type="string", example="['北京市','北京市','东城区']", description="省市区合集(DC2Type:json_array)"),
     *                  @SWG\Property( property="address", type="string", example="121", description="具体地址"),
     *                  @SWG\Property( property="contact", type="string", example="121", description="联系人"),
     *                  @SWG\Property( property="mobile", type="string", example="121", description="手机号"),
     *                  @SWG\Property( property="post_code", type="string", example="null", description="邮政编码"),
     *                  @SWG\Property( property="created", type="string", example="1611631597", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611631597", description="修改时间"),
     *                  @SWG\Property( property="is_default", type="string", example="1", description="默认地址, 1:是。2:不是"),
     *                  @SWG\Property( property="name", type="string", example="普天信息产业园测试1", description=""),
     *                  @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getDistributorAfterSalesAddressDetail(Request $request, $address_id)
    {
        // ModuleID: 76fe2a3d
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'address_id' => $address_id,
        ];
        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $result = $distributorAftersalesAddressService->getDistributorAfterSalesAddressDetail($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/distributors/aftersalesaddress/{address_id}",
     *     summary="删除店铺售后地址",
     *     tags={"店铺"},
     *     description="删除店铺售后地址",
     *     operationId="deleteDistributorAfterSalesAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function deleteDistributorAfterSalesAddress(Request $request, $address_id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'address_id' => $address_id,
        ];
        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $result = $distributorAftersalesAddressService->deleteDistributorAfterSalesAddress($filter);
        return $this->response->array($result);
    }
}
