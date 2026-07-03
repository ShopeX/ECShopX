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

use Dingo\Api\Exception\ResourceException;

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
            $userEntryInfo['branch_code'] = $userCardInfo['branch_code'] ?? '';
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
        $rs['distributor_info'] = $result;
        $rs['is_rel_dealer'] = $isRelDealer;
        $rs['is_rel_merchant'] = $isRelMerchant;
        $rs['dealer_info'] = $dealerInfo ?? null;
        $rs['merchant_info'] = $merchantInfo ?? null;

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
}
