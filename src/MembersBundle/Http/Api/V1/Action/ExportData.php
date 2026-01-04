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

namespace MembersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\MemberService;
use MembersBundle\Traits\MemberSearchFilter;
use EspierBundle\Traits\GetExportServiceTraits;
use EspierBundle\Jobs\ExportFileJob;
use MembersBundle\Jobs\BatchActionMembers;
use KaquanBundle\Jobs\batchReceiveMemberCard;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\DiscountCardService;

class ExportData extends Controller
{
    use MemberSearchFilter;
    use GetExportServiceTraits;

    /**
     * @SWG\Get(
     *     path="/member/export",
     *     summary="导出会员信息",
     *     tags={"会员"},
     *     description="导出会员信息",
     *     @SWG\Parameter( in="query", type="string", required=false, name="distributor_id", description="分销商ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
     * )
     */
    public function exportMemberData(Request $request)
    {
        // Powered by ShopEx EcShopX
        $type = 'member';
        $postdata = $request->all();
        $authdata = app('auth')->user()->get();
        $companyId = $authdata['company_id'];
        $postdata['distributor_id'] = $request->get('distributor_id', 0);
        $filter = $this->dataFilter($postdata, $authdata);

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block');
        // (new ExportFileJob($type, $companyId, $filter, $operator_id))->handle();
        $gotoJob = (new ExportFileJob($type, $companyId, $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/batchprocess",
     *     summary="批量操作会员信息",
     *     tags={"会员"},
     *     description="批量操作会员信息",
     *     operationId="batchProcessMemberData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="vip_grade", in="query", description="付费会员", required=true, type="string"),
     *     @SWG\Parameter( name="grade_id", in="query", description="会员等级", required=false, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="标签id", required=false, type="string"),
     *     @SWG\Parameter( name="remarks", in="query", description="备注", required=false, type="string"),
     *     @SWG\Parameter( name="username", in="query", description="会员名称", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="会员手机", required=false, type="string"),
     *     @SWG\Parameter( name="inviter_mobile", in="query", description="推荐人手机号", required=false, type="string"),
     *     @SWG\Parameter( name="salesman_mobile", in="query", description="导购员手机号", required=false, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *                  @SWG\Property( property="msg", type="string", example="已经处理成功", description="提示信息"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function batchProcessMemberData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        if (!$authdata) {
            throw new ResourceException(trans('MembersBundle/Members.operator_account_error'));
        }
        $companyId = $authdata['company_id'];

        //验证参数todo
        $getdata = $request->all('user_id');
        $rules = [
            'user_id.*' => ['required|integer', '会员ID必填,会员ID必须为整数'],
        ];
        $error = validator_params($getdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!array_filter($getdata)) {
            $postdata = $request->all();
            $filter = $this->dataFilter($postdata, $authdata);
        } else {
            $filter = $getdata;
            $filter['company_id'] = $companyId;
        }
        if (!$filter) {
            throw new ResourceException(trans('MembersBundle/Members.no_content_to_operate'));
        }
        if (app('auth')->user()->get('operator_type') == 'staff') {
            $sender = '员工-'.app('auth')->user()->get('username').'-'.app('auth')->user()->get('mobile');
        } else {
            $sender = app('auth')->user()->get('username');
        }
        $params['distributor_id'] = app('auth')->user()->get('distributor_id');
        $params['sender'] = $sender;

        $actionType = $request->get('action_type');
        switch ($actionType) {
            case 'rel_tag': //为会员打标签
                $params['tag_ids'] = $request->get('tag_ids');
                $redisKey = 'batchRelTagUserIds';
                break;
            case 'give_coupon':  //赠送优惠券给会员
                $params['couponsids'] = $request->get('couponsids');
                $params['source_from'] = '商城后台发放';
                $params['trigger_time'] = time();

                app('log')->debug(":user-number:couponsids: " . json_encode($params['couponsids']));
                $discountCardService = new DiscountCardService();
                $cardList = $discountCardService->getKaquanList($page = 0, $limit = 500,  ['company_id' => $companyId,'card_id|in' => $params['couponsids']]);
                app('log')->debug(":card: " . json_encode($cardList));
                foreach($cardList['list'] as $v){
                    app('log')->debug("[ExportData][batchGiveCouponUserIds]  : " . $v['get_num'] .'-'.$v['quantity'].'-'.$v['card_id']);
                    if($v['get_num'] >= $v['quantity']){
                        throw new ResourceException(trans('MembersBundle/Members.coupon_issued_out'));
                    }
                }
                app('log')->debug("[ExportData][batchGiveCouponUserIds]  : " . json_encode($cardList));
                $redisKey = 'batchGiveCouponUserIds';
                break;
            case 'send_sms':  //群发短信给会员
                $params['sms_content'] = $request->get('sms_content');
                $redisKey = 'batchSendSmsUserIds';
                break;
            case 'vip_delay':  //批量为付费会员延期
                $gradeForm = json_decode($request->get('vip_grade_form'), true);
                $params['vip_grade_id'] = $gradeForm['vip_grade_id'];
                $params['add_day'] = intval($gradeForm['add_day']);
                // 判断day是否为正整数
                if ($params['add_day'] <= 0) {
                    throw new ResourceException(trans('MembersBundle/Members.please_fill_correct_delay_days'));
                }
                $vipGradeService = new VipGradeService();
                $info = $vipGradeService->getInfo(['vip_grade_id' => $params['vip_grade_id'], 'company_id' => $companyId]);
                if (!$info) {
                    throw new ResourceException(trans('MembersBundle/Members.invalid_paid_member_level'));
                }
                if ($request->get('filter') == 'expired') {
                    return $this->expiredMemberExtension($companyId, $info, $params);
                }
                $redisKey = 'batchVipDelayUserIds';
                break;
            case 'set_grade':  //批量为会员设置等级
                $gradeForm = json_decode($request->get('grade_form'), true);
                $params['grade_id'] = $gradeForm['grade_id'];
                $params['remarks'] = $gradeForm['remarks'];
                $redisKey = 'batchSetGradeUserIds';
                break;
        }
        $memberService = new MemberService();
        $count = $memberService->getMemberCount($filter);
        if ($count > 100) {
            //$userIds = $this->getMemberLists($filter, $count);
            //$redisKey = $redisKey.":".time();
            //foreach ($userIds as $ids) {
            //    app('redis')->sadd($redisKey, $ids);
            //}
            //$filter['redisKey'] = $redisKey;
            //$queue = (new BatchActionMembers($params, $actionType, $filter, true))->onQueue('memberbatch');
            //app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
            $page = 1;
            $pageSize = 50;
            $userIds = $this->getMemberLists($filter, $count, $pageSize);
            $filter['page'] = 1;
            $filter['pageSize'] = $pageSize;
            foreach ($userIds as $ids) {
                $filter['user_id'] = $ids;
                $queue = (new BatchActionMembers($params, $actionType, $filter, true))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
            }
            return $this->response->array(['status' => true, 'msg' => '由于数据较多，已经加入队列处理']);
        } else {
            $queue = new BatchActionMembers($params, $actionType, $filter, false);
            $queue->handle();
            return $this->response->array(['status' => true, 'msg' => '已经处理成功']);
        }
    }

    private function getMemberLists($filter, $totalCount, $limit = 0)
    {
        if (!$limit) {
            $limit = 10000;
        }
        $fileNum = ceil($totalCount / $limit);

        $result = [];
        $memberService = new MemberService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $list = $memberService->getMemberList($filter, $j, $limit);
            $userIds = array_column($list, 'user_id');
            yield $userIds;
        }
    }

    private function expiredMemberExtension($companyId, $info, $params)
    {
        $vipGradeService = new VipGradeService();
        // 获取失效的会员数量
        $count = $vipGradeService->countExpiredVipGrade($companyId, $info['lv_type']);
        if ($count <= 50) {
            $users = $vipGradeService->getExpiredVipGradeUser($companyId, $info['lv_type']);
        } else {
            $jobParams = [
                'vip_grade_id' => $params['vip_grade_id'],
                'vip_type' => $info['lv_type'],
                'add_day' => $params['add_day'],
                'filter' => 'expired',
                'company_id' => $companyId,
            ];
            // 加入队列
            $gotoJob = (new batchReceiveMemberCard($jobParams))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return $this->response->array(['status' => true, 'msg' => '已经处理成功']);
    }
}
