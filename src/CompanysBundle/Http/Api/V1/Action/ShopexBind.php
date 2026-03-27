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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\ShopexAdminBindService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShopexBind extends BaseController
{
    public function status(Request $request)
    {
        $user = app('auth')->user();
        if ($user->get('operator_type') !== 'admin') {
            throw new HttpException(403, '仅商家超级管理员可查看绑定状态');
        }
        $operatorId = (int) $user->get('operator_id');
        $service = new ShopexAdminBindService();

        return response()->json(['data' => $service->getStatusForOperatorId($operatorId)]);
    }

    public function bind(Request $request)
    {
        $user = app('auth')->user();
        if ($user->get('operator_type') !== 'admin') {
            throw new HttpException(403, '仅商家超级管理员可绑定 Shopex');
        }
        $operatorId = (int) $user->get('operator_id');
        $credentials = $request->only('username', 'password', 'agreement_id', 'product_model');
        $service = new ShopexAdminBindService();

        return response()->json(['data' => $service->bindForAdminOperator($operatorId, $credentials)]);
    }
}
