<?php 

namespace ThirdPartyBundle\Listeners\DmCrm;

use GoodsBundle\Services\ItemsService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use AftersalesBundle\Entities\AftersalesDetail;
use AftersalesBundle\Services\AftersalesService;
use ThirdPartyBundle\Services\DmCrm\OrderService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use ThirdPartyBundle\Events\TradeRefundFinishEvent;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;


class TradeRefundFinishListener 
{
    public function handle(TradeRefundFinishEvent $event) 
    {
        app('log')->debug("\n TradeRefundFinishListener event=>:".var_export($event->entities, 1)); 
        try{
            $company_id = $event->entities['company_id'];
            $order_id = $event->entities['order_id'];
            $refund_bn  = $event->entities['refund_bn'];
            $aftersaleRefund = $event->entities;
            // 达摩crm, 预扣积分/取消积分
            $ns = new DmCrmSettingService();
            if (!$ns->getDmCrmSetting($company_id)['is_open'] ?? '') {
                return false;
            }  
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
            $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
            if (!$orderInfo) {
                app('log')->debug("\n TradeRefundFinishListener event=>orderinfo:".var_export($orderInfo, true)); 
                return true;
            }
            // 如果不存在预扣积分id，不是dm扣减订单，则不处理
            if (empty($orderInfo['dm_point_preid']) && ($orderInfo['point_fee'] > 0 || $orderInfo['point_use'] > 0)) {
                app('log')->debug("\n TradeRefundFinishListener event=>orderinfo:".var_export($orderInfo, true)); 
                return true;
            }
            // 订单实付金额为0都订单也进行拦截,不同步到达摩crm
            // if ($orderInfo['total_fee'] <= 0) {
            //     app('log')->debug("\n TradeRefundFinishListener event=>orderinfo:".var_export($orderInfo, true)); 
            //     return true;
            // }
            $orderItems = $normalOrdersItemsRepository->get($company_id, $order_id);
            $itemIds = array_column($orderItems, 'item_id');
            $itemsService = new ItemsService();
            $rs = $itemsService->itemsRepository->getLists(['item_id' => $itemIds], '*');
            $rsItems = array_column($rs, null, 'item_id');

            $orderService = new OrderService($company_id);

            // 导购数据
            if ($orderInfo['salesman_id']) {
                $ss = new SalespersonService(); 
                $salesmanInfo = $ss->getInfo(['company_id' => $company_id, 'salesperson_id' => $orderInfo['salesman_id'], 'salesperson_type' => 'shopping_guide']);
                $orderInfo['clerkCode'] = $salesmanInfo['work_userid'];
                $orderInfo['clerkName'] = $salesmanInfo['name'];
            }
            
            // 导购店铺数据
            $ds = new DistributorService();
            if ($orderInfo['sale_salesman_distributor_id']) {
                $distributorInfo = $ds->getInfo(['company_id' => $company_id, 'distributor_id' => $orderInfo['sale_salesman_distributor_id']]);
                $orderInfo['storeCode'] = $distributorInfo['shop_code'];
                $orderInfo['storeName'] = $distributorInfo['name'];
            }
            // 售后积分
            $orderInfo['usedMemberPoints'] = -$aftersaleRefund['refund_point'] ?? 0;

            // 如果是售前退款，没有售后详情数据，所以售后退的数据就是订单详情数据
            if (isset($event->entities['aftersales_bn']) && !empty($event->entities['aftersales_bn'])) {
                // 售后数据
                $aftersalesService = new AftersalesService();
                $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class); 
                $afInfo = $aftersalesService->get(['company_id' => $company_id ,'aftersales_bn' => $event->entities['aftersales_bn']]);
                app('log')->debug("\n TradeRefundFinishListener event=>afInfo:".var_export($afInfo, 1));
                $afDetail = $aftersalesDetailRepository->getList(['company_id' => $company_id ,'aftersales_bn' => $event->entities['aftersales_bn']]);
                app('log')->debug("\n TradeRefundFinishListener event=>afDetail:".var_export($afDetail, 1));
                $afDetailItems = array_column($afDetail['list'], null, 'item_id');
                $afDetailItemsItemIds = array_column($afDetail['list'], 'item_id');
                foreach($orderItems as $k => $item) {
                    if (!in_array($item['item_id'], $afDetailItemsItemIds)) {
                        unset($orderItems[$k]); 
                    }
                }
                $orderItems = array_values($orderItems);
                app('log')->debug("\n TradeRefundFinishListener event=>aftersale_bn:".var_export($orderItems, 1));
                foreach($orderItems as $k => &$item) {
                    if (in_array($item['item_id'], $afDetailItemsItemIds)) {
                        $item['item_name'] = $rsItems[$item['item_id']]['item_name'] ?? '';
                        $item['goods_bn'] = $rsItems[$item['item_id']]['goods_bn'] ?? '';
                        $item['item_bn'] = $rsItems[$item['item_id']]['item_bn'] ?? '';
                        $item['item_fee_t'] = -bcdiv($item['total_fee'] , $item['num'], 5);
                        $item['item_fee'] = -$item['item_fee'];
                        $item['total_fee'] = -$afDetailItems[$item['item_id']]['refund_fee'] ?? '';
                        $item['is_gift'] = $rsItems[$item['item_id']]['is_gift'] ?? '';
                        $item['num'] = -$afDetailItems[$item['item_id']]['num'] ?? ''; // 位置不能改变,↑上面用了处理数据
                    }
                    // 如果是积分商城订单，商品都是赠品
                    if ($orderInfo['order_class'] == 'pointsmall') {
                        $item['is_gift'] = 1;
                    }
                }
                $orderInfo['items'] = $orderItems;
                $orderInfo['refund_bn'] = $refund_bn;
                $orderInfo['freight_fee'] = 0;
                // 退款的话，total_fee 就是负数，并且是退款单都数据，如果退款运费存在
                if ($aftersaleRefund['return_freight'] == 1) {
                    // 退运费
                    $orderInfo['freight_fee'] = -$aftersaleRefund['freight'];
                }
                $orderInfo['total_fee'] = -$aftersaleRefund['refund_fee'];

                if (isset($afInfo['aftersales_type']) && $afInfo['aftersales_type'] == 'REFUND_GOODS') {
                    app('log')->debug("\n TradeRefundFinishListener event=>orderInfo:".var_export($orderInfo, 1));
                    $orderService->syncAfter($orderInfo); 
                }else {
                    // 仅退款
                    app('log')->debug("\n TradeRefundFinishListener event=>orderInfo:".var_export($orderInfo, 1));
                    $orderService->syncForwardAfter($orderInfo);
                }
            }else {
                // 售前退款都是仅退款
                foreach($orderItems as $k => &$item) {
                    $item['item_name'] = $rsItems[$item['item_id']]['item_name'] ?? '';
                    $item['goods_bn'] = $rsItems[$item['item_id']]['goods_bn'] ?? '';
                    $item['item_bn'] = $rsItems[$item['item_id']]['item_bn'] ?? '';
                    $item['item_fee'] = -$item['item_fee'];
                    $item['item_fee_t'] = -bcdiv($item['total_fee'] , $item['num'], 5);
                    $item['total_fee'] = -$item['total_fee'];
                    // $item['price'] = $item['price'];
                    $item['is_gift'] = $rsItems[$item['item_id']]['is_gift'] ?? '';
                    $item['num'] = -$item['num'];
                    // 如果是积分商城订单，商品都是赠品
                    if ($orderInfo['order_class'] == 'pointsmall') {
                        $item['is_gift'] = 1;
                    }
                }
                $orderInfo['items'] = $orderItems;
                $orderInfo['refund_bn'] = $refund_bn;
                $orderInfo['freight_fee'] = 0;
                if ($aftersaleRefund['return_freight'] == 1) {
                    // 退运费
                    $orderInfo['freight_fee'] = -$aftersaleRefund['freight'];
                }
                $orderInfo['total_fee'] = -$aftersaleRefund['refund_fee'];

                app('log')->debug("\n TradeRefundFinishListener event=>orderInfo:".var_export($orderInfo, 1));
                $orderService->syncForwardAfter($orderInfo);
            }
        }catch(\Exception $e) {
            app('log')->debug("\n TradeRefundFinishListener event=>Exception:".var_export([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ], 1));
        }
        
        return true;
    }

}