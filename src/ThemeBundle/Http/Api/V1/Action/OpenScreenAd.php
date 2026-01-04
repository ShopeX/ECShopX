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

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use ThemeBundle\Services\OpenScreenAdServices;

class OpenScreenAd extends Controller
{
    /**
     * @SWG\GET(
     *     path="/openscreenad/set",
     *     summary="开屏广告信息",
     *     tags={"模版"},
     *     description="开屏广告信息",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *            @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="ad_material", type="string"),
     *                     @SWG\Property(property="is_enable", type="string"),
     *                     @SWG\Property(property="waiting_time", type="string"),
     *                     @SWG\Property(property="ad_url", type="string"),
     *                     @SWG\Property(property="app", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $userinfo = app('auth')->user()->get();
        $OpenScreenAd = new OpenScreenAdServices();
        $data = $OpenScreenAd->getInfo($userinfo['company_id']);
        if (empty($data)) {
            $response = [];
        } else {
            $response = $data;
        }

        return $this->response->array($response);
    }


    /**
     * @SWG\POST(
     *     path="/openscreenad/set",
     *     summary="开屏广告信息保存",
     *     tags={"模版"},
     *     description="开屏广告信息保存",
     *     operationId="Save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="ad_material", in="formData", description="广告素材", type="string" ),
     *     @SWG\Parameter( name="material_type", in="formData", description="材料种类", type="string" ),
     *     @SWG\Parameter( name="is_enable", in="formData", description="是否启用0否1是", type="string" ),
     *     @SWG\Parameter( name="position", in="formData", description="位置", type="string" ),
     *     @SWG\Parameter( name="is_jump", in="formData", description="是否允许跳过", type="string" ),
     *     @SWG\Parameter( name="waiting_time", in="formData", description="等待时间，秒", type="string" ),
     *     @SWG\Parameter( name="ad_url[id]", in="formData", description="链接地址ID", type="string" ),
     *     @SWG\Parameter( name="ad_url[title]", in="formData", description="链接地址标题名称", type="string" ),
     *     @SWG\Parameter( name="ad_url[imgUrl]", in="formData", description="链接地址图片地址", type="string" ),
     *     @SWG\Parameter( name="ad_url[linkPage]", in="formData", description="链接地址链接页", type="string" ),
     *     @SWG\Parameter( name="app", in="formData", description="应用端", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function Save(Request $request)
    {
        // 请求参数
        $params = $request->all('ad_material', 'is_enable', 'show_time', 'position', 'is_jump', 'material_type', 'waiting_time', 'ad_url', 'app');

        // 验证数据
        $rules = [
            'ad_material' => ['required', '请上传广告素材'],
            'is_enable' => ['required', '请选择是否启用'],
            'show_time' => ['required|in:always,first', '请选择曝光时间'],
            'waiting_time' => ['required', '请设置秒数'],
            // 'app' => ['required', '请选择应用端'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        // 用户信息
        $userinfo = app('auth')->user()->get();

        $OpenScreenAd = new OpenScreenAdServices();
        $id = $OpenScreenAd->Save($userinfo['company_id'], $params);

        // 新增，添加
        if ($id) {
            $response['status'] = true;
            return $this->response->array($response);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }
}
