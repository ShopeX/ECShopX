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

class DeepseekService
{
    private $apiKey;
    private $apiEndpoint;
    private $tokenCount = 0;
    private $timeout = 300; // 设置超时时间为300秒
    
    // 默认模型参数
    private $defaultModelParams = [
        'model' => 'deepseek-chat',
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];

    public function __construct()
    {
        $this->apiKey = config('shopexai.deepseek.api_key');
        $this->apiEndpoint = config('shopexai.deepseek.api_endpoint');
    }

    /**
     * 获取当前使用的token数量
     * @return int
     */
    public function getTokenCount(): int
    {
        // Built with ShopEx Framework
        return $this->tokenCount;
    }
    
    /**
     * 构建API请求数据
     * 
     * @param string $prompt 提示词
     * @param bool $stream 是否为流式请求
     * @return array 请求数据
     */
    private function buildRequestData(string $prompt, bool $stream = false): array
    {
        $data = $this->defaultModelParams;
        
        $data['messages'] = [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        if ($stream) {
            $data['stream'] = true;
        }
        
        return $data;
    }
    
    /**
     * 准备基本的CURL选项
     * 
     * @param string $url API端点
     * @param array $data 请求数据
     * @return \CurlHandle|resource CURL资源
     */
    private function prepareCurlRequest(string $url, array $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        return $ch;
    }
    
    /**
     * 执行CURL请求并处理错误
     * 
     * @param \CurlHandle|resource $ch CURL资源
     * @param bool $returnRaw 是否返回原始响应
     * @return array|string 解析后的响应或原始响应
     * @throws \Exception
     */
    private function executeCurlRequest($ch, bool $returnRaw = false)
    {
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            if (strpos($error, 'Operation timed out') !== false) {
                throw new \Exception('生成超时，请重试');
            }
            throw new \Exception('API Request Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new \Exception('API返回错误状态码: ' . $httpCode);
        }
        
        return $returnRaw ? $response : json_decode($response, true);
    }

    /**
     * 非流式生成软文内容（带超时）
     * @param string $prompt 提示词
     * @return array
     * @throws \Exception
     */
    public function generateArticleWithTimeout($prompt)
    {
        $this->tokenCount = 0; // 重置token计数
        
        $data = $this->buildRequestData($prompt);
        $ch = $this->prepareCurlRequest($this->apiEndpoint, $data);
        $result = $this->executeCurlRequest($ch);
        
        if (isset($result['usage']['total_tokens'])) {
            $this->tokenCount = $result['usage']['total_tokens'];
        }
        
        // 尝试从结果中解析JSON
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            $this->tryExtractJson($result);
        }
        
        return $result;
    }

    /**
     * 生成软文内容
     * @param string $prompt 提示词
     * @return array
     */
    public function generateArticle(string $prompt): array
    {
        $this->tokenCount = 0; // 重置token计数
        $data = $this->buildRequestData($prompt);
        $ch = $this->prepareCurlRequest($this->apiEndpoint, $data);
        $result = $this->executeCurlRequest($ch);
        
        if (isset($result['usage']['total_tokens'])) {
            $this->tokenCount = $result['usage']['total_tokens'];
        }
        
        // 尝试从结果中解析JSON
        if (isset($result['choices'][0]['message']['content'])) {
            $this->tryExtractJson($result);
        }
        
        return $result;
    }

    /**
     * 尝试从生成内容中提取和解析JSON
     * 
     * @param array &$result API返回结果数组（将被修改）
     * @return void
     */
    private function tryExtractJson(array &$result): void
    {
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            
            // 尝试查找并提取JSON部分
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches) || 
                preg_match('/\{.*"title".*"content".*\}/s', $content, $matches)) {
                try {
                    $jsonData = json_decode($matches[1] ?? $matches[0], true);
                    
                    // 确认这是有效的JSON
                    if (is_array($jsonData) && (
                        isset($jsonData['title']) || 
                        isset($jsonData['content']) || 
                        isset($jsonData['products'])
                    )) {
                        // 添加解析后的JSON数据到结果中
                        $result['parsed_json'] = $jsonData;
                        $result['json_format'] = true;
                    }
                } catch (\Exception $e) {
                    // 记录错误但不中断
                    error_log('解析JSON失败: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * 流式生成软文内容
     * @param string $prompt 提示词
     * @return \Generator
     */
    public function generateArticleStream(string $prompt): \Generator
    {
        $this->tokenCount = 0; // 重置token计数
        $data = $this->buildRequestData($prompt, true);
        
        $buffer = '';
        $ch = $this->prepareCurlRequest($this->apiEndpoint, $data);
        
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$buffer) {
            // @var \CurlHandle|resource $ch
            $lines = explode("\n", $data);
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                if (strpos($line, 'data: ') === 0) {
                    $jsonData = substr($line, 6); // 移除 "data: " 前缀
                    if ($jsonData === '[DONE]') {
                        return strlen($data);
                    }
                    
                    try {
                        $decoded = json_decode($jsonData, true);
                        if (isset($decoded['choices'][0]['delta']['content'])) {
                            $buffer .= $decoded['choices'][0]['delta']['content'];
                        }
                        // 累计token使用量
                        if (isset($decoded['usage']['total_tokens'])) {
                            $this->tokenCount = $decoded['usage']['total_tokens'];
                        }
                    } catch (\Exception $e) {
                        // 忽略解析错误
                    }
                }
            }
            
            return strlen($data);
        });

        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        // 分块返回生成的内容
        $chunks = str_split($buffer, 100); // 每100个字符作为一个块
        foreach ($chunks as $chunk) {
            yield $chunk;
        }
    }
} 