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

namespace PopularizeBundle\Http\Api\V1\Action;

use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PopularizeBundle\Services\PromoterService;
use PopularizeBundle\Services\PromoterCountService;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use PopularizeBundle\Services\BrokerageService;

use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;

use PopularizeBundle\Services\SettingService;

class PromoterController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/popularize/promoter/list",
     *     summary="获取推广员列表",
     *     tags={"分销推广"},
     *     description="获取推广员列表",
     *     operationId="getPromoterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="通过手机号搜索", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="147", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="5", description="ID"),
     *                           @SWG\Property(property="promoter_id", type="string", example="5", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20110", description="会员ID"),
     *                           @SWG\Property(property="shop_name", type="string", example="", description="推广员自定义店铺名称"),
     *                           @SWG\Property(property="alipay_name", type="string", example="", description="推广员提现的支付宝姓名"),
     *                           @SWG\Property(property="shop_pic", type="string", example="", description="推广店铺封面"),
     *                           @SWG\Property(property="brief", type="string", example="", description="推广店铺描述"),
     *                           @SWG\Property(property="alipay_account", type="string", example="", description="推广员提现的支付宝账号"),
     *                           @SWG\Property(property="pid", type="integer", example="0", description="上级会员ID"),
     *                           @SWG\Property(property="shop_status", type="integer", example="1", description="开店状态 0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝 "),
     *                           @SWG\Property(property="reason", type="string", example="", description="审核拒绝原因"),
     *                           @SWG\Property(property="pmobile", type="string", example="", description="上级手机号"),
     *                           @SWG\Property(property="grade_level", type="integer", example="1", description="推广员等级"),
     *                           @SWG\Property(property="is_promoter", type="integer", example="1", description="是否为推广员"),
     *                           @SWG\Property(property="disabled", type="integer", example="0", description="是否有效"),
     *                           @SWG\Property(property="is_buy", type="integer", example="0", description="是否有购买记录"),
     *                           @SWG\Property(property="created", type="integer", example="1593669929", description=""),
     *                           @SWG\Property(property="children_count", type="integer", example="0", description=""),
     *                           @SWG\Property(property="bind_date", type="string", example="2020-07-02", description=""),
     *                           @SWG\Property(property="itemTotalPrice", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rebateTotal", type="integer", example="0", description=""),
     *                           @SWG\Property(property="noCloseRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="cashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rechargeRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="payedRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rechargePoint", type="integer", example="0", description="充值返佣积分"),
     *                           @SWG\Property(property="cashWithdrawalPoint", type="integer", example="0", description="已结算积分"),
     *                           @SWG\Property(property="noClosePoint", type="integer", example="0", description="未结算佣金积分"),
     *                           @SWG\Property(property="pointTotal", type="integer", example="0", description="积分总额"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoterList(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $promoterService = new PromoterService();

        $companyId = app('auth')->user()->get('company_id');
        if ($request->input('mobile', null)) {
            $filter['mobile'] = $request->input('mobile');
        }
        if ($request->input('username', null)) {
            $filter['username'] = $request->input('username');
        }
        if ($request->input('identity_name', null)) {
            $filter['identity_name'] = $request->input('identity_name');
        }
        $store_status = $request->input('store_status');
        if (!is_null($store_status) && $store_status != '') {
            $filter['shop_status'] = $request->input('store_status');
        }

        if ($request->input('time_start_begin')) {
            $filter['created|>='] = $request->input('time_start_begin');
            $filter['created|<='] = $request->input('time_start_end');
        }

        if ($request->get('distributor_id') && !$request->get('is_all', false)) {
            $filter_user['shop_id'] = $request->get('distributor_id');
        } elseif ($request->get('distributorIds')) {
            $filter_user['shop_id'] = $request->get('distributorIds');
        }

        //  todo var_dump($filter['distributor_id']);die();

        $merchantId = app('auth')->user()->get('merchant_id',0);
        $operatorType = app('auth')->user()->get('operator_type','');
        if ($operatorType == 'merchant') {
            $filter_distributor = array();
            $filter_distributor['company_id'] = $companyId; // 商户端只能获取商户的店铺
            $filter_distributor['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
            $distributorService = new DistributorService();
            $distributorList =  $distributorService->lists($filter_distributor,['created' => 'desc'],10000,1,false,"distributor_id");
            $shopIds = array_column($distributorList['list'],'distributor_id');
            $filter_user['shop_id'] = $shopIds;

        }

        $pathSource = $request->get('pathSource', false);
        if( (isset( $filter_user['shop_id'])  && $filter_user['shop_id']) 
        ||  ( isset($pathSource) && strpos($pathSource,'sellers') !== false )
        || $operatorType == 'merchant'
        ){
            $filter_user['company_id'] = $companyId;
            $salespersonService = new SalespersonService();
            $list = $salespersonService->getSalespersonList($filter_user, ['created_time' => 'DESC'], 1000, 1);
        //    var_dump($filter_user,  $list);

            $user_ids = array_column($list['list'],'user_id');
        //    var_dump($user_ids);
            $filter['user_id'] = $user_ids;

            if(isset($filter_user['shop_id']) && $filter_user['shop_id'] && !$user_ids){
                $data = [];
                return $this->response->array($data);

            }
        }


        $filter['company_id'] = $companyId;

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);
        $data = $promoterService->getPromoterList($filter, $page, $limit);

        $user_ids_data = array_column($data['list'],'user_id');
        
        if($user_ids_data){
            $filter_user_person = array();
            if(isset($shopIds) && $shopIds) $filter_user_person['shop_id'] = $shopIds;
            $filter_user_person['company_id'] = $companyId;
            $filter_user_person['user_id'] = $user_ids_data;
    
            $salespersonService = new SalespersonService();
            $salespersonlist = $salespersonService->getSalespersonList($filter_user_person, ['created_time' => 'DESC'], 1000, 1);
            $salespersonUser = array_column($salespersonlist['list'],null,'user_id');
            foreach ($data['list'] as $k => &$rowP) {
                $rowP['nameSalePerson'] = $salespersonUser[$rowP['user_id']]['name'] ?? '' ;
                if($rowP['nameSalePerson']){
                    $rowP['username'] = $rowP['username'] ." ( 业务员：{$rowP['nameSalePerson']} ) ";
                }
            }    
        }

        if ($request->input('distributor_id', false) 
        ||  ( isset($pathSource) && strpos($pathSource,'popularizedata') !== false )
        ) {
            $distributor_id = $request->input('distributor_id', false);
            $brokerageService = new BrokerageService();
            $filter['distributor_id'] = $distributor_id;
            if(isset($filter['mobile']) && $filter['mobile'] && isset($data['filter']['user_id'])){
                $filter['user_id'] = $data['filter']['user_id'];
                unset($filter['mobile']);
            }

            if ($operatorType == 'merchant' && $shopIds) {
                $filter['dIds'] = $shopIds;
    
            }
    
            $filter['groupby'] = 'distributor_id';
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-distributor_id:". json_encode($distributor_id));
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-filter:". json_encode($filter));
            $countDataShopList = $brokerageService->getSalesmanBrokerageCountList($filter, $limit, $page );
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":countDataShopList:". json_encode($countDataShopList));
            $countDataShopList_user = array_column($countDataShopList,null,'user_id'); 


            // $salesperson_id_arr = array_column($countDataShopList['list'],'salesperson_id'); 
            // if($salesperson_id_arr){
            //     $filter_salesperson = array(
            //         'salesperson_id' => $salesperson_id_arr,
            //     );
            //     $salespersonService = new SalespersonService();
    
            //     $data_alesperson = $salespersonService->salesperson->lists($filter_salesperson);
            //     $data_alesperson_id_arr = array_column($data_alesperson['list'],null,'salesperson_id'); 

            //     $countDataShopList['data_alesperson'] = $data_alesperson;
    
            // }
            // foreach($countDataShopList['list'] as $k => &$v_salesperson){
            //     $v_salesperson['mobile'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['mobile'] ?? '-';
            //     $v_salesperson['username'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['name'] ?? '-';
            // }
            // $data = 
            // return $this->response->array($countDataShopList);

        }  
        if( ( isset($pathSource) && strpos($pathSource,'popularizedata') !== false &&  strpos($pathSource,'sellers') !== false )
        ||  ( isset($pathSource) && strpos($pathSource,'popularizedata') !== false  &&  strpos($pathSource,'shopadmin') !== false)
        ){
            return $this->response->array($countDataShopList);
        }      

        
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $data['datapass_block'] = $datapassBlock;
        if ($data['total_count'] > 0) {
            $promoterCountService = new PromoterCountService();

            $userIdList = array_column($data['list'], 'user_id');
            $promoterIndex = $promoterCountService->getPromoterIndexCount($companyId, $userIdList);
            foreach ($data['list'] as $k => $row) {
                $data['list'][$k]['shopdata_price_sum'] =  $countDataShopList_user[$row['user_id']]['price_sum'] ?? 0;
                $data['list'][$k]['shopdata_rebate_sum'] =  $countDataShopList_user[$row['user_id']]['rebate_sum'] ?? 0;
                $data['list'][$k]['shopdata_rebate_sum_noclose'] =  $countDataShopList_user[$row['user_id']]['rebate_sum_noclose'] ?? 0;
                $data['list'][$k]['shopdata_total_fee'] =  $countDataShopList_user[$row['user_id']]['total_fee'] ?? 0;
                // mobile"13469793903"
                // name"cx"
                // price_sum"302"
                // rebate_sum"30"
                // rebate_sum_noclose"30"
                // total_fee"2"
                // user_id"494"



                $temp = $promoterIndex[$row['user_id']] ?? [];
                if (empty($temp)) {
                    $temp = [
                        'itemTotalPrice' => 0,
                        'rebateTotal' => 0,
                        'noCloseRebate' => 0,
                        'cashWithdrawalRebate' => 0,
                        'freezeCashWithdrawalRebate' => 0,
                        'rechargeRebate' => 0,
                        'payedRebate' => 0,
                        'rechargePoint' => 0,
                        'cashWithdrawalPoint' => 0,
                        'noClosePoint' => 0,
                        'pointTotal' => 0,
                    ];
                }

                $data['list'][$k] = array_merge($data['list'][$k], $temp);
                if ($datapassBlock) {
                    isset($row['mobile']) and $data['list'][$k]['mobile'] = data_masking('mobile', (string) $row['mobile']);
                    $data['list'][$k]['pmobile'] = data_masking('mobile', (string) $row['pmobile']);
                    isset($row['username']) and $data['list'][$k]['username'] = data_masking('truename', (string) $row['username']);
                }
            }
        }
        $data['countDataShopList'] = $countDataShopList ?? [];
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/children",
     *     summary="获取推广员直属下级列表",
     *     tags={"分销推广"},
     *     description="获取推广员直属下级列表",
     *     operationId="getPromoterchildrenList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="promoter_id", in="query", description="推广员id", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="2", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="relationship_depth", type="integer", example="1", description=""),
     *                           @SWG\Property(property="promoter_id", type="integer", example="36", description=""),
     *                           @SWG\Property(property="grade_level", type="integer", example="2", description=""),
     *                           @SWG\Property(property="company_id", type="integer", example="1", description=""),
     *                           @SWG\Property(property="shop_status", type="integer", example="0", description=""),
     *                           @SWG\Property(property="user_id", type="integer", example="20236", description=""),
     *                           @SWG\Property(property="pmobile", type="string", example="15755777778", description=""),
     *                           @SWG\Property(property="created", type="integer", example="1598493367", description=""),
     *                           @SWG\Property(property="disabled", type="integer", example="0", description=""),
     *                           @SWG\Property(property="pid", type="integer", example="20", description=""),
     *                           @SWG\Property(property="is_buy", type="integer", example="0", description=""),
     *                           @SWG\Property(property="is_promoter", type="integer", example="1", description=""),
     *                           @SWG\Property(property="children_count", type="integer", example="1", description=""),
     *                           @SWG\Property(property="bind_date", type="string", example="2020-08-27", description=""),
     *                           @SWG\Property(property="itemTotalPrice", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rebateTotal", type="integer", example="0", description=""),
     *                           @SWG\Property(property="noCloseRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="cashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="rechargeRebate", type="integer", example="0", description=""),
     *                           @SWG\Property(property="payedRebate", type="integer", example="0", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoterchildrenList(Request $request)
    {
        $promoterService = new PromoterService();

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        $dataPassBlock = $request->get('x-datapass-block');

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $id = $request->input('promoter_id', 1);
        $filter['promoter_id'] = $id;

        $data = $promoterService->getPromoterchildrenList($filter, 1, $page, $limit);
        if ($data['total_count'] > 0) {
            $promoterCountService = new PromoterCountService();
            foreach ($data['list'] as $k => $row) {
                $count = $promoterCountService->getPromoterCount($companyId, $row['user_id']);
                $data['list'][$k] = array_merge($data['list'][$k], $count);
            }

            foreach ($data['list'] as $k => $row) {
                if ($dataPassBlock) {
                    $data['list'][$k]['mobile'] = data_masking('mobile', (string)($row['mobile'] ?? ''));
                    $data['list'][$k]['pmobile'] = data_masking('mobile', (string)($row['pmobile'] ?? ''));
                    $data['list'][$k]['username'] = data_masking('truename', (string)($row['username'] ?? ''));
                    $data['list'][$k]['nickname'] = data_masking('truename', (string)($row['nickname'] ?? ''));
                }
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/shop",
     *     summary="对推广员的店铺状态进行更新",
     *     tags={"分销推广"},
     *     description="对推广员的店铺状态进行更新",
     *     operationId="updatePromoterShop",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_status", in="query", description="0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Parameter( name="reason", in="query", description="拒绝原因", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterShop(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $promoterService = new PromoterService();
        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $reason = $request->input('reason', null);

        $status = $request->input('status', 0);
        $data = $promoterService->updateShopStatus($companyId, $userId, $status, $reason);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/disabled",
     *     summary="禁用（冻结）/ 激活 （解冻）推广员 ",
     *     tags={"分销推广"},
     *     description="禁用（冻结）/ 激活 （解冻）推广员 ",
     *     operationId="updatePromoterDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="active", in="query", description="激活状态 true 激活 false 禁用", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterDisabled(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $promoterService = new PromoterService();
        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $userInfo = $promoterService->getInfoByUserId($userId);
        if (!$userInfo || !$userInfo['is_promoter'] || $userInfo['company_id'] != $companyId) {
            throw new ResourceException('无效的推广员');
        }

        $active = $request->input('active', 'true');
        $active = ($active == 'true') ? 0 : 1;
        $data = $promoterService->updateByUserId($userId, ['disabled' => $active]);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/grade",
     *     summary="推广员等级调整",
     *     tags={"分销推广"},
     *     description="推广员等级调整，推广员等级数据可以通过推广员 /popularize/promoter/config（GET请求方式）接口获取",
     *     operationId="updatePromoterDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="grade_level", in="query", description="推广员等级id 目前支持 1， 2， 3 三级", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterGrade(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $promoterService = new PromoterService();
        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $userInfo = $promoterService->getInfoByUserId($userId);
        if (!$userInfo || !$userInfo['is_promoter'] || $userInfo['company_id'] != $companyId) {
            throw new ResourceException('无效的推广员');
        }

        $gradeLevel = $request->input('grade_level');
        $data = $promoterService->updateByUserId($userId, ['grade_level' => intval($gradeLevel)]);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/remove",
     *     summary="调整推广员上下级关系 （调整到顶级）",
     *     tags={"分销推广"},
     *     description="调整推广员上下级关系，不要将当前推广员调整到自己的下级",
     *     operationId="updatePromoterDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员的user_id", required=true, type="string"),
     *     @SWG\Parameter( name="new_user_id", in="query", description="要调整到的推广员user_id，如果要调整到顶级，则当前值为 0即可", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updatePromoterRemove(Request $request)
    {
        $promoterService = new PromoterService();

        $userId = $request->input('user_id');
        if (!$userId) {
            throw new ResourceException('参数错误');
        }

        $companyId = app('auth')->user()->get('company_id');

        $newUserId = $request->input('new_user_id');
        $data = $promoterService->relRemove($companyId, $userId, $newUserId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/add",
     *     summary="指定会员成为顶级推广员",
     *     tags={"分销推广"},
     *     description="指定会员成为顶级推广员",
     *     operationId="addPromoter",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="当前推广员会员手机号", required=false, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前推广员会员id", required=false, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function addPromoter(Request $request)
    {
        $promoterService = new PromoterService();
        $companyId = app('auth')->user()->get('company_id');

        if ($request->input('mobile')) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($request->input('mobile'), $companyId);
            if (!$userId) {
                throw new ResourceException('当前手机号还不是会员');
            }
        } else {
            $userId = $request->input('user_id');
            if (!$userId) {
                throw new ResourceException('参数错误');
            }
        }
        // 后台手动添加推广员
        // 强制当前会员成为推广员
        if (config('common.oem-shuyun')) {
            // 数云模式
            $params = $request->all('identity_id', 'promoter_name', 'regions_id', 'address', 'pid', 'pmobile', 'pname');
            $settingService = new SettingService();
            $config = $settingService->getConfig($companyId);
            $params['internalOpenIdentity'] = $config['internalOpenIdentity'] ?? 'false';
            if ( $params['internalOpenIdentity'] == 'true' ) {
                $rules = [
                    'identity_id' => ['required|integer|min:1','推广员身份ID错误'],
                ];

                $error = validator_params($params, $rules);
                if ($error) {
                    throw new ResourceException($error);
                }
            }
            $data = $promoterService->changePromoter($companyId, $userId, true, $params);
        } else {
            $data = $promoterService->changePromoter($companyId, $userId, true);
            if (isset($data['list'][0]['pid']) && $data['list'][0]['pid']) {
                // 将当前推广员移动到顶级
                $promoterService->relRemove($companyId, $userId, 0);
            }
        }
        
        
        return $this->response->array(['status' => true]);
    }
    
    /**
     * 获取A级身份的推广员列表
     */
    public function getFirstIdentityPromoter(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $promoterService = new PromoterService();
        $result = $promoterService->getFirstIdentityPromoterList($companyId, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/export",
     *     summary="导出推广员列表",
     *     tags={"分销推广"},
     *     description="导出推广员列表",
     *     operationId="exportPromoterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="通过手机号搜索", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function exportPromoterList(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($inputData['mobile'] ?? '') {
            $params['mobile'] = $inputData['mobile'] ?? '';
        }
        if ($inputData['username'] ?? '') {
            $params['username'] = $inputData['username'] ?? '';
        }
        // 是否有权限查看加密数据
        $params['datapass_block'] = $request->get('x-datapass-block');
        $gotoJob = (new ExportFileJob('popularize', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    
    /**
     * @SWG\Get(
     *     path="/popularize/promoter/exportPopularizeOrder",
     *     summary="导出推广员订单列表",
     *     tags={"分销推广"},
     *     description="导出推广员列表",
     *     operationId="exportPromoterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="通过手机号搜索", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function exportPopularizeOrder(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($inputData['mobile'] ?? '') {
            $params['mobile'] = $inputData['mobile'] ?? '';
        }
        if ($inputData['username'] ?? '') {
            $params['username'] = $inputData['username'] ?? '';
        }

        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_id'] = $inputData['distributor_id'] ?? 0;
        }

        $merchantId = app('auth')->user()->get('merchant_id',0);
        $operatorType = app('auth')->user()->get('operator_type','');
        $companyId = $params['company_id'] ;
        if ($operatorType == 'merchant') {
            $filter_distributor = array();
            $filter_distributor['company_id'] = $companyId; // 商户端只能获取商户的店铺
            $filter_distributor['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
            $distributorService = new DistributorService();
            $distributorList =  $distributorService->lists($filter_distributor,['created' => 'desc'],10000,1,false,"distributor_id");
            $shopIds = array_column($distributorList['list'],'distributor_id');
            $params['dIds'] = $shopIds;

        }        
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($params, true));
        // 是否有权限查看加密数据
        $params['datapass_block'] = $request->get('x-datapass-block');
        $inputData['date_start'] = $inputData['date_start'] ?? 0;
        $inputData['date_end'] = $inputData['date_end'] ?? 0;
        if(!$inputData['date_start'] || !($inputData['date_end']) ){
            throw new ResourceException('请选择【下载日期】开始结束时间');

        }
        if($inputData['date_start']) $params['date_start'] = $inputData['date_start'];
        if($inputData['date_end'])   $params['date_end']   = $inputData['date_end'];
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($params, true));

        //exportPopularizeOrder brokerage
        $gotoJob = (new ExportFileJob('popularizeorder', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/exportPopularizeStatic",
     *     summary="导出推广业绩统计",
     *     tags={"分销推广"},
     *     description="导出推广业绩统计",
     *     operationId="exportPopularizeStatic",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="通过手机号搜索", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function exportPopularizeStatic(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($inputData['mobile'] ?? '') {
            $params['mobile'] = $inputData['mobile'] ?? '';
        }
        if ($inputData['username'] ?? '') {
            $params['username'] = $inputData['username'] ?? '';
        }

        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_id'] = $inputData['distributor_id'] ?? 0;
        }

        $merchantId = app('auth')->user()->get('merchant_id',0);
        $operatorType = app('auth')->user()->get('operator_type','');
        $companyId = $params['company_id'] ;
        if ($operatorType == 'merchant') {
            $filter_distributor = array();
            $filter_distributor['company_id'] = $companyId; // 商户端只能获取商户的店铺
            $filter_distributor['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
            $distributorService = new DistributorService();
            $distributorList =  $distributorService->lists($filter_distributor,['created' => 'desc'],10000,1,false,"distributor_id");
            $shopIds = array_column($distributorList['list'],'distributor_id');
            $params['dIds'] = $shopIds;

        }        
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($params, true));
        // 是否有权限查看加密数据
        $params['datapass_block'] = $request->get('x-datapass-block');
        $inputData['date_start'] = $inputData['date_start'] ?? 0;
        $inputData['date_end'] = $inputData['date_end'] ?? 0;
        if(!$inputData['date_start'] || !($inputData['date_end']) ){
            throw new ResourceException('请选择【下载日期】开始结束时间');

        }
        if($inputData['date_start']) $params['date_start'] = $inputData['date_start'];
        if($inputData['date_end'])   $params['date_end']   = $inputData['date_end'];
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':filter:' . var_export($params, true));

        //exportPopularizeStatic brokerage
        $gotoJob = (new ExportFileJob('popularizestatic', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * @SWG\Put(
     *     path="/popularize/promoter/member/remove",
     *     summary="调整会员上级关系 ",
     *     tags={"分销推广"},
     *     description="调整会员上级关系",
     *     operationId="updateMemberPromoterRemove",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="当前会员的user_id", required=true, type="string"),
     *     @SWG\Parameter( name="new_user_id", in="query", description="要调整到的推广员user_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function updateMemberPromoterRemove(Request $request)
    {
        $params = $request->all('user_id', 'new_user_id');
        $rules = [
            'user_id' => ['required|integer|min:1','会员ID错误'],
            'new_user_id' => ['required|integer|min:1','推广员ID错误'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $promoterService = new PromoterService();
        $companyId = app('auth')->user()->get('company_id');
        $data = $promoterService->memberRelRemove($companyId, $params['user_id'], $params['new_user_id']);
        return $this->response->array(['status' => true]);
    }


}
