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

namespace EspierBundle\Http\SuperApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SuperAdmin extends Controller
{
    protected $sessonExpier = 604800;

    /**
     * @SWG\Post(
     *     path="/login",
     *     summary="管理平台登录",
     *     tags={"平台管理"},
     *     description="管理平台登录",
     *     operationId="login",
     *     @SWG\Parameter( name="username", in="query", description="登录用户名", required=true, type="string"),
     *     @SWG\Parameter( name="password", in="query", description="用户名密码", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function login(Request $request)
    {
        // if (empty($request->input('username')) || empty($request->input('password')) ) {
        //     throw new BadRequestHttpException('请输入正确参数');
        // }

        // if ($request->input('username') != env('SUPER_ADMIN_LOGIN_NAME') || sha1($request->input('password')) != env('SUPER_ADMIN_LOGIN_PASSWORD')) {
        //     throw new BadRequestHttpException('用户名或密码错误:'.__LINE__);
        // }

        // $randomkey = $this->randomFromDev(16);
        // $sessionkey = sha1($randomkey);
        // $sessionValue = [
        //     'username' => $request->input('username'),
        //     'exp'      => time() + $this->sessonExpier,
        // ];
        // $sessionValue = json_encode($sessionValue);

        // app('redis')->connection('espier')->setex('superAdminSession3rd:'.$sessionkey, $this->sessonExpier, $sessionValue);
        // $data['token'] = $sessionkey;
        // $data['exp']  = time() + $this->sessonExpier;
        // return $this->response->array($data);
    }

    // 取随机码，用于生成session
    private function randomFromDev($len)
    {
        // $fp = @fopen('/dev/urandom', 'rb');
        // $result = '';
        // if ($fp !== FALSE) {
        //     $result .= @fread($fp, $len);
        //     @fclose($fp);
        // } else {
        //     throw new BadRequestHttpException('登录失败');
        // }
        // // convert from binary to string
        // $result = base64_encode($result);
        // // remove none url chars
        // $result = strtr($result, '+/', '-_');
        // return substr($result, 0, $len);
    }
}
