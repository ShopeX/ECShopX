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

namespace EspierBundle\Http\FrontApi\V1\Action;

use EspierBundle\Services\SubdistrictService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

class SubdistrictController extends Controller
{

    /**
     * @SWG\Get(
     *     path="/espier/subdistrict",
     *     summary="获取街道社区列表",
     *     tags={"系统"},
     *     description="获取街道社区列表",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="integer", description=""),
     *                  @SWG\Property( property="company_id", type="integer", description=""),
     *                  @SWG\Property( property="label", type="string", description="街道名称"),
     *                  @SWG\Property( property="parent_id", type="integer", description="父级id, 0为顶级0"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="integer", description=""),
     *                          @SWG\Property( property="company_id", type="integer", description=""),
     *                          @SWG\Property( property="label", type="string", description="社区名称"),
     *                          @SWG\Property( property="parent_id", type="integer", description="父级id"),
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function get(Request $request){
        $user = $request->get('auth');
        $companyId = $user['company_id'];

        $params = $request->all('distributor_id', 'receiver_state', 'receiver_city', 'receiver_district');
        $distributorId = $params['distributor_id'] ?? 0;

        $regions = [];
        if ($params['receiver_state'] ?? '') {
            $regions['province'] = $params['receiver_state'];
        }
        if ($params['receiver_city'] ?? '') {
            $regions['city'] = $params['receiver_city'];
        }
        if ($params['receiver_district'] ?? '') {
            $regions['area'] = $params['receiver_district'];
        }

        $subdistrictService = new SubdistrictService();
        $subdistrict = $subdistrictService->getSubdistrict($companyId, $distributorId, $regions);
        return $this->response->array($subdistrict);
    }
}