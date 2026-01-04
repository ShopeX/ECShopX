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

namespace EspierBundle\Auth\Jwt;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use CompanysBundle\Ego\GenericUser as GenericUser;
use CompanysBundle\Services\CompanysService;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use MerchantBundle\Services\MerchantSettlementApplyService;

/**
 * h5,商户入驻注册登录
 */
class EspierMerchantAccountProvider implements UserProvider
{
    /** @var accountService */
    protected $accountService;

    protected $prefix = 'merchantaccount_';

    /**
     * undocumented function
     *
     * @return void
     */
    public function __construct($app, $config)
    {
        $this->accountService = new MerchantSettlementApplyService();
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        list($account_type, $account_id) = explode('_', $identifier);
        if ($account_type != 'merchantaccount') {
            throw new UnauthorizedHttpException('登录类型出错，请检查！');
        }
        $user = $this->accountService->getAccountInfo($account_id);
        $user['id'] = $this->prefix . $user['account_id'];

        return $this->getGenericUser($user);
    }

    /**
     * attempt 触发
     *
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $this->getCompanyId($credentials);
        $params = [
            'company_id' => $credentials['company_id'] ?? '',
            'mobile' => $credentials['mobile'] ?? '',
            'vcode' => $credentials['vcode'] ?? '',
        ];

        // 验证密码，返回账号信息
        $user = $this->accountService->accountLogin($params);
        $user['id'] = $this->prefix . $user['account_id'];
        return $this->getGenericUser($user);
    }

    /**
     * 根据 origin 识别 company_id .
     *
     * @param  array  $credentials
     * @return boolean
     */
    private function getCompanyId(&$credentials)
    {
        if (isset($credentials['company_id']) && $credentials['company_id']) {
            return true;
        }

        if (!isset($credentials['origin']) or !$credentials['origin']) {
            return false;
        }

        $originDomain = str_replace(['http://', 'https://'], '', $credentials['origin']);
        $companyService = new CompanysService();
        $companyInfo = $companyService->getCompanyInfoByDomain($originDomain);
        if ($companyInfo) {
            $credentials['company_id'] = $companyInfo['company_id'] ?? '';
        }

        return true;
    }

    /**
     * Get the generic user.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if (isset($user) && !is_null($user)) {
            return new GenericUser($user);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        return true;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
    }
}
