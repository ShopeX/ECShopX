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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use PromotionsBundle\Interfaces\TurntableWinningPrize;
use PointBundle\Services\PointMemberService;

class TurntableWinningPrizePoint implements TurntableWinningPrize
{
    private $winning_prize;
    private $user_info;

    public function __construct($winning_prize, $user_info)
    {
        $this->winning_prize = $winning_prize;
        $this->user_info = $user_info;
    }

    //发放奖品操作
    public function grantPrize()
    {
        //发放积分
        try {
            $userId = $this->user_info['user_id'];
            $companyId = $this->user_info['company_id'];
            $points = $this->winning_prize['prize_value'];
            $pointMemberService = new PointMemberService();
            $result = $pointMemberService->addPoint($userId, $companyId, $points, 11, true, '大转盘抽奖获得');
            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
