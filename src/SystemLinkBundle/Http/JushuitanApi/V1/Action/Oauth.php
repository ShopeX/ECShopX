<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
