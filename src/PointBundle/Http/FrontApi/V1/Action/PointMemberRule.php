<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
