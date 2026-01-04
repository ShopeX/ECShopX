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
use FormBundle\Services\TranscriptService;

class Transcripts extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/transcript",
     *     summary="创建成绩单",
     *     tags={"form"},
     *     description="创建成绩单",
     *     operationId="createTranscript",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_name",
     *         in="query",
     *         description="成绩单名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="query",
     *         description="模板名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_status",
     *         in="query",
     *         description="成绩单状态",
     *         type="boolean",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",@SWG\Property(property="status", type="string")))),),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/FormErrorRespones") ) )
     * )
     */
    public function createTranscript(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $postdata = $request->all();
        $postdata['company_id'] = $companyId;
        if (!$postdata['transcript_name']) {
            return $this->response->error('成绩单名称必填！', 411);
        }
        if (!$postdata['template_name']) {
            return $this->response->error('模板必填', 411);
        }

        $transcriptService = new TranscriptService();

        $result = $transcriptService->create($postdata);

        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/transcript/{transcript_id}",
     *     summary="更新成绩单",
     *     tags={"form"},
     *     description="更新成绩单",
     *     operationId="updateTranscript",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_id",
     *         in="path",
     *         description="成绩单id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_name",
     *         in="query",
     *         description="成绩单名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="query",
     *         description="模板名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_status",
     *         in="query",
     *         description="成绩单状态(启用：on, 禁用：off， 默认off)",
     *         type="boolean",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",@SWG\Property(property="status", type="string")))),),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/FormErrorRespones") ) )
     * )
     */
    public function updateTranscript($transcript_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $postdata = $request->all();
        $postdata['company_id'] = $companyId;
        $postdata['transcript_id'] = $transcript_id;

        $transcriptService = new TranscriptService();
        $result = $transcriptService->update($postdata);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/transcript/{transcript_id}",
     *     summary="获取成绩单",
     *     tags={"from"},
     *     description="获取成绩单",
     *     operationId="getTranscript",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_id",
     *         in="path",
     *         description="成绩单id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",@SWG\Property(property="status", type="string")))),),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/FormErrorRespones") ) )
     * )
     */
    public function getTranscript($transcript_id)
    {
        $companyId = app('auth')->user()->get('company_id');

        $transcriptService = new TranscriptService();
        $result = $transcriptService->getInfo($companyId, $transcript_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/transcript/{transcript_id}",
     *     summary="删除成绩单",
     *     tags={"form"},
     *     description="删除成绩单",
     *     operationId="deleteTranscript",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="transcript_id",
     *         in="path",
     *         description="成绩单id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",@SWG\Property(property="status", type="string")))),),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/FormErrorRespones") ) )
     * )
     */
    public function deleteTranscript($transcript_id)
    {
        $companyId = app('auth')->user()->get('company_id');

        $transcriptService = new TranscriptService();
        $result = $transcriptService->delete($transcript_id);

        return $this->response->array($result);
    }
}
