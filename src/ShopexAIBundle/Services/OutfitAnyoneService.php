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

namespace ShopexAIBundle\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Carbon;

class OutfitAnyoneService
{
    /**
     * 百炼API密钥
     */
    private $apiKey;

    /**
     * 虚拟试衣API端点
     */
    private $apiEndpoint;
    
    /**
     * 任务查询API端点
     */
    private $taskEndpoint;

    /**
     * HTTP客户端
     */
    private $httpClient;
    
    /**
     * 默认图片URL
     */
    private $defaultImageUrl;
    
    /**
     * 轮询最大次数
     */
    private $maxPollAttempts;
    
    /**
     * 轮询间隔（秒）
     */
    private $pollInterval;
    
    /**
     * 虚拟试衣模型
     */
    private $model = 'aitryon-plus';
    
    /**
     * 备用模型
     */
    private $backupModel = 'aitryon';

    /**
     * 初始化服务
     */
    public function __construct()
    {
        $this->apiKey = config('shopexai.aliyun.bailian_api_key', '');
        $this->apiEndpoint = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/outfit-anyone/outfit-generation';
        $this->taskEndpoint = 'https://dashscope.aliyuncs.com/api/v1/tasks/';
        $this->defaultImageUrl = config('shopexai.aliyun.default_image_url', '');
        
        // 从配置中读取重试参数
        $this->maxPollAttempts = config('shopexai.aliyun.max_poll_attempts', 15);
        $this->pollInterval = config('shopexai.aliyun.poll_interval', 5);
        
        // 从配置中读取模型名称（如果有）
        $this->model = config('shopexai.aliyun.outfit_model', $this->model);
        $this->backupModel = config('shopexai.aliyun.outfit_backup_model', $this->backupModel);
        
        $this->httpClient = new Client([
            'timeout' => 60,
            'verify' => false,
        ]);
    }

    /**
     * 生成虚拟试衣图片
     * 
     * @param string $personImageUrl 人物图片URL
     * @param string $topGarmentUrl 上衣图片URL
     * @param string $bottomGarmentUrl 下装图片URL（可选）
     * @return array 包含URL的结果数组
     */
    public function generateOutfit(string $personImageUrl, string $topGarmentUrl, string $bottomGarmentUrl = ''): array
    {
        try {
            Log::info('开始生成虚拟试衣图片', [
                'person_image' => $personImageUrl,
                'top_garment' => $topGarmentUrl,
                'has_bottom_garment' => !empty($bottomGarmentUrl),
                'model' => $this->model
            ]);
            
            // 验证输入参数
            if (empty($personImageUrl)) {
                Log::warning('人物图片URL为空，无法生成试衣图');
                return $this->getDefaultImageResponse('人物图片URL为空');
            }
            
            if (empty($topGarmentUrl)) {
                Log::warning('服装图片URL为空，无法生成试衣图');
                return $this->getDefaultImageResponse('服装图片URL为空');
            }
            
            // 步骤1：创建任务获取任务ID
            $taskId = $this->createOutfitTask($personImageUrl, $topGarmentUrl, $bottomGarmentUrl);
            if (empty($taskId)) {
                Log::error('创建虚拟试衣任务失败');
                return $this->getDefaultImageResponse('创建任务失败');
            }
            
            Log::info('虚拟试衣任务已创建', ['task_id' => $taskId]);
            
            // 步骤2：轮询任务结果
            $outfitResult = $this->pollTaskResult($taskId);
            
            if (!$outfitResult['success']) {
                // 尝试使用备用模型再次生成
                Log::warning('主模型虚拟试衣生成失败，尝试使用备用模型', [
                    'error' => $outfitResult['error'],
                    'original_model' => $this->model
                ]);
                
                // 记录原始错误
                $originalError = $outfitResult['error'];
                
                // 切换到备用模型
                $originalModel = $this->model;
                $this->model = $this->backupModel;
                
                // 再次尝试生成
                $taskId = $this->createOutfitTask($personImageUrl, $topGarmentUrl, $bottomGarmentUrl);
                if (empty($taskId)) {
                    // 恢复原始模型
                    $this->model = $originalModel;
                    Log::error('备用模型创建虚拟试衣任务失败');
                    return $this->getDefaultImageResponse('主模型失败: ' . $originalError . '; 备用模型创建任务失败');
                }
                
                Log::info('备用模型虚拟试衣任务已创建', ['task_id' => $taskId, 'model' => $this->model]);
                
                $outfitResult = $this->pollTaskResult($taskId);
                
                // 恢复原始模型
                $this->model = $originalModel;
                
                if (!$outfitResult['success']) {
                    Log::error('备用模型虚拟试衣任务也失败', ['error' => $outfitResult['error']]);
                    return $this->getDefaultImageResponse('主模型失败: ' . $originalError . '; 备用模型失败: ' . $outfitResult['error']);
                }
                
                Log::info('备用模型虚拟试衣生成成功', ['url' => $outfitResult['url'], 'model' => $this->backupModel]);
                
                $result = [
                    'url' => $outfitResult['url'],
                    'model' => $this->backupModel,
                    'usage' => $outfitResult['usage'] ?? [],
                    'is_default' => false,
                    'task_id' => $taskId,
                    'original_error' => $originalError,
                    'backup_used' => true
                ];
                
                // 确保URL不为空
                if (empty($result['url'])) {
                    Log::error('备用模型生成的图片URL为空，使用默认图片');
                    return $this->getDefaultImageResponse('备用模型生成的URL为空');
                }
                
                return $result;
            }
            
            Log::info('虚拟试衣生成成功', ['url' => $outfitResult['url']]);
            
            $result = [
                'url' => $outfitResult['url'],
                'model' => $this->model,
                'usage' => $outfitResult['usage'] ?? [],
                'is_default' => false,
                'task_id' => $taskId
            ];
            
            // 确保URL不为空
            if (empty($result['url'])) {
                Log::error('主模型生成的图片URL为空，使用默认图片');
                return $this->getDefaultImageResponse('主模型生成的URL为空');
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('虚拟试衣生成过程发生异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultImageResponse($e->getMessage());
        }
    }
    
    /**
     * 创建虚拟试衣任务
     * 
     * @param string $personImageUrl 人物图片URL
     * @param string $topGarmentUrl 上衣图片URL
     * @param string $bottomGarmentUrl 下装图片URL（可选）
     * @return string|null 任务ID
     */
    private function createOutfitTask(string $personImageUrl, string $topGarmentUrl, string $bottomGarmentUrl = ''): ?string
    {
        $retryCount = 0;
        $maxRetries = 3; // 任务创建最多尝试3次
        
        while ($retryCount < $maxRetries) {
            try {
                // 构建请求参数
                $requestData = [
                    'model' => $this->model,
                    'input' => [
                        'person_image_url' => $personImageUrl,
                        'top_garment_url' => $topGarmentUrl
                    ]
                ];
                
                // 如果提供了下装图片，添加到请求中
                if (!empty($bottomGarmentUrl)) {
                    $requestData['input']['bottom_garment_url'] = $bottomGarmentUrl;
                }
                
                Log::info('创建虚拟试衣任务请求参数', [
                    'model' => $this->model,
                    'has_bottom_garment' => !empty($bottomGarmentUrl)
                ]);
                
                // 发送异步请求
                $response = $this->httpClient->post($this->apiEndpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'X-DashScope-Async' => 'enable'
                    ],
                    'json' => $requestData
                ]);
                
                // 解析响应
                $result = json_decode($response->getBody()->getContents(), true);
                
                // 检查响应状态
                if (!isset($result['output']) || !isset($result['output']['task_id'])) {
                    $errorMsg = $result['message'] ?? '创建任务响应格式异常';
                    Log::warning('创建虚拟试衣任务响应异常，尝试重试', [
                        'retry' => $retryCount + 1, 
                        'result' => $result,
                        'error' => $errorMsg
                    ]);
                    $retryCount++;
                    sleep(1); // 等待1秒后重试
                    continue;
                }
                
                return $result['output']['task_id'];
            } catch (\Exception $e) {
                Log::error('创建虚拟试衣任务失败，尝试重试', [
                    'retry' => $retryCount + 1,
                    'error' => $e->getMessage()
                ]);
                
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    Log::error('创建虚拟试衣任务达到最大重试次数', [
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
                
                sleep(1); // 等待1秒后重试
            }
        }
        
        return null; // 所有重试都失败
    }
    
    /**
     * 轮询任务结果
     * 
     * @param string $taskId 任务ID
     * @return array 任务结果
     */
    private function pollTaskResult(string $taskId): array
    {
        $attempts = 0;
        $lastStatus = null;
        $lastError = null;
        
        while ($attempts < $this->maxPollAttempts) {
            try {
                // 等待一段时间后再查询
                if ($attempts > 0) {
                    sleep($this->pollInterval);
                }
                
                $attempts++;
                
                // 查询任务状态
                $response = $this->httpClient->get($this->taskEndpoint . $taskId, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey
                    ]
                ]);
                
                $result = json_decode($response->getBody()->getContents(), true);
                $status = $result['output']['task_status'] ?? 'UNKNOWN';
                $lastStatus = $status;
                
                Log::info('虚拟试衣任务查询结果', [
                    'attempt' => $attempts, 
                    'task_id' => $taskId, 
                    'status' => $status
                ]);
                
                // 检查任务状态
                if (isset($result['output']['task_status'])) {
                    // 任务完成
                    if ($status === 'SUCCEEDED') {
                        // 检查结果是否包含图片URL
                        if (isset($result['output']['image_url'])) {
                            return [
                                'success' => true,
                                'url' => $result['output']['image_url'],
                                'usage' => $result['usage'] ?? []
                            ];
                        } else {
                            $lastError = '任务成功但未返回图片URL';
                            return ['success' => false, 'error' => $lastError];
                        }
                    }
                    
                    // 任务失败
                    if ($status === 'FAILED') {
                        $errorMessage = '';
                        if (isset($result['output']['code'])) {
                            $errorMessage = $result['output']['code'] . ': ' . ($result['output']['message'] ?? '未知错误');
                        } else if (isset($result['message'])) {
                            $errorMessage = $result['message'];
                        } else {
                            $errorMessage = '任务执行失败，未提供错误信息';
                        }
                        
                        $lastError = $errorMessage;
                        return ['success' => false, 'error' => $errorMessage];
                    }
                    
                    // 任务取消
                    if ($status === 'CANCELED') {
                        $lastError = '任务已被取消';
                        return ['success' => false, 'error' => $lastError];
                    }
                    
                    // 任务处理中或排队中，继续轮询
                    if (in_array($status, ['PENDING', 'RUNNING', 'PRE-PROCESSING', 'POST-PROCESSING'])) {
                        continue;
                    }
                }
            } catch (\Exception $e) {
                Log::error('查询虚拟试衣任务状态失败', [
                    'attempt' => $attempts,
                    'task_id' => $taskId,
                    'error' => $e->getMessage()
                ]);
                
                $lastError = '查询任务状态失败: ' . $e->getMessage();
                
                // 如果是最后一次尝试，则返回错误
                if ($attempts >= $this->maxPollAttempts) {
                    return ['success' => false, 'error' => $lastError];
                }
            }
        }
        
        // 超过最大尝试次数
        $errorMessage = '超过最大轮询次数，最后状态: ' . ($lastStatus ?? 'UNKNOWN');
        if ($lastError) {
            $errorMessage .= ', 错误: ' . $lastError;
        }
        
        Log::error('虚拟试衣任务轮询超时', [
            'task_id' => $taskId,
            'last_status' => $lastStatus,
            'last_error' => $lastError
        ]);
        
        return ['success' => false, 'error' => $errorMessage];
    }
    
    /**
     * 获取默认图片响应
     * 
     * @param string $errorMessage 错误信息
     * @return array 包含默认图片URL的响应
     */
    private function getDefaultImageResponse(string $errorMessage): array
    {
        $defaultUrl = $this->defaultImageUrl;
        
        // 确保默认URL不为空
        if (empty($defaultUrl)) {
            $defaultUrl = 'https://img.alicdn.com/imgextra/i4/O1CN01c26iB51CGdiWJA4L3_!!6000000000564-2-tps-818-404.png';
            Log::warning('默认图片URL为空，使用硬编码的备选URL');
        }
        
        Log::info('返回默认图片', ['url' => $defaultUrl, 'error' => $errorMessage]);
        
        return [
            'url' => $defaultUrl,
            'is_default' => true,
            'error' => $errorMessage,
            'model' => $this->model . ' (default)'
        ];
    }
    
    /**
     * 保存任务结果到Redis
     * 
     * @param string $cacheKey 缓存键
     * @param array $result 结果数据
     * @param int $ttl 缓存有效期（秒）
     * @return void
     */
    public function saveResultToCache(string $cacheKey, array $result, int $ttl = 3600): void
    {
        // 添加任务完成标记和时间戳
        $result['job_completed'] = true;
        $result['completed_at'] = Carbon::now()->toDateTimeString();
        
        // 将结果存入Redis
        Redis::setex($cacheKey, $ttl, json_encode($result));
        
        Log::info('虚拟试衣结果已缓存', [
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
            'is_default' => $result['is_default'] ?? false
        ]);
    }
    
    /**
     * 从缓存获取结果
     * 
     * @param string $cacheKey 缓存键
     * @return array|null 结果数据
     */
    public function getResultFromCache(string $cacheKey): ?array
    {
        $cached = Redis::get($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }
        return null;
    }
} 