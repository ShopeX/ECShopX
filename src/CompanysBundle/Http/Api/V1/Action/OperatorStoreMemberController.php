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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use MembersBundle\Services\OperatorStoreMemberReadinessService;

class OperatorStoreMemberController extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/operator/member/ready",
     *     summary="门店会员就绪（开单前）",
     *     tags={"企业"},
     *     description="在获取管理员购物车等开单前调用；若公司已启用开放平台且会员尚未在数云登记，则完成当前门店 member.register。未启用、或会员已在小程序/店务任一路径完成 OPEN 登记（shuyun_open_online_wxapp_sync_at 或 offline_reg_distributor）则跳过，不再重复 register。",
     *     operationId="operatorStoreMemberReady",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="会员 user_id", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="门店 distributor_id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="ok", type="boolean", example=true),
     *                  @SWG\Property( property="synced", type="boolean", description="本次是否执行了登记"),
     *                  @SWG\Property( property="skipped", type="boolean", description="是否跳过（未开能力/已登记等）"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function postReady(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $companyId = (int) $authInfo['company_id'];
        $userId = (int) $request->input('user_id', 0);
        $distributorId = (int) $request->input('distributor_id', 0);

        $result = app(OperatorStoreMemberReadinessService::class)->ensureOfflineMemberAtStore(
            $companyId,
            $distributorId,
            $userId
        );

        return $this->response->array(array_merge(['ok' => true], $result));
    }
}
