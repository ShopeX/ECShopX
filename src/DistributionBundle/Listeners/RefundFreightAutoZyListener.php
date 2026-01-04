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
