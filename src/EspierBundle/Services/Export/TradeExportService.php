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

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use MembersBundle\Services\MemberService;
use OrdersBundle\Services\TradeService;
use EspierBundle\Services\ExportFileService;
use DistributionBundle\Services\DistributorService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use AftersalesBundle\Services\AftersalesRefundService;

class TradeExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $tradeService = new TradeService();
        $count = $tradeService->getTradeCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."trade";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['orderId', 'tradeId', 'transactionId'];
        $result = $exportService->exportCsv($fileName, $title, $orderList, $textFields);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'orderId' => '订单号',
            'tradeId' => '交易单号',
            'mobile' => '会员手机号',
            'user_name' => '会员名称',
            'totalFee' => '订单总金额',
            'payFee' => '订单实付金额',
            'discountFee' => '订单优惠金额',
            'transactionId' => '支付流水号',
            'payDate' => '支付时间',
            'body' => '交易描述',
            'detail' => '交易详情',
            'store_name' => '店铺名称',
            'shop_name' => '门店名称',
            'feeType' => '支付货币类型',
            'tradeState' => '交易状态',
            'payType' => '支付方式',
            'timeStart' => '交易开始时间',
            'timeExpire' => '交易结束时间',
            'tradeSourceType' => '交易单来源类型',
            'if_refund' => '是否退款',
        ];
        return $title;
    }
    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->getTitle();
        $tradeSourceType = [
            'normal' => '实体订单购买',
            'normal_groups' => '实体商品拼团支付',
            'normal_seckill' => '实体商品秒杀支付',
            'normal_community' => '社区订单购买',
            'membercard' => '会员卡购买',
            'service' => '服务订单购买',
            'groups' => '服务商品拼团支付',
            'order_pay' => '门店线下买单',
            'diposit' => '预存款购买'
        ];
        $tradeState = [
            'NOTPAY' => '未支付',
            'REFUND_PROCESS' => '退款中',
            'REFUND_SUCCESS' => '退款成功',
            'SUCCESS' => '支付成功',
        ];
        $payType = [
            'deposit' => '预存款',
            'wxpay' => '微信',
            'pos' => 'POS刷卡',
            'localPay' => '零元订单',
            'offline_pay' => '线下转账',
            'point' => '积分支付',
            'wxpaypos' => '微信扫码支付',
            'wxpayh5' => '微信H5支付',
            'wxpaypc' => '微信PC支付',
            'alipay' => '支付宝支付',
            'alipayh5' => '支付宝H5支付',
        ];
        $tradeService = new TradeService();
        $memberService = new MemberService();
        $shopsService = new ShopsService(new WxShopsService());
        $distributorService = new DistributorService();
        $aftersalesRefundService = new AftersalesRefundService();

        $limit = 500;
        $orderBy = ['time_start' => 'DESC'];
        $fileNum = ceil($count / $limit);

        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $data = $tradeService->getTradeList($filter, $orderBy, $limit, $j);

            $userIds = array_unique(array_column($data['list'], 'userId'));
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $shopIds = array_filter(array_unique(array_column($data['list'], 'shopId')));
            if ($shopIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'wx_shop_id' => $shopIds,
                ];
                $shopList = $shopsService->getShopsList($sFilter, 1, $limit);
                $shopData = array_column($shopList['list'], null, 'wxShopId');
            }

            $storeIds = array_filter(array_unique(array_column($data['list'], 'distributorId')));
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $tradeIds = array_column($data['list'], 'tradeId');
            if ($tradeIds) {
                $refunList = $aftersalesRefundService->getList(['trade_id' => $tradeIds, 'refund_status' => 'SUCCESS']);
                $refunList = array_column($refunList['list'], 'refund_bn', 'trade_id');
            }

            foreach ($data['list'] as $key => $value) {
                $username = $userData[$value['userId']]['username'] ?? '--';
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $username != '--' and $username = data_masking('truename', (string) $username);
                }
                foreach ($title as $k => $v) {
                    if (in_array($k, ['orderId', 'tradeId', 'transactionId']) && isset($value[$k])) {
                        // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                        $orderList[$key][$k] = $value[$k];
                    } elseif (in_array($k, ['totalFee', 'payFee', 'discountFee']) && isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k] / 100;
                    } elseif (in_array($k, ['timeStart', 'timeExpire']) && isset($value[$k]) && $value[$k]) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif ($k == "tradeSourceType" && isset($value[$k])) {
                        $orderList[$key][$k] = $tradeSourceType[$value[$k]] ?? '--';
                    } elseif ($k == "tradeState" && isset($value[$k])) {
                        $orderList[$key][$k] = $tradeState[$value[$k]] ?? '--';
                    } elseif ($k == "payType" && isset($value[$k])) {
                        // 特殊处理：当 payType='localPay' 且 payChannel='offline_pay' 时，显示为"线下转账"
                        if ($value[$k] == 'localPay' && isset($value['payChannel']) && $value['payChannel'] == 'offline_pay') {
                            $orderList[$key][$k] = '线下转账';
                        } else {
                            $orderList[$key][$k] = $payType[$value[$k]] ?? '--';
                        }
                    } elseif ($k == "store_name") {
                        $orderList[$key][$k] = $storeData[$value['distributorId']]['name'] ?? '--';
                    } elseif ($k == "shop_name") {
                        $orderList[$key][$k] = $shopData[$value['shopId']]['storeName'] ?? '--';
                    } elseif ($k == "user_name") {
                        $orderList[$key][$k] = $username;
                    } elseif ($k == "payDate") {
                        $orderList[$key][$k] = empty($value[$k]) ? '--' : $value[$k];
                    } elseif ($k == "if_refund") {
                        $orderList[$key][$k] = isset($refunList[$value['tradeId']]) ? '是' : '否';
                    } elseif (isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k];
                    } else {
                        $orderList[$key][$k] = '--';
                    }
                }
            }
            yield $orderList;
        }
    }
}
