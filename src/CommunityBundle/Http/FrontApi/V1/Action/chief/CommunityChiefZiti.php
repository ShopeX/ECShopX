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

namespace CommunityBundle\Http\FrontApi\V1\Action\chief;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityChiefService;
use CommunityBundle\Services\CommunityChiefZitiService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class CommunityChiefZiti extends BaseController
{
    /**
     * @SWG\Definition(
     *     definition="CommunityChiefZiti",
     *         @SWG\Property(property="ziti_id", type="integer", example="1",description="自提点ID"),
     *         @SWG\Property(property="chief_id", type="integer", example="1",description="团长ID"),
     *         @SWG\Property(property="ziti_name", type="string", example="123",description="自提点名称"),
     *          @SWG\Property(property="province", type="string", example="1",description="省"),
     *          @SWG\Property(property="city", type="string", example="1",description="市"),
     *          @SWG\Property(property="area", type="string", example="1",description="区"),
     *          @SWG\Property( property="regions_id", type="array",description="地区ID集合",
     *              @SWG\Items( type="string", example="310000"),
     *          ),
     *          @SWG\Property( property="regions", type="array",description="地区ID集合",
     *              @SWG\Items( type="string", example="上海市"),
     *          ),
     *          @SWG\Property(property="address", type="string", example="1",description="自提点地址"),
     *          @SWG\Property(property="ziti_pics", type="string", example="1",description="自提点图片"),
     *          @SWG\Property(property="ziti_contact_user", type="string", example="1",description="自提点联系人"),
     *          @SWG\Property(property="ziti_contact_mobile", type="string", example="1",description="自提点联系电话"),
     *          @SWG\Property(property="is_default", type="integer", example="101",description="是否默认"),
     *          @SWG\Property(property="ziti_status", type="string", example="desc",description="状态 success正常 fail作废"),
     * )
     */

    // 团长的自提点列表
    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/ziti",
     *     summary="获取团长自提点列表",
     *     tags={"社区团"},
     *     description="获取团长自提点列表",
     *     operationId="actionList",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/CommunityChiefZiti"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function actionList(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $service = new CommunityChiefZitiService();
        $list = $service->getChiefZitiList($authInfo['chief_id']);

        return $this->response->array($list);
    }

    // 团长创建自提点
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/ziti",
     *     summary="团长创建自提点",
     *     tags={"社区团"},
     *     description="团长创建自提点",
     *     operationId="actionCreate",
     *     @SWG\Parameter( name="ziti_name", in="query", description="自提点名称", required=true, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省", type="string" ),
     *     @SWG\Parameter( name="city", in="query", description="市", type="string" ),
     *     @SWG\Parameter( name="area", in="query", description="区", type="string" ),
     *     @SWG\Parameter( name="regions_id", in="query", description="地区ID,逗号分隔", type="string" ),
     *     @SWG\Parameter( name="regions_id", in="query", description="地区名称集合,逗号分隔", type="string" ),
     *     @SWG\Parameter( name="address", in="query", description="自提点地址", required=true, type="string"),
     *     @SWG\Parameter( name="ziti_pics", in="query", description="自提点图片", required=false, type="string"),
     *     @SWG\Parameter( name="ziti_contact_user", in="query", description="自提点联系人", required=false, type="string"),
     *     @SWG\Parameter( name="ziti_contact_mobile", in="query", description="自提点联系电话", required=false, type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="是否默认", required=false, type="string"),
     *     @SWG\Parameter( name="ziti_status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/CommunityChiefZiti"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function actionCreate(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $params = $request->all();
        $rule = [
            'ziti_name' => ['required', '自提点名称不能为空'],
            'address' => ['required', '自提点地址不能为空'],
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        $service = new CommunityChiefZitiService();
        $result = $service->createChiefZiti($authInfo['chief_id'], $params);
        return $this->response->array($result);
    }

    // 团长修改自提点
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/ziti/{ziti_id}",
     *     summary="团长修改自提点",
     *     tags={"社区团"},
     *     description="团长修改自提点",
     *     operationId="actionUpdate",
     *     @SWG\Parameter( name="ziti_name", in="query", description="自提点名称", required=true, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省", type="string" ),
     *     @SWG\Parameter( name="city", in="query", description="市", type="string" ),
     *     @SWG\Parameter( name="area", in="query", description="区", type="string" ),
     *     @SWG\Parameter( name="regions_id", in="query", description="地区ID,逗号分隔", type="string" ),
     *     @SWG\Parameter( name="regions_id", in="query", description="地区名称集合,逗号分隔", type="string" ),
     *     @SWG\Parameter( name="address", in="query", description="自提点地址", required=true, type="string"),
     *     @SWG\Parameter( name="ziti_pics", in="query", description="自提点图片", required=false, type="string"),
     *     @SWG\Parameter( name="ziti_contact_user", in="query", description="自提点联系人", required=false, type="string"),
     *     @SWG\Parameter( name="ziti_contact_mobile", in="query", description="自提点联系电话", required=false, type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="是否默认", required=false, type="string"),
     *     @SWG\Parameter( name="ziti_status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/CommunityChiefZiti"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function actionUpdate(Request $request, $ziti_id)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $params = $request->all();

        $service = new CommunityChiefZitiService();
        $info = $service->getInfo(['ziti_id' => $ziti_id]);
        if (empty($info)) {
            throw new ResourceException('无效的自提点');
        }
        if ($info['chief_id'] != $authInfo['chief_id']) {
            throw new ResourceException('只能修改自己的自提点');
        }
        $result = $service->updateChiefZiti($ziti_id, $params);
        return $this->response->array($result);
    }

}
