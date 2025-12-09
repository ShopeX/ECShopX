<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // ShopEx framework
    /**
     * saasErp 事件
     *
     * @var array
     */
    protected $listen = [
        'ThirdPartyBundle\Events\TradeUpdateEvent' => [
            'ThirdPartyBundle\Listeners\TradeUpdateSendSaasErp', // 订单更新发送到saasErp
        ],

        'ThirdPartyBundle\Events\TradeRefundEvent' => [
            'ThirdPartyBundle\Listeners\TradeRefundSendSaasErp', // 退款申请发送到saasErp
        ],

        'ThirdPartyBundle\Events\TradeAftersalesEvent' => [
            'ThirdPartyBundle\Listeners\TradeAftersalesSendSaasErp', // 售后申请发送到saasErp
        ],

        'ThirdPartyBundle\Events\TradeAftersalesCancelEvent' => [
            'ThirdPartyBundle\Listeners\TradeAftersaleCancelSendSaasErp', //售后取消
        ],

        'ThirdPartyBundle\Events\TradeAftersalesLogiEvent' => [
            'ThirdPartyBundle\Listeners\TradeAfterLogiSendSaasErp', //退货物流信息发送到saasErp
        ],

        'ThirdPartyBundle\Events\TradeRefundCancelEvent' => [
            'ThirdPartyBundle\Listeners\TradeRefundCancelSendSaasErp', //退款取消
        ],

        'ThirdPartyBundle\Events\TradeAftersalesUpdateEvent' => [
            'ThirdPartyBundle\Listeners\TradeAftersaleUpdateSendSaasErp', //售后状态更新
        ],

        'ThirdPartyBundle\Events\CustomDeclareOrderEvent' => [
            'ThirdPartyBundle\Listeners\RealTimeDataUpload', //清关成功验签上传
        ],

        'OrdersBundle\Events\NormalOrderAddEvent' => [
            // 'PaymentBundle\Listeners\OfflinePaymentCreate',
            'ThirdPartyBundle\Listeners\MarketingCenter\OrderAddPushMarketingCenter',
        ],
        'OrdersBundle\Events\NormalOrderDeliveryEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\OrderDeliveryPushMarketingCenter',
            "ThirdPartyBundle\Listeners\DmCrm\OrderDeliveryListener", // 订单发货完成，达摩crm确认扣除积分
        ],
        'OrdersBundle\Events\NormalOrderConfirmReceiptEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\OrderConfirmReceiptPushMarketingCenter',
            "ThirdPartyBundle\Listeners\ShopexCrm\SyncConfirmReceiptOrder",
            // "ThirdPartyBundle\Listeners\DmCrm\OrderFinishListener", // 订单完成，推送订单到达摩crm
        ],
        'ThirdPartyBundle\Events\TradeAftersalesRefuseEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\TradeAftersalesRefusePushMarketingCenter',
        ],
        'GoodsBundle\Events\ItemAddEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\ItemAddPushMarketingCenter',
        ],
        'DistributionBundle\Events\DistributionAddEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\DistributionAddPushMarketingCenter',
        ],
        'DistributionBundle\Events\DistributionEditEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\DistributionEditPushMarketingCenter',
        ],
        'ThirdPartyBundle\Events\TradeRefundFinishEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\TradeRefundFinishPushMarketingCenter',
             "ThirdPartyBundle\Listeners\DmCrm\TradeRefundFinishListener", // 售后订单完成，推送售后订单到达摩crm
        ],
        'GoodsBundle\Events\ItemDeleteEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\ItemDelPushMarketingCenter',
        ],
        'GoodsBundle\Events\ItemBatchEditStatusEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\ItemBatchEditStatusPushMarketingCenter',
            'GoodsBundle\Listeners\ItemsApproveStatusSync', // 商品状态同步
        ],
        'ThirdPartyBundle\Events\ScheduleCancelOrdersEvent' => [
            'ThirdPartyBundle\Listeners\MarketingCenter\ScheduleCancelOrdersPushMarketingCenter',
        ],
        'MembersBundle\Events\CreateMemberSuccessEvent' => [
            "ThirdPartyBundle\Listeners\ShopexCrm\SyncAddMember"
        ],
        'MembersBundle\Events\UpdateMemberSuccessEvent' => [
            "ThirdPartyBundle\Listeners\ShopexCrm\SyncUpdateMember"
        ],
    ];
}
