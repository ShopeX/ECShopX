<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 *  设置自动退款开启，自动同步到自营店铺
 */

namespace DistributionBundle\Listeners;

use DistributionBundle\Events\RefundFreightAutoZyEvent;
use DistributionBundle\Services\DistributorService;

class RefundFreightAutoZyListener
{
    /**
     * @param $event
     *  [
     *      'is_refund_freight' => 1|0,
     *  ]
     */
    public function handle(RefundFreightAutoZyEvent $event)
    {
        $data = $event->entities;
        if (isset($data['is_refund_freight']) && $data['is_refund_freight'] == 1) {
            $distributorService = new DistributorService();
            $fliter = [
                'distribution_type' => 0,
            ];
            try {
                $page = 1;
                do {
                    $list = $distributorService->getLists($fliter, $page, 100);
                    if (empty($list['list'])) {
                        break;
                    }
                    $distributors = array_column($list['list'], 'distributor_id');
                    $update = [
                        'is_refund_freight' => 1,
                        'updated' => time(),
                    ];
                    $distributorService->updateBy(['distributor_id' => $distributors], $update);
                    $page++;
                }while(true);

            }catch (\Exception $e) {

            }
        }

    }

}
