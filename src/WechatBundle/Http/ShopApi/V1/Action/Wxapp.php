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

namespace WechatBundle\Http\ShopApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\StoreResourceFailedException;

use WechatBundle\Services\OpenPlatform;
use CommunityBundle\Services\CommunityService;
use MembersBundle\Services\WechatUserService;

class Wxapp extends Controller
{
    protected $openPlatform;

    protected $sess_expier;

    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->sess_expier = 3600 * 24 * 7; //过期时间7天，单位: s(秒)
    }

    /**
     * @SWG\Post(
     *     path="/login",
     *     summary="小程序第三方登录",
     *     tags={"wxapp"},
     *     description="小程序第三方登录",
     *     operationId="login",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="appid", in="query", description="小程序唯一标识", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function login(Request $request)
    {
        /**
         * 4.server调用微信提供的jsoncode2session接口获取openid, session_key, 调用失败应给予客户端反馈
         * , 微信侧返回错误则可判断为恶意请求, 可以不返回. 微信文档链接
         * 这是一个 HTTP 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。其中 session_key 是对用户数据进行加密签名的密钥。
         * 为了自身应用安全，session_key 不应该在网络上传输。
         * 接口地址："https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code"
         */
        $inputData = $request->input();
        if (empty($inputData['appid'])) {
            throw new StoreResourceFailedException('缺少参数');
        }
        $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);
        $wxParams = [
            'code' => $inputData['code'],
            'appid' => $inputData['appid'],
        ];
        $res = $app->auth->session($inputData['code']); //调用微信获取sessionkey接口，返回session_key,openid,unionid

        if (empty($res['session_key']) || empty($res['openid'])) {
            throw new StoreResourceFailedException('用户登录失败，请退出重进');
        }

        if (!isset($res['unionid']) || !$res['unionid']) {
            $res['unionid'] = $res['openid'];
        }

        $communityService = new CommunityService();
        $companyId = $this->openPlatform->getCompanyId($inputData['appid']);
        if (!$companyId) {
            throw new StoreResourceFailedException('无效的小程序ID，请通知商家先绑定');
        }
        $communityData = $communityService->getInfo(['open_id' => $res['openid'], 'company_id' => $companyId]);
        if (!$communityData) {
            return $this->response->array(['init_open_id' => false]);
        }

        if ($communityData['status'] == 'close') {
            throw new StoreResourceFailedException('账号被冻结，请联系运营商');
        }

        if ($communityData['status'] == 'loading') {
            throw new StoreResourceFailedException('账号审核中，请稍后');
        }

        if ($communityData['status'] == 'refuse') {
            throw new StoreResourceFailedException('账号审核未通过，请联系运营商');
        }

        $wechatUserService = new WechatUserService();
        $userInfo = $wechatUserService->getWechatUserInfo(['company_id' => $companyId, 'unionid' => $res['unionid']]);

        //获取头像更新社区团长图片
        if (isset($userInfo['headimgurl']) && $userInfo['headimgurl']) {
            $communityFilter['community_id'] = $communityData['community_id'];
            $communityFilter['company_id'] = $companyId;
            $communityParams['pic_imgs'] = $userInfo['headimgurl'];
            $communityService->updateOneBy($communityFilter, $communityParams);
        }

        $randomkey = $this->randomFromDev(16);
        $sessionkey = sha1($randomkey.$res['openid'].$res['session_key']);
        $sessionValue = [
            'company_id' => $companyId,
            'community_id' => $communityData['community_id'],
            'community_name' => $communityData['community_name'],
            'wxa_appid' => $inputData['appid'],
            'open_id' => $res['openid'],
            'union_id' => $res['unionid'],
        ];
        $sessionValue = json_encode($sessionValue);

        app('redis')->connection('wechat')->setex('shopSession3rd:'.$sessionkey, $this->sess_expier, $sessionValue);
        $result['shopSession3rd'] = $sessionkey;
        $result['headimgurl'] = isset($userInfo['headimgurl']) ? $userInfo['headimgurl'] : '';
        $result['community_name'] = $communityData['community_name'];
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/login/mobile",
     *     summary="小程序第三方登录",
     *     tags={"社区核销小程序登录"},
     *     description="小程序第三方登录",
     *     operationId="login",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="登录时获取的 code", required=true, type="string"),
     *     @SWG\Parameter( name="appid", in="query", description="小程序唯一标识", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */

    public function loginByMobile(Request $request)
    {
        $inputData = $request->input();
        if (empty($inputData['appid'])) {
            throw new StoreResourceFailedException('缺少参数');
        }

        $communityService = new CommunityService();
        $companyId = $this->openPlatform->getCompanyId($inputData['appid']);
        if (!$companyId) {
            throw new StoreResourceFailedException('无效的小程序ID，请通知商家先绑定');
        }

        $app = $this->openPlatform->getAuthorizerApplication($inputData['appid']);
        $wxParams = [
            'code' => $inputData['code'],
            'appid' => $inputData['appid'],
        ];
        $res = $app->auth->session($inputData['code']); //调用微信获取sessionkey接口，返回session_key,openid,unionid

        if (empty($res['session_key']) || empty($res['openid'])) {
            throw new StoreResourceFailedException('用户登录失败，请退出重进');
        }

        $data = $app->encryptor->decryptData($res['session_key'], $inputData['iv'], $inputData['encryptedData']);

        $mobile = $data['phoneNumber'];

        $communityData = $communityService->getInfo(['leader_mobile' => $mobile, 'company_id' => $companyId]);
        if (!$communityData) {
            throw new StoreResourceFailedException('当前手机号不是团长');
        }

        // 将openid 更新到社区团长表中
        // 下次可以直接登录 不需要授权手机号
        $communityService->updateOneBy(['leader_mobile' => $mobile, 'company_id' => $companyId], ['open_id' => $res->openid]);

        $wechatUserService = new WechatUserService();
        $userInfo = $wechatUserService->getWechatUserInfo(['company_id' => $companyId, 'unionid' => $res->unionid]);

        $randomkey = $this->randomFromDev(16);
        $sessionkey = sha1($randomkey.$res['openid'].$res['session_key']);
        $sessionValue = [
            'company_id' => $companyId,
            'community_id' => $communityData['community_id'],
            'community_name' => $communityData['community_name'],
            'wxa_appid' => $inputData['appid'],
            'open_id' => $res['openid'],
            'union_id' => $res['unionid'],
        ];
        $result = $sessionValue;
        $sessionValue = json_encode($sessionValue);

        app('redis')->connection('wechat')->setex('shopSession3rd:'.$sessionkey, $this->sess_expier, $sessionValue);
        $result['shopSession3rd'] = $sessionkey;
        $result['headimgurl'] = isset($userInfo['headimgurl']) ? $userInfo['headimgurl'] : '';
        $result['community_name'] = $communityData['community_name'];
        return $this->response->array($result);
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
}
