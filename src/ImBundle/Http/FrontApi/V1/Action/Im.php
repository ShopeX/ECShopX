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

namespace ImBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use ImBundle\Services\ImService;

class Im extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/im/meiqia",
     *     summary="获取美洽配置",
     *     tags={"IM"},
     *     description="获取美洽配置",
     *     operationId="meiqiaInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
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
     *                     @SWG\Property( property="is_open", type="string", example="false", description="美洽是否开启 true:开启,false:关闭"),
     *                     @SWG\Property(property="meiqia_url", type="string", example="false", description="美洽客服链接"),
     *                     @SWG\Property(property="is_distributor_open", type="boolean", example="false", description="店铺客服状态"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function meiqiaInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $imService = new ImService();
        $result = $imService->getImInfo($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/im/meiqia/distributor/{distributor_id}",
     *     summary="获取店铺美洽配置",
     *     tags={"IM"},
     *     description="获取店铺美洽配置",
     *     operationId="getDistributorMeiQiaSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="path",
     *         description="店铺id",
     *         required=true,
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
     *                     @SWG\Property(property="channel", type="string", example="", description="渠道"),
     *                     @SWG\Property(property="meiqia_url", type="string", example="", description="美洽客服链接"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function getDistributorMeiQiaSetting($distributor_id, Request $request)
    {
        $imService = new ImService();
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $result = $imService->getDistributorMeiQia($companyId, intval($distributor_id));
        return $this->response->array($result);
    }
}
