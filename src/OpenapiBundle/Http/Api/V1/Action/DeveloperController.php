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
use OpenapiBundle\Services\DeveloperService;
use CompanysBundle\Services\OperatorsService;
use CompanysBundle\Jobs\EmployeeJob;

class DeveloperController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/setting/openapi/developer",
     *     summary="获取开发配置",
     *     tags={"开放接口"},
     *     description="获取开发配置",
     *     operationId="DeveloperController_info",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="developer_id", type="string", example="1", description="developer_id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="app_key", type="string", example="test001", description="app_key"),
     *                  @SWG\Property( property="app_secret", type="string", example="token001", description="app_secret"),
     *          ),
     *     )),
     * )
     */
    public function info()
    {
        $companyId = app('auth')->user()->get('company_id');
        $developerService = new DeveloperService();
        $result = $developerService->detail($companyId);
        // $result['app_secret'] = substr($result['app_secret'], 0, 6) . '***************' . substr($result['app_secret'], -6);
        $result['external_app_secret'] = substr($result['external_app_secret'], 0, 6) . '***************' . substr($result['external_app_secret'], -6);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/setting/openapi/developer",
     *     summary="保存开发配置",
     *     tags={"开放接口"},
     *     description="保存开发配置",
     *     operationId="DeveloperController_update",
     *     @SWG\Parameter( in="formData", type="string", required=false, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="app_secret", description="app_secret" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="成功"),
     *          ),
     *     )),
     * )
     */
    public function update(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all([
            'app_key',
            'app_secret',
        ]);
        $this->checkParams($params);
        $developerService = new DeveloperService();
        $developerService->update($companyId, $params);

        $operatorsService = new OperatorsService();
        $list = $operatorsService->lists(['company_id' => $companyId, 'operator_type' => 'staff']);
        foreach ($list['list'] as $operator) {
            $eventData = [
                'company_id' => $operator['company_id'],
                'login_name' => $operator['login_name'],
                'mobile' => $operator['mobile'],
                'user_name' => $operator['username'],
                'password' => $operator['password'],
                'synctype' => 'add',
            ];
            $gotoJob = (new EmployeeJob($eventData))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

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
            'app_key' => ['required', 'app_key必填'],
            // 'app_secret' => ['required', 'app_secret必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        return [
            'app_key' => $params['app_key'],
            'app_secret' => $params['app_secret'] ?? '',
        ];
    }
}
