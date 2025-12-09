<?php 

namespace ThirdPartyBundle\Listeners\DmCrm;

use GoodsBundle\Services\ItemsService;
use OrdersBundle\Entities\NormalOrders;
use MembersBundle\Services\MemberService;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Entities\NormalOrdersItems;
use ThirdPartyBundle\Services\DmCrm\OrderService;
use ThirdPartyBundle\Services\DmCrm\PointService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;

class TradeFinishListener
{
    public function handle(TradeFinishEvent $event)
    {
        try{
            $this->handlePoint($event);
            $this->handleSync($event);
        }catch(\Exception $e) {
            
        }
        
        return true;
    }


    public function handleSync(TradeFinishEvent $event)
    {
        app('log')->debug("\n TradeFinishListener event=>:".var_export($event->entities, 1));
        try{
            $company_id = $event->entities->getCompanyId();
            $order_id = $event->entities->getOrderId();
            // 达摩crm, 预扣积分/取消积分
            $ns = new DmCrmSettingService();
            if (!$ns->getDmCrmSetting($company_id)['is_open'] ?? '') {
                return false;
            }  
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
            $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
            if (!$orderInfo) {
                app('log')->debug("\n TradeFinishListener event=>orderinfo:".var_export($orderInfo, true)); 
                return true;
            }
            // 如果不存在预扣积分id，不是dm扣减订单，则不处理
            if (empty($orderInfo['dm_point_preid']) && ($orderInfo['point_fee'] > 0 || $orderInfo['point_use'] > 0)) {
                app('log')->debug("\n TradeFinishListener event=>orderinfo:".var_export($orderInfo, true)); 
                return true;
            }
            // 订单实付金额为0都订单也进行拦截,不同步到达摩crm
            // if ($orderInfo['total_fee'] <= 0) {
            //     app('log')->debug("\n TradeFinishListener event=>orderinfo:".var_export($orderInfo, true)); 
            //     return true;
            // }
    
            $orderItems = $normalOrdersItemsRepository->get($company_id, $order_id);
            $itemIds = array_column($orderItems, 'item_id');
            $itemsService = new ItemsService();
            $rs = $itemsService->itemsRepository->getLists(['item_id' => $itemIds], '*');
            $rsItems = array_column($rs, null, 'item_id');
            foreach($orderItems as $k => &$item) {
                if (isset($rsItems[$item['item_id']])) {
                    $item['item_name'] = $rsItems[$item['item_id']]['item_name'] ?? '';
                    $item['goods_bn'] = $rsItems[$item['item_id']]['goods_bn'] ?? '';
                    $item['item_bn'] = $rsItems[$item['item_id']]['item_bn'] ?? '';
                    $item['is_gift'] = $rsItems[$item['item_id']]['is_gift'] ?? '';
                }
                $item['item_fee_t'] = bcdiv($item['total_fee'], $item['num'], 5);
                // 如果是积分商城订单，商品都是赠品
                if ($orderInfo['order_class'] == 'pointsmall') {
                    $item['is_gift'] = 1;
                }
            }
            $orderInfo['items'] = $orderItems;
            $total_fee = $orderInfo['total_fee'];
            $freight_fee = $orderInfo['freight_fee'];
            $orderInfo['total_fee'] = $total_fee - $freight_fee;
            
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

            app('log')->debug("\n TradeFinishListener event=>orderInfo:".var_export($orderInfo, 1));
            $orderService = new OrderService($company_id);
            $orderService->syncOrder($orderInfo);
        }catch(\Exception $e) {
              app('log')->debug("\n TradeFinishListener event=>Exception:".var_export([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ], 1));
        }

        return true;
    }

    public function handlePoint(TradeFinishEvent $event)
    {
        app('log')->debug("\n OrderDeliveryListener event=>:".var_export($event->entities, 1));
        try{
            $company_id = $event->entities->getCompanyId();
            $order_id = $event->entities->getOrderId();
            // 达摩crm, 预扣积分/取消积分
            $ns = new DmCrmSettingService();
            if (!$ns->getDmCrmSetting($company_id)['is_open'] ?? '') {
                return false;
            }  
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);

            // 如果不存在预扣积分id，不是dm扣减订单，则不处理
            if (empty($orderInfo['dm_point_preid']) && ($orderInfo['point_fee'] > 0 || $orderInfo['point_use'] > 0)) {
                app('log')->debug("\n OrderDeliveryListener event=>orderinfo:".var_export($orderInfo, true)); 
                return true;
            }
            // 如果纯现金订单，不需要调用确认积分
            if (!empty($orderInfo['dm_point_preid'])) {
                $pointService = new PointService($company_id);
                $memberService = new MemberService();
                $filterMember = [
                    'user_id' => $orderInfo['user_id'],
                    'company_id' => $company_id,
                ];
                $memberInfo = $memberService->getMemberInfo($filterMember, false);
                $paramsData = [
                    'mobile' => $memberInfo['mobile'] ?? '',
                    'cardNo' => $memberInfo['dm_card_no'],
                    'preDeductionId' => $orderInfo['dm_point_preid'],
                ];
                $pointService->confirmPreparePoint($paramsData);
            }
        }catch(\Exception $e) {
            app('log')->debug("\n OrderDeliveryListener event=>Exception:".var_export([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ], 1));
        }

        return true;
    }

}
