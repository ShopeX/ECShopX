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

namespace DepositBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use DepositBundle\Services\RechargeRule;
use DepositBundle\Services\RechargeAgreement;
use DepositBundle\Services\DepositTrade;
use DepositBundle\Services\Stats\Day as StatsDay;

class Recharge extends Controller
{
    /**
     * @SWG\Post(
     *     path="/deposit/rechargerule",
     *     summary="创建充值面额规则",
     *     tags={"储值"},
     *     description="创建充值面额规则",
     *     operationId="createRechargeRule",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="fixed_money", in="query", description="充值固定面额", required=true, type="string"),
     *     @SWG\Parameter( name="rule_type", in="query", description="充值赠送类型", required=true, type="string"),
     *     @SWG\Parameter( name="rule_data", in="query", description="充值赠送说明", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function createRechargeRule(Request $request)
    {
        // ShopEx EcShopX Service Component
        $rechargeRule = new RechargeRule();

        $companyId = app('auth')->user()->get('company_id');

        $money = $request->input('fixed_money');
        $data = $request->all('rule_type', 'rule_data');

        $rechargeRule->createRechargeRule($companyId, $money, $data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/deposit/rechargerules",
     *     summary="获取充值面额规则",
     *     tags={"储值"},
     *     description="获取充值面额规则",
     *     operationId="getRechargeRuleList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="4", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="7", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="money", type="string", example="1", description="金额"),
     *                          @SWG\Property( property="ruleType", type="string", example="point", description=""),
     *                          @SWG\Property( property="ruleData", type="string", example="123", description=""),
     *                          @SWG\Property( property="createTime", type="string", example="1593324458", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getRechargeRuleList(Request $request)
    {
        $rechargeRule = new RechargeRule();
        $companyId = app('auth')->user()->get('company_id');

        $filter['company_id'] = $companyId;

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $list = $rechargeRule->getRechargeRuleList($filter, $pageSize, $page);
        return $this->response->array($list);
    }

    /**
     * @SWG\Delete(
     *     path="/deposit/rechargerule/{id}",
     *     summary="根据ID删除充值面额规则",
     *     tags={"储值"},
     *     description="根据ID删除充值面额规则",
     *     operationId="deleteRechargeRuleById",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function deleteRechargeRuleById($id)
    {
        $rechargeRule = new RechargeRule();
        $companyId = app('auth')->user()->get('company_id');

        $rechargeRule->deleteRechargeRuleById($id, $companyId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/deposit/rechargerule",
     *     summary="编辑指定充值面额规则",
     *     tags={"储值"},
     *     description="编辑指定充值面额规则",
     *     operationId="editRechargeRuleById",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="fixed_money", in="query", description="充值固定面额", required=true, type="string"),
     *     @SWG\Parameter( name="rule_type", in="query", description="充值赠送类型", required=true, type="string"),
     *     @SWG\Parameter( name="rule_data", in="query", description="充值赠送说明", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function editRechargeRuleById(Request $request)
    {
        $rechargeRule = new RechargeRule();
        $companyId = app('auth')->user()->get('company_id');

        $id = $request->input('id');
        $money = $request->input('fixed_money');
        $data = $request->all('rule_type', 'rule_data');

        $rechargeRule->editRechargeRuleById($id, $companyId, $money, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/deposit/recharge/agreement",
     *     summary="设置储值协议",
     *     tags={"储值"},
     *     description="设置储值协议",
     *     operationId="setRechargeAgreement",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="content", in="query", description="充值协议内容", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function setRechargeAgreement(Request $request)
    {
        $rechargeRule = new RechargeAgreement();
        $companyId = app('auth')->user()->get('company_id');

        $content = $request->input('content');

        $rechargeRule->setRechargeAgreement($companyId, $content);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/deposit/recharge/agreement",
     *     summary="获取储值协议",
     *     tags={"储值"},
     *     description="获取储值协议",
     *     operationId="getRechargeAgreementByCompanyId",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="content", type="string", example="充值协议", description="内容"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getRechargeAgreementByCompanyId(Request $request)
    {
        $rechargeRule = new RechargeAgreement();
        $companyId = app('auth')->user()->get('company_id');

        $data = $rechargeRule->getRechargeAgreementByCompanyId($companyId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/deposit/recharge/multiple",
     *     summary="获取充值送积分信息",
     *     tags={"储值"},
     *     description="获取充值送积分信息",
     *     operationId="getRechargeMultipleByCompanyId",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="start_time", type="string", example="0", description="开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="0", description="结束时间"),
     *                  @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                  @SWG\Property( property="multiple", type="string", example="1", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getRechargeMultipleByCompanyId(Request $request)
    {
        $rechargeRule = new RechargeRule();
        $companyId = app('auth')->user()->get('company_id');

        $data = $rechargeRule->getRechargeMultipleByCompanyId($companyId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/deposit/recharge/multiple",
     *     summary="设置充值送积分",
     *     tags={"储值"},
     *     description="设置充值送积分",
     *     operationId="setRechargeMultiple",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="", required=true, type="string"),
     *     @SWG\Parameter( name="multiple", in="query", description="", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function setRechargeMultiple(Request $request)
    {
        $rechargeRule = new RechargeRule();
        $companyId = app('auth')->user()->get('company_id');

        $data['start_time'] = strtotime($request->input('start_time', 1));
        $data['end_time'] = strtotime($request->input('end_time', 1));
        $data['is_open'] = 'false' == $request->input('is_open', 'false') ? false : true;
        $data['multiple'] = (int)$request->input('multiple', 1);

        $rechargeRule->setRechargeMultiple($companyId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/deposit/trades",
     *     summary="获取储值交易记录",
     *     tags={"储值"},
     *     description="获取储值交易记录",
     *     operationId="getDepositTradeList",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="68", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="depositTradeId", type="string", example="CZ3138424000010067", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="memberCardCode", type="string", example="08082ADA4850", description=""),
     *                          @SWG\Property( property="shopId", type="string", example="0", description=""),
     *                          @SWG\Property( property="shopName", type="string", example="中核浦原科技园", description=""),
     *                          @SWG\Property( property="userId", type="string", example="20067", description=""),
     *                          @SWG\Property( property="mobile", type="string", example="15026787264", description="手机号"),
     *                          @SWG\Property( property="openId", type="string", example="oHxgH0d0etTKMjdbOPh76YXl7Bf4", description=""),
     *                          @SWG\Property( property="money", type="string", example="1", description="金额"),
     *                          @SWG\Property( property="tradeType", type="string", example="recharge", description=""),
     *                          @SWG\Property( property="authorizerAppid", type="string", example="wx6b8c2837f47e8a09", description=""),
     *                          @SWG\Property( property="wxaAppid", type="string", example="wx912913df9fef6ddd", description=""),
     *                          @SWG\Property( property="detail", type="string", example="充值", description="详情"),
     *                          @SWG\Property( property="timeStart", type="string", example="1596508634", description=""),
     *                          @SWG\Property( property="timeExpire", type="string", example="1596508641", description=""),
     *                          @SWG\Property( property="tradeStatus", type="string", example="SUCCESS", description=""),
     *                          @SWG\Property( property="transactionId", type="string", example="4200000593202008045800935623", description=""),
     *                          @SWG\Property( property="bankType", type="string", example="CMB_DEBIT", description=""),
     *                          @SWG\Property( property="rechargeRuleId", type="string", example="7", description=""),
     *                          @SWG\Property( property="payType", type="string", example="wxpay", description=""),
     *                          @SWG\Property( property="feeType", type="string", example="CNY", description=""),
     *                          @SWG\Property( property="curFeeType", type="string", example="CNY", description=""),
     *                          @SWG\Property( property="curFeeRate", type="string", example="1", description=""),
     *                          @SWG\Property( property="curFeeSymbol", type="string", example="￥", description=""),
     *                          @SWG\Property( property="curPayFee", type="string", example="1", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getDepositTradeList(Request $request)
    {
        $depositTrade = new DepositTrade();

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $filter['trade_status'] = 'SUCCESS';

        if ($request->input('mobile', false)) {
            if (strlen($request->input('mobile')) == 11) {
                $filter['mobile'] = $request->input('mobile');
            } else {
                $filter['deposit_trade_id'] = $request->input('mobile');
            }
        }

        if ($request->input('user_id', false)) {
            $filter['user_id'] = $request->input('user_id');
        }

        if ($request->input('shop_name', false)) {
            $filter['shop_name'] = $request->input('shop_name');
        }

        if ($request->input('date_begin')) {
            $filter['date_begin'] = $request->input('date_begin');
            $filter['date_end'] = $request->input('date_end');
        }

        if ($request->input('trade_type', false)) {
            $filter['trade_type'] = explode(',', $request->input('trade_type'));
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $list = $depositTrade->getDepositTradeList($filter, $pageSize, $page);
        if ($list['list']) {
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            foreach ($list['list'] as $key => $value) {
                if ($datapassBlock) {
                    $list['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
            }
        }
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/deposit/count/index",
     *     summary="获取储值统计页数据",
     *     tags={"储值"},
     *     description="获取储值统计页数据",
     *     operationId="getDepositCountIndex",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="shopDepositTotal", type="string", example="null", description=""),
     *                  @SWG\Property( property="rechargeDayTotal", type="string", example="null", description=""),
     *                  @SWG\Property( property="consumeDayTotal", type="string", example="null", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getDepositCountIndex(Request $request)
    {
        $depositTrade = new DepositTrade();

        $companyId = app('auth')->user()->get('company_id');

        $shopDepositTotal = $depositTrade->getShopDepositTotal($companyId);

        $statsDay = new StatsDay();

        $date = date('Y-m-d');
        $rechargeDayTotal = $statsDay->getRechargeTotal($companyId, $date);
        $consumeDayTotal = $statsDay->getConsumeTotal($companyId, $date);

        return $this->response->array(['shopDepositTotal' => $shopDepositTotal, 'rechargeDayTotal' => $rechargeDayTotal, 'consumeDayTotal' => $consumeDayTotal]);
    }

    public function recharge(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('user_id', 'money');
        $rules = [
            'user_id' => ['required', '用户ID必填'],
            'money' => ['required|min:1', '充值金额必填|至少充值1分钱'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $depositTrade = new DepositTrade();
        $depositTrade->adminRecharge($companyId, $params['user_id'], intval($params['money']));
        return $this->response->array(['status' => true]);
    }
}
