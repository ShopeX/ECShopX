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

namespace ThemeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use ThemeBundle\Services\OpenScreenAdServices;

class OpenScreenAd extends Controller
{
    // CONST: 1E236443
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/openscreenad",
     *     summary="开屏广告信息",
     *     tags={"模板"},
     *     description="开屏广告信息",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="path",
     *         description="公司id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *            @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="ad_material", type="string"),
     *                     @SWG\Property(property="is_enable", type="string"),
     *                     @SWG\Property(property="position", type="string"),
     *                     @SWG\Property(property="is_jump", type="string"),
     *                     @SWG\Property(property="waiting_time", type="string"),
     *                     @SWG\Property(property="ad_url", type="string"),
     *                     @SWG\Property(property="app", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $auth_info = $request->get('auth');

        $filter['company_id'] = $auth_info['company_id'];
        $filter['is_enable'] = 1;
        $OpenScreenAd = new OpenScreenAdServices();
        $data = $OpenScreenAd->lists($filter, '*', 1, 1);
        $result = !empty($data['list']) ? reset($data['list']) : [];

        // 只有配置了开始/结束时间才校验；都是 0 表示不限期（管理端未传时间时的默认值）
        if (!empty($result)) {
            $now = time();
            $startTime = (int) ($result['start_time'] ?? 0);
            $endTime = (int) ($result['end_time'] ?? 0);
            if (!($startTime === 0 && $endTime === 0)) {
                if ($startTime > 0 && $startTime > $now) {
                    $result = [];
                } elseif ($endTime > 0 && $endTime < $now) {
                    $result = [];
                }
            }
        }

        if ($result) {
            $result['ad_url'] = json_decode($result['ad_url'], true);
        }

        return $this->response->array($result);
    }
}
