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
use ThemeBundle\Services\ThemePcTemplateContentServices;
use ThemeBundle\Services\ThemePcTemplateServices;

class PcTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/pctemplate/lists",
     *     summary="pc模版列表",
     *     tags={"模版"},
     *     description="pc模版列表",
     *     operationId="lists",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="query",
     *         description="页面类型",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_no",
     *         in="query",
     *         description="页号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="每页显示页数",
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
     *                      type="object",
     *                      @SWG\Property(property="total_count", type="int"),
     *                      @SWG\Property(
     *                      property="list",
     *                      type="array",
     *                      @SWG\Items(
     *                          type="object",
     *                          @SWG\Property(property="company_id", type="int"),
     *                          @SWG\Property(property="created", type="string"),
     *                          @SWG\Property(property="deleted_at", type="string"),
     *                          @SWG\Property(property="page_type", type="string"),
     *                          @SWG\Property(property="status", type="string"),
     *                          @SWG\Property(property="template_description", type="string"),
     *                          @SWG\Property(property="template_title", type="string"),
     *                          @SWG\Property(property="theme_pc_template_id", type="string"),
     *                          @SWG\Property(property="updated", type="string"),
     *                          @SWG\Property(property="version", type="string"),
     *                        )
     *                     ),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        // 0x53686f704578
        $company_id = app('auth')->user()->get('company_id');
        $page_type = $request->input('page_type'); //页面类型 index 首页 custom 自定义页面
        $page_no = $request->input('page_no', 1);
        $page_size = $request->input('page_size', 20);
        $status = $request->input('status');

        $params = [
            'company_id' => $company_id,
            'page_type' => $page_type,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'status' => $status,
        ];

        $theme_pc_template_services = new ThemePcTemplateServices();
        $result = $theme_pc_template_services->lists($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\POST(
     *     path="/pctemplate/add",
     *     summary="新增pc模板",
     *     tags={"模版"},
     *     description="新增pc模板",
     *     operationId="add",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="version",
     *         in="formData",
     *         description="版本号",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_title",
     *         in="formData",
     *         description="页面名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_description",
     *         in="formData",
     *         description="页面描述",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="formData",
     *         description="页面类型",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="formData",
     *         description="是否启用",
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
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="deleted_at", type="string"),
     *                     @SWG\Property(property="page_type", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="template_description", type="string"),
     *                     @SWG\Property(property="template_title", type="string"),
     *                     @SWG\Property(property="theme_pc_template_id", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                     @SWG\Property(property="version", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function add(Request $request)
    {
        // 0x53686f704578
        $company_id = app('auth')->user()->get('company_id');
        $template_title = $request->input('template_title');
        $template_description = $request->input('template_description');
        $page_type = $request->input('page_type');
        $version = $request->input('version', 'v1.0.1');
        $status = $request->input('status', 2);

        $params = [
            'company_id' => $company_id,
            'template_title' => $template_title,
            'template_description' => $template_description,
            'page_type' => $page_type,
            'version' => $version,
            'status' => $status
        ];
        $rules = [
            'template_title' => ['required', '缺少页面名称'],
            'template_description' => ['required', '缺少页面描述'],
            'page_type' => ['required|in:index,custom', '缺少页面类型'],
            'version' => ['required', '缺少版本号']
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $service = new ThemePcTemplateServices();
        $result = $service->add($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/pctemplate/edit",
     *     summary="编辑pc模板",
     *     tags={"模版"},
     *     description="编辑pc模板",
     *     operationId="edit",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="version",
     *         in="formData",
     *         description="版本号",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="theme_pc_template_id",
     *         in="formData",
     *         description="主题pc模版ID",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="template_title",
     *         in="formData",
     *         description="页面名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_description",
     *         in="formData",
     *         description="页面描述",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="formData",
     *         description="页面类型",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="formData",
     *         description="是否启用",
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
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="deleted_at", type="string"),
     *                     @SWG\Property(property="page_type", type="string"),
     *                     @SWG\Property(property="status", type="string"),
     *                     @SWG\Property(property="template_description", type="string"),
     *                     @SWG\Property(property="template_title", type="string"),
     *                     @SWG\Property(property="theme_pc_template_id", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                     @SWG\Property(property="version", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function edit(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $theme_pc_template_id = $request->input('theme_pc_template_id');
        $template_title = $request->input('template_title');
        $template_description = $request->input('template_description');
        $page_type = $request->input('page_type');
        $status = $request->input('status');

        $params = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id,
            'template_title' => $template_title,
            'template_description' => $template_description,
            'page_type' => $page_type,
            'status' => $status
        ];
        $rules = [
            'theme_pc_template_id' => ['required', '缺少theme_pc_template_id'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        if (!empty($status) && !in_array($status, [1, 2])) {
            throw new ResourceException('启用状态不合法');
        }

        $service = new ThemePcTemplateServices();
        $result = $service->edit($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/pctemplate/delete/{theme_pc_template_id}",
     *     summary="删除pc模板",
     *     tags={"模版"},
     *     description="删除pc模板",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="theme_pc_template_id",
     *         in="path",
     *         description="主题PC模板ID",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function delete($theme_pc_template_id)
    {
        $company_id = app('auth')->user()->get('company_id');
        $params = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id
        ];

        $service = new ThemePcTemplateServices();
        $result = $service->delete($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/pctemplate/getHeaderOrFooter",
     *     summary="获取头部尾部",
     *     tags={"模版"},
     *     description="获取头部尾部",
     *     operationId="getHeaderOrFooter",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_name",
     *         in="query",
     *         description="页面名称",
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
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="name", type="string"),
     *                     @SWG\Property(property="params", type="string"),
     *                     @SWG\Property(property="theme_pc_template_content_id", type="int"),
     *                     @SWG\Property(property="theme_pc_template_id", type="int"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function getHeaderOrFooter(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $page_name = $request->input('page_name');

        $params = [
            'company_id' => $company_id,
            'page_name' => $page_name,
        ];
        $service = new ThemePcTemplateContentServices();
        $result = $service->detail($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/pctemplate/saveHeaderOrFooter",
     *     summary="头尾部保存",
     *     tags={"模版"},
     *     description="头尾部保存",
     *     operationId="saveHeaderOrFooter",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          required=true,
     *          description="头尾部保存",
     *          @SWG\Schema(
     *              @SWG\Property(
     *                  property="config",
     *                  type="string"
     *              ),
     *              @SWG\Property(
     *                   property="page_name",
     *                   type="string"
     *              )
     *          )
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
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="created", type="int"),
     *                     @SWG\Property(property="name", type="string"),
     *                     @SWG\Property(property="params", type="string"),
     *                     @SWG\Property(property="theme_pc_template_content_id", type="string"),
     *                     @SWG\Property(property="theme_pc_template_id", type="string"),
     *                     @SWG\Property(property="updated", type="int"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function saveHeaderOrFooter(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $page_name = $request->input('page_name');
        $config = $request->input('config');

        $params = [
            'company_id' => $company_id,
            'page_name' => $page_name,
            'config' => $config
        ];
        $service = new ThemePcTemplateContentServices();
        $result = $service->save($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/pctemplate/getTemplateContent",
     *     summary="获取pc模版内容",
     *     tags={"模版"},
     *     description="获取pc模版内容",
     *     operationId="getTemplateContent",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="theme_pc_template_id",
     *         in="query",
     *         description="主题PC模板ID",
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
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="config", type="string"),
     *                         @SWG\Property(property="name", type="string"),
     *                   )
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function getTemplateContent(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $theme_pc_template_id = $request->input('theme_pc_template_id');

        $params = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id,
        ];
        $service = new ThemePcTemplateContentServices();
        $result = $service->templateContent($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/pctemplate/saveTemplateContent",
     *     summary="保存pc模版内容",
     *     tags={"模版"},
     *     description="保存pc模版内容",
     *     operationId="saveTemplateContent",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          required=true,
     *          description="模版内容",
     *          @SWG\Schema(
     *              @SWG\Property(
     *                  property="config",
     *                  type="string"
     *              ),
     *              @SWG\Property(
     *                   property="theme_pc_template_id",
     *                   type="string"
     *              )
     *          )
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function saveTemplateContent(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $theme_pc_template_id = $request->input('theme_pc_template_id');
        $config = $request->input('config');

        $params = [
            'company_id' => $company_id,
            'theme_pc_template_id' => $theme_pc_template_id,
            'config' => $config
        ];
        $service = new ThemePcTemplateContentServices();
        $service->addTemplateContent($params);

        return $this->response->array(['status' => true]);
    }

    public function getLoginPageSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = app('redis')->connection('companys')->get('pc_login_page:'.$companyId);
        if (!$result) {
            $result['logo'] = '';
            $result['background'] = '';
        } else {
            $result = json_decode($result, true);
        }
        return $this->response->array($result);
    }

    public function saveLoginPageSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        // 整理参数
        $params = [
            'logo' => $request->input('logo'),
            'background' => $request->input('background'),
        ];
        app('redis')->connection('companys')->set('pc_login_page:'.$companyId, json_encode($params));
        return $this->response->array(['status' => true]);
    }
}
