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

namespace PopularizeBundle\Http\FrontApi\V1\Action;

use HfPayBundle\Services\HfpayBankService;
use HfPayBundle\Services\HfpayEnterapplyService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Services\BrokerageService;
use PopularizeBundle\Services\PromoterCountService;
use PopularizeBundle\Services\SettingService;
use PopularizeBundle\Services\CashWithdrawalService;

use DistributionBundle\Services\CashWithdrawalService as salesmanCashWithdrawalService;
use PopularizeBundle\Services\TaskBrokerageService;
use MembersBundle\Services\WechatUserService;
use SalespersonBundle\Services\SalespersonService;

class BrokerageController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/brokerages",
     *     summary="获取推广员佣金列表",
     *     tags={"分销推广"},
     *     description="获取推广员佣金列表",
     *     operationId="getBrokerageList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="brokerage_source", in="query", description="提现支付宝姓名", required=false, default="order", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="提现支付宝账号", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="推广员自定义店铺名称", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="close", type="object",
     *                        @SWG\Property(property="total_count", type="integer", example="1"),
     *                        @SWG\Property(property="list", type="string")
     *                    ),
     *                    @SWG\Property(property="noClose", type="object",
     *                        @SWG\Property(property="total_count", type="integer", example="1"),
     *                        @SWG\Property(property="list", type="string")
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getBrokerageList(Request $request)
    {
        $brokerageService = new BrokerageService();
        $authInfo = $request->get('auth');

        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'source' => $request->input('brokerage_source', 'order'),
        ];

        $filter['is_close'] = true;
        $isSalesmanPage = $request->input('isSalesmanPage', 0);
        if($isSalesmanPage){
            $shopName = $request->input('shopName', 0);
            $mobile   = $request->input('mobile', 0);
            $order_id   = $request->input('order_id', 0);
            if($shopName) $filter['shopName'] = $shopName ;
            if($mobile)   $filter['mobile']   = $mobile ;
            if($order_id)   $filter['order_id']   = $order_id ;
        }
        
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);
        if($isSalesmanPage){
            $data['close'] = $brokerageService->getBrokerageListSalesman($filter, $page, $pageSize);
        }else{
            $data['close'] = $brokerageService->getBrokerageList($filter, $page, $pageSize);
        }

        $filter['is_close'] =  false;
        if($isSalesmanPage){
            $data['noClose'] = $brokerageService->getBrokerageListSalesman($filter, $page, $pageSize);
        }else{
            $data['noClose'] = $brokerageService->getBrokerageList($filter, $page, $pageSize);
        }

        if ($request->input('close_type') == 'close') {
            return $this->response->array($data['close']);
        } elseif ($request->input('close_type') == 'noClose') {
            return $this->response->array($data['noClose']);
        } else {
            return $this->response->array($data);
        }
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/brokerage/count",
     *     summary="推广员佣金统计",
     *     tags={"分销推广"},
     *     description="推广员佣金统计",
     *     operationId="brokerageCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="payedRebate", type="integer", example="1", description="已提现"),
     *                    @SWG\Property(property="itemTotalPrice", type="integer", example="1", description="营业额"),
     *                    @SWG\Property(property="cashWithdrawalRebate", type="integer", example="1", description="可提现金额"),
     *                    @SWG\Property(property="noCloseRebate", type="integer", example="1", description="未结算金额"),
     *                    @SWG\Property(property="rebateTotal", type="integer", example="1", description="推广费总金额"),
     *                    @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="1", description="冻结金额"),
     *                    @SWG\Property(property="taskBrokerageItemTotalFee", type="integer", example="1", description="任务商品总销售额"),
     *                    @SWG\Property(property="orderNoCloseRebate", type="integer", example="1", description="任务商品总销售额"),
     *                    @SWG\Property(property="orderCloseRebate", type="integer", example="1", description="任务商品总销售额"),
     *                    @SWG\Property(property="orderRebate", type="integer", example="1", description="任务商品总销售额"),
     *                    @SWG\Property(property="orderTeamNoCloseRebate", type="integer", example="1", description=""),
     *                    @SWG\Property(property="orderTeamCloseRebate", type="integer", example="1", description=""),
     *                    @SWG\Property(property="orderTeamRebate", type="integer", example="1", description=""),
     *                    @SWG\Property(property="limit_time", type="integer", example="1", description=""),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function brokerageCount(Request $request)
    {
        $promoterCountService = new PromoterCountService();
        $authInfo = $request->get('auth');
        $isSalesmanPage = $request->input('isSalesmanPage', 0);

        if($isSalesmanPage){
            $distributor_id = $request->input('distributor_id', 0);
            $isSalesmanPage = $request->input('isSalesmanPage', 0);

            $salespersonService = new SalespersonService();
            $filter_salesman['company_id'] = $authInfo['company_id'];
            $filter_salesman['user_id']    = $authInfo['user_id'];
    
            $listdata = $salespersonService->getSalespersonList($filter_salesman, 1, 1000);
            $dIds = array_column($listdata['list'],'shop_id');

            //1 获取全部佣金总额 getSalesmanBrokerageCount
            $brokerageService = new BrokerageService();
            $filter_count['company_id']     = $authInfo['company_id'];

            if(isset($dIds) && $dIds) {
                $filter_count['dIds'] = $dIds;
            } 
            if($distributor_id) {
                $filter_count['distributor_id'] = $distributor_id;
                unset($filter_count['dIds']);
            }; 

            $filter_count['user_id']        = $authInfo['user_id']; 
            $countDataShop = $brokerageService->getSalesmanBrokerageCount($filter_count, 1 ,1000);

            //2 获取有效提现总额
            $filter_withdrow = $filter_count;
            if(isset($dIds) && $dIds) {
                $filter_withdrow['distributor_id'] = $dIds; 
                unset($filter_withdrow['dIds']);
            }
            if($distributor_id) {
                $filter_withdrow['distributor_id'] = $distributor_id;
            }; 

            $filter_withdrow['status'] = ['apply','process','success'];
            $salesmanCashWithdrawalService = new salesmanCashWithdrawalService();
            $list_withdraw = $salesmanCashWithdrawalService->lists($filter_withdrow);
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-filter_withdrow:". json_encode($filter_withdrow));
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-list_withdraw:". json_encode($list_withdraw));
            
            $sum_withdrawal_money = array_sum(array_column($list_withdraw['list'], 'money'));//            
            $countDataShop['sum_withdrawal_money']= $sum_withdrawal_money;
            $countDataShop['cashWithdrawalRebate']= (int)$countDataShop['rebate_sum_close'] - (int)$sum_withdrawal_money;
            $countDataShop['payedRebate'] =  (int)$sum_withdrawal_money;
            $countDataShop['orderRebate'] =  (int)$countDataShop['rebate_sum'];
            // noCloseRebate
            $countDataShop['noCloseRebate'] =  (int)$countDataShop['rebate_sum_noclose'];
            $countDataShop['orderCloseRebate'] =  (int)$countDataShop['rebate_sum_close'];
            // rebateTotal  
            $countDataShop['rebateTotal'] =  (int)$countDataShop['rebate_sum'];

            return $this->response->array($countDataShop);

        }

        $countData = $promoterCountService->getPromoterCount($authInfo['company_id'], $authInfo['user_id']);
        //已提现
        $data['payedRebate'] = $countData['payedRebate'];
        // 营业额
        $data['itemTotalPrice'] = $countData['itemTotalPrice'];
        // 可提现金额
        $data['cashWithdrawalRebate'] = $countData['cashWithdrawalRebate'];
        // 未结算金额
        $data['noCloseRebate'] = $countData['noCloseRebate'];
        // 推广费总金额
        $data['rebateTotal'] = $countData['rebateTotal'];
        // 冻结金额
        $data['freezeCashWithdrawalRebate'] = $countData['freezeCashWithdrawalRebate'];
        // 任务商品总佣金
        $taskBrokerageService = new TaskBrokerageService();
        $data['taskBrokerageItemTotalFee'] = $taskBrokerageService->getTaskPromoterRebate($authInfo['company_id'], $authInfo['user_id']);

        // 临时
        $brokerageService = new BrokerageService();
        // order
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'is_close' => false,
            'source' => 'order',
            'commission_type' => 'money',
        ];
        $data['orderNoCloseRebate'] = $brokerageService->sumRebate($filter);
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'is_close' => true,
            'source' => 'order',
            'commission_type' => 'money',
        ];
        $data['orderCloseRebate'] = $brokerageService->sumRebate($filter);
        $data['orderRebate'] = $data['orderNoCloseRebate'] + $data['orderCloseRebate'];

        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'is_close' => false,
            'source' => 'order_team',
            'commission_type' => 'money',
        ];
        $data['orderTeamNoCloseRebate'] = $brokerageService->sumRebate($filter);
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'is_close' => true,
            'source' => 'order_team',
            'commission_type' => 'money',
        ];
        $data['orderTeamCloseRebate'] = $brokerageService->sumRebate($filter);
        $data['orderTeamRebate'] = $data['orderTeamNoCloseRebate'] + $data['orderTeamCloseRebate'];
        // end

        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);
        $data['limit_time'] = $config['limit_time'];

        return $this->response->array($data);
    }

    // 积分统计

    //    "data": {
//        "grand_point_total": "7168",
//        "point_total": 8962,
//        "rebate_point": 0,
//        "order_no_close_rebate": 6270,
//        "order_close_rebate": 4936,
//        "order_total": "11206",
//        "order_team_no_close_rebate": 3904,
//        "order_team_close_rebate": 2232,
//        "order_team_total": "6136"
//    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/brokerage/point_count",
     *     summary="推广员积分佣金统计",
     *     tags={"分销推广"},
     *     description="推广员积分佣金统计",
     *     operationId="brokeragePointCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="grand_point_total", type="integer", example="1", description="累计获得积分"),
     *                    @SWG\Property(property="point_total", type="integer", example="1", description="推广积分总额"),
     *                    @SWG\Property(property="rebate_point", type="integer", example="1", description="小店提成"),
     *                    @SWG\Property(property="order_no_close_rebate", type="integer", example="1", description="提成未确认积分"),
     *                    @SWG\Property(property="order_close_rebate", type="integer", example="1", description="提成已确认积分"),
     *                    @SWG\Property(property="order_total", type="integer", example="1", description="提成总额"),
     *                    @SWG\Property(property="order_team_no_close_rebate", type="integer", example="1", description="津贴未确认积分"),
     *                    @SWG\Property(property="order_team_close_rebate", type="integer", example="1", description="津贴已确认积分"),
     *                    @SWG\Property(property="order_team_total", type="integer", example="1", description="津贴总额"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function brokeragePointCount(Request $request)
    {
        $authInfo = $request->get('auth');
        $promoterCountService = new PromoterCountService();

        $result = $promoterCountService->promoterPointCount($authInfo['company_id'], $authInfo['user_id']);

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/wxapp/promoter/cash_withdrawal",
     *     summary="推广员佣金提现申请",
     *     tags={"分销推广"},
     *     description="推广员佣金提现申请",
     *     operationId="applyCashWithdrawal",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="money", in="query", description="提现金额", required=false, default="0", type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="提现支付方式", required=false, default="wechat", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="id", type="integer", description="id"),
     *                    @SWG\Property(property="company_id", type="integer", description="company_id"),
     *                    @SWG\Property(property="user_id", type="string", description="会员id"),
     *                    @SWG\Property(property="account_name", type="string", description="提现账号姓名"),
     *                    @SWG\Property(property="pay_account", type="string", description="提现账号 微信为openid 支付宝为，支付账号"),
     *                    @SWG\Property(property="mobile", type="string", description="手机号"),
     *                    @SWG\Property(property="money", type="integer", description="提现金额，以分为单位"),
     *                    @SWG\Property(property="status", type="string", description="提现状态"),
     *                    @SWG\Property(property="remarks", type="string", description="备注"),
     *                    @SWG\Property(property="pay_type", type="string", description="提现支付类型"),
     *                    @SWG\Property(property="wxa_appid", type="string", description="提现的小程序appid"),
     *                    @SWG\Property(property="created", type="integer", description="创建时间"),
     *                    @SWG\Property(property="updated", type="integer", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function applyCashWithdrawal(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!isset($authInfo['open_id']) || !isset($authInfo['wxapp_appid'])) {
            throw new ResourceException('缺少参数');
        }
        $data = [
            'mobile' => $authInfo['mobile'],
            'company_id' => $authInfo['company_id'],
            'open_id' => $authInfo['open_id'],
            'user_id' => $authInfo['user_id'],
            'wxa_appid' => $authInfo['wxapp_appid'],
            'account_name' => $authInfo['username'],
            'money' => $request->input('money', 0),
        ];

        if ($data['money'] < 100) {
            throw new ResourceException('佣金提现最少为1元');
        }

        $payType = $request->input('pay_type', 'wechat');
        if ($payType == 'wechat' && $data['money'] > 80000) {
            throw new ResourceException('佣金单次最多提现800元');
        }

        //判断提现方式是汇付天下，则判断是否完成实名认证和银行卡绑定、
        if ($payType == 'hfpay') {
            //判断是否实名认证
            $user_filter = [
                'user_id' => $authInfo['user_id'],
                'status' => 3
            ];
            $service = new HfpayEnterapplyService();
            $user_hfpay_info = $service->getEnterapply($user_filter);
            if (empty($user_hfpay_info)) {
                throw new ResourceException('请先完成实名认证');
            }

            //判断是否完成提现卡绑定
            $filter = [
                'user_id' => $authInfo['user_id'],
                'is_cash' => 1
            ];
            $bank_service = new HfpayBankService();
            $brank = $bank_service->getBank($filter);
            if (empty($brank)) {
                throw new ResourceException('请绑定提现银行卡');
            }
        }

        $data['pay_type'] = $payType;

        $cashWithdrawalService = new CashWithdrawalService();
        $result = $cashWithdrawalService->applyCashWithdrawal($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/cash_withdrawal",
     *     summary="推广员佣金提现申请列表",
     *     tags={"分销推广"},
     *     description="推广员佣金提现申请列表",
     *     operationId="applyCashWithdrawal",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, default="0", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页页码", required=true, default="wechat", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="4", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="9", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="user_id", type="string", example="20002", description="推广员userId"),
     *                           @SWG\Property(property="pay_account", type="string", example="18818266589", description="提现账号 微信为openid 支付宝为，支付账号"),
     *                           @SWG\Property(property="account_name", type="string", example="冯博", description="提现账号姓名"),
     *                           @SWG\Property(property="mobile", type="string", example="18818266589", description="手机号"),
     *                           @SWG\Property(property="money", type="integer", example="100", description="提现金额，以分为单位"),
     *                           @SWG\Property(property="status", type="string", example="success", description="提现状态"),
     *                           @SWG\Property(property="remarks", type="string", example="", description="备注"),
     *                           @SWG\Property(property="pay_type", type="string", example="alipay", description="提现支付类型"),
     *                           @SWG\Property(property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="提现的小程序appid"),
     *                           @SWG\Property(property="created", type="integer", example="1582262981", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1582262995", description=""),
     *                           @SWG\Property(property="created_date", type="string", example="2020-02-21 13:29:41", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getCashWithdrawalList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1', '分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50', '每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        $cashWithdrawalService = new CashWithdrawalService();
        $data = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/brokerage/qrcode/info",
     *     summary="获取推广二维码",
     *     tags={"分销推广"},
     *     description="获取推广二维码",
     *     operationId="getBrokerageQrcode",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="path",
     *         description="商品id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="share_qrcode", type="string", description="分享二维码"),
     *                   @SWG\Property(property="share_uir", type="string", description="分享链接"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getBrokerageQrcode(Request $request)
    {
        $data = $request->input() ?: [];
        $authInfo = $request->get('auth');
        $query = [
            'user_id' => $authInfo['user_id']
        ];
        $params = array_merge($query, $data);
        $scene = urlencode(http_build_query($params));
        switch ($data['brokerage_type'] ?? '') {
            case 'item':
                $uri = config('common.brokerage_uri_item') . '?' . http_build_query($params) . '&scene=' . $scene;
                break;
            default:
                $uri = config('common.brokerage_uri') . '?' . http_build_query($params) . '&scene=' . $scene;
                break;
        }
        $result['share_qrcode'] = 'data:image/png;base64,' . app('DNS2D')->getBarcodePNG($uri, "QRCODE", 4, 4);
        $result['share_uir'] = $uri;
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/taskBrokerage/logs",
     *     summary="获取任务制佣金记录",
     *     tags={"分销推广"},
     *     description="获取任务制佣金记录",
     *     operationId="getTaskBrokerageList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="plan_date", in="query", description="账期时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="查询开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_end", in="query", description="查询结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="total_count", type="integer"),
     *                   @SWG\Property(property="list", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getTaskBrokerageList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        if ($request->input('status', false)) {
            $filter['status'] = $request->input('status');
        }

        if ($request->input('time_start', null) && $request->input('time_end', null)) {
            $filter['updated|gte'] = strtotime(date('Y-m-d 00:00:00', $request->input('time_start')));
            $filter['updated|lte'] = strtotime(date('Y-m-d 23:59:59', $request->input('time_end')));
        }

        if ($request->input('plan_date', false)) {
            $filter['plan_date'] = date('Y-m-t', strtotime($request->input('plan_date')));
        }

        $taskBrokerageService = new TaskBrokerageService();
        $data = $taskBrokerageService->getTaskBrokerageList($filter, '*', $params['page'], $params['pageSize'], ['created' => 'desc']);
        if ($data['list'] ?? null) {
            $userIds = array_column($data['list'], 'buy_user_id');
            $wechatUserService = new WechatUserService();
            $userFilter = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $userIds,
            ];
            $userWeachatList = $userWechatUser = $wechatUserService->getWechatUserList($userFilter);
            $userWechatData = array_column($userWeachatList, null, 'user_id');
            foreach ($data['list'] as &$v) {
                $v['username'] = isset($userWechatData[$v['buy_user_id']]) ? $userWechatData[$v['buy_user_id']]['nickname'] : '';
                $v['avatar'] = isset($userWechatData[$v['buy_user_id']]) ? $userWechatData[$v['buy_user_id']]['headimgurl'] : '';
            }
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/taskBrokerage/count",
     *     summary="获取任务制佣金统计",
     *     tags={"分销推广"},
     *     description="获取任务制佣金统计",
     *     operationId="getTaskBrokerageCountList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="查询开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="time_end", in="query", description="查询结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="plan_date", in="query", description="账期时间", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="total_count", type="integer", description="数据记录总条数"),
     *                     @SWG\Property(property="list", type="array", description="数据记录",
     *                        @SWG\Items(
     *                          @SWG\Property(property="id", type="integer"),
     *                          @SWG\Property(property="rebate_type", type="string", description="返佣模式"),
     *                          @SWG\Property(property="item_id", type="integer", description="商品id"),
     *                          @SWG\Property(property="item_bn", type="string", description="商品编号"),
     *                          @SWG\Property(property="total_fee", type="integer", description="已完成的总销售额"),
     *                          @SWG\Property(property="item_name", type="string", description="商品名称"),
     *                          @SWG\Property(property="item_spec_desc", type="string", description="商品规格描述"),
     *                          @SWG\Property(property="user_id", type="integer", description="会员id"),
     *                          @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                          @SWG\Property(property="rebate_conf", type="string", description="分销配置"),
     *                          @SWG\Property(property="rebate_money", type="integer", description="分销奖金"),
     *                          @SWG\Property(property="finish_num", type="integer", description="订单已完成数量"),
     *                          @SWG\Property(property="wait_num", type="integer", description="订单已支付，待完成数量"),
     *                          @SWG\Property(property="close_num", type="integer", description="订单已关闭数量，包含取消订单，售后订单"),
     *                          @SWG\Property(property="plan_date", type="integer", description="计划结算时间"),
     *                          @SWG\Property(property="created", type="integer", description="创建时间"),
     *                          @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                       )
     *                     ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getTaskBrokerageCountList(Request $request)
    {
        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50','每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        if ($request->input('time_start', null) && $request->input('time_end', null)) {
            $filter['updated|gte'] = strtotime(date('Y-m-d 00:00:00', $request->input('time_start')));
            $filter['updated|lte'] = strtotime(date('Y-m-d 23:59:59', $request->input('time_end')));
        }

        if ($request->input('plan_date', false)) {
            $filter['plan_date'] = date('Y-m-t', strtotime($request->input('plan_date')));
        } else {
            $filter['plan_date'] = date('Y-m-t');
        }

        $taskBrokerageService = new TaskBrokerageService();
        $data = $taskBrokerageService->getTaskBrokerageCountList($filter, '*', $params['page'], $params['pageSize'], ['created' => 'desc']);

        // 如果是查看历史业绩
        if ($request->input('plan_date', false)) {
            $data['total_rebate'] = $taskBrokerageService->getRebateMoneyTotal($filter);
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/second/brokerages",
     *     summary="获取B级推广员佣金列表",
     *     tags={"分销推广"},
     *     description="获取一个B级推广员佣金列表",
     *     operationId="getSecondBrokerageList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="brokerage_source", in="query", description="提现支付宝姓名", required=false, default="order", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="提现支付宝账号", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="推广员自定义店铺名称", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="close", type="object",
     *                        @SWG\Property(property="total_count", type="integer", example="1"),
     *                        @SWG\Property(property="list", type="string")
     *                    ),
     *                    @SWG\Property(property="noClose", type="object",
     *                        @SWG\Property(property="total_count", type="integer", example="1"),
     *                        @SWG\Property(property="list", type="string")
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getSecondBrokerageList(Request $request)
    {
        $params = $request->all('pageSize', 'page', 'promoter_user_id');
        $rules = [
            'page' => ['required|integer|min:1', '分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50', '每页最多查询50条数据'],
            'promoter_user_id' => ['required|integer|min:1', '推广员的会员ID错误'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $brokerageService = new BrokerageService();
        $authInfo = $request->get('auth');

        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $params['promoter_user_id'],
            'source' => $request->input('brokerage_source', 'order'),
            'is_close' => true,
        ];

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);
        $data['close'] = $brokerageService->getBrokerageList($filter, $page, $pageSize);

        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'source' => $request->input('brokerage_source', 'order'),
            'is_close' => false,
        ];
        $data['noClose'] = $brokerageService->getBrokerageList($filter, $page, $pageSize);
        $data['info'] = $brokerageService->getPromoterBrokerageInfo($authInfo['company_id'], $params['promoter_user_id']);
        if ($request->input('close_type') == 'close') {
            return $this->response->array($data['close']);
        } elseif ($request->input('close_type') == 'noClose') {
            return $this->response->array($data['noClose']);
        } else {
            return $this->response->array($data);
        }
    }
}
