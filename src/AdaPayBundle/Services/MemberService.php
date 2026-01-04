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

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayMemberUpdateLog;
use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Entities\AdapayMember;
use AdaPayBundle\Entities\AdapaySettleAccount;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AdaPayBundle\Entities\AdapayEntryApply;
use DistributionBundle\Services\DistributorService;
use CompanysBundle\Services\EmployeeService;
use AdaPayBundle\Entities\AdapayMerchantEntry;
use PaymentBundle\Services\Payments\AdaPaymentService;

class MemberService
{
    public $adapayMemberRepository;
    public $adapayEntryApplyRepository;
    public $adapayMerchantEntryRepository;
    public $adapayMemberUpdateLogRepository;
    public $adapaySettleAccountRepository;

    public const AUDIT_WAIT = 'A';//待审核
    public const AUDIT_FAIL = 'B';//审核失败
    public const AUDIT_MEMBER_FAIL = 'C';//开户失败
    public const AUDIT_ACCOUNT_FAIL = 'D';//开户成功但未创建结算账户
    public const AUDIT_SUCCESS = 'E';//开户和创建结算账户成功

    public function __construct($companyId = 0)
    {
        // ID: 53686f704578
        if ($companyId) {
            // parent::init($companyId);
        }
        $this->adapayMemberRepository = app('registry')->getManager('default')->getRepository(AdapayMember::class);
        $this->adapayEntryApplyRepository = app('registry')->getManager('default')->getRepository(AdapayEntryApply::class);
        $this->adapayMerchantEntryRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantEntry::class);
        $this->adapayMemberUpdateLogRepository = app('registry')->getManager('default')->getRepository(AdapayMemberUpdateLog::class);
        // $this->adapaySettleAccountRepository = app('registry')->getManager('default')->getRepository(AdapaySettleAccount::class);
    }

    public function createMember($data = [], $createSettleAccount = false)
    {
        $app_id = $this->getAppId($data['company_id']);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $operator = $this->getOperator();
            $member = [
                'app_id' => $app_id,
                'operator_id' => $operator['operator_id'],
                'operator_type' => $operator['operator_type'],
                'tel_no' => $data['tel_no'] ?? '',
                'user_name' => $data['user_name'] ?? '',
                'cert_id' => $data['cert_id'] ?? '',
                'cert_type' => $data['cert_type'] ?? '00',
                'company_id' => $data['company_id'],
                'member_type' => $data['member_type'] ?? 'person',
                'audit_state' => '0',
            ];
            $result = $this->adapayMemberRepository->create($member);
            if (!$result) {
                throw new BadRequestHttpException('用户创建失败');
            }
            $memberId = $result['id'];

            if ($createSettleAccount) {
                $settleAccount = [
                    'app_id' => $app_id,
                    'member_id' => $memberId,
                    'bank_acct_type' => '2',//银行账户类型：1-对公；2-对私
                    'card_id' => $data['bank_card_id'] ?? '',
                    'card_name' => $data['bank_card_name'] ?? '',
                    'cert_id' => $data['bank_cert_id'] ?? '',
                    'cert_type' => $data['cert_type'] ?? '00',
                    'tel_no' => $data['bank_tel_no'] ?? '',
                    'company_id' => $data['company_id'],
                    'channel' => $data['channel'] ?? 'bank_account',
                ];
                $settleAccountService = new SettleAccountService();
                $query = $settleAccountService->create($settleAccount);
                if (!$query) {
                    throw new BadRequestHttpException('结算账户创建失败');
                }
            }

            //同时创建一条申请记录
            $apply = [
                'user_name' => $data['user_name'] ?? '',
                'company_id' => $data['company_id'],
                'entry_id' => $memberId,
                'apply_type' => $operator['operator_type'],
                'status' => 'WAIT_APPROVE',
            ];
            $rs = $this->adapayEntryApplyRepository->create($apply);
            if (!$rs) {
                throw new BadRequestHttpException("开户申请创建失败");
            }

            (new AdapayLogService())->recordLogByType($data['company_id'], 'create_member_log');
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        //提交到adaPay审核，这里已经没用了，添加数据的时候不允许提交审核
        /*
        $submitReview = $data['submit_review'] ?? 'N';
        if ($submitReview == 'Y') {
            $res = $this->createApi($memberId);
            if ($res) {
                $settleAccountService->createApi($memberId);//创建用户成功后，进入创建账号
            }
        }
        */

        return $result;
    }

    public function createPromoter($companyId, $promoterId, $data)
    {
        $appId = $this->getAppId($companyId);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $member = [
                'app_id' => $appId,
                'operator_id' => $promoterId,
                'operator_type' => AdapayPromoterService::PROMOTER_OPERATOR_TYPE,
                'tel_no' => $data['tel_no'],
                'user_name' => $data['card_name'],
                'cert_id' => $data['cert_id'],
                'cert_type' => '00',
                'company_id' => $companyId,
                'member_type' => 'person',
                'audit_state' => '0',
            ];
            $result = $this->adapayMemberRepository->create($member);
            if (!$result) {
                throw new BadRequestHttpException('用户创建失败');
            }
            $memberId = $result['id'];

            $settleAccount = [
                'app_id' => $appId,
                'member_id' => $memberId,
                'bank_acct_type' => '2',
                'card_id' => $data['card_id'],
                'card_name' => $data['card_name'],
                'cert_id' => $data['cert_id'],
                'cert_type' => '00',
                'tel_no' => $data['tel_no'],
                'company_id' => $companyId,
                'channel' => 'bank_account',
            ];
            $settleAccountService = new SettleAccountService();
            $query = $settleAccountService->create($settleAccount);
            if (!$query) {
                throw new BadRequestHttpException('结算账户创建失败');
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $member['id'] = $memberId;
        $member = array_merge($member, $settleAccount);


        $subMerchantService = new SubMerchantService();
        $savePersonResult = $subMerchantService->savePersonMember($companyId, $appId, $member, false);

        if ($savePersonResult['data']['status'] == 'succeeded') {
            $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $memberId], ['audit_state' => self::AUDIT_ACCOUNT_FAIL]);

            $createSettleResult = $subMerchantService->createSettleAccount($companyId, $appId, $memberId);

            if ($createSettleResult['data']['status'] == 'succeeded') {
                $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $memberId], ['audit_state' => self::AUDIT_SUCCESS]);
                $settleAccountService->updateBy(['company_id' => $companyId, 'member_id' => $memberId], ['settle_account_id' => $createSettleResult['data']['id']]);
            } else {
                $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $memberId], ['audit_state' => self::AUDIT_ACCOUNT_FAIL, 'audit_desc' => $createSettleResult['data']['error_msg']]);
            }
        } else {
            $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $memberId], ['audit_state' => self::AUDIT_MEMBER_FAIL, 'audit_desc' => $savePersonResult['data']['error_msg']]);
        }

        return $result;
    }

    public function updatePromoter($companyId, $params)
    {
        $appId = $this->getAppId($companyId);
        $memberDbInfo = $this->adapayMemberRepository->getInfo(['id' => $params['member_id'], 'company_id' => $companyId]);
        if (!$memberDbInfo) {
            throw new ResourceException('开户信息不存在');
        }

        $member = [
            'tel_no' => $params['tel_no'],
            'user_name' => $params['card_name'],
            'cert_id' => $params['cert_id'],
            'audit_state' => '0',
            'audit_desc' => '',
        ];
        $settleAccountService = new SettleAccountService();

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = ['id' => $memberDbInfo['id']];
            $result = $this->adapayMemberRepository->updateBy($filter, $member);
            if (!$result) {
                throw new BadRequestHttpException('用户更新失败');
            }
            $accountData = [
                'card_id' => $params['card_id'],
                'card_name' => $params['card_name'],
                'cert_id' => $params['cert_id'],
                'tel_no' => $params['tel_no'],
            ];
            $settleFilter = ['member_id' => $memberDbInfo['id'], 'company_id' => $companyId];
            $result = $settleAccountService->updateBy($settleFilter, $accountData);
            if (!$result) {
                throw new BadRequestHttpException('结算账户更新失败');
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $settleAccountService = new SettleAccountService();
        $settleAccount = $settleAccountService->getInfo(['company_id' => $companyId, 'member_id' => $memberDbInfo['id']]);

        $updatedMemberDbInfo = $this->adapayMemberRepository->getInfo(['id' => $params['member_id'], 'company_id' => $companyId]);

        $subMerchantService = new SubMerchantService();
        if ($memberDbInfo['audit_state'] == 'E' || $memberDbInfo['audit_state'] == 'D') {
            $isUpdate = true;
        } else {
            $isUpdate = false;
        }
        $savePersonResult = $subMerchantService->savePersonMember($companyId, $appId, $updatedMemberDbInfo, $isUpdate);

        if ($savePersonResult['data']['status'] == 'succeeded') {
            // 已有结算账户修改，需删除重建
            $settleAccountAble = true;

            if ($settleAccount['settle_account_id']) {
                $deleteSettleResult = $subMerchantService->deleteSettleAccount($companyId, $appId, $settleAccount['settle_account_id'], $updatedMemberDbInfo);
                if ($deleteSettleResult['data']['status'] == 'failed' && isset($deleteSettleResult['data']['error_code']) && $deleteSettleResult['data']['error_code'] != 'account_not_exists') {
                    $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $updatedMemberDbInfo['id']], ['audit_state' => self::AUDIT_ACCOUNT_FAIL, 'audit_desc' => $deleteSettleResult['data']['error_msg']]);
                    $settleAccountAble = false;
                }
            }

            if ($settleAccountAble) {
                $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $updatedMemberDbInfo['id']], ['audit_state' => self::AUDIT_ACCOUNT_FAIL]);
                $createSettleResult = $subMerchantService->createSettleAccount($companyId, $appId, $updatedMemberDbInfo['id']);

                if ($createSettleResult['data']['status'] == 'succeeded') {
                    $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $updatedMemberDbInfo['id']], ['audit_state' => self::AUDIT_SUCCESS]);
                    $settleAccountService->updateBy(['company_id' => $companyId, 'member_id' => $updatedMemberDbInfo['id']], ['settle_account_id' => $createSettleResult['data']['id']]);
                } else {
                    $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $updatedMemberDbInfo['id']], ['audit_state' => self::AUDIT_ACCOUNT_FAIL, 'audit_desc' => $createSettleResult['data']['error_msg']]);
                }
            }
        } else {
            $this->adapayMemberRepository->updateBy(['company_id' => $companyId, 'id' => $memberDbInfo['id']], ['audit_state' => self::AUDIT_MEMBER_FAIL, 'audit_desc' => $savePersonResult['data']['error_msg']]);
        }

        return true;
    }

    public function modifyMember($params = [])
    {
        $operator = $this->getOperator();
        $rs = $this->adapayMemberRepository->getInfo(['id' => $params['member_id'], 'company_id' => $params['company_id']]);
        if (!$rs) {
            throw new ResourceException('开户信息不存在');
        }

        $member = [
            'tel_no' => $params['tel_no'] ?? '',
            'user_name' => $params['user_name'] ?? '',
            'cert_id' => $params['cert_id'] ?? '',
            'audit_state' => '0',
            'audit_desc' => '',
        ];

        $settleAccountService = new SettleAccountService();

        $isUpdateAction = $rs['audit_state'] == self::AUDIT_ACCOUNT_FAIL || $rs['audit_state'] == self::AUDIT_SUCCESS;

        if ($isUpdateAction) {
            unset($member['user_name']);
            unset($member['cert_id']);
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $filter = ['id' => $rs['id']];
            $result = $this->adapayMemberRepository->updateBy($filter, $member);
            if (!$result) {
                throw new BadRequestHttpException('用户更新失败');
            }
            $accountData = [
                'card_id' => $params['bank_card_id'],
                'card_name' => $params['bank_card_name'],
                'cert_id' => $params['bank_cert_id'],
                'tel_no' => $params['bank_tel_no'],
                //'bank_acct_type' => '2',//银行账户类型：1-对公；2-对私
                //'cert_type' => '00',
                //'channel' => 'bank_account',
            ];

            if ($isUpdateAction) {
                unset($accountData['card_name']);
                unset($accountData['cert_id']);
            }

            $settleFilter = ['member_id' => $rs['id'], 'company_id' => $params['company_id']];
            $result = $settleAccountService->updateBy($settleFilter, $accountData);
            if (!$result) {
                throw new BadRequestHttpException('结算账户更新失败');
            }

            //同时创建一条申请记录
            $apply = [
                'user_name' => $params['user_name'] ?? '',
                'company_id' => $params['company_id'],
                'entry_id' => $rs['id'],
                'apply_type' => $operator['operator_type'],
                'status' => 'WAIT_APPROVE',
            ];
            $rs = $this->adapayEntryApplyRepository->create($apply);
            if (!$rs) {
                throw new BadRequestHttpException("开户申请创建失败");
            }

            (new AdapayLogService())->recordLogByType($params['company_id'], 'update_member_log');
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return true;
    }


    public function updateMember($params = [])
    {
        $filter = [
            'id' => $params['member_id'],
            'company_id' => $params['company_id']
        ];
        $memberInfo = $this->getInfo($filter);
        if (!$memberInfo) {
            throw new ResourceException('开户信息不存在');
        }


        $settleFilter = ['member_id' => $params['member_id'], 'company_id' => $params['company_id']];
        $settleAccountService = new SettleAccountService();
        $accountInfo = $settleAccountService->getInfo($settleFilter);
        if (!$accountInfo) {
            throw new BadRequestHttpException('结算账户不存在');
        }


        $subMerchantService = new SubMerchantService();
        if ($accountInfo['settle_account_id']) {
            $apiRes = $subMerchantService->deleteSettleAccount($params['company_id'], $memberInfo['app_id'], $accountInfo['settle_account_id'], $memberInfo);
            if ($apiRes['data']['status'] == 'failed' && isset($apiRes['data']['error_code']) && $apiRes['data']['error_code'] != 'account_not_exists') {
                throw new BadRequestHttpException('结算账户更新失败: ' . $apiRes['data']['error_msg']);
            }
        }

        $this->updateOneBy($filter, ['is_update' => 1]);
        $settleAccountService->updateOneBy($settleFilter, ['tel_no' => $params['bank_tel_no'], 'card_id' => $params['bank_card_id']]);

        $apiRes = $subMerchantService->createSettleAccount($params['company_id'], $memberInfo['app_id'], $memberInfo['id']);
        if ($apiRes['data']['status'] == 'succeeded') {
            $this->updateBy(['company_id' => $params['company_id'], 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_SUCCESS, 'audit_desc' => '']);
            $settleAccountService->updateBy(['company_id' => $params['company_id'], 'member_id' => $memberInfo['id']], ['settle_account_id' => $apiRes['data']['id']]);

            $data = [
                'company_id' => $params['company_id'],
                'app_id' => $memberInfo['app_id'],
                'member_id' => $params['member_id'],
                'data' => json_encode($params),
                'audit_state' => self::AUDIT_SUCCESS,
            ];
            $this->adapayMemberUpdateLogRepository->create($data);
        } else {
            $data = [
                'company_id' => $params['company_id'],
                'app_id' => $memberInfo['app_id'],
                'member_id' => $params['member_id'],
                'data' => json_encode($params),
                'audit_state' => self::AUDIT_ACCOUNT_FAIL,
                'audit_desc' => $apiRes['data']['error_msg'] . '(请尽快提交修改信息，避免影响分账功能)',
            ];
            $this->adapayMemberUpdateLogRepository->create($data);
            throw new BadRequestHttpException('结算账户更新失败: ' . $apiRes['data']['error_msg']);
        }


        (new AdapayLogService())->recordLogByType($params['company_id'], 'update_member_log');


        return ['status' => true];
    }

    public function autoCreate($memberIds = [], $hfAppid = '')
    {
        $data = $this->getInfo(['id' => $memberIds]);
        if (!$data) {
            return false;
        }//理论上不可能找不到

        $data['app_id'] = $hfAppid;
        $data['pid'] = $data['id'];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $member = [
                'app_id' => $data['app_id'],
                'pid' => $data['pid'],
                'tel_no' => $data['tel_no'] ?? '',
                'user_name' => $data['user_name'] ?? '',
                'cert_id' => $data['cert_id'] ?? '',
                'cert_type' => $data['cert_type'] ?? '00',
                'company_id' => $data['company_id'],
                'member_type' => $data['member_type'] ?? 'person',
                'audit_state' => '0',
            ];
            $result = $this->create($member);
            if (!$result) {
                throw new BadRequestHttpException('用户创建失败');
            }

            $memberId = $result['id'];
            $settleAccount = [
                'app_id' => $data['app_id'],
                'member_id' => $memberId,
                'bank_acct_type' => '2',//银行账户类型：1-对公；2-对私
                'card_id' => $data['bank_card_id'] ?? '',
                'card_name' => $data['bank_card_name'] ?? '',
                'cert_id' => $data['bank_cert_id'] ?? '',
                'cert_type' => $data['cert_type'] ?? '00',
                'tel_no' => $data['bank_tel_no'] ?? '',
                'company_id' => $data['company_id'],
                'channel' => $data['channel'] ?? 'bank_account',
            ];
            $settleAccountService = new SettleAccountService();
            $query = $settleAccountService->create($settleAccount);
            if (!$query) {
                throw new BadRequestHttpException('结算账户创建失败');
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }

    //提交审核
    //    public function createApi($id = 0)
    //    {
    //        $memberInfo = $this->getInfo(['id' => $id]);
    //        if (!$memberInfo) {
    //            throw new ResourceException('提交审核的用户信息不存在');
    //        }
    //
    //        //已经开户成功的，进入更新流程
    //        if ($memberInfo['audit_state'] == self::AUDIT_ACCOUNT_FAIL) {
    //            return $this->updateApi($id);
    //        }
    //
    //        $obj = new MemberApi();
    //        $obj_params = array(
    //            'app_id' => $memberInfo['app_id'], # app_id
    //            'adapay_func_code' => 'members.realname',
    //            'member_id' => $id,# 用户id
    //            //'location' => '上海市闵行区汇付',# 用户地址
    //            //'email' => '123123@126.com',# 用户邮箱
    //            //'gender' => 'MALE',# 性别
    //            'tel_no' => $memberInfo['tel_no'],# 用户手机号
    //            'user_name' => $memberInfo['user_name'],
    //            'cert_type' => $memberInfo['cert_type'],
    //            'cert_id' => $memberInfo['cert_id'],
    //            //'nickname' => 'test',# 用户昵称
    //        );
    //        $obj->create($obj_params);
    //
    //        $adaPayRes = false;
    //        if ($obj->isError()) {
    //            $errorMsg = $this->getErrorMsg($obj);
    //            //throw new ResourceException($errorMsg);
    //            //更新审核状态
    //            $memberData = [
    //                'audit_state' => self::AUDIT_MEMBER_FAIL,//开户失败
    //                'audit_desc' => $errorMsg,
    //            ];
    //        } else {
    //            $adaPayRes = true;
    //            $memberData = [
    //                'audit_state' => self::AUDIT_ACCOUNT_FAIL,//开户成功但未创建结算账户
    //                'audit_desc' => '',
    //            ];
    //        }
    //        $this->updateOneBy(['id' => $id], $memberData);
    //
    //        return $adaPayRes;
    //    }

    //    public function updateApi($id)
    //    {
    //        $memberInfo = $this->getInfo(['id' => $id]);
    //        if (!$memberInfo) {
    //            throw new ResourceException('提交审核的用户信息不存在');
    //        }
    //
    //        $obj = new MemberApi();
    //        $obj_params = [
    //            'app_id' => $memberInfo['app_id'], # app_id
    //            'member_id' => $id,# 用户id
    //            //'location' => '上海市徐汇区汇付天下',# 用户地址
    //            //'email' => 'app1231@163.com',# 用户邮箱
    //            //'gender' => 'MALE',# 性别
    //            'tel_no' => $memberInfo['tel_no'],# 用户手机号
    //            //'disabled' => 'N',# 是否禁用该用户
    //            //'nickname' => '正式',# 用户昵称
    //        ];
    //        $obj->update($obj_params);
    //
    //        if ($obj->isError()) {
    //            $errorMsg = $this->getErrorMsg($obj);
    //            //throw new ResourceException($errorMsg);
    //            $memberData = [
    //                'audit_desc' => $errorMsg,
    //            ];
    //            $this->updateOneBy(['id' => $id], $memberData);
    //            return false;
    //        }
    //        return true;
    //    }

    /**
     * 获取用户信息
     *
     * $userId
     */
    //    private function query($data = [])
    //    {
    //        $obj = new MemberApi();
    //        $obj_params = [
    //            'app_id' => 'app_7d87c043-aae3-4357-9b2c-269349a980d6',
    //            'member_id' => 'hf_prod_member_20190920'
    //        ];
    //        $obj->query($obj_params);
    //
    //        if ($obj->isError()) {
    //            $errorMsg = $this->getErrorMsg($obj);
    //            throw new ResourceException($errorMsg);
    //        }
    //        return $obj->result;
    //    }

    /**
     * 根据 Id 获取绑定的多个 adapay 账户
     *
     * @param int $id
     * @return array
     */
    public function getBindMembers($id = 0)
    {
        $bindMembers = [];
        $rs = $this->getInfoById($id);
        $bindMembers[$rs['app_id']] = $rs;

        $filter = ['pid' => $id];
        $members = $this->getLists($filter);
        foreach ($members as $v) {
            $bindMembers[$v['app_id']] = $v;
        }

        return $bindMembers;
    }

    public function getFilter($params = [])
    {
        $filter = [];
        $filter['pid'] = 0;//汇付多应用的时候，默认只显示一条记录
        $memberType = $params['member_type'] ?? '';
        $userName = $params['user_name'] ?? '';
        $certId = $params['cert_id'] ?? '';
        $auditState = $params['audit_state'] ?? '';

        if ($memberType) {
            $filter['member_type'] = $memberType;
        }
        if ($userName) {
            $filter['user_name'] = $userName;
        }
        if ($certId) {
            $filter['cert_id'] = $certId;
        }
        if ($auditState == '0') {
            $filter['audit_state'] = '0';
        }
        if ($auditState == '1') {
            $filter['audit_state'] = 'A';
        }
        if ($auditState == '2') {
            $filter['audit_state'] = 'E';
        }
        if ($auditState == '9') {
            $filter['audit_state'] = ['B', 'C', 'D'];
        }
        return $filter;
    }

    public function getFilterOptions()
    {
        return [
            'audit_state' => [
                'name' => '审核状态',
                'options' => [
                    '0' => '待提交',
                    '1' => '待审核',
                    '2' => '审核通过',
                    '9' => '审核失败',
                ]
            ],
            'member_type' => [
                'name' => '账户类型',
                'options' => [
                    'corp' => '企业',
                    'person' => '个人',
                ]
            ]
        ];
    }

    public function handleAuditState($auditState = '')
    {
        switch ($auditState) {
            case self::AUDIT_WAIT:
                $auditState = '1';//待审核
                break;
            case self::AUDIT_SUCCESS:
                $auditState = '2';//审核通过
                break;
            case self::AUDIT_FAIL:
            case self::AUDIT_MEMBER_FAIL:
            case self::AUDIT_ACCOUNT_FAIL:
                $auditState = '9';//审核失败
                break;
            default:
                $auditState = '0';//未提交
        }

        return $auditState;
    }

    //获取开户详情 个人跟企业暂时都调这个接口
    public function getMemberInfo($filter)
    {
        $operator = $this->getOperator();
        if (!isset($filter['operator_id']) && !isset($filter['id'])) {
            $filter['operator_id'] = $operator['operator_id'];
            $filter['operator_type'] = $operator['operator_type'];
        }
        // $filter['audit_state'] = 'E'; //开户成功条件
        $result = $this->getInfo($filter, ['id' => 'DESC']);
        if (!$result) {
            return ['basicInfo' => $this->getBasic($filter, $result)];
        }

        //用来保存和识别商户的多条数据的关联关系
        /*
        if ($result['adapay_member_id']) {
            $result['id'] = $result['adapay_member_id'];
        }
        */

        //结算账户信息
        $accountFilter = [
            'member_id' => $result['id'],
            'company_id' => $filter['company_id'],
        ];
        $settleAccountService = new SettleAccountService($filter['company_id']);
        $rsAccount = $settleAccountService->getInfo($accountFilter, ['id' => 'DESC']);
        if ($rsAccount) {
            $result['settle_account_id'] = $rsAccount['settle_account_id'];
            $result['bank_card_id'] = $rsAccount['card_id'];
            $result['bank_card_name'] = $rsAccount['card_name'];
            $result['bank_cert_id'] = $rsAccount['cert_id'];
            $result['bank_tel_no'] = $rsAccount['tel_no'];
            $result['bank_name'] = $rsAccount['bank_name'];
        }

        //企业用户信息
        if ($result['member_type'] == 'corp') {
            $corpMemberService = new CorpMemberService($filter['company_id']);
            $corpMemberInfo = $corpMemberService->getInfo(['member_id' => $result['id']], ['id' => 'DESC']);
            $corpMemberInfo['attach_file'] = app('filesystem')->disk('import-file')->privateDownloadUrl($corpMemberInfo['attach_file']);
            $regionService = new RegionService();
            $prov = $regionService->getAreaName($corpMemberInfo['prov_code']);
            $area = $regionService->getAreaName($corpMemberInfo['area_code']);
            $result['area'] = $prov . '-' . $area;
            $corpMemberInfo['audit_state'] = $corpMemberInfo['audit_state'] ?? '';
            $corpMemberInfo['audit_desc'] = $corpMemberInfo['audit_desc'] ?? '';
            unset($corpMemberInfo['audit_state'], $corpMemberInfo['audit_desc']);
            $result = array_merge($corpMemberInfo, $result); //数组顺序不能反
        // $this->personFormat($result);
        } else {
            $result['member_id'] = $result['id'];
        }
        $result['hf_audit_state'] = $result['audit_state'];
        $result['audit_state'] = $this->changeStatus($result['audit_state']);
        $basicInfo = $this->getBasic($filter, $result);
        $result['basicInfo'] = $basicInfo;
        return $result;
    }

    public function changeStatus($state)
    {
        switch ($state) {
            case '0':
            case 'A':
                $state = 'A';
                break;
            case 'B':
            case 'C':
            case 'D':
                $state = 'B';
                break;
            case 'E':
                $state = 'C';
                break;
            default:
                $state = 'A';
                break;
        }

        return $state;
    }

    public function getBasic($filter, &$data)
    {
        if (!$filter['operator_id']) {
            return [];
        }
        if ($filter['operator_type'] == 'distributor') {
            $distributorService = new DistributorService();
            $result = $distributorService->getInfo(['distributor_id' => $filter['operator_id'], 'company_id' => $filter['company_id']]);
            if (!$result) {
                return [];
            }
            $data['split_ledger_info'] = json_decode($result['split_ledger_info'], true);
            $result = [
                'name' => $result['name'],
                'contact' => $result['contact'],
                'hour' => $result['hour'],
                'is_ziti' => $result['is_ziti'],
                'auto_sync_goods' => $result['auto_sync_goods'],
                'is_delivery' => $result['is_delivery'],
                'is_dada' => $result['is_dada'],
                'area' => $result['province'] . ' - ' . $result['city']
            ];
            $result['email'] = isset($data['email']) ? $data['email'] : '';
            $result['tel_no'] = isset($data['tel_no']) ? $data['tel_no'] : '';
            return $result;
        } elseif ($filter['operator_type'] == 'dealer') {
            $employeeService = new EmployeeService();
            $result = $employeeService->getInfoStaff($filter['operator_id'], $filter['company_id']);
            if (!$result) {
                return [];
            }
            $data['split_ledger_info'] = json_decode($result['split_ledger_info'], true);
            $result = [
                'name' => $result['username'],
                'contact' => $result['contact'],
            ];
            $result['email'] = isset($data['email']) ? $data['email'] : '';
            $result['tel_no'] = isset($data['tel_no']) ? $data['tel_no'] : '';
            $result['area'] = isset($data['area']) ? $data['area'] : '';
            return $result;
        } else {
            return [];
        }
    }

    public function getOperator()
    {
        $operatorId = app('auth')->user()->get('operator_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $operatorId = app('auth')->user()->get('distributor_id');
        }
        if ($operatorType == 'dealer') {
            $dealerService = new DealerService();
            $operatorInfo = $dealerService->operatorsRepository->getInfo(['operator_id' => $operatorId]);
            if (!$operatorInfo) {
                throw new ResourceException('没有账号信息');
            }
            if (isset($operatorInfo['is_dealer_main']) && !$operatorInfo['is_dealer_main']) {
                $operatorId = $operatorInfo['dealer_parent_id'];
            }
        }
        return ['operator_type' => $operatorType, 'operator_id' => $operatorId];
    }

    public function getAuditState($filter)
    {
        $operator = $this->getOperator();
        if (!isset($filter['operator_id']) && !isset($filter['id'])) {
            $filter['operator_id'] = $operator['operator_id'];
            $filter['operator_type'] = $operator['operator_type'];
        }
        $result = $this->getInfo($filter);
        if (!$result) {
            return ['audit_state' => 'D', 'audit_desc' => '待提交'];
        }
        switch ($result['audit_state']) {
            case '0':
            case 'A':
                $result['audit_state'] = 'A';
                break;
            case 'B':
            case 'C':
            case 'D':
                $result['audit_state'] = 'B';
                break;
            case 'E':
                $result['audit_state'] = 'C';
                break;
            default:
                $result['audit_state'] = 'A';
                break;
        }

        return ['audit_state' => $result['audit_state'], 'audit_desc' => $result['audit_desc'], 'update_time' => $result['update_time'], 'member_type' => $result['member_type'], 'valid' => $result['valid']];
    }

    public function getMemberIdByOperatorId($operatorId, $operatorType)
    {
        $filter = [
            'operator_id' => $operatorId,
            'operator_type' => $operatorType,
        ];
        $info = $this->getInfo($filter);

        return $info['id'];
    }

    public function getAppId($companyId)
    {
        $service = new AdaPaymentService();
        $setting = $service->getPaymentSetting($companyId);
        if (!$setting) {
            throw new BadRequestHttpException('adapay 支付信息未配置');
        }
        $app_id = $setting['app_id'];
        return $app_id;
    }

    public function checkParams($params = [], $isCreate = false)
    {
        $preg_card = '/^[1-9]\d{5}(19|20)\d{2}[01]\d[0123]\d\d{3}[X\d]$/';
        if ($params['cert_id'] ?? 0) {
            if (!preg_match($preg_card, $params['cert_id'])) {
                throw new ResourceException('身份证号码格式错误');
            }
        }

        if ($params['bank_cert_id'] ?? 0) {
            if (!preg_match($preg_card, $params['bank_cert_id'])) {
                throw new ResourceException('开户人身份证号码格式错误');
            }
        }
        return $params;
    }

    public function setValid()
    {
        $operator = $this->getOperator();
        $filter['operator_id'] = $operator['operator_id'];
        $filter['operator_type'] = $operator['operator_type'];
        if (!$filter['operator_id'] || !$filter['operator_type']) {
            return true;
        }
        return $this->updateOneBy($filter, ['valid' => true]);
    }

    public function listsService($companyId, $params, $page, $pageSize, $orderBy = ['create_time' => 'DESC'])
    {
        $filter['company_id'] = $companyId;
        //        if ($params['legal_person']) {
        //            $filter['legal_person'] = $params['legal_person'];
        //        }
        //
        //        if ($params['user_name']) {
        //            $filter['user_name'] = $params['user_name'];
        //        }

        if ($params['member_type']) {
            $filter['member_type'] = $params['member_type'];
        }

        if ($params['operator_type'] && $params['operator_type'] != 'all') {
            $filter['operator_type'] = $params['operator_type'];
        }

        if ($params['keywords']) {
            $filter['keywords'] = $params['keywords'];
        }

        //        if ($params['location']) {
        //            $filter['location'] = $params['location'];
        //        }

        $rs = $this->MemberRelCorpLists($filter, $page, $pageSize, $orderBy);

        return $rs;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapayMemberRepository->$method(...$parameters);
    }
}
