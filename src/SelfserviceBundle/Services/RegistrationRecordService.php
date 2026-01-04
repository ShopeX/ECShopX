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

namespace SelfserviceBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\EnterprisesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EspierBundle\Services\AddressService;
use MembersBundle\Services\MemberService;
use PointBundle\Services\PointMemberService;
use PromotionsBundle\Services\SmsManagerService;
use SelfserviceBundle\Entities\RegistrationRecord;
use SelfserviceBundle\Jobs\ActivityJoinSuccessJob;

class RegistrationRecordService
{
    /** @var \SelfserviceBundle\Repositories\RegistrationRecordRepository */
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(RegistrationRecord::class);
    }
    
    public function saveRecord($params, $activityInfo)
    {
        if (isset($params['record_id'])) {
            $record_id = $params['record_id'];
            unset($params['record_id']);
        } else {
            $record_id = 0;
        }
        $params['true_name'] = $params['true_name'] ?? '';       
        $params['form_id'] = $activityInfo['temp_id'] ?? 0;
        if ($activityInfo['is_need_check']) {
            $params['status'] = 'pending';
        } else {
            $params['status'] = 'passed';
        }
        
        if ($record_id) {
            $record = $this->entityRepository->updateOneBy(['record_id' => $record_id], $params);
        } else {
            //获取报名编号
            $activity_id = $activityInfo['activity_id'];
            $group_no = $activityInfo['group_no'];
            $params['group_no'] = $group_no;
            $params['record_no'] = $this->genRecordNo($activityInfo['company_id'], $activity_id, $group_no);
            $params['verify_code'] = mt_rand(111111, 999999);
            $params['is_white_list'] = 0;
            $params['get_points'] = 0;
            $params['verify_time'] = 0;
            $params['verify_operator'] = '';                        
            //新增数据
            $record = $this->entityRepository->create($params);
            //删除锁定key
            $redis_key = 'genRecordNo:' . $activity_id . ':' . $group_no;
            $redis = app('redis');
            $redis->del($redis_key);

            //如果活动不需要审核，触发报名成功的事件
            if (!$activityInfo['is_need_check']) {
                $this->activitySuccess($record, $activityInfo);
            }
        }
        return $record;
    }

    //获取报名编号
    public function genRecordNo($company_id, $activity_id, $group_no)
    {
        if (!$group_no) $group_no = 0;
        $redis_key = "genRecordNo:{$activity_id}:{$group_no}";
        $redis = app('redis');
        if ($redis->setnx($redis_key, 1)) {
            $redis->expire($redis_key, 10);
            if ($group_no) {
                $_filter = [
                    'company_id' => $company_id,
                    'group_no' => $group_no,
                ];
                $activityService = new RegistrationActivityService();
                $rs = $activityService->entityRepository->getLists($_filter, 'activity_id');
                if (!$rs) {
                    throw new ResourceException(trans('SelfserviceBundle.activity_group_code_error', ['group_no' => $group_no]));
                }
                $_filter = ['activity_id' => array_column($rs, 'activity_id')];
            } else {
                $_filter = ['activity_id' => $activity_id];
            }
            $record_no = $this->entityRepository->count($_filter);
            $record_no ++;            
        } else {
            throw new ResourceException(trans('SelfserviceBundle.activity_too_popular_try_later'));
        }
        return $record_no;
    }
    
    //报名成功，或者审核通过
    public function activitySuccess($record, $activityInfo)
    {
        app('log')->debug('registrationReview_'.$record['record_id'].' => activitySuccess begin');
        //送积分
        if ($activityInfo['gift_points'] && !$record['get_points']) {
            app('log')->debug('registrationReview'.$record['record_id'].' => 送积分');
            try {
                $gift_points = intval($activityInfo['gift_points']);
                if ($gift_points) {
                    $pointMemberService = new PointMemberService();
                    $pointMemberService->addPoint($record['user_id'], $record['company_id'], $gift_points, 16, true, '活动报名送积分');
                    //给用户送积分
                    $this->entityRepository->updateOneBy(['record_id' => $record['record_id']], ['get_points' => $gift_points]);
                }
            } catch (\Exception $e) {
                throw new ResourceException($e->getMessage());
            }
        }

        //加入内购白名单
        if ($activityInfo['is_white_list'] && $activityInfo['enterprise_ids'] && !$record['is_white_list']){
            app('log')->debug('registrationReview_'.$record['record_id'].' => 加入内购白名单');
            $enterprise_ids = explode(',', $activityInfo['enterprise_ids']);            
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $this->entityRepository->updateOneBy(['record_id' => $record['record_id']], ['is_white_list' => 1]);

                if ($record['mobile'] == $record['form_mobile']) {
                    //手机号一样，说明是给自己报名
                    $user_id = $record['user_id'];
                } else {
                    //手机号不一样，说明是帮别人报名，先判断手机号是否已经存在会员
                    $memberService = new MemberService();
                    $user_id = $memberService->getUserIdByMobile($record['form_mobile'], $record['company_id']);
                    if ($user_id) {
                        $record['mobile'] = $record['form_mobile'];
                    } else {
                        //报名手机号暂未注册会员
                        $user_id = 0;
                        $record['mobile'] = '';//会员手机号留空
                    }
                }

                $relativesService = new RelativesService();
                $employeesService = new EmployeesService();
                $enterprisesService = new EnterprisesService();
                foreach ($enterprise_ids as $enterprise_id) {
                    $enterprise_id = intval($enterprise_id);
                    if (!$enterprise_id) continue;
                    
                    $enterpriseInfo = $enterprisesService->enterprisesRepository->getInfoById($enterprise_id);
                    if (!$enterpriseInfo) continue;

                    $save_data = [
                        'company_id' => $record['company_id'],
                        'enterprise_id' => $enterprise_id,
                        'name' => $record['true_name'],
                        'user_id' => $user_id,
                        'member_mobile' => $record['mobile'],
                        'mobile' => $record['form_mobile'],
                        'distributor_id' => $enterpriseInfo['distributor_id'],
                    ];

                    // 在插入前检查是否已存在
                    if ($user_id) {
                        $_filter = ['user_id' => $user_id, 'enterprise_id' => $enterprise_id];
                    } else {
                        $_filter = ['mobile' => $record['form_mobile'], 'enterprise_id' => $enterprise_id];
                    }
                    if (!$employeesService->entityRepository->getInfo($_filter)) {
                        $employeesService->entityRepository->create($save_data);
                    }

                    // 禁用同一个企业下的亲友身份
                    if ($user_id) {
                        $relativesService->updateBy(['company_id' => $record['company_id'], 'user_id' => $user_id, 'enterprise_id' => $enterprise_id], ['disabled' => 1]);
                    }
                }
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw new ResourceException($e->getMessage());
            }
        }

        app('log')->debug('registrationReview'.$record['record_id'].' => activitySuccess ok');
    }

    public function saveData($params, $filter = [])
    {
        if ($filter) {
            return $this->entityRepository->updateOneBy($filter, $params);
        } else {
            return $this->entityRepository->create($params);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
    
    public function getStatusName($status = '')
    {
        $statusConf = [
            'pending' => trans('SelfserviceBundle.status_pending'),
            'passed' => trans('SelfserviceBundle.status_passed'),
            'rejected' => trans('SelfserviceBundle.status_rejected'),
            'verified' => trans('SelfserviceBundle.status_verified'),
            'canceled' => trans('SelfserviceBundle.status_canceled'),
        ];
        return $statusConf[$status] ?? $status;
    }

    public function getRocordList($filter, $page = 1, $pageSize = -1, $orderBy = ['record_id' => 'DESC'])
    {
        $lists = $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!$lists['list']) {
            return $lists;
        }

        $activityIds = array_column($lists['list'], 'activity_id');
        $registrationActivityService = new RegistrationActivityService();
        $ayList = $registrationActivityService->entityRepository->getLists(['activity_id' => $activityIds], 'activity_id, activity_name,start_time,end_time,join_limit,is_sms_notice,is_wxapp_notice,pics,intro,is_allow_duplicate,address,place,area');
        $aylist = array_column($ayList, null, 'activity_id');

        $week_data = ['日', '一', '二', '三', '四', '五', '六'];
        $addressService = new AddressService();
        foreach ($lists['list'] as &$v) {
            $activity_info = $aylist[$v['activity_id']] ?? [];
            if ($activity_info) {
                $registrationActivityService->getStatusName($activity_info);
                //活动开始时间
                $start_time = '';
                if ($activity_info['start_time']) {
                    $week_no = date('w', $activity_info['start_time']);
                    $week_name = $week_data[$week_no];
                    $start_time = date("Y-m-d {$week_name} H:i", $activity_info['start_time']);
                }
                
                $area_name = $addressService->getAreaName($activity_info['area']);
                
                //活动信息
                $v['activity_info'] = [
                    'address' => $activity_info['address'],
                    'place' => $activity_info['place'],
                    'is_allow_duplicate' => $activity_info['is_allow_duplicate'],
                    'intro' => $activity_info['intro'],
                    'pics' => $activity_info['pics'],
                    'start_time' => $start_time,
                    'status_name' => $activity_info['status_name'],
                    'area' => $activity_info['area'],
                    'area_name' => $area_name,
                ];
            }
            $v['record_no'] = str_pad($v['record_no'], 4, '0', STR_PAD_LEFT);
            $v['activity_name'] = $aylist[$v['activity_id']]['activity_name'] ?? '';
            $v['start_time'] = $aylist[$v['activity_id']]['start_time'] ?? '';
            $v['end_time'] = $aylist[$v['activity_id']]['end_time'] ?? '';
            $v['start_date'] = $v['start_time'] ? date('Y-m-d H:i:s', $v['start_time']) : '';
            $v['end_date'] = $v['end_time'] ? date('Y-m-d H:i:s', $v['end_time']) : '';
            $v['join_limit'] = $aylist[$v['activity_id']]['join_limit'] ?? 1;
            $v['is_sms_notice'] = $aylist[$v['activity_id']]['is_sms_notice'] ?? false;
            $v['is_wxapp_notice'] = $aylist[$v['activity_id']]['is_wxapp_notice'] ?? false;
            $v['content'] = (array)json_decode($v['content'], true);
            $v['create_date'] = date('Y-m-d H:i:s', $v['created']);
            $v['status_name'] = $this->getStatusName($v['status']);
        }
        return $lists;
    }

    /**
     * 获取当前报名记录可以执行的操作按钮
     * @param array $record
     * @return array
     */
    public function getRecordAction($record = [])
    {
        $action = [
            'cancel' => 0,//是否可以取消报名
            'edit' => 0,//是否可以修改
            'apply' => 0,//是否可以重复申请
        ];
        
        if ($record['start_time'] > time() or $record['end_time'] < time()) {
            return $action;
        }
        
        //pending 待审核，passed 已通过，rejected 已拒绝, canceled 已取消, verified 已核销
        if (in_array($record['status'], ['pending', 'passed'])) {
            $action['cancel'] = 1;
        }
        if ($record['form_id'] && in_array($record['status'], ['pending', 'rejected'])) {
            $action['edit'] = 1;
        }
        if ($record['activity_info']['is_allow_duplicate']) {
            $action['apply'] = 1;
        }
        return $action;
    }

    public function getRocordInfo($id)
    {
        $info = $this->entityRepository->getInfoById($id);
        if (!$info) {
            return [];
        }
        $registrationActivityService = new RegistrationActivityService();
        $aylist = $registrationActivityService->getInfo(['activity_id' => $info['activity_id']], 'activity_id, activity_name,start_time,end_time,join_limit,is_sms_notice,is_wxapp_notice,address,place,intro,pics,is_allow_duplicate,area,is_offline_verify');

        $activity_info = $aylist ?? [];
        if ($activity_info) {
            $registrationActivityService->getStatusName($activity_info);
            
            $addressService = new AddressService();
            $area_name = $addressService->getAreaName($activity_info['area']);
            
            //活动信息
            $info['activity_info'] = [
                'is_offline_verify' => $activity_info['is_offline_verify'],
                'is_allow_duplicate' => $activity_info['is_allow_duplicate'],
                'address' => $activity_info['address'],
                'place' => $activity_info['place'],
                'intro' => $activity_info['intro'],
                'pics' => $activity_info['pics'],
                'status_name' => $activity_info['status_name'],
                'area' => $activity_info['area'],
                'area_name' => $area_name,
            ];
        }
        
        $info['record_no'] = str_pad($info['record_no'], 4, '0', STR_PAD_LEFT);
        $info['activity_name'] = $aylist['activity_name'] ?? '';
        $info['start_time'] = $aylist['start_time'] ?? '';
        $info['end_time'] = $aylist['end_time'] ?? '';
        $info['start_date'] = $aylist['start_time'] ? date('Y-m-d H:i:s', $aylist['start_time']) : '';
        $info['end_date'] = $aylist['end_time'] ? date('Y-m-d H:i:s', $aylist['end_time']) : '';
        $info['join_limit'] = $aylist['join_limit'] ?? 1;
        $info['is_sms_notice'] = $aylist['is_sms_notice'] ?? false;
        $info['is_wxapp_notice'] = $aylist['is_wxapp_notice'] ?? false;
        $info['content'] = (array)json_decode($info['content'], true);
        $info['status_name'] = $this->getStatusName($info['status']);
        return $info;
    }



    public function sendMassage($companyId, $recordId)
    {
        // app('log')->debug('registrationReview'.$recordId.' => sendMassage begin');
        try {
            $record = $this->entityRepository->getInfo(['company_id' => $companyId, 'record_id' => $recordId]);
            $registrationActivityService = new RegistrationActivityService();
            $activity = $registrationActivityService->getInfo(['company_id' => $companyId, 'activity_id' => $record['activity_id']]);

            $content = [
                'activity_name' => $activity['activity_name'],
                'activity_start_time' => date('Y年m月d日H点i分', $activity['start_time']),
                'activity_place' => $activity['place'],
                'activity_address' => $activity['address'],
                'activity_refuse_reason' => $record['reason'],
                'tmpl_name' => ($record['status'] == 'passed') ? 'registration_success_notice' : 'registration_fail_notice',
            ];
            
            if ($activity['is_sms_notice'] == 'true') {
                app('log')->debug('registrationReview'.$recordId.' => 发短信 begin');
                $this->sendSmsMsg($companyId, $content, $record['form_mobile']);
            }
            if ($activity['is_wxapp_notice'] == 'true') {
                app('log')->debug('registrationReview'.$recordId.' => 发微信 begin');
                $this->sendWxappMsg($companyId, $content, $record['wxapp_appid'], $record['open_id']);
            }
            return true;
        } catch (\Exception $e) {
            app('log')->error('registrationReview'.$recordId.' => ' . $e->getMessage() . $e->getFile() . $e->getLine());
            app('log')->debug('报名活动审核通知'.$e->getMessage());
        }
    }

    private function sendSmsMsg($companyId, $content, $mobile)
    {
        //判断短信模版是否开启
        $smsManagerService = new SmsManagerService($companyId);
        $templateData = $smsManagerService->getOpenTemplateInfo($companyId, $content['tmpl_name']);
        if (!$templateData) {
            return true;
        }

        try {
            app('log')->debug('短信发送内容: registration_result_notice =>'.$mobile."---".json_encode($content, 256));
            $smsManagerService->send($mobile, $companyId, $content['tmpl_name'], $content);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: registration_result_notice =>'.$e->getMessage());
        }
    }

    private function sendWxappMsg($companyId, $content, $wxappId, $openId)
    {
        try {
            $sendData['scenes_name'] = 'registrationResultNotice';
            $sendData['company_id'] = $companyId;
            $sendData['appid'] = $wxappId;
            $sendData['openid'] = $openId;
            $sendData['data'] = $content;
            app('log')->debug('小程序模板消息发送内容: registration_result_notice =>'.var_export($sendData, 1));
            app('wxaTemplateMsg')->send($sendData);
        } catch (\Exception $e) {
            app('log')->debug('小程序模板消息发送失败: registration_result_notice =>'.$e->getMessage());
        }
    }

    /**
     * 处理报名详情的字段脱敏
     * @param  array $content       报名详情的内容
     * @param  int $datapassBlock 是否脱敏
     * @return array                处理后的数据
     */
    public function fixeddecryptRocordContent($content, $datapassBlock)
    {
        if (!$content || !$datapassBlock) {
            return $content;
        }
        foreach ($content as $key => &$value) {
            foreach ($value['formdata'] as $k => &$formdata) {
                $formdata['answer'] = $formdata['answer'] ?? '';
                if ($formdata['field_name'] == 'username') {
                    $formdata['answer'] = data_masking('truename', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'mobile') {
                    $formdata['answer'] = data_masking('mobile', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'birthday') {
                    $formdata['answer'] = data_masking('birthday', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'bankcard') {
                    $formdata['answer'] = data_masking('bankcard', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'idcard') {
                    $formdata['answer'] = data_masking('idcard', (string) $formdata['answer']);
                }
                if ($formdata['field_name'] == 'address') {
                    $formdata['answer'] = data_masking('detailedaddress', (string) $formdata['answer']);
                }
            }
        }
        return $content;
    }
}
