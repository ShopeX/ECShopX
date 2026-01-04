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

/**
 * ArticleService 服务类
 * 
 * 本类整合了AI文章生成系统中的文章处理逻辑，遵循面向对象设计原则
 */

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use CompanysBundle\Http\Api\V1\Action\ArticleController as CompanyArticleController;
use CompanysBundle\Services\ArticleService as CompanyArticleService;
use ShopexAIBundle\Services\ImageServiceFactory;

class ArticleService
{
    protected $deepseekService;
    protected $imageService;
    protected $redis;

    public function __construct(DeepseekService $deepseekService)
    {
        $this->deepseekService = $deepseekService;
        $this->imageService = ImageServiceFactory::getImageService();
        $this->redis = app('redis');
    }

    /**
     * 非流式生成文章（带超时）
     * @param string $prompt 文章提示词
     * @param array $imagePrompt 图片提示词数组，包含prompt和ref_image
     * @param bool $is_article 是否生成文章内容，默认为true
     * @param bool $is_image 是否生成配图，默认为true
     * @return array
     * @throws \Exception
     */
    public function generateArticleWithTimeout(string $prompt, array $imagePrompt, bool $is_article = true, bool $is_image = true)
    {
        $content = '';
        $imageUrl = '';
        $imageResult = [];
        $jsonData = null;
        
        // 生成文章
        if($is_article){
            $articleResult = $this->deepseekService->generateArticleWithTimeout($prompt);
            
            // 检查是否有解析好的JSON数据
            if (isset($articleResult['json_format']) && $articleResult['json_format'] === true && isset($articleResult['parsed_json'])) {
                $jsonData = $articleResult['parsed_json'];
                // 如果是JSON格式，保留原始内容和解析后的数据
                $content = $articleResult['choices'][0]['message']['content'] ?? '';
            } else {
                // 常规文本格式
                $content = $articleResult['choices'][0]['message']['content'] ?? '';
            }
        }
        
        // 确保无论如何都尝试生成图片(如果is_image=true)
        if($is_image) {
            try {
                // 确保我们记录生成图片的尝试
                Log::info('开始生成图片', ['image_prompt' => $imagePrompt]);
                error_log("services_66:".print_r($imagePrompt['prompt'],1)."\r\n",3,"/tmp/log.txt");
                $imageResult = $this->imageService->generateImage($imagePrompt['prompt'], $imagePrompt['ref_image']);
                $imageUrl = $imageResult['url'] ?? '';
                
                if (!empty($imageUrl)) {
                    Log::info('图片生成成功', ['image_url' => $imageUrl]);
                } else {
                    Log::warning('图片服务返回空URL', ['result' => $imageResult]);
                }
            } catch (\Exception $e) {
                // 记录异常但不中断流程
                Log::error('图片生成异常', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                // 设置默认图片
                $imageUrl = config('shopexai.default_image_url', '');
            }
        }

        $result = [
            'article' => $content ?: [],
            'image' => $imageUrl ?: '',
            'token_count' => $this->getTokenCount(),
            'is_default_image' => $imageResult['is_default'] ?? false,
            'actual_prompt' => $imageResult['actual_prompt'] ?? $imagePrompt['prompt'],
            'task_id' => $imageResult['task_id'] ?? null
        ];
        
        // 如果有JSON格式数据，添加到结果中
        if ($jsonData) {
            $result['json_data'] = $jsonData;
            $result['json_format'] = true;
        }
        
        return $result;
    }

    /**
     * 获取当前token使用量
     * @return int
     */
    public function getTokenCount(): int
    {
        return $this->deepseekService->getTokenCount();
    }

    /**
     * 生成图片
     * @param string $prompt 图片提示词
     * @param string $ref_image 参考图片路径，可选
     * @return array
     */
    public function generateImage(string $prompt, string $ref_image = ''): array
    {
        return $this->imageService->generateImage($prompt, $ref_image);
    }

    /**
     * 获取当前操作员ID
     * 优先使用传入的参数，适用于队列环境
     * 
     * @param int $defaultOperatorId 默认操作员ID
     * @return int 操作员ID
     */
    protected function getCurrentOperatorId($defaultOperatorId = null)
    {
        // 如果提供了默认值，直接使用
        if ($defaultOperatorId !== null) {
            Log::debug('使用传入的默认操作员ID', ['operator_id' => $defaultOperatorId]);
            return $defaultOperatorId;
        }

        try {
            // 检查auth服务是否可用
            if (app()->has('auth') && app('auth')->check()) {
                $user = app('auth')->user();
                if ($user && method_exists($user, 'get')) {
                    $operator_id = $user->get('operator_id');
                    if ($operator_id) {
                        Log::debug('从认证上下文获取操作员ID', ['operator_id' => $operator_id]);
                        return $operator_id;
                    }
                }
            }
        } catch (\Exception $e) {
            // 记录异常但不中断流程
            Log::warning('获取操作员ID失败', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        
        // 默认返回系统用户ID
        Log::debug('无法获取操作员ID，使用默认系统用户ID');
        return 1;
    }
    
    /**
     * 获取当前公司ID
     * 优先使用传入的参数，适用于队列环境
     * 
     * @param int $defaultCompanyId 默认公司ID
     * @return int 公司ID
     */
    protected function getCurrentCompanyId($defaultCompanyId = null)
    {
        // 如果提供了默认值，直接使用
        if ($defaultCompanyId !== null) {
            Log::debug('使用传入的默认公司ID', ['company_id' => $defaultCompanyId]);
            return $defaultCompanyId;
        }
        
        try {
            // 检查auth服务是否可用
            if (app()->has('auth') && app('auth')->check()) {
                $user = app('auth')->user();
                if ($user && method_exists($user, 'get')) {
                    $company_id = $user->get('company_id');
                    if ($company_id) {
                        Log::debug('从认证上下文获取公司ID', ['company_id' => $company_id]);
                        return $company_id;
                    }
                }
            }
        } catch (\Exception $e) {
            // 记录异常但不中断流程
            Log::warning('获取公司ID失败', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        
        // 默认返回系统公司ID
        Log::debug('无法获取公司ID，使用默认系统公司ID');
        return 1;
    }
    
    /**
     * 获取当前分销商ID
     * 优先使用传入的参数，适用于队列环境
     * 
     * @param int $defaultDistributorId 默认分销商ID
     * @return int 分销商ID
     */
    protected function getCurrentDistributorId($defaultDistributorId = null)
    {
        // 如果提供了默认值，直接使用
        if ($defaultDistributorId !== null) {
            Log::debug('使用传入的默认分销商ID', ['distributor_id' => $defaultDistributorId]);
            return $defaultDistributorId;
        }
        
        try {
            // 检查auth服务是否可用
            if (app()->has('auth') && app('auth')->check()) {
                $user = app('auth')->user();
                if ($user && method_exists($user, 'get')) {
                    $distributor_id = $user->get('distributor_id');
                    if ($distributor_id !== null) {
                        Log::debug('从认证上下文获取分销商ID', ['distributor_id' => $distributor_id]);
                        return $distributor_id;
                    }
                }
            }
        } catch (\Exception $e) {
            // 记录异常但不中断流程
            Log::warning('获取分销商ID失败', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        
        // 默认返回0（表示非分销商）
        Log::debug('无法获取分销商ID，使用默认值0');
        return 0;
    }

    /**
     * 重置或恢复EntityManager
     * 处理队列环境中的"The EntityManager is closed"错误
     * 
     * @return void
     */
    protected function resetEntityManager(): void
    {
        try {
            if (app()->has('em')) {
                $em = app('em');
                
                // 检查EntityManager是否关闭
                if (!$em->isOpen()) {
                    Log::info('EntityManager已关闭，正在重置');
                    app()->forgetInstance('em');
                    app('em');
                    Log::info('EntityManager已重置');
                    return;
                }
                
                // 检查连接是否有效
                try {
                    $conn = $em->getConnection();
                    
                    // 如果连接未建立，尝试连接
                    if (!$conn->isConnected()) {
                        Log::info('数据库连接未建立，尝试建立连接');
                        $conn->connect();
                    }
                    
                    // 测试连接是否有效
                    if (!$conn->ping()) {
                        Log::info('数据库连接ping失败，正在重置连接');
                        $conn->close();
                        $conn->connect();
                    }
                } catch (\Exception $connEx) {
                    Log::warning('测试数据库连接时出错，正在重置EntityManager', [
                        'error' => $connEx->getMessage(),
                        'trace' => $connEx->getTraceAsString()
                    ]);
                    
                    app()->forgetInstance('em');
                    app('em');
                }
            }
        } catch (\Exception $e) {
            Log::error('重置EntityManager失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 从请求数据中获取商品信息
     * 
     * @param array $data 请求数据
     * @return array 商品数据数组
     */
    protected function getProductsFromRequest(array $data): array
    {
        $products = [];
        
        // 从product数组中获取商品信息
        if (isset($data['product']) && is_array($data['product'])) {
            Log::info('从product数组中获取商品信息');
            
            // 如果product是关联数组（单个商品），转换为索引数组
            if (isset($data['product']['item_id']) || isset($data['product']['name'])) {
                $data['product'] = [$data['product']];
            } 
            foreach ($data['product'] as $product) {
                // 构建标准化的商品数据结构
                $productData = [
                    'item_id' => $product['item_id'] ?? 0,
                    'item_name' => $product['name'] ?? ($product['title'] ?? ($product['item_name'] ?? '')),
                    'price' => $product['price'] ?? ($product['pirce'] ?? ($product['product_price'] ?? 0)), // 注意处理拼写错误的情况
                    'img_url' => !empty($product['item_image_url']) ? $product['item_image_url'] : 
                              (!empty($product['image_url']) ? $product['image_url'] : 
                              (!empty($product['img_url']) ? $product['img_url'] : '')),
                    'sales' => $product['sales'] ?? '',
                ];
                $products[] = $productData;
                Log::info('从请求获取商品数据', ['item_id' => $productData['item_id'], 'name' => $productData['item_name']]);
            }
        }
        return $products;
    }

    /**
     * 格式化并保存结构化文章，支持单篇文章中的多个产品
     * 
     * @param array $data 文章数据
     * @param string $cacheKey 缓存键
     * @return array 处理结果
     */
    public function formatAndSaveToStructuredArticle(array $data, string $cacheKey = null)
    {
        try {
            // 从生成的内容中提取内容，避免保存整个data数组
            $article = $data['article'];
            $imageUrls = $data['images'] ?? ($data['image'] ? [$data['image']] : []); // 优先使用images数组
            
            // 获取用户信息 - 优先使用传入的数据中的用户信息
            $operatorId = $this->getCurrentOperatorId($data['operator_id'] ?? null);
            $companyId = $this->getCurrentCompanyId($data['company_id'] ?? null);
            $distributorId = $this->getCurrentDistributorId($data['distributor_id'] ?? null);
            
            // 重置EntityManager，解决队列环境中的连接问题
            $this->resetEntityManager();
            
            // 检测文章内容是否为JSON格式
            $jsonData = null;
            $extractedTitle = null;
            $extractedContent = null;
            
            // 优先使用已有的json_data（如果存在）
            if (isset($data['json_data']) && is_array($data['json_data'])) {
                Log::info('使用data中已有的json_data');
                $jsonData = $data['json_data'];
                
                // 直接从json_data中提取标题和内容
                if (isset($jsonData['title']) || isset($jsonData['products'][0]['title'])) {
                    $extractedTitle = $jsonData['title'] ?? $jsonData['products'][0]['title'] ?? 'AI文章';
                }
                if (isset($jsonData['content']) || isset($jsonData['products'][0]['content'])) {
                    $extractedContent = $jsonData['content'] ?? $jsonData['products'][0]['content'];
                }
            } elseif (is_string($article)) {
                // 如果没有json_data，才尝试从article中解析
                // 检查是否为Markdown格式的JSON代码块 - 支持多种格式
                if (preg_match('/```(?:json|javascript)?\s*\n(.*?)\n```/s', $article, $matches)) {
                    try {
                        $jsonString = $matches[1];
                        $jsonData = json_decode($jsonString, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                            Log::info('检测到Markdown中的JSON代码块格式内容');
                            // 提取JSON中的标题和内容
                            if (isset($jsonData['title'])) {
                                $extractedTitle = $jsonData['title'];
                            }
                            if (isset($jsonData['content'])) {
                                $extractedContent = $jsonData['content'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('JSON解析失败', ['error' => $e->getMessage()]);
                    }
                } elseif (preg_match('/(\{.*\})/s', $article, $matches)) {
                    // 检查类似JSON格式的数据，但可能不在代码块中
                    try {
                        $potentialJson = $matches[1];
                        // 尝试解析看起来像JSON的内容
                        $jsonData = json_decode($potentialJson, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                            Log::info('从文本中提取到JSON格式内容');
                            // 从提取的JSON中获取标题和内容
                            if (isset($jsonData['title'])) {
                                $extractedTitle = $jsonData['title'];
                            }
                            if (isset($jsonData['content'])) {
                                $extractedContent = $jsonData['content'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('从文本提取JSON失败', ['error' => $e->getMessage()]);
                    }
                } else {
                    // 直接尝试解析整个文本为JSON
                    try {
                        $jsonData = json_decode($article, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                            Log::info('检测到JSON格式内容');
                            // 提取JSON中的标题和内容
                            if (isset($jsonData['title'])) {
                                $extractedTitle = $jsonData['title'];
                            }
                            if (isset($jsonData['content'])) {
                                $extractedContent = $jsonData['content'];
                            }
                        }
                    } catch (\Exception $e) {
                        // 非JSON格式，无需处理
                    }
                }
            }
            
            // 使用标题，优先级：1.从JSON提取的标题 2.请求中指定的标题 3.默认标题
            $title = $extractedTitle ?? $data['json_data']['products'][0]['title'] ?? 'AI文章-' . date('YmdHis');
            
            // 使用内容，优先级：1.从JSON提取的内容 2.原始内容
            if ($extractedContent) {
                $article = $extractedContent;
            }
            
            // 从请求中获取商品信息
            $products = $this->getProductsFromRequest($data);
            Log::info('从请求中获取到商品数据', ['count' => count($products)]);
            
            // 是否为多产品模式
            $isMultiProduct = $data['multi_product'] ?? (count($products) > 1);
            
            // 构建文章内容数组
            $articleContent = [];
            
            // // 添加文章标题组件
            // $articleContent[] = [
            //     'name' => 'heading',
            //     'base' => [
            //         'title' => $title,
            //         'subtitle' => $data['subtitle'] ?? '',
            //         'padded' => true
            //     ],
            //     'config' => [
            //         'align' => 'center',
            //         'size' => 'h3'
            //     ],
            //     'data' => []
            // ];
            
            // 处理文章结构：为多产品模式构建内容
            if ($isMultiProduct && count($products) > 1) {
                Log::info('使用多产品模式构建文章内容', ['products_count' => count($products)]);
                
                // 检查是否有json_data中的products数组
                $productsData = [];
                if (isset($data['json_data']) && isset($data['json_data']['products']) && is_array($data['json_data']['products'])) {
                    $productsData = $data['json_data']['products'];
                    Log::info('从json_data中获取到products数组', ['products_count' => count($productsData)]);
                } 
                // 兼容单商品结构，将单商品数据转换为products数组格式
                elseif (isset($data['json_data']) && isset($data['json_data']['title']) && (isset($data['json_data']['content']) || isset($data['json_data']['sections']))) {
                    // 将单商品结构转为多商品格式
                    $productsData = [
                        [
                            'product_id' => isset($products[0]) ? ($products[0]['item_id'] ?? 0) : 0,
                            'title' => $data['json_data']['title'],
                            'content' => $data['json_data']['content'] ?? '',
                            'sections' => $data['json_data']['sections'] ?? []
                        ]
                    ];
                    Log::info('将单商品格式转换为products数组格式');
                    
                    // 对于单商品结构，利用product数组补充更多的商品
                    for ($i = 1; $i < count($products); $i++) {
                        $productsData[] = [
                            'product_id' => $products[$i]['item_id'] ?? 0,
                            'title' => $products[$i]['item_name'] ?? ('产品' . ($i + 1)),
                            'content' => $productsData[0]['content'] ?? '', // 复用第一个商品的内容
                            'sections' => $productsData[0]['sections'] ?? [] // 复用第一个商品的区块
                        ];
                    }
                    
                    Log::info('已为其他商品创建额外的内容结构', ['total_products' => count($productsData)]);
                }
                
                // 确保每个商品都有图片
                $productImages = [];
                if (!empty($imageUrls)) {
                    // 如果图片数量与产品数量一致，一对一映射
                    if (count($imageUrls) >= count($products)) {
                        $productImages = $imageUrls;
                    } 
                    // 否则重复使用可用的图片
                    else {
                        foreach ($products as $index => $product) {
                            $imageIndex = $index % count($imageUrls);
                            $productImages[] = $imageUrls[$imageIndex];
                        }
                    }
                }
                // 为每个产品创建组件集合
                foreach ($products as $index => $product) {
                    // 获取产品的json数据
                    $productJsonData = null;
                    if (isset($productsData[$index])) {
                        $productJsonData = $productsData[$index];
                    } else if ($index == 0 && isset($data['json_data'])) {
                        // 兼容旧格式，如果没有找到第一个产品的json数据，但有整体json_data
                        $productJsonData = [
                            'product_id' => $product['item_id'] ?? 0,
                            'title' => $data['json_data']['title'] ?? $product['item_name'] ?? '',
                            'content' => $data['json_data']['content'] ?? '',
                            'sections' => $data['json_data']['sections'] ?? []
                        ];
                    }
                    
                    // 确保有商品标题
                    $productTitle = '';
                    if ($productJsonData && isset($productJsonData['title'])) {
                        $productTitle = $productJsonData['title'];
                    } else {
                        $productTitle = $product['item_name'] ?? ('产品' . ($index + 1));
                    }
                    
                    // 添加产品分隔标题
                    $articleContent[] = [
                        'name' => 'heading',
                        'base' => [
                            'title' => $productTitle,
                            'subtitle' => '',
                            'padded' => true
                        ],
                        'config' => [
                            'align' => 'center',
                            'size' => 'h4'
                        ],
                        'data' => []
                    ];
                    
                    // 添加产品图片slider
                    if (!empty($productImages[$index])) {
                        $sliderData = [
                            [
                                'imgUrl' => $productImages[$index],
                                'linkPage' => '',
                                'content' => '',
                                'title' => $product['item_name'] ?? '',
                                'id' => ''
                            ]
                        ];
                        
                        $articleContent[] = [
                            'name' => 'slider',
                            'base' => [
                                'title' => '',
                                'subtitle' => '',
                                'padded' => true
                            ],
                            'config' => [
                                'current' => 0,
                                'interval' => 3000,
                                'spacing' => 0,
                                'height' => 220,
                                'dot' => true,
                                'dotLocation' => 'right',
                                'dotColor' => 'dark',
                                'shape' => 'circle',
                                'numNavShape' => 'rect',
                                'dotCover' => true,
                                'rounded' => false,
                                'padded' => false,
                                'content' => false
                            ],
                            'data' => $sliderData
                        ];
                    }
                    
                    // 添加产品文字内容
                    $productContent = '';
                    
                    if ($productJsonData) {
                        // 如果有sections，拼接sections内容
                        if (isset($productJsonData['sections']) && is_array($productJsonData['sections'])) {
                            foreach ($productJsonData['sections'] as $section) {
                                if (is_array($section)) {
                                    $sectionTitle = '';
                                    if (isset($section['type'])) {
                                        $typeMapping = [
                                            'introduction' => '引言',
                                            'intro' => '引言',
                                            'main' => '正文',
                                            'body' => '正文',
                                            'feature' => '特点',
                                            'features' => '特点',
                                            'benefit' => '好处',
                                            'benefits' => '好处',
                                            'conclusion' => '结论',
                                            'summary' => '总结'
                                        ];
                                        $sectionTitle = $typeMapping[$section['type']] ?? ucfirst($section['type']);
                                    } else if (isset($section['title'])) {
                                        $sectionTitle = $section['title'];
                                    }
                                    
                                    if (!empty($sectionTitle)) {
                                        $productContent .= "<h4>{$sectionTitle}</h4>\n";
                                    }
                                    
                                    if (isset($section['content'])) {
                                        $productContent .= $section['content'] . "\n\n";
                                    }
                                } else {
                                    $productContent .= $section . "\n\n";
                                }
                            }
                        } 
                        // 否则使用整体内容
                        else if (isset($productJsonData['content'])) {
                            $productContent = $productJsonData['content'];
                        }
                    } 
                    // 如果没有json数据，使用产品名称作为内容
                    else {
                        $productContent = "产品介绍：" . ($product['item_name'] ?? '');
                    }
                    
                    // 添加writing组件
                    if (!empty($productContent)) {
                        $articleContent[] = [
                            'name' => 'writing',
                            'base' => [
                                'title' => '',
                                'subtitle' => '',
                                'padded' => true
                            ],
                            'config' => [
                                'align' => 'left'
                            ],
                            'data' => [
                                [
                                    'content' => $productContent
                                ]
                            ]
                        ];
                    }
                    
                    // 添加单个产品的goods组件
                    $articleContent[] = [
                        'name' => 'goods',
                        'base' => [
                            'title' => '',
                            'subtitle' => '',
                            'padded' => true
                        ],
                        'config' => [
                            'mode' => 'default',
                            'columns' => 1,
                            'rows' => 1,
                            'padding' => [10, 10, 10, 10]
                        ],
                        'data' => [
                            [
                                'distributor_id' => $distributorId,
                                'item_id' => $product['item_id'] ?? 0,
                                'item_name' => $product['item_name'] ?? '',
                                'sales' => $product['sales'] ?? '',
                                'img_url' => $product['img_url'] ?? '',
                                'price' => $product['price'] ?? 0
                            ]
                        ]
                    ];
                }
            }
            // 单产品模式
            else {
                // 添加图片轮播组件
                if (!empty($imageUrls)) {
                    $sliderData = [];
                    foreach ($imageUrls as $imgUrl) {
                        $sliderData[] = [
                            'imgUrl' => $imgUrl,
                            'linkPage' => '',
                            'content' => '',
                            'title' => '',
                            'id' => ''
                        ];
                    }
                    
                    $articleContent[] = [
                        'name' => 'slider',
                        'base' => [
                            'title' => '',
                            'subtitle' => '',
                            'padded' => true
                        ],
                        'config' => [
                            'current' => 0,
                            'interval' => 3000,
                            'spacing' => 0,
                            'height' => 200,
                            'dot' => true,
                            'dotLocation' => 'right',
                            'dotColor' => 'dark',
                            'shape' => 'circle',
                            'numNavShape' => 'rect',
                            'dotCover' => true,
                            'rounded' => false,
                            'padded' => false,
                            'content' => false
                        ],
                        'data' => $sliderData
                    ];
                }
                
                // 准备文章内容
                $combinedContent = '';
                $sections = [];
                
                // 检查是否有JSON格式的sections
                if (is_array($jsonData) && isset($jsonData['sections']) && is_array($jsonData['sections'])) {
                    $sections = $jsonData['sections'];
                    
                    // 提取所有sections中的内容并合并
                    Log::info('从JSON sections中合并内容', ['sections_count' => count($sections)]);
                    
                    foreach ($sections as $index => $section) {
                        $sectionTitle = '';
                        $sectionContent = '';
                        
                        // 提取标题(如果有)
                        if (is_array($section)) {
                            if (isset($section['title']) && !empty($section['title'])) {
                                $sectionTitle = $section['title'];
                            } elseif (isset($section['type']) && !empty($section['type'])) {
                                // 将类型转换为标题
                                $typeMapping = [
                                    'introduction' => '引言',
                                    'intro' => '引言',
                                    'main' => '正文',
                                    'body' => '正文',
                                    'feature' => '特点',
                                    'features' => '特点',
                                    'benefit' => '好处',
                                    'benefits' => '好处',
                                    'conclusion' => '结论',
                                    'summary' => '总结'
                                ];
                                $sectionTitle = $typeMapping[$section['type']] ?? ucfirst($section['type']);
                            }
                            
                            // 提取内容
                            $sectionContent = $section['content'] ?? '';
                        } else {
                            $sectionContent = $section;
                        }
                        
                        // 将标题和内容添加到合并内容中
                        if (!empty($sectionTitle)) {
                            $combinedContent .= "<h4>{$sectionTitle}</h4>\n";
                        }
                        
                        if (!empty($sectionContent)) {
                            $combinedContent .= is_string($sectionContent) ? $sectionContent : json_encode($sectionContent);
                            $combinedContent .= "\n\n"; // 段落间添加空行
                        }
                    }
                } else if (!empty($article)) {
                    // 如果没有sections但有文章内容，直接使用
                    $combinedContent = is_string($article) ? $article : json_encode($article);
                }
                
                // 添加单个writing组件包含所有内容
                if (!empty($combinedContent)) {
                    $articleContent[] = [
                        'name' => 'writing',
                        'base' => [
                            'title' => '',
                            'subtitle' => '',
                            'padded' => true
                        ],
                        'config' => [
                            'align' => 'left'
                        ],
                        'data' => [
                            [
                                'content' => $combinedContent
                            ]
                        ]
                    ];
                }
                
                // 添加商品组件（如果有商品数据）
                if (!empty($products)) {
                    // 使用统一处理过的产品信息来创建商品组件
                    $goodsItems = [];
                    
                    foreach ($products as $product) {
                        $goodsItems[] = [
                            'distributor_id' => $distributorId,
                            'item_id' => $product['item_id'] ?? 0,
                            'item_name' => $product['item_name'] ?? '',
                            'sales' => $product['sales'] ?? '',
                            'img_url' => $product['img_url'] ?? '',
                            'price' => $product['price'] ?? 0
                        ];
                    }
                    
                    // 将商品组件添加到文章内容中
                    $articleContent[] = [
                        'name' => 'goods',
                        'base' => [
                            'title' => '推荐产品',
                            'subtitle' => '',
                            'padded' => true
                        ],
                        'config' => [
                            'mode' => 'default',
                            'columns' => count($goodsItems) >= 3 ? 3 : (count($goodsItems) == 2 ? 2 : 1),
                            'rows' => 1,
                            'padding' => [10, 10, 10, 10]
                        ],
                        'data' => $goodsItems
                    ];
                }
            }
            
            // 构建创建文章的请求数据
            $requestData = [
                'title' => $title,
                'subtitle' => $data['subtitle'] ?? '',
                'category_id' => $data['category_id'],
                'article_type' => 'bring',
                'content' => $articleContent,
                'author' => '',
                'is_ai' => 1,
                'release_status' => 0,
                'image_url' => $data['image'] ?? ($data['images'][0] ?? ''),
                'meta_title' => $title,
                'meta_keywords' => "",
                'meta_description' => "",
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'operator_id' => $operatorId,
            ];
            
            Log::info('尝试保存结构化文章', [
                'title' => $requestData['title'],
                'components_count' => count($requestData['content']),
            ]);
            
            // 使用CompanyArticleService直接保存，避免通过Controller层
            $articleService = app(CompanyArticleService::class);
            
            // 调用保存方法
            try {
                $saveResult = $articleService->create($requestData);
                
                // 检查响应状态
                if (!empty($saveResult) && !empty($saveResult['article_id'])) {
                    Log::info('结构化文章保存成功', ['article_id' => $saveResult['article_id']]);
                
                // 如果提供了缓存键，记录处理状态
                if ($cacheKey !== null && isset($saveResult['article_id'])) {
                    $this->redis->setex('article_processed:' . $cacheKey, 86400, $saveResult['article_id']);
                }
                
                return [
                    'success' => true,
                    'data' => [
                            'article_id' => $saveResult['article_id'],
                            'title' => $title
                        ]
                    ];
                } else {
                    Log::error('结构化文章保存失败', [
                        'error' => json_encode($saveResult) ?: '未知错误'
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => '保存失败：未能获取到文章ID'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('结构化文章保存失败', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return [
                    'success' => false,
                    'message' => '保存异常：' . $e->getMessage()
                ];
            }
        } catch (\Exception $e) {
            Log::error('格式化并保存结构化文章失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '格式化并保存结构化文章失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 生成并处理文章内容（包括图片处理和保存）
     * 
     * @param array $requestData 请求数据
     * @param string $prompt 文章提示词
     * @param array $imagePrompt 主图提示词数组，包含prompt和ref_image
     * @param array $imagePrompts 多图提示词数组
     * @param bool $isMultiProduct 是否多产品
     * @param int $productsCount 产品数量
     * @param string $cacheKey 缓存键，用于防止重复处理
     * @param bool $isFromQueue 是否来自队列处理
     * @return array 处理结果
     * @throws \Exception
     */
    public function generateAndProcessArticle(
        array $requestData, 
        string $prompt, 
        array $imagePrompt, 
        array $imagePrompts = [], 
        bool $isMultiProduct = false, 
        int $productsCount = 1, 
        string $cacheKey = null,
        bool $isFromQueue = false
    ): array {
        // 标记日志来源
        $logSource = $isFromQueue ? '队列任务' : '同步处理';
        
        // 如果是队列任务，重置EntityManager
        if ($isFromQueue) {
            $this->resetEntityManager();
        }
        
        // 获取内容生成选项
        $is_article = $requestData['is_article'] ?? true;
        $is_image = $requestData['is_image'] ?? true;
        
        // 记录任务开始
        Log::info("AI文章生成{$logSource}开始", [
            'is_article' => $is_article,
            'is_image' => $is_image,
            'multi_product' => $isMultiProduct,
            'products_count' => $productsCount,
            'cache_key' => $cacheKey,
            'operator_id' => $requestData['operator_id'] ?? null,
            'company_id' => $requestData['company_id'] ?? null
        ]);
        
        // 生成内容
        $result = $this->generateArticleWithTimeout($prompt, $imagePrompt, $is_article, $is_image);
        
        // 补充结果中的image_prompts字段
        $result['image_prompts'] = $imagePrompts ?: [$imagePrompt];
        $result['multi_product'] = $isMultiProduct;
        $result['products_count'] = $productsCount;
        
        // 如果有多个图片提示词且需要生成图片，为每个产品生成图片
        if ($is_image && $isMultiProduct && count($imagePrompts) > 1) {
            Log::info("{$logSource}: 检测到多产品文章，开始为每个产品生成图片", [
                'image_prompts_count' => count($imagePrompts),
                'products_count' => $productsCount
            ]);
            
            $images = [];
            $defaultImageUrl = config('shopexai.default_image_url', 'https://img.alicdn.com/imgextra/i4/O1CN01c26iB51CGdiWJA4L3_!!6000000000564-2-tps-818-404.png');
            
            // 记录第一个图片，它已在前面生成
            $images[] = $result['image'] ?: $defaultImageUrl;
            
            // 限制同时生成的图片数量，避免超时
            $maxImages = min(count($imagePrompts), $isFromQueue ? 5 : 3); // 队列环境可以生成更多图片
            
            // 从第2个开始生成其他图片
            for ($i = 1; $i < $maxImages; $i++) {
                try {
                    if (empty($imagePrompts[$i]) || empty($imagePrompts[$i]['prompt'])) {
                        $images[] = $defaultImageUrl;
                        Log::warning("{$logSource}: 第" . ($i+1) . "个产品图片提示词为空，使用默认图片");
                        continue;
                    }
                    
                    // 确保ref_image字段存在
                    $ref_image = $imagePrompts[$i]['ref_image'] ?? '';
                    
                    Log::info("{$logSource}: 开始生成第" . ($i+1) . "个产品图片", ['prompt' => $imagePrompts[$i]['prompt']]);
                    $additionalImageResult = $this->generateImage($imagePrompts[$i]['prompt'], $ref_image);
                    
                    if (!empty($additionalImageResult['url'])) {
                        $images[] = $additionalImageResult['url'];
                        Log::info("{$logSource}: 第" . ($i+1) . "个产品图片生成成功", ['url' => $additionalImageResult['url']]);
                    } else {
                        // 使用默认图片或第一张图片作为备选
                        $fallbackImage = !empty($result['image']) ? $result['image'] : $defaultImageUrl;
                        $images[] = $fallbackImage;
                        Log::warning("{$logSource}: 第" . ($i+1) . "个产品图片生成失败，使用备选图片", [
                            'error' => $additionalImageResult['error'] ?? '未知错误',
                            'fallback_image' => $fallbackImage
                        ]);
                    }
                } catch (\Exception $e) {
                    // 图片生成异常，使用默认图片或第一张图片
                    $fallbackImage = !empty($result['image']) ? $result['image'] : $defaultImageUrl;
                    $images[] = $fallbackImage;
                    Log::error("{$logSource}: 第" . ($i+1) . "个产品图片生成异常", [
                        'error' => $e->getMessage(),
                        'fallback_image' => $fallbackImage
                    ]);
                }
                
                // 为了避免API限流，每次生成图片后稍作延迟
                if ($i < $maxImages - 1) { // 避免最后一次不必要的延迟
                    usleep($isFromQueue ? 300000 : 500000); // 队列环境可以使用更短的延迟
                }
            }
            
            // 确保图片数量与产品数量一致
            while (count($images) < $productsCount) {
                // 如果图片不够，使用之前生成的图片或默认图片补齐
                $fallbackImage = !empty($images[0]) ? $images[0] : $defaultImageUrl;
                $images[] = $fallbackImage;
                Log::warning("{$logSource}: 产品图片数量不足，使用备选图片补充", ['fallback_image' => $fallbackImage]);
            }
            
            // 更新结果中的图片数组
            $result['image'] = $images[0]; // 保持向后兼容
            $result['images'] = $images;   // 所有图片的数组
            Log::info("{$logSource}: 所有产品图片生成完成", ['images_count' => count($images)]);
        } else if ($is_image) {
            // 单个图片情况，保持向后兼容
            $result['images'] = [$result['image']];
            Log::info("{$logSource}: 单产品图片生成完成", ['image' => $result['image']]);
        }
        
        // 处理图片生成失败的情况
        if ($is_image && empty($result['image'])) {
            // 设置默认图片URL
            $defaultImageUrl = config('shopexai.default_image_url', 'https://img.alicdn.com/imgextra/i4/O1CN01c26iB51CGdiWJA4L3_!!6000000000564-2-tps-818-404.png');
            $result['image'] = $defaultImageUrl;
            $result['images'] = array_fill(0, $productsCount, $defaultImageUrl);
            $result['is_default_image'] = true;
            $result['image_error'] = '图片生成失败，使用默认图片';
            Log::warning("{$logSource}: 主图片生成失败，使用默认图片", ['cache_key' => $cacheKey]);
        }
        
        // 在保存文章前处理json_data的products结构
        // 如果json_data不包含products字段，但是是多商品模式，需要转换格式
        if ($isMultiProduct && isset($result['json_data']) && !isset($result['json_data']['products'])) {
            // 将单商品结构转为多商品结构
            $products = $requestData['product'] ?? [];
            
            if (!is_array($products)) {
                $products = [$products];
            } else if (isset($products['item_id'])) {
                // 如果是单个关联数组，转换为索引数组
                $products = [$products];
            }
            
            $convertedJson = [
                'products' => []
            ];
            
            // 添加第一个产品（使用原json_data内容）
            $convertedJson['products'][] = [
                'product_id' => $products[0]['item_id'] ?? 0,
                'title' => $result['json_data']['title'] ?? '',
                'content' => $result['json_data']['content'] ?? '',
                'sections' => $result['json_data']['sections'] ?? []
            ];
            
            // 添加其他产品（复用第一个产品的内容）
            for ($i = 1; $i < count($products); $i++) {
                $convertedJson['products'][] = [
                    'product_id' => $products[$i]['item_id'] ?? 0,
                    'title' => $products[$i]['name'] ?? $products[$i]['item_name'] ?? ('产品' . ($i + 1)),
                    'content' => $convertedJson['products'][0]['content'],
                    'sections' => $convertedJson['products'][0]['sections']
                ];
            }
            
            // 更新结果中的json_data
            $result['json_data'] = $convertedJson;
            Log::info("{$logSource}: 已将单商品JSON结构转换为多商品结构", [
                'products_count' => count($convertedJson['products'])
            ]);
        }
        
        // 如果文章内容不为空且提供了缓存键，保存到文章系统
        if ($cacheKey && isset($result['article']) && !empty($result['article'])) {
            // 检查是否已经处理过该请求
            $processedFlag = $this->redis->get('article_processed:' . $cacheKey);
            if (!$processedFlag) {
                try {
                    Log::info("{$logSource}: 开始保存AI生成的文章", [
                        'cache_key' => $cacheKey,
                        'images_count' => count($result['images'] ?? []),
                        'multi_product' => $isMultiProduct
                    ]);
                    
                    // 如果是队列任务，确保EntityManager连接正常
                    if ($isFromQueue) {
                        $this->resetEntityManager();
                    }
                    
                    // 始终使用结构化文章方法保存
                    $saveResult = $this->formatAndSaveToStructuredArticle(array_merge($result, $requestData), $cacheKey);
                    
                    if ($saveResult['success'] && isset($saveResult['data']['article_id'])) {
                        $this->redis->setex('article_processed:' . $cacheKey, 86400, $saveResult['data']['article_id']); // 24小时有效期
                        $result['article_id'] = $saveResult['data']['article_id'];
                        Log::info("{$logSource}: AI文章保存成功", [
                            'article_id' => $saveResult['data']['article_id'],
                            'cache_key' => $cacheKey,
                            'operator_id' => $requestData['operator_id'] ?? 'unknown',
                            'company_id' => $requestData['company_id'] ?? 'unknown'
                        ]);
                    } else {
                        $result['save_error'] = $saveResult['message'] ?? '保存文章失败，未知原因';
                        Log::error("{$logSource}: AI文章保存失败", [
                            'error' => $saveResult['message'] ?? '未知原因',
                            'cache_key' => $cacheKey
                        ]);
                    }
                } catch (\Exception $e) {
                    $result['save_error'] = '保存文章异常: ' . $e->getMessage();
                    Log::error("{$logSource}: AI文章保存异常", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'cache_key' => $cacheKey
                    ]);
                }
            } else {
                $result['article_id'] = $processedFlag;
                Log::info("{$logSource}: AI文章已处理过", ['article_id' => $processedFlag, 'cache_key' => $cacheKey]);
            }
        }
        
        return $result;
    }
} 