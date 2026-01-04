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

use AliyunsmsBundle\Services\SceneService;
use AliyunsmsBundle\Services\SettingService;
use AliyunsmsBundle\Services\SettingServService;
use AliyunsmsBundle\Services\TemplateService;
use CrossBorderBundle\Services\Set;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class Template extends Controller
{
    /**
     * @SWG\Get(
     *     path="/aliyunsms/template/list",
     *     summary="短信模板列表",
     *     tags={"阿里短信"},
     *     description="短信模板列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="审核状态:0-审核中;1-审核通过;2-审核失败", required=false, type="integer"),
     *     @SWG\Parameter( name="template_name", in="query", description="模板名称", required=false, type="string"),
     *     @SWG\Parameter( name="template_type", in="query", description="模板类型:0-验证码;1-通知;2-推广短信", required=false, type="string"),
     *     @SWG\Parameter( name="scene_id", in="query", description="短信场景id", required=false, type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="page", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="page_size", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="", description="模板id"),
     *                          @SWG\Property( property="template_name", type="string", example="", description="模板名称"),
     *                          @SWG\Property( property="template_code", type="string", example="", description="模板编码"),
     *                          @SWG\Property( property="template_content", type="string", example="", description="模板内容"),
     *                          @SWG\Property( property="template_type", type="integer", example="", description="模板类型:0-验证码;1-短信通知;2-推广短信"),
     *                          @SWG\Property( property="scene_name", type="string", example="", description="短信场景"),
     *                          @SWG\Property( property="created", type="string", example="", description="创建时间"),
     *                          @SWG\Property( property="status", type="string", example="", description="审核状态: 0-审核中;1-审核通过;2-审核失败;3-全部"),
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
        $templateService = new TemplateService();
        $filter = [];
        if($params['template_name'] ?? 0) {
            $filter['template_name|contains'] = $params['template_name'];
        }
        if(isset($params['status'])) {
            $filter['status'] = $params['status'];
        }
        if($params['template_type'] ?? 0) {
            $filter['template_type'] = $params['template_type'];
        }
        if($params['scene_id'] ?? 0) {
            // $memberRelScenes这5个会员营销相关场景需要可以发普通短信也能发推广短信
            $sceneService = (new SceneService());
            $scene = $sceneService->getInfo(['company_id' => $companyId, 'id' => $params['scene_id']]);
            $memberRelScenes = ['member_anniversary', 'member_birthday', 'member_day', 'member_upgrade', 'member_vip_upgrade'];
            if(in_array($scene['scene_title'], $memberRelScenes)) {
                $taskScene = $sceneService->getInfo(['template_type' => 2, 'company_id' => $companyId]); //当前账号下的推广场景
                $filter['scene_id'] = [$params['scene_id'], $taskScene['id']];
            } else {
                $filter['scene_id'] = $params['scene_id'];
            }
        }
        $filter['company_id'] = $companyId;
        $cols = ['id','company_id','template_name','template_code','template_content','scene_id', 'template_type', 'status', 'reason', 'created'];
        $data = $templateService->getList($filter, $cols, $page, $pageSize);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/template/info",
     *     summary="短信模板详情",
     *     tags={"阿里短信"},
     *     description="短信模板详情",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="签名ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="", description=""),
     *                  @SWG\Property( property="template_name", type="string", example="", description="模板名称"),
     *                  @SWG\Property( property="template_code", type="string", example="", description="模板编码"),
     *                  @SWG\Property( property="template_content", type="string", example="", description="模板内容"),
     *                  @SWG\Property( property="template_type", type="integer", example="", description="模板类型:0-验证码;1-短信通知;2-推广短信"),
     *                  @SWG\Property( property="scene_id", type="string", example="", description="短信场景id"),
     *                  @SWG\Property( property="created", type="string", example="", description="创建时间"),
     *                  @SWG\Property( property="status", type="string", example="", description="审核状态: 0-审核中;1-审核通过;2-审核失败"),
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
        $templateService = new TemplateService();
        $data = $templateService->getInfo(['id' => $id]);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/template/add",
     *     summary="添加短信模板",
     *     tags={"阿里短信"},
     *     description="添加短信模板",
     *     operationId="addSign",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="formData", description="模板名称", required=true, type="string"),
     *     @SWG\Parameter( name="template_type", in="formData", description="模板类型:0-验证码;1-短信通知;2-推广短信", required=true, type="integer"),
     *     @SWG\Parameter( name="scene_id", in="formData", description="短信场景", required=true, type="integer"),
     *     @SWG\Parameter( name="template_content", in="formData", description="模板内容", required=true, type="string"),
     *     @SWG\Parameter( name="template_type", in="formData", description="模板类型:0-验证码;1-短信通知;2-推广短信", required=true, type="integer"),
     *     @SWG\Parameter( name="remark", in="formData", description="申请说明", required=true, type="string"),
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
    public function addTemplate(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'template_name' => ['required', '模板名称必填'],
            'template_type' => ['required|integer|min:0|max:2', '模板类型有误'],
            'remark' => ['required', '申请说明必填'],
            'template_content' => ['required', '模板内容必填'],
            'scene_id' => ['required', '短信场景必填'],
            'related_sign_name' => ['required', '关联签名必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['template_name'] = trim($params['template_name']);
        if( mb_strlen($params['template_name']) < 1 || mb_strlen($params['template_name']) > 30) {
            throw new ResourceException('模板名称有效长度1-30个字符');
        }
        $params['template_content'] = trim($params['template_content']);
        if( mb_strlen($params['template_content']) < 1 || mb_strlen($params['template_content']) > 500) {
            throw new ResourceException('模板内容有效长度1-500个字符');
        }
        $params['remark'] = trim($params['remark']);
        if( mb_strlen($params['remark']) < 1 || mb_strlen($params['remark']) > 100) {
            throw new ResourceException('申请说明有效长度1-100个字符');
        }
        $params['company_id'] = $companyId;
        $templateServic = new TemplateService();
        $templateServic->addTemplate($params);
        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Post(
     *     path="/aliyunsms/template/modify",
     *     summary="修改短信模板",
     *     tags={"阿里短信"},
     *     description="修改短信模板",
     *     operationId="modifyTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="formData", description="签名ID", required=true, type="integer"),
     *     @SWG\Parameter( name="template_name", in="formData", description="模板名称", required=true, type="string"),
     *     @SWG\Parameter( name="template_type", in="formData", description="模板类型:0-验证码;1-短信通知;2-推广短信", required=true, type="integer"),
     *     @SWG\Parameter( name="scene_id", in="formData", description="短信场景", required=true, type="integer"),
     *     @SWG\Parameter( name="template_content", in="formData", description="模板内容", required=true, type="string"),
     *     @SWG\Parameter( name="template_type", in="formData", description="模板类型:0-验证码;1-短信通知;2-推广短信", required=true, type="integer"),
     *     @SWG\Parameter( name="remark", in="formData", description="申请说明", required=true, type="string"),
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
    public function modifyTemplate(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'template_name' => ['required', '模板名称必填'],
            'template_type' => ['required|integer|min:0|max:2', '模板类型有误'],
            'remark' => ['required', '申请说明必填'],
            'template_content' => ['required', '模板内容必填'],
            'scene_id' => ['required', '短信场景必填'],
            'related_sign_name' => ['required', '关联签名必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['template_name'] = trim($params['template_name']);
        if( mb_strlen($params['template_name']) < 1 || mb_strlen($params['template_name']) > 30) {
            throw new ResourceException('模板名称有效长度1-30个字符');
        }
        $params['template_content'] = trim($params['template_content']);
        if( mb_strlen($params['template_content']) < 1 || mb_strlen($params['template_content']) > 500) {
            throw new ResourceException('模板内容有效长度1-500个字符');
        }
        $params['remark'] = trim($params['remark']);
        if( mb_strlen($params['remark']) < 1 || mb_strlen($params['remark']) > 100) {
            throw new ResourceException('申请说明有效长度1-100个字符');
        }
        $params['company_id'] = $companyId;
        $templateServic = new TemplateService();
        $templateServic->modifyTemplate($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/aliyunsms/template/delete/{id}",
     *     summary="删除短信模板",
     *     tags={"阿里短信"},
     *     description="删除短信模板",
     *     operationId="deleteTemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="formData", description="模板ID", required=true, type="integer"),
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
    public function deleteTemplate($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $templateService = new TemplateService();
        $filter['company_id'] = $companyId;
        $filter['id'] = $id;
        $templateService->deleteTemplate($filter);
        return $this->response->array(['status' => true]);
    }
}
