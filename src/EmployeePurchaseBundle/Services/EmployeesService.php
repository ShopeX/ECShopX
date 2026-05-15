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
use EmployeePurchaseBundle\Entities\ActivityEnterpriseParticipateUser;
use EmployeePurchaseBundle\Entities\MemberActivityAggregate;
use EmployeePurchaseBundle\Entities\MemberActivityItemsAggregate;
use EmployeePurchaseBundle\Services\EnterprisesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EmployeePurchaseBundle\Services\ActivitiesService;
use Hashids\Hashids;
use MembersBundle\Services\MemberService;

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
        // 无需验证：用户侧选企业后直接建档，后台不允许维护白名单员工
        if (($enterpriseInfo['auth_type'] ?? '') === 'no_verify') {
            throw new ResourceException('该企业不需要添加员工');
        }
        // 未开启白名单校验的企业（如邮箱验证类）同样不允许后台加人
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
     * 发送邮箱验证码：须指定当前认证企业（传 enterprise_id；无则须传 distributor_id 且该公司+店铺下邮箱后缀仅命中一家 email 认证企业）
     * 收件邮箱后缀须与该企业发件箱 suffix 一致。
     *
     * @param  array $params company_id, email, enterprise_id|distributor_id
     */
    public function sendEmailVcode($params)
    {
        if (!isemail($params['email'])) {
            throw new ResourceException('收件邮箱格式不正确');
        }
        $enterpriseId = $this->resolveEmailAuthEnterpriseId($params, $params['email']);
        if ($enterpriseId < 1) {
            throw new ResourceException('企业ID不能为空');
        }

        $enterprisesService = new EnterprisesService();
        $enterprise = $enterprisesService->getInfo([
            'company_id' => $params['company_id'],
            'id' => $enterpriseId,
        ]);
        if (!$enterprise || !empty($enterprise['disabled'])) {
            throw new ResourceException('企业不存在');
        }
        if (($enterprise['auth_type'] ?? '') !== 'email') {
            throw new ResourceException('请选择其他验证方式');
        }

        $distributorId = (int) ($params['distributor_id'] ?? 0);
        if ($distributorId > 0 && (int) ($enterprise['distributor_id'] ?? 0) !== $distributorId) {
            throw new ResourceException('企业不存在');
        }

        $box = $enterprisesService->enterpriseEmailBoxRepository->getInfo([
            'company_id' => $params['company_id'],
            'enterprise_id' => $enterpriseId,
        ]);
        if (!$box) {
            throw new ResourceException('企业未配置发件邮箱');
        }
        $recvSuffix = $this->normalizeMailboxSuffixFromEmail($params['email']);
        $cfgSuffix = $this->normalizeMailboxSuffixFromConfig((string) ($box['suffix'] ?? ''));
        if ($cfgSuffix === '' || $recvSuffix === '' || $recvSuffix !== $cfgSuffix) {
            throw new ResourceException('邮箱后缀与当前企业要求不一致');
        }

        if (empty($box['relay_host']) || empty($box['smtp_port']) || empty($box['user']) || $box['password'] === null || $box['password'] === '') {
            throw new ResourceException('企业发件箱配置错误');
        }

        $from = [
            'email_smtp_port' => $box['smtp_port'],
            'email_relay_host' => $box['relay_host'],
            'email_user' => $box['user'],
            'email_password' => $box['password'],
        ];
        $emailService = new EmailService($from);
        $to = $params['email'];
        $key = $this->generateReidsKey($to, 'email', $enterpriseId);
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

                    if (!$this->checkEmailVcode($params['email'], $params['vcode'], (int) $params['enterprise_id'])) {
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

        $quotaConsumed = false;
        $bindCompanyId = (int) $params['company_id'];
        $bindEnterpriseId = (int) $params['enterprise_id'];
        $bindActivityId = isset($params['activity_id']) ? (int) $params['activity_id'] : 0;
        $participateQuotaSvc = new PassphraseParticipateQuotaRedisService();
        if ($bindActivityId > 0 && $participateQuotaSvc->isApplicable($bindCompanyId, $bindActivityId, $bindEnterpriseId)) {
            $emExempt = app('registry')->getManager('default');
            /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterpriseParticipateUserRepository $exemptRepo */
            $exemptRepo = $emExempt->getRepository(ActivityEnterpriseParticipateUser::class);
            if (!$exemptRepo->existsForUser($bindCompanyId, $bindActivityId, $bindEnterpriseId, (int) $params['user_id'])) {
                if (!$participateQuotaSvc->tryConsumeSlot($bindCompanyId, $bindActivityId, $bindEnterpriseId)) {
                    throw new ResourceException('该企业在本活动下的参与名额已满');
                }
                $quotaConsumed = true;
            }
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
                    case 'no_verify':
                        $data['name'] = '用户-'.substr((string) $params['member_mobile'], -4);
                        $data['mobile'] = $params['member_mobile'];
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
            if ($quotaConsumed) {
                $participateQuotaSvc->releaseOneSlot($bindCompanyId, $bindActivityId, $bindEnterpriseId);
            }
            throw new ResourceException($e->getMessage());
        }

        $this->tryWriteEmployeeBindBehaviorLog($params);

        if ($quotaConsumed && $bindActivityId > 0) {
            $emExempt = app('registry')->getManager('default');
            /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterpriseParticipateUserRepository $exemptRepo */
            $exemptRepo = $emExempt->getRepository(ActivityEnterpriseParticipateUser::class);
            $exemptRepo->insertIgnore($bindCompanyId, $bindActivityId, $bindEnterpriseId, (int) $params['user_id']);
        }

        return true;
    }

    /**
     * 活动内员工账号绑定成功后写入行为流水 behavior_type=bind（管理端按活动+企业聚合 bind_user_count）。
     * 仅当入参带有效 activity_id 且该企业参与该活动时写入；失败不影响绑定结果。
     * `extra.bind_channel` 与 `auth_type` 一致，供支付成功写 `order` 流水时识别是否扫码绑定。
     *
     * @param array<string,mixed> $params {@see authentication()} 入参，须含 company_id、enterprise_id、user_id；可选 activity_id、auth_type
     */
    private function tryWriteEmployeeBindBehaviorLog(array $params): void
    {
        $activityId = isset($params['activity_id']) ? (int) $params['activity_id'] : 0;
        if ($activityId <= 0) {
            return;
        }
        $companyId = (int) ($params['company_id'] ?? 0);
        $enterpriseId = (int) ($params['enterprise_id'] ?? 0);
        $userId = (int) ($params['user_id'] ?? 0);
        if ($companyId <= 0 || $enterpriseId <= 0 || $userId <= 0) {
            return;
        }
        try {
            $activitiesService = new ActivitiesService();
            $activity = $activitiesService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
            if (empty($activity)) {
                return;
            }
            $allowed = $activitiesService->normalizeActivityEnterpriseIds($activity['enterprise_id'] ?? []);
            if (!in_array($enterpriseId, $allowed, true)) {
                return;
            }
            $bindChannel = isset($params['auth_type']) ? (string) $params['auth_type'] : '';
            $bindExtra = [ActivityEnterpriseBehaviorLogService::EXTRA_KEY_BIND_CHANNEL => $bindChannel];
            $logService = new ActivityEnterpriseBehaviorLogService();
            $logService->writeBehaviorLog(
                $companyId,
                $activityId,
                $enterpriseId,
                ActivityEnterpriseBehaviorLogService::BEHAVIOR_BIND,
                $userId,
                null,
                null,
                $bindExtra
            );
        } catch (\Throwable $e) {
            app('log')->warning('employee bind behavior log failed: '.$e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * 验证邮件验证码（须与发送时同一 enterprise_id）
     */
    public function checkEmailVcode($email, $vcode, $enterpriseId = 0)
    {
        if (empty($email)) {
            throw new ResourceException('请输入邮箱');
        }
        $enterpriseId = (int) $enterpriseId;
        if ($enterpriseId < 1) {
            throw new ResourceException('企业ID不能为空');
        }
        $key = $this->generateReidsKey($email, 'email', $enterpriseId);
        $storeVcode = $this->redisFetch($key);
        if ($storeVcode == $vcode) {
            app('redis')->del($key);
            return true;
        }
        return false;
    }

    /** @see sendEmailVcode / checkEmailVcode */
    private function generateReidsKey($token, $type = 'email', $enterpriseId = 0)
    {
        $enterpriseId = (int) $enterpriseId;
        if ($type === 'email' && $enterpriseId > 0) {
            return 'employee-purchase-'.$type.':'.$enterpriseId.':'.$token;
        }

        return 'employee-purchase-'.$type.':'.$token;
    }

    /** 收件邮箱 @ 起的小写后缀，如 @qq.com */
    private function normalizeMailboxSuffixFromEmail(string $email): string
    {
        $at = strpos($email, '@');
        if ($at === false) {
            return '';
        }

        return strtolower(substr($email, $at));
    }

    /** 库表 suffix 与收件后缀对齐：统一小写并保证带 @ */
    private function normalizeMailboxSuffixFromConfig(string $stored): string
    {
        $s = strtolower(trim($stored));
        if ($s === '') {
            return '';
        }
        if (strpos($s, '@') !== 0) {
            $s = '@'.$s;
        }

        return $s;
    }

    /**
     * 解析「当前邮箱认证」对应的企业 ID：优先 enterprise_id；否则 company_id + distributor_id + 收件后缀在库中唯一命中。
     */
    private function resolveEmailAuthEnterpriseId(array $params, string $email): int
    {
        $eid = (int) ($params['enterprise_id'] ?? 0);
        if ($eid > 0) {
            return $eid;
        }
        $dist = (int) ($params['distributor_id'] ?? 0);
        if ($dist < 1) {
            return 0;
        }
        $suffix = $this->normalizeMailboxSuffixFromEmail($email);
        if ($suffix === '') {
            return 0;
        }
        $es = new EnterprisesService();
        $row = $es->getEnterpriseByEmailSuffix([
            'company_id' => $params['company_id'],
            'suffix' => $suffix,
            'e.distributor_id' => $dist,
        ]);
        if (empty($row['id'])) {
            return 0;
        }

        return (int) $row['id'];
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
        if ($params['auth_type'] == 'email' || $params['auth_type'] == 'no_verify') {
            $info = $this->__checkEmployee($params);
            $eid = $info['enterprise_id'] ?? $info['id'] ?? 0;
            $data = [
                'enterprise_id' => $eid,
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
            'user_id' => 0,
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
            // 与仅传 enterprise_id 分支一致：按账号/手机查白名单，不限定 user_id=0；否则已绑定会员的员工行无法命中
            unset($filter['user_id']);
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
                unset($filter['user_id']);
                $filter['mobile'] = $params['mobile'];
                break;
            case 'account':
                if (!$params['account']) throw new ResourceException('账号必填');
                if (!$params['auth_code']) throw new ResourceException('密码必填');
                $filter['account'] = $params['account'];
                $filter['auth_code'] = $params['auth_code'];
                break;
            case 'email':
                if (!$params['email']) {
                    throw new ResourceException('邮箱必填');
                }
                if (!$params['vcode']) {
                    throw new ResourceException('验证码必填');
                }
                $eidEmail = $this->resolveEmailAuthEnterpriseId($params, $params['email']);
                if ($eidEmail < 1) {
                    throw new ResourceException('企业ID不能为空');
                }
                if ($activity_id > 0) {
                    $allowedE = isset($filter['enterprise_id']) ? (array) $filter['enterprise_id'] : [];
                    $allowedE = array_map('intval', $allowedE);
                    if (!in_array($eidEmail, $allowedE, true)) {
                        throw new ResourceException('企业不参与该活动');
                    }
                }
                if (!$this->checkEmailVcode($params['email'], $params['vcode'], $eidEmail)) {
                    throw new ResourceException('验证码错误');
                }
                $enterprisesService = new EnterprisesService();
                $enterpriseInfo = $enterprisesService->getInfo([
                    'company_id' => $params['company_id'],
                    'id' => $eidEmail,
                    'auth_type' => 'email',
                ]);
                if (!$enterpriseInfo || !empty($enterpriseInfo['disabled'])) {
                    throw new ResourceException('未关联企业信息，请确认后再操作');
                }
                $box = $enterprisesService->enterpriseEmailBoxRepository->getInfo([
                    'company_id' => $params['company_id'],
                    'enterprise_id' => $eidEmail,
                ]);
                $recvSuffix = $this->normalizeMailboxSuffixFromEmail($params['email']);
                $cfgSuffix = $this->normalizeMailboxSuffixFromConfig((string) ($box['suffix'] ?? ''));
                if ($cfgSuffix === '' || $recvSuffix === '' || $recvSuffix !== $cfgSuffix) {
                    throw new ResourceException('邮箱后缀与当前企业要求不一致');
                }

                return $enterpriseInfo;
                break;
            case 'no_verify':
                // 无需验证：仅校验企业存在且为 no_verify 类型（及活动/店铺范围）
                $eid = intval($params['enterprise_id'] ?? 0);
                if ($eid <= 0) {
                    throw new ResourceException('企业ID必填');
                }
                if ($activity_id > 0) {
                    $allowed = isset($filter['enterprise_id']) ? (array) $filter['enterprise_id'] : [];
                    $allowed = array_map('intval', $allowed);
                    if (!in_array($eid, $allowed, true)) {
                        throw new ResourceException('企业不参与该活动');
                    }
                } elseif (isset($filter['enterprise_id']) && !is_array($filter['enterprise_id'])) {
                    if ((int) $filter['enterprise_id'] !== $eid) {
                        throw new ResourceException('企业信息不匹配');
                    }
                }
                $enterprisesService = new EnterprisesService();
                $enterpriseInfo = $enterprisesService->getInfo([
                    'company_id' => $params['company_id'],
                    'id' => $eid,
                    'auth_type' => 'no_verify',
                ]);
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

    /**
     * 口令活动：用户已在 behavior-report 验口令（Redis）且未预录白名单时，自动写入 employee_purchase_employees（加车/下单/活动数据前调用）。
     * 已是员工或已是亲友则跳过；名额逻辑与 {@see authentication()} 一致。
     */
    public function ensurePassphraseEmployeeFromVerifiedActivity(int $companyId, int $enterpriseId, int $activityId, int $userId): void
    {
        if ($companyId < 1 || $enterpriseId < 1 || $activityId < 1 || $userId < 1) {
            return;
        }
        if ($this->check($companyId, $enterpriseId, $userId)) {
            return;
        }
        $relativesService = new RelativesService();
        if ($relativesService->check($companyId, $enterpriseId, $activityId, $userId)) {
            return;
        }

        $activitiesSvc = new ActivitiesService();
        if (!$activitiesSvc->supportsPassphraseBypassWhitelist($companyId, $activityId, $enterpriseId)) {
            return;
        }
        if (!(new PassphraseVerifiedRedisService())->isVerified($companyId, $activityId, $enterpriseId, $userId)) {
            return;
        }

        $memberService = new MemberService();
        $member = $memberService->getMemberInfo(['company_id' => $companyId, 'user_id' => $userId]);
        if (empty($member) || empty($member['user_id'])) {
            throw new ResourceException('会员信息不存在');
        }
        $memberMobile = trim((string) ($member['mobile'] ?? ''));
        $memberEmail = trim((string) ($member['email'] ?? ''));

        $enterprisesService = new EnterprisesService();
        $enterpriseInfo = $enterprisesService->getInfo(['company_id' => $companyId, 'id' => $enterpriseId]);
        if (!$enterpriseInfo) {
            return;
        }

        $quotaConsumed = false;
        $participateQuotaSvc = new PassphraseParticipateQuotaRedisService();
        if ($participateQuotaSvc->isApplicable($companyId, $activityId, $enterpriseId)) {
            $emExempt = app('registry')->getManager('default');
            /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterpriseParticipateUserRepository $exemptRepo */
            $exemptRepo = $emExempt->getRepository(ActivityEnterpriseParticipateUser::class);
            if (!$exemptRepo->existsForUser($companyId, $activityId, $enterpriseId, $userId)) {
                if (!$participateQuotaSvc->tryConsumeSlot($companyId, $activityId, $enterpriseId)) {
                    throw new ResourceException('该企业在本活动下的参与名额已满');
                }
                $quotaConsumed = true;
            }
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($this->check($companyId, $enterpriseId, $userId)) {
                $conn->rollback();
                if ($quotaConsumed) {
                    $participateQuotaSvc->releaseOneSlot($companyId, $activityId, $enterpriseId);
                }

                return;
            }

            $data = [
                'company_id' => $companyId,
                'enterprise_id' => $enterpriseId,
                'user_id' => $userId,
                'member_mobile' => $memberMobile !== '' ? $memberMobile : ($memberEmail !== '' ? $memberEmail : (string) $userId),
                'distributor_id' => $enterpriseInfo['distributor_id'],
            ];
            switch ($enterpriseInfo['auth_type']) {
                case 'email':
                    if ($memberEmail === '') {
                        throw new ResourceException('请先绑定邮箱');
                    }
                    $data['name'] = $memberEmail;
                    $data['email'] = $memberEmail;
                    break;
                case 'no_verify':
                    $mob = $memberMobile !== '' ? $memberMobile : (string) $userId;
                    $data['name'] = '用户-'.substr($mob, -4);
                    $data['mobile'] = $mob;
                    break;
                default:
                    if ($memberMobile === '') {
                        throw new ResourceException('请先绑定手机号');
                    }
                    $data['name'] = $memberMobile;
                    $data['mobile'] = $memberMobile;
                    break;
            }

            $this->entityRepository->create($data);
            $relativesService->updateBy(['company_id' => $companyId, 'user_id' => $userId, 'enterprise_id' => $enterpriseId], ['disabled' => 1]);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            if ($quotaConsumed) {
                $participateQuotaSvc->releaseOneSlot($companyId, $activityId, $enterpriseId);
            }
            if ($this->check($companyId, $enterpriseId, $userId)) {
                return;
            }
            throw new ResourceException($e->getMessage());
        }

        if ($quotaConsumed) {
            $emExempt = app('registry')->getManager('default');
            /** @var \EmployeePurchaseBundle\Repositories\ActivityEnterpriseParticipateUserRepository $exemptRepo */
            $exemptRepo = $emExempt->getRepository(ActivityEnterpriseParticipateUser::class);
            $exemptRepo->insertIgnore($companyId, $activityId, $enterpriseId, $userId);
        }

        $this->tryWriteEmployeeBindBehaviorLog([
            'company_id' => $companyId,
            'enterprise_id' => $enterpriseId,
            'user_id' => $userId,
            'activity_id' => $activityId,
            'auth_type' => ActivityEnterpriseBehaviorLogService::BIND_CHANNEL_PASSPHRASE,
        ]);
    }

    /**
     * 删除企业员工；若已绑定会员，同步清空该会员在本企业下的活动额度累计（活动总维度 + 商品维度），便于删后重新自动建档时额度重算。
     *
     * @param array<string,mixed> $filter 须能唯一定位一行，如 company_id + id
     */
    public function deleteBy(array $filter)
    {
        $row = $this->entityRepository->getInfo($filter);
        if (!$row) {
            return true;
        }

        $companyId = (int) ($row['company_id'] ?? 0);
        $enterpriseId = (int) ($row['enterprise_id'] ?? 0);
        $userId = (int) ($row['user_id'] ?? 0);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($companyId > 0 && $enterpriseId > 0 && $userId > 0) {
                $aggFilter = [
                    'company_id' => $companyId,
                    'enterprise_id' => $enterpriseId,
                    'user_id' => $userId,
                ];
                $em = app('registry')->getManager('default');
                $em->getRepository(MemberActivityAggregate::class)->deleteBy($aggFilter);
                $em->getRepository(MemberActivityItemsAggregate::class)->deleteBy($aggFilter);
            }
            $this->entityRepository->deleteBy($filter);
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        if ($companyId > 0 && $enterpriseId > 0 && $userId > 0) {
            try {
                (new PassphraseVerifiedRedisService())->forgetVerifiedForUserEnterprise($companyId, $enterpriseId, $userId);
            } catch (\Throwable $e) {
                app('log')->warning('forget passphrase verified redis failed: '.$e->getMessage(), ['exception' => $e]);
            }
        }

        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

}
