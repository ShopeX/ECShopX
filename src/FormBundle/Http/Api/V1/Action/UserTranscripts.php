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

namespace FormBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as BaseController;
use FormBundle\Services\UserTranscriptService;

class UserTranscripts extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/usertranscript",
     *     summary="创建成绩单",
     *     tags={"form"},
     *     description="创建成绩单",
     *     operationId="createUserTranscript",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id",
     *         in="query",
     *         description="店铺id",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_id",
     *         in="query",
     *         description="成绩单id",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_name",
     *         in="query",
     *         description="成绩单名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="indicator_details",
     *         in="query",
     *         description="指标项详情",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",@SWG\Property(property="status", type="string")))),),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/FormErrorRespones") ) )
     * )
     */
    public function createUserTranscript(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $postdata = $request->all();
        $postdata['company_id'] = $companyId;

        $userTranscriptService = new UserTranscriptService();
        $result = $userTranscriptService->createUserTranscript($postdata);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/usertranscript",
     *     summary="获取成绩单",
     *     tags={"form"},
     *     description="获取成绩单",
     *     operationId="getUserTranscript",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_id",
     *         in="query",
     *         description="成绩单id",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",@SWG\Property(property="status", type="string")))),),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/FormErrorRespones") ) )
     * )
     */
    public function getUserTranscript(Request $request)
    {
        $postdata = $request->input();
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        if (isset($postdata['user_id']) && $postdata['user_id']) {
            $filter['user_id'] = $postdata['user_id'];
        }
        if (isset($postdata['transcript_id']) && $postdata['transcript_id']) {
            $filter['transcript_id'] = $postdata['transcript_id'];
        }

        $userTranscriptService = new UserTranscriptService();
        $result = $userTranscriptService->getUserTranscript($postdata);

        return $this->response->array($result);
    }
}
