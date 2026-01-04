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
use AdaPayBundle\Services\Request\Request;
use Dingo\Api\Exception\ResourceException;

use AdaPayBundle\Entities\AdapayCorpMember;
use AdaPayBundle\Services\MemberService as AdaPayMemberService;
use GuzzleHttp\Client as Client;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AdaPayBundle\Entities\AdapayEntryApply;
use AdaPayBundle\Entities\AdapayMerchantEntry;
use PaymentBundle\Services\Payments\AdaPaymentService;
use AdaPayBundle\Services\CallBack\CorpMemberUpdate;

class CorpMemberService
{
    public $adapayCorpMemberRepository;
    public $adapayEntryApplyRepository;
    public $adapayMerchantEntryRepository;
    public $adapayMemberUpdateLogRepository;


    public function __construct($companyId = 0)
    {
        if ($companyId) {
            // parent::init($companyId);
        }
        $this->adapayCorpMemberRepository = app('registry')->getManager('default')->getRepository(AdapayCorpMember::class);
        $this->adapayEntryApplyRepository = app('registry')->getManager('default')->getRepository(AdapayEntryApply::class);
        $this->adapayMerchantEntryRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantEntry::class);
        $this->adapayMemberUpdateLogRepository = app('registry')->getManager('default')->getRepository(AdapayMemberUpdateLog::class);
    }

    //创建企业用户
    public function createCorpMember($data = [])
    {
        $data['order_no'] = date("YmdHis") . rand(100000, 999999);
        $service = new AdaPaymentService();
        $setting = $service->getPaymentSetting($data['company_id']);
        $app_id = $setting['app_id'];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data['app_id'] = $app_id;
            $memberService = new AdaPayMemberService();
            $operator = $memberService->getOperator();
            //保存到全局用户表
            $memberData = [
                'app_id' => $data['app_id'],
                'location' => $data['address'],
                'company_id' => $data['company_id'],
                'operator_id' => $operator['operator_id'] ?? 0,
                'operator_type' => $operator['operator_type'],
                'email' => $data['email'] ?? '',
                'member_type' => 'corp',
                'tel_no' => $data['legal_mp'],
                'user_name' => $data['name'],
                'cert_type' => '',
                'cert_id' => $data['social_credit_code'],
                'audit_state' => '0',//默认是未提交审核状态
//                'adapay_member_id' => 0,
            ];
            $res = $memberService->create($memberData);
            if (!$res) {
                throw new ResourceException('用户信息保存失败');
            }
            $data['member_id'] = $res['id'];
            $pid = $res['id'];//复制用户数据
            //防止重复处理文件
            // if (!$fileReady) {
            $data = $this->handleAttachFile($data);
            // }
            // $fileReady = true;
            // $data = $this->handleConfirmLetterFile($data);
            $res = $this->create($data);//保存到企业用户表
            if (!$res) {
                throw new ResourceException('企业用户信息保存失败');
            }
            $bankCodeService = new BankCodeService();
            //创建结算账户
            $settleAccount = [
                'app_id' => $data['app_id'],
                'member_id' => $data['member_id'],
                'bank_acct_type' => $data['bank_acct_type'] ?? '2',//银行账户类型：1-对公；2-对私
                'card_id' => $data['card_no'] ?? '',
                'card_name' => $data['card_name'] ?? '',
                'cert_id' => $data['legal_cert_id'] ?? '',
                'cert_type' => $data['cert_type'] ?? '00',
                'tel_no' => $data['legal_mp'] ?? '',
                'company_id' => $data['company_id'],
                'channel' => $data['channel'] ?? 'bank_account',
                'bank_code' => $data['bank_code'],
                'bank_name' => $bankCodeService->getBankName($data['bank_code'])
            ];
            $settleAccountService = new SettleAccountService();
            $query = $settleAccountService->create($settleAccount);
            if (!$query) {
                throw new BadRequestHttpException('结算账户创建失败');
            }

            $regionService = new RegionService();
            $prov = $regionService->getAreaName($data['prov_code']);
            $area = $regionService->getAreaName($data['area_code']);
            $address = $prov . "-" . $area;
            //同时创建一条申请记录
            $apply = [
                'user_name' => $data['name'] ?? '',
                'company_id' => $data['company_id'],
                'entry_id' => $data['member_id'],
                'apply_type' => $operator['operator_type'],
                'address' => $address,
                'status' => 'WAIT_APPROVE'
            ];
            $rs = $this->adapayEntryApplyRepository->create($apply);
            if (!$rs) {
                throw new BadRequestHttpException("开户申请创建失败");
            }

            // 记录日志
            (new AdapayLogService())->recordLogByType($data['company_id'], 'create_member_log');

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $res;
    }

    public function autoCreate($memberIds = [], $hfAppid = '')
    {
        $data = $this->getInfo(['member_id' => $memberIds]);
        if (!$data) {
            return false;
        }//理论上不可能找不到

        $data['app_id'] = $hfAppid;
        $data['pid'] = $data['member_id'];
        $data['order_no'] = date("YmdHis") . rand(100000, 999999);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //保存到全局用户表
            $memberData = [
                'app_id' => $data['app_id'],
                'pid' => $data['pid'],
                'location' => $data['address'],
                'company_id' => $data['company_id'],
                'operator_id' => $data['operator_id'] ?? 0,
                'email' => $data['email'] ?? '',
                'member_type' => 'corp',
                'tel_no' => $data['legal_mp'],
                'user_name' => $data['name'],
                'cert_type' => '',
                'cert_id' => $data['social_credit_code'],
                'audit_state' => '0',//默认是未提交审核状态
            ];
            $memberService = new AdaPayMemberService();
            $resMember = $memberService->create($memberData);
            if (!$resMember) {
                throw new ResourceException('用户信息保存失败');
            }

            $data['member_id'] = $resMember['id'];
            $res = $this->create($data);//保存到企业用户表
            if (!$res) {
                throw new ResourceException('企业用户信息保存失败');
            }

            //创建结算账户
            $settleAccount = [
                'app_id' => $data['app_id'],
                'member_id' => $data['member_id'],
                'bank_acct_type' => $data['bank_acct_type'] ?? '2',//银行账户类型：1-对公；2-对私
                'card_id' => $data['card_no'] ?? '',
                'card_name' => $data['card_name'] ?? '',
                'cert_id' => $data['legal_cert_id'] ?? '',
                'cert_type' => $data['cert_type'] ?? '00',
                'tel_no' => $data['legal_mp'] ?? '',
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

        return $resMember;
    }

    public function updateCorpMemberRequest($companyId, $appId, $memberInfo)
    {
        $corpData = [
            'company_id' => $companyId,
            'app_id' => $appId,# app_id
            'member_id' => $memberInfo['member_id'],# 商户用户id
            'order_no' => date('YmdHis') . rand(1000, 9999) . $memberInfo['member_id'],# 订单号
            'name' => $memberInfo['name'],# 企业名称
            'prov_code' => $memberInfo['prov_code'],# 省份
            'area_code' => $memberInfo['area_code'],# 地区
            'social_credit_code' => $memberInfo['social_credit_code'],# 统一社会信用码
            'social_credit_code_expires' => $memberInfo['social_credit_code_expires'], //（格式：YYYYMMDD，例如：20190909）
            'business_scope' => $memberInfo['business_scope'],# 经营范围
            'legal_person' => $memberInfo['legal_person'],# 法人姓名
            'legal_cert_id' => $memberInfo['legal_cert_id'],# 法人身份证号码
            'legal_cert_id_expires' => $memberInfo['legal_cert_id_expires'],//法人身份证有效期（格式：YYYYMMDD，例如：20190909）
            'legal_mp' => $memberInfo['legal_mp'],# 法人手机号
            'address' => $memberInfo['address'],# 企业地址
            'zip_code' => $memberInfo['zip_code'],# 邮编
            'telphone' => $memberInfo['telphone'],# 企业电话
            'email' => $memberInfo['email'],# 企业邮箱
            //'attach_file'                => $url,# 上传附件
            'notify_url' => config('adapay.notify_url'),
            'api_method' => 'CorpMember.update',
        ];

        if ($memberInfo['attach_file']) {
            $url = $this->getFilePath($memberInfo['attach_file']);
            $corpData['attach_file'] = $url;
            $corpData['file_content'] = file_get_contents($url);
        }

        return (new Request())->call($corpData);
    }

    //更新企业用户
    public function updateCorpMember($data = [])
    {
        $member_id = $data['member_id'];
        $memberService = new AdaPayMemberService();
        $memberInfo = $memberService->getInfo(['id' => $member_id]);
        if (!$memberInfo) {
            throw new BadRequestHttpException("开户信息不存在");
        }

//        $subMerchantService = new SubMerchantService();

        $memberService->updateOneBy(['id' => $member_id], ['is_update' => 1]);

        $data = $this->handleAttachFile($data);

        $apiRes = $this->updateCorpMemberRequest($data['company_id'], $memberInfo['app_id'], $data);
        if ($apiRes['data']['status'] == 'failed') {
            //$audit_state = $memberService::AUDIT_FAIL;
            $audit_desc = $apiRes['data']['error_msg'];
            $data = [
                'company_id' => $data['company_id'],
                'app_id' => $memberInfo['app_id'],
                'member_id' => $data['member_id'],
                'data' => json_encode($data),
                'audit_state' => $memberService::AUDIT_FAIL,
                'audit_desc' => $apiRes['data']['error_msg'],
            ];
            $this->adapayMemberUpdateLogRepository->create($data);
            throw new BadRequestHttpException('数据更新失败: '.$audit_desc);
        } else {
            $data = [
                'company_id' => $data['company_id'],
                'app_id' => $memberInfo['app_id'],
                'member_id' => $data['member_id'],
                'data' => json_encode($data),
                'audit_state' => $memberService::AUDIT_WAIT,
            ];
            $this->adapayMemberUpdateLogRepository->create($data);
            if ($apiRes['data']['status'] == 'succeeded') {
                $callbackData = $apiRes['data'];
                $callbackData['audit_state'] = CorpMemberUpdate::AUDIT_UPDATE_SUCCESS;
                $callback = new CorpMemberUpdate();
                $callback->succeeded($callbackData);
            }
        }
//        $memberService->updateOneBy($filter, ['audit_state' => $audit_state, 'audit_desc' => $audit_desc]);


        (new AdapayLogService())->recordLogByType($data['company_id'], 'update_member_log');


        return ['status' => true];
    }

    public function syncBaseData($params)
    {
        $memberId = $params['member_id'];
        $data = json_decode($params['data'], true);
        $adaPayMemberService = new AdaPayMemberService();
        $updateData = [
            'email' => $data['email'],
            'tel_no' => $data['legal_mp'],
            'user_name' => $data['name'],
            'cert_id' => $data['social_credit_code'],
        ];

        $memberInfo = $adaPayMemberService->updateOneBy(['id' => $memberId], $updateData);

        $cropInfo = $this->updateOneBy(['member_id' => $memberId, 'company_id' => $params['company_id']], $data);

        return ['member_info' => $memberInfo, 'corp_info' => $cropInfo];
    }

    public function waitDataTranf($adapayMemberInfo)
    {
        if (isset($adapayMemberInfo['member_type']) && $adapayMemberInfo['member_type'] == 'corp') {
            $updateData = $this->adapayMemberUpdateLogRepository->getInfo(['member_id' => $adapayMemberInfo['member_id']], ['id' => 'DESC']);
            if (!$updateData) {
                return $adapayMemberInfo;
            }
            $data = json_decode($updateData['data'], true);
            if ($updateData['audit_state'] == 'A') {
                $adapayMemberInfo['name'] = $data['name'];
                $adapayMemberInfo['prov_code'] = $data['prov_code'];
                $adapayMemberInfo['area_code'] = $data['area_code'];
                $adapayMemberInfo['social_credit_code'] = $data['social_credit_code'];
                $adapayMemberInfo['social_credit_code_expires'] = $data['social_credit_code_expires'];
                $adapayMemberInfo['business_scope'] = $data['business_scope'];
                $adapayMemberInfo['legal_person'] = $data['legal_person'];
                $adapayMemberInfo['legal_cert_id'] = $data['legal_cert_id'];
                $adapayMemberInfo['legal_cert_id_expires'] = $data['legal_cert_id_expires'];
                $adapayMemberInfo['legal_mp'] = $data['legal_mp'];
                $adapayMemberInfo['address'] = $data['address'];
                $adapayMemberInfo['zip_code'] = $data['zip_code'];
                $adapayMemberInfo['telphone'] = $data['telphone'];
                $adapayMemberInfo['email'] = $data['email'];
                $adapayMemberInfo['attach_file'] = $data['attach_file'];
                $adapayMemberInfo['attach_file_name'] = $data['attach_file_name'];
                $adapayMemberInfo['bank_code'] = $data['bank_code'];
                $adapayMemberInfo['bank_acct_type'] = $data['bank_acct_type'];
                $adapayMemberInfo['card_no'] = $data['card_no'];
                $adapayMemberInfo['card_name'] = $data['card_name'];
                $adapayMemberInfo['audit_state'] = $updateData['audit_state'];
//                $adapayMemberInfo['social_credit_code_expires'] = $data['social_credit_code_expires'];
            } elseif ($updateData['audit_state'] == 'B' || $updateData['audit_state'] == 'C' || $updateData['audit_state'] == 'D') {
                $adapayMemberInfo['audit_state'] = $updateData['audit_state'];
                $adapayMemberInfo['audit_desc'] = $updateData['audit_desc'];
            }
        }

        return $adapayMemberInfo;
    }

    public function modifyCorpMember($data = [])
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $memberService = new AdaPayMemberService();
            $operator = $memberService->getOperator();
            //查询所有关联的企业用户
            $rs = $memberService->getInfo(['id' => $data['member_id']]);
            if ($rs) {
                $member_id = $rs['id'];
            } else {
                throw new BadRequestHttpException("开户信息不存在");
            }
            // $member_id
            //更新全局用户表 member
            $filter = [
                'id' => $member_id,
                'company_id' => $data['company_id'],
            ];
            $memberData = [
                'location' => $data['address'],
                'email' => $data['email'] ?? '',
                'tel_no' => $data['legal_mp'],
                'user_name' => $data['name'],
                'cert_id' => $data['social_credit_code'],
                'audit_state' => '0',//默认是未提交审核状态
                'audit_desc' => '' //重新提交的时候清空审核结果
            ];

            // 申请成功后修改
            $isSuccessUpdate = $rs['audit_state'] == MemberService::AUDIT_ACCOUNT_FAIL || $rs['audit_state'] == MemberService::AUDIT_SUCCESS;

            if ($isSuccessUpdate) {
                unset($memberData['cert_id']);
            }

            $res = $memberService->updateBy($filter, $memberData);
            if (!$res) {
                throw new ResourceException('用户信息更新失败');
            }
            //更新企业用户表 corp_member
            $filter = [
                'member_id' => $member_id,
                'company_id' => $data['company_id'],
            ];
            $data = $this->handleAttachFile($data);
            // $data = $this->handleConfirmLetterFile($data);
            $res = $this->updateBy($filter, $data);
            if (!$res) {
                throw new ResourceException('企业用户信息更新失败');
            }

            //更新结算账户
            $filter = [
                'member_id' => $member_id,
                'company_id' => $data['company_id'],
            ];
            $bankCodeService = new BankCodeService();
            $accountData = [
                // 'app_id' => $res['app_id'], //
                'company_id' => $data['company_id'],
                'member_id' => $member_id,
                'bank_acct_type' => $data['bank_acct_type'] ?? '2',//银行账户类型：1-对公；2-对私
                'card_id' => $data['card_no'] ?? '',
                'card_name' => $data['card_name'] ?? '',
                'cert_id' => $data['legal_cert_id'] ?? '',
                'cert_type' => $data['cert_type'] ?? '00',
                'tel_no' => $data['legal_mp'] ?? '',
                'channel' => $data['channel'] ?? 'bank_account',
                'bank_code' => $data['bank_code'],
                'bank_name' => $bankCodeService->getBankName($data['bank_code'])
            ];

            // if ($isSuccessUpdate) {
            //     unset($accountData['card_name']);
            // }

            $settleAccountService = new SettleAccountService();
            $query = $settleAccountService->updateBy($filter, $accountData);
            if (!$query) {
                throw new BadRequestHttpException('结算账户更新失败');
            }
            $regionService = new RegionService();
            $prov = $regionService->getAreaName($data['prov_code']);
            $area = $regionService->getAreaName($data['area_code']);
            $address = $prov."-".$area;
            //同时创建一条申请记录
            $apply = [
                'user_name' => $data['name'] ?? '',
                'company_id' => $data['company_id'],
                'entry_id' => $data['member_id'],
                'apply_type' => $operator['operator_type'],
                'address' => $address,
                'status' => 'WAIT_APPROVE'
            ];
            $rs = $this->adapayEntryApplyRepository->create($apply);
            if (!$rs) {
                throw new BadRequestHttpException("开户申请创建失败");
            }

            (new AdapayLogService())->recordLogByType($data['company_id'], 'update_member_log');

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $res;
    }

    public function handleAttachFile($data = [])
    {
        //更新数据的时候可以不更新附件。
        if (!isset($data['attach_file']) or !$data['attach_file']) {
            unset($data['attach_file']);
            return $data;
        }

        $fileName = $data['attach_file']->getClientOriginalName();
        $file = $data['attach_file']->getRealPath();
        $extension = $data['attach_file']->getClientOriginalExtension();
        if ($extension != 'zip') {
            throw new BadRequestHttpException('文件类型不符合要求');
        }
        $filePath = 'adapay/' . $data['company_id'] . '/' . time() . '/' . md5($fileName) . '.zip';

        //上传文件
        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put($filePath, file_get_contents($file));
        $data['attach_file'] = $filePath;
        $data['attach_file_name'] = $fileName;
        return $data;
    }

    public function handleConfirmLetterFile($data = [])
    {
        //更新数据的时候可以不更新附件。
        if (!isset($data['confirm_letter_file']) or !$data['confirm_letter_file']) {
            unset($data['confirm_letter_file']);
            return $data;
        }

        $fileName = $data['confirm_letter_file']->getClientOriginalName();
        $file = $data['confirm_letter_file']->getRealPath();
        $extension = $data['confirm_letter_file']->getClientOriginalExtension();
        if (!in_array($extension, ['zip', 'jpg', 'jpeg', 'png', 'pdf'])) {
            throw new BadRequestHttpException('文件类型不符合要求');
        }
        $filePath = 'adapay/' . $data['company_id'] . '/' . time() . '/' . md5($fileName) . '.' . $extension;

        //上传文件
        $filesystem = app('filesystem')->disk('import-file');
        $filesystem->put($filePath, file_get_contents($file));
        //$fileUrl = $filesystem->privateDownloadUrl($filePath);

        $data['confirm_letter_file'] = $filePath;
        $data['confirm_letter_file_name'] = $fileName;
        return $data;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 获取文件的临时路径
     * @param $filePath
     * @return bool|string
     */
    public function getFilePath($filePath)
    {
        $filesystem = app('filesystem')->disk('import-file');
        $url = $filesystem->privateDownloadUrl($filePath);

        return $url;
        //兼容本地文件存储
        // if (strtolower(substr($url, 0, 4)) != 'http') {
        //     $url = storage_path('uploads') . '/' . $filePath;
        //     $content = file_get_contents($url);
        // } else {
        //     $client = new Client();
        //     $content = $client->get($url)->getBody()->getContents();
        // }

        // $tmpTarget = tempnam('/tmp', 'import-file');
        // file_put_contents($tmpTarget, $content);
        // return $tmpTarget;
    }

    public function getFileUrl($filePath)
    {
        $filesystem = app('filesystem')->disk('import-file');
        return $filesystem->privateDownloadUrl($filePath);
    }

    //    public function queryApi($memberId = 0)
    //    {
    //        $data = $this->getInfo(['member_id' => $memberId]);
    //        if (!$data) {
    //            throw new ResourceException('企业用户信息不存在');
    //        }
    //
    //        $obj = new CorpMemberApi();
    //        $obj_params = array(
    //            'app_id' => $data['app_id'],# app_id
    //            'member_id' => $data['member_id'],# 商户用户id
    //        );
    //        $obj->query($obj_params);
    //        if ($obj->isError()) {
    //            $errorMsg = $this->getErrorMsg($obj);
    //            throw new ResourceException($errorMsg);
    //        }
    //        return $obj->result;
    //    }

    //    public function createApi($memberId = 0)
    //    {
    //        $data = $this->getInfo(['member_id' => $memberId]);
    //        if (!$data) {
    //            throw new ResourceException('企业用户信息不存在');
    //        }
    //
    //        $file_real_path = $this->getFilePath($data['attach_file']);
    //        $file_real_path = realpath($file_real_path);
    //
    //        $obj = new CorpMemberApi();
    //        $obj_params = array(
    //            'app_id' => $data['app_id'],# app_id
    //            'member_id' => $data['member_id'],# 商户用户id
    //            'order_no' => date('YmdHis') . rand(1111, 9999),# 订单号
    //            'name' => $data['name'],# 企业名称
    //            'prov_code' => $data['prov_code'],# 省份
    //            'area_code' => $data['area_code'],# 地区
    //            'social_credit_code' => $data['social_credit_code'],# 统一社会信用码
    //            'social_credit_code_expires' => $this->_formatDate($data['social_credit_code_expires']), //（格式：YYYYMMDD，例如：20190909）
    //            'business_scope' => $data['business_scope'],# 经营范围
    //            'legal_person' => $data['legal_person'],# 法人姓名
    //            'legal_cert_id' => $data['legal_cert_id'],# 法人身份证号码
    //            'legal_cert_id_expires' => $this->_formatDate($data['legal_cert_id_expires']),//法人身份证有效期（格式：YYYYMMDD，例如：20190909）
    //            'legal_mp' => $data['legal_mp'],# 法人手机号
    //            'address' => $data['address'],# 企业地址
    //            'zip_code' => $data['zip_code'],# 邮编
    //            'telphone' => $data['telphone'],# 企业电话
    //            'email' => $data['email'],# 企业邮箱
    //            'attach_file' => new \CURLFile($file_real_path),# 上传附件
    //            'bank_code' => $data['bank_code'],    # 银行代码
    //            'bank_acct_type' => $data['bank_acct_type'],# 银行账户类型
    //            'card_no' => $data['card_no'],# 银行卡号
    //            'card_name' => $data['card_name'],#银行卡对应的户名，
    //            'notify_url' => config('adapay.notify_url'),
    //        );
    //
    //        app('log')->info('CorpMemberApi => ' . var_export($obj_params, 1));
    //
    //        $obj->create($obj_params);
    //
    //        $filter = ['id' => $data['member_id']];
    //        $memberService = new AdaPayMemberService();
    //        if ($obj->isError()) {
    //            $errorMsg = $this->getErrorMsg($obj);
    //
    //            //更新审核状态
    //            $memberData = [
    //                'audit_state' => AdaPayMemberService::AUDIT_FAIL,
    //                'audit_desc' => $errorMsg,
    //            ];
    //            //throw new ResourceException($errorMsg);
    //        } else {
    //            //更新审核状态
    //            $memberData = [
    //                'audit_state' => AdaPayMemberService::AUDIT_WAIT,
    //                'audit_desc' => '',
    //            ];
    //        }
    //
    //        $memberService->updateOneBy($filter, $memberData);
    //
    //        return $obj->result;
    //    }

    public function checkParams($params = [], $isCreate = false)
    {
        $maxFileSize = 8.5 * 1024 * 1024;//最大上传文件 8M

        if ($params['bank_acct_type'] == '1' && $params['card_name'] != $params['name']) {
            throw new ResourceException('银行卡对应的户名，必须与企业名称一致');
        }

        if (is_string($params['area'])) {
            $params['area'] = explode(',', $params['area']);
        }
        if (!is_array($params['area']) or count($params['area']) != 2) {
            throw new ResourceException('地区数据格式错误');
        } else {
            $params['prov_code'] = $params['area'][0];
            $params['area_code'] = $params['area'][1];
        }

        if ($isCreate && !$params['attach_file']) {
            throw new ResourceException('请上传附件');
        }

        if ($params['attach_file'] && $params['attach_file']->getSize() >= $maxFileSize) {
            throw new ResourceException('附件不能超过8M');
        }

        if ($params['zip_code']) {
            $params['zip_code'] = substr($params['zip_code'], 0, 6);
        }

        if ($params['address']) {
            $params['address'] = mb_substr($params['address'], 0, 60);
        }

        if ($params['name']) {
            $params['name'] = mb_substr($params['name'], 0, 30);
        }

        if ($params['business_scope']) {
            $params['business_scope'] = mb_substr($params['business_scope'], 0, 500);
        }

        if ($params['legal_person']) {
            $params['legal_person'] = mb_substr($params['legal_person'], 0, 20);
        }

        if ($params['legal_cert_id']) {
            $preg_card = '/^[1-9]\d{5}(19|20)\d{2}[01]\d[0123]\d\d{3}[X\d]$/';
            if (!preg_match($preg_card, $params['legal_cert_id'])) {
                throw new ResourceException('法人身份证号码格式错误');
            }
        }

        $bankCodeService = new BankCodeService();
        if ($bankCodeService->count(['bank_code' => $params['bank_code']]) <= 0) {
            throw new ResourceException('请选择正确的结算银行卡所属银行');
        }

        if (isset($params['confirm_letter_file']) && $params['confirm_letter_file'] && $params['confirm_letter_file']->getSize() >= $maxFileSize) {
            throw new ResourceException('附件不能超过8M');
        }

        // $filter = [
        //     'cert_id' => $params['social_credit_code'],
        //     'company_id' => $params['company_id'],
        //     'pid' => 0,
        // ];
        // $memberService = new MemberService();
        // $rsMember = $memberService->getInfo($filter);
        // if ($rsMember) {
        //     if ($isCreate) {
        //         throw new ResourceException('营业执照号不能重复');
        //     }
        //     if (isset($params['member_id']) && $rsMember['id'] != $params['member_id']) {
        //         throw new ResourceException('营业执照号已被注册');
        //     }
        // }

        return $params;
    }

    //获取唯一值来比对是否有变更
    public function getUniqueKey($params = [])
    {
        $checkFields = [
            'name', 'prov_code', 'area_code', 'social_credit_code_expires', 'business_scope',
            'legal_person', 'legal_cert_id', 'legal_cert_id_expires', 'legal_mp', 'address',
            'zip_code', 'telphone', 'email', 'bank_code', 'bank_acct_type', 'card_no', 'card_name'];
        $uniqueKey = '';
        foreach ($checkFields as $key) {
            $uniqueKey .= $key . '=' . ($params[$key] ?? '');
        }
        return $uniqueKey;
    }

    /**
     * 处理前端的日期格式
     * @param string $str Y-m-d
     * @return string YYYYMMDD, 例如：20190909
     */
    private function _formatDate($str = '')
    {
        $str = str_replace('-', '', $str);
        return $str;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapayCorpMemberRepository->$method(...$parameters);
    }
}
