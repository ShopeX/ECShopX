<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
