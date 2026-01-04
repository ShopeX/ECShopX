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

namespace AliyunsmsBundle\Http\Api\V1\Action;

use AliyunsmsBundle\Services\SignService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class Sign extends Controller
{
    /**
     * @SWG\Get(
     *     path="/aliyunsms/sign/list",
     *     summary="短信签名列表",
     *     tags={"阿里短信"},
     *     description="短信签名列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="审核状态:0-审核中;1-审核通过;2-审核失败;", required=false, type="integer"),
     *     @SWG\Parameter( name="sign_name", in="query", description="签名名称", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="签名名称", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="签名名称", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="", description=""),
     *                          @SWG\Property( property="sign_name", type="string", example="", description="签名名称"),
     *                          @SWG\Property( property="sign_source", type="string", example="", description="签名来源"),
     *                          @SWG\Property( property="remark", type="string", example="", description="申请说明"),
     *                          @SWG\Property( property="created", type="string", example="", description="创建时间"),
     *                          @SWG\Property( property="status", type="string", example="", description="审核状态"),
     *                          @SWG\Property( property="reason", type="string", example="", description="审核备注"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 10;
        $signService = new SignService();
        $filter = [];
        if($params['sign_name'] ?? 0) {
            $filter['sign_name|contains'] = $params['sign_name'];
        }
        if(isset($params['status'])) {
            $filter['status'] = $params['status'];
        }
        $filter['company_id'] = $companyId;
        $cols = ['id','company_id','sign_name','sign_source','remark', 'reason', 'status', 'created'];
        $data = $signService->getList($filter, $cols, $page, $pageSize);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/sign/info",
     *     summary="短信签名详情",
     *     tags={"阿里短信"},
     *     description="短信签名详情",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="签名ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="", description=""),
     *                  @SWG\Property( property="sign_name", type="string", example="", description="签名名称"),
     *                  @SWG\Property( property="sign_source", type="string", example="", description="签名来源"),
     *                  @SWG\Property( property="remark", type="string", example="", description="申请说明"),
     *                  @SWG\Property( property="sign_file", type="string", example="", description="资质证明"),
     *                  @SWG\Property( property="delegate_file", type="string", example="", description="委托授权证明"),
     *                  @SWG\Property( property="status", type="string", example="", description="审核状态"),
     *                  @SWG\Property( property="reason", type="string", example="", description="审核备注"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = $request->input('id');
        $signService = new SignService();
        $data = $signService->getInfo(['id' => $id]);
        if ($data) {
            $data['third_party'] = $data['third_party'] == 1 ? 'true' : 'false';
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/sign/add",
     *     summary="添加短信签名",
     *     tags={"阿里短信"},
     *     description="添加短信签名",
     *     operationId="addSign",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="sign_name", in="formData", description="签名名称", required=true, type="string"),
     *     @SWG\Parameter( name="sign_source", in="formData", description="签名来源:0-5", required=true, type="integer"),
     *     @SWG\Parameter( name="remark", in="formData", description="签名申请说明", required=true, type="string"),
     *     @SWG\Parameter( name="sign_file", in="formData", description="资质证明书", required=false, type="string"),
     *     @SWG\Parameter( name="delegate_file", in="formData", description="委托授权书", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function addSign(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'sign_name' => ['required', '签名名称必填'],
            'sign_source' => ['required|integer|min:0|max:5', '签名来源有误'],
            'remark' => ['required', '申请说明必填'],
            'third_party' => ['required|in:true,false', '签名用途必填'],
            'qualification_id' => ['required', '资质ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['sign_name'] = trim($params['sign_name']);
        if( mb_strlen($params['sign_name']) < 2 || mb_strlen($params['sign_name']) > 12) {
            throw new ResourceException('签名有效长度2-12个字符');
        }
        $params['remark'] = trim($params['remark']);
        if(mb_strlen($params['remark']) > 200) {
            throw new ResourceException('申请说明长度不超过200个字符');
        }
        $params['company_id'] = $companyId;
        $params['third_party'] = $params['third_party'] == 'true' ? true : false;
        $signService = new SignService();
        $signService->addSign($params);
        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Post(
     *     path="/aliyunsms/sign/modify",
     *     summary="修改短信签名",
     *     tags={"阿里短信"},
     *     description="修改短信签名",
     *     operationId="modifySign",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="formData", description="签名ID", required=true, type="integer"),
     *     @SWG\Parameter( name="sign_source", in="formData", description="签名来源:0-5", required=true, type="integer"),
     *     @SWG\Parameter( name="remark", in="formData", description="签名申请说明", required=true, type="string"),
     *     @SWG\Parameter( name="sign_file", in="formData", description="资质证明书", required=false, type="string"),
     *     @SWG\Parameter( name="delegate_file", in="formData", description="委托授权书", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function modifySign(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'sign_source' => ['required|integer|min:0|max:5', '签名来源有误'],
            'remark' => ['required', '申请说明必填'],
            'third_party' => ['required|in:true,false', '签名用途必填'],
            'qualification_id' => ['required', '资质ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['remark'] = trim($params['remark']);
        if(mb_strlen($params['remark']) > 200) {
            throw new ResourceException('申请说明长度不超过200个字符');
        }
        $params['company_id'] = $companyId;
        $params['third_party'] = $params['third_party'] == 'true' ? true : false;
        $signService = new SignService();
        $signService->modifySign($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/aliyunsms/sign/delete/{id}",
     *     summary="删除短信签名",
     *     tags={"阿里短信"},
     *     description="删除短信签名",
     *     operationId="deleteSign",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="formData", description="签名ID", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function deleteSign($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        if(!$id) {
            throw new ResourceException('id必填');
        }
        $signService = new SignService();
        $filter['company_id'] = $companyId;
        $filter['id'] = $id;
        $signService->deleteSign($filter);
        return $this->response->array(['status' => true]);
    }

}
