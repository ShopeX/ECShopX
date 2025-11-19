<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;

use MembersBundle\Services\ShopRelMemberService;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class TradeFinishLinkMember extends BaseListeners implements ShouldQueue
{
    // ShopEx EcShopX Service Component
    use GetOrderServiceTrait;
    protected $queue = 'default';

    public function handle(TradeFinishEvent $event)
    {
        // ShopEx EcShopX Service Component
        app('log')->debug('分店商品关联会员信息start:'.$event->entities->getPayType());
        // 积分支付订单不需要
//        if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
//            return true;
//        }

        $params['user_id'] = $event->entities->getUserId();
        $params['company_id'] = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();

        $shop_id = $event->entities->getShopId();
        $distributor_id = $event->entities->getDistributorId();

        app('log')->debug('相关的参数：user_id: '.$params['user_id'].' company_id: '. $params['company_id'].' orderId: '.$orderId.' shop_id: '.$shop_id.' distributor_id: ' .$distributor_id);

        try {
            if ($distributor_id) {
                $params['shop_id'] = $distributor_id;
                $params['shop_type'] = 'distributor';
            } else {
                $params['shop_id'] = $shop_id;
            }

            app('log')->debug('关联会员参数:'.var_export($params, 1));

            if ($params['shop_id']) {
                $service = new ShopRelMemberService();
                if ($service->count($params)) {
                    app('log')->debug('已有相关的数据');
                    return true;
                }
                app('log')->debug('创建店铺相关数据'. var_export($params));
                return $service->create($params);
            }
        } catch (\Exception $e) {
            app('log')->debug('订单号:'.$orderId.', 状态更新错误: '.$e->getMessage());
        }

        return true;
    }
}
