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

use SelfserviceBundle\Services\FormSettingService;

class FormSettingController extends Controller
{
    public $formSettingService;
    public $limit;

    public function __construct()
    {
        $this->formSettingService = new FormSettingService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/formdata",
     *     summary="新增表单元素配置项",
     *     tags={"报名"},
     *     description="新增表单元素配置项",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="field_title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="field_name", in="query", description="名称", required=true, type="string"),
     *     @SWG\Parameter( name="image_url", in="query", description="图片地址", required=true, type="string"),
     *     @SWG\Parameter( name="form_element", in="query", description="表单元素（text、textarea、select、radio、checkbox）任选一个", required=true, type="string"),
     *     @SWG\Parameter( name="options[][value]", in="query", description="选择项内容", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/FormElement"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function createData(Request $request)
    {
        $params = $request->all('field_title', 'field_name', 'form_element', 'options', 'image_url', 'pic_name');
        $rules = [
            'field_title' => ['required', trans('SelfserviceBundle.field_title_required')],
            'field_name' => ['required', trans('SelfserviceBundle.field_name_required')],
            'form_element' => ['required', trans('SelfserviceBundle.form_element_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $result = $this->formSettingService->saveData($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/selfhelp/formdata",
     *     summary="更新表单元素配置项",
     *     tags={"报名"},
     *     description="更新表单元素配置项",
     *     operationId="updateData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="ID", required=true, type="string"),
     *     @SWG\Parameter( name="field_title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="field_name", in="query", description="名称", required=true, type="string"),
     *     @SWG\Parameter( name="image_url", in="query", description="图片地址", required=true, type="string"),
     *     @SWG\Parameter( name="form_element", in="query", description="表单元素（text、textarea、select、radio、checkbox）任选一个", required=true, type="string"),
     *     @SWG\Parameter( name="options", in="query", description="选择项内容", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/FormElement"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function updateData(Request $request)
    {
        $params = $request->all('id', 'field_title', 'field_name', 'form_element', 'options', 'image_url', 'pic_name');
        $rules = [
            'id' => ['required', trans('SelfserviceBundle.tag_id_required')],
            'field_title' => ['required', trans('SelfserviceBundle.tag_name_required')],
            'field_name' => ['required', trans('SelfserviceBundle.tag_name_required')],
            'form_element' => ['required', trans('SelfserviceBundle.tag_name_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $params['id'];
        $filter['company_id'] = $companyId;
        $params['company_id'] = $companyId;
        $result = $this->formSettingService->saveData($params, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/formdata",
     *     summary="获取表单元素列表",
     *     tags={"报名"},
     *     description="获取表单元素列表",
     *     operationId="getDatalist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否有效(0,1)", required=true, type="integer"),
     *     @SWG\Parameter( name="field_title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="form_element", in="query", description="表单元素", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object", ref="#/definitions/FormElement" ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDatalist(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'form_element', 'field_title', 'is_valid');
        $page = $params['page'] ?: 0;
        $size = $params['pageSize'] ?: $this->limit;
        $result = [
            'total_count' => 0,
            'list' => [],
        ];

        $orderBy = ['id' => 'DESC'];

        if ($params['is_valid']) {
            $filter['status'] = intval($params['is_valid']);
        }

        if ($params['form_element']) {
            $filter['form_element'] = $params['form_element'];
        }
        if ($params['field_title']) {
            $filter['field_title|like'] = $params['field_title'];
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result['total_count'] = $this->formSettingService->entityRepository->count($filter);
        if ($result['total_count']) {
            $result['list'] = $this->formSettingService->entityRepository->getLists($filter, '*', $page, $size, $orderBy);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/formdata/{id}",
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
     *                  ref="#/definitions/FormElement"
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
        $result = $this->formSettingService->getInfoById($id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/formdata/discard/{id}",
     *     summary="废弃指定项",
     *     tags={"报名"},
     *     description="废弃指定项",
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
        $result = $this->formSettingService->entityRepository->discard($id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/formdata/restore/{id}",
     *     summary="还原指定项",
     *     tags={"报名"},
     *     description="还原指定项",
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
        $result = $this->formSettingService->entityRepository->restore($id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Definition(
     *     definition="FormElement",
     *     description="表单元素",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="26", description="ID"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *     @SWG\Property( property="field_title", type="string", example="下拉选择元素", description="表单项标题(中文描述)"),
     *     @SWG\Property( property="field_name", type="string", example="combobox", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *     @SWG\Property( property="form_element", type="string", example="select", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *     @SWG\Property( property="status", type="string", example="1", description="状态"),
     *     @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *     @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *     @SWG\Property( property="image_url", type="string", example="http://bbctest.aixue7.com/1/2019/09/25/...", description="元素配图"),
     *     @SWG\Property( property="options", type="array",
     *          @SWG\Items( type="object",
     *               @SWG\Property( property="value", type="string", example="项目1", description="选择项名称"),
     *               @SWG\Property( property="image_url", type="string", example="http://bbctest.aixue7.com/1/2019/09/27/...", description="元素配图"),
     *          ),
     *     ),
     * )
     */
}
