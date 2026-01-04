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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Services\EmailService;
use DistributionBundle\Services\DistributorService;
use EmployeePurchaseBundle\Entities\Employees;
use EmployeePurchaseBundle\Services\EnterprisesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EmployeePurchaseBundle\Services\ActivitiesService;
use Hashids\Hashids;

class EmployeesService
{
    /** @var \EmployeePurchaseBundle\Repositories\EmployeesRepository */
    public $entityRepository;

    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Employees::class);
    }

    //创建数据格式化
    public function create($data)
    {
        $enterprisesService = new EnterprisesService();
        $enterpriseInfo = $enterprisesService->getInfo(['company_id' => $data['company_id'], 'id' => $data['enterprise_id']]);
        if (!$enterpriseInfo) {
            throw new ResourceException('企业不存在');
        }
        if ($enterpriseInfo['is_employee_check_enabled'] == false) {
            throw new ResourceException('该企业不需要添加员工');
        }
        $authType = $enterpriseInfo['auth_type'];
        $this->__checkParams($data, $authType);

        return $this->entityRepository->create($data);
    }

    public function __checkParams($data, $authType)
    {
        $filter = [
            'company_id' => $data['company_id'],
            'enterprise_id' => $data['enterprise_id'],
        ];
        switch ($authType) {
            case 'qr_code':
            case 'mobile':
                if (!ismobile($data['mobile'])) {
                    throw new ResourceException('请填写正确的手机号');
                }
                $filter['mobile'] = $data['mobile'];
                $exist = $this->count($filter);
                if ($exist) {
                    throw new ResourceException('手机号已经存在');
                }
                break;
            case 'email':
                if (!isset($data['email']) || !$data['email']) {
                    throw new ResourceException('请填写邮箱');
                }
                if (!isemail($data['email'])) {
                    throw new ResourceException('请填写正确的邮箱');
                }
                $filter['email'] = $data['email'];
                $exist = $this->count($filter);
                if ($exist) {
                    throw new ResourceException('邮箱已经存在');
                }
                break;
            case 'account':
                if (!isset($data['account'], $data['auth_code']) || !$data['account'] || !$data['auth_code']) {
                    throw new ResourceException('请填写账号密码');
                }
                $filter['account'] = $data['account'];
                $exist = $this->count($filter);
                if ($exist) {
                    throw new ResourceException('账号已经存在');
                }
                break;
            default:
                throw new ResourceException('验证类型有误！');
                break;
        }
    }

    public function getEmployeeListWithRel($filter, $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result = $this->entityRepository->getEmployeeListWithRel($filter, $page, $pageSize, $orderBy);
        if (!$result['total_count']) {
            return $result;
        }
        $distributorService = new DistributorService();
        $storeIds = array_filter(array_unique(array_column($result['list'], 'distributor_id')), function ($distributorId) {
            return is_numeric($distributorId) && $distributorId >= 0;
        });
        $storeData = [];
        if ($storeIds) {
            $storeList = $distributorService->getDistributorOriginalList([
                'company_id' => $filter['company_id'],
                'distributor_id' => $storeIds,
            ], 1, $pageSize);
            $storeData = array_column($storeList['list'], null, 'distributor_id');
            // 附加总店信息
            $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
        }
        foreach ($result['list'] as $key => $row) {
            $result['list'][$key]['distributor_name'] = isset($row['distributor_id']) ? ($storeData[$row['distributor_id']]['name'] ?? '') : '';
        }
        return $result;
    }

    /**
     * 发送邮箱验证码
     * @param  array $params
     */
    public function sendEmailVcode($params)
    {
        // 验证邮箱格式
        if (!isemail($params['email'])) {
            throw new ResourceException('收件邮箱格式不正确');
        }
        $suffix = substr($params['email'], strpos($params['email'], "@"));
        // 查询企业设置的发件邮箱配置
        $enterprisesService = new EnterprisesService();
        $filter = [
            'company_id' => $params['company_id'],
            'suffix' => $suffix,
        ];
        $distributorId = intval($params['distributor_id'] ?? 0);
        if ( $distributorId > 0) {
            $filter['distributor_id'] = $distributorId;
        }
        $enterpriseId = intval($params['enterprise_id'] ?? 0);
        if ( $enterpriseId > 0) {
            $filter['enterprise_id'] = $enterpriseId;
        }
        $enterpriseInfo = $enterprisesService->getEnterpriseByEmailSuffix($filter);
        if (!$enterpriseInfo) {
            throw new ResourceException('企业不存在');
        }
        if ($enterpriseInfo['auth_type'] != 'email') {
            throw new ResourceException('请选择其他验证方式');
        }
        if (!isset($enterpriseInfo['relay_host'], $enterpriseInfo['smtp_port'], $enterpriseInfo['email_user'], $enterpriseInfo['email_password']) || !$enterpriseInfo['relay_host'] || !$enterpriseInfo['smtp_port'] || !$enterpriseInfo['email_user'] || !$enterpriseInfo['email_password']) {
            throw new ResourceException('企业发件箱配置错误');
        }

        // 检查邮件是否在白名单中
        // $filter = [
        //     'company_id' => $params['company_id'],
        //     'enterprise_id' => $params['enterprise_id'],
        //     'email' => $params['email'],
        // ];
        // $employeeInfo = $this->entityRepository->getInfo($filter);
        // if (!$employeeInfo) {
        //     throw new ResourceException('非企业员工邮箱');
        // }
        $from = [
            'email_smtp_port' => $enterpriseInfo['smtp_port'],
            'email_relay_host' => $enterpriseInfo['relay_host'],
            'email_user' => $enterpriseInfo['email_user'],
            'email_password' => $enterpriseInfo['email_password'],
        ];
        $emailService = new EmailService($from);
        $to = $params['email'];
        $key = $this->generateReidsKey($to, 'email');
        $vcode = (string)mt_rand(100000, 999999);
        //保存验证码
        $this->redisStore($key, $vcode, 1800);
        // 标题
        $subject = '企业员工验证';
        //邮件内容
        $body = <<<EOF
<p>尊敬的用户:</p>
<p style="text-indent: 2em;">您的验证码是:{$vcode}位数字，30分钟内有效，请尽快完成验证。</p>
EOF;
        return $emailService->sendmail($to, $subject, $body);
    }

    public function authentication_bak($params) {
        $enterprisesService = new EnterprisesService();
        $enterpriseInfo = $enterprisesService->getInfo(['company_id' => $params['company_id'], 'id' => $params['enterprise_id']]);
        if (!$enterpriseInfo) {
            throw new ResourceException('企业不存在');
        }

        $exist = $this->entityRepository->count(['enterprise_id' => $params['enterprise_id'], 'user_id' => $params['user_id']]);
        if ($exist) {
            throw new ResourceException('已经是该企业员工，不需要重复绑定');
        }

        if (!isset($params['auth_type']) || $params['auth_type'] != 'qrcode') {
            $filter['company_id'] = $params['company_id'];
            $filter['enterprise_id'] = $params['enterprise_id'];
            $authType = $enterpriseInfo['auth_type'];
            switch ($authType) {
                case 'mobile':
                    if (!isset($params['mobile']) || !$params['mobile']) {
                        throw new ResourceException('请输入手机号');
                    }

                    $filter['mobile'] = $params['mobile'];
                    break;
                case 'email':
                    if (!isset($params['email']) || !$params['email']) {
                        throw new ResourceException('请输入邮箱');
                    }

                    if (!isset($params['vcode']) || !$params['vcode']) {
                        throw new ResourceException('请输入验证码');
                    }

                    if (!$this->checkEmailVcode($params['email'], $params['vcode'])) {
                        throw new ResourceException('验证码错误');
                    }

                    $filter['email'] = $params['email'];
                    break;
                case 'account':
                    if (!isset($params['account']) || !$params['account']) {
                        throw new ResourceException('请输入登录账号');
                    }

                    if (!isset($params['auth_code']) || !$params['auth_code']) {
                        throw new ResourceException('请输入登录密码');
                    }

                    $filter['account'] = $params['account'];
                    $filter['auth_code'] = $params['auth_code'];
                    break;
            }
            $employee = $this->entityRepository->getInfo($filter);
            if (!$employee) {
                throw new ResourceException('企业员工验证失败');
            }

            if ($employee['user_id']) {
                throw new ResourceException('企业员工已绑定其他用户');
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (isset($params['auth_type']) && $params['auth_type'] == 'qrcode') {
                $data = [
                    'company_id' => $params['company_id'],
                    'name' => '用户-'.substr($params['member_mobile'], -4),
                    'enterprise_id' => $params['enterprise_id'],
                    'user_id' => $params['user_id'],
                    'member_mobile' => $params['member_mobile'],
                ];
                $this->entityRepository->create($data);
            } else {
                // 绑定员工身份
                $data = [
                    'user_id' => $params['user_id'],
                    'member_mobile' => $params['member_mobile'],
                ];
                $this->entityRepository->updateBy($filter, $data);
            }

            // 禁用同一个企业下的亲友身份
            $relativesService = new RelativesService();
            $relativesService->updateBy(['company_id' => $params['company_id'], 'user_id' => $params['user_id'], 'enterprise_id' => $params['enterprise_id']], ['disabled' => 1]);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    /**
     * 调整了认证流程，认证白名单流程提前，选择认证方式，认证后选择企业（如果是扫描二维码，则无需选择企业）
     * 验证白名单的企业，绑定员工（需传employee_id）
     * 无需验证白名单的企业，创建员工（无需传employee_id）
     */
    public function authentication($params) {
        $enterprisesService = new EnterprisesService();
        $enterpriseFilter = ['company_id' => $params['company_id'], 'id' => $params['enterprise_id'], 'auth_type' => $params['auth_type']];
        $enterpriseInfo = $enterprisesService->getInfo($enterpriseFilter);
        if (!$enterpriseInfo) {
            throw new ResourceException('企业不存在');
        }

        $exist = $this->entityRepository->count(['enterprise_id' => $params['enterprise_id'], 'user_id' => $params['user_id']]);
        if ($exist) {
            throw new ResourceException('已经是该企业员工，不需要重复绑定');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($enterpriseInfo['is_employee_check_enabled'] == false) {
                $data = [
                    'company_id' => $params['company_id'],
                    // 'name' => '用户-'.substr($params['member_mobile'], -4),
                    'enterprise_id' => $params['enterprise_id'],
                    'user_id' => $params['user_id'],
                    'member_mobile' => $params['member_mobile'],
                    'distributor_id' => $enterpriseInfo['distributor_id'],
                ];
                switch ($enterpriseInfo['auth_type']) {
                    case 'email':
                        $data['name'] = urldecode($params['email']);
                        $data['email'] = urldecode($params['email']);
                        break;
                    default:
                        $data['name'] = $params['member_mobile'];
                        $data['mobile'] = $params['member_mobile'];
                        break;
                }
                $this->entityRepository->create($data);
            } else {
                $employeeId = intval($params['employee_id']);
                if ($employeeId <= 0) throw new ResourceException('企业员工验证失败');
                $filter['company_id'] = $params['company_id'];
                $filter['enterprise_id'] = $params['enterprise_id'];
                $filter['id'] = $employeeId;
                $employee = $this->entityRepository->getInfo($filter);
                if (!$employee) {
                    throw new ResourceException('企业员工验证失败');
                }

                if ($employee['user_id']) {
                    throw new ResourceException('企业员工已绑定其他用户');
                }
                // 绑定员工身份
                $data = [
                    'user_id' => $params['user_id'],
                    'member_mobile' => $params['member_mobile'],
                ];
                $this->entityRepository->updateBy($filter, $data);
            }

            // 禁用同一个企业下的亲友身份
            $relativesService = new RelativesService();
            $relativesService->updateBy(['company_id' => $params['company_id'], 'user_id' => $params['user_id'], 'enterprise_id' => $params['enterprise_id']], ['disabled' => 1]);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    //验证邮件验证码
    public function checkEmailVcode($email, $vcode)
    {
        if (empty($email)) {
            throw new ResourceException('请输入邮箱');
        }
        $key = $this->generateReidsKey($email, 'email');
        $storeVcode = $this->redisFetch($key);
        if ($storeVcode == $vcode) {
            app('redis')->del($key);
            return true;
        }
        return false;
    }

    //生成验证码的redis key
    private function generateReidsKey($token, $type = 'email')
    {
        return 'employee-purchase-'.$type.':'.$token;
    }

    //redis存储
    private function redisStore($key, $value, $expire = 300)
    {
        app('redis')->set($key, $value);
        app('redis')->expire($key, $expire);
    }

    //redis读取
    private function redisFetch($key)
    {
        return app('redis')->get($key);
    }

    public function check($companyId, $enterpriseId, $userId)
    {
        $filter = [
            'company_id' => $companyId,
            'enterprise_id' => $enterpriseId,
            'user_id' => $userId,
            'disabled' => 0,
        ];
        return $this->entityRepository->getInfo($filter);
    }

    public function getInviteCode($companyId, $enterpriseId, $activityId, $userId)
    {
        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if (!in_array($enterpriseId, $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        if (!$activity['if_relative_join']) {
            throw new ResourceException('活动不可以邀请亲友');
        }

        $employee = $this->check($companyId, $enterpriseId, $userId);
        if (!$employee) {
            throw new ResourceException('只有员工可以邀请');
        }

        $inviteNum = $this->getInviteNum($companyId, $enterpriseId, $activityId, $userId);
        if ($inviteNum >= $activity['invite_limit']) {
            throw new ResourceException('已达到邀请上限');
        }

        return $this->genShareCode($companyId, $enterpriseId, $activityId, $userId);
    }

    public function getInviteNum($companyId, $enterpriseId, $activityId, $userId)
    {
        $relativesService = new RelativesService();
        return $relativesService->count(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'employee_user_id' => $userId, 'activity_id' => $activityId, 'disabled' => 0]);
    }

    private function genShareCode($companyId, $enterpriseId, $activityId, $userId)
    {
        do {
            $code = (string)rand(1000000, 9999999);
            $key = $this->getRedisKey($companyId);
            if (!app('redis')->hget($key, $code)) {
                $encodeData = [$enterpriseId, $activityId, $userId];
                $hashids = new Hashids();
                $ticket = $hashids->encode($encodeData);
                app('redis')->hset($key, $code, $ticket);
                return $code;
            }
        } while (true);
    }

    /**
     * 验证分享码是否存在
     * @param  string $companyId 企业ID
     * @param  string $code      分享码
     */
    public function lockInviteCode($companyId, $code)
    {
        $key = $this->getRedisKey($companyId);
        $ticket = app('redis')->hget($key, $code);
        if (app('redis')->hdel($key, $code)) {
            app('redis')->hset($key, $code.'_', $ticket);
            return true;
        }
        throw new ResourceException('邀请码已被使用');
    }

    public function unlockInviteCode($companyId, $code)
    {
        $key = $this->getRedisKey($companyId);
        $ticket = app('redis')->hget($key, $code.'_');
        if (app('redis')->hdel($key, $code.'_')) {
            app('redis')->hset($key, $code, $ticket);
        }
        return true;
    }

    public function delInviteCode($companyId, $code)
    {
        $key = $this->getRedisKey($companyId);
        app('redis')->hdel($key, $code);
        app('redis')->hdel($key, $code.'_');
        return true;
    }

    public function getInviteTicket($companyId, $code)
    {
        $key = $this->getRedisKey($companyId);
        $ticket = app('redis')->hget($key, $code.'_');
        if (!$ticket) {
            throw new ResourceException('分享链接已失效');
        }
        $hashids = new Hashids();
        $ticketData = $hashids->decode($ticket);
        return $ticketData;
    }

    public function getRedisKey($companyId)
    {
        return 'employee_purchase_invite:'.$companyId;
    }

    /**
     * 根据验证方式，验证员工白名单是否有关联企业
     * @param  array $params 参数信息
     */
    public function doEmployeeCheck($params)
    {
        // 验证参数
        if ($params['auth_type'] == 'email') {
            $info = $this->__checkEmployee($params);
            $data = [
                'enterprise_id' => $info['enterprise_id'],
                'enterprise_name' => $info['name'],
                'enterprise_sn' => $info['enterprise_sn'],
                'auth_type' => $info['auth_type'],
                'distributor_id' => $info['distributor_id'],
                'operator_id' => $info['operator_id'],
            ];
            $result = [
                'total_count' => 1,
                'list' => [$data],
            ];
            return $result;
        }
        $filter = $this->__checkEmployee($params);
        // 获取企业列表
        $employeeLists = $this->getEmployeeListWithRel($filter);
        if ($employeeLists['total_count'] == 0) {
            throw new ResourceException('未关联企业信息，请确认后再操作');
        }
        return $employeeLists;
    }

    /**
     * 验证员工白名单的参数
     */
    public function __checkEmployee(&$params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => false,
            'auth_type' => $params['auth_type'],
        ];

        $activity_id = intval($params['activity_id'] ?? 0);
        if ($activity_id > 0) {
            $activitiesService = new ActivitiesService();
            $enterprisesList = $activitiesService->getActivityEnterprises([
                'company_id' => $filter['company_id'],
                'activity_id' => $activity_id,
            ]);
            if (empty($enterprisesList)) {
                return $this->response->array([]);
            }
            $filter['enterprise_id'] = array_column($enterprisesList, 'enterprise_id');
        } else {
            $distributorId = intval($params['distributor_id'] ?? 0);
            if ( $distributorId > 0) {
                $filter['distributor_id'] = $distributorId;
            }

            $enterpriseId = intval($params['enterprise_id'] ?? 0);
            if ( $enterpriseId > 0) {
                $filter['enterprise_id'] = $enterpriseId;
                unset($filter['user_id']);
            }
        }

        switch ($params['auth_type']) {
            case 'qr_code':
            case 'mobile':
                if (!$params['mobile']) throw new ResourceException('手机号必填');
                $filter['mobile'] = $params['mobile'];
                break;
            case 'account':
                if (!$params['account']) throw new ResourceException('账号必填');
                if (!$params['auth_code']) throw new ResourceException('密码必填');
                $filter['account'] = $params['account'];
                $filter['auth_code'] = $params['auth_code'];
                break;
            case 'email':
                if (!$params['email']) throw new ResourceException('邮箱必填');
                if (!$params['vcode']) throw new ResourceException('验证码必填');
                // 去验证邮箱验证码正确
                if (!$this->checkEmailVcode($params['email'], $params['vcode'])) {
                    throw new ResourceException('验证码错误');
                }
                $filter['suffix'] = substr($params['email'], strpos($params['email'], "@"));
                unset($filter['user_id']);
                $enterprisesService = new EnterprisesService();
                $enterpriseInfo = $enterprisesService->getEnterpriseByEmailSuffix($filter);
                if (!$enterpriseInfo) {
                    throw new ResourceException('未关联企业信息，请确认后再操作');
                }
                return $enterpriseInfo;
                break;
            default:
                throw new ResourceException('请选择正确的验证方式');
                break;
        }
        return $filter;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

}
