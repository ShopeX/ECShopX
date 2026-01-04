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

namespace CompanysBundle\Services;

use SalespersonBundle\Entities\SalespersonStatistics;
use CompanysBundle\Entities\Statistics;
use CompanysBundle\Entities\StoreStatistics;
use CompanysBundle\Jobs\SalespersonActiveArticleRecordStatisticsJob;
use CompanysBundle\Jobs\SalespersonCommissionRecordStatisticsJob;
use CompanysBundle\Jobs\SalespersonGiveCouponsRecordStatisticsJob;
use CompanysBundle\Jobs\SalespersonRecordStatisticsJob;
use SalespersonBundle\Services\SalespersonService;
use KaquanBundle\Entities\SalespersonGiveCoupons;
use OrdersBundle\Traits\GetOrderServiceTrait;
use CompanysBundle\Jobs\RecordStatisticsJob;
use CompanysBundle\Jobs\SalespersonPopularizeRecordStatisticsJob;

use GoodsBundle\Services\ItemStoreService;
use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Entities\SalespersonActiveArticleStatistics;
use PromotionsBundle\Services\ActiveArticlesService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use AftersalesBundle\Services\AftersalesService;
use DepositBundle\Services\Stats\Day;
use OrdersBundle\Services\RefundErrorLogsService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;

class CompanysStatisticsService
{
    use GetOrderServiceTrait;
    private $statisticsRepository;
    private $storeStatisticsRepository;

    public function __construct()
    {
        $this->statisticsRepository = app('registry')->getManager('default')->getRepository(Statistics::class);
        $this->storeStatisticsRepository = app('registry')->getManager('default')->getRepository(StoreStatistics::class);
    }

    /**
     * scheduleRecordStatistics 定时记录商城订单 会员相关统计数据.
     */
    public function scheduleRecordStatistics()
    {
        $job = (new RecordStatisticsJob())->onQueue('slow');
        $salesperson_job = (new SalespersonRecordStatisticsJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($salesperson_job);
    }

    /**
     * scheduleRecordStatistics 定时记录商城订单 会员相关统计数据.
     */
    public function scheduleActiveArticleRecordStatistics()
    {
        $job = (new RecordStatisticsJob())->onQueue('slow');
        $salesperson_job = (new SalespersonActiveArticleRecordStatisticsJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($salesperson_job);
    }

    /**
     * scheduleRecordStatistics 定时记录导购分润统计数据.
     */
    public function scheduleCommissionRecordStatistics()
    {
        $job = (new RecordStatisticsJob())->onQueue('slow');
        $salesperson_job = (new SalespersonCommissionRecordStatisticsJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($salesperson_job);
    }

    /**
     * scheduleRecordStatistics 定时记录导购推广分润统计数据.
     */
    public function schedulePopularizeRecordStatistics()
    {
        $job = (new RecordStatisticsJob())->onQueue('slow');
        $salesperson_job = (new SalespersonPopularizeRecordStatisticsJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($salesperson_job);
    }

    /**
     * scheduleGiveCouponsRecordStatistics 定时导购送券相关统计数据.
     */
    public function scheduleGiveCouponsRecordStatistics()
    {
        $job = (new RecordStatisticsJob())->onQueue('slow');
        $salesperson_job = (new SalespersonGiveCouponsRecordStatisticsJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($salesperson_job);
    }

    /**
     * recordStatistics 记录统计数据.
     *
     * @param  int  $companyId
     * @param  string   $statisticsType {normal, service}
     * @param  int   $date
     * @return boolean
     */
    public function recordStatistics($companyId, $statisticsType, $date)
    {
        if (!$date) {
            $date = date('Ymd', strtotime(date('Y-m-d')) - 24 * 3600);
        }
        $time = strtotime($date);
        $expireat = time() + 3 * 24 * 3600;
        //商城每日新增会员数
        $redisKey = "Member:".$companyId.":".$date;
        $newUserNum = app('redis')->scard($redisKey);
        app('redis')->expireat($redisKey, $expireat);
        if ($newUserNum) {
            $this->createData($companyId, $date, 'newAddMember', 'member', $newUserNum);
        }

        //商城每日新增vip会员数
        $vipRedisKey = "MemberCard:".$companyId.":vip:".$date;
        $vipUserNum = app('redis')->scard($vipRedisKey);
        app('redis')->expireat($vipRedisKey, $expireat);
        if ($vipUserNum) {
            $this->createData($companyId, $date, 'vipMember', 'member', $vipUserNum);
        }

        //商城每日新增svip会员数
        $svipRedisKey = "MemberCard:".$companyId.":svip:".$date;
        $svipUserNum = app('redis')->scard($svipRedisKey);
        app('redis')->expireat($svipRedisKey, $expireat);
        if ($svipUserNum) {
            $this->createData($companyId, $date, 'svipMember', 'member', $svipUserNum);
        }

        $payedMembersKey = $this->__key($companyId, $statisticsType, $date."_orderPayUser");
        //每天下单会员数(去重)
        $real_payed_members = app('redis')->scard($payedMembersKey);
        app('redis')->expireat($payedMembersKey, $expireat);
        if ($real_payed_members) {
            $this->createData($companyId, $date, 'orderPayUser', $statisticsType, $real_payed_members);
        }
        $orderPayRedisKey = $this->__key($companyId, $statisticsType, $date);
        $statisticsData = app('redis')->hgetall($orderPayRedisKey);
        app('redis')->expireat($orderPayRedisKey, $expireat);
        foreach ($statisticsData as $key => $value) {
            $title = '';
            $shopId = 0;
            $keyArr = array_filter(explode('_', $key));
            if (count($keyArr) == 1) {
                $title = reset($keyArr);
                $this->createData($companyId, $date, $title, $statisticsType, $value);
            } elseif (count($keyArr) == 2) {
                list($shopId, $title) = $keyArr;
                $this->createData($companyId, $date, $title, $statisticsType, $value, $shopId);

                $payedMembersKey = $this->__key($companyId, $statisticsType, $date."_".$shopId."_orderPayUser");
                $real_payed_members = app('redis')->scard($payedMembersKey);
                app('redis')->expireat($payedMembersKey, $expireat);
                if ($real_payed_members) {
                    $this->createData($companyId, $date, 'orderPayUser', $statisticsType, $real_payed_members, $shopId);
                }
            }
        }
        return true;
    }

    /**
     * recordSalespersonStatistics 记录统计数据.
     *
     * @param  int  $companyId
     * @param  string   $statisticsType {normal, service}
     * @param  int   $date
     * @return boolean
     */
    public function recordSalespersonStatistics($companyId, $salespersonId, $statisticsType, $date)
    {
        if (!$date) {
            $date = date('Ymd', strtotime(date('Y-m-d')) - 24 * 3600);
        }
        $time = strtotime($date);
        $expireat = time() + 3 * 24 * 3600;

        //商城每日新增会员数
        $salespersonService = new SalespersonService();
        $memberRedisKey = $salespersonService->getSalespersonKey($companyId, $salespersonId, $date);
        ;
        $newUserNum = app('redis')->scard($memberRedisKey);
        app('redis')->expireat($memberRedisKey, $expireat);
        if ($newUserNum) {
            $this->createData($companyId, $date, 'newAddMember', 'member', $newUserNum);
        }

//        $payedMembersKey = $this->__salespersonKey($companyId, $statisticsType, $date, $salespersonId);
//        //每天下单会员数(去重)
//        $real_payed_members = app('redis')->scard($payedMembersKey);
//        app('redis')->expireat($payedMembersKey, $expireat);
//        if ($real_payed_members) {
//            $this->createData($companyId, $date, 'orderPayUser', $statisticsType, $real_payed_members);
//        }

        $orderPayRedisKey = $this->__salespersonKey($companyId, $statisticsType, $date, $salespersonId);
        $statisticsData = app('redis')->hgetall($orderPayRedisKey);
        app('redis')->expireat($orderPayRedisKey, $expireat);

        //销售额
        $orderPayFee = $statisticsData[$salespersonId."_salesperson_orderPayFee"] ?? 0;
        $title = 'orderPayFee';
        $this->createData($companyId, $date, $title, $statisticsType, $orderPayFee, null, $salespersonId);

        //订单数
        $orderPayNum = $statisticsData[$salespersonId."_salesperson_orderPayNum"] ?? 0;
        $title = 'orderPayNum';
        $this->createData($companyId, $date, $title, $statisticsType, $orderPayNum, null, $salespersonId);
        return true;
    }

    public function recordActiveArticleStatistics($companyId, $salespersonId, $date)
    {
        //活动转发数
        $activeArticleService = new ActiveArticlesService();
        $articleKey = $activeArticleService->_getKey($companyId, $salespersonId);
        $activeArticleNum = app('redis')->hvals($articleKey);
        $activeArticleSum = array_sum($activeArticleNum);
        $this->createData($companyId, $date, '', '', $activeArticleSum, null, $salespersonId, true);
        return true;
    }

    /**
     *  统计导购分润信息
     * @param $companyId
     * @param $salespersonId
     * @param $date
     * @return bool
     */
    public function recordSalespersonCommissionStatistics($companyId, $salespersonId, $date)
    {
        // 拉新分润统计
        $salespersonService = new SalespersonService();
        $redisCommissionKey = $salespersonService->getSalespersonCommissionKey($companyId, $salespersonId, $date);
        $newGuestDivided = (int)app('redis')->get($redisCommissionKey);
        $this->createData($companyId, $date, 'newGuestDivided', 'member', $newGuestDivided, null, $salespersonId);
        return true;
    }

    /**
     *  统计导购分润信息
     * @param $companyId
     * @param $salespersonId
     * @param $date
     * @return bool
     */
    public function recordSalespersonPopularizeStatistics($companyId, $salespersonId, $date)
    {
        // 推广分润统计
        $salespersonService = new SalespersonService();
        $redisPopularizeKey = $salespersonService->getSalespersonPopularizeKey($companyId, $salespersonId, $date);
        $salesCommission = (int)app('redis')->get($redisPopularizeKey);
        $this->createData($companyId, $date, 'salesCommission', 'member', $salesCommission, null, $salespersonId);
        return true;
    }

    /**
     * 导购员发放优惠券统计
     * @param $companyId
     * @param $salespersonId
     * @param $date
     * @return bool
     */
    public function recordSalespersonGiveCouponsStatistics($companyId, $salespersonId, $date)
    {
        // 导购员发放优惠券统计
        $filter = [
            'salesperson_id' => $salespersonId,
            'company_id' => $companyId,
            'give_time|gte' => strtotime($date),
            'give_time|lt' => strtotime($date) + 24 * 3600,
            'status' => 1,
        ];
        $salespersonGiveCoupons = app('registry')->getManager('default')->getRepository(SalespersonGiveCoupons::class);
        $sendCouponsNum = $salespersonGiveCoupons->count($filter);
        $this->createData($companyId, $date, 'salespersonGiveCoupons', 'member', $sendCouponsNum, null, $salespersonId);
        return true;
    }

    /**
     * createData 添加至数据表中.
     *
     * @param  int  $companyId
     * @param  int  $date
     * @param  string  $title 具体统计描述
     * @param  string  $type {normal, service, member}
     * @param  int  $value 统计值
     * @param  string  $shopId
     * @return boolean
     */
    public function createData($companyId, $date, $title, $type, $value, $shopId = null, $salesperson_id = null, $activeArticle = false)
    {
        $params = [
            'company_id' => $companyId,
            'statistic_title' => $title,
            'statistic_type' => $type,
            'add_date' => $date,
        ];
        if ($shopId) {
            $params['shop_id'] = $shopId;
            $recordService = app('registry')->getManager('default')->getRepository(StoreStatistics::class);
        } elseif ($salesperson_id && !$activeArticle) {
            $params['salesperson_id'] = $salesperson_id;
            $recordService = app('registry')->getManager('default')->getRepository(SalespersonStatistics::class);
        } elseif ($salesperson_id && $activeArticle) {
            unset($params['statistic_title'], $params['statistic_type']);
            $params['salesperson_id'] = $salesperson_id;
            $recordService = app('registry')->getManager('default')->getRepository(SalespersonActiveArticleStatistics::class);
        } else {
            $recordService = app('registry')->getManager('default')->getRepository(Statistics::class);
        }
        $info = $recordService->getInfo($params);
        if ($info) {
            return $info;
        }
        $params['data_value'] = $value;
        $result = $recordService->create($params);
        return $result;
    }

    private function __key($companyId, $type, $date)
    {
        return "OrderPayStatistics:".$type.":".$companyId.":".$date;
    }

    //导购统计键值
    private function __salespersonKey($companyId, $type, $date, $salespersonId)
    {
        return "OrderPaySalespersonStatistics:$type:$companyId:SalespersonId:$salespersonId:$date";
    }

    public function getStatistics($companyId, $date = null, $shopId = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }

        $arrType = ['normal', 'service'];
        $returnData = [];
        foreach ($arrType as $statisticsType) {
            $orderPayRedisKey = $this->__key($companyId, $statisticsType, $date);
            if ($shopId !== null) {
                $result['real_payed_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderPayFee');
                $result['real_payed_orders'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderPayNum');
                $result['real_refunded_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderRefundFee');
                $result['real_aftersale_count'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderAftersales');
                $result['real_payed_members'][$statisticsType] = (int)app('redis')->scard($orderPayRedisKey."_".$shopId."_orderPayUser");
            } else {
                $result['real_payed_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderPayFee');
                $result['real_payed_orders'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderPayNum');
                $result['real_refunded_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderRefundFee');
                $result['real_aftersale_count'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderAftersales');
                $result['real_payed_members'][$statisticsType] = (int)app('redis')->scard($orderPayRedisKey."_orderPayUser");
            }
        }
        foreach ($result as $key => $arr) {
            $resultData[$key] = array_sum($arr);
        }
        $depositDayService = new Day();
        $resultData['real_deposit'] = (int)$depositDayService->getRechargeTotal($companyId, date('Y-m-d', strtotime($date)));
        $resultData['real_atv'] = 0;
        if ($resultData['real_payed_members'] > 0) {
            $resultData['real_atv'] = (int)bcdiv($resultData['real_payed_fee'], $resultData['real_payed_members']) ?: 0;
        }
        return $resultData;
    }

    public function getMerchantStatistics($companyId, $merchantId, $date = null, $shopId = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }

        $arrType = ['normal', 'service'];
        $returnData = [];
        foreach ($arrType as $statisticsType) {
            $orderPayRedisKey = $this->__key($companyId, $statisticsType, $date);
            if ($shopId !== null) {
                $result['real_payed_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderPayFee');
                $result['real_payed_orders'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderPayNum');
                $result['real_refunded_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderRefundFee');
                $result['real_aftersale_count'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $shopId.'_orderAftersales');
                $result['real_payed_members'][$statisticsType] = (int)app('redis')->scard($orderPayRedisKey."_".$shopId."_orderPayUser");
            } elseif (!empty($merchantId)) {
                $result['real_payed_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $merchantId.'_merchant_orderPayFee');
                $result['real_payed_orders'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $merchantId.'_merchant_orderPayNum');
                $result['real_refunded_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $merchantId.'_merchant_orderRefundFee');
                $result['real_aftersale_count'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, $merchantId.'_merchant_orderAftersales');
                $result['real_payed_members'][$statisticsType] = (int)app('redis')->scard($orderPayRedisKey."_".$merchantId."_merchant_orderPayUser");
            } else {
                $result['real_payed_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderPayFee');
                $result['real_payed_orders'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderPayNum');
                $result['real_refunded_fee'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderRefundFee');
                $result['real_aftersale_count'][$statisticsType] = (int)app('redis')->hget($orderPayRedisKey, 'orderAftersales');
                $result['real_payed_members'][$statisticsType] = (int)app('redis')->scard($orderPayRedisKey."_orderPayUser");
            }
        }
        foreach ($result as $key => $arr) {
            $resultData[$key] = array_sum($arr);
        }
        $resultData['real_atv'] = 0;
        if ($resultData['real_payed_members'] > 0) {
            $resultData['real_atv'] = (int)bcdiv($resultData['real_payed_fee'], $resultData['real_payed_members']) ?: 0;
        }
        return $resultData;
    }

    public function getMemberStatistics($companyId, $date = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }
        $yesterday = date('Ymd', strtotime($date) - 24 * 3600);
        $sixDayBefore = date('Ymd', strtotime($date) - 6 * 24 * 3600);
        $filter = [
            'add_date|gte' => $sixDayBefore,
            'add_date|lte' => $yesterday,
            'statistic_type' => 'member',
            'company_id' => $companyId,
        ];
        $statisticsRepository = app('registry')->getManager('default')->getRepository(Statistics::class);

        $memberdata = $statisticsRepository->lists($filter)['list'];
        foreach ($memberdata as $value) {
            $everyDayData[$value['add_date']][] = $value;
        }

        $result = [];
        $newData = $sixDayBefore;
        for ($i = 0; $i < 6; $i++) {
            $result[$newData] = [
                'newAddMember' => 0,
                'vipMember' => 0,
                'svipMember' => 0,
            ];
            $newData = date('Ymd', strtotime($newData) + 24 * 3600);
        }
        if ($everyDayData ?? []) {
            foreach ($everyDayData as $key => $value) {
                $newVal = array_column($value, 'data_value', 'statistic_title');
                $result[$key]['newAddMember'] = $newVal['newAddMember'] ?? 0;
                $result[$key]['vipMember'] = $newVal['vipMember'] ?? 0;
                $result[$key]['svipMember'] = $newVal['svipMember'] ?? 0;
            }
        }
        //商城每日新增会员数
        $result[$date]['newAddMember'] = app('redis')->scard("Member:".$companyId.":".$date);
        //商城每日新增vip会员数
        $result[$date]['vipMember'] = app('redis')->scard("MemberCard:".$companyId.":vip:".$date);
        //商城每日新增svip会员数
        $result[$date]['svipMember'] = app('redis')->scard("MemberCard:".$companyId.":svip:".$date);
        return $result;
    }

    public function getNoticeStatisticsData($companyId, $distributorId = 0, $beginTime = null, $endTime = null)
    {
        //统计订单待发货数量
        $filter = [
            'company_id' => $companyId,
            'order_type' => 'normal',
            'order_status' => 'PAYED',
            #'ziti_status' => 'NOTZITI',
            "receipt_type" => "logistics",
            'order_class|notin' => ["drug","pointsmall"],
            'cancel_status|in' => ['NO_APPLY_CANCEL', 'FAILS']
        ];
        if ($distributorId) {
            $filter['distributor_id'] = $distributorId;
        }
        $orderService = $this->getOrderService('normal');
        $result['wait_delivery_count'] = $orderService->countOrderNum($filter);

        //统计商品库存预警商品数量
        $itemStoreService = new ItemStoreService();
        $warningStore = $itemStoreService->getWarningStore($companyId, $distributorId);
        $filter = [
            'company_id' => $companyId,
            'type' => 0,
            'item_type' => 'normal',
            'is_default' => 1,
            'store|lte' => $warningStore,
            'distributor_id' => $distributorId,
        ];
        $itemsService = new ItemsService();
        $result['warning_goods_count'] = $itemsService->getItemCount($filter);

        $filter['supplier_item_id'] = 0;
        $result['warning_goods_count_plat'] = $itemsService->getItemCount($filter);
        $result['warning_goods_count'] = $result['warning_goods_count_plat'] .' (自营) / '.  $result['warning_goods_count'] .' (全部)  ';
        //统计进行中的秒杀活动数量
        $filter = [
            'company_id' => $companyId,
            'activity_start_time|lte' => time(),
            'activity_end_time|gt' => time(),
            'seckill_type' => 'normal',
            'disabled' => 0,
        ];

        $seckillService = new PromotionSeckillActivityService();
        $result['started_seckill_count'] = $seckillService->count($filter);

        //统计进行中的拼团活动数量
        $filter = [
            'company_id' => $companyId,
            'begin_time|lte' => time(),
            'end_time|gte' => time(),
            'disabled' => false,
        ];
        $groupService = new PromotionGroupsActivityService();
        $result['started_gtoups_count'] = $groupService->count($filter);

        //统计售后待处理数据
        $filter = [
            'company_id' => $companyId,
            'aftersales_status' => [0],
        ];
        $aftersalesService = new AftersalesService();
        $result['aftersales_count'] = $aftersalesService->countAftersalesNum($filter);


        //统计退款失败待处理数量
        $filter = [
            'company_id' => $companyId,
            'is_resubmit' => 0,
        ];
        $refundErrorLogsService = new RefundErrorLogsService();
        $result['refund_errorlogs_count'] = $refundErrorLogsService->errorLogsNum($filter);
        return $result;
    }

    ## 商户统计
    public function getMerchantNoticeStatisticsData($companyId, $merchantId = 0, $distributorId = 0, $beginTime = null, $endTime = null)
    {
        //统计订单待发货数量
        $filter = [
            'company_id' => $companyId,
            'order_type' => 'normal',
            'order_status' => 'PAYED',
            'ziti_status' => 'NOTZITI',
            'order_class|notin' => ["drug","pointsmall"],
            'cancel_status|in' => ['NO_APPLY_CANCEL', 'FAILS'],
        ];
        if ($distributorId) {
            $filter['distributor_id'] = $distributorId;
        }
        if ($merchantId) {
            $filter['merchant_id'] = $merchantId;
        }
        $orderService = $this->getOrderService('normal');
        $result['wait_delivery_count'] = $orderService->countOrderNum($filter);

        //统计售后待处理数据
        $filter = [
            'company_id' => $companyId,
            'aftersales_status' => [0],
            'merchant_id' => $merchantId
        ];
        $aftersalesService = new AftersalesService();
        $result['aftersales_count'] = $aftersalesService->countAftersalesNum($filter);


        //统计退款失败待处理数量
        $filter = [
            'company_id' => $companyId,
            'is_resubmit' => 0,
            'merchant_id' => $merchantId
        ];
        $refundErrorLogsService = new RefundErrorLogsService();
        $result['refund_errorlogs_count'] = $refundErrorLogsService->errorLogsNum($filter);
        return $result;
    }
}
