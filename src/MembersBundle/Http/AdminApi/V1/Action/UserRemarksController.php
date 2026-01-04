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

namespace MembersBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use MembersBundle\Services\UserRemarksService;

/**
 *
 */
class UserRemarksController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/member/remarks",
     *     summary="导购员给会员加备注",
     *     tags={"会员"},
     *     description="导购员给会员加备注",
     *     operationId="addRemarks",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="remarks", in="formData", description="备注名", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function addRemarks(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $user_id = $request->input('user_id', '');
        $remarks = $request->input('remarks', '');

        if (!$user_id) {
            throw new ResourceException('请选择用户');
        }

        if (mb_strlen($remarks) > 255) {
            throw new ResourceException('备注内容不能超过255个字符');
        }

        $data = [
            'user_id' => $user_id,
            'salesperson_id' => $salesperson_info['salesperson_id'],
            'company_id' => $salesperson_info['company_id']
        ];

        $member_remarks_services = new UserRemarksService();

        if ($remarks) {
            $data['remarks'] = $remarks;
            $result = $member_remarks_services -> addRemarks($data);
        } else {
            $result = $member_remarks_services -> deleteRemarks($data);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/remarks",
     *     summary="获取会员备注",
     *     tags={"会员"},
     *     description="获取会员备注",
     *     operationId="addRemarks",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="remarks_id", type="string", example="11", description="主键id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="salesperson_id", type="string", example="28", description="导购员id"),
     *                  @SWG\Property( property="user_id", type="string", example="20261", description="用户id"),
     *                  @SWG\Property( property="remarks", type="string", example="新客户", description="备注"),
     *                  @SWG\Property( property="created", type="string", example="1612349103", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612349103", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getRemarks(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $user_id = $request->input('user_id', '');

        if (!$user_id) {
            throw new ResourceException('请选择用户');
        }

        $filter = [
            'user_id' => $user_id,
            'salesperson_id' => $salesperson_info['salesperson_id'],
            'company_id' => $salesperson_info['company_id']
        ];

        $member_remarks_services = new UserRemarksService();
        $result = $member_remarks_services -> getRemarks($filter);
        return $this->response->array($result);
    }
}
