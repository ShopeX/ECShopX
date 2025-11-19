<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
// use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use BsPayBundle\Services\UserService;
// use BsPayBundle\Services\BankCodeService;

/**
 * 用户
 */
class User extends Controller
{
    /**
     * @SWG\Get(
     *     path="/bspay/user/audit_state",
     *     summary="查询用户对象状态",
     *     tags={"汇付斗拱"},
     *     description="查询用户对象状态",
     *     operationId="getAuditState",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="audit_state", type="string", description="审核状态，状态包括： A-待审核；B-审核失败；C-开户成功;D-待提交"),
     *                   @SWG\Property(property="audit_desc", type="string", description="审核结果描述"),
     *                   @SWG\Property(property="member_type", type="string", description="开户类型:person-个人;corp-企业"),
     *                   @SWG\Property(property="update_time", type="string", description="更新时间"),
     *                   @SWG\Property(property="valid", type="boolean", description="是否点过结算中心"),
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function getAuditState(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $userService = new UserService();

        $filter = [
            'company_id' => $companyId,
        ];
        $result = $userService->getAuditState($filter);
        return $this->response->array($result);
    }

    /**
     * 获取二级所有地区
     * path = "bspay/regions"
     */
    public function getRegions(Request $request)
    {
        $userService = new UserService();
        $regions = $userService->getRegionsList();
        return $this->response->array($regions);
    }

    /**
     * 获取三级所有地区
     * path = "bspay/regions/third"
     */    
    public function getRegionsThird(Request $request)
    {
        $userService = new UserService();
        $regions = $userService->getRegionsThirdList();
        return $this->response->array($regions);
    }
}
