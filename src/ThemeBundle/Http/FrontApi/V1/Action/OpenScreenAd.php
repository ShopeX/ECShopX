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
        // CONST: 1E236443
        $params = $request->all('company_id');
        $auth_info = $request->get('auth');

        $OpenScreenAd = new OpenScreenAdServices();
        $data = $OpenScreenAd->getInfo($auth_info['company_id']);
        if (empty($data)) {
            $response = [];
        } else {
            $response = $data;
        }

        return $this->response->array($response);
    }
}
