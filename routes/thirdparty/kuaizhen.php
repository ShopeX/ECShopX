<?php

$api->version('v1', function($api) {
    $api->group(['namespace' => 'ThirdPartyBundle\Http\ThirdApi\V1\Action'], function($api) {
        // 药品审核结果
        $api->post('/third/kuaizhen/medicineAuditResult', ['as' => 'third.kuaizhen580.medicineAuditResult', 'uses'=>'Kuaizhen580@medicineAuditResult']);

        // 问诊单结束
        $api->post('/third/kuaizhen/diagnosisFinish', ['as' => 'third.kuaizhen580.diagnosisFinish', 'uses'=>'Kuaizhen580@diagnosisFinish']);

        // 医生拒绝开方
        $api->post('/third/kuaizhen/refusePrescribe', ['as' => 'third.kuaizhen580.refusePrescribe', 'uses'=>'Kuaizhen580@refusePrescribe']);

        // 患者取消订单
        $api->post('/third/kuaizhen/cancelDiagnosis', ['as' => 'third.kuaizhen580.cancelDiagnosis', 'uses'=>'Kuaizhen580@cancelDiagnosis']);

        // 处方开具且审核后推送接口
        $api->post('/third/kuaizhen/prescriptionMedicationAndAudit', ['as' => 'third.kuaizhen580.prescriptionMedicationAndAudit', 'uses'=>'Kuaizhen580@prescriptionMedicationAndAudit']);

        // 处方作废
        $api->post('/third/kuaizhen/prescriptionMedicationDelete', ['as' => 'third.kuaizhen580.prescriptionMedicationDelete', 'uses'=>'Kuaizhen580@prescriptionMedicationDelete']);
    });
});
