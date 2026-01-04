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

namespace AdaPayBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\MemberService;
use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Services\AdapayDrawCashService;
use Illuminate\Http\Request;
use AdaPayBundle\Services\SubMerchantService;

class AdapayDrawCash extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adapay/drawcash/getList",
     *     summary="提现记录",
     *     tags={"Adapay"},
     *     description="提现记录",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="page", in="query", type="string", description="页码", required=false),
     *     @SWG\Parameter(name="page_size", in="query", type="string", description="分页大小", required=false),
     *     @SWG\Parameter(name="order_no", in="query", type="string", description="提现单号", required=false),
     *     @SWG\Parameter(name="begin_time", in="query", type="string", description="提现日期开始", required=false),
     *     @SWG\Parameter(name="end_time", in="query", type="string", description="提现日期结束", required=false),
     *     @SWG\Parameter(name="status", in="query", type="string", description="提现状态", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    required={"total_count", "cash_types","list"},
     *                    @SWG\Property(property="cash_types", type="string", description="提现类型列表: D0\D1\T1",example="D0"),
     *                    @SWG\Property(property="cash_balance", type="string", description="可提现金额"),
     *                    @SWG\Property(property="cash_limit", type="string", description="提现限额"),
     *                    @SWG\Property(property="search_options", type="object", description="搜索条件",
     *                        @SWG\Property(property="status", type="string", description="提现状态"),
     *                    ),
     *                    @SWG\Property(property="count", type="integer", description="总记录条数"),
     *                    @SWG\Property(property="list", type="array", description="明细数据",
     *                           @SWG\Items(
     *                              @SWG\Property(property="create_time", type="string", description="日期"),
     *                              @SWG\Property(property="order_no", type="string", description="提现单号"),
     *                              @SWG\Property(property="cash_type", type="string", description="提现类型"),
     *                              @SWG\Property(property="cash_amt", type="integer", description="提现金额（分）"),
     *                              @SWG\Property(property="user_name", type="string", description="提现账号"),
     *                              @SWG\Property(property="bank_card", type="string", description="提现卡号"),
     *                              @SWG\Property(property="status", type="string", description="取现状态: pending提现处理中 succeeded提现成功 failed提现失败"),
     *                              @SWG\Property(property="remark", type="string", description="备注"),
     *                           ),
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function getList(Request $request)
    {
        // Ref: 1996368445
        $auth = app('auth')->user()->get();
        $company_id = $auth['company_id'];
        $filter = [];
        $adapayDrawCashService = new AdapayDrawCashService();
        $drawLimit = (new SubMerchantService())->getDrawLimit($company_id);
        $cash_limit = $drawLimit['draw_limit'] ?? 0;

        //当前登录用户的类型
        $adaPayMemberId = 0;
        $memberService = new MemberService();
        $operator = $memberService->getOperator();
        if ($operator['operator_type'] != 'admin' && $operator['operator_type'] != 'staff') {
            $member = $memberService->getMemberInfo(['operator_type' => $operator['operator_type'], 'operator_id' => $operator['operator_id'], 'company_id' => $company_id]);
            if ($member && is_array($member)) {
                $adaPayMemberId = $member['id'] ?? 0;
            }
            //指定商户冻结金额
            $subMerchantService = new SubMerchantService();
            $drawLimitList = $subMerchantService->getDrawLimitList($company_id, true);
            if (isset($drawLimitList[$adaPayMemberId])) {
                $cash_limit = $drawLimitList[$adaPayMemberId];
            }
        }

        //当前商户的余额
        $cash_balance = $adapayDrawCashService->getBalance($company_id);
        $cash_balance = floatval($cash_balance);
        if ($cash_balance) {
            $cash_balance = bcmul($cash_balance, 100);//转换成: 分
        }
        $cash_limit = floatval($cash_limit);
        $cash_limit = min($cash_limit, $cash_balance);//冻结金额不能大于用户余额
        $valid_balance = $cash_balance - $cash_limit;//可提现金额: 分

        $cash_types = ["D0", "D1", "T1"];
        $params = json_decode($request->input('params', 0), true);
        $page = $params['page'] ?? 1;
        $page_size = $params['page_size'] ?? 20;
        $order_no = $params['order_no'] ?? '';
        $begin_time = $params['begin_time'] ?? '';
        $end_time = $params['end_time'] ?? '';
        $status = $params['status'] ?? '';
        $commission_fee = 0;
        //明细记录
        $filter['company_id'] = $company_id;

        if ($order_no) {
            $filter['order_no'] = $order_no;
        }
        if ($status) {
            $filter['status'] = $status;
        }
        if ($begin_time && $end_time) {
            $filter['create_time|gte'] = strtotime($begin_time);
            $filter['create_time|lte'] = strtotime($end_time);
        }
        $cols = "id,order_no,bank_card_id,bank_card_name,adapay_member_id,cash_amt,cash_type,status,create_time,update_time,remark,operator_id";
        $cash_result = $adapayDrawCashService->lists($filter, $page, $page_size, $cols);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        foreach ($cash_result['list'] as $key => $value) {
            if ($value['status'] == 'succeeded' || $value['status'] == 'pending') {
                $cash_result['list'][$key]['remark'] = '';
            }
            if ($datapassBlock) {
                $cash_result['list'][$key]['bank_card'] = data_masking('bankcard', (string) $value['bank_card']);
            }
        }

        $searchOptions = [
            'status' => [
                ['label' => '提现成功', 'value' => 'succeeded'],
                ['label' => '提现失败', 'value' => 'failed'],
                ['label' => '提现中', 'value' => 'pending'],
            ],
        ];

        //当前商户的自动提现是否开启，Y or N，默认为 N
        $subMerchantService = new SubMerchantService();
        $autoConfig = $subMerchantService->getAutoCashConfig($company_id);

        $result = [
            'cash_balance' => $valid_balance,
            'cash_limit' => $cash_limit,
            'commission_fee' => $commission_fee,//待结算佣金
            'cash_types' => $cash_types,
            'search_options' => $searchOptions,
            'auto_draw_cash' => $autoConfig['auto_draw_cash'] ?? 'N',
            'count' => $cash_result['total_count'],
            'list' => $cash_result['list']
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/withdraw",
     *     summary="店铺提现",
     *     tags={"Adapay"},
     *     description="店铺提现",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="cash_amt", in="query", type="string", description="提现金额", required=true),
     *     @SWG\Parameter(name="cash_type", in="query", type="string", description="提现类型:D0\D1\T1", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *        @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function withdraw(Request $request)
    {
        $auth = app('auth')->user()->get();
        $company_id = $auth['company_id'];

        $cash_amt = $request->input('cash_amt');
        $cash_type = $request->input('cash_type');
        $params = [
            'cash_amt' => $cash_amt,
            'cash_type' => $cash_type,
        ];
        $rules = [
            'cash_amt' => ['required'],
            'cash_type' => ['in:D0,D1,T1']
        ];

        $validator = app('validator')->make($params, $rules, [
            'cash_amount.required' => '请输入提现金额',
            'cash_type.required' => '请选择提现类型',
        ]);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        if (bcmul($cash_amt, 100) < 1) {
            throw new ResourceException('提现金额不低于0.01元的数字');
        }
        $params['company_id'] = $company_id;
        $adapayDrawCashService = new AdapayDrawCashService();
        $result = $adapayDrawCashService->withdraw($params);
        return $this->response->array(['status' => true]);
    }
}
