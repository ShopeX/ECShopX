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

namespace CompanysBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Events\CompanyCreateEvent;
use CompanysBundle\Services\PrismIshopexService;
use CompanysBundle\Services\AuthService;

class OnlineOpenCallbackListener extends BaseListeners implements
    ShouldQueue
// class OnlineOpenCallbackListener extends BaseListeners
{
    /**
     * Handle the event.
     *
     * @param  CompanyCreateEvent $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        // 如果为数云模式，则不执行
        if (config('common.oem-shuyun')) {
            return false;
        }
        // if (!config('common.system_is_saas') || !config('common.system_open_online')) {
        if (!config('common.system_is_saas')) {
            return false;
        }
        $issue_id = $event->entities['issue_id'] ?? '';
        if (!$issue_id) {
            return false;
        }
        $authService = new AuthService();
        $url = $authService->getOuthorizeurl();
        $params = [
            'issue_id' => $issue_id,
            'url' => $url,
        ];
        $prismIshopexService = new PrismIshopexService();
        $result = $prismIshopexService->onlineOpenCallback($params);
        return $result;
    }
}
