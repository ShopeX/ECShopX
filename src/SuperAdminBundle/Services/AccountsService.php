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

namespace SuperAdminBundle\Services;

use Dingo\Api\Exception\ResourceException;
use SuperAdminBundle\Entities\Accounts;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Dingo\Api\Exception\ValidationHttpException;

class AccountsService
{
    /** @var accountRepository */
    public $accountRepository;

    public function __construct()
    {
        $this->accountRepository = app('registry')->getManager('default')->getRepository(Accounts::class);
    }

    /**
     * 添加平台管理员账号
     */
    public function createAccount($params)
    {
        $findFilter = [
            'login_name' => $params['login_name'],
        ];
        if ($this->accountRepository->findOneBy($findFilter)) {
            throw new ValidationHttpException('当前管理员账号已存在');
        }

        $accountParams['login_name'] = $params['login_name'];
        $accountParams['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
        $accountParams['name'] = $params['name'];
        $accountParams['super'] = $params['super'];
        $accountParams['status'] = $params['status'];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->accountRepository->create($accountParams);

            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 编辑平台管理员账号
     */
    public function updateAccount($params, $filter)
    {
        $accountParams['name'] = $params['name'];
        $accountParams['status'] = $params['status'];
        if (isset($params['password']) && $params['password']) {
            $accountParams['password'] = password_hash($params['secret'], PASSWORD_DEFAULT);
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->accountRepository->updateOneBy(['account_id' => $filter['account_id']], $accountParams);

            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    /**
     * 修改管理员密码
     */
    public function updateAccountPassword($params, $filter)
    {
        if (isset($params['old_password']) && $params['old_password'] && isset($params['password_confirmation']) && $params['password_confirmation']) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('*')->from('super_admin_accounts')->where($qb->expr()->eq('account_id', $filter['account_id']));
            $account = $qb->execute()->fetchAll();

            if (!password_verify($params['old_password'], $account[0]['password'])) {
                throw new ResourceException('旧密码不正确！');
            }
        }

        if (isset($params['password']) && $params['password']) {
            $accountParams['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $result = $this->accountRepository->updateOneBy(['account_id' => $filter['account_id']], $accountParams);

                $conn->commit();
                return $result;
            } catch (\Exception $e) {
                $conn->rollback();
                throw $e;
            }
        } else {
            throw new UnauthorizedHttpException('密码不存在');
        }
    }

    /**
     *  获取员工账号及信息列表
     */
    public function getAccountList($filter, $page = 1, $pageSize = 20, $orderBy = ['created' => 'desc'])
    {
        $result = $this->accountRepository->lists($filter, $orderBy, $pageSize, $page);

        return $result;
    }

    // 认证获取用户信息
    public function getAccountInfo($account_id)
    {
        $accountInfo = $this->accountRepository->getInfoById($account_id);

        if ($accountInfo) {
            $result = [
                'account_id' => $accountInfo['account_id'],
                'login_name' => $accountInfo['login_name'],
                'super' => $accountInfo['super'],
                'status' => $accountInfo['status'],
                'name' => $accountInfo['name'],
                'operator_type' => 'shopadmin',
            ];
            return $result;
        }

        throw new \LogicException("获取登录信息出错!");
    }

    /**
     *  平台账号登录验证
     */
    public function AccountLogin($params)
    {
        $filter['login_name'] = $params['login_name'];
        $accountInfo = $this->accountRepository->getInfo($filter);
        if (!$accountInfo) {
            throw new UnauthorizedHttpException('账号不存在');
        }

        $checkPassword = password_verify($params['password'], $accountInfo['password']);
        if (!$checkPassword) {
            throw new UnauthorizedHttpException('账号密码错误，请重新登录');
        }

        $result = [
            'account_id' => $accountInfo['account_id'],
            'login_name' => $accountInfo['login_name'],
            'super' => $accountInfo['super'],
            'status' => $accountInfo['status'],
            'name' => $accountInfo['name'],
            'operator_type' => 'shopadmin',
        ];
        return $result;
    }
}
