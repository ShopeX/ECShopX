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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use CompanysBundle\Entities\Operators;
use Illuminate\Http\Request;

use OpenapiBundle\Http\Controllers\Controller as Controller;
use CompanysBundle\Services\OperatorsService;

class Jurisdiction extends Controller
{
    public function __construct()
    {
        $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
    }

    // 用户从导购同步到本地
    public function sysuser(Request $request)
    {
        $params = $request->all();
        $params['company_id'] = $request->get('auth')['company_id'];

        $admin = $this->operatorsRepository->getInfo(['mobile' => $params['shopexid'], 'operator_type' => 'admin']);
        if ($admin['company_id'] != $params['company_id']) {
            $return = [
                'status' => false,
                'message' => '配置错误',
            ];
            $this->api_response('fail', '操作失败', $return, 'E0001');
        }

        // 根据同步key查询是否存在
        $operator = $this->operatorsRepository->getInfo(['mobile' => $params['mobile'], 'operator_type' => $params['operator_type']]);
        $is_ok = true;
        if ($operator) {
            if ($operator['company_id'] != $admin['company_id']) {
                $return = [
                    'status' => false,
                    'message' => '帐号已存在',
                ];
                $this->api_response('fail', '操作失败', $return, 'E0001');
            }

            $is_ok = $this->updateuser($params);  // 更新
        } else {
            $is_ok = $this->adduser($params); // 添加
        }

        // 返回是否成功
        if ($is_ok) {
            $return = [
                'status' => true,
                'message' => '保存成功',
            ];
            $this->api_response('true', '操作成功', $return, 'E0000');
        } else {
            $return = [
                'status' => false,
                'message' => '保存失败',
            ];
            $this->api_response('fail', '操作失败', $return, 'E0001');
        }
    }

    // 用户添加
    private function adduser($params)
    {
        $add['operator_type'] = $params['operator_type'];
        $add['login_name'] = $params['login_name'];
        $add['mobile'] = $params['mobile'];
        $add['company_id'] = $params['company_id'];
        $add['username'] = $params['username'];
        $add['regionauth_id'] = 0;
        $add['shop_ids'] = [];
        $add['distributor_ids'] = [];
        $add['password'] = password_hash(uniqid(), PASSWORD_DEFAULT);
        if ($params['operator_type'] == 'admin') {
            $operatorData = [
                'eid' => $params['eid'],
                'mobile' => $params['mobile'],
                'passport_uid' => $params['passport_uid'],
                'password' => password_hash(uniqid(), PASSWORD_DEFAULT),
            ];

            $operatorService = new OperatorsService();
            $result = $operatorService->open($operatorData);
        } else {
            $result = $this->operatorsRepository->create($add);
        }
        if (!empty($result)) {
            return true;
        } else {
            return false;
        }
    }

    // 用户修改
    private function updateuser($params)
    {
        $filter['company_id'] = $params['company_id'];
        $filter['mobile'] = $params['mobile'];

        $update['username'] = $params['username'];
        $update['password'] = $params['password'];

        $result = $this->operatorsRepository->updateOneBy($filter, $update);
        if (!empty($result)) {
            return true;
        } else {
            return false;
        }
    }

    // 用户删除
    private function deluser($params)
    {
        $filter['company_id'] = $params['company_id'];
        $filter['mobile'] = $params['mobile'];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $operatorData = $this->operatorsRepository->getInfo($filter);
            $operator = $this->operatorsRepository->deleteBy(['operator_id' => $operatorData['operator_id'], 'company_id' => $params['company_id']]);
            $employeeRelRole = $this->employeeRelRoleRepository->deleteBy(['operator_id' => $operatorData['operator_id'], 'company_id' => $params['company_id']]);
            if ($operator && $employeeRelRole) {
                $conn->commit();
                return true;
            }
        } catch (\Exception $e) {
            $conn->rollback();
            return false;
        }
    }

    public function getuser(Request $request)
    {
        $params = $request->all();
        $filter['mobile'] = $params['mobile'];
        $filter['operator_type'] = $params['type'];
        #普通用户
        $is_ok = false;
        $return = [];
        if ($filter['operator_type'] == 'staff') {
            $return = $this->operatorsRepository->getInfo($filter);
            if (!empty($return)) {
                $is_ok = true;
            }
        }
        if ($is_ok) {
            $this->api_response('true', '操作成功', $return, 'E0000');
        } else {
            $this->api_response('fail', '操作失败', $return, 'E0001');
        }
    }
}
