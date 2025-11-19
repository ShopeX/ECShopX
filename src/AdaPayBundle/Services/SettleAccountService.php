<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Services;

use AdaPayBundle\Services\MemberService as AdaPayMemberService;
use AdaPayBundle\Services\Request\Request;
use Dingo\Api\Exception\ResourceException;

use AdaPayBundle\Entities\AdapaySettleAccount;

class SettleAccountService
{
    public $adapaySettleAccountRepository;

    public function __construct($companyId = 0)
    {
        if ($companyId) {
            // parent::init($companyId);
        }
        $this->adapaySettleAccountRepository = app('registry')->getManager('default')->getRepository(AdapaySettleAccount::class);
    }

    public function transfer($companyId, $fromMemberId = '0', $toMemberId = '0', $transAmt = '0.00', $transType = '')
    {
        $memberService = new MemberService();
        $params['app_id'] = $memberService->getAppId($companyId);
        $params['order_no'] = 'TF_' . date('YmdHis') . rand(100000, 999999);
        $params['company_id'] = $companyId;
        $params['trans_amt'] = $transAmt;
        $params['out_member_id'] = $fromMemberId;
        $params['in_member_id'] = $toMemberId;
        $params['trans_type'] = $transType;
        $params['notify_url'] = config('adapay.notify_url');
        $params['api_method'] = 'SettleAccount.transfer';
        //打接口到代理商后台  返回状态存入
        $request = new Request();
        $resData = $request->call($params);
        return $resData;
    }

//    public function saveAccount($data = [])
//    {
//        $data['app_id'] = $data['app_id'] ?? self::$appId;
//
//        $filter = [
//            'member_id' => $data['member_id'],
//            'company_id' => $data['company_id'],
//        ];
//        if ($this->count($filter) >= 1) {
//            $result = $this->updateOneBy($filter, $data);
//        } else {
//            $result = $this->create($data);
//        }
//        return $result;
//    }

    //    public function createApi($memberId = 0)
    //    {
    //        if (!$memberId) return false;
    //
    //        $accountInfo = $this->getInfo(['member_id' => $memberId]);
    //        if (!$accountInfo) {
    //            throw new ResourceException('账户信息不存在');
    //        }
    //
    //        $obj = new SettleAccountApi();
    //        $obj_params = array(
    //            'app_id' => $accountInfo['app_id'],
    //            'member_id' => $memberId,
    //            'channel' => 'bank_account',
    //            'account_info' => [
    //                'card_id' => $accountInfo['card_id'],
    //                'card_name' => $accountInfo['card_name'],//银行卡对应的户名, 若银行账户类型是对公，必须与企业名称一致
    //                'cert_id' => $accountInfo['cert_id'],
    //                'cert_type' => $accountInfo['cert_type'] ?? '00',
    //                'tel_no' => $accountInfo['tel_no'],
    //                'bank_code' => $accountInfo['bank_code'] ?? '',
    //                'bank_name' => $accountInfo['bank_name'] ?? '',
    //                'bank_acct_type' => $accountInfo['bank_acct_type'],//银行账户类型：1-对公；2-对私
    //                'prov_code' => $accountInfo['prov_code'],
    //                'area_code' => $accountInfo['area_code']
    //            ]
    //        );
    //        $obj->create($obj_params);
    //
    //        if ($obj->isError()) {
    //            $errorMsg = $this->getErrorMsg($obj);
    //            $memberData = [
    //                'audit_desc' => $errorMsg,
    //            ];
    //            $memberService = new AdaPayMemberService();
    //            $memberService->updateOneBy(['id' => $memberId], $memberData);
    //            //throw new ResourceException($errorMsg);
    //            return false;
    //        } else {
    //            //更新结算账户表 settle_account_id
    //            $filter = [
    //                'member_id' => $memberId,
    //            ];
    //            $updateData = [
    //                'settle_account_id' => $obj->result['id'] ?? '',
    //            ];
    //            $this->updateOneBy($filter, $updateData);
    //
    //            //更新用户表审核结果
    //            $memberData = [
    //                'audit_state' => AdaPayMemberService::AUDIT_SUCCESS,//开户和创建结算账户成功
    //                'audit_desc' => '',
    //            ];
    //            $memberService = new AdaPayMemberService();
    //            $memberService->updateOneBy(['id' => $memberId], $memberData);
    //        }
    //
    //        return $obj->result;
    //    }

    //todo 结算账户暂时不支持更新
    public function updateApi($memberId = 0)
    {
    }

    //获取唯一值来比对是否有变更
    public function getUniqueKey($params = [])
    {
        $checkFields = [
            'card_id','card_name','cert_id','cert_type','tel_no',
            'bank_code','bank_name','bank_acct_type','prov_code','area_code'];
        $uniqueKey = '';
        foreach ($checkFields as $key) {
            $uniqueKey .= $key . '=' . ($params[$key] ?? '');
        }
        return $uniqueKey;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapaySettleAccountRepository->$method(...$parameters);
    }
}
