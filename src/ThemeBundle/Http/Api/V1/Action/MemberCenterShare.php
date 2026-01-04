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
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use ThemeBundle\Services\MemberCenterShareServices;

class MemberCenterShare extends Controller
{
    /**
     * @SWG\Post(
     *     path="/memberCenterShare/set",
     *     summary="设置会员中心分享信息",
     *     tags={"模版"},
     *     description="设置会员中心分享信息",
     *     operationId="set",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(name="share_title", in="path", description="分享标题", required=true, type="string"),
     *     @SWG\Parameter(name="share_description", in="path", description="分享描述", required=true, type="string"),
     *     @SWG\Parameter(name="share_pic_wechatapp", in="path", description="分享图片小程序", required=false, type="string"),
     *     @SWG\Parameter(name="share_pic_h5", in="path", description="分享图片H5", required=false, type="string"),
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
    public function set(Request $request)
    {
        $userinfo = app('auth')->user()->get();
        $company_id = $userinfo['company_id'];

        $share_title = $request->input('share_title');
        $share_description = $request->input('share_description');
        $share_pic_wechatapp = $request->input('share_pic_wechatapp', '');
        $share_pic_h5 = $request->input('share_pic_h5', '');

        $params = [
            'company_id' => $company_id,
            'share_title' => $share_title,
            'share_description' => $share_description,
            'share_pic_wechatapp' => $share_pic_wechatapp,
            'share_pic_h5' => $share_pic_h5
        ];

        $rules = [
            'share_title' => ['required', '缺少分享标题'],
            'share_description' => ['required', '缺少模板展示类型'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $service = new MemberCenterShareServices();
        $reslut = $service->save($params);

        return $this->response->array($reslut);
    }

    /**
     * @SWG\Get(
     *     path="/memberCenterShare/getInfo",
     *     summary="获取会员中心分享信息",
     *     tags={"模版"},
     *     description="获取会员中心分享信息",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
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
        $userinfo = app('auth')->user()->get();
        $company_id = $userinfo['company_id'];

        $params = [
            'company_id' => $company_id
        ];
        $service = new MemberCenterShareServices();
        $reslut = $service->detail($params);

        return $this->response->array($reslut);
    }
}
