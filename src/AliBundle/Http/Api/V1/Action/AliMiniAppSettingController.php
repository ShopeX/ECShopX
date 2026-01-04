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

namespace AliBundle\Http\Api\V1\Action;

use AliBundle\Kernel\Config;
use AliBundle\Services\AliMiniAppSettingService;
use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use function app;
use function validator_params;

class AliMiniAppSettingController extends Controller
{
    public function actionInfo()
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new AliMiniAppSettingService();
        $result = $service->getInfoByCompanyId($companyId);

        return $this->response->array($result);
    }

    public function actionSave(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all(
            'setting_id',
            'authorizer_appid',
            'merchant_private_key',
            'api_sign_method',
            'alipay_cert_path',
            'alipay_root_cert_path',
            'merchant_cert_path',
            'alipay_public_key',
            'notify_url',
            'encrypt_key'
        );
        $rules = [
            'authorizer_appid' => ['required', 'authorizer_appid 必填'],
            'merchant_private_key' => ['required', 'merchant_private_key 必填'],
            'api_sign_method' => ['required', 'api_sign_method 必填'],
        ];
        $params['company_id'] = $companyId;
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        if (!in_array($params['api_sign_method'], array_keys(Config::API_SIGN_METHOD_ARRAY))) {
            $msg = 'api_sign_method 参数错误，只能为【'.implode(',', array_keys(Config::API_SIGN_METHOD_ARRAY)).'】中的一种';
            throw new ResourceException($msg);
        }
        $service = new AliMiniAppSettingService();
        $service->save($params);

        return $this->response->array(['status' => true]);
    }
}
