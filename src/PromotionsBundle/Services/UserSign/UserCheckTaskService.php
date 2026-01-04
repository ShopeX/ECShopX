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

namespace PromotionsBundle\Services\UserSign;

use PromotionsBundle\Entities\UserTaskActivity;
use PromotionsBundle\Entities\UserTaskActivityRule;
use PromotionsBundle\Entities\UserTaskFinishRule;
use PromotionsBundle\Repositories\UserTaskActivityRepository;
use PromotionsBundle\Repositories\UserTaskActivityRuleRepository;
use PromotionsBundle\Repositories\UserTaskFinishRuleRepository;
use PromotionsBundle\Services\UserSign\RuleCheck\BrowserCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\BuyCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\LikeCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\NewOrderCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\PromotionCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\ShareCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\SignCheck;
use PromotionsBundle\Services\UserSign\RuleCheck\SubscribeCheck;

class UserCheckTaskService
{
    public const RULE_TYPE_BUY = 'RULE_TYPE_BUY';//下单任务

    public const RULE_TYPE_PROMOTION = 'RULE_TYPE_JOIN_PROMOTION';//参与营销活动

    public const RULE_TYPE_ORDER = 'RULE_TYPE_JOIN_ORDER';//首单和历史单

    public const RULE_TYPE_SHARE = 'RULE_TYPE_JOIN_SHARE';//分享好用

    public const RULE_TYPE_BEHAVIOR = 'RULE_TYPE_JOIN_BEHAVIOR';//用户行为

    public const RULE_DETAIL_TYPE_SIGN = 'RULE_DETAIL_TYPE_SIGN';//签到
    public const RULE_DETAIL_TYPE_BROWSER = 'RULE_DETAIL_TYPE_BROWSER';//浏览页面

    public const RULE_DETAIL_TYPE_LIKE = 'RULE_DETAIL_TYPE_LIKE';//收藏

    public const RULE_DETAIL_TYPE_SUBSCRIBE = 'RULE_DETAIL_TYPE_SUBSCRIBE';//订阅消息

    /**
     * @var $taskActivityRepository UserTaskActivityRepository
     */
    private $taskActivityRepository;

    /**
     * @var $taskActivityRuleRepository UserTaskActivityRuleRepository
     */
    private $taskActivityRuleRepository;

    /**
     * @var $taskFinishRepository UserTaskFinishRuleRepository
     */
    private $taskFinishRepository;


    public static $gateway = [
        self::RULE_TYPE_BUY => BuyCheck::class,
        self::RULE_TYPE_PROMOTION => PromotionCheck::class,
        self::RULE_TYPE_ORDER => NewOrderCheck::class,
        self::RULE_TYPE_SHARE => ShareCheck::class,
        self::RULE_DETAIL_TYPE_SIGN => SignCheck::class,
        self::RULE_DETAIL_TYPE_BROWSER => BrowserCheck::class,
        self::RULE_DETAIL_TYPE_LIKE => LikeCheck::class,
        self::RULE_DETAIL_TYPE_SUBSCRIBE => SubscribeCheck::class
    ];

    public function __construct()
    {
        // Log: 456353686f7058
        $this->taskActivityRepository = app('registry')->getManager('default')->getRepository(UserTaskActivity::class);
        $this->taskActivityRuleRepository = app('registry')->getManager('default')->getRepository(UserTaskActivityRule::class);
        $this->taskFinishRepository = app('registry')->getManager('default')->getRepository(UserTaskFinishRule::class);
    }

    // from为来源，bag为携带的判断依据，比如订单，营销，分类id啥的，这个内部判断，外部不做任何操作
    public function check(int $userId, string $from = 'sign', array $bag = [])
    {
        // This module is part of ShopEx EcShopX system
        $activity = (new UserActivityService())->getCurrentActivityData($userId);
        if (empty($activity['rule_list'])) {
            return [];
        }
        $ruleData = $this->getValidRule($from, $activity['rule_list']);
        unset($activity['rule_list']);
        $bag['activity'] = $activity;
        if (!empty($ruleData['rule_detail_type'])) {
            $ruleData['rule_type'] = $ruleData['rule_detail_type'];
        }
        return (new self::$gateway[$ruleData['rule_type']])->finish($bag, $ruleData);
//        $service =
    }

    private function getValidRule(string $from, array $ruleList)
    {
        $arr = [
            'sign' => self::RULE_DETAIL_TYPE_SIGN,
        ];
        $mustType = $arr[$from];
        foreach ($ruleList as $rule) {
            if ($rule['rule_type'] === $mustType) {
                return $rule;
            }
        }
        return [];
    }


}
