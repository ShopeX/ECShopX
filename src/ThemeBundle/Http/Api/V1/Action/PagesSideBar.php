<?php

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use ThemeBundle\Services\PagesSideBarService;

class PagesSideBar extends Controller
{
    /**
     * @SWG\Get(
     *     path="/sidebar/list",
     *     tags={"模版"},
     *     summary="侧边栏列表",
     *     description="侧边栏列表",
     *     operationId="getList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=true,
     *         type="integer",
     *         minimum=1
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量",
     *         required=true,
     *         type="integer",
     *         minimum=1,
     *         maximum=50
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="query",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         description="侧边栏ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="侧边栏名称",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="query",
     *         description="页面类型",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Property(property="data", type="object",
     *             @SWG\Property(property="total_count", type="integer", example="100", description="总数"),
     *             @SWG\Property(property="list", type="array", @SWG\Items(ref="#/definitions/PagesSideBar")),
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function getList(Request $request)
    {
        $params = $request->all();

        $validator = app('validator')->make($params, [
            'page'     => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $page  = $params['page'];
        $pageSize = $params['pageSize'];

        if (isset($params['regionauth_id']) && $params['regionauth_id']) {
            $filter['regionauth_id'] = $params['regionauth_id'];
        }

        if (isset($params['id']) && $params['id']) {
            $filter['id'] = $params['id'];
        }

        if (isset($params['name']) && $params['name']) {
            $filter['name|contains'] = $params['name'];
        }

        if (isset($params['page_type']) && $params['page_type']) {
            $filter['pages|contains'] = ','.$params['page_type'].',';
        }

        $orderBy = ['id' => 'desc'];
        $cols = 'id,company_id,regionauth_id,name,pages,disabled,created,updated';

        $service = new PagesSideBarService();
        $result = $service->lists($filter, $cols, $page, $pageSize, $orderBy);
        foreach ($result['list'] as $key => $val) {
            if ($val['pages']) {
                $result['list'][$key]['pages'] = explode(',', trim($val['pages'], ','));
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/sidebar/{id}",
     *     tags={"模版"},
     *     summary="侧边栏详情",
     *     description="根据ID获取侧边栏详情",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="侧边栏ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(ref="#/definitions/PagesSideBar")
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function getInfo(Request $request, $id)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        $service = new PagesSideBarService();
        $result = $service->getInfo($filter);
        if (isset($result['pages']) && $result['pages']) {
            $result['pages'] = explode(',', trim($result['pages'], ','));
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/sidebar",
     *     tags={"模版"},
     *     summary="创建侧边栏",
     *     description="创建侧边栏",
     *     operationId="create",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         description="侧边栏名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pages",
     *         in="formData",
     *         description="关联页面",
     *         required=true,
     *         type="array",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="formData",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="disabled",
     *         in="formData",
     *         description="是否禁用,true禁用/false启用",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="setting",
     *         in="formData",
     *         description="设置信息,json格式",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(ref="#/definitions/PagesSideBar")
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function create(Request $request)
    {
        $params = $request->all();
        $params['company_id'] = app('auth')->user()->get('company_id');

        $validator = app('validator')->make($params, [
            'name' => 'required',
            'pages' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        if (is_array($params['pages']) && $params['pages']) {
            $params['pages'] = ','.implode(',', $params['pages']).',';
        }

        $service = new PagesSideBarService();
        $regionauthId = $params['regionauth_id'] ?? 0;
        $service->checkPagesExist($params['company_id'], $regionauthId, $params['pages']);
        $result = $service->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/sidebar/{id}",
     *     tags={"模版"},
     *     summary="更新侧边栏",
     *     description="更新侧边栏",
     *     operationId="update",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="侧边栏ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         description="侧边栏名称",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pages",
     *         in="formData",
     *         description="关联页面",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="formData",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="disabled",
     *         in="formData",
     *         description="是否禁用,true禁用/false启用",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="setting",
     *         in="formData",
     *         description="设置信息,json格式",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function update(Request $request, $id)
    {
        $params = $request->all();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;
        $params['company_id'] = $filter['company_id'];

        $rules = [];
        if (isset($params['name'])) {
            $rules['name'] = 'required';
        }
        if (isset($params['pages'])) {
            $rules['pages'] = 'required';
        }
        if ($rules) {
            $validator = app('validator')->make($params, $rules);
            if ($validator->fails()) {
                throw new ResourceException($validator->errors()->first());
            }
        }

        if (isset($params['disabled'])) {
            $params['disabled'] = $params['disabled'] == 'true' ? true : boolval($params['disabled']);
        }

        if (isset($params['pages']) && is_array($params['pages']) && $params['pages']) {
            $params['pages'] = ','.implode(',', $params['pages']).',';
        }

        $service = new PagesSideBarService();
        if (isset($params['pages']) && $params['pages']) {
            if (isset($params['regionauth_id']) && $params['regionauth_id']) {
                $regionauthId = $params['regionauth_id'];
            } else {
                $info = $service->getInfo($filter);
                if (!$info) {
                    throw new ResourceException('未查询到更新数据');
                }
                $regionauthId = $info['regionauth_id'];
            }
            $service->checkPagesExist($params['company_id'], $regionauthId, $params['pages'], $id);
        }
        $result = $service->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/sidebar/{id}",
     *     tags={"模版"},
     *     summary="删除侧边栏",
     *     description="删除侧边栏",
     *     operationId="delete",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="侧边栏ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function delete(Request $request, $id)
    {
        $params = $request->all();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        $service = new PagesSideBarService();
        $result = $service->deleteBy($filter);
        return $this->response->array(['status' => true]);
    }
    
}

/**
 * @SWG\Definition(
 *     definition="PagesSideBar",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", description="ID"),
 *     @SWG\Property(property="company_id", type="integer", description="公司ID"),
 *     @SWG\Property(property="regionauth_id", type="integer", description="区域ID"),
 *     @SWG\Property(property="name", type="string", description="侧边栏名称"),
 *     @SWG\Property(property="pages", type="array", @SWG\Items(type="string"), description="关联页面"),
 *     @SWG\Property(property="disabled", type="boolean", description="是否禁用"),
 *     @SWG\Property(property="setting", type="string", description="设置信息,json格式"),
 *     @SWG\Property(property="created", type="integer", description="创建时间"),
 *     @SWG\Property(property="updated", type="integer", description="更新时间")
 * )
 */