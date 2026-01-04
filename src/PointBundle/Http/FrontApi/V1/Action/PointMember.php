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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PointBundle\Services\PointMemberLogService;
use PointBundle\Services\PointMemberService;
use MembersBundle\Services\MemberService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use ThirdPartyBundle\Services\DmCrm\PointService;

class PointMember extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/point/member",
     *     summary="积分列表",
     *     tags={"积分"},
     *     description="会员积分记录列表",
     *     operationId="lists",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="page_no", in="query", description="页码 default:1", type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="条数 default:10", type="integer"),
     *     @SWG\Parameter( name="outin_type", in="query", description="类型 outcome:支出 income:收入", type="string"),
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
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        $page = $request->input('page_no', 1);
        $pageSize = $request->input('page_size', 10);
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $outinType = $request->input('outin_type');
        if ($outinType) {
            if ('outcome' == $outinType) {
                $params['outcome|gt'] = 0;
            } else {
                $params['income|gt'] = 0;
            }
        }

        // 达摩crm, 会员积分明细
        $ns = new DmCrmSettingService();
        if ($ns->getDmCrmSetting($authInfo['company_id'])['is_open'] ?? '') {
            $memberService = new MemberService();
            $pointService = new PointService($authInfo['company_id']);
            $memberFilter = [
                'user_id' => $authInfo['user_id'],
                'company_id' => $authInfo['company_id']
            ];
            $memberInfoInfo = $memberService->getMemberInfo($memberFilter);
            $paramsData = [
                'mobile' => $memberInfoInfo['mobile'],
                'currentPage' => $page,
                'pageSize' => $pageSize,
                'user_id' => $authInfo['user_id'],
                'company_id' => $authInfo['company_id']
            ];
            $pointList = $pointService->getPointDetailList($paramsData);
            
            $result['list'] = $pointList['items'] ?? [];
            $result['total_count'] = $pointList['totalCount'] ?? 0;
            if (!empty($result['list'])) {
                // 兼容积分支出/收入过滤  
                if ($outinType) {
                     if ($outinType == 'outcome') {
                        $tempList = array_filter($result['list'], function ($item) {
                            return $item['outcome'] > 0;
                        });
                        $result['list'] = array_values($tempList);
                    } elseif ($outinType == 'income') {
                        $tempList = array_filter($result['list'], function ($item) {
                            return $item['income'] > 0;
                        });
                        $result['list'] = array_values($tempList);
                    }
                }
                // 处理预占积分时间问题
                foreach($result['list'] as $k => $v) {
                    // if (preg_match('/积分解冻时间+(:|：)(\d{4}-\d{2}-\d{2})/', $v['operater_remark'], $matches)) {
                    //     $dateStr = $matches[2]." 00:00:00";
                    //     $result['list'][$k]['created'] = $dateStr;
                    //     if (time() < strtotime($dateStr)) {
                    //        // 未来时间，积分未解冻
                    //        unset($result['list'][$k]);
                    //     }
                    // }
                    if (!empty($v['effectTime'])) {
                        $result['list'][$k]['created'] = $v['effectTime'];
                        if (time() < $v['effectTime']) {
                           // 未来时间，积分未解冻
                           unset($result['list'][$k]);
                        }
                    }
                }
                $result['list'] = array_values($result['list']);
            } 
        }else{
            $pointMemberService = new PointMemberLogService();
            $result = $pointMemberService->lists($params, $page, $pageSize, $orderBy = ["created" => "DESC"]);

            foreach ($result['list'] as $key => $row) {
                $result['list'][$key]['journal_type_desc'] = PointMemberService::JOURNAL_TYPE_MAP[$row['journal_type']] ?? '';
            }

        }
    
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/point/member/info",
     *     summary="获取积分信息",
     *     tags={"积分"},
     *     description="获取积分统计信息",
     *     operationId="info",
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
     *                 @SWG\Property(property="user_id", type="string", description="会员ID"),
     *                 @SWG\Property(property="company_id", type="string", description="企业ID"),
     *                 @SWG\Property(property="point", type="string", description="现有积分数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PointErrorRespones") ) )
     * )
     */
    public function info(Request $request)
    {
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $pointMemberService = new PointMemberService();
        $result = $pointMemberService->getInfo($params);
        return $this->response->array($result);
    }
}
