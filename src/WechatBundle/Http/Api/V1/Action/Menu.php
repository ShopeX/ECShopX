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

namespace WechatBundle\Http\Api\V1\Action;

use WechatBundle\Services\WechatMenuServices;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\StoreResourceFailedException;

class Menu extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wechat/menu",
     *     summary="添加菜单",
     *     tags={"微信"},
     *     description="本地菜单保存",
     *     operationId="addMenu",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="menu",
     *          in="body",
     *          description="子菜单列表",
     *          @SWG\Schema(
     *              @SWG\Items(ref="#/definitions/WechatMenu")
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
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function addMenu(Request $request)
    {
        $postdata = $this->__checkPost($request->all());
        $menuServices = new WechatMenuServices();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        $result = $menuServices->addMenuTree($authorizerAppId, $companyId, $postdata);
        return $this->response->array($postdata);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/menutree",
     *     summary="获取菜单树形列表",
     *     tags={"微信"},
     *     description="获取本地菜单树形结构列表",
     *     operationId="getMenuTree",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="authorizer_appid",
     *         in="query",
     *         description="公众号标识",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="0"),
     *                  @SWG\Property( property="name", type="string", example="菜单"),
     *                  @SWG\Property( property="menu_type", type="string", example="1"),
     *                  @SWG\Property( property="news_type", type="string", example="text"),
     *                  @SWG\Property( property="sort", type="string", example=""),
     *                  @SWG\Property( property="is_show", type="string", example=""),
     *                  @SWG\Property( property="url", type="string", example=""),
     *                  @SWG\Property( property="app_id", type="string", example=""),
     *                  @SWG\Property( property="pagepath", type="string", example=""),
     *                  @SWG\Property( property="content", type="string", example="aaaa"),
     *                  @SWG\Property( property="second_menu", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="0_0"),
     *                          @SWG\Property( property="name", type="string", example="菜单"),
     *                          @SWG\Property( property="menu_type", type="string", example="1"),
     *                          @SWG\Property( property="news_type", type="string", example="text"),
     *                          @SWG\Property( property="sort", type="string", example=""),
     *                          @SWG\Property( property="is_show", type="string", example=""),
     *                          @SWG\Property( property="url", type="string", example=""),
     *                          @SWG\Property( property="app_id", type="string", example=""),
     *                          @SWG\Property( property="pagepath", type="string", example=""),
     *                          @SWG\Property( property="content", type="string", example="bbb"),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getMenuTree(Request $request)
    {
        $menuServices = new WechatMenuServices();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        $result = $menuServices->getMenuTree($authorizerAppId, $companyId);
        if ($result) {
            foreach ($result as $key => $menu) {
                $result[$key]['menu_type'] = intval($menu['menu_type']);
                if (isset($menu['second_menu']) && $menu['second_menu']) {
                    foreach ($menu['second_menu'] as $k => $val) {
                        $result[$key]['second_menu'][$k]['menu_type'] = intval($val['menu_type']);
                    }
                } else {
                    $result[$key]['second_menu'] = [];
                }
            }
            return $this->response->array($result);
        }
        return $this->response->array(array());
    }

    /**
     * @SWG\Delete(
     *     path="/wechat/menu",
     *     summary="删除菜单[暂时废弃改接口]",
     *     tags={"微信"},
     *     description="本地菜单移除",
     *     operationId="removeMenu",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="authorizer_appid",
     *         in="query",
     *         description="公众号标识",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="menu",
     *          in="body",
     *          description="子菜单列表",
     *          @SWG\Schema(
     *              @SWG\Items(ref="#/definitions/WechatMenu")
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
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function removeMenu(Request $request)
    {
        $postdata = $this->__checkPost($request->all());
        $menuServices = new WechatMenuServices();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        $result = $menuServices->addMenuTree($authorizerAppId, $companyId, $postdata);
        return $this->response->array(['status' => $result]);
    }

    private function __checkPost($postdata)
    {
        foreach ($postdata as $value) {
            if (!$value['name']) {
                throw new StoreResourceFailedException('主菜单名称必填');
            }
            if (strlen($value['name']) > 12) {
                throw new StoreResourceFailedException('主菜单名称不超过4个汉字或12个字母');
            }
            foreach ($value['second_menu'] as $val) {
                if (!$val['name']) {
                    throw new StoreResourceFailedException('子菜单名称必填');
                }
                if (strlen($value['name']) > 24) {
                    throw new StoreResourceFailedException('子菜单名称不超过8个汉字或16个字母');
                }

                if (!$val['content']) {
                    throw new StoreResourceFailedException('子菜单内容必填');
                }
            }
        }
        return $postdata;
    }
}
