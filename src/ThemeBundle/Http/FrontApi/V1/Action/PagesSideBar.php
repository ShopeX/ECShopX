<?php

namespace ThemeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use ThemeBundle\Services\PagesSideBarService;
use Dingo\Api\Exception\ResourceException;

class PagesSideBar extends Controller
{

    /**
     * @SWG\Get(
     *     path="/wxapp/sidebar/info",
     *     tags={"模版-前端"},
     *     summary="侧边栏详情",
     *     description="根据ID获取侧边栏详情",
     *     operationId="getPagesSideBar",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="query",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="query",
     *         description="页面类型",
     *         required=true,
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
    public function getPagesSideBar(Request $request)
    {
        $params = $request->all();
        $authInfo = $request->get('auth');

        $validator = app('validator')->make($params, [
            'page_type'     => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $filter['company_id'] = $authInfo['company_id'];
        $filter['regionauth_id'] = $params['regionauth_id'] ?? 0;
        $filter['disabled'] = false;
        $filter['pages|contains'] = ','.$params['page_type'].',';

        $service = new PagesSideBarService();
        $list = $service->getLists($filter, '*', 1, 1);
        $result = [];
        if ($list) {
            $result = $list[0];
        }
        return $this->response->array($result);
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