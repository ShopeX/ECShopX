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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ThirdPartyBundle\Http\Controllers\Controller;
use ThirdPartyBundle\Jobs\MedicineAuditResultJob;
use ThirdPartyBundle\Services\Kuaizhen580Center\Src\DiagnosisService;

class Kuaizhen580 extends Controller
{
    /**
     * 4.14 同步药品信息回推错误数据接口
     * 580回传药品审核结果
     * 第三方同步药品数据不正确，580将错误结果推送给第三方。
     * 举例：推送500条数据 10条A类型错误 10条B类型错误 480条正确 那么会推送3次
     * 错误和正确的数据都会推
     * @param Request $request
     */
    public function medicineAuditResult(Request $request)
    {
        // KEY: U2hvcEV4
        $input = $request->all();
        if (empty($input)) {
            $this->KzResponse(500, '参数错误');
        }
        if (empty($input['medicineIds'])) {
            $this->KzResponse(500, '缺少参数');
        }
        if (is_string($input['medicineIds'])) {
            $input['medicineIds'] = json_decode($input['medicineIds'], true);
        }

        // 插入队列处理
        $gotoJob = (new MedicineAuditResultJob($input))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        $this->KzResponse(0, '操作成功');
    }

    /**
     * 4.10 处方推送接口（580开方/重开时推送）
     * 第三方业务系统提供处方信息推送接口给580，由580推送处方信息给第三方，第三方做新增保存操作，重开的时候做编辑操作。
     * @param Request $request
     */
    public function prescriptionMedication(Request $request)
    {
        // KEY: U2hvcEV4
        $input = $request->all();
        app('log')->debug('prescriptionMedication--->>>' . json_encode($input));

        $this->KzResponse(0, '操作成功');
    }

    /**
     * 4.11 问诊信息推送接口-第三方提供（580问诊结束时推送）
     */
    public function diagnosisFinish(Request $request)
    {
        $input = $request->all();
        app('log')->debug('diagnosisFinish--->>>' . json_encode($input));

        $rules = [
            'bizOrderId' => ['required', '缺少订单号'],
        ];
        $error = validator_params($input, $rules);
        if ($error) {
            $this->KzResponse(500, $error);
        }

        $diagnosisService = new DiagnosisService();
        $diagnosisService->diagnosisFinish($input);

        $this->KzResponse(0, '操作成功');
    }

    /**
     * 4.12 处方作废
     * @param Request $request
     */
    public function prescriptionMedicationDelete(Request $request)
    {
        $input = $request->all();
        app('log')->debug('prescriptionMedicationDelete--->>>' . json_encode($input));

        $rules = [
            'bizOrderId' => ['required', '缺少订单号'],
        ];
        $error = validator_params($input, $rules);
        if ($error) {
            $this->KzResponse(500, $error);
        }

        $diagnosisService = new DiagnosisService();
        $diagnosisService->prescriptionMedicationDelete($input);

        $this->KzResponse(0, '操作成功');
    }

    /**
     * 4.15 医生拒绝开方
     * @param Request $request
     */
    public function refusePrescribe(Request $request)
    {
        $input = $request->all();
        app('log')->debug('refusePrescribe--->>>' . json_encode($input));

        $rules = [
            'bizOrderId' => ['required', '缺少订单号'],
        ];
        $error = validator_params($input, $rules);
        if ($error) {
            $this->KzResponse(500, $error);
        }

        $diagnosisService = new DiagnosisService();
        $diagnosisService->refusePrescribe($input);

        $this->KzResponse(0, '操作成功');
    }

    /**
     * 4.16 处方开具且审核后推送接口
     * @param Request $request
     */
    public function prescriptionMedicationAndAudit(Request $request)
    {
        $input = $request->all();
        app('log')->debug('prescriptionMedicationAndAudit--->>>' . json_encode($input));

        $rules = [
            'bizOrderId' => ['required', '缺少订单号'],
        ];
        $error = validator_params($input, $rules);
        if ($error) {
            $this->KzResponse(500, $error);
        }

        $diagnosisService = new DiagnosisService();
        $diagnosisService->prescriptionMedicationAndAudit($input);

        $this->KzResponse(0, '操作成功');
    }

    /**
     * 4.18 患者取消订单
     * 患者进行图文/视频问诊时遇到排队页面，点击取消订单时会触发该接口调用，第三方接收到接口调用后将对应订单号（bizOrderId）的问诊状态为用户取消问诊。
     * @return JsonResponse
     */
    public function cancelDiagnosis(Request $request)
    {
        $this->KzResponse(0, '操作成功');
    }

    public function KzResponse($msg = '', $code = 0, $data = null)
    {
        $this->data_format = 'json';
        $result['err'] = $code;
        $result['errmsg'] = $msg;
        if ($data) {
            $result['data'] = $data;
        }
        $this->return_date($result);
    }

    public function return_date($data)
    {
        $this->_header('application/json');
        $result = json_encode($data);
        echo $result;
        exit();
    }
}
