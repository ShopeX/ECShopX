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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use SuperAdminBundle\Services\GlobalconfigService;

class Globalconfig extends Controller
{
    /**
     * @SWG\Get(
     *     path="/superadmin/globalconfig/getinfo", summary="获取全局配置", tags={"全局配置"}, description="获取全局配置",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="title", type="string", example="test", description="标题"),
     *                  @SWG\Property( property="logo_url", type="string", example="logo url", description="LOGO"),
     *                  @SWG\Property( property="background_url", type="string", example="background url", description="背景图"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getinfo(Request $request)
    {
        // IDX: 2367340174
        $globalconfig = new GlobalconfigService();
        $info = $globalconfig->getinfo();
        return $this->response->array($info);
    }

    /**
     * @SWG\Put(path="/superadmin/globalconfig/saveset", tags={"全局配置"}, summary="保存全局设置", description="保存全局设置", operationId="saveset", produces={"application/json"},
     *   @SWG\Parameter( in="query", name="title", description="标题", required=true, type="string" ),
     *   @SWG\Parameter( in="query", name="logo_url", description="logo", required=true, type="string" ),
     *   @SWG\Parameter( in="query", name="background_url", description="背景图", required=true, type="string" ),
     *   @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="string", example="", description="保存成功"),
     *          ),
     *     )),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function saveset(Request $request)
    {
        $params = $request->input();
        $Globalconfig = new GlobalconfigService();
        $data = $Globalconfig->saveset($params);

        return $this->response->array($data);
    }
}
