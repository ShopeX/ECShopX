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

use AliyunsmsBundle\Services\SceneItemService;
use AliyunsmsBundle\Services\SceneService;
use AliyunsmsBundle\Services\SettingService;
use AliyunsmsBundle\Services\SettingServService;
use AliyunsmsBundle\Services\SignService;
use AliyunsmsBundle\Services\TemplateService;
use CrossBorderBundle\Services\Set;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Ego\CompanysActivationEgo;

class Scene extends Controller
{
    /**
     * @SWG\Get(
     *     path="/aliyunsms/scene/list",
     *     summary="短信场景列表",
     *     tags={"阿里短信"},
     *     description="短信场景列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="", required=false, type="integer"),
     *     @SWG\Parameter( name="scene_name", in="query", description="场景名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="integer", example="", description="场景id"),
     *                          @SWG\Property( property="scene_name", type="string", example="", description="模板名称"),
     *                          @SWG\Property( property="template_type", type="integer", example="", description="模板类型:0-验证码;1-短信通知;2-推广短信"),
     *                          @SWG\Property( property="item_list", type="array", description="场景实例",  @SWG\Items(
     *                              @SWG\Property( property="id", type="integer", example="", description="场景实例id"),
     *                              @SWG\Property( property="template_content", type="string", example="", description="模板内容"),
     *                              @SWG\Property( property="sign_name", type="string", description="签名名称"),
     *                              @SWG\Property( property="status", type="string", description="实例状态:0-未启用;1-已启用"),
     *                          )),
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
        $sceneService = new SceneService();
        $filter = ['company_id' => $companyId];
        if($params['scene_name'] ?? 0) {
            $filter['scene_name|contains'] = $params['scene_name'];
        }
        $cols = ['id','company_id','scene_name','template_type'];
        $data = $sceneService->getList($filter, $cols, $page, $pageSize);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/scene/simpleList",
     *     summary="下拉短信场景列表",
     *     tags={"阿里短信"},
     *     description="下拉短信场景列表",
     *     operationId="getSimpleList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_type", in="query", description="模板类型:0-验证码;1-短信通知;2-推广短信", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="integer", example="", description="场景id"),
     *                          @SWG\Property( property="scene_name", type="string", example="", description="模板名称"),
     *                          @SWG\Property( property="template_type", type="integer", example="", description="模板类型:0-验证码;1-短信通知;2-推广短信"),
     *                      )
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getSimpleList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $sceneService = new SceneService();
        $filter = [];
        $cols = ['id','scene_name','template_type'];
        if(isset($params['template_type'])) {
            $filter['template_type'] = $params['template_type'];
        }
        $filter['company_id'] = $companyId;
        $data = $sceneService->getSimpleList($filter, $cols, 0);

        $company = (new CompanysActivationEgo())->check($companyId);
        $sceneList = config('sms.'.$company['product_model']);
        if ($sceneList) {
            foreach ($data['list'] as $key => $val) {
                if (!in_array($val['scene_name'], $sceneList)) {
                    unset($data['list'][$key]);
                    $data['count']--;
                }
            }
            $data['list'] = array_values($data['list']);
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/scene/detail",
     *     summary="短信场景详情",
     *     tags={"阿里短信"},
     *     description="短信场景详情",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="场景ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="", description=""),
     *                  @SWG\Property( property="scene_name", type="string", example="", description="场景名称"),
     *                  @SWG\Property( property="default_template", type="string", example="", description="默认模板"),
     *                  @SWG\Property( property="variables", type="string", example="", description="变量参数"),
     *                  @SWG\Property( property="template_type", type="integer", example="", description="模板类型:0-验证码;1-短信通知;2-推广短信"),
     *                  @SWG\Property( property="created", type="string", example="", description="创建时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function getDetail(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = $request->input('id');
        $sceneService = new SceneService();
        $data = $sceneService->getDetail($id);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/aliyunsms/scene/addItem",
     *     summary="添加短信场景明细",
     *     tags={"阿里短信"},
     *     description="添加短信场景明细",
     *     operationId="addItem",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="scene_id", in="formData", description="场景ID", required=true, type="integer"),
     *     @SWG\Parameter( name="sign_id", in="formData", description="签名ID", required=true, type="integer"),
     *     @SWG\Parameter( name="template_id", in="formData", description="模板ID", required=true, type="integer"),
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
    public function addItem(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $rules = [
            'scene_id' => ['required', '请选择场景'],
            'sign_id' => ['required', '请选择签名'],
            'template_id' => ['required', '请选择模板'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = $companyId;
        $sceneItemService = new SceneItemService();
        $sceneItemService->addItem($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/aliyunsms/scene/deleteItem/{id}",
     *     summary="删除场景实例",
     *     tags={"阿里短信"},
     *     description="删除场景实例",
     *     operationId="deleteItem",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function deleteItem($id, Request $request)
    {
        if(!$id) {
            throw new ResourceException('id必填');
        }
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $filter['id'] = $id;
        $sceneItemService = new SceneItemService();
        $sceneItemService->deleteItem($filter);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/scene/enableItem",
     *     summary="启用场景实例",
     *     tags={"阿里短信"},
     *     description="启用场景实例",
     *     operationId="enableItem",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="场景实例ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function enableItem(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = $request->input('id');
        $sceneItemService = new SceneItemService();
        $filter['company_id'] = $companyId;
        $filter['id'] = $id;
        $sceneItemService->enableItem($filter);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/aliyunsms/scene/disableItem",
     *     summary="停用场景实例",
     *     tags={"阿里短信"},
     *     description="停用场景实例",
     *     operationId="disableItem",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="场景实例ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AliyunsmsErrorResponse") ) )
     * )
     */
    public function disableItem(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $id = $request->input('id');
        $sceneItemService = new SceneItemService();
        $filter['company_id'] = $companyId;
        $filter['id'] = $id;
        $sceneItemService->disableItem($filter);
        return $this->response->array(['status' => true]);
    }
}
