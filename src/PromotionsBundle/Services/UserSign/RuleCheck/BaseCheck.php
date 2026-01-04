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
