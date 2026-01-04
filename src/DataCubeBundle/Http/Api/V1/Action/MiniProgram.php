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

namespace DataCubeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use DataCubeBundle\Services\MiniProgramService;

class MiniProgram extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/datacube/miniprogram/pages",
     *     summary="获取小程序的页面及参数信息",
     *     tags={"统计"},
     *     description="获取小程序的页面及参数信息",
     *     operationId="getPages",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="wxappid", in="query", description="小程序appid", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="page", type="string"),
     *                     @SWG\Property(property="label", type="string"),
     *                     @SWG\Property(property="pathParams", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getPages(Request $request)
    {
        $params = $request->input();

        $validator = app('validator')->make($params, [
            'wxappid' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('添加来源出错.', $validator->errors());
        }

        $authInfo = app('auth')->user()->get();

        $miniProgramService = new MiniProgramService($authInfo['company_id'], $params['wxappid']);

        $result = $miniProgramService->getPages();

        return $this->response->array($result);
    }
}
