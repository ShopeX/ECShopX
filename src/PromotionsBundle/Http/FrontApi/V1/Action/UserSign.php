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

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use PromotionsBundle\Services\TurntableService;
use PromotionsBundle\Services\UserSign\UserSignService;

class UserSign extends Controller
{

    // /wxapp/sign  用户签到
    public function signIn(Request $request)
    {
        $user_info = $request->get('auth');

        $turntable_services = new UserSignService();

        $result = $turntable_services->signIn($user_info['user_id'],$user_info['company_id']);

        return $this->response->array($result);
    }

    // /wxapp/sign/weekly/list
    public function getUserSignList(Request $request)
    {
        $user_info = $request->get('auth');
        $turntable_services = new UserSignService();
        $list = $turntable_services->getWeeklySignInStatus($user_info['user_id']);
        $days = $turntable_services->getConsecutiveDays($user_info['user_id']);
        return $this->response->array(['days' => $days, 'list' => $list]);
    }

}
