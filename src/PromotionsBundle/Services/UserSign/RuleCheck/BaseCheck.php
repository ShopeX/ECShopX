<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services\UserSign\RuleCheck;

use Carbon\Carbon;

abstract class BaseCheck
{

    abstract public function finish(array $bag, array $ruleData): array;

    public function getTimeRange(array $ruleData,array $acData): array
    {
        $date = Carbon::now();
        $ruleData['frequency'] = (int)$ruleData['frequency'];
        switch ($ruleData['frequency']) {
            case 1:
                return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            case 2:
                $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
                $endOfWeek = $startOfWeek->copy()->addDays(6);
                return [$startOfWeek->getTimestamp(), $endOfWeek->getTimestamp()];
            case 3:
                return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
            default:
                return [$acData['begin_time'],$acData['end_time']];
        }

    }

}
