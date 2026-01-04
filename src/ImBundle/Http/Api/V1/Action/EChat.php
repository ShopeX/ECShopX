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

namespace ImBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use ImBundle\Services\EChatService;

class EChat extends Controller
{
    /**
     * @SWG\Get(
     *     path="/im/echat",
     *     summary="获取一洽配置",
     *     tags={"IM"},
     *     description="获取一洽配置",
     *     operationId="getInfo",
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
     *             @SWG\Items(ref="#/definitions/EChat")
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $echatService = new EChatService();
        $result = $echatService->getInfo($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/im/echat",
     *     summary="保存一洽配置",
     *     tags={"IM"},
     *     description="保存一洽配置",
     *     operationId="saveInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="is_open",
     *         in="path",
     *         description="是否开启 true false",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="echat_url",
     *         in="path",
     *         description="一洽客服链接地址",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *              @SWG\Items(ref="#/definitions/EChat")
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function saveInfo(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $data = $request->all('is_open', 'echat_url');
        $data['is_open'] = 'true' == $data['is_open'] ? 'true' : 'false';
        $echatService = new EChatService();
        $result = $echatService->saveInfo($companyId, $data);

        return $this->response->array($result);
    }
}
