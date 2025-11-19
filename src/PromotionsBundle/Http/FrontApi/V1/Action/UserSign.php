<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
