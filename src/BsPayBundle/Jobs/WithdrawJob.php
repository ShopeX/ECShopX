<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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