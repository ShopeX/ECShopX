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

use DistributionBundle\Services\BasicConfigService;

use Dingo\Api\Exception\StoreResourceFailedException;

class BasicConfig extends Controller
{
    /**
     * @SWG\Post(
     *     path="/distribution/basic_config",
     *     summary="保存分销基础配置",
     *     tags={"店铺"},
     *     description="保存分销基础配置",
     *     operationId="saveBasicConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_buy", in="query", description="分销商是否可购买", required=true, type="string"),
     *     @SWG\Parameter( name="limit_rebate", in="query", description="佣金金额满多少元后，可以提现", required=true, type="string"),
     *     @SWG\Parameter( name="limit_time", in="query", description="自订单完成后多少天，可以提现", required=true, type="string"),
     *     @SWG\Parameter( name="return_name", in="query", description="退换货收货人", required=true, type="string"),
     *     @SWG\Parameter( name="return_address", in="query", description="退换货地址", required=true, type="string"),
     *     @SWG\Parameter( name="return_phone", in="query", description="退换货联系方式", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="stirng"),
     *                     @SWG\Property(property="name", type="stirng"),
     *                     @SWG\Property(property="address", type="stirng"),
     *                     @SWG\Property(property="mobile", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function saveBasicConfig(Request $request)
    {
        // ShopEx EcShopX Service Component
        $params = $request->all('is_buy', 'limit_rebate', 'limit_time', 'return_address', 'return_phone', 'return_name');

        $rules = [
            'return_name' => ['required', trans('DistributionBundle/Controllers/BasicConfig.return_name_required')],
            'return_address' => ['required', trans('DistributionBundle/Controllers/BasicConfig.return_address_required')],
            'return_phone' => ['required', trans('DistributionBundle/Controllers/BasicConfig.return_phone_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');

        $distributorService = new BasicConfigService();
        $data = $distributorService->saveBasicConfig($companyId, $params);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/distribution/basic_config",
     *     summary="获取分销基础配置",
     *     tags={"店铺"},
     *     description="获取分销基础配置",
     *     operationId="getBasicConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="stirng"),
     *                     @SWG\Property(property="name", type="stirng"),
     *                     @SWG\Property(property="address", type="stirng"),
     *                     @SWG\Property(property="mobile", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getBasicConfig(Request $request)
    {
        // CONST: 1E236443
        $companyId = app('auth')->user()->get('company_id');

        $distributorService = new BasicConfigService();
        $data = $distributorService->getInfoById($companyId);

        return $this->response->array($data);
    }
}
