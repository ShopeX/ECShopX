<?php

namespace ThirdPartyBundle\Services\DmCrm;

use function AlibabaCloud\Client\json;
use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\MemberCardGrade;
use MembersBundle\Entities\MemberOperateLog;
use ThirdPartyBundle\Services\DmCrm\DmService;

class MemberService extends DmService
{
    // 达摩crm消息订阅回调事件, 会员等级变更同步
    public function syncMemberLevelChange($companyId, $paramsData)
    {  
        // $companyId = 1;  // 无法获取到company_id,默认给1
        $memberSerivce = new \MembersBundle\Services\MemberService();
        $memberOperateLogRepository = app('registry')->getManager('default')->getRepository(MemberOperateLog::class);
        $membercardGradeRepository = app('registry')->getManager('default')->getRepository(MemberCardGrade::class);

        $memberInfo = $memberSerivce->getMemberInfoByDmCardNo($paramsData['cardNo'], $companyId);
        if (empty($memberInfo)) {
            throw new ResourceException("会员卡号不存在");
        }
        // 获取变更的等级
        $membercardGradeInfo = $membercardGradeRepository->getInfo(['dm_grade_code' => $paramsData['afterGradeCode']]);
        if (empty($membercardGradeInfo)) {
            throw new ResourceException("达摩CRM等级编码不存在");
        }
        $updateMember = [
            'grade_id' => $membercardGradeInfo['grade_id'],
            'updated' => time(),
        ];
        $filterMember = [
            'company_id' => $companyId,
            'user_id' => $memberInfo['user_id'],
        ];
        $result = $memberSerivce->memberUpdate($updateMember, $filterMember);
        if ($result) {
             // 记录操作日志
            $operateParams = [
                'user_id' => $memberInfo['user_id'],
                'company_id' => $companyId,
                'operate_type' => 'grade_id',
                'old_data' => $paramsData['beforeGradeCode'],
                'new_data' => $paramsData['afterGradeCode'],
                'operater' => "达摩CRM",
                'remarks' => json_encode($paramsData),
            ];
            $logResult = $memberOperateLogRepository->create($operateParams);
        }

        return $result;
    }

     // 达摩crm消息订阅回调事件, 会员基本信息变更事件
    public function syncBaseInfoChange($companyId, $paramsData)
    {
        // $companyId = 1; // 无法获取到company_id,默认给1
        $memberSerivce = new \MembersBundle\Services\MemberService();
        $memberserviceLocal = new MemberService($companyId);
        $memberOperateLogRepository = app('registry')->getManager('default')->getRepository(MemberOperateLog::class);

        $memberInfo = $memberSerivce->getMemberInfoByDmCardNo($paramsData['cardNo'], $companyId);
        if (empty($memberInfo)) {
            throw new ResourceException("会员卡号不存在");
        }
        // 获取会员信息变更之后，燃火在查询一下会员接口获取会员信息
        $paramsData = [
            'mobile' => $memberInfo['mobile'],
            'cardNo' => $paramsData['cardNo'],
        ];
        $dmMemberInfo = $memberserviceLocal->getMemberInfo($paramsData);
        $updateMember = [
            'sex' => $dmMemberInfo['sex'],
            'username' => $dmMemberInfo['name'],
            'birthday' => date('Y-m-d', $dmMemberInfo['birthday']/1000)
        ];
        $filterMember = [
            'company_id' => $companyId,
            'user_id' => $memberInfo['user_id']
        ];
        $result = $memberSerivce->memberInfoUpdate($updateMember, $filterMember);
        if ($result) {
             // 记录操作日志
            $operateParams = [
                'user_id' => $memberInfo['user_id'],
                'company_id' => $companyId,
                'operate_type' => 'member_id',
                'old_data' => json_encode($memberInfo),
                'new_data' => json_encode($dmMemberInfo),
                'operater' => "达摩CRM",
                'remarks' => '会员基本信息变更事件',
            ];
            $logResult = $memberOperateLogRepository->create($operateParams);
        }    

        return $result;
    }

     // 达摩crm消息订阅回调事件, 会员积分变更事件
    public function syncIntegralChange($companyId, $paramsData)
    {
        // $companyId = 1; // 无法获取到company_id,默认给1
        $memberSerivce = new \MembersBundle\Services\MemberService();
        $memberOperateLogRepository = app('registry')->getManager('default')->getRepository(MemberOperateLog::class);

        $memberInfo = $memberSerivce->getMemberInfoByDmCardNo($paramsData['cardNo'], $companyId);
        if (empty($memberInfo)) {
            throw new ResourceException("会员卡号不存在");
        }
        $point = $paramsData['integral'];
        // 更新用户积分
        $conn = app("registry")->getConnection("default");
        if ($point >= 0) {
            $result = $conn->executeStatement(
                'UPDATE point_member SET point = point + ? WHERE company_id = ? and user_id = ?',
                [abs($point), $companyId, $memberInfo['user_id']]
            );
        }else {
            $result = $conn->executeStatement(
                'UPDATE point_member SET point = point - ? WHERE company_id = ? and user_id = ?',
                [abs($point), $companyId, $memberInfo['user_id']]
            );
        }

        if ($result) {
             // 记录操作日志
            $operateParams = [
                'user_id' => $memberInfo['user_id'],
                'company_id' => $companyId,
                'operate_type' => 'member_id',
                'old_data' => '',
                'new_data' => json_encode($paramsData),
                'operater' => "达摩CRM",
                'remarks' => '会员基积分变更事件',
            ];
            $logResult = $memberOperateLogRepository->create($operateParams);
        }    

        return $result ?? [];
    }

    /**
     * 获取达摩CRM会员信息
     * @param array $paramsData
     */
    public function getMemberInfo($paramsData)
    {
        $worker = '/cgi-api/member/get_member_info';
        $params = [
            'mobile' => $paramsData['mobile'] ?? '',
        ];
        if (isset($paramsData['cardNo']) && !empty($paramsData['cardNo'])) {
            $params['cardNo'] = $paramsData['cardNo'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        if (isset($result['code']) && $result['code'] == 0) {
            return $result['result'] ?? [];
        } else {
            return false;
        }
    }

    /**
     * 绑定达摩CRM会员
     * @param array $paramsData
     */
    public function memberBind($paramsData)
    {
        $worker = '/cgi-api/member/bind_member';
        $params = [
            'channelCode' => 'c_brand_mall',
            'mobile' => $paramsData['mobile'],
            'userIdentity' => $paramsData['mobile'],
        ];
        $result = $this->requestApiPost($worker, $params);
        return $this->returnResponse($result);
    }

    /**
     * 注册达摩CRM会员
     * @param array $paramsData
     */
    public function memberRegister($paramsData)
    {
        $worker = '/cgi-api/member/add_member_info';
        $params = [
            'gicOpenCard' => true,// 使用达摩CRM会员卡
            'name' => $paramsData['username'],
            'mobile' => $paramsData['mobile'],
            'autoGrade' => 1,//自动默认等级
            'unionId' => $paramsData['unionid'],
            'openId' => $paramsData['open_id'],
            'sourceCode' => 'DEFAULT',
            'sex' => $paramsData['sex'] ?? 0,
            'openCardDate' => date('Y-m-d H:i:s', time()),
            'channelCode' => 'c_brand_mall',
        ];
        if (isset($paramsData['openClerkCode']) && !empty($paramsData['openClerkCode'])) {
            $params['openClerkCode'] = $paramsData['openClerkCode'];
        }
        if (isset($paramsData['mainClerkCode']) && !empty($paramsData['mainClerkCode'])) {
            $params['mainClerkCode'] = $paramsData['mainClerkCode'];
        }
        if (isset($paramsData['openStoreCode']) && !empty($paramsData['openStoreCode'])) {
            $params['openStoreCode'] = $paramsData['openStoreCode'];
        }
        if (isset($paramsData['mainStoreCode']) && !empty($paramsData['mainStoreCode'])) {
            $params['mainStoreCode'] = $paramsData['mainStoreCode'];
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        app('log')->debug('dmcrm::memberRegister::result===>'.json_encode($result));
        if (isset($result['code']) && $result['code'] == 0) {
            return $result['result'] ?? [];
        } else {
            return false;
        }
    }

    /**
     * 更新达摩CRM会员信息
     * @param array $paramsData
     */
    public function updateMemberInfoByMobile($params)
    {
        $worker = '/cgi-api/member/update_member_info_by_mobile';
        app('log')->debug('dmcrm::updateMemberInfoByMobile::params===>'.json_encode($params));
        //birthday":"2005-08-20
        if (isset($params['birthday']) && !empty($params['birthday'])) {
            if (is_numeric($params['birthday'])) {
                $params['birthday'] = date('Y-m-d', $params['birthday']/1000);// H:i:s 
            } else {
                $params['birthday'] = $params['birthday'];//2005-08-20 H I s
            }
        }
        if (isset($params['sex']) && !empty($params['sex'])) {
            $params['sex'] = $params['sex'] == '男' ? 1 : 0;
        }
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);
        app('log')->debug('dmcrm::updateMemberInfoByMobile::result===>'.json_encode($result));
        return $result['result'] ?? [];
    }

    /* 
     *  达摩会员等级列表
     *  https://hope-demogic.yuque.com/org-wiki-hope-demogic-dfi2t5/ay2dlf/member_get_grade_list
     *  @return void
     */
    public function MemberGradeList($paramsData)
    {
        $worker = '/cgi-api/member/get_grade_list';
        $params = [
            'gradeType' => $paramsData['gradeType'],
        ];
        $result = $this->requestApiPost($worker, $params);
        $result = $this->returnResponse($result);

        return $result['result'] ?? [];
    }
    
}