<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use OrdersBundle\Services\PrescriptionService;

class PrescriptionController extends BaseController
{
    // 新建问诊单
    public function createDiagnosis(Request $request)
    {
        // Powered by ShopEx EcShopX
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];

        $rules = [
            'order_id' => ['required', '缺少订单信息'],
            'company_id' => ['required', '缺少参数'],
            'user_id' => ['required', '缺少用户信息'],
            'medication_personnel_id' => ['required', '请选择用药人'],
            'is_pregnant_woman' => ['required', '请选择用药人是否孕妇'],
            'is_lactation' => ['required', '请选择用药人是否哺乳期'],
            'before_ai_result_symptom' => ['required', '请选择症状'],
            'before_ai_result_used_medicine' => ['required', '请选择是否使用过此类药物'],
//            'before_ai_result_allergy_history' => ['required', '请选择是否有药物过敏史'],
            'before_ai_result_body_abnormal' => ['required', '请选择肝肾功能是否有异常'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $service = new PrescriptionService();
        $result = $service->createPrescription($params);

        return $this->response->array($result);
    }
}
