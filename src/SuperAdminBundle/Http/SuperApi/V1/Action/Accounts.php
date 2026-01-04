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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use SuperAdminBundle\Services\AccountsService;
use SuperAdminBundle\Services\ShopMenuService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;

class Accounts extends BaseController
{
    /** @var accountsService */
    private $accountsService;

    /**
     * Accounts constructor.
     * @param AccountsService $accountsService
     */
    public function __construct(AccountsService $accountsService)
    {
        $this->accountsService = new $accountsService();
    }

    /**
     * @SWG\Post(path="/superadmin/account/add",
     *   tags={"管理员"},
     *   summary="添加平台管理员账户",
     *   description="开通账户",
     *   operationId="addAccounts",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter( name="login_name", in="query", description="登录用户名", required=true, type="string"),
     *   @SWG\Parameter( name="password", in="query", description="密码", required=true, type="string"),
     *   @SWG\Parameter( name="name", in="query", description="姓名", required=true, type="string"),
     *   @SWG\Parameter( name="status", in="query", description="开启状态(true,false)", required=true, type="string"),
     *   @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="account_id", type="string", example="2", description="账号id"),
     *                  @SWG\Property( property="login_name", type="string", example="test", description="登录账号名"),
     *                  @SWG\Property( property="password", type="string", example="$2y$10$M2wMAQjk11F0QZg18mnqv...", description="密码 | "),
     *                  @SWG\Property( property="name", type="string", example="test", description="姓名"),
     *                  @SWG\Property( property="super", type="string", example="1", description="是否超级管理员"),
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="created", type="string", example="1612511776", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612511776", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function addAccount(Request $request)
    {
        $params = $request->input();
        $rules = [
            'login_name' => ['required', '登录用户名必填'],
            'password' => ['required', '密码必填'],
            'name' => ['required', '姓名必填'],
            'status' => ['required|in:true,false', '开启状态必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $data = [
            'login_name' => $params['login_name'],
            'password' => $params['password'],
            'name' => $params['name'],
            'super' => false,
            'status' => (isset($params['status']) && $params['status']) ? true : false,
        ];
        $result = $this->accountsService->createAccount($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(path="/superadmin/account/updatePassword",
     *   tags={"管理员"},
     *   summary="更新管理员密码",
     *   description="修改密码",
     *   operationId="addAccounts",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter( name="old_password", in="query", description="旧密码必填,且长度不能小于6位", required=true, type="string"),
     *   @SWG\Parameter( name="password", in="query", description="新密码", required=true, type="string"),
     *   @SWG\Parameter( name="password_confirmation", in="query", description="确认新密码", required=true, type="string"),
     *   @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="account_id", type="string", example="2", description="账号id"),
     *                  @SWG\Property( property="login_name", type="string", example="test", description="登录账号名"),
     *                  @SWG\Property( property="password", type="string", example="$2y$10$M2wMAQjk11F0QZg18mnqv...", description="密码 | "),
     *                  @SWG\Property( property="name", type="string", example="test", description="姓名"),
     *                  @SWG\Property( property="super", type="string", example="1", description="是否超级管理员"),
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="created", type="string", example="1612511776", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612511776", description="修改时间"),
     *          ),
     *     )),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function updatePassword(Request $request)
    {
        $params = $request->input();
        $rules = [
            'old_password' => ['required|between:6,50', '旧密码必填,且长度不能小于6位'],
            'password' => ['required|between:6,50|confirmed', '新密码必填，且新密码和确认新密码必须一至'],
            'password_confirmation' => ['required|between:6,50', '确认新密码必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $account_id = app('auth')->user()->get('account_id');

        $result = $this->accountsService->updateAccountPassword($params, ['account_id' => $account_id]);

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/superadmin/permission",
     *     summary="获取平台后台权限详情",
     *     tags={"管理员"},
     *     description="获取平台后台权限详情",
     *     operationId="getPermission",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="shopmenu_id", type="string", example="10114", description="菜单id"),
     *                  @SWG\Property( property="company_id", type="string", example="0", description="公司id"),
     *                  @SWG\Property( property="name", type="string", example="首页", description="菜单名称"),
     *                  @SWG\Property( property="url", type="string", example="/dashboard", description="菜单对应路由"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序，数字越大越靠前"),
     *                  @SWG\Property( property="is_menu", type="string", example="true", description="是否为菜单"),
     *                  @SWG\Property( property="pid", type="string", example="0", description="上级菜单id"),
     *                  @SWG\Property( property="icon", type="string", example="tachometer-alt", description="菜单图标"),
     *                  @SWG\Property( property="is_show", type="string", example="true", description="是否显示"),
     *                  @SWG\Property( property="alias_name", type="string", example="index", description="菜单别名,唯一值"),
     *                  @SWG\Property( property="version", type="string", example="2", description=" 菜单版本,1:商家菜单;2:平台菜单,3:店铺菜单,4:供应商菜单 "),
     *                  @SWG\Property( property="menu_type", type="string", example="all", description="菜单所属类型 standard 标准版 platform 平台版 "),
     *                  @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     *                  @SWG\Property( property="created", type="string", example="1561355388", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1561355388", description="修改时间"),
     *                  @SWG\Property( property="level", type="string", example="1", description="等级"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getPermission()
    {
        // 根据帐号类型获取菜单
        $menuFilter['company_id'] = 0;
        $menuFilter['disabled'] = 0;
        $menuFilter['version'] = 2; // 平台菜单
        $shopMenuService = new ShopMenuService();

        $data = $shopMenuService->getShopMenu($menuFilter, false, false);

        return $this->response->array($data['tree']);
    }
}
