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
use ShopexAIBundle\Services\ArticleService;
use ShopexAIBundle\Services\PromptService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class GenerateArticleJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 请求数据
     */
    protected $requestData;
    
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
    public $timeout = 600;
    
    /**
     * 最大尝试次数
     */
    public $tries = 2;

    /**
     * 创建新的任务实例
     *
     * @param array $requestData 请求数据
     * @param string $cacheKey 缓存键
     * @param int $cacheTtl 缓存有效期（秒）
     * @param int $companyId 公司ID
     * @param int $operatorId 操作员ID
     * @param int $distributorId 分销商ID
     * @return void
     */
    public function __construct(
        array $requestData, 
        string $cacheKey, 
        int $cacheTtl = 60, 
        int $companyId = 0, 
        int $operatorId = 0, 
        int $distributorId = 0
    ) {
        $this->requestData = $requestData;
        $this->cacheKey = $cacheKey;
        $this->cacheTtl = $cacheTtl;
        $this->companyId = $companyId;
        $this->operatorId = $operatorId;
        $this->distributorId = $distributorId;
        
        // 确保用户信息也包含在请求数据中
        $this->requestData['company_id'] = $companyId;
        $this->requestData['operator_id'] = $operatorId;
        $this->requestData['distributor_id'] = $distributorId;
    }

    /**
     * 执行任务
     *
     * @param ArticleService $articleService
     * @param PromptService $promptService
     * @return void
     */
    public function handle(ArticleService $articleService, PromptService $promptService)
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
            
            Log::info('开始队列处理AI文章生成', [
                'cache_key' => $this->cacheKey,
                // 移除大量数据，避免内存占用
                'request_data_keys' => array_keys($this->requestData)
            ]);
            
            // 使用PromptService构建提示词
            $promptData = $promptService->buildPrompt($this->requestData);
            $prompt = $promptData['prompt'];
            $imagePrompt = $promptData['image_prompt'];
            $imagePrompts = $promptData['image_prompts'] ?? [$imagePrompt];
            $isMultiProduct = $promptData['multi_product'] ?? false;
            $productsCount = $promptData['products_count'] ?? 1;
            
            // 确保prompt是字符串类型
            if (is_array($prompt)) {
                $prompt = $prompt['text'] ?? (is_string(reset($prompt)) ? reset($prompt) : json_encode($prompt));
                Log::info('将数组格式的prompt转换为字符串', ['prompt' => $prompt]);
            }
            
            // 确保imagePrompt是正确的格式
            if (is_string($imagePrompt)) {
                $imagePrompt = [
                    'prompt' => $imagePrompt,
                    'ref_image' => ''
                ];
            }
            
            // 确保imagePrompts中的每个项也是正确的格式
            foreach ($imagePrompts as $key => $promptItem) {
                if (is_string($promptItem)) {
                    $imagePrompts[$key] = [
                        'prompt' => $promptItem,
                        'ref_image' => ''
                    ];
                }
            }
            
            // 记录任务信息
            Log::info('队列任务准备生成内容', [
                'image_prompts_count' => count($imagePrompts),
                'is_multi_product' => $isMultiProduct,
                'products_count' => $productsCount
            ]);
            
            // 使用ArticleService中的通用方法处理生成逻辑
            $result = $articleService->generateAndProcessArticle(
                $this->requestData,
                $prompt,
                $imagePrompt,
                $imagePrompts,
                $isMultiProduct,
                $productsCount,
                $this->cacheKey,
                true // 来自队列处理
            );
            
            // 为结果添加任务完成标记和时间戳
            $result['job_completed'] = true;
            $result['completed_at'] = Carbon::now()->toDateTimeString();
            
            // 将结果存入Redis前清理大型内容，减少内存占用
            if (isset($result['article']) && is_string($result['article']) && mb_strlen($result['article']) > 10000) {
                $result['article'] = mb_substr($result['article'], 0, 10000) . '...(结果已截断)';
            }
            
            // 将结果存入Redis
            Redis::setex($this->cacheKey, $this->cacheTtl, json_encode($result));
            
            Log::info('AI文章生成队列任务完成', [
                'cache_key' => $this->cacheKey,
                'token_count' => $result['token_count'] ?? 0,
                'has_article_id' => isset($result['article_id'])
            ]);
            
            // 手动清理不再需要的大变量，帮助垃圾回收
            unset($result);
            unset($promptData);
        } catch (\Exception $e) {
            // 记录错误
            Log::error('AI文章生成队列任务失败', [
                'cache_key' => $this->cacheKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 存储错误信息
            $errorResult = [
                'job_completed' => true,
                'error' => true,
                'message' => '生成失败：' . $e->getMessage(),
                'completed_at' => Carbon::now()->toDateTimeString()
            ];
            
            Redis::setex($this->cacheKey, $this->cacheTtl, json_encode($errorResult));
        }
    }
} 