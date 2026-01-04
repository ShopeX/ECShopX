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

namespace PointBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use PointBundle\Services\PointMemberRuleService;

class PointMemberRule extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/point/rule",
     *     summary="获取积分规则信息",
     *     tags={"积分"},
     *     description="获取积分规则信息",
     *     operationId="ruleInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="name", type="string", description="积分名"),
     *                 @SWG\Property(property="rule_desc", type="string", description="积分规则说明"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorRespones") ) )
     * )
     */
    public function info(Request $request)
    {
        $authInfo = $request->get('auth');
        $pointMemberRuleService = new PointMemberRuleService();
        $pointRuleInfo = $pointMemberRuleService->getPointRule($authInfo['company_id']);
        $result = [
            'name' => $pointRuleInfo['name'],
            'rule_desc' => $pointRuleInfo['rule_desc'],
        ];
        return $this->response->array($result);
    }
}
