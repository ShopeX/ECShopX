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

namespace CompanysBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;

use Dingo\Api\Exception\ResourceException;
use EasyWeChat\Factory;
use Illuminate\Http\Request;

use WorkWechatBundle\Services\WorkWechatService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use WechatBundle\Services\Admin\ApplicationService;
use SalespersonBundle\Services\SalespersonService;

use GuzzleHttp\Client as Client;

class Wxapp extends Controller
{
    protected $sessonExpier = 604800;

    /**
     * @SWG\Post(
     *     path="/wxapp/login",
     *     summary="平台小程序登录",
     *     tags={"企业"},
     *     description="平台小程序登录",
     *     operationId="login",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="appname", in="formData", description="小程序名称", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="formData", description="小程序登录时获取的code", required=true, type="string"),
     *     @SWG\Parameter( name="encryptedData", in="formData", description="小程序登录时用户授权手机号的加密数据", required=true, type="string"),
     *     @SWG\Parameter( name="iv", in="formData", description="小程序登录时用户授权手机号加密算法的初始向量", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="session3rd", type="string", description=""),
     *                 @SWG\Property(property="phoneNumber", type="string", description="手机号"),
     *                 @SWG\Property(property="salesperson_name", type="string", description="导购员姓名"),
     *                 @SWG\Property(property="salesperson_type", type="string", description="导购员类型"),
     *                 @SWG\Property(property="baidu_access_token", type="string", description="百度access_token"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function login(Request $request)
    {
        $appname = $request->input('appname');
        if (empty($appname)) {
            throw new BadRequestHttpException('缺少参数，登录失败');
        }

        $miniProgramService = new ApplicationService();

        $app = $miniProgramService->getWxappApplication($appname);

        //code换取session_key
        $code = $request->input('code');
        $result = $app->auth->session($code);

        //解密用户授权的手机号数据信息
        $phoneNumberData = $app->encryptor->decryptData($result['session_key'], $request->input('iv'), $request->input('encryptedData'));

        //验证用户手机号是否可以登录
        $salespersonService = new SalespersonService();
        //purePhoneNumber 没有区号的手机号
        //手机号获取核销员信息
        $mobileArr = [$phoneNumberData['purePhoneNumber'], $phoneNumberData['phoneNumber']];
        $salespersonInfo = $salespersonService->getSalespersonByMobileByType($mobileArr, ['admin', 'verification_clerk'], 'true');
        if (!$salespersonInfo) {
            throw new BadRequestHttpException('当前手机号无权限');
        }

        $randomkey = $this->randomFromDev(16);
        $sessionkey = sha1($randomkey.$result['openid']);
        $sessionValue = [
            'open_id' => $result['openid'],
            'session_key' => $result['session_key'],
            'appname' => $appname,
            'phoneNumber' => $salespersonInfo['mobile'],
            'company_id' => $salespersonInfo['company_id'],
            'salesperson_id' => $salespersonInfo['salesperson_id'],
            'salesperson_name' => $salespersonInfo['name'],
            'salesperson_type' => $salespersonInfo['salesperson_type'],
        ];
        $sessionValue = json_encode($sessionValue);

        app('redis')->connection('wechat')->setex('adminSession3rd:'.$sessionkey, $this->sessonExpier, $sessionValue);
        $data['session3rd'] = $sessionkey;
        $data['phoneNumber'] = $salespersonInfo['mobile'];
        $data['salesperson_name'] = $salespersonInfo['name'];
        $data['salesperson_type'] = $salespersonInfo['salesperson_type'];

        // 核销小程序语言播报获取token
        $client = new Client();
        $baiduAuth = $client->post('https://openapi.baidu.com/oauth/2.0/token', [
            'verify' => false,
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => 'qFyvllG4Ak95I5fjSLrTeobn',
                'client_secret' => '57129664bbf1b1bd2eb2b098caadd348'
            ]
        ])->getBody();
        $content = $baiduAuth->getContents();
        $baiduAuthInfo = json_decode($content, true);
        $data['baidu_access_token'] = $baiduAuthInfo['access_token'];

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/workwechatlogin",
     *     summary="导购小程序登录",
     *     tags={"企业"},
     *     description="导购小程序登录",
     *     operationId="workwechatlogin",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="appname", in="formData", description="小程序名称", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="formData", description="小程序登录时获取的code", required=true, type="string"),
     *     @SWG\Parameter( name="encryptedData", in="formData", description="小程序登录时用户授权手机号的加密数据", required=true, type="string"),
     *     @SWG\Parameter( name="iv", in="formData", description="小程序登录时用户授权手机号加密算法的初始向量", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="session3rd", type="string", description=""),
     *                 @SWG\Property(property="company_id", type="string", description="公司id"),
     *                 @SWG\Property(property="distributor_id", type="string", description="分销商id"),
     *                 @SWG\Property(property="avatar", type="string", description="头像"),
     *                 @SWG\Property(property="phoneNumber", type="string", description="手机号"),
     *                 @SWG\Property(property="salesperson_name", type="string", description="导购员姓名"),
     *                 @SWG\Property(property="salesperson_type", type="string", description="导购员类型"),
     *                 @SWG\Property(property="salesperson_id", type="string", description="导购员id"),
     *                 @SWG\Property(property="baidu_access_token", type="string", description="百度access_token"),
     *                 @SWG\Property(property="store_name", type="string", description="门店名称"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function workwechatlogin(Request $request)
    {
        if (empty($request->input('appname'))) {
            throw new BadRequestHttpException('缺少参数，登录失败');
        }

        $workWechatService = new WorkWechatService();
        $randomkey = $this->randomFromDev(16);
        $result = null;
        $companyId = $workWechatService->getCompanyIdByAppid($request->input('app_id'));
        $workWechatInfo = [];
        if (!$workWechatService->checkCodeAuth($companyId)) {
            try {
                $code = $request->input('code');
                $miniProgramService = new ApplicationService();

                $miniProgram = $miniProgramService->getWxappApplication($request->input('appname'));

                //code换取session_key
                $result = $miniProgram->sns->getSessionKey($code);

                //解密用户授权的手机号数据信息
                $phoneNumberData = $miniProgram->encryptor->decryptData($result->session_key, $request->input('iv'), $request->input('encryptedData'));

                if (!is_array($phoneNumberData)) {
                    throw new ResourceException('登录失败');
                }

                $mobileArr = [$phoneNumberData['purePhoneNumber'], $phoneNumberData['phoneNumber']];
                $sessionkey = sha1($randomkey . $result->openid);
            } catch (\Exception $e) {
                throw new ResourceException('当前状态不允许普通登录');
            }
        } else {
            try {
                $sessionKey = $request->input('session_key');
                $encryptedData = $request->input('encryptedData');
                $iv = $request->input('iv');

                $config = app('wechat.work.wechat')->getConfig($companyId);

                $wechatWork = Factory::work($config);
                $workWechatInfo = $wechatWork->miniProgram()->encryptor->decryptData($sessionKey, $iv, $encryptedData);
                // 读取成员详细
                $userInfo = $wechatWork->user->get($workWechatInfo['userid']);
                $mobileArr = [$userInfo['mobile'], $userInfo['telephone']];
                $sessionkey = sha1($randomkey . $userInfo['userid']);
            } catch (\Exception $e) {
                throw new ResourceException('登录失败请联系后台管理员');
            }
        }
        //验证用户手机号是否可以登录
        $salespersonService = new SalespersonService();
        //purePhoneNumber 没有区号的手机号
        //手机号获取核销员信息
        $salespersonInfo = $salespersonService->getSalespersonByMobileByType($mobileArr, 'shopping_guide', 'true');
        if (!$salespersonInfo) {
            throw new BadRequestHttpException('当前手机号无权限');
        }
        if (!isset($salespersonInfo['store_name'])) {
            throw new BadRequestHttpException('请在后台为当前导购添加店铺');
        }
        if (!$salespersonInfo['store_name']) {
            throw new BadRequestHttpException('请在后台为当前导购添加店铺');
        }

        if (isset($workWechatInfo['userid'])) {
            $salespersonService->salesperson->updateSalespersonById($salespersonInfo['salesperson_id'], ['work_userid' => $workWechatInfo['userid'], 'avatar' => $userInfo['avatar']]);
        }

        if (isset($workWechatInfo['userid'])) {
            $config = app('wechat.work.wechat')->getConfig($companyId);

            $configId = $salespersonInfo['work_configid'] ?? null;

            $contactWayConfig = [
                'style' => 1,
                'skip_verify' => true,
                'user' => $workWechatInfo['userid'],
            ];

            if (!$salespersonInfo['work_configid']) {
                $configIdTemp = Factory::work($config)->contact_way->create(1, 1, $contactWayConfig);
                $configId = $configIdTemp['config_id'] ?? null;
            }

            $qrcodeConfigId = $salespersonInfo['work_qrcode_configid'] ?? null;
            if (!$salespersonInfo['work_qrcode_configid']) {
                $qrcodeConfigIdTemp = Factory::work($config)->contact_way->create(1, 2, $contactWayConfig);
                $qrcodeConfigId = $qrcodeConfigIdTemp['config_id'] ?? null;
            }

            $salespersonService->salesperson->updateSalespersonById($salespersonInfo['salesperson_id'], ['work_configid' => $configId, 'work_qrcode_configid' => $qrcodeConfigId]);
        }
        $sessionValue = [
            'open_id' => $result->openid ?? $workWechatInfo['userid'],
            'session_key' => $result->session_key ?? $sessionKey,
            'appname' => $request->input('appname'),
            'phoneNumber' => $salespersonInfo['mobile'],
            'company_id' => $salespersonInfo['company_id'],
            'salesperson_id' => $salespersonInfo['salesperson_id'],
            'salesperson_name' => $salespersonInfo['name'],
            'salesperson_type' => $salespersonInfo['salesperson_type'],
        ];
        $sessionValue = json_encode($sessionValue);

        app('redis')->connection('wechat')->setex('adminSession3rd:'.$sessionkey, $this->sessonExpier, $sessionValue);
        $data['session3rd'] = $sessionkey;
        $data['company_id'] = $salespersonInfo['company_id'];
        $data['phoneNumber'] = $salespersonInfo['mobile'];
        $data['distributor_id'] = $salespersonInfo['distributor_id'] ?? 0;
        $data['avatar'] = $userInfo['avatar'] ?? '';
        $data['salesperson_name'] = $salespersonInfo['name'];
        $data['salesperson_type'] = $salespersonInfo['salesperson_type'];
        $data['salesperson_id'] = $salespersonInfo['salesperson_id'];
        $data['store_name'] = $salespersonInfo['store_name'];

        // 核销小程序语言播报获取token
        $client = new Client();
        $baiduAuth = $client->post('https://openapi.baidu.com/oauth/2.0/token', [
            'verify' => false,
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => 'qFyvllG4Ak95I5fjSLrTeobn',
                'client_secret' => '57129664bbf1b1bd2eb2b098caadd348'
            ]
        ])->getBody();
        $content = $baiduAuth->getContents();
        $baiduAuthInfo = json_decode($content, true);
        $data['baidu_access_token'] = $baiduAuthInfo['access_token'];

        return $this->response->array($data);
    }

    // 取随机码，用于生成session
    private function randomFromDev($len)
    {
        $fp = @fopen('/dev/urandom', 'rb');
        $result = '';
        if ($fp !== false) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        } else {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');
        return substr($result, 0, $len);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/check",
     *     summary="导购小程序登录session检测",
     *     tags={"企业"},
     *     description="导购小程序登录",
     *     operationId="workwechatlogin",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="code", in="formData", description="小程序登录时获取的code", required=true, type="string"),
     *     @SWG\Parameter( name="app_id", in="formData", description="小程序id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function checkSessionKey(Request $request)
    {
        $code = $request->input('code');
        $workWechatService = new WorkWechatService();
        $companyId = $workWechatService->getCompanyIdByAppid($request->input('app_id'));
        $workOpen = $workWechatService->checkCodeAuth($companyId);
        app('log')->info('file:'.__FILE__.',line:'.__LINE__.',companyId:'.$companyId);
        if ($workOpen) { // 未开启企业微信不走
            try {
                $config = app('wechat.work.wechat')->getConfig($companyId);
                $result = Factory::work($config)->miniProgram()->auth->session($code);
                app('log')->info('file:'.__FILE__.',line:'.__LINE__.',code:'.$code);
                app('log')->info('file:'.__FILE__.',line:'.__LINE__.',result:'.var_export($result, 1));
            } catch (\Exception $e) {
                throw new ResourceException('当前状态不允许普通登录');
            }
        }
        $result['work_open'] = $workOpen;
        return $this->response->array($result);
    }
}
