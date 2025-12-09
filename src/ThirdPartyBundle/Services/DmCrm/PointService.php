<?php

namespace ThirdPartyBundle\Services\DmCrm;

class PointService extends DmService
{
    /**
     * 查询客户当前积分
     * https://hope-demogic.yuque.com/kgrc1y/gbhe7m/member_get_integral_detail
     */
    public function getPoint($paramsData){
        $worker = '/cgi-api/member/get_integral_detail';
        $params = [
//            'cardNo' => '',
            'mobile' => $paramsData['mobile'] ?? '',
//            'unionId' => '',
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        if (isset($paramsData['unionId']) && !empty($paramsData['unionId'])) {
            $params['unionId'] = $paramsData['unionId'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);

        return $result['result'] ?? [];
    }

     /**
     * 会员积分明细
     *  https://hope-demogic.yuque.com/kgrc1y/gbhe7m/member_member_get_integral_detail
     */
    public function getPointDetailList($paramsData)
    {
        $worker = '/cgi-api/member/member_get_integral_detail';
        $params = [
//            'cardNo' => '',
            'mobile' => $paramsData['mobile'] ?? '',
            'currentPage' => $paramsData['currentPage'] ?? 1,
            'pageSize' => $paramsData['pageSize']
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        $result = $this->switchPointList($result['result'], $paramsData);

        return $result ?? [];
    }

    private function switchPointList($pointList, $otherParams)
    {
        if (empty($pointList)) {
            return [];
        }
        $memberIntegralCodeMap = [
            '1201' => '消费抵现',
            '1202' => '退款追扣',
            '1203' => '手动扣除',
            '1204' => '积分兑换',
            '1205' => '扣减(其他)',
            '1206' => '互动营销扣除',
        ];
        $result = [];
        if (!empty($pointList['items'])) {
            foreach($pointList['items'] as $k => $v) {
                 $ec = [
                    'user_id' => $otherParams['user_id'],
                    'company_id' =>  $otherParams['company_id'],
                    'journal_type' => $memberIntegralCodeMap[$v['memberIntegralCode']] ?? '',
                    'point_desc' => $v['memberIntegralName'] ?? '', // 'memberIntegralName' => $v['memberIntegralName'],
                    'income' => '',   // intervalInout = 1, lastInterval - intervalHistory
                    'outcome' => '',   // intervalInout =0, lastInterval - intervalHistory
                    'order_id' => $v['orelationId'] ?? '',
                    'created' => $v['createTime']/1000, //date('Y-m-d H:i:s', $v['createTime']/1000),
                    'updated' => '',
                    'external_id' => '',
                    'operater' => '',
                    'operater_remark' => '',
                    's_point' => $v['lastInterval'], // bcmul($v['lastInterval'], 100, 2),
                    'point' => abs($v['intervalHistory']),
                    'effectTime' => $v['effectTime'] > 0 ? $v['effectTime']/1000 : '',
                    'order_remark' => ($v['orelationId'] ?? '') . '--' . ($v['relationId'] ?? ''),
                    'remark' => $v['remark'] ?? '',
                ];
                // 处理下订单id
                $orderId = explode('-', $v['orelationId']);
                $ec['order_id'] = $orderId[0] ?? '';
                if ($v['intervalInout'] == 1) {
                    $ec['income'] = abs($v['intervalHistory']);
                } else {
                    $ec['outcome'] = abs($v['intervalHistory']);   
                }
                $result[] = $ec;
            }
        }
        $pointList['items'] = $result;
       
        return $pointList;
    }

    /**
     * 变更会员积分:
     * https://hope-demogic.yuque.com/kgrc1y/gbhe7m/member_change_member_integral
     *
     * 开发文档地址：
     * https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/ay2dlf/member_change_member_integral
     */
    public function changePoint($paramsData)
    {
        $worker = '/cgi-api/member/change_member_integral';
        $params = [
//            'cardNo' => '',
            'mobile' => $paramsData['mobile'],
//            'unionId' => '',
            'integral' => $paramsData['integral'],
            'type' => $paramsData['type'],
            'changeType' => $paramsData['changeType'],
            'remark' => $paramsData['remark'],
//            'storeCode' => '',
            'integralFlow' => $paramsData['mobile'].'_'.time(),
//            'amount' => '',
//            'sourceCode' => '',
//            'orelationId' => '',
//            'effectTime' => '',
//            'effectLimitTime' => '',
            'sourceChannel' => $paramsData['sourceChannel'],
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponseException($result);

        return $result ?? [];
    }

    /**
     * 预扣积分：
     * https://hope-demogic.yuque.com/kgrc1y/gbhe7m/member_member_integral_pre_deduction
     *
     * 开发文档地址：
     * https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/ay2dlf/member_member_integral_pre_deduction
     */
    public function minusPreparePoint($paramsData)
    {
        $worker = '/cgi-api/member/member_integral_pre_deduction';
        $params = [
//            'cardNo' => '',
            'mobile' => $paramsData['mobile'] ?? '',
            'integralVal' => $paramsData['integralVal'],
            'relationId' => $paramsData['relationId'],
            'delay' => $paramsData['delay'],
//            'sourceCode' => $paramsData['sourceCode'],
            'memberIntegralCode' => $paramsData['memberIntegralCode'],
//            'storeCode' => $paramsData['storeCode'],
//            'costFee' => $paramsData['costFee'],
            'remark' => $paramsData['remark'],
//            'idempotentCheck' => $paramsData['idempotentCheck'],
//            'supportFrozen' => $paramsData['supportFrozen'],
//            'sourceChannel' => $paramsData['sourceChannel'],
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponseException($result, '210000');

        return $result ?? [];
    }

    /**
     * 取消预扣：
     * https://hope-demogic.yuque.com/kgrc1y/gbhe7m/member_member_cancel_integral_pre_deduction
     *
     * 开发文档地址
     * https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/ay2dlf/member_member_cancel_integral_pre_deduction
     */
    public function cancelPreparePoint($paramsData)
    {
        $worker = '/cgi-api/member/member_cancel_integral_pre_deduction';
        $params = [
//            'cardNo' => '',
            'mobile' => $paramsData['mobile'] ?? '',
            'preDeductionId' => $paramsData['preDeductionId'],
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponseException($result, '210000');

        return $result ?? [];
    }

    /**
     * 确认扣除：
     * https://hope-demogic.yuque.com/kgrc1y/gbhe7m/member_member_confirm_pre_deduction

     * 开发文档地址
     *  https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/ay2dlf/member_member_confirm_pre_deduction
     */
    public function confirmPreparePoint($paramsData)
    {
        $worker = '/cgi-api/member/member_confirm_pre_deduction';
        $params = [
//            'cardNo' => '',
            'mobile' => $paramsData['mobile'] ?? '',
            'preDeductionId' => $paramsData['preDeductionId'],
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponseException($result, '210000');

        return $result ?? [];
    }
    
     /**
     * 同步积分日志

     * 开发文档地址
     *  https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/ay2dlf/member_sync_member_integral_log
     */
    public function syncPointLogs($paramsData)
    {
        $worker = '/cgi-api/member/sync_member_integral_log';
        $params = [
            // 'cardNo' => $paramsData['cardNo'],
            'mobile' => $paramsData['mobile'],
            'integral' => $paramsData['mobile'],
            'type' => $paramsData['type'], // 1 -> 1140, 0 -> 1240
            'changeType' => $paramsData['changeType'], // 1140 -> 1, 1240 -> 0
            'integralFlow' => $paramsData['integralFlow'], 
            // 'effectTime' => $paramsData['effectTime'], 
            'remark' => $paramsData['remark'], 
            'lastInterval' => $paramsData['lastInterval'], 
            'sourceChannel' => 'c_brand_mall',  // c_brand_mall
        ];
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);

        return $result['result'] ?? [];
    }

}

