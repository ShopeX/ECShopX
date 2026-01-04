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

namespace PointBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PointBundle\Services\PointMemberRuleService;
use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Services\SettingService;
use CompanysBundle\Ego\CompanysActivationEgo;

class PointMemberRule extends Controller
{
    /**
     * @SWG\Put(
     *     path="/member/point/rule",
     *     summary="积分规则设置",
     *     tags={"积分"},
     *     description="积分规则设置",
     *     operationId="save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="isOpenMemberPoint", in="query", description="是否开启积分 true:开启 false:未开启", required=true, type="string"),
     *     @SWG\Parameter( name="gain_point", in="query", type="string", description="积分获取比例 订单金额1元人民币 获得x积分", required=true),
     *     @SWG\Parameter( name="gain_limit", in="query", type="string", description="积分获取限制 默认:9999999"),
     *     @SWG\Parameter( name="gain_time", in="query", description="获取积分时间点", required=true, type="string"),
     *     @SWG\Parameter( name="isOpenDeductPoint", in="query", description="是否开启积分抵扣  true:开启 false:未开启", required=true, type="string"),
     *     @SWG\Parameter( name="include_freight", in="query", description="是否包含运费  true:包含 false:不包含", required=true, type="string"),
     *     @SWG\Parameter( name="deduct_proportion_limit", in="query", description="每单积分抵扣金额上限 % 1 <= x <= 100", required=true, type="string"),
     *     @SWG\Parameter( name="deduct_point", in="query", description="积分抵扣比例 x积分抵扣1元人民币", required=true, type="string"),
     *     @SWG\Parameter( name="access", type="string", in="query", description="积分获取方式 items:可按单商品设置的积分值获取 order:可按订单金额比例获取"),
     *     @SWG\Parameter( name="rule_desc", type="string", in="query", description="积分规则说明"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="access", type="string", description="积分获取方式 items:可按单商品设置的积分值获取 order:可按订单金额比例获取"),
     *                     @SWG\Property(property="deduct_point", type="string", description="积分抵扣比例 x积分抵扣1元人民币"),
     *                     @SWG\Property(property="deduct_proportion_limit", type="string", description="每单积分抵扣金额上限 % 1 <= x <= 100"),
     *                     @SWG\Property(property="gain_limit", type="string", description="积分获取限制 默认:9999999"),
     *                     @SWG\Property(property="gain_point", type="string", description="积分获取比例 订单金额1元人民币 获得x积分"),
     *                     @SWG\Property(property="gain_time", type="string", description="积分获取时间 订单完成x天，获取积分"),
     *                     @SWG\Property(property="include_freight", type="string", description="获取积分是否包含运费 true:包含 false:不包含"),
     *                     @SWG\Property(property="isOpenDeductPoint", type="string", description="是否开启积分抵扣 true:开启 false:未开启"),
     *                     @SWG\Property(property="isOpenMemberPoint", type="string", description="是否开启积分 true:开启 false:未开启"),
     *                     @SWG\Property(property="rule_desc", type="string", description="积分规则说明"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构",@SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorResponse")) )
     * )
     */
    public function save(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->input();
        $rules = [
            'name' => ['max:8', "积分名最长为8个字符"],
            'isOpenMemberPoint' => ['in:true,false',"是否开启积分错误"],
            'isOpenDeductPoint' => ['in:true,false',"是否开启积分抵扣错误"],
            'gain_point' => ["required_if:isOpenMemberPoint,true | numeric | min:0", '获取积分比例必填'],
            'gain_limit' => ["required_if:isOpenMemberPoint,true | numeric | min:1", '获取积分限制必填'],
            'gain_time' => ["required_if:isOpenMemberPoint,true  | numeric | min:0", '获取积分时间点必填'],
            'deduct_proportion_limit' => ["required_if:isOpenDeductPoint,true | numeric | min:1", '每单积分抵扣金额上限最小为1'],
            'deduct_point' => ["required_if:isOpenDeductPoint,true | numeric | min:0", '抵扣积分比例必填'],
            'include_freight' => ['required_if:access,order','是否包含运费错误'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        if ($data['isOpenDeductPoint'] && (!preg_match("/^[1-9][0-9]*$/", $data['deduct_proportion_limit']) || $data['deduct_proportion_limit'] > 100)) {
            throw new ResourceException('每单积分抵扣金额上限为1-100的整数');
        }
        $pointMemberRuleService = new PointMemberRuleService();
        $result = $pointMemberRuleService->savePointRule($companyId, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/point/rule",
     *     summary="获取积分规则",
     *     tags={"积分"},
     *     description="获取积分规则详情",
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
     *                     @SWG\Property(property="access", type="string", description="积分获取方式 items:可按单商品设置的积分值获取 order:可按订单金额比例获取"),
     *                     @SWG\Property(property="deduct_point", type="string", description="积分抵扣比例 x积分抵扣1元人民币"),
     *                     @SWG\Property(property="deduct_proportion_limit", type="string", description="每单积分抵扣金额上限 % 1 <= x <= 100"),
     *                     @SWG\Property(property="gain_limit", type="string", description="积分获取限制 默认:9999999"),
     *                     @SWG\Property(property="gain_point", type="string", description="积分获取比例 订单金额1元人民币 获得x积分"),
     *                     @SWG\Property(property="gain_time", type="string", description="积分获取时间 订单完成x天，获取积分"),
     *                     @SWG\Property(property="include_freight", type="string", description="获取积分是否包含运费 true:包含 false:不包含"),
     *                     @SWG\Property(property="isOpenDeductPoint", type="string", description="是否开启积分抵扣 true:开启 false:未开启"),
     *                     @SWG\Property(property="isOpenMemberPoint", type="string", description="是否开启积分 true:开启 false:未开启"),
     *                     @SWG\Property(property="popularize_commission_type", type="string", description="返佣激励方式 money返还佣金；point返还积分"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构",@SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorResponse")) )
     * )
     */
    public function info()
    {
        $companyId = app('auth')->user()->get('company_id');
        $pointMemberRuleService = new PointMemberRuleService();
        $result = $pointMemberRuleService->getPointRule($companyId);
        $result['access'] = $result['access'] ?? 'order';
        $result['include_freight'] = $result['include_freight'] ?? 'true';

        $popularizeSet = (new SettingService())->getConfig($companyId);
        $result['popularize_commission_type'] = isset($popularizeSet['commission_type']) && in_array($popularizeSet['commission_type'], ['money', 'point']) ? $popularizeSet['commission_type'] : 'money';

        return $this->response->array($result);
    }
}
