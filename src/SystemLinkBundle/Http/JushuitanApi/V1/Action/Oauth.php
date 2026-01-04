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

namespace SystemLinkBundle\Http\JushuitanApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Http\Controllers\Controller as Controller;

use SystemLinkBundle\Services\Jushuitan\Request as ShuyunRequest;
use SystemLinkBundle\Services\JushuitanSettingService;

class Oauth extends Controller
{

    public function callback(Request $request)
    {
        $params = $request->query();

        foreach((array)$params as $key=>$val)
        {
            $params[$key] = trim($val);
        }
        $companyId = $params['state'] ?? '';
        if (!$companyId) {
            app('log')->debug('jushuitan oauthcallback request 缺少state参数');
            $this->api_response_shuyun('fail', '缺少必要参数');
        }

        $code = $params['code'] ?? '';
        if (!$code) {
            app('log')->debug('jushuitan oauthcallback request 缺少code参数');
            $this->api_response_shuyun('fail', '缺少必要参数');
        }

        $shuyunRequest = new ShuyunRequest($companyId);
        // 验证签名
        if (!isset($params['sign']) || !$params['sign'])
        {
            $params['sign'] = '';
        }

        $sign = trim($params['sign']);
        
        unset($params['sign']);

        app('log')->debug('JushuitanCheck oauth sign:' . $shuyunRequest->getOauthSign($params));
        app('log')->debug('JushuitanCheck oauth params:' . var_export($params, true));
        if (!$sign || $sign != $shuyunRequest->getOauthSign($params) )
        {
            $this->api_response_shuyun('fail', 'sign error');
        }
        
        
        // 根据code获取access_token
        $method = 'oauth_token';

        $result = $shuyunRequest->call($method, ['code' => $code]);
        // 存储数据
        if ($result['code'] != 0) {
            $this->api_response_shuyun('fail', $result['msg']);
        }
        $jushuitanSettingService = new JushuitanSettingService();
        $setting = $jushuitanSettingService->getJushuitanSetting($companyId);
        $setting['access_token'] = $result['data']['access_token'] ?? '';
        $setting['expires_in'] = $result['data']['expires_in'] ?? '';
        $setting['refresh_token'] = $result['data']['refresh_token'] ?? '';
        $setting['scope'] = $result['data']['scope'] ?? '';
        $jushuitanSettingService->setJushuitanSetting($companyId, $setting);
        $this->api_response_shuyun('true', '成功');
    }

}
