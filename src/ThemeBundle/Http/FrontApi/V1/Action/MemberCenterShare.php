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
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use ThemeBundle\Services\MemberCenterShareServices;

class MemberCenterShare extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/memberCenterShare/getInfo",
     *     summary="会员中心分享信息",
     *     tags={"模板"},
     *     description="会员中心分享信息",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="path",
     *         description="公司id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *            response=200,
     *            description="成功返回结构",
     *            @SWG\Schema(
     *              @SWG\Property(
     *              property="data",
     *              description="数据集合",
     *              type="object",
     *                   @SWG\Property(property="theme_member_center_share_id", type="integer"),
     *                   @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                   @SWG\Property(property="share_title", type="string", description="分享标题"),
     *                   @SWG\Property(property="share_description", type="string", description="分享描述"),
     *                   @SWG\Property(property="share_pic_wechatapp", type="string", description="分享图片小程序"),
     *                   @SWG\Property(property="share_pic_h5", type="string", description="分享图片h5"),
     *                   @SWG\Property(property="created", type="integer", description="创建时间"),
     *                   @SWG\Property(property="updated", type="integer", description="最后修改时间"),
     *              ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $company_id = $request->get('company_id');

        $params = [
            'company_id' => $company_id
        ];
        $rules = [
            'company_id' => ['required', '缺少company_id'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $service = new MemberCenterShareServices();
        $reslut = $service->detail($params);

        return $this->response->array($reslut);
    }
}
