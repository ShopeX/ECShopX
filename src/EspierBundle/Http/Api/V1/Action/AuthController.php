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
