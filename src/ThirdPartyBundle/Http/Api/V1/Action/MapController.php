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

namespace ThirdPartyBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ThirdPartyBundle\Services\Map\MapService;
use Swagger\Annotations as SWG;

class MapController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/third/map/setting",
     *     tags={"地图配置"},
     *     summary="地图配置 - 更新地图类型",
     *     description="地图配置 - 更新地图类型",
     *     operationId="set",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Parameter(name="map_type", in="formData", description="地图类型 【amap: 高德地图】【tencent 腾讯地图】", required=true, type="string", default=""),
     *     @SWG\Parameter(name="app_key", in="formData", description="第三方控制台中生成的key", required=true, type="string", default=""),
     *     @SWG\Parameter(name="app_secret", in="formData", description="第三方控制台中生成的秘钥", required=true, type="string", default=""),
     *     @SWG\Parameter(name="is_default", in="formData", description="是否启用为默认 【0 关闭】【1 开启】", required=true, type="string", default=""),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"id","company_id","type","app_key","app_secret","is_default","created","updated"},
     *                 @SWG\Property(property="id", type="integer", default="1", description="id"),
     *                 @SWG\Property(property="company_id", type="integer", default="1", description="公司company_id"),
     *                 @SWG\Property(property="type", type="string", default="amap", description="地图类型 【amap: 高德地图】【tencent 腾讯地图】"),
     *                 @SWG\Property(property="app_key", type="string", default="", description="第三方控制台中生成的key"),
     *                 @SWG\Property(property="app_secret", type="string", default="", description="第三方控制台中生成的秘钥"),
     *                 @SWG\Property(property="is_default", type="integer", default="1", description="是否启用为默认 【0 关闭】【1 开启】"),
     *                 @SWG\Property(property="created", type="integer", default="", description="新增时间"),
     *                 @SWG\Property(property="updated", type="integer", default="", description="更新时间"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function set(Request $request)
    {
        // 获取公司id
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);

        $formData = $request->input();
        if ($massageBag = validation($formData, [
            "map_type" => ["required"],
            "app_key" => ["required"],
            "is_default" => ["required", Rule::in([MapService::DEFAULT_NO, MapService::DEFAULT_YES])]
        ], [
            "map_type.required" => "地图配置类型必填！",
            "app_key.required" => "key必填！",
            "*.*" => "参数有误"
        ])) {
            throw new ResourceException($massageBag->first());
        }

        $info = (new MapService())->setConfig($companyId, $formData["map_type"], [
            "app_key" => $formData["app_key"],
            "app_secret" => $formData["app_secret"] ?? "",
            "is_default" => $formData["is_default"],
        ]);

        return $this->response->array($info);
    }

    /**
     * @SWG\Get(
     *     path="/third/map/setting",
     *     tags={"地图配置"},
     *     summary="地图配置 - 获取地图类型",
     *     description="地图配置 - 获取地图类型",
     *     operationId="get",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Property(property="data", type="object", description="", required={"total_count", "list"},
     *               @SWG\Property(property="total_count", type="integer", default="1", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                    @SWG\Items(type="object", required={"id","company_id","type","app_key","app_secret","is_default","created","updated"},
     *                     @SWG\Property(property="id", type="integer", default="1", description="id"),
     *                     @SWG\Property(property="company_id", type="integer", default="1", description="公司company_id"),
     *                     @SWG\Property(property="type", type="string", default="amap", description="地图类型 【amap: 高德地图】【tencent 腾讯地图】"),
     *                     @SWG\Property(property="app_key", type="string", default="", description="第三方控制台中生成的key"),
     *                     @SWG\Property(property="app_secret", type="string", default="", description="第三方控制台中生成的秘钥"),
     *                     @SWG\Property(property="is_default", type="integer", default="1", description="是否启用为默认 【0 关闭】【1 开启】"),
     *                     @SWG\Property(property="created", type="integer", default="", description="新增时间"),
     *                     @SWG\Property(property="updated", type="integer", default="", description="更新时间"),
     *                    )
     *               ),
     *         ),
     *     ),
     * )
     */
    public function get(Request $request)
    {
        // 获取公司id
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);

        $list = (new MapService())->getConfigList($companyId);

        return $this->response->array($list);
    }
}
