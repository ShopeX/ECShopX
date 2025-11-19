<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
