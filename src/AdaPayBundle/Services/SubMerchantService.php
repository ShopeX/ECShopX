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

use AdaPayBundle\Services\Request\Request;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Entities\AdapayEntryApply;
use DistributionBundle\Services\DistributorService;
use GuzzleHttp\Client as Client;
use PromotionsBundle\Services\SmsManagerService;
use OrdersBundle\Services\LocalDeliveryService;
use PaymentBundle\Services\Payments\AdaPaymentService;
use AdaPayBundle\Services\CallBack\CorpMemberUpdate;

class SubMerchantService
{
    public const AUDIT_WAIT = 'A';//待审核
    public const AUDIT_FAIL = 'B';//审核失败
    public const AUDIT_MEMBER_FAIL = 'C';//开户失败
    public const AUDIT_ACCOUNT_FAIL = 'D';//开户成功但未创建结算账户
    public const AUDIT_SUCCESS = 'E';//开户和创建结算账户成功

    private $key = 'draw_limit';
    public $adapayEntryApplyRepository;

    private $keyList = 'draw_limit_list';
    private $keyAutoConfig = 'draw_limit_config';
    private $smsKey = 'last_is_sms';

    public function __construct()
    {
        $this->adapayEntryApplyRepository = app('registry')->getManager('default')->getRepository(AdapayEntryApply::class);
    }

    public function subApproveListsService($companyId, $params, $page, $pageSize)
    {
        $filter = [
            'company_id' => $companyId
        ];
        if ($params['status'] ?? []) {
            $filter['status'] = $params['status'];
        }

        if ($params['user_name'] ?? []) {
            $filter['user_name|like'] = $params['user_name'];
        }

        if ($params['address'] ?? []) {
            $filter['address'] = $params['address'];
        }

        if ($params['time_start'] ?? []) {
            $filter['create_time|gte'] = $params['time_start'];
            $filter['create_time|lte'] = $params['time_end'] + 86399;
        }

        return $this->adapayEntryApplyRepository->lists($filter, '*', $page, $pageSize, ['create_time' => 'DESC']);
    }

    public function subApproveInfoService($companyId, $id)
    {
        $entryApplyInfo = $this->adapayEntryApplyRepository->getInfo(['id' => $id, 'company_id' => $companyId]);
        $rs['entry_apply_info'] = $entryApplyInfo;
        $memberService = new MemberService();
        $entryInfo = $memberService->getInfo(['company_id' => $companyId, 'id' => $entryApplyInfo['entry_id']]);
        if (!$entryInfo) {
            throw new ResourceException('没有开户详情');
        } else {
            $operatorId = $entryInfo['operator_id'] ?? 0;//对应店铺ID 或 经销商ID
        }

        $settleAccountService = new SettleAccountService();
        $rsAccount = $settleAccountService->getInfo(['company_id' => $companyId, 'member_id' => $entryInfo['id']]);
        if ($rsAccount) {
            $entryInfo['bank_card_id'] = $rsAccount['card_id'];
            $entryInfo['bank_card_name'] = $rsAccount['card_name'];
            $entryInfo['bank_cert_id'] = $rsAccount['cert_id'];
            $entryInfo['bank_tel_no'] = $rsAccount['tel_no'];
            $entryInfo['bank_name'] = $rsAccount['bank_name'];
        }

        if ($entryInfo['member_type'] == 'corp') {
            $corpMemberService = new CorpMemberService();
            $corpMemberInfo = $corpMemberService->getInfo(['member_id' => $entryInfo['id']]);
            $regionService = new RegionService();
            $prov = $regionService->getAreaName($corpMemberInfo['prov_code']);
            $area = $regionService->getAreaName($corpMemberInfo['area_code']);
            $entryInfo['area'] = $prov . '-' . $area;
            $entryInfo = array_merge($entryInfo, $corpMemberInfo);
            $entryInfo['operator_id'] = $operatorId;//防止店铺ID被 $corpMemberInfo 错误覆盖
        }

        $isRelDealer = false;
        $rs['entry_info'] = $entryInfo;
        if ($entryApplyInfo['apply_type'] == 'dealer') {
            $result = null;
            $filter = [
                'company_id' => $companyId,
                'operator_id' => $entryInfo['operator_id'],
            ];
            $operatorsService = new OperatorsService();
            $operatorsInfo = $operatorsService->getInfo($filter);
            $dealerInfo = [
                'operator_id' => $operatorsInfo['operator_id'],
                'mobile' => $operatorsInfo['mobile'],
                'username' => $operatorsInfo['username'],
                'head_portrait' => $operatorsInfo['head_portrait'],
                'split_ledger_info' => $operatorsInfo['split_ledger_info'],
            ];
        } elseif ($entryApplyInfo['apply_type'] == 'distributor') {
            $filter = [
                'company_id' => $companyId,
                'distributor_id' => $entryInfo['operator_id'],
            ];
            $distributorService = new DistributorService();
            $result = $distributorService->getInfo($filter);
            $localDeliveryService = new LocalDeliveryService();
            $businessList = $localDeliveryService->getShopService()->getBusinessList();
            $result['business_list'] = $businessList;
            $localDeliveryConfig = $localDeliveryService->getConfigService()->getInfo(['company_id' => $filter['company_id']]);
            $result['is_local_delivery'] = $localDeliveryConfig['is_open'] ?? false;
            $result['regionauth_id'] = empty($result['regionauth_id']) ? '' : $result['regionauth_id'];

            $latlng = $result['lat'] . ',' . $result['lng'];
            $result['qqmapimg'] = 'http://apis.map.qq.com/ws/staticmap/v2/?'
                . 'key=' . config('common.qqmap_key')
                . '&size=500x249'
                . '&zoom=16'
                . '&center=' . $latlng
                . '&markers=color:blue|label:A|' . $latlng;

            if ($result['dealer_id'] != 0) {
                $isRelDealer = true;
                $operatorsService = new OperatorsService();
                $operatorsInfo = $operatorsService->getInfo(['company_id' => $companyId, 'operator_id' => $result['dealer_id']]);
                $result['dealer_info'] = [
                    'operator_id' => $operatorsInfo['operator_id'],
                    'mobile' => $operatorsInfo['mobile'],
                    'username' => $operatorsInfo['username'],
                    'head_portrait' => $operatorsInfo['head_portrait'],
                    'split_ledger_info' => $operatorsInfo['split_ledger_info'],
                ];
                $dealerInfo = $result['dealer_info'];
            } else {
                $result['dealer_info'] = null;
            }
        }
        $rs['headquarters_adapay_fee_mode'] = 'to do..';
        $rs['distributor_info'] = $result;
        $rs['is_rel_dealer'] = $isRelDealer;
        $rs['dealer_info'] = $dealerInfo ?? null;
        $rs['last_is_sms'] = $this->getLastIsSms($companyId);

        return $rs;
    }

    public function saveSplitLedgerService($companyId, $params)
    {
        $splitLedgerInfo = json_decode($params['split_ledger_info'], true);

        if ($splitLedgerInfo['dealer_proportion']) {
            if ($splitLedgerInfo['headquarters_proportion'] + $splitLedgerInfo['dealer_proportion'] > 100) {
                throw new ResourceException('分账占比合必须小于等于100%');
            }
        } else {
            if ($splitLedgerInfo['headquarters_proportion'] > 100) {
                throw new ResourceException('分账占比必须小于等于100%');
            }
        }

        // 获取申请记录
        $res = $this->adapayEntryApplyRepository->getInfo(['company_id' => $companyId, 'id' => $params['id']]);
        if (empty($res)) {
            throw new ResourceException('未找到该申请记录');
        }

        // 账户信息
        $memberService = new MemberService();
        $memberInfo = $memberService->getInfo(['company_id' => $companyId, 'id' => $res['entry_id']]);

        // 结算信息
        $settleAccountService = new SettleAccountService();
        $settleAccount = $settleAccountService->getInfo(['company_id' => $companyId, 'member_id' => $memberInfo['id']]);

        $operatorsService = new OperatorsService();
        if ($params['status'] == 'APPROVED') {
            // $openAccountService = new OpenAccountService();
            // $merchantEntryInfo = $openAccountService->adapayMerchantEntryRepository->getInfo(['company_id' => $companyId]);
            // $appIdList = json_decode($merchantEntryInfo['app_id_list'], true);
            // $appId = $appIdList[0]['app_id'];
            $service = new AdaPaymentService();
            $setting = $service->getPaymentSetting($companyId);
            $appId = $setting['app_id'];

            // 有结算ID 说明是修改操作，修改操作的话就先删除结算账户
            $isUpdateAction = !empty($settleAccount['settle_account_id']);
            // 是否更新账户信息，1.修改操作 2.开户成功但未创建结算账户 都只要更新账户，不用重新创建账户
            $isUpdateMember = $memberInfo['is_created'];

            if ($memberInfo['member_type'] == 'person') {
                //提交子商户个人开户
                $savePersonResult = $this->savePersonMember($companyId, $appId, $memberInfo, $isUpdateMember);

                if ($savePersonResult['data']['status'] == 'succeeded') {
                    $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['is_created' => 1]);
                    // 已有结算账户修改，需删除重建
                    $settleAccountAble = true;

                    if ($isUpdateAction) {
                        $deleteSettleResult = $this->deleteSettleAccount($companyId, $appId, $settleAccount['settle_account_id'], $memberInfo);
                        if ($deleteSettleResult['data']['status'] == 'failed' && isset($deleteSettleResult['data']['error_code']) && $deleteSettleResult['data']['error_code'] != 'account_not_exists') {
                            $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_ACCOUNT_FAIL, 'audit_desc' => $deleteSettleResult['data']['error_msg']]);
                            $settleAccountAble = false;
                        }
                    }

                    if ($settleAccountAble) {
                        $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_ACCOUNT_FAIL]);
                        $createSettleResult = $this->createSettleAccount($companyId, $appId, $memberInfo['id']);

                        if ($createSettleResult['data']['status'] == 'succeeded') {
                            $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_SUCCESS, 'audit_desc' => '']);
                            $settleAccountService->updateBy(['company_id' => $companyId, 'member_id' => $memberInfo['id']], ['settle_account_id' => $createSettleResult['data']['id']]);
                            if ($params['apply_type'] == 'dealer') {
                                $operatorsService->updateOneBy(['operator_id' => $params['save_id'], 'company_id' => $companyId], ['adapay_open_account_time' => time()]);
                            }
                        } else {
                            $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_ACCOUNT_FAIL, 'audit_desc' => $createSettleResult['data']['error_msg']]);
                        }
                    }
                } else {
                    $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_MEMBER_FAIL, 'audit_desc' => $savePersonResult['data']['error_msg']]);
                }
            } elseif ($memberInfo['member_type'] == 'corp') {
                //提交子商户企业开户
                $saveCorpMemberResult = $this->saveCorpMember($companyId, $appId, $memberInfo, $isUpdateMember);

                if ($saveCorpMemberResult['data']['status'] == 'failed') {
                    $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_FAIL, 'audit_desc' => $saveCorpMemberResult['data']['error_msg']]);
                    $corpMemberService = new CorpMemberService();
                    $corpMemberService->updateBy(['company_id' => $companyId, 'member_id' => $memberInfo['id']], ['audit_state' => self::AUDIT_FAIL, 'audit_desc' => $saveCorpMemberResult['data']['error_msg']]);
                } else {
                    $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_WAIT]);
                    $corpMemberService = new CorpMemberService();
                    $corpMemberService->updateBy(['company_id' => $companyId, 'member_id' => $memberInfo['id']], ['audit_state' => self::AUDIT_WAIT]);
                    if ($isUpdateMember) {
                        $callbackData = $saveCorpMemberResult['data'];
                        $callbackData['audit_state'] = CorpMemberUpdate::AUDIT_UPDATE_SUCCESS;
                        $callback = new CorpMemberUpdate();
                        $callback->succeeded($callbackData);
                    }
                }
            }

            //存储分账信息
            if ($params['apply_type'] == 'dealer') {
                $operatorsService->updateOneBy(['operator_id' => $params['save_id'], 'company_id' => $companyId], ['split_ledger_info' => $params['split_ledger_info']]);
            } elseif ($params['apply_type'] == 'distributor') {
                $distributorService = new DistributorService();
                $distributorService->updateBy(['distributor_id' => $params['save_id'], 'company_id' => $companyId], ['split_ledger_info' => $params['split_ledger_info']]);
            }
        } else {
            //云店审批不通过
            $memberService->updateBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['audit_state' => self::AUDIT_FAIL, 'audit_desc' => $params['comments']]);
            if ($memberInfo['member_type'] == 'crop') {
                $corpMemberService = new CorpMemberService();
                $corpMemberService->updateBy(['company_id' => $companyId, 'member_id' => $memberInfo['id']], ['audit_state' => self::AUDIT_FAIL, 'audit_desc' => $params['comments']]);
            }
        }

        //更新子商户申请记录表
        $filter = [
            'company_id' => $companyId,
            'id' => $params['id'],
        ];
        $data = [
            'status' => $params['status'],
            'comments' => $params['comments'],
            'is_sms' => $params['is_sms'],
        ];

        $this->adapayEntryApplyRepository->updateOneBy($filter, $data);
        $newMemberInfo = $memberService->updateOneBy(['company_id' => $companyId, 'id' => $memberInfo['id']], ['is_sms' => $params['is_sms']]);

        if ($params['is_sms'] && $newMemberInfo['audit_state'] != self::AUDIT_WAIT) {
            try {
                $data = ['mer_name' => $newMemberInfo['user_name']];
                $smsManagerService = new SmsManagerService($companyId);
                $smsManagerService->send($newMemberInfo['tel_no'], $companyId, 'sub_account_approved', $data);
            } catch (\Exception $e) {}
        }

        $this->setLastIsSms($companyId, $params['is_sms']);

        // 分店还是经销商 取到对应的名称
        if (in_array($memberInfo['operator_type'], ['distributor', 'dealer'])) {
            $name = '';
            if ($memberInfo['operator_type'] == 'distributor') {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $params['save_id']]);
                $name = $distributorInfo['name'];
                $relId = $params['save_id'];
            } elseif ($memberInfo['operator_type'] == 'dealer') {
                $operatorInfo = (new OperatorsService())->getInfo(['company_id' => $companyId, 'operator_id' => $memberInfo['operator_id']]);
                $name = $operatorInfo['username'];

                if (isset($operatorInfo['is_dealer_main']) && !$operatorInfo['is_dealer_main']) {
                    $relId = $operatorInfo['dealer_parent_id'];
                } else {
                    $relId = $operatorInfo['operator_id'];
                }
            }

            $adapayLogService = new AdapayLogService();
            // 主商户一条
            $logParams = [
                'company_id' => $companyId,
                'status' => $params['status'],
                'name' => $name,
                'is_sms' => $params['is_sms']
            ];
            $merchantRelId = app('auth')->user()->get('operator_id');

            $adapayLogService->logRecord($logParams, $merchantRelId, 'sub_approve/save_split_ledger', 'merchant');
            $adapayLogService->logRecord($logParams, $relId, 'sub_approve/save_split_ledger', $memberInfo['operator_type']);
        }


        return ['status' => true];
    }

    public function setLastIsSms($companyId, $isSms)
    {
        $redisKey = $this->smsKey . sha1($companyId);
        return app('redis')->set($redisKey, $isSms);
    }

    public function getLastIsSms($companyId)
    {
        $redisKey = $this->smsKey . sha1($companyId);
        return app('redis')->get($redisKey);
    }

    /**
     * 保存个人账户
     *
     * @param int $companyId
     * @param string $appId
     * @param array $memberInfo
     * @param bool $isUpdate
     * @return array|mixed
     */
    public function savePersonMember(int $companyId, string $appId, array $memberInfo, bool $isUpdate)
    {
        $personData = [
            'company_id' => $companyId,
            'app_id' => $appId,
            'adapay_func_code' => 'members.realname',
            'member_id' => $memberInfo['id'],
            'tel_no' => $memberInfo['tel_no'],
            'user_name' => $memberInfo['user_name'],
            'cert_type' => '00',
            'cert_id' => $memberInfo['cert_id'],
        ];
        $personData['api_method'] = $isUpdate ? 'Member.update' : 'Member.create';
        return (new Request())->call($personData);
    }

    /**
     * 保存企业账户
     *
     * @param  $companyId
     * @param  $appId
     * @param  $memberInfo
     * @param  $isUpdate
     * @param  $autoCreateSettle
     * @return array|mixed
     */
    public function saveCorpMember($companyId, $appId, $memberInfo, $isUpdate, $autoCreateSettle = true)
    {
        $corpMemberService = new CorpMemberService();
        $corpMemberInfo = $corpMemberService->getInfo(['member_id' => $memberInfo['id']]);
        unset($corpMemberInfo['id']);
        $memberInfo = array_merge($memberInfo, $corpMemberInfo);

        $url = false;
        $memberInfo['attach_file'] = $memberInfo['attach_file'] ?? '';//附件可以为空
        if ($memberInfo['attach_file']) {
            $url = $this->getFilePath($memberInfo['attach_file']);
        }

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
        ];

        if ($url) {
            $corpData['attach_file'] = $url;
            $corpData['file_content'] = file_get_contents($url);
        }

        if (!$isUpdate && $autoCreateSettle) {
            $corpData['bank_code'] = $memberInfo['bank_code']; // 银行代码
            $corpData['bank_acct_type'] = $memberInfo['bank_acct_type']; // 银行账户类型
            $corpData['card_no'] = $memberInfo['card_no']; // 银行卡号
            $corpData['card_name'] = $memberInfo['card_name']; // 银行卡对应的户名
        }

        $corpData['api_method'] = $isUpdate ? 'CorpMember.update' : 'CorpMember.create';

        return (new Request())->call($corpData);
    }

    /**
     * 删除结算账户
     *
     * @param  $companyId
     * @param  $appId
     * @param  $settleId
     * @param  $memberInfo
     * @return array|mixed
     */
    public function deleteSettleAccount($companyId, $appId, $settleId, $memberInfo)
    {
        $settleData = [
            'company_id' => $companyId,
            'app_id' => $appId,
            'member_id' => $memberInfo['id'],
            'settle_account_id' => $settleId,
            'api_method' => 'SettleAccount.delete'
        ];
        return (new Request())->call($settleData);
    }

    /**
     * 创建结算账户
     *
     * @param  $companyId
     * @param  $appId
     * @param  $memberId
     * @return array|mixed
     */
    public function createSettleAccount($companyId, $appId, $memberId)
    {
        $request = new Request();

        $settleAccountService = new SettleAccountService();
        $accountInfo = $settleAccountService->getInfo(['company_id' => $companyId, 'member_id' => $memberId]);

        $settleData = [
            'company_id' => $companyId,
            'app_id' => $appId,
            'member_id' => $memberId,
            'channel' => 'bank_account',
            'account_info' => [
                'card_id' => $accountInfo['card_id'],
                'card_name' => $accountInfo['card_name'],
                'cert_id' => $accountInfo['cert_id'],
                'cert_type' => $accountInfo['cert_type'] ?? '00',
                'tel_no' => $accountInfo['tel_no'],
                'bank_code' => $accountInfo['bank_code'] ?? '',
                'bank_name' => $accountInfo['bank_name'] ?? '',
                'bank_acct_type' => $accountInfo['bank_acct_type'],//银行账户类型：1-对公；2-对私
                'prov_code' => $accountInfo['prov_code'],
                'area_code' => $accountInfo['area_code']
            ],
            'api_method' => 'SettleAccount.create'
        ];
        return $request->call($settleData);
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
        //        if (strtolower(substr($url, 0, 4)) != 'http') {
        //            $url = storage_path('uploads') . '/' . $filePath;
        //            $content = file_get_contents($url);
        //        } else {
        //            $client = new Client();
        //            $content = $client->get($url)->getBody()->getContents();
        //        }
        //
        //        $tmpTarget = tempnam('/tmp', 'import-file');
        //        file_put_contents($tmpTarget, $content);
        //        return $tmpTarget;
    }


    public function setDrawLimit($companyId, $limit)
    {
        $key = $this->key . sha1($companyId);
        $data = [
            'draw_limit' => bcmul($limit, 100)
        ];
        app('redis')->set($key, json_encode($data));

        return ['status' => true];
    }

    //批量设置商户的暂冻金额
    public function setDrawLimitList($companyId, $draw_limit_list = [])
    {
        $limitData = [];
        foreach ($draw_limit_list as $v) {
            $draw_limit = $v['draw_limit'] ?? 0;
            if (!$v['id'] or !$draw_limit) {
                throw new ResourceException('暂冻金额设置错误!');
            }
            $limitData[$v['id']] = bcmul(strval($draw_limit), 100);
        }
        $key = $this->keyList . sha1($companyId);
        app('redis')->set($key, json_encode($limitData));

        return ['status' => true];
    }

    public function setAutoCashConfig($companyId, $config = [])
    {
        $key = $this->keyAutoConfig . sha1($companyId);
        app('redis')->set($key, json_encode($config, 256));
        return ['status' => true];
    }

    public function getAutoCashConfig($companyId)
    {
        $key = $this->keyAutoConfig . sha1($companyId);
        $result = app('redis')->get($key);
        if (!$result) {
            return [];
        }

        return json_decode($result, true);
    }

    public function getDrawLimit($companyId)
    {
        $key = $this->key . sha1($companyId);
        $result = app('redis')->get($key);
        if (!$result) {
            return [];
        }

        return json_decode($result, true);
    }

    public function getDrawLimitList($companyId, $rawData = false)
    {
        $limitData = [];
        $key = $this->keyList . sha1($companyId);
        $result = app('redis')->get($key);
        if (!$result) {
            return [];
        }

        $result = json_decode($result, true);
        if ($rawData) {
            return $result;
        }

        //获取商户的名称和详细地址
        $memberIds = array_keys($result);
        if (!$memberIds) {
            return [];//被清空了
        }

        //企业用户
        $filter = ['member_id' => $memberIds];
        $corpMemberService = new CorpMemberService();
        $rs = $corpMemberService->getLists($filter);
        $corpMemberInfo = array_column($rs, null, 'member_id');

        //个人用户和企业用户
        $filter = ['id' => $memberIds];
        $memberService = new MemberService();
        $rs = $memberService->getLists($filter);
        foreach ($rs as $v) {
            $merchantInfo = [
                'id' => $v['id'],
                'member_id' => $v['id'],
                'user_name' => $v['user_name'],
                'merchant_name' => $v['user_name'],
                'location' => $v['location'],
                'contact_name' => $v['user_name'],
                'draw_limit' => bcdiv($result[$v['id']], 100, 2),
            ];

            if ($v['member_type'] == 'corp' && isset($corpMemberInfo[$v['id']])) {
                $merchantInfo['merchant_name'] = $corpMemberInfo[$v['id']]['name'];
                $merchantInfo['contact_name'] = $corpMemberInfo[$v['id']]['legal_person'];
            }

            $limitData[] = $merchantInfo;
        }

        return $limitData;
    }
}
