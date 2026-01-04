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

namespace ShopexAIBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Http\Request;
use ShopexAIBundle\Services\ArticleService;
use ShopexAIBundle\Services\PromptService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use ShopexAIBundle\Jobs\GenerateArticleJob;
use Illuminate\Support\Facades\Log;

class ArticleController extends BaseController
{
    protected $articleService;
    protected $redis;
    protected $promptService;
    
    // 默认缓存时间（秒）
    protected $cacheTtl = 60;
    
    // 缓存键前缀
    protected $cacheKeyPrefix = 'article_gen:';
    
    // 处理中标记
    protected $processingFlag = 'PROCESSING';
    
    // 是否使用队列
    protected $useQueue = true;
    
    // 队列名称
    protected $queueName = 'slow';

    public function __construct(ArticleService $articleService, PromptService $promptService)
    {
        $this->articleService = $articleService;
        $this->promptService = $promptService;
        $this->redis = app('redis');
        
        // 从配置中读取设置
        $this->cacheTtl = config('shopexai.cache_ttl', 300);
        $this->useQueue = config('shopexai.use_queue', true);
        $this->queueName = config('shopexai.queue_name', 'slow');
    }
    
    /**
     * @SWG\Post(
     *     path="/article/generate-direct",
     *     summary="直接生成软文及配图",
     *     tags={"AI软文生成"},
     *     description="根据提示词直接生成软文内容和配图",
     *     operationId="generateDirect",
     *     @SWG\Parameter(
     *         name="product",
     *         in="formData",
     *         description="商品数据数组",
     *         required=true,
     *         type="array",
     *         @SWG\Items(
     *             type="object",
     *             @SWG\Property(property="name", type="string", description="商品名称"),
     *             @SWG\Property(property="params", type="string", description="商品参数描述"),
     *             @SWG\Property(property="price", type="number", description="商品价格(单位:分)"),
     *             @SWG\Property(property="category", type="string", description="商品分类"),
     *             @SWG\Property(property="item_id", type="integer", description="商品ID"),
     *             @SWG\Property(property="item_image_url", type="string", description="商品图片URL")
     *             @SWG\Property(property="sales", type="integer", description="商品销量")
     *         )
     *     ),
     *     @SWG\Parameter(
     *         name="is_article",
     *         in="formData",
     *         description="是否生成文章内容，默认为true",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="is_image",
     *         in="formData",
     *         description="是否生成配图，默认为true",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="formData",
     *         description="文章分类ID",
     *         required=true,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         name="author_persona",
     *         in="formData",
     *         description="作者人设",
     *         required=false,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         name="subject_desc",
     *         in="formData",
     *         description="主题描述",
     *         required=false,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         name="detial_desc",
     *         in="formData",
     *         description="详细描述",
     *         required=false,
     *         type="integer"
     *     ),
     *      @SWG\Parameter(
     *         name="industry_presets",
     *         in="formData",
     *         description="行业预设值",
     *         required=false,
     *         type="array",
     *         @SWG\Items(
     *             type="object",
     *             @SWG\Property(property="fabric", type="string", description="面料"),
     *             @SWG\Property(property="fabric", type="string", description="透气性"),
     *             @SWG\Property(property="style", type="string", description="款式"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="article", type="string", description="生成的文章内容"),
     *             @SWG\Property(property="image", type="string", description="主图URL"),
     *             @SWG\Property(property="images", type="array", @SWG\Items(type="string"), description="所有图片URL数组"),
     *             @SWG\Property(property="token_count", type="integer", description="使用的token数量"),
     *             @SWG\Property(property="article_id", type="integer", description="保存的文章ID")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="请求参数错误",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="message", type="string", description="错误信息")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="服务器错误",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="message", type="string", description="错误信息")
     *         )
     *     )
     * )
     *
     * 直接生成内容（非流式）- 不使用会话
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function generateDirect(Request $request)
    {
        // 获取请求数据
        $requestData = $request->all();
        
        Log::info("AI文章生成请求", ['request_data' => $requestData]);
        
        // 验证请求数据
        $validationErrors = $this->validateArticleRequest($requestData);
        if(!empty($validationErrors)){
            return $this->response->error('参数验证失败：' . implode('；', $validationErrors), 400);
        }

        // 生成缓存键
        $cacheKey = $this->generateCacheKey($requestData);
        
        // 检查缓存
        $cachedResult = $this->checkCache($cacheKey);
        
        // 如果有缓存结果且不是处理中，直接返回
        if ($cachedResult && !isset($cachedResult['processing'])) {
            // 如果结果中包含article
            if (isset($cachedResult['article']) && !empty($cachedResult['article'])) {
                // 检查是否已经处理过该请求
                $processedFlag = $this->redis->get('article_processed:' . $cacheKey);
                if (!$processedFlag) {
                    // 始终使用结构化文章方法保存
                    try {
                        // 使用已缓存的结果保存到文章系统
                        $saveResult = $this->articleService->formatAndSaveToStructuredArticle(array_merge($cachedResult, $requestData), $cacheKey);
                        
                        Log::info("AI文章保存结果", [
                            'success' => $saveResult['success'], 
                            'data' => $saveResult['data'] ?? [], 
                            'cache_key' => $cacheKey
                        ]);
                        
                        if ($saveResult['success'] && isset($saveResult['data']['article_id'])) {
                            $this->redis->setex('article_processed:' . $cacheKey, $this->cacheTtl, $saveResult['data']['article_id']); // 24小时有效期
                            $cachedResult['article_id'] = $saveResult['data']['article_id'];
                            return $this->response->array($cachedResult);
                        } else {
                            Log::error("AI文章保存失败", ['message' => $saveResult['message'] ?? '未知错误', 'cache_key' => $cacheKey]);
                        }
                    } catch (\Exception $e) {
                        // 记录错误但不中断流程
                        Log::error("AI文章保存异常", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'cache_key' => $cacheKey]);
                        $cachedResult['save_error'] = $e->getMessage();
                    }
                } else {
                    $cachedResult['article_id'] = $processedFlag;
                    return $this->response->array($cachedResult);
                }
            }
            
            return $this->response->array($cachedResult);
        }
        
        // 如果正在处理中，返回提示
        if ($cachedResult && isset($cachedResult['processing'])) {
            return $this->response->array([
                'message' => '内容正在生成中，请稍等片刻',
                'processing' => true,
                'cache_key' => $cacheKey
            ]);
        }
        
        // 标记为处理中
        $this->markAsProcessing($cacheKey);
                

        // 根据配置决定同步或异步处理
        if ($this->useQueue) {
            // 获取当前用户信息，用于队列任务
            $companyId = app('auth')->user()->get('company_id') ?: 1;
            $operatorId = app('auth')->user()->get('operator_id') ?: 1;
            $distributorId = app('auth')->user()->get('distributor_id') ?: 0;
            
            
            // 创建队列任务并指定队列名称
            $aiGenerationJob = (new GenerateArticleJob(
                $requestData, 
                $cacheKey, 
                $this->cacheTtl,
                $companyId,
                $operatorId,
                $distributorId
            ))->onQueue($this->queueName);
            
            // 使用Bus Dispatcher分发任务
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($aiGenerationJob);
            
            return $this->response->array([
                'message' => '内容生成任务已加入队列，请稍后查询结果',
                'processing' => true,
                'cache_key' => $cacheKey,
                'async' => true
            ]);
        } else {

            // 使用 PromptService 构建提示词
            $promptData = $this->promptService->buildPrompt($requestData);
            $prompt = $promptData['prompt'];
            $imagePrompt = $promptData['image_prompt'];
            $imagePrompts = $promptData['image_prompts'] ?? [$imagePrompt];
            $isMultiProduct = $promptData['multi_product'] ?? false;
            $productsCount = $promptData['products_count'] ?? 1;

            Log::info("AI文章生成提示词", [
                'prompt_length' => strlen($prompt),
                'image_prompts_count' => count($imagePrompts),
                'is_multi_product' => $isMultiProduct,
                'products_count' => $productsCount,
                'cache_key' => $cacheKey
            ]);

            // 同步处理 - 立即生成内容
            try {
                // 使用ArticleService中的公共方法处理生成逻辑
                $result = $this->articleService->generateAndProcessArticle(
                    $requestData,
                    $prompt,
                    $imagePrompt,
                    $imagePrompts,
                    $isMultiProduct,
                    $productsCount,
                    $cacheKey,
                    false // 非队列处理
                );
                
                // 缓存结果
                $this->cacheResult($cacheKey, $result);
                
                return $this->response->array($result);
            } catch (\Exception $e) {
                // 移除处理中标记
                $this->redis->del($cacheKey);
                Log::error("AI文章生成失败", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                
                return $this->response->error('生成失败：' . $e->getMessage(), 500);
            }
        }
    }
    
    /**
     * @SWG\Get(
     *     path="/article/check-status",
     *     summary="检查文章生成状态",
     *     tags={"AI软文生成"},
     *     description="检查文章生成任务的处理状态",
     *     operationId="checkGenerateStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="cache_key",
     *         in="query",
     *         description="生成任务的缓存键",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="message", type="string", description="状态描述"),
     *             @SWG\Property(property="processing", type="boolean", description="是否正在处理中"),
     *             @SWG\Property(property="found", type="boolean", description="是否找到任务"),
     *             @SWG\Property(property="article", type="string", description="生成的文章内容，仅当任务完成时返回"),
     *             @SWG\Property(property="image", type="string", description="生成的图片URL，仅当任务完成时返回"),
     *             @SWG\Property(property="article_id", type="integer", description="保存的文章ID，仅当任务完成且保存成功时返回")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="请求参数错误",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="message", type="string", description="错误信息")
     *         )
     *     )
     * )
     *
     * 检查文章生成状态
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function checkGenerateStatus(Request $request)
    {
        $cacheKey = $request->input('cache_key');
        
        if (empty($cacheKey)) {
            return $this->response->error('请提供有效的缓存键', 400);
        }
        
        // 检查缓存
        $cachedResult = $this->checkCache($cacheKey);
        
        // 如果没有找到缓存
        if (empty($cachedResult)) {
            return $this->response->array([
                'message' => '未找到生成任务或任务已过期',
                'found' => false
            ]);
        }
        
        // 如果正在处理中
        if (isset($cachedResult['processing'])) {
            return $this->response->array([
                'message' => '内容正在生成中，请稍等片刻',
                'processing' => true,
                'cache_key' => $cacheKey
            ]);
        }
        
        // 如果有错误
        if (isset($cachedResult['error']) && $cachedResult['error']) {
            $cachedResult['processing'] = false;
            return $this->response->array($cachedResult);
        }
        
        // 返回结果
        $cachedResult['processing'] = false;
        return $this->response->array($cachedResult);
    }

    /**
     * 生成文章内容和配图
     * @param string $prompt 文章提示词
     * @param string $imagePrompt 图片提示词
     * @return array
     */
    public function generateArticleWithImage(string $prompt, string $imagePrompt): array
    {
        // 生成文章内容
        $articleResult = $this->deepseekService->generateArticle($prompt);
        
        // 生成配图
        $imageResult = $this->aliyunImageService->generateImage($imagePrompt);

        return [
            'article' => $articleResult['choices'][0]['message']['content'] ?? '',
            'image' => $imageResult['url'] ?? '',
            'is_default_image' => $imageResult['is_default'] ?? false,
            'actual_prompt' => $imageResult['actual_prompt'] ?? $imagePrompt
        ];
    }
    

    /**
     * 生成缓存键
     * @param array $data 请求数据
     * @return string 缓存键
     */
    protected function generateCacheKey(array $data): string
    {
        // 对数据进行排序，确保相同内容但顺序不同的数据生成相同的键
        $this->sortRecursive($data);
        
        // 生成MD5作为缓存键
        return $this->cacheKeyPrefix . md5(json_encode($data));
    }
    
    /**
     * 递归排序数组
     * @param array &$array 要排序的数组
     * @return void
     */
    protected function sortRecursive(array &$array): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortRecursive($value);
            }
        }
        
        if (array_keys($array) === range(0, count($array) - 1)) {
            sort($array);
        } else {
            ksort($array);
        }
    }
    
    /**
     * 检查缓存并获取数据
     * @param string $cacheKey 缓存键
     * @return array|null 缓存数据或null
     */
    protected function checkCache(string $cacheKey): ?array
    {
        $cachedData = $this->redis->get($cacheKey);
        
        if (!$cachedData) {
            return null;
        }
        
        // 如果是处理中标记，返回特殊标记
        if ($cachedData === $this->processingFlag) {
            return ['processing' => true];
        }
        
        return json_decode($cachedData, true);
    }
    
    /**
     * 标记请求为处理中
     * @param string $cacheKey 缓存键
     * @return void
     */
    protected function markAsProcessing(string $cacheKey): void
    {
        $this->redis->setex($cacheKey, $this->cacheTtl, $this->processingFlag);
    }
    
    /**
     * 缓存生成结果
     * @param string $cacheKey 缓存键
     * @param array $data 要缓存的数据
     * @return void
     */
    protected function cacheResult(string $cacheKey, array $data): void
    {
        $this->redis->setex($cacheKey, $this->cacheTtl, json_encode($data));
    }

    /**
     * @SWG\Post(
     *     path="/article/save-structured",
     *     summary="保存结构化商品文章",
     *     tags={"AI软文生成"},
     *     description="将生成的内容保存为结构化的文章，支持多商品、多模块布局",
     *     operationId="saveStructuredArticle",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="article",
     *         in="formData",
     *         description="文章内容",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image",
     *         in="formData",
     *         description="主图URL",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="images",
     *         in="formData",
     *         description="多张图片URL数组",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="multi_product",
     *         in="formData",
     *         description="是否多产品文章",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="products_count",
     *         in="formData",
     *         description="产品数量",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="product",
     *         in="formData",
     *         description="商品数据数组",
     *         required=false,
     *         type="array",
     *         @SWG\Items(
     *             type="object",
     *             @SWG\Property(property="name", type="string", description="商品名称"),
     *             @SWG\Property(property="params", type="string", description="商品参数描述"),
     *             @SWG\Property(property="price", type="number", description="商品价格(单位:分)"),
     *             @SWG\Property(property="category", type="string", description="商品分类"),
     *             @SWG\Property(property="item_id", type="integer", description="商品ID"),
     *             @SWG\Property(property="item_image_url", type="string", description="商品图片URL")
     *         )
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="formData",
     *         description="文章分类ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="author",
     *         in="formData",
     *         description="作者名称",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="formData",
     *         description="分销商ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="success", type="boolean", description="是否成功"),
     *             @SWG\Property(property="article_id", type="integer", description="文章ID"),
     *             @SWG\Property(property="title", type="string", description="文章标题"),
     *             @SWG\Property(property="structured", type="boolean", description="是否为结构化文章"),
     *             @SWG\Property(property="module_count", type="integer", description="文章模块数量")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="请求参数错误",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="message", type="string", description="错误信息")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="服务器错误",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="message", type="string", description="错误信息")
     *         )
     *     )
     * )
     *
     * 保存结构化文章
     * 将生成的内容保存为结构化的文章
     * 
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function saveStructuredArticle(Request $request)
    {
        // 获取请求数据
        $requestData = $request->all();
        Log::info("保存结构化文章请求", ['request_data' => $requestData]);
        
        // 如果请求为空，返回错误
        if (empty($requestData)) {
            return $this->response->error('请提供有效的文章数据', 400);
        }
        
        // 检查必须字段
        if (!isset($requestData['article']) || empty($requestData['article'])) {
            return $this->response->error('请提供文章内容', 400);
        }
        
        // 生成缓存键
        $cacheKey = $this->generateCacheKey($requestData);
        
        try {
            // 调用服务保存结构化文章
            $result = $this->articleService->formatAndSaveToStructuredArticle($requestData, $cacheKey);
            
            if (!$result['success']) {
                return $this->response->error($result['message'] ?? '保存失败', 500);
            }
            
            return $this->response->array([
                'success' => true,
                'article_id' => $result['data']['article_id'] ?? null,
                'title' => $result['data']['title'] ?? null,
                'structured' => $result['data']['structured'] ?? true,
                'module_count' => $result['data']['module_count'] ?? 1
            ]);
        } catch (\Exception $e) {
            Log::error("保存结构化文章异常", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cache_key' => $cacheKey
            ]);
            
            return $this->response->error('保存结构化文章失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 验证文章生成请求数据
     * 
     * @param array $requestData 请求数据
     * @return array $errors
     */
    protected function validateArticleRequest(array $requestData): array
    {
        $validationErrors = [];

        // 如果请求为空，返回错误
        if (empty($requestData)) {
            $validationErrors[] = '请提供有效的提示词数据';
        }
        
        // 验证商品数据数组
        if (!isset($requestData['product']) || !is_array($requestData['product']) || empty($requestData['product'])) {
            $validationErrors[] = '商品数据不能为空且必须是数组';
        } 
        
        // 验证文章分类ID
        if (!isset($requestData['category_id'])) {
            $validationErrors[] = '文章分类ID不能为空';
        } elseif (!is_numeric($requestData['category_id'])) {
            $validationErrors[] = '文章分类ID必须是数字';
        }

        // 验证行业类型
        if (!isset($requestData['industry']) || empty($requestData['industry'])) {
            $validationErrors[] = '行业类型不能为空';
        } elseif (!is_string($requestData['industry'])) {
            $validationErrors[] = '行业类型必须是字符串';
        }
        
        // 验证可选字段类型
        
        // 验证是否生成文章
        if (isset($requestData['is_article']) && !is_bool($requestData['is_article']) && $requestData['is_article'] !== 'true' && $requestData['is_article'] !== 'false') {
            $validationErrors[] = 'is_article必须是布尔值';
        }
        
        // 验证是否生成图片
        if (isset($requestData['is_image']) && !is_bool($requestData['is_image']) && $requestData['is_image'] !== 'true' && $requestData['is_image'] !== 'false') {
            $validationErrors[] = 'is_image必须是布尔值';
        }
        
        // 验证作者人设
        if (isset($requestData['author_persona']) && !is_string($requestData['author_persona']) && !is_numeric($requestData['author_persona'])) {
            $validationErrors[] = 'author_persona必须是字符串或数字';
        }
        
        // 验证主题描述
        if (isset($requestData['subject_desc']) && !is_string($requestData['subject_desc']) && !is_numeric($requestData['subject_desc'])) {
            $validationErrors[] = 'subject_desc必须是字符串或数字';
        }
        
        // 验证详细描述(注意原文档中的拼写错误detial_desc)
        if (isset($requestData['detial_desc']) && !is_string($requestData['detial_desc']) && !is_numeric($requestData['detial_desc'])) {
            $validationErrors[] = 'detial_desc必须是字符串或数字';
        }       
        return $validationErrors;
    }
} 