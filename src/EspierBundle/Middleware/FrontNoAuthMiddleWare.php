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

namespace EspierBundle\Middleware;

use Closure;
use Exception;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use WechatBundle\Services\OpenPlatform;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\UserService;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\CompanysService;
use AliBundle\Services\AliMiniAppSettingService;

// 区分小程序和h5、app在不需要认证的接口时的默认参数信息，特别是company_id
class FrontNoAuthMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $apiFrom)
    {
        $mid_params = [];
        if ($apiFrom == 'h5app') {
            config(['auth.defaults.guard' => 'h5api']);
            try {
                $auth = app('auth')->user();
                $mid_params['auth'] = $auth->attributes;
                $mid_params['auth']['api_from'] = 'h5app';
                $companyId = $request->input('company_id');
                if (!$companyId) {
                    $mid_params['company_id'] = $auth->get('company_id');
                }
            } catch (\Exception $e) {
                if (!config('common.system_is_saas')) {
                    $companyId = config('common.system_companys_id');
                } elseif (config('common.system_main_companys_id')) {
                    $companyId = config('common.system_main_companys_id');
                } else {
                    $companyId = $request->input('company_id');
                    if (!$companyId) {
                        $saasDomain = $request->header('origin');
                        $saasDomain = str_replace(['http://', 'https://'], '', $saasDomain);
                        $companysService = new CompanysService();
                        $companyInfo = $companysService->getCompanyInfoByDomain($saasDomain);
                        if ($companyInfo) {
                            $companyId = $companyInfo['company_id'] ?? '';
                            $mid_params['company_id'] = $companyId;
                        }
                    }
                }
                $openId = $request->input('open_id', '');
                $unionid = $request->input('union_id', '');
                $appid = $request->input('appid', '');
                if (!$appid) {
                    $appid = $request->headers->get('authorizer-appid');
                }
                $mid_params['auth']['api_from'] = 'h5app';
                $mid_params['auth']['company_id'] = intval($companyId);
                $mid_params['auth']['unionid'] = $unionid; // 兼容小程序的参数
                $mid_params['auth']['open_id'] = $openId; // 兼容小程序的参数
                $mid_params['auth']['openid'] = $openId; // 兼容小程序的参数
                $mid_params['auth']['user_id'] = 0; // 兼容小程序的参数
                $mid_params['auth']['wxapp_appid'] = ''; // 兼容小程序的参数
                $mid_params['auth']['woa_appid'] = ''; // 服务号appid
                if ($appid) {
                    if (strpos($appid, 'wx') === 0) {
                        $mid_params['auth']['wxapp_appid'] = $appid;
                        $openPlatform = new OpenPlatform();
                        $companyId = $openPlatform->getCompanyId($appid);
                        if ($companyId) {
                            $mid_params['auth']['company_id'] = $companyId;
                        }
                        $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);
                        if ($woaAppid) {
                            $mid_params['auth']['woa_appid'] = $woaAppid;
                        }
                    } else {
                        $mid_params['auth']['alipay_appid'] = $appid;
                        $aliMiniAppSettingService = new AliMiniAppSettingService();
                        $companyId = $aliMiniAppSettingService->getCompanyId($appid);
                        if ($companyId) {
                            $mid_params['auth']['company_id'] = $companyId;
                        }
                    }
                }

                if (!$companyId) {
                    throw new UnauthorizedHttpException('无权访问该API,非法访问！');
                }
            }
        } elseif ($apiFrom == 'wechat') {
            $mid_params['auth']['company_id'] = 0;
            $appid = $request->headers->get('authorizer-appid');
            if ($appid) {
                $openPlatform = new OpenPlatform();
                $companyId = $openPlatform->getCompanyId($appid);
                $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);
                $mid_params['auth']['company_id'] = $companyId;
                $mid_params['auth']['woa_appid'] = $woaAppid;
            }

            $requestSession = $request->headers->get('x-wxapp-session');
            if ($requestSession) {
                $local_session = app('redis')->connection('wechat')->get('session3rd:' . $requestSession);
                $mid_params['auth'] = $this->getUser(json_decode($local_session, true));
            }

            $mid_params['auth']['api_from'] = 'wechat';

            if (!$mid_params['auth']['company_id']) {
                throw new UnauthorizedHttpException('WxappAuth', 'Unable to company_id.', null, 401001);
            }
        }
        $request->attributes->add($mid_params); // 添加参数
        return $next($request);
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
        ];
    }
}
