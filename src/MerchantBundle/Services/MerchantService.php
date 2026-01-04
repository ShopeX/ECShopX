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

namespace MerchantBundle\Services;

use Dingo\Api\Exception\ResourceException;

use MerchantBundle\Entities\Merchant;
use MerchantBundle\Jobs\MerchantAuditSuccessNotice;
use MerchantBundle\Jobs\MerchantAuditFailNotice;
use MerchantBundle\Jobs\MerchantEnterSuccessNotice;

use CompanysBundle\Services\OperatorsService;

class MerchantService
{
    /**
     * @var \MerchantBundle\Repositories\MerchantRepository
     */
    public $merchantRepository;


    public function __construct()
    {
        $this->merchantRepository = app('registry')->getManager('default')->getRepository(Merchant::class);
    }

    /**
     * 根据商户ID，获取商户详情数据
     * @param  string $accountId 商户ID
     * @return array            商户详情数据
     */
    public function getMerchantDetail($accountId)
    {
        $info = $this->merchantRepository->getInfoById($accountId);
        if (!$info) {
            throw new ResourceException(trans('MerchantBundle.merchant_data_query_failed'));
        }
        //格式化数字，防止前端显示错误
        $info['merchant_type_id'] = intval($info['merchant_type_id']);        
        
        $settingService = new MerchantSettingService();
        $typeName = $settingService->getTypeNameById($info['company_id'], $info['merchant_type_id']);
        return array_merge($info, $typeName);
    }

    /**
     * 商户列表，数据脱敏
     * @param  array $result        商户列表数据
     * @param  boolean $datapassBlock 是否需要脱敏
     * @return array                商户列表数据
     */
    public function merchantListDataMasking($result, $datapassBlock)
    {
        if (!$datapassBlock) {
            return $result;
        }
        foreach ($result['list'] as $key => $list) {
            $list['legal_name'] = data_masking('truename', (string) $list['legal_name']);
            $list['legal_mobile'] = data_masking('mobile', (string) $list['legal_mobile']);
            $result['list'][$key] = $list;
        }
        return $result;
    }

    /**
     * 商户详情，数据脱敏
     * @param  array $result        商户详情数据
     * @param  boolean $datapassBlock 是否需要数据脱敏
     * @return array                商户详情数据
     */
    public function merchantDetailDataMasking($result, $datapassBlock)
    {
        if (!$datapassBlock) {
            return $result;
        }
        $result['legal_cert_id'] = data_masking('idcard', (string) $result['legal_cert_id']);
        $result['legal_mobile'] = data_masking('mobile', (string) $result['legal_mobile']);
        $result['card_id_mask'] = data_masking('bankcard', (string) $result['card_id_mask']);
        $result['bank_mobile'] = data_masking('mobile', (string) $result['bank_mobile']);
        $result['legal_certid_front_url'] = data_masking('image', (string) $result['legal_certid_front_url']);
        $result['legal_cert_id_back_url'] = data_masking('image', (string) $result['legal_cert_id_back_url']);
        $result['bank_card_front_url'] = data_masking('image', (string) $result['bank_card_front_url']);
        return $result;
    }

    public function createMerchant($params)
    {
        // 检查数据
        $this->__checkCreateParams($params);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建商户申请表数据
            $settlementApplyService = new MerchantSettlementApplyService();
            $applyData = $params;
            $applyData['is_agree_agreement'] = $params['settled_succ_sendsms'] == '1' ? true : false;
            $applyData['audit_status'] = MerchantSettlementApplyService::AUDIT_STATUS['succ'];
            $settlementApplyResult = $settlementApplyService->create($applyData);

            // 创建商户数据
            $params['settlement_apply_id'] = $settlementApplyResult['id'];
            $merchantResult = $this->create($params);

            // 立即发送短信（入驻成功）
            if ($params['settled_succ_sendsms'] == '1') {
                // 创建账号数据
                $operatorsService = new OperatorsService();
                $operatorsData = [
                    'company_id' => $params['company_id'],
                    'mobile' => $params['mobile'],
                    'login_name' => $params['mobile'],
                    'operator_type' => 'merchant',
                    'password' => (string)rand(100000, 999999),
                    'is_disable' => 0,
                    'is_dealer_main' => 0,
                    'is_merchant_main' => 1,
                    'merchant_id' => $merchantResult['id'],
                ];

                $operatorsService->createOperator($operatorsData);
                // 发送入驻成功短信
                $this->scheduleEnterSuccessNotice($params['company_id'], $params['mobile'], $operatorsData['password']);
            }

            $conn->commit();
            
            return [
                'mobile' => $params['mobile'],
                'password' => $operatorsData['password'] ?? '确认协议后显示',
            ];
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 审核完成（通过）
     * @param  string $companyId 企业ID
     * @param  string $mobile    接收短信的手机号
     */
    public function scheduleAuditSuccessNotice($companyId, $mobile)
    {
        $msgData = [
            'company_id' => $companyId,
            'mobile' => $mobile,
        ];
        $job = (new MerchantAuditSuccessNotice($msgData))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return true;
    }

    /**
     * 审核完成（拒绝）
     * @param  string $companyId 企业ID
     * @param  string $mobile    接收短信的手机号
     */
    public function scheduleAuditFailNotice($companyId, $mobile)
    {
        $msgData = [
            'company_id' => $companyId,
            'mobile' => $mobile,
        ];
        $job = (new MerchantAuditFailNotice($msgData))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return true;
    }

    /**
     * 入驻完成
     * @param  string $companyId   企业ID
     * @param  string $mobile   接收短信的手机号、短信内容中的手机号
     * @param  string $password 密码
     */
    public function scheduleEnterSuccessNotice($companyId, $mobile, $password)
    {
        $msgData = [
            'company_id' => $companyId,
            'mobile' => $mobile,
            'password' => $password,
        ];
        $job = (new MerchantEnterSuccessNotice($msgData))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return true;
    }

    /**
     * 创建商户，检查数据
     * @param  array $params
     */
    private function __checkCreateParams($params)
    {
        if (!ismobile($params['legal_mobile'])) {
            throw new ResourceException(trans('MerchantBundle.mobile_format_incorrect_confirm_and_retry'));
        }
        if ($params['bank_acct_type'] == 2 && $params['bank_mobile'] && !ismobile($params['bank_mobile'])) {
            throw new ResourceException(trans('MerchantBundle.bank_mobile_format_incorrect_confirm_and_retry'));
        }
        if (!ismobile($params['mobile'])) {
            throw new ResourceException(trans('MerchantBundle.account_mobile_format_incorrect_confirm_and_retry'));
        }
        // 检查商户类型
        $settingService = new MerchantSettingService();
        $settingService->__checkMerchantType($params['company_id'], $params['merchant_type_id']);
        // 验证手机号唯一
        $settlementApplyService = new MerchantSettlementApplyService();
        $settlementApplyInfo = $settlementApplyService->getInfo(['mobile' => $params['mobile']]);
        if ($settlementApplyInfo) {
            throw new ResourceException(trans('MerchantBundle.account_mobile_exists_confirm_and_retry'));
        }
        return true;
    }

    /**
     * 查询条件，去掉禁用商户关联的店铺
     * @param  array $filter 查询条件
     * @return array
     */
    public function __formateFilter($filter)
    {
        // 查询已禁用商户的店铺
        $disabledDistributorIds = $this->getDisabledDistributorIds($filter['company_id']);
        if (!$disabledDistributorIds) {
            return $filter;
        }
        if (isset($filter['distributor_id']) && $filter['distributor_id']) {
            $filterDistributorIds = is_array($filter['distributor_id']) ? $filter['distributor_id'] : [$filter['distributor_id']];
            $filter['distributor_id'] = array_diff($filterDistributorIds, $disabledDistributorIds);
            if (empty($filter['distributor_id'])) {
                $filter['distributor_id'] = -1;
            }
        } else {
            $filter['distributor_id|notIn'] = $disabledDistributorIds;
        }
        return $filter;
    }

    /**
     * 根据店铺id和关联的商户id，获取可用的店铺id
     * @param  string $companyId    企业ID
     * @param  array $merchantIds  店铺关联的商户ID
     * @param  array $validShopIds 可用的店铺id
     */
    public function getVaildDistributorByMid($companyId, $merchantIds, $validShopIds)
    {
        if (empty($validShopIds) || $merchantIds) {
            return [];
        }
        // 查询已禁用商户的店铺
        $disabledDistributorIds = $this->getDisabledDistributorIds($companyId);
        if (!$disabledDistributorIds) {
            return $validShopIds;
        }
        return array_diff($validShopIds, $disabledDistributorIds);
    }





    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->merchantRepository->$method(...$parameters);
    }
}
