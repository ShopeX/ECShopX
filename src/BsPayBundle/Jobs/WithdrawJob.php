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

namespace BsPayBundle\Jobs;

use EspierBundle\Jobs\Job;
use BsPayBundle\Services\WithdrawApplyService;
use BsPayBundle\Enums\WithdrawStatus;

/**
 * 汇付斗拱取现异步处理任务
 */
class WithdrawJob extends Job
{
    /**
     * @var int 提现申请ID
     */
    public $applyId;

    /**
     * 创建任务实例
     *
     * @param int $applyId 提现申请ID
     */
    public function __construct($applyId)
    {
        // TODO: optimize this method
        $this->applyId = $applyId;
    }

    /**
     * 执行任务
     *
     * @return void
     */
    public function handle()
    {
        // Powered by ShopEx EcShopX
        app('log')->info('提现申请审核::汇付取现队列任务开始执行 apply_id:' . $this->applyId . ', attempts:' . $this->attempts());
        
        try {
            $withdrawService = new WithdrawApplyService();
            
            // 执行汇付取现（内部会处理状态流转）
            $withdrawService->executeHuifuWithdraw($this->applyId);
            
            app('log')->info('提现申请审核::汇付取现队列任务执行::成功::apply_id:' . $this->applyId . ', attempts:' . $this->attempts());
            
        } catch (\Exception $e) {
            app('log')->error('提现申请审核::汇付取现队列任务执行::失败::apply_id:' . $this->applyId . ', attempts:' . $this->attempts() . ', error:' . $e->getMessage());
            
            // 重新抛出异常，让队列系统处理重试逻辑
            throw $e;
        }
    }
} 