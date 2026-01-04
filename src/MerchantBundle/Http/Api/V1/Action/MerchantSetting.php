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

namespace MerchantBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use MerchantBundle\Services\MerchantSettingService;

class MerchantSetting extends Controller
{
    /**
     * @SWG\Get(
     *     path="/merchant/basesetting",
     *     summary="获取商户基础设置",
     *     tags={"商户"},
     *     description="获取商户基础设置",
     *     operationId="getBase",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="false", description="是否允许加盟商入驻 true:是 false:否"),
     *                  @SWG\Property( property="settled_type", type="array", description="允许加盟商入驻类型 enterprise:企业 soletrader:个体户",
     *                      @SWG\Items( type="string", example="", description=""),
     *                  ),
     *                  @SWG\Property( property="content", type="string", example="", description="入驻协议内容"),
     *                  @SWG\Property( property="h5url", type="string", example="", description="入驻协议内容"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getBase(Request $request)
    {
        $settingService = new MerchantSettingService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $settingService->getBaseSetting($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/setting",
     *     summary="保存商户基础设置",
     *     tags={"商户"},
     *     description="保存商户基础设置",
     *     operationId="saveBase",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="是否允许加盟商入驻 true:是 false:否", required=true, type="string"),
     *     @SWG\Parameter( name="settled_type", in="query", description="[数组] 允许加盟商入驻类型 enterprise:企业 soletrader:个体户", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="入驻协议内容", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function saveBase(Request $request)
    {
        $params = $request->all('status', 'display_on_pc', 'settled_type', 'content');
        $rules = [
            'status' => ['required', trans('MerchantBundle.merchant_status_required')],
            'display_on_pc' => ['required_if:status,true|in:true,false', trans('MerchantBundle.pc_display_required')],
            'settled_type' => ['required', trans('MerchantBundle.settlement_type_option_required')],
            'content' => ['required', trans('MerchantBundle.agreement_content_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $settingService = new MerchantSettingService();
        $companyId = app('auth')->user()->get('company_id');
        $data = [
            'status' => $params['status'] == true ? 'true' : 'false',
            'display_on_pc' => $params['display_on_pc'],
            'settled_type' => $params['settled_type'],
            'content' => $params['content'],
        ];
        $settingService->setBaseSetting($companyId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/merchant/type/list",
     *     summary="获取商户类型列表",
     *     tags={"商户"},
     *     description="获取全部商户类型列表，一级是商户类型，二级是经营范围;可以按类型名称筛选；",
     *     operationId="getTypeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter(
     *         name="is_show_children",
     *         in="query",
     *         description="是否显示子类目,默认true",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="类型名称",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sort_order_by",
     *         in="query",
     *         description="按排序字段进行排序 asc:正序 desc:倒序",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="2", description="ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公企业ID"),
     *                  @SWG\Property( property="name", type="string", example="贸易类", description="类型名称"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父分类id,顶级为0"),
     *                  @SWG\Property( property="path", type="string", example="2", description="路径"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序，数字越小越靠前"),
     *                  @SWG\Property( property="level", type="string", example="1", description="等级，从1开始"),
     *                  @SWG\Property( property="cur_level", type="string", example="0", description="当前等级，从0开始"),
     *                  @SWG\Property( property="created", type="string", example="1639465397", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1639465397", description="修改时间"),
     *                  @SWG\Property( property="is_show", type="string", example="1", description="是否展示,1展示 0不展示"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="3", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="name", type="string", example="五金交电", description="类型名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="2", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="path", type="string", example="2,3", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越小越靠前"),
     *                          @SWG\Property( property="level", type="string", example="2", description="等级，从0开始"),
     *                          @SWG\Property( property="created", type="string", example="1639465518", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1639465518", description="修改时间"),
     *                          @SWG\Property( property="is_show", type="string", example="1", description="是否展示,1展示 0不展示"),
     *                          @SWG\Property( property="cur_level", type="string", example="1", description="当前等级，从0开始"),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getTypeList(request $request)
    {
        $settingService = new MerchantSettingService();
        $params = $request->all('sort_order_by');

        if (!empty($params['sort_order_by']) && in_array($params['sort_order_by'], ['asc','desc'])) {
            $orderBy['sort'] = $params['sort_order_by'];
        }
        $orderBy['updated'] = 'DESC';
        $filter = [
            'company_id' => app('auth')->user()->get('company_id'),
        ];
        $isShowChildren = 'false' == $request->input('is_show_children', 'true') ? false : true;
        if ($request->input('name', '')) {
            $filter['name|contains'] = $request->input('name');
            $result = $settingService->getTypeListByName($filter, $isShowChildren, 1, -1, $orderBy);
        } else {
            $result = $settingService->getTypeList($filter, $isShowChildren, 1, -1, $orderBy);
        }
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/merchant/visibletype/list",
     *     summary="获取可见的商户类型列表",
     *     tags={"商户"},
     *     description="获取可见商户类型列表,可以根据名称筛选。查询商户类型数据时，只返回有可见经营范围的数据。",
     *     operationId="getVisibleTypeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="parent_id", in="query", description="父级ID,顶级传0", required=false, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="商户类型名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="3", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="name", type="string", example="五金交电", description="类型名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="2", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="path", type="string", example="2,3", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越小越靠前"),
     *                          @SWG\Property( property="level", type="string", example="2", description="等级"),
     *                          @SWG\Property( property="created", type="string", example="1639465518", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1639465518", description="修改时间"),
     *                          @SWG\Property( property="is_show", type="boolean", example="1", description="是否展示,1展示 0不展示"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getVisibleTypeList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new MerchantSettingService();
        $filter = [
            'company_id' => $companyId,
            'is_show' => true,
            'parent_id' => $request->input('parent_id', 0),
        ];
        if ($request->input('name', '')) {
            $filter['name|contains'] = $request->input('name');
        }
        $result = $settingService->getVisibleTypeList($filter, '*', 1, -1, ['sort' => 'ASC', 'created' => 'ASC']);
        return $this->response->array($result['list'] ?? []);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/type/create",
     *     summary="添加商户类型",
     *     tags={"商户"},
     *     description="添加商户类型",
     *     operationId="createType",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="类型名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="父级id,顶级传0",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         description="排序,大于等于0的整数",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="is_show",
     *         in="query",
     *         description="是否显示 0:不显示 1:显示",
     *         required=true,
     *         type="string"
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
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function createType(Request $request, MerchantSettingService $settingService)
    {
        $rules = [
            'name' => ['required|max:18', trans('MerchantBundle.name_required_max_18_chars')],
            'sort' => ['required|numeric|min:0|max:999999', trans('MerchantBundle.sort_0_to_999999_integer')],
            'parent_id' => ['numeric|min:0', trans('MerchantBundle.parent_id_min_0')],
        ];
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('name', 'sort', 'parent_id', 'is_show');
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = $settingService->createMerchantType($companyId, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/merchant/type/{id}",
     *     summary="更新单条商户类型信息",
     *     tags={"商户"},
     *     description="更新单条商户类型信息",
     *     operationId="updateType",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="ID", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="类型名称", required=false, type="string"),
     *     @SWG\Parameter( name="sort", in="query", description="排序", required=false, type="string"),
     *     @SWG\Parameter( name="is_show", in="query", description="是否显示 0:不显示 1:显示", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function updateType($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $settingService = new MerchantSettingService();

        $data = $request->input();
        $rules = [
            'name' => ['max:18', trans('MerchantBundle.name_max_18_chars')],
            'sort' => ['numeric|min:0|max:999999', trans('MerchantBundle.sort_0_to_999999_integer')],
            'parent_id' => ['numeric|min:0', trans('MerchantBundle.parent_id_min_0')],
        ];
        $companyId = app('auth')->user()->get('company_id');
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $result = $settingService->updateMerchantType(['id' => $id, 'company_id' => $companyId], $data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/merchant/type/{id}",
     *     summary="删除商户类型",
     *     tags={"商户"},
     *     description="删除商户类型",
     *     operationId="deleteType",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="id", in="path", description="商户类型ID", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function deleteType($id)
    {
        $params['id'] = $id;
        $validator = app('validator')->make($params, [
            'id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('MerchantBundle.delete_category_error'), $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $settingService = new MerchantSettingService();
        $params = [
            'id' => $id,
            'company_id' => $company_id,
        ];
        $result = $settingService->deleteMerchantType($params);

        return $this->response->array(['status' => true]);
    }
}
