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

namespace PopularizeBundle\Services;

use AftersalesBundle\Entities\AftersalesDetail;
use CompanysBundle\Traits\GetDefaultCur;
use DepositBundle\Services\DepositTrade;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use OrdersBundle\Traits\OrderSettingTrait;
use PointBundle\Services\PointMemberRuleService;
use MembersBundle\Services\MemberService;

use PopularizeBundle\Entities\Brokerage;
use MembersBundle\Services\WechatUserService;
use GoodsBundle\Services\ItemsService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;

/**
 * 分销基础配置
 */
class BrokerageService
{
    use GetOrderServiceTrait;
    use GetDefaultCur;
    use GetPaymentServiceTrait;
    use OrderSettingTrait;

    /**
     * 当前返佣的订单信息
     */
    public $orderInfo = [];
    public $order = [];
    public $rebateGoods = [];

    public $plan_close_time;
    public $ratioType;
    public $rebateGoodsMode;
    public $itemsRebateConf;
    /**
     * 返佣类型
     * @var string
     */
    public $commissionType = 'money';

    /**
     * 返佣基础金额
     */
    public $itemTotalFee = 0;

    public $brokerageRepository;
    public $aftersalesDetailRepository;

    public function __construct()
    {
        $this->brokerageRepository = app('registry')->getManager('default')->getRepository(Brokerage::class);
        $this->aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
    }

    /**
     * 插入订单返佣
     */
    public function insertOrderBrokerage($order, $payFee)
    {

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $this->orderInfo = $orderService->getOrderInfo($order['company_id'], $order['order_id']);

        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': this->orderInfo :' . json_encode($this->orderInfo) );

        $this->order = $order;

        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': this->order :' . json_encode($this->order) );
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': this->order inputt:' . json_encode($order) );

        if (isset($this->orderInfo['orderInfo']['freight_fee'])) {
            $rate = round(floatval($this->orderInfo['orderInfo']['fee_rate']), 4);
            $freightFee = $this->orderInfo['orderInfo']['freight_fee'];
            if ($rate != 1 && $freightFee) {
                $freightFee = round($freightFee * $rate);
            }
            $this->itemTotalFee = $payFee - $freightFee;
        } else {
            $this->itemTotalFee = $payFee;
        }

        $config = (new SettingService())->getConfig($order['company_id']);
        $this->setCommissionTypeByConfig($config);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': this->order :start' . json_encode($this->order) );

            // 推广员层级佣金（每单结算模式计算） //只到第三级
            $levelBrokerage = $this->promoterLevelBrokerage();

            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': this->order :end' . json_encode($this->order) );
            app('log')->debug(':export brokerage:订单返佣推广员佣金层级：'. json_encode($levelBrokerage));

            // 如果返回null 者表示不往下处理了
            if ($levelBrokerage === null) {
                $conn->commit();
                return true;
            }

            $promoterCountService = new PromoterCountService();
            if ($levelBrokerage) {
                foreach ($levelBrokerage as $row) {
                    $this->brokerageRepository->create($row);
                    // 只有直属上级才需要统计营业额
                    // $itemPrice = ($row['brokerage_type'] == 'first_level') ? $row['price'] : 0;
                    // 上级和上上级也统计营业额
                    $itemPrice = $row['price'];
                    if ('money' == $this->commissionType) {
                        $promoterCountService->addPopularize($row['company_id'], $row['user_id'], $itemPrice, $row['rebate']);
                    } else {
                        $promoterCountService->updatePromoterOrderPoint($row['company_id'], $row['user_id'], $itemPrice, $row['rebate_point']);
                    }
                    if ($row['brokerage_type'] == 'first_level') {
                        // 如果有下线购买者进行升级判断
                        $promoterGradeService = new PromoterGradeService();
                        $promoterGradeService->upgradeGrade($row['company_id'], $row['user_id']);
                    }
                }
            }

            $gradeBrokerage = $this->promoterGradeBrokerage();
            if ($gradeBrokerage) {
                foreach ($gradeBrokerage as $data) {
                    $this->brokerageRepository->create($data);
                    if ('money' == $this->commissionType) {
                        $promoterCountService->addPopularize($data['company_id'], $data['user_id'], 0, $data['rebate']);
                    } else {
                        $promoterCountService->updatePromoterOrderPoint($data['company_id'], $data['user_id'], 0, $data['rebate_point']);
                    }
                }
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        }

        $promoterService = new PromoterService();
        $promoterService->updateByUserId($this->orderInfo['orderInfo']['user_id'], ['is_buy' => 1]);

        return true;
    }

    /**
     * 推广员等级返佣 function
     *
     * @return mixed
     */
    protected function promoterGradeBrokerage()
    {
        $orderId = $this->orderInfo['orderInfo']['order_id'];
        $companyId = $this->orderInfo['orderInfo']['company_id'];
        $userId = $this->orderInfo['orderInfo']['user_id'];

        $promoterGradeService = new PromoterGradeService();
        $isOpen = $promoterGradeService->getOpenPromoterGrade($companyId);
        app('log')->debug(':export brokerage:是否开启推广员等级' . $isOpen);
        // 当前商家未开启推广员等级返佣
        if ($isOpen == 'false') {
            return null;
        }

        $promoterService = new PromoterService();
        $promoterInfo = $promoterService->getInfoByUserId($userId);
        app('log')->debug(':export brokerage:当前购买人的信息' . var_export($promoterInfo, true));
        if (!isset($promoterInfo['grade_level']) || $promoterInfo['grade_level'] === 3) {
            return null;
        }

        // 获取上级团队
        $filter = ['user_id' => $promoterInfo['user_id'], 'grade_level|gt' => intval($promoterInfo['grade_level']), 'disabled' => 0]; //这些写法都是默认只有两级的分销(也就是顶级只到下下级为止)
        $promoterList = $promoterService->getRelationParentBy($filter, null);
        // $promoterList = $promoterService->where('grade_level', '>', intval($promoterInfo['grade_level']))->where('disabled', '=', 0)->getRelationParentByUserId(intval($promoterInfo['user_id']), 1, null); //只取直属上级(grade_level必须二级起步)
        app('log')->debug(':export brokerage:上级团队' . var_export($promoterList, true));
        if (!$promoterList['total_count']) {
            return null;
        }

        foreach ($promoterGradeService->promoterGradeDefault as $key => $value) {
            $promoterGrade[$value['grade_level']] = $key;
        }

        $promoterGradeConfig = $promoterGradeService->getPromoterGradeConfig($companyId);
        $brokerageItem = [
            'order_id' => $orderId,
            'source' => 'order_team',
            'buy_user_id' => $userId,
            'order_type' => $this->orderInfo['orderInfo']['order_type'],
            'company_id' => $companyId,
            'commission_type' => $this->commissionType,
            'plan_close_time' => $this->plan_close_time,
            'is_close' => false,
        ];

        // 如果上级团队为三级团队
        if ($promoterList['list'][0]['grade_level'] == 3) {
            $brokerageItem['brokerage_type'] = $promoterGrade[3];
            // 如果当前推广员为1级
            if ($promoterInfo['grade_level'] == 1) {
                $ratio = $promoterGradeConfig['grade'][$promoterGrade[3]]['first_ratio'];
            } else {
                $ratio = $promoterGradeConfig['grade'][$promoterGrade[3]]['second_ratio'];
            }
        } else {
            $brokerageItem['brokerage_type'] = $promoterGrade[2];
            $ratio = $promoterGradeConfig['grade'][$promoterGrade[2]]['first_ratio'];
        }

        $pointMemberRuleService = new PointMemberRuleService($companyId);
        $popularizeRatesData = $this->popularizeRates($ratio);
        $temp = $brokerageItem;
        $temp['user_id'] = $promoterList['list'][0]['user_id'];
        $temp['price'] = $this->itemTotalFee;
        $temp['rebate'] = $popularizeRatesData['rebate'];
        $temp['detail'] = $popularizeRatesData['detail'];
        $temp["rebate_point"] = $pointMemberRuleService->moneyToPointSend($temp['rebate']);
        app('log')->debug(':export brokerage:上级团队数据' . var_export($temp, true));

        $results[] = $temp;

        if ($promoterList['list'][0]['grade_level'] != 3) {
            // 获取上级团队
            // $thirdPromoterList = $promoterService->where('grade_level', '=', 3)->where('disabled', '=', 0)->getRelationParentByUserId(intval($promoterInfo['user_id']), 1, null, 0, 1);
            $filter = ['user_id' => $promoterInfo['user_id'], 'grade_level' => 3, 'disabled' => 0];
            $thirdPromoterList = $promoterService->getRelationParentBy($filter, null);
            app('log')->debug(':export brokerage:上上级团队' . var_export($thirdPromoterList, true));
            if ($thirdPromoterList['total_count'] > 0) {
                $ratio = $promoterGradeConfig['grade'][$promoterGrade[3]]['second_ratio'];
                $popularizeRatesData = $this->popularizeRates($ratio);
                $brokerageItem['brokerage_type'] = $promoterGrade[3];
                $thirdTemp = $brokerageItem;
                $thirdTemp['user_id'] = $thirdPromoterList['list'][0]['user_id'];
                $thirdTemp['price'] = $this->itemTotalFee;
                $thirdTemp['rebate'] = $popularizeRatesData['rebate'];
                $thirdTemp['detail'] = $popularizeRatesData['detail'];
                $thirdTemp["rebate_point"] = $pointMemberRuleService->moneyToPointSend($thirdTemp['rebate']);
                app('log')->debug(':export brokerage:上上级团队数据' . var_export($thirdTemp, true));

                $results[] = $thirdTemp;
            }
        }

        return $results;
    }

    /**
     * 推广员层级佣金 function
     *
     * @return mixed
     */
    protected function promoterLevelBrokerage()
    {
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': this->order start1111:' . json_encode($this->order) );

        $orderId = $this->orderInfo['orderInfo']['order_id'];
        $companyId = $this->orderInfo['orderInfo']['company_id'];
        $userId = $this->orderInfo['orderInfo']['user_id'];
        $promoterUserId = $this->order['promoter_user_id'];
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': orderInfo :' . json_encode($this->orderInfo) ) ;
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': order :' . json_encode($this->order) ) ;
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': order:promoteruserid :' . json_encode($promoterUserId) ) ;

        $settingService = new SettingService();
        $isOpen = $settingService->getOpenPopularize($companyId);
        app('log')->debug(':export brokerage:是否开启推广员返佣' . $isOpen);
        // 当前商家未开启推广员返佣
        if ($isOpen == 'false') {
            return null;
        }
        // 支付返佣层级
        $supportPromoterLevel = $settingService->supportPromoterLevel;
        $promoterList = $this->getParentPromoterList($userId, count($supportPromoterLevel));
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': order:promoterList:::::promoterList: :' . json_encode($promoterList) ) ;

        $promoterList = array_column($promoterList, null, 'relationship_depth');

        if($promoterUserId){
            $promoterList = $this->getsalesPersonPromoterList($promoterUserId);
            $promoterList = array_column($promoterList, null, 'relationship_depth');
            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': order:promoterList:::::promoterList:yewuyuan:' .$promoterUserId. json_encode($promoterList) ) ;

        }

        if (empty($promoterList)) {
            return null; 
        }

        // 获取推广员层级返佣比例
        $config = $settingService->getConfig($companyId);
        $popularizeRatio = $config['popularize_ratio'];
        $settleTime = $config['limit_time'] ?: 0;

        $ordersSetting = $this->getOrdersSetting($companyId);

        app('log')->debug(':export brokerage:分佣结算时间,订单状态:'. $this->orderInfo['orderInfo']['order_status']);
        // 如果订单创建则已完成，那么就根据的结算周期进行结算
        if ($this->orderInfo['orderInfo']['order_status'] == 'DONE') {
            $latestAftersaleTime = $ordersSetting['latest_aftersale_time'] ?? 0;
            $this->plan_close_time = time() + 3600 * 24 * ($settleTime + $latestAftersaleTime);
        } else {
            $this->plan_close_time = time() + 3600 * 24 * 1000;
        }

//        $latestAftersaleTime = $ordersSetting['latest_aftersale_time'] ?? 0;
//        $this->plan_close_time = time() + 3600 * 24 * ($settleTime + $latestAftersaleTime);

        $this->ratioType = $popularizeRatio['type'];
        // 是否所有的商品都可以参加返佣
        $this->rebateGoodsMode = $config['goods'];
        $this->commissionType = $config['commission_type'];

        $this->itemsRebateConf = $this->getItemsRebateConf();

        $results = array();
        foreach ($supportPromoterLevel as $name => $row) {
            $promoterInfo = isset($promoterList[$row['level']]) ? $promoterList[$row['level']] : null;
            // 不存在对应的推广员，或者已被禁用
            if (!$promoterInfo || $promoterInfo['disabled']) {
                continue;
            }
            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': promoterInfo :' . json_encode($promoterInfo));

            if($this->orderInfo['orderInfo']['distributor_id'] > 0 && !$this->checkSalespersonShop($promoterInfo['user_id'])){
                continue;
            }
            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': promoterInfo-userid OK :' . $promoterInfo['user_id']);

            //通用返佣比例
            $ratio = $config['popularize_ratio'][$this->ratioType][$name]['ratio'];

            $popularizeRatesData = $this->popularizeRates($ratio, $name);

            $brokerageItem = [
                'brokerage_type' => $name,
                'order_id' => $orderId,
                'buy_user_id' => $userId,
                'source' => 'order',
                'order_type' => $this->orderInfo['orderInfo']['order_type'],
                'company_id' => $companyId,
                'plan_close_time' => $this->plan_close_time,
                'commission_type' => $this->commissionType,
                'is_close' => false,
                'user_id' => $promoterInfo['user_id'],
                'price' => $this->itemTotalFee,
                'rebate' => $popularizeRatesData['rebate'],
                'detail' => $popularizeRatesData['detail'],
            ];

            $pointMemberRuleService = new PointMemberRuleService($companyId);
            $brokerageItem["rebate_point"] = $pointMemberRuleService->moneyToPointSend($popularizeRatesData["rebate"]);
            $results[] = $brokerageItem;
        }
        return $results;
    }
    public function checkSalespersonShop($user_id_promoter){

        $salespersonService = new SalespersonService();
        $filter['company_id'] = $this->orderInfo['orderInfo']['company_id'];
        $filter['shop_id'] = $this->orderInfo['orderInfo']['distributor_id'];
        $filter['user_id']    = $user_id_promoter;
        $filter['is_valid']    = 'true';
        
        $listdata = $salespersonService->getSalespersonList($filter, 1, 1000);
        $dIds = array_column($listdata['list'],'shop_id');
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': filter :' . json_encode($filter));
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': dIds :' . json_encode($dIds));

        if(empty($dIds)){
            return false;
        }
        app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.': dIds :' . json_encode($dIds));
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoSimple(['distributor_id' => $this->orderInfo['orderInfo']['distributor_id'] ]);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":distributorInfo:". json_encode($distributorInfo));
        
        if(!$distributorInfo['is_open_salesman']){
            return false;
            // throw new ResourceException('店铺未开启业务员配置！');
        }

        return in_array($this->orderInfo['orderInfo']['distributor_id'],$dIds );

    }
    /**
     * 获取单品分销配置
     */
    private function getItemsRebateConf()
    {
        $orderInfo = $this->orderInfo['orderInfo'];
        $itemsRebateConf = [];
        if (isset($orderInfo['items'])) {
            $itemIds = array_column($orderInfo['items'], 'item_id');
            $itemsService = new ItemsService();
            $itemList = $itemsService->list(['item_id' => $itemIds]);
            if ($itemList['total_count'] === 0) {
                return [];
            }

            $defaultItemList = $itemsService->list(['item_id' => array_column($itemList['list'], 'default_item_id')]);
            $defaultItemsRebate = array_column($defaultItemList['list'], 'rebate', 'default_item_id');

            foreach ($itemList['list'] as $row) {
                // 只有参加返佣的商品需要获取配置
                // 店铺自有商品必须加入到商品分销池才可以进行分销
                app('log')->debug('分佣商品装载:' . $this->rebateGoodsMode . 'distributor_id:' . $row['distributor_id'] . 'rebate:' . $row['rebate']);
                if (($this->rebateGoodsMode == 'all' && !$row['distributor_id']) || ($defaultItemsRebate[$row['default_item_id']] ?? 0)) {
                    $this->rebateGoods[] = $row['item_id'];
                } else {
                    continue;
                }

                $rebateConf = $row['rebate_conf'];
                if (isset($rebateConf['type']) && $rebateConf['type'] == 'money') {
                    $itemsRebateConf[$row['item_id']]['rebate'] = $rebateConf['value'];
                }

                if (isset($rebateConf['type']) && $rebateConf['type'] == 'ratio' && $this->ratioType == $rebateConf['ratio_type']) {
                    $itemsRebateConf[$row['item_id']]['ratio'] = $rebateConf['value'];
                }
            }
        }
        return $itemsRebateConf;
    }

    /**
     * 按利润计算返佣
     *
     * @param int $ratio 计算比例
     * @param int $levelName 层级返佣，层级名称
     */
    private function popularizeRates($ratio, $levelName = null)
    {
        $orderInfo = $this->orderInfo['orderInfo'];
        $rate = round(floatval($orderInfo['fee_rate']), 4);
        $orderInfo['cost_fee'] = round(bcmul($orderInfo['cost_fee'], $rate));
        $detail = [
            'ratio_type' => $this->ratioType,
            'ratio' => $ratio, // 通用返佣比例
            'total_fee' => $this->itemTotalFee,
            'cost_fee' => $orderInfo['cost_fee'],
        ];
        app('log')->debug('分佣计算时分佣商品:'. var_export($this->rebateGoods, 1));

        app('log')->debug('分佣计算时分佣商品:'. __FUNCTION__.':'.__LINE__.json_encode($ratio) );

        if (isset($orderInfo['items'])) {
            $results['rebate'] = 0;
            foreach ($orderInfo['items'] as $row) {
                // 如果商品不参加返佣
                if (!in_array($row['item_id'], $this->rebateGoods)) {
                    continue;
                }

                $row['cost_fee'] = round(bcmul(round(floatval($row['fee_rate']), 4), $row['cost_fee']));
                $row['total_fee'] = round(bcmul(round(floatval($row['fee_rate']), 4), $row['total_fee']));

                app('log')->debug('分佣计算时分佣商品:'. __FUNCTION__.':'.__LINE__.json_encode($this->itemsRebateConf) );
                app('log')->debug('分佣计算时分佣商品:'. __FUNCTION__.':'.__LINE__.json_encode($ratio) );
                $itemRebate = '';
                $itemRatio = '';
                if ($levelName && $this->itemsRebateConf && isset($this->itemsRebateConf[$row['item_id']])) {
                    if (isset($this->itemsRebateConf[$row['item_id']]['rebate'])
                    && isset($this->itemsRebateConf[$row['item_id']]['rebate'][$levelName])
                    ) {
                        $itemRebate = $this->itemsRebateConf[$row['item_id']]['rebate'][$levelName];
                    } else {
                        if (isset($this->itemsRebateConf[$row['item_id']]['ratio']) 
                        && isset($this->itemsRebateConf[$row['item_id']]['ratio'][$levelName] )
                        ) {
                            $itemRatio = $this->itemsRebateConf[$row['item_id']]['ratio'][$levelName];
                        }

                    }
                }
                app('log')->debug('分佣计算时分佣商品1:'. __FUNCTION__.':'.__LINE__.json_encode($this->itemsRebateConf) );
                app('log')->debug('分佣计算时分佣商品2:'. __FUNCTION__.':'.__LINE__.json_encode($ratio) );

                if($this->order['promoter_user_id'] && $itemRebate == '' ){
                    $ratio = 0;
                    app('log')->debug('分佣计算时分佣商品3:'. __FUNCTION__.':'.__LINE__.json_encode($ratio) );

                    continue;
                }  
                app('log')->debug('分佣计算时分佣商品4:'. __FUNCTION__.':'.__LINE__.json_encode($ratio) );

                $desc = '单品通用配置返佣';
                $rebate = null;
                $calRatio = $ratio;

                // 商品单品金额返佣
                if ($itemRebate !== '') {
                    $rebate = bcmul($itemRebate, 100);// 值为0则返回返佣0元 单位分
                    $desc = '单品金额返佣';
                } elseif ($itemRatio !== '') {
                    $calRatio = $itemRatio;
                    $desc = '单品比例返佣';
                }

                if ($rebate === null) {
                    if ($this->ratioType == 'profit') {
                        // 单个商品的返佣
                        $rebate = bcmul(bcsub($row['total_fee'], $row['cost_fee']), bcdiv($calRatio, 100, 4));
                    } else {
                        $rebate = bcmul($row['total_fee'], bcdiv($calRatio, 100, 4));
                    }
                }

                $items = [
                    'item_id' => $row['item_id'],
                    'cost_fee' => $row['cost_fee'],
                    'total_fee' => $row['total_fee'],
                    'item_num' => $row['num'],
                    'rebate' => (round($rebate) > 0) ? round($rebate) : 0,
                    'item_ratio' => ($itemRebate === '') ? $calRatio : 0, // 如果不是单品返佣金额
                    'desc' => $desc
                ];
                $results['rebate'] += $items['rebate'];
                $detail['rebate_detail'][$row['item_id']] = $items;
            }
        } else {
            // 计算返佣
            if ($this->ratioType == 'profit') {
                $results['rebate'] = bcmul(bcsub($this->itemTotalFee, $orderInfo['cost_fee']), bcdiv($ratio, 100, 4));
            } else {
                $results['rebate'] = bcmul($this->itemTotalFee, bcdiv($ratio, 100, 4));
            }
            $results['rebate'] = (round($results['rebate']) > 0) ? round($results['rebate']) : 0;
            $detail['rebate_detail']['item_id'] = [
                'item_id' => $orderInfo['item_id'],
                'item_num' => $orderInfo['item_num'],
                'total_fee' => $this->itemTotalFee,
                'rebate' => $results['rebate'],
            ];
        }
        $results['detail'] = $detail;
        return $results;
    }

    public function getBrokerageList($filter, $page, $limit, $orderBy = ['created' => 'DESC'])
    {
        $data = $this->brokerageRepository->lists($filter, $orderBy, $limit, $page);
        if ($data['total_count'] && $data['list']) {
            $userIds = array_column($data['list'], 'buy_user_id');
            $memberService = new MemberService();
            $memberList = $memberService->getList(1, $limit, array('user_id|in' => $userIds));
            $memberList = array_column($memberList['list'], null, 'user_id');

            $wechatUserService = new WechatUserService();
            $wechatUserList = $wechatUserService->getWechatUserList(['company_id' => $filter['company_id'], 'user_id' => $userIds]);
            $wechatUserList = array_column($wechatUserList, null, 'user_id');

            foreach ($data['list'] as &$row) {
                $row['created_date'] = date('Y-m-d H:i:s', $row['created']);
                if (isset($memberList[$row['buy_user_id']])) {
                    $row['username'] = data_masking('truename', $memberList[$row['buy_user_id']]['username'] ?? '');
                    $row['mobile'] = data_masking('mobile', $memberList[$row['buy_user_id']]['mobile'] ?? '');
                }

                if (isset($wechatUserList[$row['buy_user_id']])) {
                    $row['nickname'] = data_masking('truename', $wechatUserList[$row['buy_user_id']]['nickname'] ?? '');
                    $row['headimgurl'] = $wechatUserList[$row['buy_user_id']]['headimgurl'];
                }
            }
        }

        return $data;
    }


    public function getBrokerageListSalesman($filter, $page, $limit, $orderBy = ['created' => 'DESC'])
    {
        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter_sales['company_id'] = $filter['company_id'];
        $filter_sales['user_id']    = $filter['user_id'];

        $listdata = $salespersonService->getSalespersonList($filter_sales, 1, 500);
        $dIds = array_column($listdata['list'],'shop_id');
        $salesperson = array_column($listdata['list'],null,'shop_id');
        if(!$dIds){
            return array();
        }

        $distributorService = new DistributorService();
        if(isset($filter['shopName']) ){
            $filter_store = array();
            $filter_store['distributor_id'] = $dIds;
            $filter_store['name|like'] = $filter['shopName'];
            $data_store = $distributorService->lists($filter_store);
            $dIds = array_column($data_store['list'],'distributor_id');
            if(!$dIds){
                return array();
            }    
        }

        $params = $filter;
        $params['dIds'] = $dIds ;
        $params['order_id'] = $filter['order_id'] ?? 0; 
        $data = $this->getSalesmanBrokeragelistsBySql($params, $limit, $page);
        // $data = $this->brokerageRepository->lists($filter, $orderBy, $limit, $page);
        if ($data['total_count'] && $data['list']) {
            $userIds = array_column($data['list'], 'buy_user_id');
            $memberService = new MemberService();
            $memberList = $memberService->getList(1, $limit, array('user_id|in' => $userIds));
            $memberList = array_column($memberList['list'], null, 'user_id');

            $wechatUserService = new WechatUserService();
            $wechatUserList = $wechatUserService->getWechatUserList(['company_id' => $filter['company_id'], 'user_id' => $userIds]);
            $wechatUserList = array_column($wechatUserList, null, 'user_id');

            foreach ($data['list'] as &$row) {
                $row['created_date'] = date('Y-m-d H:i:s', $row['created']);
                if (isset($memberList[$row['buy_user_id']])) {
                    $row['username'] = data_masking('truename', $memberList[$row['buy_user_id']]['username'] ?? '');
                    $row['mobile'] = data_masking('mobile', $memberList[$row['buy_user_id']]['mobile'] ?? '');
                }

                if (isset($wechatUserList[$row['buy_user_id']])) {
                    $row['nickname'] = data_masking('truename', $wechatUserList[$row['buy_user_id']]['nickname'] ?? '');
                    $row['headimgurl'] = $wechatUserList[$row['buy_user_id']]['headimgurl'];
                }
            }
        }

        return $data;
    }

    public function getSalesmanBrokeragelistsBySql($params,  $limit, $page){
        $userId = $params['user_id'] ?? 0;
        $start = $limit * ($page - 1);

        $sqlWhereShopId = ' ';
        //is_close
        if(isset($params['is_close']) ){
            $is_close = $params['is_close'] ? 1 : 0;
            $sqlWhereIsClose = " and bb.is_close = {$is_close}  ";
        }
        if(isset($params['mobile']) && $params['mobile'] ){
            $sqlWhereIsMobile = " and oo.mobile = '{$params['mobile']}'  ";
        }
        if(isset($params['order_id']) && $params['order_id'] ){
            $sqlWhereIsOrderId = " and bb.order_id = '{$params['order_id']}'  ";
        }

        if(isset($params['distributor_id']) ){
            $dIds_str = $params['distributor_id'];
            $sqlWhereShopId = " and oo.distributor_id in  ( {$dIds_str} )  ";
        }

        if(isset($params['dIds']) ){
            $dIds_str = implode($params['dIds'], ",");
            $sqlWhereShopId = " and oo.distributor_id in  ( {$dIds_str} )  ";
        }


        if(isset($params['date_start']) ){
            $date_str = strtotime($params['date_start']." 00:00:00");
            $sqlWhereStart = " and bb.created >=  ( {$date_str} )  ";
        }
        
        if(isset($params['date_end']) ){
            $date_str =   strtotime($params['date_end']." 23:59:59");
            $sqlWhereEnd = " and bb.created <= ( {$date_str} )  ";
        }

        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $sql_total = "SELECT  count(1)  as total";  
        $sql_cls   = "SELECT  bb.*,oo.distributor_id,oo.title ,oo.total_fee ,ss.name, ss.mobile ,dd.name as store_name ,if(ss.user_id >0,'业务员','推广员')  as promote_type  ,ss.salesperson_id ";
        $countSql = " FROM popularize_brokerage as bb left join orders_normal_orders oo ON bb.order_id = oo.order_id left join aftersales as aa ON bb.order_id = aa.order_id  left join shop_salesperson as ss ON bb.user_id = ss.user_id and  oo.distributor_id = ss.shop_id  left join distribution_distributor as dd ON oo.distributor_id = dd.distributor_id WHERE 1 and source = 'order' and ss.salesperson_id is not null  ";
        // $countSql .= $sqlWhereDate ;
        if($userId){
            $countSql .= " AND bb.user_id =  {$userId} ";
        }
        $countSql .= $sqlWhereShopId ?? ' ';
        $countSql .= $sqlWhereIsClose ?? ' ';
        $countSql .= $sqlWhereIsMobile ?? ' ';
        $countSql .= $sqlWhereStart ?? ' '; 
        $countSql .= $sqlWhereEnd ?? ' '; 
        $countSql .= $sqlWhereIsOrderId ?? ' '; 
        // $countSql .= $sqlTab ?? ' ';
        $countSql .= " order by bb.created desc ";

        $countBrolage = $conn->executeQuery( $sql_total . $countSql)->fetch();

        $countSql .= " limit {$start} , {$limit}";
        $conn = app('registry')->getConnection('default');
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-countSql:". json_encode($countSql));


        $listBrokerage = $conn->executeQuery($sql_cls . $countSql)->fetchAll();
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listBrokerage:". json_encode($sql_cls . $countSql));
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listBrokerage:". json_encode($listBrokerage));
        // 统计推广员增加人数
        // $listPromoter = $this->getSalesPromotersStatic($userId, $dateLen , $sqlWhereDate, $sqlWhereShopId ) ;
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listPromoter:". json_encode($listPromoter));
        // 合并数据
        // $listMerge    = $this->mergeSaleStaticByDate($listPromoter, $listBrokerage);
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listMerge:". json_encode($listMerge));

        $countDataShopList = array('total_count' =>  intval($countBrolage['total'] ?? 0),    'list'=> $listBrokerage ,'isSalesmanPage' => 1 );



        $salesperson_id_arr = array_column($countDataShopList['list'],'salesperson_id'); 
        if($salesperson_id_arr){
            $filter_salesperson = array(
                'salesperson_id' => $salesperson_id_arr,
            );
            $salespersonService = new SalespersonService();

            $data_alesperson = $salespersonService->salesperson->lists($filter_salesperson);
            $data_alesperson_id_arr = array_column($data_alesperson['list'],null,'salesperson_id'); 

            $countDataShopList['data_alesperson'] = $data_alesperson;

        }
        foreach($countDataShopList['list'] as $k => &$v_salesperson){
            $v_salesperson['mobile'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['mobile'] ?? '-';
            $v_salesperson['username'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['name'] ?? '-';
            $v_salesperson['name'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['name'] ?? '-';
        }

        return $countDataShopList;

    }


    public function getSalesmanBrokerageCount($params,  $limit, $page){
        $userId = $params['user_id'] ?? 0;
        $start = $limit * ($page - 1);

        $sqlWhereShopId = ' ';
        //is_close
        if(isset($params['is_close']) ){
            $is_close = $params['is_close'] ? 1 : 0;
            $sqlWhereIsClose = " and bb.is_close = {$is_close}  ";
        }
        if(isset($params['mobile']) && $params['mobile'] ){
            $sqlWhereIsMobile = " and oo.mobile = '{$params['mobile']}'  ";
        }
        if(isset($params['order_id']) && $params['order_id'] ){
            $sqlWhereIsOrderId = " and bb.order_id = '{$params['order_id']}'  ";
        }

        if(isset($params['distributor_id']) && !empty($params['distributor_id'])){
            $dIds_str = $params['distributor_id'];
            $sqlWhereShopId = " and oo.distributor_id in  ( {$dIds_str} )  ";
        }

        if(isset($params['dIds']) ){//dIds
            $dIds_str = implode($params['dIds'], ",");
            $sqlWhereShopId = " and oo.distributor_id in  ( {$dIds_str} )  ";
        }


        if(isset($params['date_start']) ){
            $date_str = strtotime($params['date_start']." 00:00:00");
            $sqlWhereStart = " and bb.created >=  ( {$date_str} )  ";
        }
        
        if(isset($params['date_end']) ){
            $date_str =   strtotime($params['date_end']." 23:59:59");
            $sqlWhereEnd = " and bb.created <= ( {$date_str} )  ";
        }

        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        // $sql_total = "SELECT  count(1)  as total";  
        // $sql_cls   = "SELECT  bb.*,oo.distributor_id,oo.title ,oo.total_fee ,ss.name, ss.mobile ";

        $sql_sum   = "SELECT  sum(if(is_close=0,bb.rebate,0)) as rebate_sum_noclose,
        sum(if(is_close=1,bb.rebate,0)) as rebate_sum_close,
        sum(bb.rebate)  as rebate_sum,  sum(price) as price_sum,oo.total_fee ,ss.name, ss.mobile ";
        $countSql = " FROM popularize_brokerage as bb left join orders_normal_orders oo ON bb.order_id = oo.order_id left join aftersales as aa ON bb.order_id = aa.order_id  left join shop_salesperson as ss ON bb.user_id = ss.user_id and  oo.distributor_id = ss.shop_id  WHERE 1  and bb.source = 'order'  and ss.salesperson_id is not null   ";
        // $countSql .= $sqlWhereDate ;
        if($userId){
            $countSql .= " AND bb.user_id =  {$userId} ";
        }
        $countSql .= $sqlWhereShopId ?? ' ';
        $countSql .= $sqlWhereIsClose ?? ' ';
        $countSql .= $sqlWhereIsMobile ?? ' ';
        $countSql .= $sqlWhereStart ?? ' '; 
        $countSql .= $sqlWhereEnd ?? ' '; 
        $countSql .= $sqlWhereIsOrderId ?? ' '; 
        // $countSql .= $sqlTab ?? ' ';
        $countSql .= " order by bb.created desc ";
        $countBrolage = $conn->executeQuery( $sql_sum . $countSql)->fetch();
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-sql_sum:". json_encode( $sql_sum . $countSql));

        return $countBrolage;
    }


    public function getSalesmanBrokerageCountList($params,  $limit = 1000, $page = 1){
        $userId = $params['user_id'] ?? 0;
        $start = $limit * ($page - 1);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-userId:". json_encode($userId));
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-userId:". var_export($userId));
        $is_list_user = is_array($userId );

        // // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-$is_list_user:". var_export($is_list_user));
        // if( is_array($userId ) ){
        //     $userId = false;
        //     $userIdArray = $userId;
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-userIdArray:". json_encode(($userIdArray ?? [])) );

        // }
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-userIdArray:". json_encode(($userIdArray ?? [])) );

        $sqlWhereShopId = ' ';
        //is_close
        if(isset($params['is_close']) ){
            $is_close = $params['is_close'] ? 1 : 0;
            $sqlWhereIsClose = " and bb.is_close = {$is_close}  ";
        }
        if(isset($params['mobile']) && $params['mobile'] ){
            $sqlWhereIsMobile = " and oo.mobile = '{$params['mobile']}'  ";
        }
        if(isset($params['order_id']) && $params['order_id'] ){
            $sqlWhereIsOrderId = " and bb.order_id = '{$params['order_id']}'  ";
        }

        if(isset($params['distributor_id']) &&  $params['distributor_id']){
            $dIds_str = $params['distributor_id'];
            $sqlWhereShopId = " and oo.distributor_id in  ( {$dIds_str} )  ";
        }

        if(isset($params['dIds']) ){
            $dIds_str = implode($params['dIds'], ",");
            $sqlWhereShopId = " and oo.distributor_id in  ( {$dIds_str} )  ";
        }
        // if($userIdArray){
        //     $userIdArray_str = implode($userIdArray, ",");
        //     $sqlWhereUseridArray = " and bb.user_id in  ( {$userIdArray_str} )  ";
        // }
        if( is_array($userId ) && $userId ){
            $userIdArray_str = implode($userId, ",");
            $sqlWhereUseridArray = " and bb.user_id in  ( {$userIdArray_str} )  ";
            $userId = 0;

        }        
        // year: 2024
        // month: 2024-07
        // day: 2024-07-30
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-params:ymd:". json_encode($params));
        $sqlWhereDate = ' '; 
        if(isset($params['year']) && !empty($params['year'])) {
            $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,4) = '".$params['year']."' ";
        }
        if(isset($params['month']) && !empty($params['month']) ) {
            $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,7) = '".$params['month']."' ";
        }
        if(isset($params['day'])  && !empty($params['day'])) {
            $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,10) = '".$params['day']."' ";
        }

        if(isset($params['date_start']) ){
            $date_str = strtotime($params['date_start']." 00:00:00");
            $sqlWhereStart = " and bb.created >=  ( {$date_str} )  ";
        }
        
        if(isset($params['date_end']) ){
            $date_str =   strtotime($params['date_end']." 23:59:59");
            $sqlWhereEnd = " and bb.created <= ( {$date_str} )  ";
        }

        $sqlGroupBy = " group by bb.user_id,oo.distributor_id ";
        // groupby
        if(isset($params['groupby']) ){
            $sqlGroupBy .= " , oo.{$params['groupby']}  ";
        }

        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $sql_total = "SELECT  count(1)  as total FROM ";  
        // $sql_cls   = "SELECT  bb.*,oo.distributor_id,oo.title ,oo.total_fee ,ss.name, ss.mobile ";

        $sql_sum   = "SELECT ss.salesperson_id,SUM(if(rebate > 0,1,0)) as order_num, SUM(if(rebate < 0,1,0)) as order_num_refund,  bb.order_id, bb.user_id, sum(if(is_close=0,bb.rebate,0)) as rebate_sum_noclose,sum(bb.rebate)  as rebate_sum,  sum(price) as price_sum,oo.total_fee ,ss.name as username, ss.mobile,dd.name as store_name,dd.distributor_id ";

        $countSql = " FROM popularize_brokerage as bb left join orders_normal_orders oo ON bb.order_id = oo.order_id left join aftersales as aa ON bb.order_id = aa.order_id  left join distribution_distributor as dd ON oo.distributor_id = dd.distributor_id left join shop_salesperson as ss ON bb.user_id = ss.user_id and  oo.distributor_id = ss.shop_id and ss.user_id > 0 WHERE 1 and oo.distributor_id > 0  AND ss.salesperson_id is not null  and bb.source = 'order'  and ss.salesperson_id is not null   ";
        $countSql .= $sqlWhereDate ;
        if($userId){
            $countSql .= " AND bb.user_id =  {$userId} ";
        }


        $countSql .= $sqlWhereShopId ?? ' ';
        $countSql .= $sqlWhereIsClose ?? ' ';
        $countSql .= $sqlWhereIsMobile ?? ' ';
        $countSql .= $sqlWhereStart ?? ' '; 
        $countSql .= $sqlWhereEnd ?? ' '; 
        $countSql .= $sqlWhereIsOrderId ?? ' '; 
        $countSql .= $sqlWhereUseridArray ?? ' ';
        $countSql .= $sqlGroupBy ?? ' ';

        // $countSql .= $sqlTab ?? ' ';
        $countSql .= " order by rebate_sum desc ";//bb.created

        // $countBrolage = $conn->executeQuery( $sql_total ." ( ". $sql_sum . $countSql . " )   as da ")->fetch();
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-sql_total:".  $sql_total ." ( ". $countSql . " )  as da " );

        $countSql .= " limit {$start} , {$limit}  ";
        $countListBrolage = $conn->executeQuery( $sql_sum . $countSql)->fetchAll();
        // $listBrokerage = $conn->executeQuery($sql_cls . $countSql)->fetchAll();
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-sql_sum:". json_encode( $sql_sum . $countSql));


        // $countDataShopList =  array('total_count' =>  intval($countBrolage['total'] ?? 0),    'list'=> $countListBrolage ,'isSalesmanPage' => 1 );


        $salesperson_id_arr = array_column($countListBrolage, 'salesperson_id'); 
        if($salesperson_id_arr){
            $filter_salesperson = array(
                'salesperson_id' => $salesperson_id_arr,
            );
            $salespersonService = new SalespersonService();

            $data_alesperson = $salespersonService->salesperson->lists($filter_salesperson);
            $data_alesperson_id_arr = array_column($data_alesperson['list'],null,'salesperson_id'); 

            // $countDataShopList['data_alesperson'] = $data_alesperson;

        }
        foreach($countListBrolage as $k => &$v_salesperson){
            $v_salesperson['mobile'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['mobile'] ?? '-';
            $v_salesperson['username'] = $data_alesperson_id_arr[$v_salesperson['salesperson_id']]['name'] ?? '-';
        }

        return $countListBrolage;
    }


    /**
     * 定时执行结算
     */
    public function scheduleSettleRebate()
    {
        $filter = [
            'plan_close_time|lte' => time(),
            'is_close' => false
        ];
        $totalCount = $this->brokerageRepository->count($filter);
        if (!$totalCount) {
            return true;
        }

        $promoterCountService = new PromoterCountService();
        $totalPage = ceil($totalCount / 100);
        for ($i = 1; $i <= $totalPage; $i++) {
            $data = $this->brokerageRepository->lists($filter, ["created" => "DESC"], 100, 1);
            foreach ($data['list'] as $row) {
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                try {
                    $this->brokerageRepository->updateOneBy(['id' => $row['id']], ['is_close' => true]);
                    // 根据类型结算
                    if ($row['commission_type'] == 'money') {
                        $promoterCountService->addSettleRebate($row['company_id'], $row['user_id'], $row['rebate']);
                    } else {
                        $promoterCountService->updateSettlePoint($row['company_id'], $row['user_id'], $row['rebate_point'], $row['order_id']);
                    }
                    $conn->commit();
                } catch (\Exception $e) {
                    $conn->rollback();
                    app('log')->debug('定时执行佣金结算失败=>' . $e->getMessage());
                    app('log')->debug('定时执行佣金结算失败参数=>' . var_export($row, 1));
                } catch (\Throwable $e) {
                    $conn->rollback();
                    app('log')->debug('定时执行佣金结算失败=>' . $e->getMessage());
                    app('log')->debug('定时执行佣金结算失败参数=>' . var_export($row, 1));
                }
            }
        }
        return true;
    }

    /**
     * 确认最终的预计结算的时间
     *
     * 1 当前用户确认收货的时候
     * 2 当用户已支付并且取消订单的时候
     * 3 当用户订单全部申请售后并且通过的时候
     */
    public function updatePlanCloseTime($companyId, $orderId)
    {
        // 获取推广员层级返佣比例
        $settingService = new SettingService();
        $config = $settingService->getConfig($companyId);
        $settleTime = $config['limit_time'] ?: 0;
        $ordersSetting = $this->getOrdersSetting($companyId);
        $latestAftersaleTime = $ordersSetting['latest_aftersale_time'] ?? 0;
        // 计划分销佣金确认的时间 = 订单售后时效 + 分销佣金确认的时间

        $planCloseTime = time() + 3600 * 24 * ($settleTime + $latestAftersaleTime);
        app('log')->debug('settleTime: ' . $settleTime . ' latest_aftersale_time: ' . $latestAftersaleTime.' time:'. date('Y-m-d H:i:s', $planCloseTime) .' 服务器当前时间'. date('Y-m-d H:i:s', time()));

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'is_close' => false,
        ];
        $this->brokerageRepository->updateBy($filter, ["plan_close_time" => $planCloseTime]);

        $taskBrokerageService = new TaskBrokerageService();
        $taskBrokerageService->updateTaskBrokerage($companyId, $orderId);

        return true;
    }

    /**
     * 售后处理佣金
     * @param $companyId
     * @param $orderId
     * @param $itemId
     * @param $num
     * @return bool
     * @throws \Exception
     */
    public function brokerageByAftersalse($companyId, $orderId, $itemId, $num)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'is_close' => false,
            'rebate|gt' => 0
        ];

        $data = $this->brokerageRepository->lists($filter);
        if ($data['total_count'] <= 0) {
            return true;
        }

        //已退商品数
        $_filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'aftersales_status' => 2,
        ];
        $aftersalesDetail = $this->aftersalesDetailRepository->sum($_filter, 'sum(num) as num');
        $aftersalesDetail['num'] = $aftersalesDetail['num'] ?? 0;

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $promoterCountService = new PromoterCountService();
            foreach ($data['list'] as $row) {
                if (!isset($row['detail']['rebate_detail'][$itemId])) {
                    continue;
                }

                $itemRebateData = $row['detail']['rebate_detail'][$itemId];

                $left_num = $itemRebateData['item_num'] - $aftersalesDetail['num'] - $num;
                if ($left_num == 0) {
                    //已回收佣金金额
                    $filter = [
                        'company_id' => $companyId,
                        'order_id' => $orderId,
                        'rebate|lt' => 0,
                        'brokerage_type' => $row['brokerage_type'],
                    ];
                    $sumRebate = $this->brokerageRepository->sumRebate($filter);
                    $sumRebate = abs($sumRebate);//负数变正数

                    $row['rebate'] = -($itemRebateData['rebate'] - $sumRebate);
                    $row['price'] = -(round($itemRebateData['total_fee'] / $itemRebateData['item_num'])) * $num;
                } elseif ($left_num > 0) {
                    //按数量占比回收佣金
                    $row['rebate'] = -(round($itemRebateData['rebate'] / $itemRebateData['item_num'])) * $num;
                    $row['price'] = -(round($itemRebateData['total_fee'] / $itemRebateData['item_num'])) * $num;
                } else {
                    throw new ResourceException('申请售后单数据异常');
                }

                $config = (new SettingService())->getConfig($companyId);
                $settleTime = $config['limit_time'] ?: 0;

                $ordersSetting = $this->getOrdersSetting($companyId);
                $latestAftersaleTime = $ordersSetting['latest_aftersale_time'] ?? 0;
                $row['plan_close_time'] = time() + 3600 * 24 * ($settleTime + $latestAftersaleTime);

                $pointMemberRuleService = new PointMemberRuleService($companyId);
                $row['rebate_point'] = $pointMemberRuleService->moneyToPointSend($row['rebate']);

                $row['is_close'] = 0; //退佣金状态为已结算
                $row['detail']['remarks'] = '申请售后生成';
                $row['created'] = time();
                $this->brokerageRepository->create($row);

                $itemPrice = ($row['source'] == 'order') ? $row['price'] : 0;
                if ('money' == $row['commission_type']) {
                    // $itemPrice = ($row['brokerage_type'] == 'first_level') ? $row['price'] : 0;
                    $promoterCountService->addPopularize($row['company_id'], $row['user_id'], $itemPrice, $row['rebate']);
                } else {
                    $promoterCountService->updatePromoterOrderPoint($row['company_id'], $row['user_id'], $itemPrice, $row['rebate_point']);
                }
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        }

        return true;
    }


    // 取消订单处理佣金
    public function brokerageBycancelOrder($companyId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'is_close' => false,
        ];
        $data = $this->brokerageRepository->lists($filter);
        if ($data['total_count'] <= 0) {
            // 订单佣金为空，还要处理任务制返佣
            $this->updatePlanCloseTime($companyId, $orderId);
            return true;
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $promoterCountService = new PromoterCountService();
            foreach ($data['list'] as $row) {
                // 取消订单新增一个负的佣金
                $row['rebate'] = -$row['rebate'];
                $row['price'] = -$row['price'];
                $row['rebate_point'] = -$row['rebate_point'];
                $row['plan_close_time'] = time() + 3600 * 24 * 1000;
                $row['detail']['remarks'] = '取消订单生成';
                $row['created'] = time();
                $this->brokerageRepository->create($row);

                $itemPrice = ($row['source'] == 'order') ? $row['price'] : 0;
                if ('money' == $row['commission_type']) {
                    $promoterCountService->addPopularize($row['company_id'], $row['user_id'], $itemPrice, $row['rebate']);
                } else {
                    $promoterCountService->updatePromoterOrderPoint($row['company_id'], $row['user_id'], $itemPrice, $row['rebate_point']);
                }
            }

            $this->updatePlanCloseTime($companyId, $orderId);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        }

        return true;
    }

    /**
     * 获取业务员推广员的上级 function
     *
     * @return mixed
     */
    public function getsalesPersonPromoterList($userId)
    {
        $promoterService = new PromoterService();
        $promoterInfo = $promoterService->getInfoByUserId($userId);
        
        app('log')->debug('推广员详细信息' . json_encode($promoterInfo));
        $list = array();
        if ($promoterInfo) {
            $promoterInfo['relationship_depth'] = 1;
            $list[] = $promoterInfo;
            if(isset($promoterInfo['pid']) && $promoterInfo['pid']){
                $promoterInfoPid = $promoterService->getInfo(['id'=> $promoterInfo['pid'] ]);
                if($promoterInfoPid && isset($promoterInfoPid['user_id'])){
                    // $promoterInfoPid['relationship_depth'] = 2;
                    // $list[] = $promoterInfoPid;    
                }    
            }
        }
        return $list;
    }



    /**
     * 获取推广员的上级 function
     *
     * @return mixed
     */
    public function getParentPromoterList($userId, $depth = 2)
    {
        $promoterService = new PromoterService();
        $promoterInfo = $promoterService->getInfoByUserId($userId);
        app('log')->debug('推广员详细信息' . json_encode($promoterInfo));
        $list = array();
        if ($promoterInfo) {
            // $data = $promoterService->getRelationParentById($promoterInfo['promoter_id'], 1, $depth);
            $filter['promoter_id'] = $promoterInfo['promoter_id'];
            $data = $promoterService->getRelationParentBy($filter, $depth);
            $list = ($data['total_count'] > 0) ? $data['list'] : array();
        }
        return $list;
    }

    /**
     * 充值返佣金
     * !已废弃 充值返佣金有风险
     *
     * @param $userId
     * @param $companyId
     * @param $price
     * @param string $orderId
     * @return void|null
     * @throws \Throwable
     */
    public function recharge($userId, $companyId, $price, $orderId = '')
    {
        $settingService = new SettingService();
        $isOpen = $settingService->getOpenPopularize($companyId);
        // 当前商家未开启推广员返佣
        if ($isOpen == 'false') {
            return null;
        }

        // 是否打开充值返佣
        $config = $settingService->getConfig($companyId);
        if (!isset($config['isOpenRecharge']) || $config['isOpenRecharge'] == 'false') {
            return null;
        }

        // 支付返佣层级
        $rechargePromoterLevel = $settingService->rechargePromoterLevel;
        $promoterList = $this->getParentPromoterList($userId, count($rechargePromoterLevel));
        if (empty($promoterList)) {
            return null;
        }
        $promoterList = array_column($promoterList, null, 'relationship_depth');

        // 获取推广员层级返佣比例

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($rechargePromoterLevel as $name => $row) {
                // 如果设置返佣为 0 则不进行返佣
                $ratio = $config['recharge']['profit'][$name]['ratio'];
                if ($ratio <= 0) {
                    continue;
                }

                $promoterInfo = isset($promoterList[$row['level']]) ? $promoterList[$row['level']] : null;
                // 不存在对应的推广员，或者已被禁用
                if (!$promoterInfo || $promoterInfo['disabled']) {
                    continue;
                }
                $rebate = intval(($config['recharge']['profit'][$name]['ratio'] / 100) * $price);
                $pointMemberRuleService = new PointMemberRuleService($companyId);
                $rebatePoint = $pointMemberRuleService->moneyToPointSend($rebate);
                // 充值返佣增加推广人员的储值
                $data = [
                    'brokerage_type' => $name,
                    'order_id' => $orderId,
                    'source' => 'recharge',
                    'company_id' => $companyId,
                    'price' => $price,
                    'rebate' => $rebate,
                    'detail' => json_encode($config['recharge']),
                    'user_id' => $promoterInfo['user_id'],
                    'is_close' => true,
                    'order_type' => 'recharge',
                    'commission_type' => $config['commission_type'],
                    'plan_close_time' => time(),
                    'buy_user_id' => $userId,
                    'rebate_point' => $rebatePoint,
                ];
                $this->brokerageRepository->create($data);
                $depositTrade = new DepositTrade();
                $depositTrade->addDepositToRedis($companyId, $promoterInfo['user_id'], intval(($config['recharge']['profit'][$name]['ratio'] / 100) * $price), 0);
                $memberService = new MemberService();
                $info = $memberService->getMemberInfo(['user_id' => $promoterInfo['user_id']]);
                $depositTradeData['deposit_trade_id'] = $depositTrade->genDepositTradeId($promoterInfo['user_id']);
                $depositTradeData['company_id'] = $companyId;
                $depositTradeData['member_card_code'] = $info['user_card_code']; //
                $depositTradeData['shop_id'] = '';
                $depositTradeData['shop_name'] = '';
                $depositTradeData['user_id'] = $promoterInfo['user_id'];
                $depositTradeData['mobile'] = $info['mobile']; //
                $depositTradeData['open_id'] = $info['open_id'] ?? ''; //
                $depositTradeData['money'] = $data['rebate'];
                $depositTradeData['trade_type'] = 'recharge_send';
                $depositTradeData['trade_status'] = 'SUCCESS';
                $depositTradeData['wxa_appid'] = '';
                $depositTradeData['detail'] = '充值返佣';
                $depositTradeData['cur_pay_fee'] = $data['rebate'];
                $depositTradeData['authorizer_appid'] = $info['authorizer_appid']; //
                $depositTradeData['time_start'] = time();
                $depositTradeData['time_expire'] = time();
                app('registry')->getManager('default')->getRepository(\DepositBundle\Entities\DepositTrade::class)->createDepositTrade($depositTradeData);

                // 读取配置信息
                $config = $settingService->getConfig($companyId);
                $this->setCommissionTypeByConfig($config);

                $promoterCountService = new PromoterCountService();
                if ($this->commissionType == 'money') {
                    $promoterCountService->addRechargeRebate($companyId, $promoterInfo['user_id'], $data['rebate']);
                } else {
                    $promoterCountService->updateRechargePoint($companyId, $promoterInfo['user_id'], $data['rebate_point']);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    //预先冻结主商户的待结算佣金，按最高分佣比例结算
    public function freezeCommissionFee($orderId)
    {
        //获取商品任务

        //调用汇付的冻结接口
    }

    //解冻主商户的待结算佣金，每个月任务结束，结算佣金的时候调用
    public function unfreezeCommissionFee($userId)
    {
        //当前推广员所有的 待结算佣金 的冻结记录

        //调用汇付的解冻接口

        //调用转账接口，将佣金转给推广员账户
    }

    /**
     * Set CommissionType
     *
     * @param $config
     * @return mixed|string
     */
    public function setCommissionTypeByConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config, true);
        }
        $commissionType = $config['commission_type'] ?? 'money';
        $this->commissionType = $commissionType;
        return $commissionType;
    }

    public function getOrderBrokerage(int $companyId, int $userId): array
    {
        $where = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'commission_type' => 'point',
        ];

        $fields = "company_id,user_id,is_close,source,rebate_point";
        $list = $this->brokerageRepository->getLists($where, $fields);

        $orderNoCloseRebate = 0;
        $orderCloseRebate = 0;
        $orderTeamNoCloseRebate = 0;
        $orderTeamCloseRebate = 0;

        foreach ($list as $item) {
            if ($item['source'] == 'order') {
                if ($item['is_close']) {
                    $orderCloseRebate += $item['rebate_point'];
                } else {
                    $orderNoCloseRebate += $item['rebate_point'];
                }
            } elseif ($item['source'] == 'order_team') {
                if ($item['is_close']) {
                    $orderTeamCloseRebate += $item['rebate_point'];
                } else {
                    $orderTeamNoCloseRebate += $item['rebate_point'];
                }
            }
        }

        return [
            'order_no_close_rebate' => (int)$orderNoCloseRebate,
            'order_close_rebate' => (int)$orderCloseRebate,
            'order_team_no_close_rebate' => (int)$orderTeamNoCloseRebate,
            'order_team_close_rebate' => (int)$orderTeamCloseRebate,
        ];
    }

    public function getBrokerageDbList($filter, $page, $pageSize)
    {
        $data = $this->brokerageRepository->lists($filter, ["created" => "DESC"], $pageSize, $page);

        foreach ($data['list'] as $key => $datum) {
            if ($datum['commission_type'] == 'point') {
                $data['list'][$key]['rebate'] = 0;
            } else {
                $data['list'][$key]['rebate_point'] = 0;
            }
        }

        return $data;
    }

    /**
     * 获取推广员的业绩信息
     * @param  string $companyId      企业ID
     * @param  string $promoterUserId 推广员的会员ID
     */
    public function getPromoterBrokerageInfo($companyId, $promoterUserId)
    {
        $memberService = new MemberService();
        $mobile = $memberService->getMobileByUserId($promoterUserId, $companyId);
        // 已提现:&nbsp;<span>{{
        //     (count.rebateTotal -
        //       count.cashWithdrawalRebate -
        //       count.freezeCashWithdrawalRebate -
        //       count.noCloseRebate) 
        $promoterCountService = new PromoterCountService();
        $countData = $promoterCountService->getPromoterCount($companyId, $promoterUserId);
        $hasbeenCashWithdrawalRebate = $countData['rebateTotal'] - $countData['cashWithdrawalRebate'] - $countData['freezeCashWithdrawalRebate'] - $countData['noCloseRebate'];
        $result = [
            'promoter_mobile' => $mobile,
            'itemTotalPrice' => $countData['itemTotalPrice'],//商品总额/销售额
            'rebateTotal' => $countData['rebateTotal'],//佣金总额
            'cashWithdrawalRebate' => $countData['cashWithdrawalRebate'],//可提现
            'freezeCashWithdrawalRebate' => $countData['freezeCashWithdrawalRebate'],//申请提现、冻结金额
            'hasbeenCashWithdrawalRebate' => $hasbeenCashWithdrawalRebate,//已提现
            'noCloseRebate' => $countData['noCloseRebate'],//未结算
            'pointTotal' => $countData['pointTotal'] ?? 0,//积分总额
        ];
        return $result;
    }

    public function __call($method, $parameters)
    {
        return $this->brokerageRepository->$method(...$parameters);
    }
}
