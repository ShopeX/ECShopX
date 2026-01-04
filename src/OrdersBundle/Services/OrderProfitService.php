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

namespace OrdersBundle\Services;

use SalespersonBundle\Services\SalespersonService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributionService;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsProfitService;
use GoodsBundle\Services\ItemsCategoryProfitService;
use OrdersBundle\Entities\OrderItemsProfit;
use OrdersBundle\Entities\OrderProfit;
use OrdersBundle\Traits\OrderSettingTrait;
use WorkWechatBundle\Services\WorkWechatRelService;

class OrderProfitService
{
    use OrderSettingTrait;

    // 分润比例最大小数点位数 例如 分润比例是 0.01255
    public const PROFIT_SCALE = 5;

    // 总部分润
    public const PROFIT_TYPE_HEADQUARTER = 1;
    // 自营门店分润
    public const PROFIT_TYPE_DISTRIBUTOR = 2;
    // 加盟门店分润
    public const PROFIT_TYPE_DISTRIBUTOR_PROPRIETARY = 3;

    // 无门店
    public const PROPRIETARY_NULL = 0;
    // 自营门店
    public const PROPRIETARY = 1;
    // 加盟门店
    public const PROPRIETARY_POPULARIZE = 2;

    // 分润状态（未支付不分润，退款不分润）
    public const PROFIT_STATUS_FAIL = 0;
    // 分润状态（已支付冻结分润）
    public const PROFIT_STATUS_FROZEN = 1;
    // 分润状态（已支付已分润）
    public const PROFIT_STATUS_SYNC = 2;

    public $orderProfitRepository;
    public $orderItemsProfitRepository;

    public function __construct()
    {
        $this->orderProfitRepository = app('registry')->getManager('default')->getRepository(OrderProfit::class);
        $this->orderItemsProfitRepository = app('registry')->getManager('default')->getRepository(OrderItemsProfit::class);
    }

    /**
     * 分润
     * @param $ordersResult
     */
    public function profitByOrderResult($orderData, $totalFee)
    {
        // Ver: 8d1abe8e
        $companyId = $orderData['company_id'];
        $userId = $orderData['user_id'];
        $orderId = $orderData['order_id'];
        $orderDistributorId = $orderData['distributor_id'];
        $salesmanId = $orderData['salesman_id'];

        $sellerId = 0;
        $distributorId = 0;

        $distributorService = new DistributorService();
        $workWechatRelService = new WorkWechatRelService();
        $salespersonService = new SalespersonService();

        $salespersonBindInfo = $workWechatRelService->workWechatRelRepository->getInfo(['user_id' => $userId, 'is_bind' => 1]);

        if ($salespersonBindInfo['salesperson_id'] ?? 0) {
            if ($salespersonBindInfo['salesperson_id'] ?? 0) {
                $relSalespersonInfo = $salespersonService->relSalesperson->getInfo(['salesperson_id' => $salespersonBindInfo['salesperson_id'], 'store_type' => 'distributor']);
                if ($relSalespersonInfo) {
                    $sellerId = $relSalespersonInfo['salesperson_id'];
                    $distributorInfo = $distributorService->entityRepository->getInfo(['distributor_id' => $relSalespersonInfo['shop_id']]);
                    if ($distributorInfo) {
                        $distributorId = 'true' == $distributorInfo['is_valid'] ? $distributorInfo['distributor_id'] : 0;
                    }
                }
            }
        }

        $relationIds = [
            'user_id' => $userId,
            'seller_id' => $sellerId, // 拉新导购id
            'distributor_id' => $distributorId, // 拉新店铺id
            'order_distributor_id' => $orderDistributorId, // 下单店铺id
            'popularize_seller_id' => $salesmanId, // 推广导购id
        ];

        return $this->profit($orderData['company_id'], $orderData['order_id'], $totalFee, $relationIds);
    }

    /**
     * 分润
     * @param $companyId 公司id
     * @param $orderId 订单id
     * @param $totalFee 订单总价格（分）
     * @param array $relation 关联id
     * 'user_id' => $userId,
     * 'seller_id' => $sellerId, // 拉新导购id
     * 'distributor_id' => $distributorId, // 拉新店铺id
     * 'order_distributor_id' => $distributorPid, // 下单店铺id
     * 'popularize_seller_id' => $salesmanId, // 推广导购id
     *
     */
    private function profit($companyId, $orderId, $totalFee = [], $relation = [])
    {
        $data = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'order_profit_status' => self::PROFIT_STATUS_FAIL,
            'profit_type' => self::PROFIT_TYPE_DISTRIBUTOR,
            'pay_fee' => $totalFee['pay_fee'],
            'user_id' => $relation['user_id'] ?? 0,
            'dealer_id' => 0,
            'distributor_id' => $relation['distributor_id'] ?? 0,
            'order_distributor_id' => $relation['order_distributor_id'] ?? 0,
            'distributor_nid' => 0,
            'seller_id' => $relation['seller_id'] ?? 0,
            'popularize_distributor_id' => 0,
            'popularize_seller_id' => $relation['popularize_seller_id'] ?? 0,
            'proprietary' => self::PROFIT_TYPE_DISTRIBUTOR,
            'popularize_proprietary' => self::PROFIT_TYPE_DISTRIBUTOR,
            'dealers' => 0,
            'seller' => 0,
            'distributor' => 0,
            'popularize_seller' => 0,
            'popularize_distributor' => 0,
            'commission' => 0,
        ];
        $distributionService = new DistributionService();
        $distributionConfig = $distributionService->getDistributionConfig($companyId);
        $profitType = $this->getProfitType();
        $dealersProfit = 0;
        $sellerProfit = 0;
        $distributorProfit = 0;
        $popularizeSellerProfit = 0;
        $popularizeDistributorProfit = 0;
        $commissionProfit = 0;
        if (!($distributionConfig[$profitType] ?? 0)) {
            app('log')->info('分润未开启, 分润失败:' . $orderId);
        }
        $data['rule'] = $distributionConfig[$profitType];
        if ($distributionConfig[$profitType]['show'] ?? 0) {
            $sellerProfit = $distributionConfig[$profitType]['seller'] ?? 0;
            $distributorProfit = isset($distributionConfig[$profitType]['distributor']) && $data['distributor_id'] > 0 ? $distributionConfig[$profitType]['distributor'] : 0;
            $popularizeSellerProfit = $distributionConfig[$profitType]['popularize_seller'] ?? 0;
        }
        $itemIds = array_keys($totalFee['item_fee']);
        $itemProfitData = $this->getItemProfit($itemIds);


        $data['seller'] = 0;
        $data['distributor'] = 0;
        $data['popularize_seller'] = 0;
        $data['total_fee'] = 0;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            foreach ($totalFee['item_fee'] as $k => $v) {
                $itemData = $data;
                $itemData['item_id'] = $k;
                $itemData['dealers'] = 0;
                $itemData['popularize_distributor'] = 0;
                $itemData['commission'] = 0;
                if ($v['is_profit']) {
                    if (($itemProfitData[$k] ?? 0) && $relation['seller_id']) {
                        $itemData['seller'] = 1 == $itemProfitData[$k]['profit_type'] ? bcmul($v['item_fee'], bcdiv($itemProfitData[$k]['profit'], 100, self::PROFIT_SCALE), 0) : $itemProfitData[$k]['profit'] ;
                        $itemData['distributor'] = bcmul($v['item_fee'], bcdiv($distributorProfit, 100, self::PROFIT_SCALE), 0);
                    }
                    if (($itemProfitData[$k] ?? 0) && $relation['popularize_seller_id']) {
                        $itemData['popularize_seller'] = 1 == $itemProfitData[$k]['profit_type'] ? bcmul($v['item_fee'], bcdiv($itemProfitData[$k]['popularize_profit'], 100, self::PROFIT_SCALE), 0) : $itemProfitData[$k]['popularize_profit'] ;
                    }
                    if (!($itemProfitData[$k] ?? 0) && $relation['seller_id']) {
                        $itemData['seller'] = bcmul($v['item_fee'], bcdiv($sellerProfit, 100, self::PROFIT_SCALE), 0);
                        $itemData['distributor'] = bcmul($v['item_fee'], bcdiv($distributorProfit, 100, self::PROFIT_SCALE), 0);
                    }
                    if (!($itemProfitData[$k] ?? 0) && $relation['popularize_seller_id']) {
                        $itemData['popularize_seller'] = bcmul($v['item_fee'], bcdiv($popularizeSellerProfit, 100, self::PROFIT_SCALE), 0);
                    }
                }
                $itemData['total_fee'] = bcsub($v['item_fee'], $itemData['seller']);
                $itemData['total_fee'] = bcsub($itemData['total_fee'], $itemData['distributor']);
                $itemData['total_fee'] = bcsub($itemData['total_fee'], $itemData['popularize_seller']);
                $data['seller'] += $itemData['seller'];
                $data['distributor'] += $itemData['distributor'];
                $data['popularize_seller'] += $itemData['popularize_seller'];
                $data['total_fee'] += $itemData['total_fee'];
                $this->orderItemsProfitRepository->create($itemData);
            }

            $result = $this->orderProfitRepository->create($data);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->info('分润失败:' . $e);
            throw new ResourceException('分润失败');
        }
        return $result;
    }

    private function getProfitType($profitType = '')
    {

        //系统bug，目前只支持自营店分润
        return 'distributor';

        // $profitTypeList = [
        //     self::PROFIT_TYPE_HEADQUARTER => 'headquarters',
        //     self::PROFIT_TYPE_DISTRIBUTOR => 'distributor',
        //     self::PROFIT_TYPE_DISTRIBUTOR_PROPRIETARY => 'distributor_proprietary',
        // ];
        // if (!isset($profitTypeList[$profitType])) {
        //     throw new ResourceException('分润类型错误');
        // }
        // return $profitTypeList[$profitType];
    }

    /**
     * 获取分润信息
     * @param $orderId
     * @return array
     */
    public function getOrderProfit($orderId)
    {
        // Ver: 8d1abe8e
        $profitResult = $this->orderProfitRepository->getInfo(['order_id' => $orderId]);
        if (!$profitResult) {
            return [];
        }
        $distributorIds = array_filter([$profitResult['distributor_id']]);
        $sellerIds = array_filter([$profitResult['seller_id'], $profitResult['popularize_seller_id']]);
        $distributorService = new DistributorService();
        $salespersonService = new SalespersonService();
        $shopsRelSalespersonListTemp = $distributorIds ? $distributorService->entityRepository->getLists(['company_id' => $profitResult['company_id'], 'distributor_id' => $distributorIds]) : [];
        $shopsRelSalespersonList = $shopsRelSalespersonListTemp ? array_column($shopsRelSalespersonListTemp, null, 'distributor_id') : [];
        $salespersonListTemp = $sellerIds ? $salespersonService->salesperson->getLists(['company_id' => $profitResult['company_id'], 'salesperson_id' => $sellerIds]) : [];
        $salespersonList = $salespersonListTemp ? array_column($salespersonListTemp, null, 'salesperson_id') : [];
        $profitResult['distributor_info'] = $shopsRelSalespersonList[$profitResult['distributor_id']] ?? [];
        $profitResult['seller_info'] = $salespersonList[$profitResult['seller_id']] ?? [];
        $profitResult['popularize_seller_info'] = $salespersonList[$profitResult['popularize_seller_id']] ?? [];
        return $profitResult;
    }

    /**
     * 获取商品分润信息
     *
     * @param array $itemIds
     * @return array
     */
    public function getItemProfit($itemIds)
    {
        $itemsProfit = [];

        $itemsProfitService = new ItemsProfitService();
        $itemsProfitFilter = ['item_id' => $itemIds];
        $itemsProfitListTemp = $itemsProfitService->getLists($itemsProfitFilter, '*', 1, -1);
        $itemsProfitList = array_column($itemsProfitListTemp, null, 'item_id');
        $itemIdsTemp = [];
        foreach ($itemsProfitList as &$v) {
            $profitConf = json_decode($itemsProfitList[$v['item_id']]['profit_conf'], 1);
            $itemsProfit[$v['item_id']] = [
                'profit_type' => $itemsProfitList[$v['item_id']]['profit_type'],
                'profit' => $profitConf['profit'],
                'popularize_profit' => $profitConf['popularize_profit'],
            ];
            $itemIdsTemp[] = $v['item_id'];
        }
        unset($v);

        $itemIds = array_diff($itemIds, $itemIdsTemp);
        if ($itemIds) {
            $itemsService = new ItemsService();
            $itemFilter = ['item_id' => $itemIds];
            $itemList = $itemsService->getSkuItemsList($itemFilter, 1, -1);
            $isMainCatIds = array_column($itemList['list'], 'item_main_cat_id');

            $itemsCategoryProfitService = new ItemsCategoryProfitService();
            $itemsCategoryProfitFilter = ['category_id' => $isMainCatIds];
            $itemsCategoryProfitListTemp = $itemsCategoryProfitService->getLists($itemsCategoryProfitFilter, '*', 1, -1);
            $itemsCategoryProfitList = array_column($itemsCategoryProfitListTemp, null, 'category_id');
            foreach ($itemList['list'] as $v) {
                if (!isset($itemsCategoryProfitList[$v['item_main_cat_id']])) {
                    continue;
                }
                $profitConf = json_decode($itemsCategoryProfitList[$v['item_main_cat_id']]['profit_conf'], 1);
                $itemsProfit[$v['item_id']] = [
                    'profit_type' => $itemsCategoryProfitList[$v['item_main_cat_id']]['profit_type'],
                    'profit' => $profitConf['profit'],
                    'popularize_profit' => $profitConf['popularize_profit'],
                ];
            }
        }

        return $itemsProfit;
    }

    /**
     * 导购分润计划结算时间
     *
     * @param int $companyId
     * @return void
     */
    public function orderProfitPlanCloseTime($companyId, $orderId)
    {
        $distributionService = new DistributionService();
        $orderProfitService = new OrderProfitService();

        $ordersSettingResult = $this->getOrdersSetting($companyId);
        $distributionConfig = $distributionService->getDistributionConfig($companyId);

        $day = ($ordersSettingResult['latest_aftersale_time'] ?? 0) + ($distributionConfig['distributor']['plan_limit_time'] ?? 0);

        $filter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
        ];
        $orderProfitUpdate = [
            'plan_close_time' => time() + 86400 * $day,
        ];

        $profit = $orderProfitService->getInfo($filter);
        if ($profit) {
            $result = $orderProfitService->updateOneBy($filter, $orderProfitUpdate);
            return $result;
        }
    }

    /**
     * 定时结算导购佣金
     *
     * @param int $companyId
     * @return void
     */
    public function scheduleSettleProfit()
    {
        $filter = [
            'plan_close_time|lte' => time(),
            'order_profit_status' => 1
        ];
        $pageSize = 100;
        $totalCount = $this->count($filter);
        if (!$totalCount) {
            return true;
        }
        $totalPage = ceil($totalCount / $pageSize);
        for ($i = 1; $i <= $totalPage; $i++) {
            $list = $this->getLists($filter, '*', 1, $pageSize);
            foreach ($list as $row) {
                $conn = app('registry')->getConnection('default');
                $conn->beginTransaction();
                try {
                    $this->updateBy(['id' => $row['id'], 'order_profit_status' => 1], ['order_profit_status' => 2]);
                    $this->orderItemsProfitRepository->updateBy(['order_id' => $row['order_id'], 'order_profit_status' => 1], ['order_profit_status' => 2]);
                    $conn->commit();
                } catch (\Exception $e) {
                    $conn->rollback();
                    app('log')->debug('定时执行导购分销佣金结算失败=>' . $e->getMessage());
                    app('log')->debug('定时执行导购分销佣金结算失败参数=>' . var_export($row, 1));
                }
            }
        }
        return true;
    }

    /**
     * Dynamically call the OrderProfitService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->orderProfitRepository->$method(...$parameters);
    }
}
