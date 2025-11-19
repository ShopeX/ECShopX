<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // Powered by ShopEx EcShopX
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'MembersBundle\Events\SyncWechatFansEvent' => [
            'MembersBundle\Listeners\SyncWechatFansListener',
        ],
        'MembersBundle\Events\SyncWechatTagsEvent' => [
            'MembersBundle\Listeners\SyncWechatTagsListener',
        ],
        'MembersBundle\Events\CreateMemberSuccessEvent' => [
            'PromotionsBundle\Listeners\CreateMemberSuccessPromotions',//创建会员成功促销
            'PromotionsBundle\Listeners\CreateMemberSuccessSendMembercard',//创建会员成功送付费会员卡
            'MembersBundle\Listeners\CreateMemberSuccessNotice',//创建会员成功消息通知
            'MembersBundle\Listeners\RegisterNumStatsListener', // 创建会员成功记录千人千码来源统计
            'MembersBundle\Listeners\RegisterPointListener', // 创建会员送积分
        ]
    ];
}
