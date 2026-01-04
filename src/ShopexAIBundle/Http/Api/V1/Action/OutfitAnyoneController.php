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

use Illuminate\Http\Request;
use ShopexAIBundle\Services\OutfitAnyoneService;
use ShopexAIBundle\Jobs\GenerateOutfitJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use ShopexAIBundle\Services\MemberOutfitService;
use ShopexAIBundle\Models\MemberOutfitLog;

class OutfitAnyoneController
{
    /**
     * @var OutfitAnyoneService
     */
    protected $outfitService;

    /**
     * @var MemberOutfitService
     */
    protected $memberOutfitService;

    /**
     * 构造函数
     *
     * @param OutfitAnyoneService $outfitService
     * @param MemberOutfitService $memberOutfitService
     */
    public function __construct(OutfitAnyoneService $outfitService, MemberOutfitService $memberOutfitService)
    {
        $this->outfitService = $outfitService;
        $this->memberOutfitService = $memberOutfitService;
    }

    /**
     * @SWG\Post(
     *     path="/outfit/generate",
     *     summary="生成虚拟试衣图片",
     *     tags={"AI虚拟试衣"},
     *     description="生成虚拟试衣图片，支持直接生成和异步生成两种模式",
     *     operationId="generateOutfit",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="model_id",
     *         in="formData",
     *         description="模特ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="formData",
     *         description="商品ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="top_garment_url",
     *         in="formData",
     *         description="上衣图片URL（与bottom_garment_url至少需要提供一个）",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="bottom_garment_url",
     *         in="formData",
     *         description="下装图片URL（与top_garment_url至少需要提供一个）",
     *         required=false,
     *         type="string"
     *     ),

     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200, description="响应状态码"),
     *             @SWG\Property(property="message", type="string", example="虚拟试衣生成成功", description="响应消息"),
     *             @SWG\Property(property="data", type="object", description="响应数据",
     *                 @SWG\Property(property="url", type="string", description="生成的图片URL"),
     *                 @SWG\Property(property="is_default", type="boolean", description="是否为默认图片"),
     *                 @SWG\Property(property="model", type="string", description="使用的AI模型"),
     *                 @SWG\Property(property="task_id", type="string", description="任务ID（异步模式）"),
     *                 @SWG\Property(property="cache_key", type="string", description="缓存键（异步模式）")
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="请求参数错误",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=400, description="错误状态码"),
     *             @SWG\Property(property="message", type="string", description="错误信息")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="错误返回结构",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/ShopexAIErrorResponse")
     *         )
     *     )
     * )
     *
     * 生成虚拟试衣图片（支持直接生成和异步生成）
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        echo "<pre>";
        print_r($request->all());
        exit;
        try {
            // 验证请求参数
            $validatedData = $this->validateParams($request, [
                'model_id' => 'required|integer',
                'item_id' => 'nullable|integer',
                'top_garment_url' => 'required_without:bottom_garment_url|url',
                'bottom_garment_url' => 'required_without:top_garment_url|url'
            ]);
            
            if (isset($validatedData['error'])) {
                return response()->json([
                    'code' => 400,
                    'message' => $validatedData['error']
                ], 400);
            }
            
            // 获取请求参数
            $modelId = $validatedData['model_id'];
            $itemId = $validatedData['item_id'] ?? null;
            $topGarmentUrl = $validatedData['top_garment_url'] ?? '';
            $bottomGarmentUrl = $validatedData['bottom_garment_url'] ?? '';
            
            // 获取会员ID
            $memberId = app('auth')->user()->member_id;
            
            // 创建试衣记录
            $outfitLog = $this->memberOutfitService->createOutfitLog(
                $memberId,
                $modelId,
                $itemId,
                $topGarmentUrl,
                $bottomGarmentUrl
            );
            
            // 从配置中获取是否使用队列
            $useQueue = config('shopexai.outfit.use_queue', true);
            
            // 根据配置决定是否使用队列
            if ($useQueue) {
                return $this->generateWithQueue($request, $outfitLog);
            } else {
                return $this->generateDirect($request, $outfitLog);
            }
        } catch (\Exception $e) {
            Log::error('虚拟试衣生成失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 直接生成虚拟试衣图片
     *
     * @param Request $request
     * @param MemberOutfitLog $outfitLog
     * @return \Illuminate\Http\JsonResponse
     */
    private function generateDirect(Request $request, $outfitLog)
    {
        try {
            Log::info('开始直接生成虚拟试衣图片', [
                'person_image' => $outfitLog->getModel()->getModelImage(),
                'top_garment' => $outfitLog->getTopGarmentUrl(),
                'has_bottom_garment' => !empty($outfitLog->getBottomGarmentUrl())
            ]);
            
            // 调用服务生成图片
            $result = $this->outfitService->generateOutfit(
                $outfitLog->getModel()->getModelImage(),
                $outfitLog->getTopGarmentUrl(),
                $outfitLog->getBottomGarmentUrl()
            );
            
            // 处理生成结果
            if ($result['is_default'] ?? false) {
                // 生成失败，更新记录状态
                $this->memberOutfitService->updateOutfitLog(
                    $outfitLog->getRequestId(),
                    $result['url'],
                    2 // 失败状态
                );
                
                return response()->json([
                    'code' => 200,
                    'data' => [
                        'url' => $result['url'],
                        'is_default' => true,
                        'request_id' => $outfitLog->getRequestId()
                    ],
                    'message' => $result['error'] ?? '虚拟试衣生成失败'
                ]);
            }
            
            // 生成成功，更新记录状态
            $this->memberOutfitService->updateOutfitLog(
                $outfitLog->getRequestId(),
                $result['url'],
                1 // 成功状态
            );
            
            return response()->json([
                'code' => 200,
                'data' => [
                    'url' => $result['url'],
                    'is_default' => false,
                    'model' => $result['model'],
                    'request_id' => $outfitLog->getRequestId()
                ],
                'message' => '虚拟试衣生成成功'
            ]);
        } catch (\Exception $e) {
            Log::error('虚拟试衣直接生成失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * 使用队列异步生成虚拟试衣图片
     *
     * @param Request $request
     * @param MemberOutfitLog $outfitLog
     * @return \Illuminate\Http\JsonResponse
     */
    private function generateWithQueue(Request $request, $outfitLog)
    {
        try {
            // 获取当前用户信息
            $companyId = app('auth')->user()->company_id ?? 1;
            $operatorId = app('auth')->user()->operator_id ?? 1;
            $distributorId = app('auth')->user()->distributor_id ?? 0;
            
            // 设置初始状态到Redis
            Redis::setex($outfitLog->getRequestId(), 3600, json_encode([
                'status' => 'pending',
                'created_at' => Carbon::now()->toDateTimeString(),
                'job_completed' => false
            ]));
            
            Log::info('创建虚拟试衣异步任务', [
                'request_id' => $outfitLog->getRequestId(),
                'person_image' => $outfitLog->getModel()->getModelImage(),
                'has_bottom_garment' => !empty($outfitLog->getBottomGarmentUrl())
            ]);
            
            // 创建队列任务
            Queue::push(new GenerateOutfitJob(
                $outfitLog->getModel()->getModelImage(),
                $outfitLog->getTopGarmentUrl(),
                $outfitLog->getBottomGarmentUrl(),
                $outfitLog->getRequestId(),
                3600,
                $companyId,
                $operatorId,
                $distributorId
            ));
            
            // 返回任务ID
            return response()->json([
                'code' => 200,
                'data' => [
                    'request_id' => $outfitLog->getRequestId()
                ],
                'message' => '虚拟试衣任务已创建，请使用任务ID查询结果'
            ]);
        } catch (\Exception $e) {
            Log::error('创建虚拟试衣异步任务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * @SWG\Get(
     *     path="/outfit/status/{task_id}",
     *     summary="查询虚拟试衣任务状态",
     *     tags={"AI虚拟试衣"},
     *     description="查询异步生成的虚拟试衣任务状态和结果",
     *     operationId="checkOutfitStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="task_id",
     *         in="path",
     *         description="任务ID",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200, description="响应状态码"),
     *             @SWG\Property(property="message", type="string", example="任务已完成", description="响应消息"),
     *             @SWG\Property(property="data", type="object", description="响应数据",
     *                 @SWG\Property(property="status", type="string", enum={"processing", "completed", "failed"}, description="任务状态"),
     *                 @SWG\Property(property="url", type="string", description="生成的图片URL"),
     *                 @SWG\Property(property="is_default", type="boolean", description="是否为默认图片"),
     *                 @SWG\Property(property="model", type="string", description="使用的AI模型"),
     *                 @SWG\Property(property="created_at", type="string", description="任务创建时间"),
     *                 @SWG\Property(property="completed_at", type="string", description="任务完成时间")
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="任务不存在",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=404, description="错误状态码"),
     *             @SWG\Property(property="message", type="string", example="任务不存在或已过期", description="错误信息")
     *         )
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="错误返回结构",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/ShopexAIErrorResponse")
     *         )
     *     )
     * )
     *
     * 查询任务状态
     *
     * @param Request $request
     * @param string $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request, string $taskId)
    {
        try {
            $cacheKey = 'outfit_' . $taskId;
            
            // 从Redis获取任务状态
            $cached = Redis::get($cacheKey);
            
            if (!$cached) {
                return response()->json([
                    'code' => 404,
                    'message' => '任务不存在或已过期'
                ], 404);
            }
            
            $result = json_decode($cached, true);
            
            // 如果任务未完成
            if (!isset($result['job_completed']) || !$result['job_completed']) {
                return response()->json([
                    'code' => 200,
                    'data' => [
                        'status' => 'processing',
                        'created_at' => $result['created_at'] ?? Carbon::now()->toDateTimeString()
                    ],
                    'message' => '任务正在处理中'
                ]);
            }
            
            // 如果任务有错误
            if (isset($result['error']) && $result['error']) {
                return response()->json([
                    'code' => 200,
                    'data' => [
                        'status' => 'failed',
                        'url' => $result['url'] ?? null,
                        'is_default' => true,
                        'created_at' => $result['created_at'] ?? null,
                        'completed_at' => $result['completed_at'] ?? null
                    ],
                    'message' => $result['message'] ?? '任务执行失败'
                ]);
            }
            
            // 任务成功
            return response()->json([
                'code' => 200,
                'data' => [
                    'status' => 'completed',
                    'url' => $result['url'],
                    'is_default' => $result['is_default'] ?? false,
                    'model' => $result['model'] ?? null,
                    'created_at' => $result['created_at'] ?? null,
                    'completed_at' => $result['completed_at'] ?? null
                ],
                'message' => '任务已完成'
            ]);
        } catch (\Exception $e) {
            Log::error('查询虚拟试衣任务状态失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => '服务器内部错误: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 验证请求参数
     *
     * @param Request $request
     * @param array $rules 验证规则
     * @return array
     */
    private function validateParams(Request $request, array $rules): array
    {
        try {
            // 使用Laravel的Validator进行参数验证
            $validator = Validator::make($request->all(), $rules, [
                'model_id.required' => '模特ID不能为空',
                'model_id.integer' => '模特ID必须是整数',
                'item_id.integer' => '商品ID必须是整数',
                'top_garment_url.required_without' => '上衣图片URL和下装图片URL至少需要提供一个',
                'top_garment_url.url' => '上衣图片URL格式不正确',
                'bottom_garment_url.required_without' => '上衣图片URL和下装图片URL至少需要提供一个',
                'bottom_garment_url.url' => '下装图片URL格式不正确'
            ]);

            if ($validator->fails()) {
                return ['error' => $validator->errors()->first()];
            }

            // 返回验证通过的数据
            return $validator->validated();
        } catch (\Exception $e) {
            Log::error('参数验证失败', [
                'error' => $e->getMessage(),
                'rules' => $rules,
                'input' => $request->all()
            ]);
            
            return ['error' => '参数验证失败: ' . $e->getMessage()];
        }
    }
} 