<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Services\CallBack;

use BsPayBundle\Services\WithdrawApplyService;

class Withdraw
{
    /**
     * 处理提现回调
     * @param array $postData 回调数据
     * @param string $eventType 事件类型
     * @return array
     */
    public function handle($postData, $eventType)
    {
        app('log')->info('bspay::doWithdraw::提现回调开始::req_seq_id:'.$postData['req_seq_id']);
        
        // 1. 获取关键参数
        $reqSeqId = $postData['req_seq_id'] ?? '';  // 业务请求流水号
        
        // 2. 查询提现申请记录
        $withdrawApplyService = new WithdrawApplyService();
        $withdrawApply = $withdrawApplyService->getByReqSeqId($reqSeqId);
        if (!$withdrawApply) {
            app('log')->info('bspay::doWithdraw::提现回调未找到记录::req_seq_id:'.$reqSeqId);
            return ['success' => true];
        }
        
        // 3. 处理回调
        $withdrawApplyService->handleWithdrawNotify($postData, $withdrawApply);
        
        app('log')->info('bspay::doWithdraw::提现回调处理完成::req_seq_id:'.$reqSeqId.',apply_id:'.$withdrawApply['id']);
        return ['success' => true];
    }
} 