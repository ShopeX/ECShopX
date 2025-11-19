<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\EmployeeService;
use DistributionBundle\Services\selfDeliveryService;
use Illuminate\Http\Request;
class SelfDeliveryStaff extends BaseController
{
    //获取配送员店铺列表
    public function getSelfDeliveryStaffDistributor(Request $request)
    {
        $operator_id = $request->input('self_delivery_operator_id',[]);
        $authInfo = $request->get('auth');

        $companyId = (int)$authInfo['company_id']; // 企业id
        $result = [];
        if(!$operator_id){
            return $this->response->array($result);
        }
        $params['operator_id'] = $operator_id;
        $params['company_id'] = $companyId;
        $selfDeliveryService = new selfDeliveryService();
        $data = $selfDeliveryService->getSelfDeliveryStaffDistributorList($params);
        if($data){
            $result = $data;
        }

        return $this->response->array($result);
    }

    public function getSelfDeliveryList(Request $request)
    {
        $authInfo = $request->get('auth');

        $companyId = (int)$authInfo['company_id']; // 企业id

        $operator_id = $request->input('self_delivery_operator_id',[]);
        $employeeService = new EmployeeService();
        $params = ['company_id'=>$companyId,'operator_id'=>$operator_id,'operator_type'=>'self_delivery_staff'];
        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_ids'] = '"distributor_id":"'.$inputData['distributor_id'].'"';
        }

        $result = $employeeService->getListStaff($params);

        return $this->response->array($result);
    }


}
