<?php 

namespace ThirdPartyBundle\Listeners\DmCrm;

use OrdersBundle\Entities\NormalOrders;
use MembersBundle\Services\MemberService;
use OrdersBundle\Events\NormalOrderDeliveryEvent;
use ThirdPartyBundle\Services\DmCrm\PointService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;

/**
 *  订单发货完成，达摩crm预扣积分确认扣款
 */

class OrderDeliveryListener
{
     /**
     * Handle the event.
     *
     * @param NormalOrderDeliveryEvent $event
     * @return void
     */
    public function handle(NormalOrderDeliveryEvent $event)
    {
        return true;
        
        app('log')->debug("\n OrderDeliveryListener event=>:".var_export($event->entities, 1));
        try{
            $company_id = $event->entities['company_id'];
            $order_id = $event->entities['order_id'];
            // 达摩crm, 预扣积分/取消积分
            $ns = new DmCrmSettingService();
            if (!$ns->getDmCrmSetting($company_id)['is_open'] ?? '') {
                return false;
            }  
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
            if (!$orderInfo || $orderInfo['delivery_status'] != 'DONE') {
                app('log')->debug("\n OrderDeliveryListener event=>orderinfo:".var_export($orderInfo, true)); 
                return true;
            }
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