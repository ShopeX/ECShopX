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
use CompanysBundle\Services\EmailService;
use CompanysBundle\Events\CompanyCreateEvent;

class OnlineOpenSendEmailListener extends BaseListeners implements
    ShouldQueue
// class OnlineOpenSendEmailListener extends BaseListeners
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

        //收件人邮箱
        $to = $event->entities['email'] ?? '';

        if (!$to) {
            return false;
        }

        //标题
        $subject = '商派云店系统成功开通通知';

        $mobile = $event->entities['mobile'];
        $activeAt = date('Y-m-d', $event->entities['active_at']);
        $expiredAt = date('Y-m-d', $event->entities['expired_at']);
        $shopAdminUrl = config('common.shop_admin_url');

        //邮件内容
        $body = <<<EOF
<p>尊敬的用户:</p>
<p style="text-indent: 2em;">您已于{$activeAt}成功开通商派云店系统，有效期至{$expiredAt};</p>
<p style="text-indent: 2em;">请通过：<a href="{$shopAdminUrl}">{$shopAdminUrl}</a>进入管理端;</p>
<p style="text-indent: 2em;">账户：{$mobile};</p>
<p style="text-indent: 2em;">密码：注册所用密码;</p>
EOF;

        $emailService = new EmailService();
        $emailService->sendmail($to, $subject, $body);
    }
}
