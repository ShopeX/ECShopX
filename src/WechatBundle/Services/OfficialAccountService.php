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

namespace WechatBundle\Services;

use EasyWeChat\OfficialAccount\Application as OfficialAccountApplication;
use Overtrue\Socialite\Exceptions\AuthorizeFailedException;

/**
 * 微信公众号相关的业务逻辑
 * 使用的是EasyWeChat v5.x
 */
class OfficialAccountService
{
    /**
     * 微信公众号的实际服务对象
     * @var null|OfficialAccountApplication
     */
    protected $app;

    public function __construct(?OfficialAccountApplication $app)
    {
        $this->app = $app;
    }

    /**
     * 获取用户的授权url
     * @param string $redirectUrlWhenOperationSuccess 当操作成功后需要重定向回来的url
     * @return string 用户去授权的页面
     */
    public function getAuthorizationUrl(string $redirectUrlWhenOperationSuccess): string
    {
        if (is_null($this->app) || empty($redirectUrlWhenOperationSuccess)) {
            return "";
        }
        return $this->app->oauth->scopes(['snsapi_userinfo'])->redirect($redirectUrlWhenOperationSuccess);
    }

    /**
     * 根据code获取用户信息
     * @param string $code
     * @return array|null
     */
    public function getUserInfoByCode(string $code): ?array
    {
        if (is_null($this->app) || empty($code)) {
            return null;
        }

        // {"id":"oj4su0bjIN9OgCfjzdJNL4CLayWo","name":"不忘初心","nickname":"不忘初心","avatar":"https://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJ8icITPLko2qLFia1IwibQ9y90IicHep1DOB0bzsT8BibUwhU844ec7HHtATBj3YicFVUE17N6CmAiaMaibg/132","email":null,"raw":{"openid":"oj4su0bjIN9OgCfjzdJNL4CLayWo","nickname":"不忘初心","sex":0,"language":"","city":"","province":"","country":"","headimgurl":"https://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJ8icITPLko2qLFia1IwibQ9y90IicHep1DOB0bzsT8BibUwhU844ec7HHtATBj3YicFVUE17N6CmAiaMaibg/132","privilege":[],"unionid":"oCzyo5ztNxY6R_CaRymNbsA08OLM"},"access_token":"52_b6mL0ow4Xg0ns-wSCu8L8LWP6qjsjSXCrYklQUSRAkX_XYNlpnDxvjCwCgTyixkA7hXSuUds4FnLYIJA3bWTEw","refresh_token":"52_jujagRjqnERjHs5RDHfHRXts533AGlsHN2NmM2AWOtgC1bRWN6di4OnUbtNyjhS3sNBrW5XItLLzGahjnsARIg","expires_in":7200,"token_response":{"access_token":"52_b6mL0ow4Xg0ns-wSCu8L8LWP6qjsjSXCrYklQUSRAkX_XYNlpnDxvjCwCgTyixkA7hXSuUds4FnLYIJA3bWTEw","expires_in":7200,"refresh_token":"52_jujagRjqnERjHs5RDHfHRXts533AGlsHN2NmM2AWOtgC1bRWN6di4OnUbtNyjhS3sNBrW5XItLLzGahjnsARIg","openid":"oj4su0bjIN9OgCfjzdJNL4CLayWo","scope":"snsapi_userinfo","unionid":"oCzyo5ztNxY6R_CaRymNbsA08OLM"}}
        try {
            // \Overtrue\Socialite\User
            $userInfo = $this->app->oauth->userFromCode($code)->getAttributes();
            return [
                "email" => $userInfo["email"] ?? "", // 用户的email
                "nickname" => $userInfo["raw"]["nickname"] ?? "", // 用户昵称
                "avatar" => $userInfo["raw"]["headimgurl"] ?? "", // 用户头像
                "openid" => $userInfo["raw"]["openid"] ?? "",
                "sex" => $userInfo["raw"]["sex"] ?? 0, // 性别【0 未知】【1 男】【2 女】
                "language" => $userInfo["raw"]["language"] ?? "",
                "city" => $userInfo["raw"]["city"] ?? "", // 城市
                "province" => $userInfo["raw"]["province"] ?? "", // 省份
                "country" => $userInfo["raw"]["country"] ?? "", // 国家
                "privilege" => (array)jsonDecode($userInfo["raw"]["privilege"] ?? []),
                "unionid" => $userInfo["raw"]["unionid"] ?? [],
                "openid" => $userInfo["raw"]["openid"] ?? [],
                "appid" => $this->app->oauth->getClientId() // 公众号id
            ];
        } catch (AuthorizeFailedException $authorizeFailedException) {
            app("log")->info(sprintf("OfficialAccountService_error:%s", jsonEncode([
                "body" => $authorizeFailedException->body,
                "message" => $authorizeFailedException->getMessage(),
            ])));
        } catch (\Throwable $throwable) {
            app("log")->info(sprintf("OfficialAccountService_error:%s", jsonEncode([
                "message" => $throwable->getMessage(),
                "file" => $throwable->getFile(),
                "line" => $throwable->getLine(),
            ])));
        }
        return null;
    }
}
