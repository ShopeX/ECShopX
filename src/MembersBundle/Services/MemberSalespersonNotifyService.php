<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use DistributionBundle\Services\DistributorService;
use MembersBundle\Services\WechatUserService;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;

/**
 * 会员导购通知服务
 * 用于通知导购端进行会员解绑等操作
 */
class MemberSalespersonNotifyService
{
    /**
     * 通知导购端批量解绑会员
     * 
     * @param int $companyId 企业ID
     * @param array $userIds 会员ID数组
     * @return array 返回结果，包含成功和失败的统计信息
     */
    public function notifyUnbindMembers(int $companyId, array $userIds): array
    {
        if (empty($userIds)) {
            return [
                'success' => true,
                'total_count' => 0,
                'success_count' => 0,
                'fail_count' => 0,
                'results' => [],
            ];
        }

        // 获取会员信息，包括 op_distributor 和 unionid
        $membersRepository = app('registry')->getManager('default')->getRepository(\MembersBundle\Entities\Members::class);
        $members = $membersRepository->getList([
            'company_id' => $companyId,
            'user_id' => $userIds,
        ], 'user_id,op_distributor');

        if (empty($members)) {
            return [
                'success' => true,
                'total_count' => 0,
                'success_count' => 0,
                'fail_count' => 0,
                'results' => [],
            ];
        }

        // 获取会员的 unionid
        $wechatUserService = new WechatUserService();
        $memberUnionids = [];
        foreach ($members['list'] as $member) {
            $unionid = $wechatUserService->getUnionidByUserId($member['user_id'], $companyId);
            if ($unionid) {
                $memberUnionids[$member['user_id']] = [
                    'unionid' => $unionid,
                    'op_distributor' => $member['op_distributor'] ?? 0,
                ];
            }
        }

        if (empty($memberUnionids)) {
            app('log')->info('通知导购端解绑会员：没有找到有效的unionid', [
                'company_id' => $companyId,
                'user_ids' => $userIds,
            ]);
            return [
                'success' => true,
                'total_count' => count($userIds),
                'success_count' => 0,
                'fail_count' => 0,
                'results' => [],
            ];
        }

        // 按照 op_distributor 分组
        $groupedByDistributor = [];
        foreach ($memberUnionids as $userId => $data) {
            $distributorId = $data['op_distributor'] ?: 0;
            if (!isset($groupedByDistributor[$distributorId])) {
                $groupedByDistributor[$distributorId] = [];
            }
            $groupedByDistributor[$distributorId][] = $data['unionid'];
        }

        // 使用统一的导购端接口调用方式
        $marketingCenterRequest = new MarketingCenterRequest();

        // 获取门店信息
        $distributorService = new DistributorService();
        $allResults = [];
        $totalSuccess = 0;
        $totalFail = 0;

        // 按门店分组调用接口
        foreach ($groupedByDistributor as $distributorId => $unionids) {
            if (empty($unionids)) {
                continue;
            }

            // 获取门店编号
            $storeBn = '';
            if ($distributorId > 0) {
                $distributorInfo = $distributorService->getData([
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ]);
                $storeBn = $distributorInfo['shop_code'] ?? '';
            }

            if (empty($storeBn) && $distributorId > 0) {
                app('log')->warning('通知导购端解绑会员：门店编号不存在', [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorId,
                ]);
                // 记录失败
                foreach ($unionids as $unionid) {
                    $allResults[] = [
                        'unionid' => $unionid,
                        'success' => false,
                        'error' => '门店编号不存在',
                    ];
                    $totalFail++;
                }
                continue;
            }

            // 调用导购端接口（使用统一的方式）
            $params = [
                'store_bn' => $storeBn,
                'unionids' => $unionids,
            ];
            
            $result = $marketingCenterRequest->call($companyId, 'members.unbindMembers', $params);
            
            // 处理结果
            if (!empty($result) && isset($result['errcode']) && $result['errcode'] == 0) {
                $responseData = $result['data'] ?? [];
                $responseResults = $responseData['results'] ?? [];
                
                foreach ($responseResults as $item) {
                    $allResults[] = [
                        'unionid' => $item['unionid'] ?? '',
                        'success' => $item['success'] ?? false,
                        'error' => $item['error'] ?? null,
                    ];
                    if ($item['success'] ?? false) {
                        $totalSuccess++;
                    } else {
                        $totalFail++;
                    }
                }
            } else {
                // 接口调用失败，记录所有 unionid 为失败
                $errorMsg = $result['errmsg'] ?? '接口调用失败';
                app('log')->warning('通知导购端解绑会员：接口调用失败', [
                    'company_id' => $companyId,
                    'store_bn' => $storeBn,
                    'unionids' => $unionids,
                    'result' => $result,
                ]);
                foreach ($unionids as $unionid) {
                    $allResults[] = [
                        'unionid' => $unionid,
                        'success' => false,
                        'error' => $errorMsg,
                    ];
                    $totalFail++;
                }
            }
        }

        return [
            'success' => $totalFail === 0,
            'total_count' => count($memberUnionids),
            'success_count' => $totalSuccess,
            'fail_count' => $totalFail,
            'results' => $allResults,
        ];
    }

}

