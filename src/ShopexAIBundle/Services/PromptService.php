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

class PromptService
{
    // ModuleID: 76fe2a3d
    /**
     * 处理结构化提示词数据为AI生成提示词
     * 
     * @param array $data 请求中的结构化数据
     * @return array 包含文章提示词和图片提示词的数组
     */
    public function buildPrompt(array $data): array
    {
        // 否则，处理结构化数据
        return $this->buildStructuredPrompt($data);
    }

    /**
     * 从结构化数据构建AI生成提示词
     * 
     * @param array $data 结构化提示词数据
     * @return array 包含文章提示词和图片提示词的数组
     */
    protected function buildStructuredPrompt(array $data): array
    {
        error_log("promptserv-29:".print_r($data,1)."\r\n",3,"/tmp/log.txt");
        // 提取公共参数
        $authorPersona = $data['author_persona'] ?? '';
        $industryPresets = $data['industry_presets'] ?? '';
        $subjectDesc = $data['subject_desc'] ?? '';
        $detailedDesc = $data['detailed_desc'] ?? '';
        $industry = $data['industry'] ?? '';
        
        // 标准化产品数据为数组格式
        $products = [];
        if (isset($data['product'])) {
            if (is_array($data['product'])) {
                if (isset($data['product'][0]) && is_array($data['product'][0])) {
                    // 已经是多维产品数组
                    $products = $data['product'];
                    Log::info('检测到多产品数据', ['products_count' => count($products)]);
                } else if (!empty($data['product'])) {
                    // 单个产品对象
                    $products = [$data['product']];
                    Log::info('检测到单个产品数据');
                }
            }
        }
        
        // 如果没有产品数据，创建一个空产品以生成通用提示词
        if (empty($products)) {
            $products = [[]];
            Log::info('未检测到产品数据，使用通用提示词');
        }
        
        // 为每个产品生成单独的提示词
        $prompts = [];
        $imagePrompts = [];
        foreach ($products as $index => $product) {
            $productCategory = $product['category'] ?? '';
            $productName = $product['name'] ?? '';
            $productPrice = $product['price'] ?? ($product['pirce'] ?? ''); // 处理可能的拼写错误
            $productParams = $product['params'] ?? '';
            
            // 构建该产品的文章提示词
            $prompt = $this->buildArticlePromptContent(
                $productCategory, 
                $productName, 
                $productPrice, 
                $productParams, 
                $authorPersona, 
                $industryPresets ?? '', 
                $subjectDesc, 
                $detailedDesc
            );
            
            $prompts[] = $prompt;
            
            // 构建该产品的图片提示词
            $imagePrompts[] = $this->buildImagePromptContent($productName, $productCategory, $product,$industry);
        }
        // 记录生成的提示词数量
        Log::info('生成提示词完成', [
            'prompts_count' => count( $prompts),
            'image_prompts_count' => count($imagePrompts)
        ]);
        
        // 根据产品数量确定返回结果
        if (count($prompts) > 1) {
            // 多产品情况，合并所有产品的提示词
            $combinedPrompt = "我需要你为以下多个产品分别撰写评测内容，请严格按照我指定的格式返回：\n\n";
            foreach ($prompts as $index => $productPrompt) {
                $combinedPrompt .= "产品" . ($index + 1) . "：\n" . $productPrompt . "\n\n";
            }
            
            // 添加要求返回JSON格式的指示
            $combinedPrompt .= "请返回如下JSON格式的结果，不要有任何其他额外输出：\n";
            $combinedPrompt .= "```json\n";
            $combinedPrompt .= "{\n";
            $combinedPrompt .= "  \"products\": [\n";
            
            for ($i = 0; $i < count($prompts); $i++) {
                $combinedPrompt .= "    {\n";
                $combinedPrompt .= "      \"product_id\": " . ($i + 1) . ",\n";
                $combinedPrompt .= "      \"title\": \"产品" . ($i + 1) . "的标题\",\n";
                $combinedPrompt .= "      \"content\": \"产品" . ($i + 1) . "的完整文章内容，可以使用\\n表示换行\",\n";
                $combinedPrompt .= "    }" . ($i < count($prompts) - 1 ? "," : "") . "\n";
            }
            
            $combinedPrompt .= "  ]\n";
            $combinedPrompt .= "}\n";
            $combinedPrompt .= "```\n";
            
            Log::info('生成多产品JSON格式提示词', ['products_count' => count($prompts)]);
            return [
                'prompt' => $combinedPrompt,
                'image_prompt' => $imagePrompts[0] ?? '', // 第一张图片作为主图片
                'image_prompts' => $imagePrompts, // 所有图片提示词
                'multi_product' => true,
                'products_count' => count($prompts)
            ];
        } else {
            // 单个产品情况，直接返回提示词
            Log::info('生成单产品提示词');
            
            return [
                'prompt' => $prompts[0] ?? '',
                'image_prompt' => $imagePrompts[0] ?? '',
                'image_prompts' => $imagePrompts,
                'multi_product' => false,
                'products_count' => 1
            ];
        }
    }
    
    /**
     * 构建文章生成提示词内容
     */
    protected function buildArticlePromptContent(
        string $productCategory, 
        string $productName, 
        string $productPrice, 
        string $productParams, 
        string $authorPersona, 
        string $industryPresets,
        string $subjectDesc, 
        string $detailedDesc
    ): string {
        $prompt = "请撰写一篇商品软文，";
        
        // 添加商品信息
        $prompt .= "商品信息如下：\n";
        if (!empty($productCategory)) {
            $prompt .= "- 商品分类：{$productCategory}\n";
        }
        if (!empty($productName)) {
            $prompt .= "- 商品名称：{$productName}\n";
        }
        if (!empty($productPrice)) {
            $prompt .= "- 商品价格：{$productPrice}\n";
        }
        if (!empty($productParams)) {
            $prompt .= "- 商品参数：{$productParams}\n";
        }
        
        $prompt .= "\n请根据上述商品详细信息以";
        
        // 添加作者人设信息
        if (!empty($authorPersona)) {
            $prompt .= "「{$authorPersona}」";
        } else {
            $prompt .= "专业评测人员";
        }
        
        $prompt .= "的口吻围绕";
        
        // 添加行业预设值
        
        $prompt .= $industryPresets;
        
        
        $prompt .= "，可根据产品的类别选择上述相关条件，写一篇针对对应人群的关于";
        
        // 添加主题描述或详细描述
        if (!empty($subjectDesc)) {
            $prompt .= "「{$subjectDesc}」";
        } elseif (!empty($detailedDesc)) {
            $prompt .= "「{$detailedDesc}」";
        } else {
            $prompt .= "「产品测评」";
        }
                
        // 添加额外要求
        $prompt .= "要求：\n";
        $prompt .= "1. 文章为随笔风格\n";
        $prompt .= "2. 文章需带有趣的表情符号\n";
        $prompt .= "3. 突出产品优势和核心卖点\n";
        $prompt .= "4. 文章长度在600字以内\n";
        $prompt .= "5.请直接返回如下JSON格式的内容，不要有其他额外输出：\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"您的文章标题\",\n";
        $prompt .= "  \"content\": \"完整的文章内容，可以使用\\n表示换行\",\n";
        $prompt .= "}\n";
        $prompt .= "```\n";
        
        return $prompt;
    }
    
    /**
     * 构建图片生成提示词内容
     * 
     * @param string $productName 产品名称
     * @param string $productCategory 产品分类
     * @param array $productData 完整的产品数据（可选）
     * @return string 图片生成提示词
     */
    protected function buildImagePromptContent(string $productName, string $productCategory, array $productData = [], string $industry = ''): array
    {
        $imagePromptArray = [];
        $imagePrompt = '';

        // 根据行业生成基础提示词
        switch ($industry) {
            case '运动服饰':
                $imagePrompt = "模特穿着{$productName}，运动场景，产品特写，干净简单的浅色背景，高清摄影";
                break;
                
            case '护肤':
                $imagePrompt = "模特使用{$productName}，面部特写，肌肤光滑透亮，产品质地展示，纯色背景，高清摄影";
                break;
                
            case '食品':
                $imagePrompt = "{$productName}，精致摆盘，食物特写，自然光线，简约背景，高清摄影";
                break;
                
            case '数码':
                $imagePrompt = "{$productName}，产品展示，细节特写，简约背景，专业打光，高清摄影";
                break;
                
            default:
                $imagePrompt = "{$productName}，产品展示，干净背景，专业打光，高清摄影";
        }

        // 添加通用优化参数
        $imagePrompt .= "，高品质，商业摄影";
        
        // 如果有产品分类，添加相关描述
        if (!empty($productCategory)) {
            $imagePrompt .= "，{$productCategory}风格";
        }

        $imagePromptArray['prompt'] = $imagePrompt;
        $imagePromptArray['ref_image'] = $productData['item_image_url'] ?? '';
        
        return $imagePromptArray;
    }
} 