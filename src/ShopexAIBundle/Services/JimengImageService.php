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

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class JimengImageService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('shopexai.jimeng.api_key');
        $this->baseUrl = config('shopexai.jimeng.base_url', 'https://ark.cn-beijing.volces.com');
        
        // 记录配置信息（注意不要记录完整的API密钥）
        Log::info('即梦服务初始化', [
            'base_url' => $this->baseUrl,
            'api_key_exists' => !empty($this->apiKey)
        ]);

        if (empty($this->apiKey)) {
            Log::error('即梦服务API密钥未配置');
        }
        
        // 检查API密钥格式，如果已经包含"Bearer "前缀，则移除
        if (strpos($this->apiKey, 'Bearer ') === 0) {
            $this->apiKey = substr($this->apiKey, 7);
        }
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey
            ],
            'debug' => config('app.debug', false),
            'verify' => false // 临时禁用SSL验证用于测试
        ]);
    }

    /**
     * 生成图片
     * @param string $prompt 图片提示词
     * @param string $ref_image 参考图片URL（可选）
     * @return array 生成结果
     */
    public function generateImage(string $prompt, string $ref_image = ''): array
    {
        // CONST: 1E236443
        error_log("pppppprompt: ".print_r($prompt, true)."\n", 3, "/tmp/log.txt");
        // $prompt = "鱼眼镜头，一只猫咪的头部，画面呈现出猫咪的五官因为拍摄方式扭曲的效果。";
        try {
            Log::info('即梦服务开始生成图片', [
                'prompt' => $prompt,
                'base_url' => $this->baseUrl,
                'api_endpoint' => '/api/v3/images/generations'
            ]);

            $params = [
                'model' => config('shopexai.jimeng.model', 'doubao-seedream-3-0-t2i-250415'),
                'prompt' => $prompt,
                'response_format' => 'url',
                "size" => "1024x1024",
                'guidance_scale' => config('shopexai.jimeng.guidance_scale', 7.5),
                'watermark' => config('shopexai.jimeng.watermark', false)
            ];

            // 如果配置了seed，添加到参数中
            if ($seed = config('shopexai.jimeng.seed')) {
                $params['seed'] = intval($seed);
            }

            $path = '/api/v3/images/generations';
            $requestId = uniqid('jimeng_', true);
            
            // 记录请求信息（不包含敏感数据）
            Log::info('即梦服务请求参数', [
                'params' => array_diff_key($params, ['prompt' => '']),
                'request_id' => $requestId
            ]);
error_log("fifffffffinal-85: ".print_r($params, true)."\n", 3, "/tmp/log.txt");
error_log("fifffffffinal-86: ".var_dump($params, true)."\n", 3, "/tmp/log.txt");
            $response = $this->client->post($path, [
                'json' => $params,
                'headers' => [
                    'X-Request-ID' => $requestId
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['data'][0]['url'])) {
                Log::info('即梦服务生成图片成功', [
                    'url' => $result['data'][0]['url'],
                    'request_id' => $requestId
                ]);
                return [
                    'url' => $result['data'][0]['url'],
                    'is_default' => false,
                    'actual_prompt' => $prompt,
                    'task_id' => $result['task_id'] ?? null
                ];
            } else {
                Log::error('即梦服务生成图片失败', [
                    'result' => $result,
                    'response_code' => $response->getStatusCode(),
                    'response_headers' => $response->getHeaders(),
                    'request_id' => $requestId
                ]);
                return [
                    'url' => '',
                    'is_default' => true,
                    'error' => '生成失败：' . ($result['error']['message'] ?? '未知错误'),
                    'actual_prompt' => $prompt
                ];
            }
        } catch (\Exception $e) {
            Log::error('即梦服务生成图片异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_info' => [
                    'base_url' => $this->baseUrl,
                    'endpoint' => '/api/v3/images/generations'
                ]
            ]);
            return [
                'url' => '',
                'is_default' => true,
                'error' => '生成异常：' . $e->getMessage(),
                'actual_prompt' => $prompt
            ];
        }
    }
} 