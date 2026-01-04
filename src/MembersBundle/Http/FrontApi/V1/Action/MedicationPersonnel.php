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

namespace MembersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use MembersBundle\Services\MedicationPersonnelService;
use Dingo\Api\Exception\ResourceException;

class MedicationPersonnel extends Controller
{
    public $service;

    public function __construct()
    {
        $this->service = new MedicationPersonnelService();
    }

    public function create(Request $request)
    {
        $params = $request->all();
        $rules = [
            'user_family_name' => ['required', '请填写正确的用药人姓名'],
            'user_family_id_card' => ['required', '请填写正确的用药人身份证号'],
            'user_family_age' => ['required', '请填写正确的用药人年龄'],
            'user_family_gender' => ['required', '请填写正确的用药人性别'],
            'user_family_phone' => ['required|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'relationship' => ['required', '请选择正确的与本人关系'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];

        $result = $this->service->create($params);
        return $this->response->array($result);
    }

    /**
     * 更新用药人信息
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $params = $request->all(['id', 'user_family_phone', 'relationship', 'user_family_name', 'user_family_id_card', 'user_family_age', 'user_family_gender']);
        $rules = [
            'id' => ['required', '缺少参数'],
            'user_family_name' => ['required', '请填写正确的用药人姓名'],
            'user_family_id_card' => ['required', '请填写正确的用药人身份证号'],
            'user_family_age' => ['required', '请填写正确的用药人年龄'],
            'user_family_gender' => ['required', '请填写正确的用药人性别'],
            'user_family_phone' => ['required|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'relationship' => ['required', '请选择正确的与本人关系'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $filter = ['id' => $params['id']];
        unset($params['id']);

        $result = $this->service->update($filter, $params);
        return $this->response->array($result);
    }

    /**
     * 获取用药人列表
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getList(Request $request)
    {
        $params = $request->all(['page', 'pageSize']);

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];

        $result = $this->service->getList($params);
        return $this->response->array($result);
    }

    /**
     * 获取用药人详情
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getDetail(Request $request)
    {
        $params = $request->all(['id']);
        if (empty($params['id'])) {
            throw new ResourceException(trans('MembersBundle/Members.missing_param'));
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];

        $result = $this->service->getDetail($params);
        return $this->response->array($result);
    }

    /**
     * 删除用药人
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function deleteMedicationPersonnel(Request $request)
    {
        $params = $request->all(['id']);
        if (empty($params['id'])) {
            throw new ResourceException(trans('MembersBundle/Members.missing_param'));
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];

        $result = $this->service->deleteMedicationPersonnel($params);
        return $this->response->array($result);
    }
}
