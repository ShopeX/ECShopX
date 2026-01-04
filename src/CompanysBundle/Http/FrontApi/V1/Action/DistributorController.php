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

namespace CompanysBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use WechatBundle\Services\DistributorWechatService;
use CompanysBundle\Services\SettingService;
use CompanysBundle\Ego\CompanysActivationEgo;

class DistributorController extends BaseController
{

    /**
     * 检查小程序账号是否绑定店务端
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function checkDistributor(Request $request)
    {
        $authInfo = $request->get('auth');

        $returnData = [
            'status' => false,
            'result' => [],
        ];

        $settingService = new SettingService();
        $result = $settingService->getDianwuSetting($authInfo['company_id']);
        if (!($result['dianwu_show_status'] ?? false)) {
            $returnData['msg'] = '未开启移动端显示店务端入口';
            return $this->response->array($returnData);
        }

        if (!$authInfo['user_id'] || !$authInfo['mobile']) {
            throw new ResourceException('请重新登录');
        }

        // 查询账户
        $filter = [
            'company_id' => $authInfo['company_id'],
            'mobile' => $authInfo['mobile'],
            'operator_type' => 'distributor',
        ];
        $company = (new CompanysActivationEgo())->check($authInfo['company_id']);
        if ($company['product_model'] != 'platform') {
            $filter['operator_type'] = 'staff';
        }

        $operatorService = new OperatorsService();
        $operator = $operatorService->getInfo($filter);
        if (!$operator) {
            return $this->response->array($returnData);
        }
        $filter['operator_id'] = $operator['operator_id'];
        $operator = $operatorService->getInfo($filter, true);
        $returnData['operator'] = $operator;
        if (!$operator || ($operator['operator_type'] == 'distributor' && empty($operator['distributor_ids']))) {
            $returnData['msg'] = '账号未绑定店铺';
            return $this->response->array($returnData);
        }

        // 查询是否绑定
        $wxFilter = [
            'company_id' => $authInfo['company_id'],
            'operator_id' => $operator['operator_id'],
            'app_type' => 'wxa',
        ];
        $service = new DistributorWechatService();

        $result = $service->getInfo($wxFilter);
        // 没有绑定则绑定
        if (empty($result)) {
            $result = $service->bindDistributorUser([
                'company_id' => $authInfo['company_id'],
                'app_id' => $authInfo['wxapp_appid'] ?? 'no_wxapp_app_id',
                'app_type' => 'wxa',
                'openid' => $authInfo['open_id'],
                'unionid' => $authInfo['unionid'],
                'operator_id' => $operator['operator_id'],
                'bound_time' => time(),
            ]);
        }
        $returnData['result'] = $result;
        $returnData['status'] = empty($result) ? false : true;

        return $this->response->array($returnData);
    }

    /**
     * 检查小程序账号是否绑定配送员端
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function checkDeliveryStaff(Request $request)
    {
        $authInfo = $request->get('auth');

        $returnData = [
            'status' => false,
            'result' => [],
        ];

//        $settingService = new SettingService();
//        $result = $settingService->getDianwuSetting($authInfo['company_id']);
//        if (!($result['dianwu_show_status'] ?? false)) {
//            $returnData['msg'] = '未开启移动端显示店务端入口';
//            return $this->response->array($returnData);
//        }

        if (!$authInfo['user_id'] || !$authInfo['mobile']) {
            throw new ResourceException('请重新登录');
        }

        // 查询账户
        $filter = [
            'company_id' => $authInfo['company_id'],
            'mobile' => $authInfo['mobile'],
            'operator_type' => 'self_delivery_staff',
        ];
//        $company = (new CompanysActivationEgo())->check($authInfo['company_id']);
//        if ($company['product_model'] != 'platform') {
//            $filter['operator_type'] = 'staff';
//        }

        $operatorService = new OperatorsService();
        $operator = $operatorService->getInfo($filter);
        if (!$operator) {
            return $this->response->array($returnData);
        }
        $filter['operator_id'] = $operator['operator_id'];
        $operator = $operatorService->getInfo($filter, true);
        $returnData['operator'] = $operator;
        if (!$operator || ($operator['operator_type'] == 'self_delivery_staff' && empty($operator['distributor_ids']))) {
            $returnData['msg'] = '账号未绑定店铺';
            return $this->response->array($returnData);
        }

        // 查询是否绑定
        $wxFilter = [
            'company_id' => $authInfo['company_id'],
            'operator_id' => $operator['operator_id'],
            'app_type' => 'wxa',
        ];
        $service = new DistributorWechatService();

        $result = $service->getInfo($wxFilter);
        // 没有绑定则绑定
        if (empty($result)) {
            $result = $service->bindDistributorUser([
                'company_id' => $authInfo['company_id'],
                'app_id' => $authInfo['wxapp_appid'] ?? 'no_wxapp_app_id',
                'app_type' => 'wxa',
                'openid' => $authInfo['open_id'],
                'unionid' => $authInfo['unionid'],
                'operator_id' => $operator['operator_id'],
                'bound_time' => time(),
            ]);
        }
        $returnData['result'] = $result;
        $returnData['status'] = empty($result) ? false : true;

        return $this->response->array($returnData);
    }

}
