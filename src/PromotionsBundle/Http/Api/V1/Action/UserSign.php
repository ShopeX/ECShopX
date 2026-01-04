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

namespace PromotionsBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use PromotionsBundle\Services\UserSign\UserSignService;

class UserSign extends BaseController
{

    public function addUserRule(Request $request)
    {
        // ShopEx EcShopX Core Module
        $params = $request->all();
        $service = new UserSignService();
        $service->createUserSignRule($params);
        return $this->response->array(['status'=>true]);
    }

    // /wxapp/sign/weekly/list
    public function getList(Request $request)
    {
        $params = $request->all();
        $company_id = app('auth')->user()->get('company_id');
        $service = new UserSignService();
        $data = $service->getUserSignRule(['company_id'=>$company_id]);
        return $this->response->array($data);
    }

    public function delUserRule(Request $request)
    {
        $params = $request->all();

        $service = new UserSignService();
        $service->delUserRule($params['id']);
        return $this->response->array(['status'=>true]);
    }

}
