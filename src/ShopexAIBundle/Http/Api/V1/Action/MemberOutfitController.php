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
use ShopexAIBundle\Services\MemberOutfitService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MemberOutfitController
{
    /**
     * @var MemberOutfitService
     */
    protected $memberOutfitService;

    public function __construct(MemberOutfitService $memberOutfitService)
    {
        $this->memberOutfitService = $memberOutfitService;
    }

    /**
     * @SWG\Post(
     *     path="/member/outfit/model",
     *     summary="创建会员模特",
     *     tags={"会员模特管理"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="model_image",
     *         in="formData",
     *         description="模特图片URL",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200),
     *             @SWG\Property(property="message", type="string"),
     *             @SWG\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function createModel(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'model_image' => 'required|url'
            ], [
                'model_image.required' => '模特图片不能为空',
                'model_image.url' => '模特图片URL格式不正确'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $memberId = app('auth')->user()->member_id;
            $modelImage = $request->input('model_image');

            $outfit = $this->memberOutfitService->createMemberOutfit($memberId, $modelImage);

            return response()->json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $outfit
            ]);
        } catch (\Exception $e) {
            Log::error('创建会员模特失败', [
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
     * @SWG\Put(
     *     path="/member/outfit/model/{id}",
     *     summary="更新会员模特",
     *     tags={"会员模特管理"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="模特ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="model_image",
     *         in="formData",
     *         description="模特图片URL",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200),
     *             @SWG\Property(property="message", type="string"),
     *             @SWG\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function updateModel(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'model_image' => 'required|url'
            ], [
                'model_image.required' => '模特图片不能为空',
                'model_image.url' => '模特图片URL格式不正确'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $memberId = app('auth')->user()->member_id;
            $modelImage = $request->input('model_image');

            $outfit = $this->memberOutfitService->updateMemberOutfit($memberId, $id, $modelImage);

            return response()->json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $outfit
            ]);
        } catch (\Exception $e) {
            Log::error('更新会员模特失败', [
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
     * @SWG\Delete(
     *     path="/member/outfit/model/{id}",
     *     summary="删除会员模特",
     *     tags={"会员模特管理"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="模特ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200),
     *             @SWG\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function deleteModel(Request $request, $id)
    {
        try {
            $memberId = app('auth')->user()->member_id;

            $this->memberOutfitService->deleteMemberOutfit($memberId, $id);

            return response()->json([
                'code' => 200,
                'message' => '删除成功'
            ]);
        } catch (\Exception $e) {
            Log::error('删除会员模特失败', [
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
     * @SWG\Get(
     *     path="/member/outfit/models",
     *     summary="获取会员模特列表",
     *     tags={"会员模特管理"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200),
     *             @SWG\Property(property="message", type="string"),
     *             @SWG\Property(property="data", type="array", @SWG\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getModels(Request $request)
    {
        try {
            $memberId = app('auth')->user()->member_id;

            $outfits = $this->memberOutfitService->getMemberOutfits($memberId);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $outfits
            ]);
        } catch (\Exception $e) {
            Log::error('获取会员模特列表失败', [
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
     * @SWG\Get(
     *     path="/member/outfit/logs",
     *     summary="获取会员试衣记录",
     *     tags={"会员模特管理"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="每页数量",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="code", type="integer", example=200),
     *             @SWG\Property(property="message", type="string"),
     *             @SWG\Property(property="data", type="object",
     *                 @SWG\Property(property="total", type="integer"),
     *                 @SWG\Property(property="page", type="integer"),
     *                 @SWG\Property(property="limit", type="integer"),
     *                 @SWG\Property(property="list", type="array", @SWG\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function getLogs(Request $request)
    {
        try {
            $memberId = app('auth')->user()->member_id;
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            $result = $this->memberOutfitService->getMemberOutfitLogs($memberId, $page, $limit);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('获取会员试衣记录失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 