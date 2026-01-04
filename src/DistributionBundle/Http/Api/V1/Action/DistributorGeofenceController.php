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

namespace DistributionBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use DistributionBundle\Services\DistributorGeofenceService;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class DistributorGeofenceController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/distributor/geofence",
     *     summary="获取一个店铺的所有围栏或某个围栏",
     *     tags={"店铺"},
     *     description="获取一个店铺的所有围栏",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_geofence_id", in="query", description="店铺围栏的主键id", type="integer", required=false),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="integer", required=true),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",required={"id","company_id","distributor_id","config_service_local_id","geofence_id","geofence_data","geofence_list","status","created","updated"},
     *                 @SWG\Property( property="id", type="integer", default="", description="围栏id"),
     *                 @SWG\Property( property="company_id", type="integer", default="", description="公司id"),
     *                 @SWG\Property( property="distributor_id", type="string", default="", description="门店id"),
     *                 @SWG\Property( property="config_service_local_id", type="string", default="", description="地图配置服务的主键id"),
     *                 @SWG\Property( property="geofence_id", type="string", default="", description="第三方的的围栏id"),
     *                 @SWG\Property( property="geofence_data", type="object", default="", description="围栏数据",
     *                     @SWG\Property( property="geofence_id", type="string", default="", description="第三方的的围栏id"),
     *                     @SWG\Property( property="type", type="string", default="", description="围栏类型"),
     *                     @SWG\Property( property="name", type="string", default="", description="围栏名称"),
     *                     @SWG\Property( property="params", type="object", default="", description="围栏",
     *                         @SWG\Property( property="points", type="string", default="121.417732,31.175441;121.457732,31.175441;121.457732,31.185441;121.417732,31.185441", description="围栏多个坐标的具体内容，多个经纬度由逗号连接"),
     *                     ),
     *                 ),
     *                 @SWG\Property( property="geofence_list", type="array", default="", description="围栏的经纬度信息",
     *                     @SWG\Items(type="object",required={"lng","lat"},
     *                         @SWG\Property( property="lng", type="string", default="121.417732", description="经度"),
     *                         @SWG\Property( property="lat", type="string", default="31.175441", description="纬度"),
     *                     )
     *                 ),
     *                 @SWG\Property( property="status", type="integer", default="", description="当前店铺围栏的状态【1 开启】【0 禁用】"),
     *                 @SWG\Property( property="created", type="integer", default="", description="创建时间"),
     *                 @SWG\Property( property="updated", type="integer", default="", description="更新时间"),
     *             ),
     *         )
     *     ),
     * )
     */
    public function get(Request $request)
    {
        // 获取公司id
        $companyId = (int)app('auth')->user()->get('company_id');
        // 店铺id
        $distributorId = (int)$request->input("distributor_id");
        if (empty($distributorId)) {
            return $this->response->array([]);
        }

        $filter = [
            "company_id" => $companyId,
            "distributor_id" => $distributorId
        ];
        if (!is_null($distributorGeofenceId = $request->input("distributor_geofence_id"))) {
            $filter["id"] = $distributorGeofenceId;
        }

        $distributorGeofenceService = new DistributorGeofenceService();
        // 获取围栏信息
        $request->input("distributor_geofence_id");
        $result = $distributorGeofenceService->listsWithJoin($filter, [], [], ["*"], ["service_id"], ["app_key", "type as config_type"], $this->getPage(), $this->getPageSize());

        foreach ($result["list"] as &$item) {
            $item = $distributorGeofenceService->handleData($item);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/distributor/geofence",
     *     summary="创建一个店铺围栏",
     *     tags={"店铺"},
     *     description="创建一个店铺围栏",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_geofence_id", in="formData", description="店铺围栏的主键id，如果存在则更新，如果不存在则新增", type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", type="integer"),
     *     @SWG\Parameter( name="data[0][lat]", in="formData", description="店铺围栏的信息，纬度", type="string"),
     *     @SWG\Parameter( name="data[0][lng]", in="formData", description="店铺围栏的信息，经度", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",required={"id","company_id","distributor_id","config_service_local_id","geofence_id","geofence_data","status","created","updated"},
     *                 @SWG\Property( property="id", type="integer", default="", description="围栏id"),
     *                 @SWG\Property( property="company_id", type="integer", default="", description="公司id"),
     *                 @SWG\Property( property="distributor_id", type="string", default="", description="门店id"),
     *                 @SWG\Property( property="config_service_local_id", type="string", default="", description="地图配置服务的主键id"),
     *                 @SWG\Property( property="geofence_id", type="string", default="", description="第三方的的围栏id"),
     *                 @SWG\Property( property="geofence_data", type="object", default="", description="围栏数据",
     *                     @SWG\Items(required={"geofence_id","type","name","params"},
     *                         @SWG\Property( property="geofence_id", type="string", default="", description="第三方的的围栏id"),
     *                         @SWG\Property( property="type", type="string", default="", description="围栏类型"),
     *                         @SWG\Property( property="name", type="string", default="", description="围栏名称"),
     *                         @SWG\Property( property="params", type="object", default="", description="围栏",
     *                             @SWG\Items(required={"points"},
     *                                 @SWG\Property( property="points", type="string", default="121.417732,31.175441;121.457732,31.175441;121.457732,31.185441;121.417732,31.185441", description="围栏多个坐标的具体内容，多个经纬度由逗号连接"),
     *                             )
     *                         ),
     *                     )
     *                 ),
     *                 @SWG\Property( property="status", type="integer", default="1", description="当前店铺围栏的状态【1 开启】【0 禁用】"),
     *                 @SWG\Property( property="created", type="integer", default="1", description="创建时间"),
     *                 @SWG\Property( property="updated", type="integer", default="1", description="更新时间"),
     *             ),
     *         )
     *     ),
     * )
     */
    public function save(Request $request)
    {
        // 获取公司id
        $companyId = (int)app('auth')->user()->get('company_id');
        // 店铺id
        $distributorId = (int)$request->input("distributor_id");
        // 更新店铺围栏信息
        $data = (array)$request->input("data");
        if ($distributorId <= 0 || empty($data)) {
            throw new \Exception("参数有误！");
        }
        // 尝试获取店铺围栏的主键id
        $distributorGeofenceId = $request->input("distributor_geofence_id");

        // 整理数据
        $data = [
            "data" => $data
        ];
        if (!is_null($distributorGeofenceId)) {
            $data["id"] = $distributorGeofenceId;
        }
        // 保存围栏
        $result = (new DistributorGeofenceService())->save($companyId, $distributorId, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/distributor/geofence",
     *     summary="删除一个店铺围栏, 或删除该店铺下的所有围栏",
     *     tags={"店铺"},
     *     description="删除一个店铺围栏, 或删除该店铺下的所有围栏",
     *     operationId="delete",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_geofence_id", in="formData", description="店铺围栏的主键id", type="string", required=false),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",required={"status"},
     *                 @SWG\Property( property="status", type="integer", default="1", description="操作状态【1 已删除】【0 删除失败】"),
     *             ),
     *         )
     *     ),
     * )
     */
    public function delete(Request $request)
    {
        // 获取公司id
        $companyId = (int)app('auth')->user()->get('company_id');
        // 店铺id
        $distributorId = (int)$request->input("distributor_id");
        if ($distributorId <= 0) {
            throw new \Exception("参数有误！");
        }
        // 删除围栏
        (new DistributorGeofenceService())->deleteByDistributorId($companyId, $distributorId, $request->input("distributor_geofence_id"));
        return $this->response->array(["status" => 1]);
    }
}
