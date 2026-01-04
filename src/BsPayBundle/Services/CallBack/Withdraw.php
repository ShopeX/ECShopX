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