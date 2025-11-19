<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use EspierBundle\Services\Cache\RedisCacheService;
use Illuminate\Http\Request;
use ThirdPartyBundle\Services\Map\MapService;
use Swagger\Annotations as SWG;

class MapController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/third_party/map/key",
     *     tags={"地图配置"},
     *     summary="地图配置 - 获取地图key",
     *     description="地图配置 - 获取地图key",
     *     operationId="get",
     *     @SWG\Parameter(name="type", in="query", description="地图的类型【amap: 高德地图】【tencent 腾讯地图】", required=false, type="string", default="amap"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Property(property="data", type="object", description="", required={"type", "key", "is_default"},
     *               @SWG\Property(property="type", type="integer", default="", description="地图的类型【amap: 高德地图】【tencent 腾讯地图】"),
     *               @SWG\Property(property="key", type="integer", default="1", description="地图的key"),
     *               @SWG\Property(property="is_default", type="integer", default="1", description="是否默认【0 不是】【1 是】"),
     *         ),
     *     ),
     * )
     */
    public function get(Request $request)
    {
        // 获取参数
        $authInfo = $request->get('auth');
        // 企业id
        $companyId = (int)$authInfo['company_id'];
        $type = $request->input("type", MapService::TYPE_AMAP);

        $redisService = new RedisCacheService($companyId, sprintf("third_party_map_%s", $type), 60);

        $data = $redisService->getByPrevention(function () use ($companyId, $type) {
            $data = (new MapService())->getConfigList($companyId, ["type" => MapService::TYPE_AMAP]);
            $list = (array)($data["list"] ?? []);
            return (array)array_shift($list);
        });

        return $this->response->array([
            "type" => $data["type"] ?? null,
            "key" => $data["app_key"] ?? null,
            "is_default" => $data["is_default"] ?? null,
        ]);
    }
}
