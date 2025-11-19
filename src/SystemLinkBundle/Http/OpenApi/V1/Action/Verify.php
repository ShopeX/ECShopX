<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Http\OpenApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Http\Controllers\Controller as Controller;

class Verify extends Controller
{
    public function openApi(Request $request)
    {
        $params = $request->all();

        app('log')->debug('openapi_request=>:'.var_export($params, 1));

        foreach ((array)$params as $key => $val) {
            $params[$key] = trim($val);
        }

        $openapiAct = [
            'ecapi.site.create' => 'Company@create', //站点开通
            'ecapi.site.close' => 'Company@close', //站点关闭
            'ecapi.site.renew' => 'Company@renew', //站点续费
            'ecapi.site.checkopen' => 'Company@checkisopen', // 检查是否已开通
        ];


        if (!isset($params['method']) || !isset($openapiAct[trim($params['method'])]) || !$openapiAct[trim($params['method'])]) {
            app('log')->debug('openapi_request_result=>:'.$params['method'].'接口不存在');
            $this->api_response('fail', '接口不存在');
        }

        list($ctl, $act) = explode('@', trim($openapiAct[$params['method']]));

        if (!$ctl || !$act) {
            app('log')->debug('openapi_request_result=>:'.$ctl.'或'.$act.'方法不存在');
            $this->api_response('fail', '方法不存在');
        }

        $className = 'SystemLinkBundle\Http\OpenApi\V1\Action\\'.$ctl;

        $ctlObj = new $className();

        return $ctlObj->$act($request);
    }
}
