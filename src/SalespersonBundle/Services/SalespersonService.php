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

namespace SalespersonBundle\Services;

use EasyWeChat\Factory;
use SalespersonBundle\Entities\SalespersonStatistics;
use SalespersonBundle\Entities\ShopSalesperson;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use SalespersonBundle\Entities\SalemanCustomerComplaint;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Entities\DistributorUser;
use DistributionBundle\Services\DistributorService;
use KaquanBundle\Entities\SalespersonGiveCoupons;
use PromotionsBundle\Entities\SalespersonActiveArticleStatistics;
use WorkWechatBundle\Entities\WorkWechatRel;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use DistributionBundle\Services\DistributorSalesmanRoleService;

use CompanysBundle\Services\CompanysService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderProfitService;
use WorkWechatBundle\Services\WorkWechatRelService;

use MembersBundle\Services\WechatUserService;
use PopularizeBundle\MysqlDatabase\Promoter as PromoterMysql;
use PopularizeBundle\Entities\Promoter as EntitiesPromoter;

class SalespersonService
{
    use GetOrderServiceTrait;

    /**
     * ShopSalesperson Repositories实例化
     */
    public $salesperson;
    public $relSalesperson;
    public $salemanCustomerComplaints;
    public $distributorUser;
    public $workWechatRelRepository;
    public $distributor;
    public $salespersonStatistics;
    public $salespersonActiveSatistics;
    public $salespersonGiveCoupons;

    public function __construct()
    {
        $this->salesperson = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);
        $this->relSalesperson = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        $this->salemanCustomerComplaints = app('registry')->getManager('default')->getRepository(SalemanCustomerComplaint::class);
        $this->distributorUser = app('registry')->getManager('default')->getRepository(DistributorUser::class);
        $this->workWechatRelRepository = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        $this->distributor = app('registry')->getManager('default')->getRepository(Distributor::class);
        $this->salespersonStatistics = app('registry')->getManager('default')->getRepository(SalespersonStatistics::class);
        $this->salespersonActiveSatistics = app('registry')->getManager('default')->getRepository(SalespersonActiveArticleStatistics::class);
        $this->salespersonGiveCoupons = app('registry')->getManager('default')->getRepository(SalespersonGiveCoupons::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->salesperson->$method(...$parameters);
    }

    /**
     * 创建门店人员信息
     *
     * @param array $data 门店人员信息数据
     */
    public function createSalesperson(array $data)
    {
        //添加类型为导购员，则判断是否超出可添加上限
        // if ($data['salesperson_type'] == 'shopping_guide') {
        //     $companys_service = new CompanysService();
        //     $companys = $companys_service->get(['company_id' => $data['company_id']]);
        //     $salesman_limit = $companys->getSalesmanLimit();
        //     if (!empty($salesman_limit)) {
        //         $current_num = $this->salesperson->count(['company_id' => $data['company_id'], 'salesperson_type' => $data['salesperson_type']]);
        //         if ($current_num >= $salesman_limit) {
        //             throw new ResourceException("导购员数量已超出最大可添加数");
        //         }
        //     }
        // }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $shopIds = $data['shop_id'] ?? '';
            $distributorIds = $data['distributor_id'] ?? '';
            //unset($data['shop_id'], $data['distributor_id']);
            unset( $data['distributor_id']);
            $result = $this->salesperson->createSalesperson($data);
            if ($shopIds) {
                foreach ((array)$shopIds as $shopId) {
                    $data = [
                       'company_id' => $result['company_id'],
                       'shop_id' => $shopId,
                       'salesperson_id' => $result['salesperson_id'],
                       'store_type' => 'shop',
                   ];
                    $this->relSalesperson->create($data);
                }
            }
            if ($distributorIds) {
                foreach ((array)$distributorIds as $distributorId) {
                    $data = [
                        'company_id' => $result['company_id'],
                        'shop_id' => $distributorId,
                        'salesperson_id' => $result['salesperson_id'],
                        'store_type' => 'distributor',
                    ];
                    $this->relSalesperson->create($data);
                }
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getSalespersonDetail($filter, $haveStoreData = false)
    {
        // ShopEx EcShopX Business Logic Layer
        $info = $this->salesperson->getInfo($filter);
        if ($info) {
            $rfilter['company_id'] = $info['company_id'];
            $rfilter['salesperson_id'] = $info['salesperson_id'];
            $relList = $this->relSalesperson->lists($rfilter);
            $info['shop_ids'] = [];
            $info['distributor_ids'] = [];
            foreach ($relList['list'] as $value) {
                if ($value['store_type'] == 'shop') {
                    $info['shop_ids'][] = $value['shop_id'];
                    $info['store_type'] = 'shop';
                } elseif ($value['store_type'] == 'distributor') {
                    $info['distributor_ids'][] = $value['shop_id'];
                    $info['store_type'] = 'distributor';
                }
            }

            if ($haveStoreData && $info['shop_ids']) {
                $shopsService = new ShopsService(new WxShopsService());
                $filter = [
                    'company_id' => $info['company_id'],
                    'wx_shop_id' => $info['shop_ids'],
                ];
                $shopList = $shopsService->getShopsList($filter);
                $info['shopList'] = $shopList['list'];
            }
            $info['distributor_id'] = reset($info['distributor_ids']);
            // if ($haveStoreData && $info['shop_ids']) {
            //     $filter = [
            //         'distributor_id' => $info['shop_ids'],
            //     ];
            //     $info['shopList'] = array_values($this->getDistributorList($filter));
            // }

            if ($haveStoreData && $info['distributor_ids']) {
                $filter = [
                    'distributor_id' => $info['distributor_ids'],
                ];
                $info['distributorList'] = array_values($this->getDistributorList($filter));
            }
            if ($info['distributor_id']) {
                $filter = [
                    'distributor_id' => $info['distributor_id'],
                    'company_id' => $info['company_id'],
                ];
                $data = $this->getDistributorList($filter);
                $info['store_name'] = $data[$info['distributor_id']]['name'];
                $info['shop_code'] = $data[$info['distributor_id']]['shop_code'];
            }
        }
        return $info;
    }

    /**
     * 获取门店人员信息
     */
    public function getSalespersonList($filter, $orderBy = ['created_time' => 'DESC'], $pageSize = 20, $page = 1)
    {
        if (!isset($filter['company_id'])) {
            return ['list' => [], 'total_count' => 0];
        }
//var_dump($filter,__LINE__);
        if ($filter['distributor_id'] ?? '') {
            $f = ['company_id' => $filter['company_id'], 'distributor_id' => $filter['distributor_id']];
            $salespersonIds = $this->relSalesperson->getSalespersonIdsByShopId($f, $page, $pageSize);
            if (!$salespersonIds) {
                return ['list' => [], 'total_count' => 0];
            }
            $filter['salesperson_id'] = ($filter['salesperson_id'] ?? 0) ? array_merge((array)$filter['salesperson_id'], (array)$salespersonIds) : (array)$salespersonIds;
            $shopdata = $this->getDistributorList($f);
        }

//var_dump($filter,__LINE__);
        if ($filter['shop_id'] ?? '') {
            $f = ['company_id' => $filter['company_id'], 'shop_id' => $filter['shop_id']];
 //           $salespersonIds = $this->relSalesperson->getSalespersonIdsByShopId($f, $page, $pageSize);
 //          var_dump('salesids:', $salespersonIds,__LINE__);
 //           if (!$salespersonIds) {
 //               return ['list' => [], 'total_count' => 0];
 //           }
 //           $filter['salesperson_id'] = ($filter['salesperson_id'] ?? 0) ? array_merge((array)$filter['salesperson_id'], (array)$salespersonIds) : (array)$salespersonIds;
 //           $shopdata = $this->getDistributorList(['company_id' => $filter['company_id'], 'distributor_id' => $filter['shop_id']]);
        }

  //      unset($filter['shop_id'], $filter['distributor_id']);
        unset($filter['distributor_id']);
  //      unset($filter['shop_id']);

        app('log')->info(':salesperson:servicelists:'.__FUNCTION__.__LINE__.':filter:'.json_encode($filter));

        $result = $this->salesperson->lists($filter, $page, $pageSize);
        app('log')->info(':salesperson:servicelists:'.__FUNCTION__.__LINE__.':result:'.json_encode($result));

        if (!($result ['list'] ?? [])) {
            return ['list' => [], 'total_count' => 0];
        }

        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();
        $roleTempList = $distributorSalesmanRoleService->lists(['company_id' => $filter['company_id']], '*', 1, 2000, ['salesman_role_id' => 'desc']);
        $roleList = [];
        if ($roleTempList['list']) {
            $roleList = array_column($roleTempList['list'], null, 'salesman_role_id');
        }

        $userIds = array_column($result['list'], 'user_id');

        $wechatUserService = new WechatUserService();
        $wechatUserList = $wechatUserService->getWechatUserList(['company_id' =>  $filter['company_id'], 'user_id' => $userIds]);
        $wechatUserList = array_column($wechatUserList, null, 'user_id');

        $filter_promoter['is_promoter'] = 1;
        $filter_promoter['user_id'] = $userIds;
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterList = $promoterRepository->getLists($filter_promoter, $page, 1000, ['created' => 'DESC']);
        $promoterList_user = array_column($promoterList['list'], null, 'user_id');
        $promoterData = $promoterList['list']['list'] ?? $promoterList['list'];
        $pidList = array_column($promoterData, 'id');
        $promoterMysql = new PromoterMysql();
        if($pidList){
            $childrenCountList = $promoterMysql->relationChildrenCountByPidList($pidList);
        }

        $shopIds = array_column($result['list'], 'shop_id');
        $distributorService = new DistributorService();
        $store_filter = ['company_id' => $filter['company_id'], 'distributor_id' => $shopIds];
        $distributoreList = $distributorService->getDistributorEasylists($store_filter);
        if ($distributoreList['list']) {
            $storeInfo = array_column($distributoreList['list'], null, 'distributor_id');
        }


        foreach ($result['list'] as &$value) {
            $value['child_count'] = $this->workWechatRelRepository->count(['salesperson_id' => $value['salesperson_id']]);
            // $value['children_count'] = $childrenCountList[$promoterList_user[$value['user_id']]['id']]['count'] ?? 0;
            $value['children_count'] =  isset($promoterList_user[$value['user_id']]) ? ($childrenCountList[$promoterList_user[$value['user_id']]['id']]['count'] ?? 0) : 0;            
            $value['promoter_id'] = $promoterList_user[$value['user_id']]['promoter_id'] ?? 0;
            $value['salespersonId'] = $value['salesperson_id'];
            $value['companyId'] = $value['company_id'];
            $value['storeInfo'] = $storeInfo[$value['shop_id']] ?? array('name' => '');
            $value['createdTime'] = $value['created_time'];
            $value['role_name'] = isset($roleList[$value['role']]) ? $roleList[$value['role']]['role_name'] : '无角色';
            $value['salespersonType'] = $value['salesperson_type'];
        }
        return $result;
    }

    public function getSalespersonListByDistributor($filter, $orderBy = ['created_time' => 'DESC'], $pageSize = 20, $page = 1)
    {
        if (!isset($filter['company_id'])) {
            return ['list' => [], 'total_count' => 0];
        }
        if ($filter['distributor_id'] ?? '') {
            $f = ['company_id' => $filter['company_id'], 'distributor_id' => $filter['distributor_id']];
            $salespersonIds = $this->relSalesperson->getSalespersonIdsByDistributorId($f, $page, $pageSize);
            if (!$salespersonIds) {
                return ['list' => [], 'total_count' => 0];
            }
            $filter['salesperson_id'] = ($filter['salesperson_id'] ?? 0) ? array_merge((array)$filter['salesperson_id'], (array)$salespersonIds) : (array)$salespersonIds;
        }


        unset($filter['shop_id'], $filter['distributor_id']);

        $result = $this->salesperson->lists($filter, $page, $pageSize);
        if (!($result ['list'] ?? [])) {
            return ['list' => [], 'total_count' => 0];
        }

        return $result;
    }

    /**
     * 获取导购员销售额统计数据
     * @param $salesperson_info
     * @param $date_range
     * @param $start
     * @param $end
     * @return array
     */
    public function getSalespersonCountData($salesperson_info, $start, $end)
    {
        $filter = [
            'salesperson_id' => $salesperson_info['salesperson_id'],
            'company_id' => $salesperson_info['company_id'],
            'give_time|gte' => $start,
            'give_time|lte' => $end,
            'status' => 1,
        ];
        $result['sendCouponsNum'] = $this->salespersonGiveCoupons->count($filter);

        $newUserFilter['company_id'] = $salesperson_info['company_id'];
        $newUserFilter['salesperson_id'] = $salesperson_info['salesperson_id'];
        $newUserFilter['bound_time|gte'] = $start;
        $newUserFilter['bound_time|lte'] = $end;
        $newUserFilter['is_bind'] = 1;
        $workWechatRelService = new WorkWechatRelService();
        $result['newUserNum'] = 0;
        $result['activityForward'] = 0;

        $orderCountData = $this->orderCountData($salesperson_info['company_id'], $salesperson_info['salesperson_id'], $start, $end);
        $result['orderPayFee'] = $orderCountData['total_fee'];
        $result['orderPayNum'] = $orderCountData['total_count'];

        $profitCountData = $this->profitCountData($salesperson_info['company_id'], $salesperson_info['salesperson_id'], $start, $end);
        $result['newGuestDivided'] = $profitCountData['seller_fee'];
        $popularizeProfitCountData = $this->popularizeProfitCountData($salesperson_info['company_id'], $salesperson_info['salesperson_id'], $start, $end);
        $result['salesCommission'] = $popularizeProfitCountData['popularize_seller_fee'];

        return $result;
    }

    /**
     * 获取导购本月统计数据
     *
     * @param int $companyId
     * @param int $salespersonId
     * @param int $currentMonthStartTime
     * @param int $currentMonthEndTime
     * @return array
     */
    public function getCurrentMonthStatistics($companyId, $salespersonId, $currentMonthStartTime = null, $currentMonthEndTime = null)
    {
        $month = date('Y-m-01 00:00:00');
        $currentMonthStartTime = $currentMonthStartTime ?: strtotime($month);
        $currentMonthEndTime = $currentMonthEndTime ?: strtotime("$month + 1 month -1 day") + 86399;

        $orderService = $this->getOrderService('normal');
        $orderResult = $this->orderCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime, true);
        $offlineOrderResult = $this->orderCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime, false);
        // 推广销售
        $result['popularize_order_fee'] = $orderResult['total_fee'] ?? 0;
        // 推广订单数
        $result['popularize_order_count'] = $orderResult['total_count'] ?? 0;
        // 门店开单
        $result['offline_order_fee'] = $offlineOrderResult['total_fee'] ?? 0;
        // 门店开单数
        $result['offline_order_count'] = $offlineOrderResult['total_count'] ?? 0;
        // 销售额
        $result['order_fee'] = $result['popularize_order_fee'] + $result['offline_order_fee'];
        // 订单数
        $result['order_count'] = $result['popularize_order_count'] + $result['offline_order_count'];

        return $result;
    }

    /**
     * 获取导购当月信息
     *
     * @param int $companyId
     * @param int $salespersonId
     * @param int $currentMonthStartTime
     * @param int $currentMonthEndTime
     * @return array
     */
    public function getCurrentMonthStatisticsInfo($companyId, $salespersonId, $currentMonthStartTime = null, $currentMonthEndTime = null)
    {
        $month = date('Y-m-01 00:00:00');
        if (!$currentMonthStartTime && !$currentMonthEndTime) {
            $currentMonthStartTime = $currentMonthStartTime ?: strtotime($month);
            $currentMonthEndTime = $currentMonthEndTime ?: strtotime("$month + 1 month -1 day") + 86399;
        }
        $key = 'salesperson:month:statistics:' . date('Ym', $currentMonthStartTime) . ':' . $companyId . ':' . $salespersonId;
        $redis = app('redis');
        $result = $redis->get($key);
        if (!$result) {
            $result = $this->getCurrentMonthStatistics($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime);
            $redundResult = $this->refundOrderCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime);
            // 退款金额
            $result['total_refund_fee'] = $redundResult['total_refund_fee'] ?? 0;
            // 退款单数
            $result['total_refund_count'] = $redundResult['total_refund_count'] ?? 0;
            $customerResult = $this->customerCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime);
            // 绑定客户数
            $result['bind_count'] = $customerResult['bind_count'] ?? 0;
            // 添加好友数
            $result['friend_count'] = $customerResult['friend_count'] ?? 0;
            // 销售客户数
            $result['sale_count'] = $customerResult['sale_count'] ?? 0;

            $profitOnlineCountData = $this->profitCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime);
            // 预计绑定会员提成
            $result['seller_fee'] = $profitOnlineCountData['seller_fee'] ?? 0;
            $popularizeProfitCountData = $this->popularizeProfitCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime, true);
            // 预计客户推广提成
            $result['popularize_seller_fee'] = $popularizeProfitCountData['popularize_seller_fee'] ?? 0;
            $profitOfflineCountData = $this->popularizeProfitCountData($companyId, $salespersonId, $currentMonthStartTime, $currentMonthEndTime, false);
            // 预计门店开单提成
            $result['offline_seller_fee'] = $profitOfflineCountData['popularize_seller_fee'] ?? 0;
            // 预计销售提成
            $result['sales_fee'] = $result['seller_fee'] + $result['offline_seller_fee'] + $result['popularize_seller_fee'];
            $redis->set($key, json_encode($result));
            $redis->expire($key, 300);
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 导购订单退款统计
     *
     * @param int $companyId
     * @param int $salespersonId
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function refundOrderCountData($companyId, $salespersonId, $currentMonthStartTime = null, $currentMonthEndTime = null)
    {
        $filter['ono.company_id'] = $companyId;
        $filter['ono.order_type'] = 'normal';
        $filter['ono.pay_status'] = 'PAYED';
        $filter['ono.order_status'] = 'CANCEL';
        $filter['ono.salesman_id'] = $salespersonId;
        $filter['ar.refund_success_time|gte'] = $currentMonthStartTime;
        $filter['ar.refund_success_time|lte'] = $currentMonthEndTime;
        $filter['ar.refund_status'] = 'SUCCESS';
        $orderService = $this->getOrderService('normal');
        // 退款订单
        $result['total_refund_count'] = (int)$orderService->orderInterface->normalOrdersRepository->refundCount($filter, 'DISTINCT(ar.order_id)');
        $result['total_refund_fee'] = 0;
        if ($result['total_refund_count']) {
            // 退款金额
            $result['total_refund_fee'] = (int)$orderService->orderInterface->normalOrdersRepository->refundSum($filter, 'refunded_fee');
        }
        return $result;
    }

    /**
     * 导购订单统计
     *
     * @param int $companyId
     * @param int $salespersonId
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    private function orderCountData($companyId, $salespersonId, $startTime, $endTime, $isOnline = null)
    {
        $filter['company_id'] = $companyId;
        $filter['order_type'] = 'normal';
        $filter['pay_status'] = 'PAYED';
        $filter['salesman_id'] = $salespersonId;
        $filter['create_time|gte'] = $startTime;
        $filter['create_time|lte'] = $endTime;
        $orderService = $this->getOrderService('normal');
        if ($isOnline === true) {
            $filter['order_source|neq'] = 'shop_offline';
        } elseif ($isOnline === false) {
            $filter['order_source'] = 'shop_offline';
        }
        // 销售订单数量
        $result['total_count'] = (int)$orderService->orderInterface->normalOrdersRepository->count($filter);
        $result['total_fee'] = 0;
        if ($result['total_count']) {
            // 销售金额
            $result['total_fee'] = (int)$orderService->orderInterface->normalOrdersRepository->sum($filter, 'total_fee');
        }

        return $result;
    }

    /**
     * 统计导购信息
     *
     * @param int $salespersonId 导购id
     * @param int $startTime 开始时间
     * @param int $endTime 结束时间
     * @return array
     */
    private function popularizeProfitCountData($companyId, $salespersonId, $startTime, $endTime, $isOnline = null)
    {
        $orderProfitFilter['ono.company_id'] = $companyId;
        $orderProfitFilter['ono.salesman_id'] = $salespersonId;
        if ($isOnline === true) {
            $orderProfitFilter['ono.order_source|neq'] = 'shop_offline';
        } elseif ($isOnline === false) {
            $orderProfitFilter['ono.order_source'] = 'shop_offline';
        }
        $orderProfitFilter['op.order_profit_status|neq'] = 0;
        $orderProfitFilter['op.created|gte'] = $startTime;
        $orderProfitFilter['op.created|lte'] = $endTime;
        $orderProfitService = new OrderProfitService();
        // 推广提成
        $result['popularize_seller_fee'] = (int)$orderProfitService->sum($orderProfitFilter, 'popularize_seller');
        return $result;
    }

    /**
     * 统计导购信息
     *
     * @param int $salespersonId 导购id
     * @param int $startTime 开始时间
     * @param int $endTime 结束时间
     * @param bool $isOnline 是否是线上 true 是 false 否
     * @return array
     */
    private function profitCountData($companyId, $salespersonId, $startTime, $endTime)
    {
        $orderProfitService = new OrderProfitService();
        $orderProfitFilter['ono.company_id'] = $companyId;
        $orderProfitFilter['op.seller_id'] = $salespersonId;
        $orderProfitFilter['op.order_profit_status|neq'] = 0;
        $orderProfitFilter['op.created|gte'] = $startTime;
        $orderProfitFilter['op.created|lte'] = $endTime;
        // 销售提成
        $result['seller_fee'] = (int)$orderProfitService->sum($orderProfitFilter, 'op.seller');
        return $result;
    }

    /**
     * 统计导购添加用户信息
     *
     * @param int $salespersonId 导购id
     * @param int $startTime 开始时间
     * @param int $endTime 结束时间
     * @return array
     */
    public function customerCountData($companyId, $salespersonId, $startTime, $endTime)
    {
        $filter['company_id'] = $companyId;
        $filter['salesperson_id'] = $salespersonId;
        $filter['bound_time|gte'] = $startTime;
        $filter['bound_time|lte'] = $endTime;
        $filter['is_bind'] = 1;
        $workWechatRelService = new WorkWechatRelService();
        $result['bind_count'] = (int)$workWechatRelService->count($filter);

        $friendFilter['salesperson_id'] = $salespersonId;
        $friendFilter['add_friend_time|gte'] = $startTime;
        $friendFilter['add_friend_time|lte'] = $endTime;
        $friendFilter['is_friend'] = 1;
        $result['friend_count'] = (int)$workWechatRelService->count($friendFilter);

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $result['sale_count'] = $qb->select('COUNT(DISTINCT(user_id))')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('order_type', $qb->expr()->literal('normal')))
            ->andWhere($qb->expr()->eq('pay_status', $qb->expr()->literal('PAYED')))
            ->andWhere($qb->expr()->eq('salesman_id', $salespersonId))
            ->andWhere($qb->expr()->gte('create_time', $startTime))
            ->andWhere($qb->expr()->lte('create_time', $endTime))
            ->execute()->fetchColumn();
        return $result;
    }

    /**
     * 统计导购分润信息
     *
     * @param int $companyId
     * @param int $salespersonId
     * @param int $startTime
     * @param int $endTime
     * @return void
     */
    public function profitFee($companyId, $salespersonId, $startTime = null, $endTime = null)
    {
        if (!$startTime && !$endTime) {
            $month = date('Y-m-01 00:00:00');
            $startTime = $startTime ?: strtotime($month);
            $endTime = $endTime ?: strtotime("$month + 1 month -1 day") + 86399;
        }
        $result = $this->getCurrentMonthStatisticsInfo($companyId, $salespersonId, $startTime, $endTime);

        $orderProfitService = new OrderProfitService();
        $filter = [
            'ono.order_source|neq' => 'shop_offline',
            'op.order_profit_status' => 1,
            'op.seller_id' => $salespersonId,
            'ono.create_time|gte' => $startTime,
            'ono.create_time|lte' => $endTime,
        ];

        $result['unconfirmed_seller_fee'] = (int)$orderProfitService->orderItemsProfitRepository->sum($filter, 'seller');
        $filter['op.order_profit_status'] = 2;
        $result['confirm_seller_fee'] = (int)$orderProfitService->orderItemsProfitRepository->sum($filter, 'seller');
        $filter = [
            'ono.order_source' => 'shop_offline',
            'op.order_profit_status' => 1,
            'op.seller_id' => $salespersonId,
            'ono.create_time|gte' => $startTime,
            'ono.create_time|lte' => $endTime,
        ];
        $result['unconfirmed_offline_seller_fee'] = (int)$orderProfitService->orderItemsProfitRepository->sum($filter, 'seller');
        $filter['op.order_profit_status'] = 2;
        $result['confirm_offline_seller_fee'] = (int)$orderProfitService->orderItemsProfitRepository->sum($filter, 'seller');
        $filter = [
            'op.order_profit_status' => 1,
            'op.popularize_seller_id' => $salespersonId,
            'ono.create_time|gte' => $startTime,
            'ono.create_time|lte' => $endTime,
        ];
        $result['unconfirmed_popularize_seller_fee'] = (int)$orderProfitService->orderItemsProfitRepository->sum($filter, 'popularize_seller');
        $filter['op.order_profit_status'] = 2;
        $result['confirm_popularize_seller_fee'] = (int)$orderProfitService->orderItemsProfitRepository->sum($filter, 'popularize_seller');
        $result['unconfirmed_fee'] = $result['unconfirmed_seller_fee'] + $result['unconfirmed_offline_seller_fee'] + $result['unconfirmed_popularize_seller_fee'];
        $result['confirm_fee'] = $result['confirm_seller_fee'] + $result['confirm_offline_seller_fee'] + $result['confirm_popularize_seller_fee'];
        return $result;
    }

    /**
     * 获取门店管理员或核销员管理的店铺列表
     */
    public function getSalespersonRelShopdata($filter, $page = 1, $pageSize = 500)
    {
        if (isset($filter['store_name'])) {
            $distributorService = new DistributorService();
            $store_filter = ['company_id' => $filter['company_id'], 'name|contains' => $filter['store_name']];
            $distributoreList = $distributorService->getDistributorEasylists($store_filter);
            if (!$distributoreList['list']) {
                $filter['shop_id'] = [0];
            } else {
                $filter['shop_id'] = array_column($distributoreList['list'], 'distributor_id');
            }
            unset($filter['store_name']);
        }
        $result = $this->relSalesperson->lists($filter, $page, $pageSize);

        if ($result['list']) {
            foreach ($result['list'] as $value) {
                $storedata[$value['store_type']][] = $value['shop_id'];
            }
            if ($storedata['shop'] ?? []) {
                $shopdata['shop'] = $this->getShopList(['company_id' => $filter['company_id'], 'wx_shop_id' => $storedata['shop']]);
            }
            if ($storedata['distributor'] ?? []) {
                $shopdata['distributor'] = $this->getDistributorList(['company_id' => $filter['company_id'], 'distributor_id' => $storedata['distributor']]);
            }
            foreach ($result['list'] as &$value) {
                $tagname = $value['store_type'] == 'shop' ? '门店' : '店铺';
                $shop = $shopdata[$value['store_type']][$value['shop_id']] ?? [];
                $value['address'] = $shop['address'] ?? '未知';
                $value['store_name'] = $shop['name'] ?? '未知';
                if ($tagname == '门店') {
                    $value['shop_id'] = $shop['shop_id'] ?? '未知';
                }
                if ($tagname == '店铺') {
                    $value['shop_id'] = $shop['distributor_id'] ?? '未知';
                }
                $value['distributor_id'] = $shop['distributor_id'] ?? '未知';
                $value['shop_logo'] = $shop['logo'] ?? '未知';
                $value['hour'] = $shop['hour'] ?? '未知';
            }
        }
        return $result;
    }

    private function getShopList($filter)
    {
        $shopsService = new ShopsService(new WxShopsService());
        $shopLogo = $shopsService->getWxShopsSetting($filter['company_id'])['logo'];
        $shopList = $shopsService->getShopsList($filter);
        $shopdata = [];
        foreach ($shopList['list'] as $val1) {
            $shopdata[$val1['wxShopId']] = [
                'address' => $val1['address'],
                'name' => $val1['storeName'],
                'shop_id' => $val1['wxShopId'],
                'logo' => $shopLogo,
            ];
        }
        return $shopdata;
    }

    private function getDistributorList($filter)
    {
        $distributoreService = new DistributorService();
        $distributorList = $distributoreService->getDistributorEasylists($filter);
        $shopdata = array_column($distributorList['list'], null, 'distributor_id');
        return $shopdata;
    }

    /**
     * 根据人员手机号查询人员信息
     */
    public function getSalespersonByMobile($mobile, $isValide = false)
    {
        $filter['mobile'] = $mobile;
        if ($isValide) {
            $filter['is_valid'] = $isValide;
        }
        $info = $this->getSalespersonDetail($filter);
        return $info;
    }

    /**
     * 根据人员手机号查询人员信息
     */
    public function getSalespersonByMobileByType($mobile, $type = '', $isValide = false)
    {
        $filter['mobile'] = $mobile;
        if ($isValide) {
            $filter['is_valid'] = $isValide;
        }
        if ($type) {
            $filter['salesperson_type'] = $type;
        }
        $info = $this->getSalespersonDetail($filter);
        return $info;
    }

    /**
     * 修改门店人信息
     *
     * @param int $companyId 企业ID
     * @param int $salespersonId 门店人员ID
     * @param array $data 门店人员信息数据
     */
    public function updateSalesperson($companyId, $salespersonId, array $data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $shopIds = $data['shop_id'] ?? '';
            $distributorIds = $data['distributor_id'] ?? '';
            unset($data['shop_id'], $data['distributor_id']);
            $result = $this->salesperson->updateSalesperson($companyId, $salespersonId, $data);
            if ($shopIds) {
                $this->relSalesperson->deleteBy($companyId, $salespersonId);
                foreach ((array)$shopIds as $shopId) {
                    $data = [
                        'company_id' => $companyId,
                        'shop_id' => $shopId,
                        'salesperson_id' => $salespersonId,
                        'store_type' => 'shop',
                    ];
                    $this->relSalesperson->create($data);
                }
            }
            if ($distributorIds) {
                $this->relSalesperson->deleteBy($companyId, $salespersonId);
                foreach ((array)$distributorIds as $distributorId) {
                    $data = [
                        'company_id' => $companyId,
                        'shop_id' => $distributorId,
                        'salesperson_id' => $salespersonId,
                        'store_type' => 'distributor',
                    ];
                    $this->relSalesperson->create($data);
                }
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 修改门店人角色权限信息
     *
     * @param int $companyId 企业ID
     * @param int $salespersonId 门店人员ID
     * @param array $role 门店人员权限集合
     */
    public function updateSalespersonRole($companyId, $salespersonId, string $role)
    {
        $result = $this->salesperson->updateSalespersonRole($companyId, $salespersonId, $role);
        return $result;
    }

    /**
     * 删除门店人员信息
     *
     * @param int $companyId 企业ID
     * @param int $salespersonId 门店人员ID
     */
    public function deleteSalesperson($companyId, $salespersonId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->salesperson->deleteSalesperson($companyId, $salespersonId);
            $this->relSalesperson->deleteBy($companyId, $salespersonId);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function hincrbyChildCount($companyId, $salesmanId)
    {
        if ($salesmanId) {
            return $this->salesperson->hincrbyChildCount($companyId, $salesmanId);
        }
    }

    public function getSalemanCustomerComplaintsList($filter, $orderBy = ['created_time' => 'DESC'], $page = 1, $pageSize = 100)
    {
        if (!isset($filter['company_id'])) {
            return ['list' => [], 'total_count' => 0];
        }
        $list = $this->salemanCustomerComplaints->lists($filter, '*', $page, $pageSize, $orderBy);
        return $list;
    }

    /**
     * @param $filter array 过滤条件
     * @param $data array 更新数据
     * @return mixed updateResult array 返回更新后结果
     */
    public function replySalemanCustomerComplaints($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->salemanCustomerComplaints->updateOneBy($filter, $data);
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 会员发起投诉导购员
     * @param $filter
     * @param $data
     * @return mixed
     */
    public function sendSalespersonComplaints($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $salesperson_rel_user = $this->workWechatRelRepository->getInfo($filter);
            if (!$salesperson_rel_user) {
                throw new ResourceException('获取导购员信息失败');
            }
            $salespersonFilter = [
                'salesperson_id' => $salesperson_rel_user['salesperson_id'],
                'salesperson_type' => 'shopping_guide',
                'is_valid' => 'true',
            ];
            $salesperson_info = $this->getSalespersonDetail($salespersonFilter);
            if (!$salesperson_info) {
                throw new ResourceException('获取导购员信息失败！');
            }
//            $filter = [
//                'distributor_id' => $salesperson_rel_user['distributor_id']
//            ];
//            $distributor_info = $this -> distributor ->getInfo($filter);
//            if(!$distributor_info) throw new ResourceException('获取导购员店铺信息失败');

            $data['saleman_id'] = $salesperson_info['salesperson_id'];
            $data['distributor_id'] = $salesperson_info['distributor_id'];
            $data['saleman_name'] = $salesperson_info['name'];
            $data['saleman_mobile'] = $salesperson_info['mobile'];
            $data['saleman_distribution_name'] = $salesperson_info['store_name'] ?? "";
            $data['saleman_avatar'] = $salesperson_info['avatar'] ?? '';

            $insert_result = $this->salemanCustomerComplaints->create($data);
            $conn->commit();

            if ($insert_result) {
                return $insert_result;
            } else {
                throw new ResourceException('投诉失败,请稍后重试');
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 用户获取已投诉列表
     * @param $filter
     * @param array $orderBy
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getSalespersonComplaintsList($filter, $orderBy, $page, $pageSize)
    {
        try {
            $list = $this->salemanCustomerComplaints->lists($filter, '*', $page, $pageSize, $orderBy);
            return $list;
        } catch (\Exception $e) {
            throw new ResourceException($e);
        }
    }

    public function getSalespersonComplaintsDetail($filter)
    {
        if (!$filter['id']) {
            return ['data' => []];
        }

        try {
            $result = $this->salemanCustomerComplaints->getInfo($filter);
            return $result;
        } catch (\Exception $exception) {
            throw new ResourceException($exception);
        }
    }

    public function workWechatCallback($companyId, $respone)
    {
        $config = app('wechat.work.wechat')->getConfig($companyId);

        // 激活状态：1=激活或关注， 2=禁用， 4=未激活（重新启用未激活用户或者退出企业并且取消关注时触发）
        if ('update_user' == $respone['ChangeType'] && '2' == $respone['Status']) {
            $userInfo = Factory::work($config)->user->get($respone['UserID']);
            $mobileArr = [$userInfo['mobile'], $userInfo['telephone']];
            $salespersonInfo = $this->getSalespersonByMobileByType($mobileArr, 'shopping_guide');
            $this->updateSalesperson($companyId, $salespersonInfo['salesperson_id'], ['is_valid' => 'false']);
        } elseif ('update_user' == $respone['ChangeType'] && '1' == $respone['Status']) {
            $userInfo = Factory::work($config)->user->get($respone['UserID']);
            $mobileArr = [$userInfo['mobile'], $userInfo['telephone']];
            $salespersonInfo = $this->getSalespersonByMobileByType($mobileArr, 'shopping_guide');
            $this->updateSalesperson($companyId, $salespersonInfo['salesperson_id'], ['is_valid' => 'true']);
        } elseif ('delete_user' == $respone['ChangeType']) {
            $userInfo = Factory::work($config)->user->get($respone['UserID']);
            $mobileArr = [$userInfo['mobile'], $userInfo['telephone']];
            $salespersonInfo = $this->getSalespersonByMobileByType($mobileArr, 'shopping_guide');
            $this->updateSalesperson($companyId, $salespersonInfo['salesperson_id'], ['is_valid' => 'delete']);
        }
    }

    /**
     * Notes: 同步企业用户信息 到 导购员信息 【有则更新 ，无则新增】
     * Author:Michael-Ma
     * Date:  2020年06月08日 12:03:37
     *
     * @param  int  $companyId
     * @param  array  $userData
     *
     * @return array[]
     */
    public function syncUserToSalesperson(int $companyId = 1, array $userData = [])
    {
        $update_salesperson_reulst = $create_salesperson_result = [];
        $distributorService = new DistributorService();
        foreach ($userData as $v) {
            if ($v['mobile']) {
                $salespersonInfo = $this->salesperson->getInfo([
                    'mobile' => $v['mobile'],
                    'company_id' => $companyId,
                    // 'work_userid' => $value['userid'],
                ]);
                // 查询 企微 成员的所在的 主部门 是否已经 绑定店铺
                $distributoreInfo = $distributorService->entityRepository->getInfo([
                    'company_id' => $companyId,
                    'wechat_work_department_id' => $v['main_department'],
                ]);
                // 成员所属部门尚未绑定店铺，无法同步
                if (!$distributoreInfo) {
                    throw new ResourceException('企微成员' . $v['name'] . '所属主部门尚未绑定店铺，无法同步');
                }

                if ($salespersonInfo) { // 有导购信息  就更新掉
                    // 更新的内容：店铺ID、状态、
                    $update_salesperson_reulst[] = $this->updateSalesperson($companyId, $salespersonInfo['salesperson_id'], [
                        'distributor_id' => [$distributoreInfo['distributor_id']],
                        'is_valid' => $v['status'] == 2 ? 'delete' : 'true',
                        'name' => $v['name'],
                        'work_userid' => $v['userid'],
                        'salesperson_type' => 'shopping_guide',
                        'company_id' => $companyId,
                    ]);
                } else { // 无信息则新增 导购员
                    $create_salesperson_result[] = $this->createSalesperson([
                        'distributor_id' => [$distributoreInfo['distributor_id']],
                        'mobile' => $v['mobile'],
                        'name' => $v['name'],
                        'work_userid' => $v['userid'],
                        'company_id' => $companyId,
                        'is_valid' => $v['status'] == 2 ? 'delete' : 'true',
                        'salesperson_type' => 'shopping_guide',
                    ]);
                }
            }
        }
        return [
            'update_salesperson_reulst' => $update_salesperson_reulst,
            'create_salesperson_result' => $create_salesperson_result,
        ];
    }

    /**
     * Notes: 获取 企业微信通讯录信息
     * Author:Michael-Ma
     * Date:  2020年06月04日 11:12:33
     *
     * @param $companyId
     *
     * @return mixed
     */
    public function getWechatWorkInfo($companyId)
    {
        $config = app('wechat.work.wechat')->getConfig($companyId);
        $word = Factory::work($config);
        $departmentList = $word->department->list();
        // 更新店铺 & 新增店铺 & 更新导购员 & 新增导购员
        $update_distributore_result = $insert_distributore_result = $update_salesperson_reulst = $create_salesperson_result = $salespersonInfo = [];
        $departmentListInfo = $departmentList['department'] ?? [];
        $distributoreService = new DistributorService();
//        $userList     = $app->getUserList(1);
//        dd($departmentListInfo,$userList['userlist']);

        $userApp = $word->user;
        foreach ($departmentListInfo as $v) {
            // 企业部门信息 到 店铺
            $distributoreInfo = $distributoreService->entityRepository->getInfo([
                'wechat_work_department_id' => $v['id'],
                'company_id' => $companyId,
            ]);

            if ($distributoreInfo) { // 有数据更新
                $update_distributore_result[] = $distributoreService->updateDistributor($distributoreInfo['distributor_id'], [
                    'name' => $v['name'],
                    'company_id' => $companyId,
                ]);
            } else { // 无数据则新增
                $insert_distributore_result[] = $distributoreService->entityRepository->create([
                    'name' => $v['name'],// 店铺名称
                    'wechat_work_department_id' => $v['id'], // 企业微信部门ID
                    'is_ziti' => 'true', // 默认支持自提
                    'is_valid' => 'true', // 是否有效分销店铺
                    'company_id' => $companyId,
                    'mobile' => implode('-', [
                        $v['id'],
                        $v['parentid'],
                        $v['order'],
                    ]), // 店铺联系方式 -- 用 部门ID + 父级部门ID + 排序 拼接
                    /*'province'                  => '', // 店铺所在省市
                    'city'                      => '', // 店铺所在城市
                    'area'                      => '', // 店铺所在区域*/
                ]);
            }
            // 获取 店铺ID
            $distributor_ids = $distributoreInfo['distributor_id'] ?? $insert_distributore_result['distributor_id'] ?? 0;

            // 获取成员信息  -- 不要递归获取
            $userList = $userApp->getDetailedDepartmentUsers($v['id']);
            $userListInfo = $userList['userlist'] ?? [];

            if ($userListInfo) {
                foreach ($userListInfo as $value) { // 企业微信下有 成员
                    // 查询 导购员信息
                    if ($value['mobile']) {
                        $salespersonInfo = $this->salesperson->getInfo([
                            'mobile' => $value['mobile'],
                            'company_id' => $companyId,
                            //                        'work_userid' => $value['userid'],
                        ]);

                        if ($salespersonInfo) { // 有导购信息  就更新掉
                            // 更新的内容：店铺ID、状态、
                            $update_salesperson_reulst[] = $this->updateSalesperson($companyId, $salespersonInfo['salesperson_id'], [
                                'distributor_id' => [$distributor_ids],
                                'is_valid' => $value['status'] == 2 ? 'delete' : 'true',
                                'name' => $value['name'],
                                'work_userid' => $value['userid'],
                                'salesperson_type' => 'shopping_guide',
                                'company_id' => $companyId,
                            ]);
                        } else { // 无信息则新增 导购员
                            $create_salesperson_result[] = $this->createSalesperson([
                                'distributor_id' => [$distributor_ids],
                                'mobile' => $value['mobile'],
                                'name' => $value['name'],
                                'work_userid' => $value['userid'],
                                'company_id' => $companyId,
                                'is_valid' => $value['status'] == 2 ? 'delete' : 'true',
                                'salesperson_type' => 'shopping_guide',
                            ]);
                        }
                    }
                }
            }
            /*            } else { // 企业微信下 没有成员了
                // 如果 店铺存在，就清空掉  里面所有的导购员
                if ($distributor_ids) {
                    $salespersonRelInfo = $this->relSalesperson->getLists([
                        'shop_id' => $distributor_ids,
                        'company_id' => $companyId,
                    ]);
                    // 如果店铺存在，就去重 获取 这个店铺下的 所有导购员ID，并且清空里面的导购员
                    if ($salespersonRelInfo) {
                        $salesperson_ids = array_unique(array_column($departmentListInfo, 'salesperson_id'));
                        foreach ($salesperson_ids as $v) {
                            $delete_salesperson_result[] = $this->deleteSalesperson($companyId, $v);
                        }
                    }
                }*/ // 不删除 导购员
        }

        return [
            'departmentList' => $departmentList,
            'departmentListInfo' => $departmentListInfo,
            'update_distributore_result' => $update_distributore_result,
            'insert_distributore_result' => $insert_distributore_result,
            'update_salesperson_reulst' => $update_salesperson_reulst,
            'create_salesperson_result' => $create_salesperson_result,
        ];
    }

    public function __key($companyId, $type, $date)
    {
        $redisKey = "OrderPayStatistics:" . $type . ":" . $companyId . ":" . $date;
        return $redisKey;
    }

    //导购统计键值
    public function __salespersonKey($companyId, $type, $date, $salespersonId)
    {
        $redisKey = "OrderPaySalespersonStatistics:$type:$companyId:SalespersonId:$salespersonId:$date";
        return $redisKey;
    }


    /**
     * 拉新用户分润储键值
     *
     * @param string $companyId
     * @param string $salespersonId
     * @param string $date
     * @return string
     */
    public function getSalespersonKey($companyId, $salespersonId, $date)
    {
        $redisKey = "Member:Salesperson:" . $salespersonId . ":Company:" . $companyId . ":" . $date;
        return $redisKey;
    }

    /**
     * 拉新用户分润储键值
     *
     * @param string $companyId
     * @param string $salespersonId
     * @param string $date
     * @return void
     */
    public function getSalespersonCommissionKey($companyId, $salespersonId, $date)
    {
        $redisKey = "Member:Salesperson:Commission:" . $salespersonId . ":Company:" . $companyId . ":" . $date;
        return $redisKey;
    }

    /**
     * 推广用户分润储键值
     *
     * @param string $companyId
     * @param string $salespersonId
     * @param string $date
     * @return void
     */
    public function getSalespersonPopularizeKey($companyId, $salespersonId, $date)
    {
        $redisKey = "Member:Salesperson:Popularize:" . $salespersonId . ":Company:" . $companyId . ":" . $date;
        return $redisKey;
    }

    /**
     * 获取权限集合
     *
     * @return void
     */
    public function salespersonRole()
    {
        $role = [
            1 => [
                'key' => '1',
                'name' => '发货管理',
            ],
            2 => [
                'key' => '2',
                'name' => '导购数据',
            ],
            3 => [
                'key' => '3',
                'name' => '售后管理',
            ],
            4 => [
                'key' => '4',
                'name' => '订单操作记录',
            ],
        ];
        return $role;
    }

    /**
     * 导购店铺，是有有效，是否为绑定关系
     * @param sting $company_id       企业ID
     * @param  string $salesperson_id 导购ID
     * @param  string $distributor_id 店铺ID
     * @return array                 导购信息
     */
    public function checkDistributorIsValid($company_id, $salesperson_id, $distributor_id)
    {
        // 检查导购和店铺是否为绑定关系
        $filter = [
            'company_id' => $company_id,
            'salesperson_id' => $salesperson_id,
            'shop_id' => $distributor_id,
        ];
        $relSalespersonInfo = $this->relSalesperson->getInfo($filter);
        if (!$relSalespersonInfo) {
            return false;
        }
        // 检查店铺是否有效
        $distributorService = new DistributorService();
        $filter = [
            'company_id' => $company_id,
            'is_valid' => 'true',
            'distributor_id' => $distributor_id,
        ];
        $distributorInfo = $distributorService->getInfo($filter);
        if (!$distributorInfo) {
            return false;
        }
        return true;
    }

    /**
     * openapi创建导购员，格式化数据
     * @param  array $params 导购员参数
     * @return array         格式化后的数据
     */
    public function __formatSalesperson($params)
    {
        $data = [
            'company_id' => $params['company_id'],
            'name' => $params['name'],
            'salesperson_type' => $params['salesperson_type'],
            'employee_status' => $params['employee_status'],
            'salesperson_job' => $params['salesperson_job'],
            'avatar' => $params['avatar'],
            'mobile' => $params['mobile'],
            'work_userid' => $params['work_userid'],
            'is_valid' => $params['is_valid'],
            'work_qrcode_configid' => $params['work_qrcode_configid'],
        ];

        if ($params['shop_code'] ?? '') {
            // 根据shop_code获取distributor_id
            $distributorService = new DistributorService();
            $filter = [
                'company_id' => $params['company_id'],
                'shop_code' => $params['shop_code'],
                'is_valid' => 'true',
            ];
            // 根据shop_code查询distributor_id
            $distributorList = $distributorService->getDistributorEasylists($filter);
            if (!$distributorList['list']) {
                $msg = '店铺错误:'.json_encode($params['shop_code']).',work_userid:'.$params['work_userid'];
                app('log')->info('openapi createSalesperson error:'.$msg);
                throw new ResourceException("店铺code未查询到正在开启的店铺信息");
            }
            $data['distributor_id'] = array_column($distributorList['list'], 'distributor_id');
        }

        return $data;
    }

    /**
     * 更新导购的绑定店铺
     * @param  int $companyId     企业id
     * @param  int $salespersonId 导购id
     * @param  string $shop_code     店铺编号，多个以逗号间隔
     * @return bool
     */
    public function updateSalespersonStore($companyId, $salespersonId, $shop_code)
    {
        if ($shop_code == '0') {
            $this->relSalesperson->deleteBy($companyId, $salespersonId);
            return true;
        }

        $filter = [
            'company_id' => $companyId,
            'shop_code' => explode(',', $shop_code),
            'is_valid' => 'true',
        ];
        $distributorService = new DistributorService();
        $distributorList = $distributorService->getDistributorEasylists($filter);
        if (!$distributorList['list']) {
            throw new ResourceException("未查询到店铺信息");
        }
        $distributorIds = array_column($distributorList['list'], 'distributor_id');

        if ($distributorIds) {
            $this->relSalesperson->deleteBy($companyId, $salespersonId);
            foreach ((array)$distributorIds as $distributorId) {
                $data = [
                    'company_id' => $companyId,
                    'shop_id' => $distributorId,
                    'salesperson_id' => $salespersonId,
                    'store_type' => 'distributor',
                ];
                $this->relSalesperson->create($data);
            }
        }
        return true;
    }

    /**
     * 增加导购的会员数量
     * @param int $companyId 企业id
     * @param int $inviterId 推荐人的用户id
     * @param int $userId 用户id
     */
    public function increaseSalespersonMemberNum(int $companyId, int $inviterId, int $userId)
    {
        if ($inviterId < 0) {
            return;
        }
        $date = date('Ymd');
        $redisKey = "Member:" . $companyId . ":" . $date;
        app('redis')->sadd($redisKey, $userId);
        //是否为导购员拉新
        $salespersonInfo = $this->salesperson->getInfo([
            'user_id' => $inviterId,
            'company_id' => $companyId
        ]);
        if ($salespersonInfo) {
            $redisKey = $this->getSalespersonKey($companyId, $salespersonInfo['salesperson_id'], $date);
            app('redis')->sadd($redisKey, $userId);
        }
    }
}
