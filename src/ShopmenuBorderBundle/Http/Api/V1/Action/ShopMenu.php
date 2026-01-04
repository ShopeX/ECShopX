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

namespace ShopmenuBorderBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use SuperAdminBundle\Services\ShopMenuService;
use Dingo\Api\Exception\DeleteResourceFailedException;

class ShopMenu extends Controller
{
    /**
     * @SWG\Get(
     *     path="/super/admin/shopmenu",
     *     summary="获取店铺菜单列表",
     *     tags={"商家菜单"},
     *     description="获取店铺菜单列表",
     *     operationId="getShopMenu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="version",in="query",description="版本",required=false,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tree", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shopmenu_id", type="string", example="88", description="菜单id"),
     *                          @SWG\Property( property="company_id", type="string", example="0", description="公司id"),
     *                          @SWG\Property( property="name", type="string", example="概况", description="菜单名称"),
     *                          @SWG\Property( property="url", type="string", example="/", description="菜单对应路由"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                          @SWG\Property( property="is_menu", type="string", example="true", description="是否为菜单"),
     *                          @SWG\Property( property="pid", type="string", example="0", description="上级菜单id"),
     *                          @SWG\Property( property="apis", type="string", example="company.activate.info", description="API权限集"),
     *                          @SWG\Property( property="icon", type="string", example="tachometer-alt", description="菜单图标"),
     *                          @SWG\Property( property="is_show", type="string", example="true", description="是否显示"),
     *                          @SWG\Property( property="alias_name", type="string", example="index", description="菜单别名,唯一值"),
     *                          @SWG\Property( property="version", type="string", example="1", description="菜单版本,1:商家菜单;2:平台菜单,3:店铺菜单,4:供应商菜单"),
     *                          @SWG\Property( property="menu_type", type="string", example="all", description="菜单所属类型 standard 标准版 platform 平台版 "),
     *                          @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     *                          @SWG\Property( property="created", type="string", example="1572338485", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1612429365", description="修改时间"),
     *                          @SWG\Property( property="level", type="string", example="1", description="层级"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shopmenu_id", type="string", example="88", description="菜单id"),
     *                          @SWG\Property( property="company_id", type="string", example="0", description="公司id"),
     *                          @SWG\Property( property="name", type="string", example="概况", description="菜单名称"),
     *                          @SWG\Property( property="url", type="string", example="/", description="菜单对应路由"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                          @SWG\Property( property="is_menu", type="string", example="true", description="是否为菜单"),
     *                          @SWG\Property( property="pid", type="string", example="0", description="上级菜单id"),
     *                          @SWG\Property( property="apis", type="string", example="company.activate.info", description="API权限集"),
     *                          @SWG\Property( property="icon", type="string", example="tachometer-alt", description="菜单图标"),
     *                          @SWG\Property( property="is_show", type="string", example="true", description="是否显示"),
     *                          @SWG\Property( property="alias_name", type="string", example="index", description="菜单别名,唯一值"),
     *                          @SWG\Property( property="version", type="string", example="1", description="菜单版本,1:商家菜单;2:平台菜单,3:店铺菜单,4:供应商菜单"),
     *                          @SWG\Property( property="menu_type", type="string", example="all", description="菜单所属类型 standard 标准版 platform 平台版"),
     *                          @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     *                          @SWG\Property( property="created", type="string", example="1572338485", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1612429365", description="修改时间"),
     *                          @SWG\Property( property="level", type="string", example="1", description="层级"),
     *                          @SWG\Property( property="parent_name", type="string", example="无", description="上级菜单"),
     *                          @SWG\Property( property="isChildrenMenu", type="string", example="false", description="是否子菜单"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default",description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ShopMenuErrorResponse") ) )
     * )
     */
    public function getShopMenu(Request $request)
    {
        // Ver: 8d1abe8e
        $shopMenuService = new ShopMenuService();
        $filter['disabled'] = 0;
        $filter['company_id'] = 0;
        $filter['version'] = $request->input('version', 1);
        $data = $shopMenuService->getShopMenu($filter);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/super/admin/shopmenu/upload",
     *     summary="导入店铺菜单",
     *     tags={"商家菜单"},
     *     description="导入店铺菜单",
     *     operationId="uploadMenu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="company_id",in="query",description="企业id",required=false,type="integer"),
     *     @SWG\Parameter(name="file",in="formData",description="菜单数据文件(json编码内容)",required=true,type="file"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response(response="default",description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ShopMenuErrorResponse") ) )
     * )
     */
    public function uploadMenu(Request $request)
    {
        // Ver: 8d1abe8e
        $fileObject = $request->file('file');

        $json = file_get_contents($fileObject->path());
        $menus = json_decode($json, true);

        $shopMenuService = new ShopMenuService();
        $data = $shopMenuService->uploadMenus($menus);
        return $this->response->array(['status' => $data]);
    }

    /**
     * @SWG\Get(
     *     path="/super/admin/shopmenu/down",
     *     summary="下载店铺菜单列表",
     *     tags={"商家菜单"},
     *     description="下载店铺菜单列表",
     *     operationId="downShopMenu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="company_id",in="query",description="企业id",required=false,type="string"),
     *     @SWG\Parameter(name="version",in="query",description="菜单版本",required=false,type="string"),
     *     @SWG\Parameter(name="menu_type",in="query",description="菜单所属类型",required=false,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="name", type="string", example="菜单-2021-02-06.json", description="导出文件名"),
     *                  @SWG\Property( property="file", type="string", example="data:text/plain;base64,W3sic2hvcG1lbnVfaWQiOiIxMDExN", description="base64编码文件内容"),
     *          ),
     *     )),
     *     @SWG\Response(response="default",description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ShopMenuErrorResponse") ) )
     * )
     */
    public function downShopMenu(Request $request)
    {
        $shopMenuService = new ShopMenuService();
        $filter['disabled'] = 0;
        $filter['is_show'] = 1;
        if ($request->input('version')) {
            $filter['version'] = $request->input('version', 1);
        }

        $data = $shopMenuService->getShopMenu($filter);

        $jsonData = json_encode($data['list']);

        $filename = '菜单-'. date('Y-m-d').'.json';
        $response = array(
            'name' => $filename, //no extention needed
            'file' => "data:text/plain;base64,".base64_encode($jsonData)
        );
        return response()->json($response);
    }

    /**
     * @SWG\Post(
     *     path="/super/admin/shopmenu",
     *     summary="新增店铺菜单",
     *     tags={"商家菜单"},
     *     description="新增店铺菜单",
     *     operationId="addShopMenu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="company_id",in="query",description="企业id",required=true,type="integer"),
     *     @SWG\Parameter(name="name",in="query",description="菜单名称",required=true,type="string"),
     *     @SWG\Parameter(name="url",in="query",description="菜单对应路由",required=true,type="string"),
     *     @SWG\Parameter(name="is_menu",in="query",description="是否为菜单",required=false,type="string"),
     *     @SWG\Parameter(name="sort",in="query",description="排序",required=false,type="integer"),
     *     @SWG\Parameter(name="pid",in="query",description="上级菜单ID",required=false,type="integer"),
     *     @SWG\Parameter(name="alias_name",in="query",description="菜单唯一标识",required=false,type="string"),
     *     @SWG\Parameter(name="version",in="query",description="菜单版本",required=false,type="string"),
     *     @SWG\Parameter(name="menu_type",in="query",description="菜单所属类型",required=false,type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/ShopMenuInfo"
     *          ),
     *     )),
     *     @SWG\Response(response="default",description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ShopMenuErrorResponse") ) )
     * )
     */
    public function addShopMenu(Request $request)
    {
        // 用户信息
        $shopMenuService = new ShopMenuService();
        $postData = $request->input();
        $postData['version'] = $postData['version'] ?? 1;
        $postData['company_id'] = 0;

        $data = $shopMenuService->create($postData);
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/super/admin/shopmenu",
     *     summary="更新店铺菜单",
     *     tags={"商家菜单"},
     *     description="更新店铺菜单",
     *     operationId="updateShopMenu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="company_id",in="query",description="企业id",required=true,type="integer"),
     *     @SWG\Parameter(name="name",in="query",description="菜单名称",required=true,type="string"),
     *     @SWG\Parameter(name="url",in="query",description="菜单对应路由",required=true,type="string"),
     *     @SWG\Parameter(name="is_menu",in="query",description="是否为菜单",required=false,type="string"),
     *     @SWG\Parameter(name="sort",in="query",description="排序",required=false,type="integer"),
     *     @SWG\Parameter(name="pid",in="query",description="上级菜单ID",required=false,type="integer"),
     *     @SWG\Parameter(name="alias_name",in="query",description="菜单唯一标识",required=false,type="string"),
     *     @SWG\Parameter(name="version",in="query",description="菜单版本",required=true,type="string"),
     *     @SWG\Parameter(name="menu_type",in="query",description="菜单所属类型",required=false,type="string"),
     *     @SWG\Parameter(name="shopmenu_id",in="query",description="菜单id",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/ShopMenuInfo"
     *          ),
     *     )),
     *     @SWG\Response(response="default",description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ShopMenuErrorResponse") ) )
     * )
     */
    public function updateShopMenu(Request $request)
    {
        $requestData = $request->input();

        if (!$requestData['shopmenu_id']) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $updateResult = (new ShopMenuService())->updateMenus($requestData);

        return $this->response->array($updateResult);
    }

    /**
     * @SWG\Delete(
     *     path="/super/admin/shopmenu/{shopmenu_id}",
     *     summary="删除店铺菜单",
     *     tags={"商家菜单"},
     *     description="删除店铺菜单",
     *     operationId="deleteShopMenu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="shopmenu_id",in="path",description="菜单ID",required=true,type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response(response="default",description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ShopMenuErrorResponse") ) )
     * )
     */
    public function deleteShopMenu($shopmenuId)
    {
        if (!$shopmenuId) {
            throw new DeleteResourceFailedException("参数错误");
        }
        $deleteResult = (new ShopMenuService())->deleteMenus($shopmenuId);
        return $this->response->array($deleteResult);
    }

    /**
     * @SWG\Definition(
     *     definition="ShopMenuInfo",
     *     description="菜单信息",
     *     type="object",
     *     @SWG\Property( property="shopmenu_id", type="string", example="10467", description="菜单id"),
     *     @SWG\Property( property="company_id", type="string", example="0", description="公司id"),
     *     @SWG\Property( property="name", type="string", example="test2", description="菜单名称"),
     *     @SWG\Property( property="url", type="string", example="test123", description="菜单对应路由"),
     *     @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *     @SWG\Property( property="is_menu", type="string", example="1", description="是否为菜单"),
     *     @SWG\Property( property="pid", type="string", example="0", description="上级菜单id"),
     *     @SWG\Property( property="apis", type="string", example="null", description="API权限集"),
     *     @SWG\Property( property="icon", type="string", example="null", description="菜单图标"),
     *     @SWG\Property( property="is_show", type="string", example="1", description="是否展示,1展示 0不展示"),
     *     @SWG\Property( property="alias_name", type="string", example="test123", description="菜单别名,唯一值"),
     *     @SWG\Property( property="version", type="string", example="1", description="菜单版本,1:商家菜单;2:平台菜单,3:店铺菜单,4:供应商菜单"),
     *     @SWG\Property( property="menu_type", type="string", example="all", description="菜单所属类型 standard 标准版 platform 平台版 "),
     *     @SWG\Property( property="disabled", type="string", example="0", description="是否删除。0:否；1:是"),
     *     @SWG\Property( property="created", type="string", example="1612582094", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1612582094", description="修改时间"),
     * )
     */
}
