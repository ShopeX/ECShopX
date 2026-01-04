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

class AliyunImageService
{
    /**
     * 百炼API密钥
     */
    private $apiKey;

    /**
     * 百炼文生图API端点
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
     * 图片生成模型
     */
    // 更新模型名称，使用通义万相V1版本模型（支持文+图生成图片）
    private $model = 'wanx-v1';
    // 备用模型，当主模型失败时使用
    private $backupModel = 'wanx2.1-t2i-turbo';

    /**
     * 初始化服务
     */
    public function __construct()
    {
        // IDX: 2367340174
        $this->apiKey = config('shopexai.aliyun.bailian_api_key', '');
        $this->apiEndpoint = config('shopexai.aliyun.bailian_endpoint', 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis');
        $this->taskEndpoint = 'https://dashscope.aliyuncs.com/api/v1/tasks/';
        $this->defaultImageUrl = config('shopexai.aliyun.default_image_url', '');
        
        // 从配置中读取重试参数
        $this->maxPollAttempts = config('shopexai.aliyun.max_poll_attempts', 15); // 增加默认重试次数
        $this->pollInterval = config('shopexai.aliyun.poll_interval', 5);
        
        // 从配置中读取模型名称（如果有）
        $this->model = config('shopexai.aliyun.model', $this->model);
        $this->backupModel = config('shopexai.aliyun.backup_model', $this->backupModel);
        
        $this->httpClient = new Client([
            'timeout' => 60, // 设置超时时间为60秒
            'verify' => false, // 开发环境可能需要禁用SSL验证
        ]);
    }

    /**
     * 使用通义万相-文生图V1生成图片（异步方式）
     * 
     * @param string $prompt 图片描述提示词
     * @param string $ref_image 参考图片（可选）
     * @param string $negative_prompt 反向提示词（可选）
     * @return array 包含URL的结果数组
     */
    public function generateImage(string $prompt, string $ref_image = '', string $negative_prompt = ''): array
    {
        try {
            Log::info('开始生成图片', [
                'prompt' => $prompt,
                'ref_image' => !empty($ref_image) ? '已提供' : '未提供', 
                'model' => $this->model,
                'has_negative_prompt' => !empty($negative_prompt)
            ]);
            
            // 检查提示词是否为空
            if (empty(trim($prompt))) {
                Log::warning('图片提示词为空，使用默认图片');
                return $this->getDefaultImageResponse('提示词为空');
            }
            
            // 步骤1：创建任务获取任务ID
            $taskId = $this->createImageTask($prompt, $ref_image, $negative_prompt);
            if (empty($taskId)) {
                Log::error('创建图片生成任务失败');
                return $this->getDefaultImageResponse('创建任务失败');
            }
            
            Log::info('图片生成任务已创建', ['task_id' => $taskId]);
            
            // 步骤2：轮询任务结果
            $imageResult = $this->pollTaskResult($taskId);
            
            if (!$imageResult['success']) {
                // 尝试使用备用模型再次生成
                Log::warning('主模型图片生成失败，尝试使用备用模型', [
                    'error' => $imageResult['error'],
                    'original_model' => $this->model
                ]);
                
                // 记录原始错误
                $originalError = $imageResult['error'];
                
                // 切换到备用模型
                $originalModel = $this->model;
                $this->model = $this->backupModel;
                
                // 再次尝试生成
                $taskId = $this->createImageTask($prompt,$ref_image, $negative_prompt);
                if (empty($taskId)) {
                    // 恢复原始模型
                    $this->model = $originalModel;
                    Log::error('备用模型创建图片生成任务失败');
                    return $this->getDefaultImageResponse('主模型失败: ' . $originalError . '; 备用模型创建任务失败');
                }
                
                Log::info('备用模型图片生成任务已创建', ['task_id' => $taskId, 'model' => $this->model]);
                
                $imageResult = $this->pollTaskResult($taskId);
                
                // 恢复原始模型
                $this->model = $originalModel;
                
                if (!$imageResult['success']) {
                    Log::error('备用模型图片生成任务也失败', ['error' => $imageResult['error']]);
                    return $this->getDefaultImageResponse('主模型失败: ' . $originalError . '; 备用模型失败: ' . $imageResult['error']);
                }
                
                Log::info('备用模型图片生成成功', ['url' => $imageResult['url'], 'model' => $this->model]);
                
                $result = [
                    'url' => $imageResult['url'],
                    'model' => $this->model . ' (备用)',
                    'usage' => $imageResult['usage'] ?? [],
                    'is_default' => false,
                    'task_id' => $taskId,
                    'actual_prompt' => $imageResult['actual_prompt'] ?? $prompt,
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
            
            Log::info('图片生成成功', ['url' => $imageResult['url']]);
            
            $result = [
                'url' => $imageResult['url'],
                'model' => $this->model,
                'usage' => $imageResult['usage'] ?? [],
                'is_default' => false,
                'task_id' => $taskId,
                'actual_prompt' => $imageResult['actual_prompt'] ?? $prompt
            ];
            
            // 确保URL不为空
            if (empty($result['url'])) {
                Log::error('主模型生成的图片URL为空，使用默认图片');
                return $this->getDefaultImageResponse('主模型生成的URL为空');
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('图片生成过程发生异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultImageResponse($e->getMessage());
        }
    }
    
    /**
     * 创建图片生成任务
     * 
     * @param string $prompt 图片描述提示词
     * @param string $ref_image 参考图片（可选）
     * @param string $negative_prompt 反向提示词（可选）
     * @return string|null 任务ID
     */
    private function createImageTask(string $prompt, string $ref_image = '', string $negative_prompt = ''): ?string
    {
        $retryCount = 0;
        $maxRetries = 3; // 任务创建最多尝试3次
        
        while ($retryCount < $maxRetries) {
            try {
                // 构建基础请求参数
                $requestData = [
                    'model' => $this->model,
                    'input' => [
                        'prompt' => $prompt
                    ],
                    'parameters' => [
                        'n' => 1,
                        'size' => '512*512' // 默认图片尺寸
                    ]
                ];
                
                // 添加负面提示词（如果有）
                if (!empty($negative_prompt)) {
                    $requestData['input']['negative_prompt'] = $negative_prompt;
                }
                
                // 根据不同模型添加特定参数
                if (strpos($this->model, 'stable-diffusion') !== false) {
                    // SD系列模型参数
                    $requestData['parameters']['style_preset'] = 'photographic';
                    $requestData['parameters']['steps'] = 30;
                    
                    // 如果有参考图片，添加img2img参数
                    if (!empty($ref_image)) {
                        $requestData['input']['image'] = $ref_image;
                        $requestData['parameters']['image_strength'] = 0.7; // 参考图片强度
                    }
                } elseif (strpos($this->model, 'wanx') !== false) {
                    // 万相系列模型参数
                    $requestData['parameters']['style'] = '<auto>';
                    $requestData['parameters']['size'] = '1024*1024'; // 提高图片质量
                    
                    // 如果有参考图片，添加对应参数
                    if (!empty($ref_image)) {
                        // wanx-v1模型使用ref_image参数
                        $requestData['input']['ref_image'] = $ref_image;
                        
                        // 对于wanx-v1模型，可以添加参考图强度和模式参数
                        if ($this->model === 'wanx-v1') {
                            $requestData['parameters']['ref_strength'] = '0.7'; // 参考图影响强度
                            $requestData['parameters']['ref_mode'] = 'repaint'; // 参考图模式：repaint复绘
                        }
                    }
                    
                    // 可以添加反向提示词
                    if (isset($requestData['input']['negative_prompt'])) {
                        $requestData['input']['negative_prompt'] = '低质量, 模糊, 变形';
                    }
                }
                
                Log::info('创建图片任务请求参数', [
                    'model' => $this->model,
                    'has_ref_image' => !empty($ref_image),
                    'param_keys' => array_keys($requestData['parameters'])
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
                    Log::warning('创建图片任务响应异常，尝试重试', [
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
                Log::error('创建图片任务失败，尝试重试', [
                    'retry' => $retryCount + 1,
                    'error' => $e->getMessage()
                ]);
                
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    Log::error('创建图片任务达到最大重试次数', [
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
                
                Log::info('图片生成任务查询结果', [
                    'attempt' => $attempts, 
                    'task_id' => $taskId, 
                    'status' => $status
                ]);
                
                // 检查任务状态
                if (isset($result['output']['task_status'])) {
                    // 任务完成
                    if ($status === 'SUCCEEDED') {
                        // 检查结果是否完整
                        if (isset($result['output']['results'][0]['url'])) {
                            return [
                                'success' => true,
                                'url' => $result['output']['results'][0]['url'],
                                'usage' => $result['usage'] ?? [],
                                'actual_prompt' => $result['output']['results'][0]['actual_prompt'] ?? null
                            ];
                        } else {
                            $lastError = '任务成功但未返回图片URL';
                            return ['success' => false, 'error' => $lastError];
                        }
                    }
                    
                    // 任务失败
                    if ($status === 'FAILED') {
                        $errorMessage = '';
                        if (isset($result['output']['results'][0]['message'])) {
                            $errorMessage = $result['output']['results'][0]['message'];
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
                    if ($status === 'PENDING' || $status === 'RUNNING') {
                        continue;
                    }
                }
            } catch (\Exception $e) {
                Log::error('查询任务状态失败', [
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
        
        Log::error('图片生成任务轮询超时', [
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
} 