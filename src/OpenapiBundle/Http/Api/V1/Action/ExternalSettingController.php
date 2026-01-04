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

namespace OpenapiBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Request;
use OpenapiBundle\Services\ExternalSettingService;

class ExternalSettingController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/setting/openapi/external",
     *     summary="外部请求配置-获取配置详情接口",
     *     tags={"开放接口"},
     *     description="外部请求配置-获取配置详情接口",
     *     operationId="ExternalSettingController_getConfig",
     *     @SWG\Parameter( in="header", type="string", required=false, name="Authorization", description="jwt签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base_uri", type="string", example="http://ecshopx.shopex123.com/", description="自行更改字段描述"),
     *                  @SWG\Property( property="app_key", type="string", example="app_key", description="自行更改字段描述"),
     *                  @SWG\Property( property="app_secret", type="string", example="app_secret", description="自行更改字段描述"),
     *          ),
     *     )),
     * )
     */
    public function getConfig()
    {
        $companyId = app('auth')->user()->get('company_id');
        $salespersonService = new ExternalSettingService();
        $config = $salespersonService->getConfig($companyId);
        return $this->response->array($config);
    }

    /**
     * @SWG\Post(
     *     path="/setting/openapi/external",
     *     summary="外部请求配置-获取配置详情接口",
     *     tags={"开放接口"},
     *     description="外部请求配置-获取配置详情接口",
     *     operationId="ExternalSettingController_getConfig",
     *     @SWG\Parameter( in="header", type="string", required=false, name="Authorization", description="jwt签名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="base_uri", description="描述" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="app_key", description="描述" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="app_secret", description="描述" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object",
     *              @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     * )
     */
    public function setConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $request = $request->all([
            'base_uri',
            'app_key',
            'app_secret',
        ]);
        $salespersonService = new ExternalSettingService();
        $params = $this->checkParams($request);
        $salespersonService->setConfig($companyId, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * 验证参数.
     *
     * @param array $params 参数
     */
    private function checkParams(array $params)
    {
        $rules = [
            'base_uri' => ['required', 'base_uri必填'],
            'app_key' => ['required', 'app_key必填'],
            'app_secret' => ['required', 'app_secret必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        return [
            'base_uri' => $params['base_uri'],
            'app_key' => $params['app_key'],
            'app_secret' => $params['app_secret'],
        ];
    }
}
