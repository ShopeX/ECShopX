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

namespace WorkWechatBundle\Http\Controllers;

use WorkWechatBundle\Services\WorkWechatVerifyDomainService;

class WorkWechatVerify
{
    public function domain($verify_name)
    {
        $verify_domain_service = new WorkWechatVerifyDomainService();
        $verify_info = $verify_domain_service->getVerifyInfoByName($verify_name);

        if (!$verify_info) {
            return response('', 404);
        }

        return response($verify_info['contents'], 200)
            ->header('Content-Type', 'text/plain');
    }
}
