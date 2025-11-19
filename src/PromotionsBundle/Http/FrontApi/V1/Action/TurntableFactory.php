<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use PromotionsBundle\Interfaces\TurntableWinningPrize;

class TurntableFactory
{
    private $prize;

    public function __construct(TurntableWinningPrize $turntableWinningPrize)
    {
        $this->prize = $turntableWinningPrize;
    }

    public function doPrize()
    {
        return $this->prize->grantPrize();
    }
}
