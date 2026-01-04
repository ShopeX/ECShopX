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

namespace WorkWechatBundle\Services;

use EasyWeChat\Factory;
use SalespersonBundle\Services\SalespersonService;
use MembersBundle\Entities\MembersAssociations;
use MembersBundle\Services\MemberService;
use WorkWechatBundle\Entities\WorkWechatRel;
use WorkWechatBundle\Entities\WorkWechatRelLogs;

class WorkWechatRelService
{
    public $workWechatRelRepository;

    public $workWechatRelLogsRepository;
    /**
     * WorkWechatRelService 构造函数.
     */
    public function __construct()
    {
        $this->workWechatRelRepository = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        $this->workWechatRelLogsRepository = app('registry')->getManager('default')->getRepository(WorkWechatRelLogs::class);
    }

    /**
     * 获取导购员关联会员信息
     * @param $filter 条件字段
     * @param string $cols 获取字段
     * @param int $page 页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序
     * @return mixed
     */
    public function getWorkWechatRel($filter, $cols = '*', $page = 1, $pageSize = 10, $orderBy = ['id' => 'DESC'])
    {
        // CRC: 2367340174
        $result = $this->workWechatRelRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        app('log')->info("work_wechat_rel result===>" . json_encode($result));
        if ($result['total_count'] > 0) {
            $salesPersionIds = array_column($result['list'], 'salesperson_id');
            $userIds = array_column($result['list'], 'user_id');
            $salespersonService = new SalespersonService();
            $salespersonInfoTemp = $salespersonService->salesperson->lists(['salesperson_id' => $salesPersionIds]);
            app('log')->info("work_wechat_rel salespersonInfoTemp===>" . json_encode($salespersonInfoTemp));
            $salespersonInfo = array_column($salespersonInfoTemp['list'], null, 'salesperson_id');
            app('log')->info("work_wechat_rel salespersonInfo===>" . json_encode($salespersonInfo));

            $memberService = new MemberService();
            $userInfoTemp = $memberService->getMemberList(['user_id' => $userIds]);
            $userInfo = array_column($userInfoTemp, null, 'user_id');
            app('log')->info("work_wechat_rel userInfo===>" . json_encode($userInfo));
            foreach ($result['list'] as &$v) {
                $v['user_info'] = $userInfo[$v['user_id']] ?? [];
                $v['salesperson_info'] = $salespersonInfo[$v['salesperson_id']] ?? [];
                $v['work_userid'] = $v['salesperson_info']['work_userid'] ?? '';
            }
        }

        return $result;
    }

    /**
     * 获取导购员关联会员信息
     * @param $filter 条件字段
     * @param string $cols 获取字段
     * @param int $page 页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序
     * @return mixed
     */
    public function getWorkWechatExternalUserId($filter)
    {
        $info = $this->workWechatRelRepository->getLists($filter, 'user_id,external_userid');
        $result = array_column($info, null, 'user_id');
        return $result;
    }

    /**
     * 获取导购员关联会员信息日志
     * @param $filter 条件字段
     * @param string $cols 获取字段
     * @param int $page 页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序
     * @return mixed
     */
    public function getWorkWechatRelLogs($filter, $cols = '*', $page = 1, $pageSize = 10, $orderBy = ['created' => 'DESC'])
    {
        $result = $this->workWechatRelLogsRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if ($result['total_count'] > 0) {
            $salesPersionIds = array_column($result['list'], 'salesperson_id');
            $salespersonService = new SalespersonService();
            $salespersonInfoTemp = $salespersonService->salesperson->lists(['salesperson_id' => $salesPersionIds]);
            $salespersonInfo = array_column($salespersonInfoTemp['list'], null, 'salesperson_id');
            foreach ($result['list'] as &$v) {
                $v['salesperson_info'] = $salespersonInfo[$v['salesperson_id']] ?? [];
                if (strstr($v['remarks'], ':')) {
                    list($v['log_type'], $v['remarks']) = explode(':', $v['remarks']);
                }
            }
        }

        return $result;
    }

    public function saveWorkWechatRelLogs($params = [])
    {
        // CRC: 2367340174
        $salesPersonName = $params['salesperson_name'] ?? '';
        if (!$salesPersonName && $params['salesperson_id']) {
            //获取导购员姓名
            $salesPersonService = new SalespersonService();
            $salespersonInfo = $salesPersonService->salesperson->getInfoById($params['salesperson_id']);
            if ($salespersonInfo) {
                $salesPersonName = $salespersonInfo['name'];
            }
        }

        if ($params['is_first_bind']) {
            $remarks = "初始绑定: 与导购 {$salesPersonName} 绑定关系";
        } else {
            $remarks = "绑定变更: 绑定导购变更为 {$salesPersonName}";
        }

        $logData = [
            'company_id' => $params['company_id'],
            'work_userid' => $params['work_userid'] ?? '',
            'salesperson_id' => $params['salesperson_id'],
            'external_userid' => $params['external_userid'] ?? '',
            'unionid' => $params['unionid'],
            'user_id' => $params['user_id'],
            'is_friend' => $params['is_friend'],
            'remarks' => $remarks,
        ];
        $result = $this->workWechatRelLogsRepository->create($logData);
        return $result;
    }

    /**
     * 外部联系人关系
     * @param $respone
     */
    public function relationship($companyId, $respone)
    {
        switch ($respone['ChangeType']) {
            case 'add_external_contact':
                $this->addExternalContact($companyId, $respone);
                break;
            case 'add_half_external_contact':
                $this->addHalfExternalContact($companyId, $respone);
                break;
            case 'del_external_contact':
                $this->delExternalContact($companyId, $respone);
                break;
            case 'del_follow_user':
                $this->delFollowUser($companyId, $respone);
                break;
        }
    }

    /**
     * 导购员外部联系人添加好友关系
     * @param $respone
     */
    public function addExternalContact($companyId, $respone)
    {
        app('log')->info('导购员外部联系人添加好友关系');
        $this->change($companyId, $respone['UserID'], $respone['ExternalUserID'], true, '导购员外部联系人添加好友关系');
    }

    /**
     * 外部联系人请求添加好友
     * @param $respone
     */
    public function addHalfExternalContact($companyId, $respone)
    {
        app('log')->info('外部联系人请求添加好友');
        $this->change($companyId, $respone['UserID'], $respone['ExternalUserID'], false, '外部联系人请求添加好友');
    }

    /**
     * 导购员删除外部联系人
     * @param $respone
     */
    public function delExternalContact($companyId, $respone)
    {
        app('log')->info('导购员删除外部联系人');
        // $this->change($companyId, $respone['UserID'], $respone['ExternalUserID'], false, '导购员删除外部联系人');
    }

    /**
     * 外部联系人删除导购员 （注意：外部联系人删除导购员不删除好友关系）
     * @param $respone
     */
    public function delFollowUser($companyId, $respone)
    {
        app('log')->info('外部联系人删除导购员'); // 注意：外部联系人删除导购员不删除好友关系
        // $this->change($companyId, $respone['UserID'], $respone['ExternalUserID'], true, '外部联系人删除导购员');
    }

    /**
     * 企业微信导购员与商城用户信息改变
     * @param $workUserid 导购员企业微信userid
     * @param $externalUserid 商城用户企业微信外部联系人id
     * @param bool $isFirend 是否是朋友关系
     * @param bool $isBind 是否是绑定关系
     */
    public function change($companyId, $workUserid, $externalUserid, $isFirend = false, $message = '信息')
    {
        $config = app('wechat.work.wechat')->getConfig($companyId);

        $app = Factory::work($config)->external_contact;
        $externalUserInfo = $app->get($externalUserid);
        app('log')->info('$externalUserInfo data:' . var_export($externalUserInfo, 1));
        if (!isset($externalUserInfo['external_contact']['unionid'])) {
            app('log')->info('unionid获取失败, 需要企业或第三方服务商绑定了微信开发者ID');
            die();
        }

        $unionid = $externalUserInfo['external_contact']['unionid'];
        $shopSalesperson = new SalespersonService();
        $workUserInfo = Factory::work($config)->user->get($workUserid);

        $shopSalespersonInfo = $shopSalesperson->salesperson->getInfo(['mobile' => $workUserInfo['mobile'], 'salesperson_type' => 'shopping_guide']);
        $salespersonId = $shopSalespersonInfo['salesperson_id'] ?? 0;
        $membersAssoc = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $membersAssocInfo = $membersAssoc->lists(['unionid' => $unionid]);

        app('log')->info('$membersAssocInfo data:' . var_export($membersAssocInfo, 1));
        if ($membersAssocInfo[0] ?? 0) {
            foreach ($membersAssocInfo as $v) {
                $info = $this->getInfo(['salesperson_id' => $salespersonId, 'user_id' => $v['user_id']]);
                $data = [
                    'company_id' => $v['company_id'],
                    'work_userid' => $workUserid,
                    'salesperson_id' => $salespersonId,
                    'external_userid' => $externalUserid,
                    'unionid' => $unionid,
                    'user_id' => $v['user_id'],
                    'is_friend' => $isFirend,
                    'is_bind' => $info['is_bind'] ?? false,
                    'bound_time' => isset($info['is_bind']) && $info['is_bind'] ? $info['bound_time'] : 0,
                    'add_friend_time' => $isFirend ? time() : 0,
                ];
                app('log')->info('$membersAssocInfo data:' . var_export($data, 1));

                if ($info) {
                    $this->workWechatRelRepository->updateOneBy(['id' => $info['id']], $data);
                } else {
                    $this->workWechatRelRepository->create($data);
                }
                $logData = $data;
                unset($logData['is_bind']);
                unset($logData['bound_time']);
                $logData['remarks'] = $message;
                $workUserList = $app->list($workUserid);
                $this->workWechatRelLogsRepository->create($logData);
                $shopSalesperson->salesperson->updateNum($salespersonId, count($workUserList['external_userid']));
            }
        } else {
            $info = $this->getInfo(['salesperson_id' => $salespersonId, 'unionid' => $unionid]);
            $data = [
                'company_id' => 0,
                'work_userid' => $workUserid,
                'salesperson_id' => $salespersonId,
                'external_userid' => $externalUserid,
                'unionid' => $unionid,
                'user_id' => 0,
                'is_friend' => $isFirend,
                'is_bind' => false,
                'bound_time' => 0,
                'add_friend_time' => $isFirend ? time() : 0,
            ];
            if ($info) {
                $this->workWechatRelRepository->updateOneBy(['id' => $info['id']], $data);
            } else {
                $this->workWechatRelRepository->create($data);
            }
        }
    }

    /**
     * Dynamically call the WorkWechatRelService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->workWechatRelRepository->$method(...$parameters);
    }
}
