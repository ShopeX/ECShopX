<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request){
        // 保留验证码相关参数（根据ADMIN_LOGIN_CHECK_LEVEL配置决定是否验证）
        switch (env('ADMIN_LOGIN_CHECK_LEVEL', 'yzm')) {
            case 'img_code':
                $credentials = app('request')->only('username', 'password', 'logintype', 'product_model', 'agreement_id', 'token', 'yzm');
                break;
            default:
                $credentials = app('request')->only('username', 'password', 'logintype', 'product_model', 'agreement_id');
                break;
        }
        
        // 默认使用本地账号登录
        if (!isset($credentials['logintype'])) {
            $credentials['logintype'] = 'localadmin';
        }
        
        $token = app('auth')->guard('api')->attempt($credentials);
        return response()->json(['data'=>['token'=>$token]]);
    }

    public function getLevel(){
        return $this->response->array(['level' => env('ADMIN_LOGIN_CHECK_LEVEL', '')]);
    }
}
