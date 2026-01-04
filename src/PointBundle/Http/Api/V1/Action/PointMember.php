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

use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\Query\Expr\Func;
use PointBundle\Services\PointMemberLogService;
use PointBundle\Services\PointMemberService;
use MembersBundle\Services\MemberService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use ThirdPartyBundle\Services\DmCrm\PointService;

class PointMember extends Controller
{
    private function getFilter($request)
    {
        $params['company_id'] = app('auth')->user()->get('company_id');
        $memberService = new MemberService();
        if ($request->input('user_id', 0) > 0) {
            $params['user_id'] = $request->input('user_id');
        }
        if ($request->input('date_begin')) {
            $params['created|gte'] = $request->input('date_begin');
            $params['created|lte'] = $request->input('date_end');
        }
        if ($mobile = $request->input('mobile', '')) {
            $member_list = $memberService->getMemberList(['company_id' => $params['company_id'],'mobile' => $mobile]);
            if (!empty($member_list)) {
                $filterUsers[] = array_column($member_list, 'user_id');
            }else {
                $filterUsers[] = [];
            }
        }
        if ($username = $request->input('username', '')) {
            $member_list = $memberService->getMemberList(['company_id' => $params['company_id'],'username' => $username], 1, -1);
            if (!empty($member_list)) {
                $filterUsers[] = array_column($member_list, 'user_id');
            }else {
                $filterUsers[] = [];
            }
        }
        if ($name = $request->input('name', '')) {
            $member_list = $memberService->getMemberList(['company_id' => $params['company_id'],'name|contains' => $name], 1, -1);
            if (!empty($member_list)) {
                $filterUsers[] = array_column($member_list, 'user_id');
            } else {
                $filterUsers[] = [];
            }
        }
        if (isset($filterUsers) && !empty($filterUsers)) {
            $compareArr = $filterUsers[0];
            foreach ($filterUsers as $key => $value) {
                if($key == 0) {
                    continue;
                }
                $compareArr = array_intersect($value, $compareArr);
            }
            $params['user_id'] = !empty($compareArr) ? $compareArr : [0];
        }

        return $params;
    }

    /**
     * @SWG\Get(
     *     path="/point/member",
     *     summary="积分记录",
     *     tags={"积分"},
     *     description="获取用户积分记录列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数 默认:1", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数 默认:20", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="date_begin", in="query", description="创建时间的开始时间，时间戳", type="string"),
     *     @SWG\Parameter( name="date_end", in="query", description="创建时间的结束时间，时间戳", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="总数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                         @SWG\Property(property="company_id", type="string", description="企业Id"),
     *                         @SWG\Property(property="id", type="string", description="自增Id"),
     *                         @SWG\Property(property="user_id", type="string", description="会员ID"),
     *                         @SWG\Property(property="income", type="integer", description="收入"),
     *                         @SWG\Property(property="outcome", type="integer", description="支出"),
     *                         @SWG\Property(property="point", type="integer", description="增加或减少的积分数"),
     *                         @SWG\Property(property="journal_type", type="integer", description="积分交易类型，1:注册送积分 2.推荐送分 3.充值返积分 4.推广注册返积分 5.积分换购 6.储值兑换积分 7.订单返积分 8.会员等级返佣 9.取消订处理积分 10.售后处理积分 11.大转盘抽奖送积分 12:管理员手动调整积分"),
     *                         @SWG\Property(property="outin_type", type="string", description="类型 out:支出 in:收入"),
     *                         @SWG\Property(property="point_desc", type="string", description="积分描述"),
     *                         @SWG\Property(property="order_id", type="string", description="订单编号"),
     *                         @SWG\Property(property="created", type="string", description="创建时间 时间戳"),
     *                         @SWG\Property(property="updated", type="string", description="更新时间 时间戳"),
     *                         ),
     *                     ),
     *             ),
     *          ),
     *      ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorResponse")) )
     * )
     */
    public function lists(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $pointMemberService = new PointMemberLogService();
        $params = $this->getFilter($request);

        // 达摩crm, 会员积分明细
        $ns = new DmCrmSettingService();
        if ($ns->getDmCrmSetting($params['company_id'])['is_open'] ?? '') {
            $memberService = new MemberService();
            $pointService = new PointService($params['company_id']);
            // 兼容积分流水，多user_id, 但是分页可能不准确了
            $pointItems = [];
            $pointTotal = 0;
            if (isset($params['user_id']) && is_array($params['user_id'])) {
                // pass
            }elseif(isset($params['user_id']) && !empty($params['user_id'])){
                $params['user_id'] = [$params['user_id']];    
            } else {
                $result = $pointMemberService->lists($params, $page, $pageSize, $orderBy = ["created" => "DESC"]);
                if (isset($result['list']) && !empty($result['list'])) {
                    $userIds = array_column($result['list'], 'user_id');
                    $userIds = array_flip(array_flip($userIds ?? [])); // 去重
                    $params['user_id'] = $userIds;
                }
            }
            if (!empty($params['user_id'])) {
                foreach($params['user_id'] as $k => $userId) {
                    $memberFilter = [
                        'user_id' => $userId,
                        'company_id' => $params['company_id']
                    ];
                    $memberInfoInfo = $memberService->getMemberInfo($memberFilter);
                    if (empty($memberInfoInfo)) {
                        continue;
                    }
                    $paramsData = [
                        'mobile' => $memberInfoInfo['mobile'],
                        'currentPage' => $page,
                        'pageSize' => count($params['user_id']) >= 1 ? ceil($pageSize/count($params['user_id'])) : $pageSize ,
                        'user_id' => $userId,
                        'company_id' => $params['company_id']
                    ];
                    $pointList = $pointService->getPointDetailList($paramsData);
                    $pointItems = array_merge($pointItems, $pointList['items'] ?? []);
                    $pointTotal += $pointList['totalCount'] ?? 0;
                }
            }

            $result['list'] = $pointItems;
            $result['total_count'] = $pointTotal;

            if (isset($result['list']) && !empty($result['list'])) {
                $memberService = new MemberService();
                $userIds = array_column($result['list'], 'user_id');
                if (empty($userIds)) {
                    $userIds = $params['user_id'];
                }
                $memberList = $memberService->getMemberList(['company_id' => $params['company_id'],'user_id' => $userIds], 1, -1);
                $memberList = array_column($memberList, null, 'user_id');
            
                foreach ($result['list'] as $key => $row) {
                    $result['list'][$key]['username'] = $memberList[$row['user_id']]['username'] ?? '';
                    $result['list'][$key]['name'] =  $memberList[$row['user_id']]['name'] ?? '';
                    $result['list'][$key]['mobile'] =  $memberList[$row['user_id']]['mobile'] ?? '';
                    $result['list'][$key]['s_point'] =  $row['s_point'] ?? 0;
                }
            }
        }else {
            $result = $pointMemberService->lists($params, $page, $pageSize, $orderBy = ["created" => "DESC"]);
            if (isset($result['list']) && !empty($result['list'])) {
                $memberService = new MemberService();
                $userIds = array_column($result['list'], 'user_id');
                $memberList = $memberService->getMemberList(['company_id' => $params['company_id'],'user_id' => $userIds], 1, -1);
                $memberList = array_column($memberList, null, 'user_id');
            }

            foreach ($result['list'] as $key => $row) {
                $point = explode('：', $row['point_desc']);
                $result['list'][$key]['journal_type_desc'] = PointMemberService::JOURNAL_TYPE_MAP[$row['journal_type']] ?? '';
                $result['list'][$key]['username'] = $memberList[$row['user_id']]['username'] ?? '';
                $result['list'][$key]['name'] =  $memberList[$row['user_id']]['name'] ?? '';
                $result['list'][$key]['mobile'] =  $memberList[$row['user_id']]['mobile'] ?? '';
                $result['list'][$key]['s_point'] =  end($point) ?? 0;
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/pointcount/index",
     *     summary="获取积分总览页数据",
     *     tags={"积分"},
     *     description="获取积分总览页数据",
     *     operationId="getPointCountIndex",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *         @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Items(
     *                 @SWG\Property(property="can_use", type="string", description="可用积分总额"),
     *                 @SWG\Property(property="total", type="string", description="累计积分总额"),
     *                 @SWG\Property(property="used", type="string", description="已使用积分总额"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorResponse")) )
     * )
     */
    public function getPointCountIndex(Request $request)
    {
        // CONST: 1E236443
        $pointMemberService = new PointMemberLogService();
        $companyId = app('auth')->user()->get('company_id');

        $memberPointTotal = $pointMemberService->getMemberPointTotal($companyId);

        return $memberPointTotal;
    }

    /**
     * @SWG\Post(
     *     path="/point/adjustment",
     *     summary="会员积分调整",
     *     tags={"积分"},
     *     description="会员积分调整",
     *     operationId="adjustment",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员ID", required=true, type="string"),
     *     @SWG\Parameter( name="adjustment_type", in="query", description="调整类型 plus:加 reduce:减", required=true, type="string"),
     *     @SWG\Parameter( name="point", in="query", description="调整积分", required=true, type="integer"),
    *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *         @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Items(
     *                 @SWG\Property(property="status", type="boolean", description="状态"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorResponse")) )
     * )
     */
    public function adjustment(Request $request)
    {
        // CONST: 1E236443
        $params = $request->all('user_id', 'adjustment_type', 'point');
        $rules = [
            'user_id' => ['required', '会员ID必填'],
            'adjustment_type' => ['required|in:plus,reduce', '调整类型必填'],
            'point' => ['required|min:0', '积分必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = app('auth')->user();
        $operator_type = $authInfo->get('operator_type');
        if ('admin' != $operator_type) {
            throw new ResourceException('请使用超级管理员调整积分');
        }
        if (intval($params['point']) > 9999999) {
            throw new ResourceException('可调整积分最大为9999999');
        }
        $companyId = $authInfo->get('company_id');
        $operation_name = $authInfo->get('username');

        // 查询会员信息
        $memberService = new MemberService();
        $mobile = $memberService->getMobileByUserId($params['user_id'], $companyId);
        if (!$mobile) {
            throw new ResourceException('未查询到相关会员信息');
        }
        $pointMemberService = new PointMemberService();
        $point = intval($params['point']);
        if ($point <= 0) {
            throw new ResourceException('积分必填');
        }
        if ($params['adjustment_type'] == 'plus') {
            $status = true;
        } else {
            $status = false;
        }
        $record = '管理员：'.$operation_name.'，手动调整积分';
        $result = $pointMemberService->addPoint($params['user_id'], $companyId, $point, 12, $status, $record);
        return $this->response->array(['status' => $result]);
    }

    public function exportData(Request $request) {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $pointMemberService = new PointMemberLogService();
        $params = $this->getFilter($request);

        $resultList = $pointMemberService->lists($params, $page, $pageSize, $orderBy = ["created" => "DESC"]);
        if ($resultList['total_count'] <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }
        $operator_id = app('auth')->user()->get('operator_id');
        $operator_type = app('auth')->user()->get('operator_type');
        if (in_array($operator_type, ['admin', 'staff'])) {
            $params['datapass_block'] = 1;
        }else {
            $params['datapass_block'] = 0;
        }

        $shouldQueue = (bool)$request->input("should_queue", 1);
        if ($shouldQueue) {
            $gotoJob = (new ExportFileJob('member_point_logs', $params['company_id'], $params, $operator_id))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }else {
            (new ExportFileJob('member_point_logs', $params['company_id'], $params, $operator_id))->handle();
        }

        $result['status'] = true;
        return response()->json($result);
    }

}
