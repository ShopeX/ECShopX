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

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use ThemeBundle\Jobs\PagesTemplateSyncJob;
use ThemeBundle\Services\PagesTemplateServices;

class PagesTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/pagestemplate/lists",
     *     summary="模板列表",
     *     tags={"模版"},
     *     description="模板列表",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="门店id",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_no",
     *         in="query",
     *         description="页码",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="每页记录条数",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="pages_template_id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="distributor_id", type="int"),
     *                     @SWG\Property(property="template_name", type="string"),
     *                     @SWG\Property(property="template_pic", type="string"),
     *                     @SWG\Property(property="template_type", type="int"),
     *                     @SWG\Property(property="element_edit_status", type="int"),
     *                     @SWG\Property(property="status", type="int"),
     *                     @SWG\Property(property="timer_status", type="int"),
     *                     @SWG\Property(property="timer_time", type="int"),
     *                     @SWG\Property(property="template_status_modify_time", type="int"),
     *                     @SWG\Property(property="weapp_pages", type="string"),
     *                     @SWG\Property(property="template_content", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        // Debug: 1e2364
        $company_id = app('auth')->user()->get('company_id');
        $distributor_id = $request->input('distributor_id', 0);
        $page_no = $request->input('page_no', 1);
        $page_size = $request->input('page_size', 50);

        $params = [
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'page_no' => $page_no,
            'page_size' => $page_size
        ];

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->lists($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/pagestemplate/add",
     *     summary="新增模板",
     *     tags={"模版"},
     *     description="新增模板",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="formData",
     *         description="门店id",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="formData",
     *         description="模板名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_pic",
     *         in="formData",
     *         description="封面图片",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_title",
     *         in="formData",
     *         description="模板标题",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="weapp_pages",
     *         in="formData",
     *         description="小程序页码类型",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="pages_template_id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="distributor_id", type="int"),
     *                     @SWG\Property(property="template_name", type="string"),
     *                     @SWG\Property(property="template_pic", type="string"),
     *                     @SWG\Property(property="template_type", type="int"),
     *                     @SWG\Property(property="element_edit_status", type="int"),
     *                     @SWG\Property(property="status", type="int"),
     *                     @SWG\Property(property="timer_status", type="int"),
     *                     @SWG\Property(property="timer_time", type="int"),
     *                     @SWG\Property(property="template_status_modify_time", type="int"),
     *                     @SWG\Property(property="weapp_pages", type="string"),
     *                     @SWG\Property(property="template_content", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function add(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $distributor_id = $request->input('distributor_id', 0);
        $template_name = $request->input('template_name');  //展示客户端名称 如小程序商城 yykweishop
        $template_title = $request->input('template_title');
        $template_pic = $request->input('template_pic');
        $weapp_pages = $request->input('weapp_pages', 'index'); //小程序页面类型 默认为首页页面

        $params = [
            'company_id' => $company_id,
            'distributor_id' => $distributor_id,
            'template_name' => $template_name,
            'template_title' => $template_title,
            'template_pic' => $template_pic,
            'weapp_pages' => $weapp_pages,
        ];

        $rules = [
            'template_title' => ['required', '缺少模板名称'],
            'template_name' => ['required', '缺少模板展示类型'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/pagestemplate/edit",
     *     summary="编辑模板",
     *     tags={"模版"},
     *     description="编辑模板",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="formData",
     *         description="店铺ID",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pages_template_id",
     *         in="formData",
     *         description="模板id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="formData",
     *         description="模板名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_content",
     *         in="formData",
     *         description="模板内容",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="element_edit_status",
     *         in="formData",
     *         description="店铺可编辑挂件 1 开启 2关闭",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="pages_template_id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="distributor_id", type="int"),
     *                     @SWG\Property(property="template_name", type="string"),
     *                     @SWG\Property(property="template_pic", type="string"),
     *                     @SWG\Property(property="template_type", type="int"),
     *                     @SWG\Property(property="element_edit_status", type="int"),
     *                     @SWG\Property(property="status", type="int"),
     *                     @SWG\Property(property="timer_status", type="int"),
     *                     @SWG\Property(property="timer_time", type="int"),
     *                     @SWG\Property(property="template_status_modify_time", type="int"),
     *                     @SWG\Property(property="weapp_pages", type="string"),
     *                     @SWG\Property(property="template_content", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function edit(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $pages_template_id = $request->input('pages_template_id');

        $template_title = $request->input('template_title', false);
        $template_pic = $request->input('template_pic', false);
        if ($template_title !== false || $template_pic !== false) {
            $pages_template_services = new PagesTemplateServices();
            $pages_template_services->updateInfo($company_id, $pages_template_id, [
                'template_title' => $template_title ?: '',
                'template_pic' => $template_pic ?: '',
            ]);
            return $this->response->array(['status' => true]);
        }

        $template_content = $request->input('template_content');
        $element_edit_status = $request->input('element_edit_status');
        $template_name = $request->input('template_name');  //展示客户端名称 如小程序商城 yykweishop

        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
            'template_content' => $template_content,
            'element_edit_status' => $element_edit_status,
            'template_name' => $template_name
        ];

        $rules = [
            'template_name' => ['required', '缺少模板展示类型'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $pages_template_services = new PagesTemplateServices();
        $pages_template_services->edit($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/pagestemplate/detail",
     *     summary="模板详情",
     *     tags={"模版"},
     *     description="模板详情",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pages_template_id",
     *         in="query",
     *         description="模板id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="pages_template_id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="distributor_id", type="int"),
     *                     @SWG\Property(property="template_name", type="string"),
     *                     @SWG\Property(property="template_pic", type="string"),
     *                     @SWG\Property(property="template_type", type="int"),
     *                     @SWG\Property(property="element_edit_status", type="int"),
     *                     @SWG\Property(property="status", type="int"),
     *                     @SWG\Property(property="timer_status", type="int"),
     *                     @SWG\Property(property="timer_time", type="int"),
     *                     @SWG\Property(property="template_status_modify_time", type="int"),
     *                     @SWG\Property(property="weapp_pages", type="string"),
     *                     @SWG\Property(property="template_content", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function detail(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $pages_template_id = $request->input('pages_template_id');
        $version = $request->input('version', 'v1.0.2');       //小程序版本

        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
            'version' => $version
        ];

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->detail($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/pagestemplate/copy",
     *     summary="复制模板",
     *     tags={"模版"},
     *     description="复制模板",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pages_template_id",
     *         in="formData",
     *         description="模板id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="pages_template_id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="distributor_id", type="int"),
     *                     @SWG\Property(property="template_name", type="string"),
     *                     @SWG\Property(property="template_pic", type="string"),
     *                     @SWG\Property(property="template_type", type="int"),
     *                     @SWG\Property(property="element_edit_status", type="int"),
     *                     @SWG\Property(property="status", type="int"),
     *                     @SWG\Property(property="timer_status", type="int"),
     *                     @SWG\Property(property="timer_time", type="int"),
     *                     @SWG\Property(property="template_status_modify_time", type="int"),
     *                     @SWG\Property(property="weapp_pages", type="string"),
     *                     @SWG\Property(property="template_content", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function copy(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $pages_template_id = $request->input('pages_template_id');

        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
        ];

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->copy($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/pagestemplate/del/{pages_template_id}",
     *     summary="废弃模板",
     *     tags={"模版"},
     *     description="废弃模板",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pages_template_id",
     *         in="path",
     *         description="页面模板ID",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="bool"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function delete($pages_template_id, Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');

        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
        ];

        $pages_template_services = new PagesTemplateServices();
        $result = $pages_template_services->delete($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/pagestemplate/modifyStatus",
     *     summary="模板状态变更",
     *     tags={"模版"},
     *     description="模板状态变更",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pages_template_id",
     *         in="formData",
     *         description="模板id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="formData",
     *         description="模板名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="formData",
     *         description="启用状态 1启用 2关闭",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="timer_status",
     *         in="formData",
     *         description="定时启用状态 1启用 2关闭",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="timer_time",
     *         in="formData",
     *         description="定时启用时间",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="pages_template_id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="distributor_id", type="int"),
     *                     @SWG\Property(property="template_name", type="string"),
     *                     @SWG\Property(property="template_pic", type="string"),
     *                     @SWG\Property(property="template_type", type="int"),
     *                     @SWG\Property(property="element_edit_status", type="int"),
     *                     @SWG\Property(property="status", type="int"),
     *                     @SWG\Property(property="timer_status", type="int"),
     *                     @SWG\Property(property="timer_time", type="int"),
     *                     @SWG\Property(property="template_status_modify_time", type="int"),
     *                     @SWG\Property(property="weapp_pages", type="string"),
     *                     @SWG\Property(property="template_content", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function modifyStatus(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $pages_template_id = $request->input('pages_template_id');
        $status = $request->input('status');
        $timer_status = $request->input('timer_status');
        $timer_time = $request->input('timer_time');

        //status、timer_status 不能同时为空
        if (empty($status) && empty($timer_status)) {
            $error = '无效的状态变更操作';
            throw new ResourceException($error);
        }
        //timer_status、timer_time 不能同时为空
        if (!empty($timer_status) && $timer_status == 1) {
            if (empty($timer_time)) {
                $error = '定时启用缺少必要参数';
                throw new ResourceException($error);
            }
        }

        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
            'status' => $status,
            'timer_status' => $timer_status,
            'timer_time' => strtotime($timer_time)
        ];

        $pages_template_services = new PagesTemplateServices();
        $pages_template_services->modifyStatus($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/pagestemplate/sync",
     *     summary="同步模板",
     *     tags={"模版"},
     *     description="同步模板",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pages_template_id",
     *         in="formData",
     *         description="模板id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="is_all_distributor",
     *         in="formData",
     *         description="是否全部门店 1是 2否",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_ids",
     *         in="formData",
     *         description="部分门店id 格式：[1,2,3,4,5,6]",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="bool"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function sync(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $pages_template_id = $request->input('pages_template_id');
        $is_all_distributor = $request->input('is_all_distributor', 2);
        $distributor_ids = $request->input('distributor_ids');

        $params = [
            'company_id' => $company_id,
            'pages_template_id' => $pages_template_id,
            'is_all_distributor' => $is_all_distributor,
            'distributor_ids' => $distributor_ids,
        ];

        $gotoJob = (new PagesTemplateSyncJob($params))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;

        return response()->json($result);
    }
}
