<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Jobs;

use EspierBundle\Jobs\Job;
use ThirdPartyBundle\Services\MarketingCenter\Request;

/**
 * 异步推送会员标签关系到导购平台
 */
class PushMemberTagRelationJob extends Job
{
    private $companyId;
    private $action;
    private $relations;
    
    /**
     * 构造函数
     *
     * @param int $companyId 公司ID
     * @param string $action 操作类型：add/remove
     * @param array $relations 标签关系列表
     */
    public function __construct(int $companyId, string $action, array $relations)
    {
        $this->companyId = $companyId;
        $this->action = $action;
        $this->relations = $relations;
    }
    
    /**
     * 执行任务
     */
    public function handle()
    {
        try {
            app('log')->info('[PushMemberTagRelationJob] 开始推送', [
                'company_id' => $this->companyId,
                'action' => $this->action,
                'total_count' => count($this->relations),
            ]);
            
            // 分批推送（每批200条，避免单次请求数据过大）
            $batches = array_chunk($this->relations, 200);
            $totalBatches = count($batches);
            
            foreach ($batches as $index => $batch) {
                $params = [
                    'company_id' => (string)$this->companyId,
                    'action' => $this->action,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'relations' => $batch,
                ];
                
                app('log')->info('[PushMemberTagRelationJob] 推送批次', [
                    'company_id' => $this->companyId,
                    'batch' => $index + 1,
                    'total_batches' => $totalBatches,
                    'count' => count($batch),
                ]);
                
                $request = new Request();
                $result = $request->call(
                    $this->companyId, 
                    'members.tag.relation.sync', 
                    [$params]
                );
                
                app('log')->info('[PushMemberTagRelationJob] 批次推送完成', [
                    'company_id' => $this->companyId,
                    'batch' => $index + 1,
                    'total_batches' => $totalBatches,
                    'count' => count($batch),
                    'result' => $result,
                ]);
                
                // 避免请求过快，批次之间间隔100ms
                if ($totalBatches > 1 && $index < $totalBatches - 1) {
                    usleep(100000); // 100ms
                }
            }
            
            app('log')->info('[PushMemberTagRelationJob] 全部推送完成', [
                'company_id' => $this->companyId,
                'action' => $this->action,
                'total_count' => count($this->relations),
                'batches' => $totalBatches,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            app('log')->error('[PushMemberTagRelationJob] 推送失败', [
                'company_id' => $this->companyId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // 抛出异常，触发队列重试机制
            throw $e;
        }
    }
}

