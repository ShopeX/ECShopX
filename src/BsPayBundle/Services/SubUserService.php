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

namespace BsPayBundle\Services;

use BsPayBundle\Services\Request\Request;
use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Client as Client;

use CompanysBundle\Services\OperatorsService;
use BsPayBundle\Entities\EntryApply;
use BsPayBundle\Entities\UserCard;

use BsPayBundle\Services\UserService;
use BsPayBundle\Services\UserEntService;
use BsPayBundle\Services\UserIndvService;
use BsPayBundle\Services\RegionsService;
use BsPayBundle\Services\V2\User\BasicdataEnt;
use BsPayBundle\Services\V2\User\BasicdataEntModify;
use BsPayBundle\Services\V2\User\BasicdataIndv;
use BsPayBundle\Services\V2\User\BasicdataIndvModify;
use BsPayBundle\Services\V2\User\BusiOpen;
use BsPayBundle\Services\V2\User\BusiModify;

use DistributionBundle\Services\DistributorService;
use DistributionBundle\Entities\Distributor;
use OrdersBundle\Services\CompanyRelDadaService;
use PromotionsBundle\Services\SmsManagerService;
use ThirdPartyBundle\Services\DadaCenter\ShopService;
use PaymentBundle\Services\Payments\BsPayService;

class SubUserService
{
    public const AUDIT_WAIT = 'A';//待审核
    public const AUDIT_FAIL = 'B';//审核失败
    public const AUDIT_MEMBER_FAIL = 'C';//开户失败
    public const AUDIT_ACCOUNT_FAIL = 'D';//开户成功但未创建结算账户
    public const AUDIT_SUCCESS = 'E';//开户和创建结算账户成功

    public $entryApplyRepository;
    public $userCardRepository;

    public function __construct()
    {
        $this->entryApplyRepository = app('registry')->getManager('default')->getRepository(EntryApply::class);
        $this->userCardRepository = app('registry')->getManager('default')->getRepository(UserCard::class);
    }

    public function getSubApproveLists($companyId, $params, $page, $pageSize)
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
            $filter['created|gte'] = $params['time_start'];
            $filter['created|lte'] = $params['time_end'] + 86399;
        }

        return $this->entryApplyRepository->lists($filter, '*', $page, $pageSize, ['created' => 'DESC']);
    }

    public function getSubApproveInfo($companyId, $id)
    {
        $userService = new UserService();
        $userEntryInfo = $userService->getUserInfo(['id' => $id]);
        if (!$userEntryInfo) {
            throw new ResourceException('没有开户详情');
        }
        $operatorId = $userEntryInfo['operator_id'] ?? 0;//对应店铺ID 或 经销商ID 或商户ID

        $entryApplyInfo = $this->entryApplyRepository->getInfoById($id);
        $rs['entry_apply_info'] = $entryApplyInfo;

        $userCardInfo = $this->userCardRepository->getInfo(['company_id' => $companyId, 'user_id' => $userEntryInfo['id'], 'user_type' => $entryApplyInfo['user_type']]);
        $regionService = new RegionsService();
        if ($userCardInfo) {
            $userEntryInfo['card_no'] = $userCardInfo['card_no'];
            $userEntryInfo['card_name'] = $userCardInfo['card_name'];
            $userEntryInfo['bank_cert_no'] = $userCardInfo['cert_no'];
            $userEntryInfo['mp'] = $userCardInfo['mp'];
            $userEntryInfo['card_type'] = $userCardInfo['card_type'];
            $userEntryInfo['bank_code'] = $userCardInfo['bank_code'];
            $userEntryInfo['branch_name'] = $userCardInfo['branch_name'];// 支行名称
            
            $prov = $regionService->getAreaName($userCardInfo['prov_id']);
            $area = $regionService->getAreaName($userCardInfo['area_id']);
            $userEntryInfo['card_area'] = $prov . '-' . $area;
        }
        // 企业信息处理
        if ($userEntryInfo['user_type'] == 'ent') {
            $userEntryInfo['ent_type_value'] = $userService->ent_type_options[$userEntryInfo['ent_type']];

            $prov = $regionService->getAreaName($userEntryInfo['reg_prov_id']);
            $area = $regionService->getAreaName($userEntryInfo['reg_area_id']);
            $district = $regionService->getAreaName($userEntryInfo['reg_district_id']);
            $userEntryInfo['reg_area'] = $prov . '-' . $area . '-'.$district;
        }
        $isRelDealer = $isRelMerchant = false;
        $rs['entry_info'] = $userEntryInfo;
        if ($entryApplyInfo['operator_type'] == 'merchant') {
            $result = null;
            $operatorsService = new OperatorsService();
            $operatorsInfo = $operatorsService->getInfo(['company_id' => $companyId, 'merchant_id' => $entryApplyInfo['operator_id'], 'operator_type' => 'merchant', 'is_merchant_main' => 1]);
            $merchantInfo = [
                'operator_id' => $operatorsInfo['operator_id'],
                'mobile' => $operatorsInfo['mobile'],
                'username' => $operatorsInfo['username'],
                'head_portrait' => $operatorsInfo['head_portrait'],
                // 'split_ledger_info' => $operatorsInfo['split_ledger_info'],
            ];
        } elseif ($entryApplyInfo['operator_type'] == 'dealer' or $entryApplyInfo['operator_type'] == 'supplier') {
            $result = null;
            $filter = [
                'company_id' => $companyId,
                'operator_id' => $entryApplyInfo['operator_id'],
            ];
            $operatorsService = new OperatorsService();
            $operatorsInfo = $operatorsService->getInfo($filter);
            $dealerInfo = [
                'operator_id' => $operatorsInfo['operator_id'],
                'mobile' => $operatorsInfo['mobile'],
                'username' => $operatorsInfo['username'],
                'head_portrait' => $operatorsInfo['head_portrait'],
                // 'split_ledger_info' => $operatorsInfo['split_ledger_info'],
            ];
        } elseif ($entryApplyInfo['operator_type'] == 'distributor') {
            $filter = [
                'company_id' => $companyId,
                'distributor_id' => $entryApplyInfo['operator_id'],
            ];
            $distributorService = new DistributorService();
            $result = $distributorService->getInfo($filter);
            $shopService = new ShopService();
            $businessList = $shopService->getBusinessList();
            $result['business_list'] = $businessList;
            $companyRelDadaService = new CompanyRelDadaService();
            $dadaInfo = $companyRelDadaService->getInfo(['company_id' => $filter['company_id']]);
            $result['company_dada_open'] = $dadaInfo['is_open'] ?? false;
            $result['regionauth_id'] = empty($result['regionauth_id']) ? '' : $result['regionauth_id'];

            $latlng = $result['lat'] . ',' . $result['lng'];
            $result['qqmapimg'] = 'http://apis.map.qq.com/ws/staticmap/v2/?'
                . 'key=' . config('common.qqmap_key')
                . '&size=500x249'
                . '&zoom=16'
                . '&center=' . $latlng
                . '&markers=color:blue|label:A|' . $latlng;

            if ($result['merchant_id'] != 0) {
                $isRelMerchant = true;
                $operatorsService = new OperatorsService();
                $operatorsInfo = $operatorsService->getInfo(['company_id' => $companyId, 'merchant_id' => $result['merchant_id'], 'operator_type' => 'merchant', 'is_merchant_main' => 1]);
                $result['merchant_info'] = [
                    'operator_id' => $operatorsInfo['operator_id'],
                    'mobile' => $operatorsInfo['mobile'],
                    'username' => $operatorsInfo['username'],
                    'head_portrait' => $operatorsInfo['head_portrait'],
                    // 'split_ledger_info' => $operatorsInfo['split_ledger_info'],
                ];
                $merchantInfo = $result['merchant_info'];
            } elseif ($result['dealer_id'] != 0) {
                $isRelDealer = true;
                $operatorsService = new OperatorsService();
                $operatorsInfo = $operatorsService->getInfo(['company_id' => $companyId, 'operator_id' => $result['dealer_id']]);
                $result['dealer_info'] = [
                    'operator_id' => $operatorsInfo['operator_id'],
                    'mobile' => $operatorsInfo['mobile'],
                    'username' => $operatorsInfo['username'],
                    'head_portrait' => $operatorsInfo['head_portrait'],
                    // 'split_ledger_info' => $operatorsInfo['split_ledger_info'],
                ];
                $dealerInfo = $result['dealer_info'];
            } else {
                $result['dealer_info'] = null;
            }
        }
        // $rs['headquarters_adapay_fee_mode'] = 'to do..';
        $rs['distributor_info'] = $result;
        $rs['is_rel_dealer'] = $isRelDealer;
        $rs['is_rel_merchant'] = $isRelMerchant;
        $rs['dealer_info'] = $dealerInfo ?? null;
        $rs['merchant_info'] = $merchantInfo ?? null;
        // $rs['last_is_sms'] = $this->getLastIsSms($companyId);

        return $rs;
    }

    public function saveAudit($companyId, $params)
    {
        $splitLedgerInfo = json_decode($params['split_ledger_info'], true);

        if ($splitLedgerInfo['dealer_proportion']) {
            if ($splitLedgerInfo['headquarters_proportion'] + $splitLedgerInfo['dealer_proportion'] > 100) {
                throw new ResourceException('分账占比合必须小于等于100%');
            }
        } elseif ($splitLedgerInfo['merchant_proportion']) {
            if ($splitLedgerInfo['headquarters_proportion'] + $splitLedgerInfo['merchant_proportion'] > 100) {
                throw new ResourceException('分账占比合必须小于等于100%');
            }
        } else {
            if ($splitLedgerInfo['headquarters_proportion'] > 100) {
                throw new ResourceException('分账占比必须小于等于100%');
            }
        }

        // 用户信息
        $userService = new UserService();
        $userInfo = $userService->getUserInfo(['company_id' => $companyId, 'id' => $params['id']]);
        if (empty($userInfo)) {
            throw new ResourceException('未找到用户进件信息');
        }
        
        // 结算信息
        $userCardInfo = $this->userCardRepository->getInfo(['company_id' => $companyId, 'user_id' => $userInfo['id']]);

        switch ($userInfo['user_type']) {
            case 'ent':
                $service = new UserEntService();
                if ($userInfo['is_update'] == 1) {
                    $basicdataService = new BasicdataEntModify($companyId);
                } else {
                    $basicdataService = new BasicdataEnt($companyId);
                }
                break;
            case 'indv':
                $service = new UserIndvService();
                if ($userInfo['is_update'] == 1) {
                    $basicdataService = new BasicdataIndvModify($companyId);
                } else {
                    $basicdataService = new BasicdataIndv($companyId);
                }
                break;
            
            default:
                throw new ResourceException('用户类型错误！');
                break;
        }
        $operatorsService = new OperatorsService();
        if ($params['status'] == 'APPROVED') {
            //存储分账信息
            if ($params['operator_type'] == 'distributor') {
                $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                $distributorRepository->updateBy(['distributor_id' => $params['save_id'], 'company_id' => $companyId], ['bspay_split_ledger_info' => $params['split_ledger_info']]);
            }
            
            //提交子商户企业开户
            $userInfo['req_seq_id'] = date("YmdHis").mt_rand();
            $apiRes = $basicdataService->handle($userInfo);
            # 成功/失败应答的处理
            $apply_update_filter = [
                'company_id' => $userInfo['company_id'],
                'id' => $userInfo['id'],
            ];
            $update_data = [
                'req_seq_id' => $userInfo['req_seq_id']
            ];
            if (!$apiRes || $apiRes->isError()) {
                $result = $apiRes->getErrorInfo();
                $update_data['audit_desc'] = $result['msg'];
                $update_data['audit_state'] = $userService::AUDIT_FAIL;
            } else {
                $result = $apiRes->getRspDatas();
                if ($result['data']['resp_code'] == '00000000') {
                    $update_data['audit_state'] = $userService::AUDIT_CARD_FAIL;
                    $update_data['huifu_id'] = $result['data']['huifu_id'];
                    $update_data['is_update'] = 1;
                    $userCardInfo['huifu_id'] = $update_data['huifu_id'];
                } else {
                    $update_data['audit_state'] = $userService::AUDIT_FAIL;
                    $update_data['audit_desc'] = $result['data']['resp_desc'];
                }
                
            }
            $service->updateBy($apply_update_filter, $update_data);
            // 用户开户成功后，用户业务入驻
            if (!empty($update_data['huifu_id'])) {
                $bsPayService = new BsPayService();
                $setting = $bsPayService->getPaymentSetting($companyId);
                // $userCardInfo['upper_huifu_id'] = $setting['upper_huifu_id'];
                $userCardInfo['upper_huifu_id'] = $setting['sys_id'];
                if ($userCardInfo['audit_state'] == $userService::CARD_SUCCESS) {
                    $busiService = new BusiModify($companyId);
                } else {
                    $busiService = new BusiOpen($companyId);
                }
                $userCardInfo['req_seq_id'] = date("YmdHis").mt_rand();
                $busiApiRes = $busiService->handle($userCardInfo);
                # 成功/失败应答的处理
                $update_filter = [
                    'company_id' => $userInfo['company_id'],
                    'user_id' => $userInfo['id'],
                ];
                $update_data = [
                    'req_seq_id' => $userCardInfo['req_seq_id'],
                    'huifu_id' => $update_data['huifu_id'],
                ];
                $apply_update_data = [];
                if (!$busiApiRes || $busiApiRes->isError()) {
                    $result = $busiApiRes->getErrorInfo();
                    $update_data['audit_desc'] = $result['data']['resp_desc'];
                    $update_data['audit_state'] = $userService::CARD_FAIL;
                } else {
                    $result = $busiApiRes->getRspDatas();
                    app('log')->info('saveAudit result====>'.json_encode($result));
                    if ($result['data']['resp_code'] == '00000000') {
                        $update_data['apply_no'] = $result['data']['token_no'];
                        $resp_business = json_decode($result['data']['resp_business'], 1);
                        foreach ($resp_business as $business) {
                            if ($business['type'] == '1') {
                                if ($business['code'] == $userService::BUSINESS_SUCC) {
                                    $update_data['audit_state'] = $userService::CARD_SUCCESS;
                                    $apply_update_data['audit_state'] = $userService::AUDIT_SUCCESS;
                                } else {
                                    $update_data['audit_desc'] = $business['msg'];
                                    $update_data['audit_state'] = $userService::CARD_FAIL;
                                    $apply_update_data['audit_desc'] = $business['msg'];
                                }
                            }
                        }
                    } else {
                        $update_data['audit_desc'] = $result['data']['resp_desc'];
                        $update_data['audit_state'] = $userService::CARD_FAIL;
                        $apply_update_data['audit_desc'] = $result['data']['resp_desc'];
                    }
                    
                }
                app('log')->info('saveAudit update_filter====>'.json_encode($update_filter).',update_data====>'.json_encode($update_data));
                app('log')->info('saveAudit apply_update_filter====>'.json_encode($apply_update_filter).',apply_update_data====>'.json_encode($apply_update_data));
                $this->userCardRepository->updateBy($update_filter, $update_data);
                if (!empty($apply_update_data)) {
                    $service->updateBy($apply_update_filter, $apply_update_data);
                }
            }
        } else {
            //云店审批不通过
            $update_filter = [
                'company_id' => $userInfo['company_id'],
                'id' => $userInfo['id'],
            ];
            $update_data = [
                'audit_state' => $userService::AUDIT_FAIL,
                'audit_desc' => $params['comments'],
            ];
            $service->updateBy($update_filter, $update_data);
        }

        //更新子商户申请记录表
        $filter = [
            'company_id' => $companyId,
            'id' => $params['id'],
        ];
        $data = [
            'status' => $params['status'],
            'comments' => $params['comments'],
        ];
        $this->entryApplyRepository->updateOneBy($filter, $data);
        
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
