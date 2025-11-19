<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayMember;
use AdaPayBundle\Entities\AdapaySettleAccount;
use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Entities\Promoter;

/**
 * adapay推广员
 */
class AdapayPromoterService
{
    public const PROMOTER_OPERATOR_TYPE = 'promoter';

    public const AUDIT_VALUE = [
        'AUDIT_WAIT' => 'A',
        'AUDIT_FAIL' => 'B',
        'AUDIT_MEMBER_FAIL' => 'C',
        'AUDIT_ACCOUNT_FAIL' => 'D',
        'AUDIT_SUCCESS' => 'E',
    ];

    private $adapayMemberRepository;
    private $adapaySettleAccountRepository;
    private $promoterRepository;

    public function __construct()
    {
        $this->adapayMemberRepository = app('registry')->getManager('default')->getRepository(AdapayMember::class);
        $this->promoterRepository = app('registry')->getManager('default')->getRepository(Promoter::class);
        $this->adapaySettleAccountRepository = app('registry')->getManager('default')->getRepository(AdapaySettleAccount::class);
    }

    /**
     * 获取分销员认证信息
     *
     * @param int $companyId
     * @param int $userId
     * @param int $isDataMasking
     * @return array
     */
    public function getCertInfo(int $companyId, int $userId, $isDataMasking = 1): array
    {
        // Powered by ShopEx EcShopX
        $filter = [
            'company_id' => $companyId,
            'user_id' => $userId,
        ];

        $promoterInfoCount = $this->promoterRepository->count($filter);
        if ($promoterInfoCount == 0) {
            throw new ResourceException("非推广员不能分销员认证");
        }

        $memberFilter = [
            'operator_id' => $userId,
            'operator_type' => self::PROMOTER_OPERATOR_TYPE,
            'company_id' => $companyId,
        ];
        $memberInfo = $this->adapayMemberRepository->getInfo($memberFilter);

        // 从未认证过
        if (empty($memberInfo)) {
            return [
                'member_id' => 0,
                'tel_no' => '',
                'card_id' => '',
                'cert_id' => '',
                'card_name' => '',
                'cert_status' => []
            ];
        }

        $indexAuditValue = array_flip(self::AUDIT_VALUE);
        $auditValue = isset($memberInfo['audit_state']) ? $indexAuditValue[$memberInfo['audit_state']] : '';

        $auditStatus = [
            'audit_state' => $memberInfo['audit_state'] ?? '',  // 审核状态
            'audit_value' => $auditValue,  // 审核状态语义化值
            'audit_desc' => $memberInfo['audit_desc'] ?? '',  // 审核
            'error_info' => $memberInfo['error_info'] ?? '',  // 用户名
            'create_time' => $memberInfo['create_time'] ?? '',  // 创建时间
            'update_time' => $memberInfo['update_time'] ?? '',  // 更新时间
        ];

        $settleFilter = [
            'member_id' => $memberInfo['id'],
            'company_id' => $companyId
        ];
        $settleAccount = $this->adapaySettleAccountRepository->getInfo($settleFilter);

        return [
            'member_id' => $memberInfo['id'],
            'tel_no' => $isDataMasking ? data_masking('mobile', $settleAccount['tel_no']) : $settleAccount['tel_no'],  // 手机号
            'card_id' => $isDataMasking ? data_masking('bankcard', $settleAccount['card_id']) : $settleAccount['card_id'], // 卡号
            'cert_id' => $isDataMasking ? data_masking('idcard', $settleAccount['cert_id']) : $settleAccount['cert_id'], // 身份证
            'card_name' => $isDataMasking ? data_masking('truename', $settleAccount['card_name']) : $settleAccount['card_name'],  // 开户人姓名
            'settle_account_id' => $settleAccount['settle_account_id'],  // adapay账户id
            'cert_status' => $auditStatus
        ];
    }
}
