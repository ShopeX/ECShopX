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

namespace EspierBundle\Auth\Wxapp;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Contract\Auth\Provider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use MembersBundle\Services\UserService;
use MembersBundle\Services\WechatUserService;
use WechatBundle\Services\OpenPlatform;
use MembersBundle\Services\MemberService;
use CommunityBundle\Services\CommunityChiefService;


class WxappUserProvider implements Provider
{
    /**
     * Authenticate request with a Wxapp.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        $sessionVal = $this->getSession($request);
        if (!$sessionVal) {
            throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.', null, 401001);
        }
        try {
            if (!$user = $this->getUser($sessionVal)) {
                throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.');
            }
        } catch (\Exception $exception) {
            throw new UnauthorizedHttpException('WxappAuth', $exception->getMessage(), $exception, 401001);
        }

        return $user;
    }

    /**
     * Get the sessionvalue from the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getSession(Request $request)
    {
        // Built with ShopEx Framework
        $request_session = $request->headers->get('x-wxapp-session');
        if (!$request_session) {
            throw new BadRequestHttpException();
        }
        $local_session = app('redis')->connection('wechat')->get('session3rd:' . $request_session);
        if (!$local_session) {
            return false;
        }
        return json_decode($local_session, 1);
    }

    /**
     * Get the user from sessionval
     *
     * @param array $sessionVal
     *
     * @return array
     */
    protected function getUser($sessionVal)
    {
        $userService = new UserService(new WechatUserService());
        $user = $userService->getUserInfo(['open_id' => $sessionVal['open_id'], 'unionid' => $sessionVal['union_id']]);

        $openPlatform = new OpenPlatform();

        $companyId = $openPlatform->getCompanyId($user['authorizer_appid']);
        $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);

        $memberService = new MemberService();
        $memberInfo = [];
        if ($user['user_id']) {
            $memberInfo = $memberService->getMemberInfo(['user_id' => $user['user_id'], 'company_id' => $companyId]);
        }

        //团长
        $chiefService = new CommunityChiefService();
        $chief = $chiefService->getChiefInfoByUserID($user['user_id']);

        return [
            'id' => $user['user_id'],
            'user_id' => $user['user_id'],
            'disabled' => $memberInfo['disabled'] ?? 0,
            'company_id' => $companyId,
            'wxapp_appid' => $user['authorizer_appid'],
            'woa_appid' => $woaAppid,
            'open_id' => $user['open_id'],
            'unionid' => $user['unionid'],
            'nickname' => $user['nickname'],
            'mobile' => isset($memberInfo['mobile']) ? $memberInfo['mobile'] : '',
            'username' => isset($memberInfo['username']) ? $memberInfo['username'] : '',
            'sex' => isset($memberInfo['sex']) ? $memberInfo['sex'] : $user['sex'],
            'user_card_code' => $memberInfo['user_card_code'] ?? '',
            'member_card_code' => $memberInfo['user_card_code'] ?? '',
            'offline_card_code' => $memberInfo['offline_card_code'] ?? '',
            'chief_id'  => $chief['chief_id'] ?? 0,
        ];
    }
}
