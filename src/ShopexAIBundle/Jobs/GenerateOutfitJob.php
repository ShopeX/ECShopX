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

namespace ShopexAIBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ShopexAIBundle\Services\OutfitAnyoneService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class GenerateOutfitJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 人物图片URL
     */
    protected $personImageUrl;
    
    /**
     * 上衣图片URL
     */
    protected $topGarmentUrl;
    
    /**
     * 下装图片URL
     */
    protected $bottomGarmentUrl;
    
    /**
     * 缓存键
     */
    protected $cacheKey;
    
    /**
     * 缓存有效期（秒）
     */
    protected $cacheTtl;
    
    /**
     * 公司ID
     */
    protected $companyId;
    
    /**
     * 操作员ID
     */
    protected $operatorId;
    
    /**
     * 分销商ID
     */
    protected $distributorId;
    
    /**
     * 执行超时时间（秒）
     */
    public $timeout = 300;
    
    /**
     * 最大尝试次数
     */
    public $tries = 2;

    /**
     * 创建新的任务实例
     *
     * @param string $personImageUrl 人物图片URL
     * @param string $topGarmentUrl 上衣图片URL
     * @param string $bottomGarmentUrl 下装图片URL（可选）
     * @param string $cacheKey 缓存键
     * @param int $cacheTtl 缓存有效期（秒）
     * @param int $companyId 公司ID
     * @param int $operatorId 操作员ID
     * @param int $distributorId 分销商ID
     * @return void
     */
    public function __construct(
        string $personImageUrl,
        string $topGarmentUrl,
        string $bottomGarmentUrl = '',
        string $cacheKey = '', 
        int $cacheTtl = 3600, 
        int $companyId = 0, 
        int $operatorId = 0, 
        int $distributorId = 0
    ) {
        $this->personImageUrl = $personImageUrl;
        $this->topGarmentUrl = $topGarmentUrl;
        $this->bottomGarmentUrl = $bottomGarmentUrl;
        $this->cacheKey = $cacheKey;
        $this->cacheTtl = $cacheTtl;
        $this->companyId = $companyId;
        $this->operatorId = $operatorId;
        $this->distributorId = $distributorId;
        
        // 如果没有提供缓存键，生成一个唯一的缓存键
        if (empty($this->cacheKey)) {
            $this->cacheKey = 'outfit_' . md5($personImageUrl . $topGarmentUrl . $bottomGarmentUrl . time());
        }
    }

    /**
     * 执行任务
     *
     * @param OutfitAnyoneService $outfitService
     * @return void
     */
    public function handle(OutfitAnyoneService $outfitService)
    {
        try {
            // 在队列任务处理前，确保EntityManager是新的实例
            if (app()->has('em')) {
                $em = app('em');
                // 不仅检查是否开启，还需要处理可能的异常情况
                try {
                    if (!$em->isOpen()) {
                        // 重置EntityManager
                        app()->forgetInstance('em');
                        // 重新获取
                        app('em');
                        Log::info('队列任务初始化时重置EntityManager状态');
                    } else {
                        // 即使EntityManager是开启的，也需要确保它的连接是有效的
                        $em->getConnection()->ping();
                        Log::info('队列任务初始化时EntityManager连接正常');
                    }
                } catch (\Exception $e) {
                    // 如果出现任何异常，尝试完全重置EntityManager
                    Log::warning('队列任务中检测到EntityManager异常，尝试重置', [
                        'error' => $e->getMessage()
                    ]);
                    app()->forgetInstance('em');
                    app('em');
                }
            }
            
            Log::info('开始队列处理虚拟试衣生成', [
                'cache_key' => $this->cacheKey,
                'person_image' => $this->personImageUrl,
                'has_bottom_garment' => !empty($this->bottomGarmentUrl)
            ]);
            
            // 调用服务生成虚拟试衣图片
            $result = $outfitService->generateOutfit(
                $this->personImageUrl,
                $this->topGarmentUrl,
                $this->bottomGarmentUrl
            );
            
            // 添加任务完成标记和时间戳
            $result['job_completed'] = true;
            $result['completed_at'] = Carbon::now()->toDateTimeString();
            
            // 将结果保存到缓存
            $outfitService->saveResultToCache($this->cacheKey, $result, $this->cacheTtl);
            
            Log::info('虚拟试衣生成队列任务完成', [
                'cache_key' => $this->cacheKey,
                'is_default' => $result['is_default'] ?? false,
                'model' => $result['model'] ?? '未知'
            ]);
            
            // 手动清理不再需要的大变量，帮助垃圾回收
            unset($result);
        } catch (\Exception $e) {
            // 记录错误
            Log::error('虚拟试衣生成队列任务失败', [
                'cache_key' => $this->cacheKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 存储错误信息
            $errorResult = [
                'job_completed' => true,
                'error' => true,
                'is_default' => true,
                'message' => '生成失败：' . $e->getMessage(),
                'completed_at' => Carbon::now()->toDateTimeString(),
                'url' => config('shopexai.aliyun.default_image_url', 'https://img.alicdn.com/imgextra/i4/O1CN01c26iB51CGdiWJA4L3_!!6000000000564-2-tps-818-404.png')
            ];
            
            $outfitService->saveResultToCache($this->cacheKey, $errorResult, $this->cacheTtl);
        }
    }
} 