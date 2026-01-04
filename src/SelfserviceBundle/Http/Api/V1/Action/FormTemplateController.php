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

namespace SelfserviceBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\StoreResourceFailedException;

use SelfserviceBundle\Services\FormTemplateService;

class FormTemplateController extends Controller
{
    public $formTemplateService;
    public $limit;

    public function __construct()
    {
        $this->formTemplateService = new FormTemplateService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/formtem",
     *     summary="新增表单模板",
     *     tags={"报名"},
     *     description="新增表单模板",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tem_name", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="tem_type", in="query", description="模板类型", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="内容", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Parameter( name="key_index", in="query", description="关键指标", required=true, type="string"),
     *     @SWG\Parameter( name="form_style", in="query", description="表单风格", required=true, type="string"),
     *     @SWG\Parameter( name="header_link_title", in="query", description="表单头部文字", required=true, type="string"),
     *     @SWG\Parameter( name="header_title", in="query", description="表单头部内容", required=true, type="string"),
     *     @SWG\Parameter( name="bottom_title", in="query", description="表单底部文字", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/FormData"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function createData(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $params = $request->all('tem_name', 'tem_type', 'content', 'status', 'key_index', 'form_style', 'header_link_title', 'header_title', 'bottom_title', 'header_bg_pic', 'header_height');
        $rules = [
            'tem_name' => ['required', trans('SelfserviceBundle.tem_name_required')],
            'content' => ['required', trans('SelfserviceBundle.content_required')],
            'tem_type' => ['required', trans('SelfserviceBundle.tem_type_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (!is_array($params['content'])) {
            $params['content'] = json_decode($params['content'], true);
        }
        if (!is_array($params['key_index'])) {
            $params['key_index'] = json_decode($params['key_index'], true);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $result = $this->formTemplateService->saveData($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/selfhelp/formtem",
     *     summary="更新表单模板",
     *     tags={"报名"},
     *     description="更新表单模板",
     *     operationId="updateData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="id", required=true, type="string"),
     *     @SWG\Parameter( name="tem_name", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="tem_type", in="query", description="模板类型", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="内容", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Parameter( name="key_index", in="query", description="关键指标", required=true, type="string"),
     *     @SWG\Parameter( name="form_style", in="query", description="表单风格", required=true, type="string"),
     *     @SWG\Parameter( name="header_link_title", in="query", description="表单头部文字", required=true, type="string"),
     *     @SWG\Parameter( name="header_title", in="query", description="表单头部文字内容", required=true, type="string"),
     *     @SWG\Parameter( name="bottom_title", in="query", description="表单底部文字", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/FormData"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function updateData(Request $request)
    {
        $params = $request->all('id', 'tem_name', 'tem_type', 'content', 'status', 'key_index', 'form_style', 'header_link_title', 'header_title', 'bottom_title', 'header_bg_pic', 'header_height');
        $rules = [
            'id' => ['required', trans('SelfserviceBundle.tag_id_required')],
            'tem_name' => ['required', trans('SelfserviceBundle.field_title_required')],
            'content' => ['required', trans('SelfserviceBundle.field_name_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        if (!is_array($params['content'])) {
            $params['content'] = json_decode($params['content'], true);
        }
        if (!is_array($params['key_index'])) {
            $params['key_index'] = json_decode($params['key_index'], true);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $params['id'];
        $filter['company_id'] = $companyId;
        $result = $this->formTemplateService->saveData($params, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/formtem",
     *     summary="获取表单模板列表",
     *     tags={"报名"},
     *     description="获取表单模板列表",
     *     operationId="getDatalist",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页长度",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="field_title",
     *         in="query",
     *         description="标题",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="form_element",
     *         in="query",
     *         description="表单元素",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/FormData"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDatalist(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'tem_type', 'is_valid', 'tem_name');
        $page = $params['page'] ?: 0;
        $size = $params['pageSize'] ?: 10;

        $orderBy = ['id' => 'DESC'];

        $filter['company_id'] = app('auth')->user()->get('company_id');
        if ($params['is_valid']) {
            $filter['status'] = intval($params['is_valid']);
        }
        if ($params['tem_name']) {
            $filter['tem_name'] = $params['tem_name'];
        }
        if ($params['tem_type']) {
            $filter['tem_type'] = $params['tem_type'];
        }

        $result = $this->formTemplateService->lists($filter, $orderBy, $size, $page);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/formtem/{id}",
     *     summary="获取指定详情",
     *     tags={"报名"},
     *     description="获取指定详情",
     *     operationId="getDataInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/FormData"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDataInfo($id)
    {
        $result = [];
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->formTemplateService->getInfoById($id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/formtem/discard/{id}",
     *     summary="废弃表单模板",
     *     tags={"报名"},
     *     description="废弃表单模板",
     *     operationId="deleteData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function deleteData($id)
    {
        $result = [];
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->formTemplateService->discard($id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/formtem/restore/{id}",
     *     summary="恢复表单模板",
     *     tags={"报名"},
     *     description="恢复表单模板",
     *     operationId="deleteData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function restoreData($id)
    {
        $result = [];
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->formTemplateService->restore($id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Definition(
     *     definition="FormData",
     *     description="表单数据",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="23", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="tem_name", type="string", example="免费美家设计", description="表单模板名称"),
     *                          @SWG\Property( property="content", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="title", type="string", example="抢领免费设计名额", description="标题 "),
     *                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                  @SWG\Property( property="formdata", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="38", description="表单元素ID"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                          @SWG\Property( property="field_title", type="string", example="称呼", description="表单项标题(中文描述)"),
     *                                          @SWG\Property( property="field_name", type="string", example="name", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                          @SWG\Property( property="form_element", type="string", example="text", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                          @SWG\Property( property="status", type="string", example="1", description="自行更改字段描述"),
     *                                          @SWG\Property( property="sort", type="string", example="0", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="is_required", type="string", example="true", description="是否必填"),
     *                                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                       ),
     *                                  ),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                          @SWG\Property( property="form_style", type="string", example="single", description="表单关键指数, single:单页问卷, multiple:多页问卷"),
     *                          @SWG\Property( property="header_link_title", type="string", example="test", description="头部文字"),
     *                          @SWG\Property( property="header_title", type="string", example="抢领免费设计名额", description="头部文字内容"),
     *                          @SWG\Property( property="bottom_title", type="string", example="底部文字", description="表单关键指数"),
     *                          @SWG\Property( property="key_index", type="array",
     *                              @SWG\Items( type="string", example="1", description="关键指标"),
     *                          ),
     *                          @SWG\Property( property="tem_type", type="string", example="ask_answer_paper", description="表单模板类型；ask_answer_paper：问答考卷，basic_entry：基础录入"),
     * )
     */
}
