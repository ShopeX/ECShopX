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

namespace ThirdPartyBundle\Listeners\MarketingCenter;

use DistributionBundle\Events\DistributionEditEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;
use DistributionBundle\Entities\Distributor;

class DistributionEditPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param DistributionEditEvent $event
     * @return void
     */
    public function handle(DistributionEditEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $distributor_id = $event->entities['distributor_id'];
        $distributor = app('registry')->getManager('default')->getRepository(Distributor::class);
        $info = $distributor->lists(['company_id' => $company_id, 'distributor_id' => $distributor_id]);
        $input['store_bn'] = $info['list'][0]['shop_code'];
        $input['store_name'] = $info['list'][0]['name'];
        // $input['region_id'] = $info['list'][0]['regions_id'];
        $input['contract_phone'] = $info['list'][0]['mobile'];
        $input['lng'] = $info['list'][0]['lng'];
        $input['lat'] = $info['list'][0]['lat'];
        $input['is_deleted'] = ($info['list'][0]['is_valid'] == 'true') ? '0' : '1';
        $input['address'] = ($info['list'][0]['province'] ?? '').($info['list'][0]['city'] ?? '').($info['list'][0]['area'] ?? '').($info['list'][0]['address'] ?? '');

        foreach ($input as &$value) {
            if (is_int($value)) {
                $value = strval($value);
            }
            if (is_null($value)) {
                $value = '';
            }
            if (is_array($value) && empty($value)) {
                $value = '';
            }
        }
        $params[0] = $input;
        $request = new Request();
        $request->call($company_id, 'basics.store.proccess', $params);
    }
}
