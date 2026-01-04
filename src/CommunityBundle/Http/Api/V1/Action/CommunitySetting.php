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

namespace CommunityBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use CommunityBundle\Services\CommunitySettingService;

class CommunitySetting extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/community/activity/setting",
     *     summary="获取社区团购设置",
     *     tags={"社区团管理端"},
     *     description="获取社区团购设置",
     *     operationId="info",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="condition_type", type="string", description="成团条件"),
     *                     @SWG\Property(property="condition_money", type="string", description="最低成团金额"),
     *                     @SWG\Property(property="aggrement", type="string", description="注册协议"),
     *                     @SWG\Property(property="explanation", type="string", description="申请说明"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构",@SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse")) )
     * )
     */
    public function get(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }
        $settingService = new CommunitySettingService($companyId, $distributorId);
        $result = $settingService->getSetting();

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/community/activity/setting",
     *     summary="保存社区团购设置",
     *     tags={"社区团管理端"},
     *     description="获取社区团购设置",
     *     operationId="info",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="condition_type", in="query", type="string", description="成团条件 num:按商品数量 money:按总金额"),
     *     @SWG\Parameter( name="condition_money", in="query", type="string", required=true, description="最低成团金额"),
     *     @SWG\Parameter( name="aggrement", in="query", type="string", required=false, description="注册协议"),
     *     @SWG\Parameter( name="explanation", in="query", type="string", required=false, description="申请说明"),
     *     @SWG\Parameter( name="rebate_ratio", in="query", type="string", required=false, description="佣金比例"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="condition_type", type="string", description="成团条件"),
     *                     @SWG\Property(property="condition_money", type="string", description="最低成团金额"),
     *                     @SWG\Property(property="aggrement", type="string", description="注册协议"),
     *                     @SWG\Property(property="explanation", type="string", description="申请说明"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构",@SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse")) )
     * )
     */
    public function save(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }
        $data = $request->input();
        $rules = [
            'condition_type' => ['required|in:num,money', '成团条件必选'],
            'condition_money' => ['required_if:condition_type,money | numeric | min:0', '最低成团金额必填'],
            'rebate_ratio' => ['numeric | min:0 | max:100', '佣金比例在0～100之间']
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $settingService = new CommunitySettingService($companyId, $distributorId);
        $result = $settingService->saveSetting($data);

        return $this->response->array($result);

    }

}
