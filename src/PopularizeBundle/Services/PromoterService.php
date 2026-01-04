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

namespace PopularizeBundle\Services;

// use PopularizeBundle\Neo4jLabels\Promoter;
use PopularizeBundle\MysqlDatabase\Promoter;
use PopularizeBundle\Services\PromoterGradeService;
use PopularizeBundle\Services\SettingService;
use KaquanBundle\Services\VipGradeOrderService;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Services\OrderAssociationService;
use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Entities\Promoter as EntitiesPromoter;
use PopularizeBundle\Entities\PromoterIdentity;

class PromoterService
{
    public $promoterNeoEloquent;
    public function __construct()
    {
        $this->promoterNeoEloquent = new Promoter();
    }

    public function __call($method, $parameters)
    {
        return $this->promoterNeoEloquent->$method(...$parameters);
    }

    /**
     * 保存推广员信息
     */
    public function create($data)
    {
        // 判断是否有推荐人
        $pid = null;
        $pmobile = 0;
        $pname = '';
        if (isset($data['inviter_id']) && $data['inviter_id']) {
            // 判断推荐人是否为推广员
            $inviterInfo = $this->promoterNeoEloquent->getInfoByUserId($data['inviter_id']);
            if ($inviterInfo && $inviterInfo['is_promoter']) {
                $pid = intval($inviterInfo['promoter_id']);
                $memberService = new MemberService();
                $pmobile = $memberService->getMobileByUserId($data['inviter_id'], $data['company_id']);
                $pname = $inviterInfo['promoter_name'];
            }
        }
        // 仅内部推广时，通过A级推广员二维码，注册的会员，会带着puid
        // 将会员添加为B级推广员，并给一个默认的B级身份
        $internalPromoter = false;
        if (isset($data['puid']) && intval($data['puid']) > 0) {
            $internalPromoter = true;
            $defaultIdentity = $this->getDefaultIdentity($data['company_id']);
            $data['identity_id'] = $defaultIdentity['id'] ?? 0;
            $data['is_subordinates'] = $defaultIdentity['is_subordinates'] ?? 0;
            app('log')->info('推广员发展下级 推荐关系跟踪 data=====>'.var_export($data, true));
        }
        $isPromoter = $this->userIsChangePromoter(intval($data['company_id']), intval($data['user_id']), $internalPromoter);

        $createData = [
            'user_id' => intval($data['user_id']),
            'company_id' => intval($data['company_id']),
            'identity_id' => intval($data['identity_id'] ?? 0),
            'is_subordinates' => intval($data['is_subordinates'] ?? 0),
            'pid' => $pid,
            'pmobile' => $pmobile,
            'pname' => $pname,
            'grade_level' => 1,
            'is_promoter' => $isPromoter ? 1 : 0,
            'disabled' => 0,
            'shop_status' => 0,
            'is_buy' => 0,
            'promoter_name' => $data['promoter_name'] ?? '',
            'regions_id' => $data['regions_id'] ?? null,
            'address' => $data['address'] ?? '',
            'created' => time()
        ];
        $this->promoterNeoEloquent->create($createData, $pid);

        // 如果已经成为推广员，并且有上级，那么对上级进行升级判断
        // if ($pid && $createData['is_promoter']) {
        // 如果会员由上级则会自动升级
        if ($pid) {
            $promoterGradeService = new PromoterGradeService();
            $promoterGradeService->upgradeGrade($data['company_id'], $data['inviter_id']);
        }

        // 如果成为推广员，对当前推广员进行升级
        if ($isPromoter) {
            $promoterGradeService = new PromoterGradeService();
            $promoterGradeService->upgradeGrade(intval($data['company_id']), intval($data['user_id']));
        }

        return $createData;
    }

    public function getInfo($filter)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        return $promoterRepository->getInfo($filter);
    }

    /**
     * 获取推广员列表
     */
    public function getPromoterList($filter = array(), $page = 1, $limit = 20)
    {
        $filter['user_id'] = $filter['user_id'] ?? [];

        if (!is_array($filter['user_id'])) {
            $filter['user_id'] = [$filter['user_id']];
        }

        if (isset($filter['mobile']) && $filter['mobile']) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($filter['mobile'], $filter['company_id']);
            if (empty($userId)) {
                $filter['user_id'] = '-1';
            } else {
                if (!empty($filter['user_id'])) {
                    $filter['user_id'] = array_intersect($filter['user_id'], [$userId]);
                    if (empty($filter['user_id'])) {
                        $filter['user_id'] = '-1';
                    }
                } else {
                    $filter['user_id'] = [$userId];
                }
            }
            unset($filter['mobile']);
        }

        if (isset($filter['username']) && $filter['username'] && $filter['user_id'] != '-1') {
            $memberService = new MemberService();
            $userIdList = $memberService->getUserIdByUsername($filter['username'], $filter['company_id']);
            if (empty($userIdList)) {
                $filter['user_id'] = '-1';
            } else {
                if (!empty($filter['user_id'])) {
                    $filter['user_id'] = array_intersect($filter['user_id'], $userIdList);
                    if (empty($filter['user_id'])) {
                        $filter['user_id'] = '-1';
                    }
                } else {
                    $filter['user_id'] = $userIdList;
                }
            }
            unset($filter['username']);
        }
        if (!empty($filter['username'])) {
            unset($filter['username']);
        }
        if (empty($filter['user_id'])) {
            unset($filter['user_id']);
        }
        // 推广员身份名称
        if (isset($filter['identity_name']) && $filter['identity_name']) {
            $promoterIdentityRepository = app('registry')->getManager('default')->getRepository(PromoterIdentity::class);
            $identityInfo = $promoterIdentityRepository->getInfo(['company_id' => $filter['company_id'], 'name' => $filter['identity_name']]);
            $filter['identity_id'] = $identityInfo['id'] ?? -1;
            unset($filter['identity_name']);
        }

        $filter['is_promoter'] = 1;
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterList = $promoterRepository->getLists($filter, $page, $limit, ['created' => 'DESC']);
        if (!$promoterList['list']) {
            return $promoterList;
        }

        $promoterList = $this->__formatPromoterData($filter['company_id'], $promoterList, $limit);
        $promoterList['filter'] = $filter;
        return $promoterList;
    }

    public function getLists($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterList = $promoterRepository->getLists($filter);
        if (!$promoterList['list']) {
            return $promoterList;
        }


        return $promoterList;

    }

    // 推广员详情
    public function getPromoterInfo($companyId, $userId)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $data = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $userId]);
        if (!$data) {
            return [];
        }

        $promoterList['list'][] = $data;
        if (isset($data['pid']) && $data['pid']) {
            $promoterList['list'][] = $promoterRepository->getInfo(['id' => $data['pid']]);
        }

        $promoterList = $this->__formatPromoterData($companyId, $promoterList, 2);
        $return = $promoterList['list'][0];
        if (isset($promoterList['list'][1])) {
            $return['parent_info'] = $promoterList['list'][1];
        }
        return $return;
    }

    // 将推广员移动到指定推广员上
    public function relRemove($companyId, $userId, $newUserId)
    {
        if ($userId == $newUserId) {
            throw new ResourceException('自己不能调到自己');
        }

        $userInfo = $this->getInfoByUserId($userId);
        if (!$userInfo) {// || !$userInfo['is_promoter']
            throw new ResourceException('当前不是推广员' . ($userInfo['is_promoter']??'-'));
        }

        if ($userInfo && $userInfo['company_id'] != $companyId) {
            throw new ResourceException('无效的推广员');
        }

        if ($newUserId) {

            // 判断当前新的推广员是否在 以前推广员的下线
            // 我 不可 移动 到 我的 下级
            $relData = $this->promoterNeoEloquent->getRelationParentBy(['user_id' => $newUserId]);
            if ($relData['total_count'] > 0) {
                if (in_array($userId, array_column($relData['list'], 'user_id'))) { //
                    throw new ResourceException('不能移动到下级');
                }
            }

            $pdata = $this->getInfoByUserId($newUserId);

            if (!$pdata) {
                throw new ResourceException('无效的上级！');
            }

            if ($pdata['company_id'] != $companyId) {
                throw new ResourceException('无效的上级。');
            }

            if ($pdata && ( $userInfo['disabled'])) {//!$userInfo['is_promoter'] ||
                throw new ResourceException('无效的上级-');
            }

            $pid = $pdata['promoter_id']; //从Neo4j里取出来后把记录的id 赋给promoter_id
            $pname = $pdata['promoter_name'] ?? '';
            $memberService = new MemberService();
            $pmobile = $memberService->getMobileByUserId($newUserId, $pdata['company_id']);
        } else {
            if (!isset($userInfo['pid']) || $userInfo['pid'] === null) {
                throw new ResourceException('当前推广员已是顶级');
            }
            $pid = null;
            $pmobile = null;
            $pname = null;
        }

        $this->updateByUserId($userId, ['pid' => $pid, 'pmobile' => $pmobile, 'pname' => $pname]);

        $promoterGradeService = new PromoterGradeService();
        if($newUserId) {
            $promoterGradeService->upgradeGrade($companyId, $newUserId);
        }
        if($userId) {
            $promoterGradeService->upgradeGrade($companyId, $userId);
        }
        if (isset($userInfo['pid']) && $userInfo['pid']) {
            $promoterGradeService->upgradeGrade($companyId, $userInfo['pid']);
        }

        return true;
    }

    // 将推广员移动到指定会员上
    public function memberRelRemove($companyId, $userId, $newUserId)
    {
        if ($userId == $newUserId) {
            throw new ResourceException('自己不能调到自己');
        }

        $userInfo = $this->getInfoByUserId($userId);
        if (!$userInfo) {
            throw new ResourceException('当前不支持调整');
        }
        if (!isset($userInfo['pid']) || !$userInfo['pid']) {
            throw new ResourceException('当前不支持调整');
        }

        if ($userInfo && $userInfo['is_promoter']) {
            throw new ResourceException('当前已经是推广员');
        }
        
        $newUserId = intval($newUserId);
        if ($newUserId <= 0) {
            throw new ResourceException('推广员ID错误');
        }
        // 判断当前新的推广员是否在
        $pdata = $this->getInfoByUserId($newUserId);
        if (!$pdata || $pdata['company_id'] != $companyId) {
            throw new ResourceException('无效的上级');
        }

        if ($pdata && (!$pdata['is_promoter'] || $pdata['disabled'])) {
            throw new ResourceException('无效的上级');
        }

        $pid = $pdata['promoter_id']; //从Neo4j里取出来后把记录的id 赋给promoter_id

        $memberService = new MemberService();
        $pmobile = $memberService->getMobileByUserId($newUserId, $pdata['company_id']);

        $this->updateByUserId($userId, ['pid' => $pid, 'pmobile' => $pmobile]);

        $promoterGradeService = new PromoterGradeService();
        $promoterGradeService->upgradeGrade($companyId, $newUserId);
        $promoterGradeService->upgradeGrade($companyId, $userId);
        $promoterGradeService->upgradeGrade($companyId, $userInfo['pid']);

        return true;
    }

    // 获取指定会员的上级的推广员
    public function getPromoter($companyId, $userId)
    {
        $settingService = new SettingService();

        // 如果没有开启推广员 则不返回
        $isOpen = $settingService->getOpenPopularize($companyId);
        if ($isOpen == 'false') {
            return null;
        }

        $data = $this->getInfoByUserId($userId);

        if ($data && isset($data['company_id']) && $data['company_id'] != $companyId) {
            return null;
            // throw new ResourceException('参数错误');
        }

        if ($data && isset($data['pid']) && $data['pid']) {
            $pdata = $this->getInfoById($data['pid']);
            if (!$pdata) {
                return null;
            }
            return $pdata['user_id'];
        } else {
            return null;
        }
    }

    /**
     * 获取推广员下级列表
     */
    public function getPromoterchildrenList($filter, $depth = null, $page = 1, $limit = 20, $secrecy = 0)
    {
        $offset = ($page - 1) * $limit;

        // company_id id 必填
        $companyId = $filter['company_id'];
        if (!isset($filter['user_id']) && !isset($filter['promoter_id'])) {
            throw new ResourceException('参数错误');
        }

        $promoterList = $this->getRelationChildrenBy($filter, $depth, $offset, $limit);
        if (!$promoterList['list']) {
            return $promoterList;
        }

        $promoterList = $this->__formatPromoterData($companyId, $promoterList, $limit, $secrecy);

        return $promoterList;
    }

    /**
     * 获取推广员下级总数
     */
    public function getPromoterchildrenCount($filter, $depth = null)
    {
        // company_id id 必填
        $companyId = $filter['company_id'];
        if (!isset($filter['user_id']) && !isset($filter['promoter_id'])) {
            throw new ResourceException('参数错误');
        }
        $promoterList = $this->getRelationChildrenBy($filter, $depth, 1, 1);
        return $promoterList['total_count'] ?? 0;
    }

    /**
     * 获取推广员上级列表
     */
    public function getPromoterParentList($filter, $depth, $page = 1, $limit = 20, $secrecy = 0)
    {
        $offset = ($page - 1) * $limit;

        // company_id id 必填
        $companyId = $filter['company_id'];
        if (!isset($filter['user_id']) && !isset($filter['promoter_id'])) {
            throw new ResourceException('参数错误');
        }

        $promoterList = $this->getRelationParentBy($filter, $depth, $offset, $limit);
        if (!$promoterList['list']) {
            return $promoterList;
        }

        $promoterList = $this->__formatPromoterData($companyId, $promoterList, $limit, $secrecy);

        return $promoterList;
    }

    private function __formatPromoterData($companyId, $promoterList, $limit, $secrecy = 0)
    {
        $promoterGradeService = new PromoterGradeService();
        $isOpenPromoterGrade = $promoterGradeService->getOpenPromoterGrade($companyId);
        $config = $promoterGradeService->getPromoterGradeConfig($companyId);
        if (isset($config['grade'])) {
            foreach ($config['grade'] as $key => $row) {
                $gradeCustom[$row['grade_level']] = $row['custom_name'];
            }
        } else {
            $gradeCustom = array_column($promoterGradeService->promoterGradeDefault, 'name', 'grade_level');
        }

        $promoterData = $promoterList['list']['list'] ?? $promoterList['list'];
        $userIds = array_column($promoterData, 'user_id');
        $memberService = new MemberService();
        $page = 1;
        $memberList = $memberService->getList($page, $limit, array('user_id|in' => $userIds));
        $memberList = array_column($memberList['list'], null, 'user_id');

        $wechatUserService = new WechatUserService();
        $wechatUserList = $wechatUserService->getWechatUserList(['company_id' => $companyId, 'user_id' => $userIds]);
        $wechatUserList = array_column($wechatUserList, null, 'user_id');
        $pidList = array_column($promoterData, 'id');
        $childrenCountList = $this->relationChildrenCountByPidList($pidList);
        // 数云模式，查询推广员身份
        if (config('common.oem-shuyun')) {
            $identityIds = array_column($promoterData, 'identity_id');
            $identityIds = array_unique($identityIds);
            $promoterIdentityRepository = app('registry')->getManager('default')->getRepository(PromoterIdentity::class);
            $identityLists = $promoterIdentityRepository->getLists(['id' => $identityIds], 'id,name');
            $identityLists = array_column($identityLists, null, 'id');
        }
        foreach ($promoterList['list'] as $k => $row) {
            if (empty($row)) {
                continue;
            }
            $promoterList['list'][$k]['children_count'] = $childrenCountList[$row['id']]['count'] ?? 0;
            // $promoterList['list'][$k]['children_count'] = $this->relationChildrenCountByUserId($row['user_id'], 1);
            $promoterList['list'][$k]['bind_date'] = date('Y-m-d', $row['created']);
            if (isset($memberList[$row['user_id']])) {
                // if ($secrecy) {
                //     $memberList[$row['user_id']]['mobile'] = substr_replace($memberList[$row['user_id']]['mobile'], '*****', 3, 5);
                // }
                $promoterList['list'][$k] = array_merge($memberList[$row['user_id']], $promoterList['list'][$k]);
                $promoterList['list'][$k]['promoter_grade_name'] = $gradeCustom[$row['grade_level']] ?? '';
                $promoterList['list'][$k]['is_open_promoter_grade'] = $isOpenPromoterGrade;
            }
            if (isset($wechatUserList[$row['user_id']])) {
                $promoterList['list'][$k]['nickname'] = (string)$wechatUserList[$row['user_id']]['nickname'];
                $promoterList['list'][$k]['headimgurl'] = $wechatUserList[$row['user_id']]['headimgurl'];
            }
            // 数云模式，推广员身份
            if (config('common.oem-shuyun')) {
                $promoterList['list'][$k]['identity_name'] = $identityLists[$row['identity_id']]['name'] ?? '';
            }
        }
        return $promoterList;
    }

    /**
     * 指定会员成为推广员
     *
     * @param int $companyId 企业ID
     * @param int $userId 会员ID
     * @param boolean $force 是否强制成为推广员
     */
    public function changePromoter($companyId, $userId, $force = false, $params = null)
    {
        // 如果不是强制当前用户成为推广员，
        // 那么对当前用户进行检查，是否满足成为推广员的条件
        if (!$force) {
            $isPromoter = $this->userIsChangePromoter($companyId, $userId);
            // 当前用户未达到成为推广员条件 那么则不进行更新
            if (!$isPromoter) {
                throw new ResourceException('不满足条件');
            }
        }

        $info = $this->promoterNeoEloquent->getInfoByUserId($userId);
        $_data = [
            'promoter_name' => $params['promoter_name'] ?? 0,
            'regions_id' => $params['regions_id'] ?? 0,
            'address' => $params['address'] ?? 0,
        ];
        // 查询推广员身份
        if (isset($params['identity_id']) && $params['identity_id'] > 0) {
            $identityData = $this->getPromoterIdentity($companyId, $params);
            $_data = array_merge($_data, $identityData);
        }
        if (!$info) {
            $insertData['user_id'] = $userId;
            $insertData['company_id'] = $companyId;
            $memberService = new MemberService();
            $inviterId = $memberService->getinviterByUserId($userId, $companyId);
            if ($inviterId) {
                $insertData['inviter_id'] = $inviterId;
            }
            $insertData = array_merge($insertData, $_data);
            $info = $this->create($insertData);
        } else {
            if ($info['company_id'] != $companyId) {
                throw new ResourceException('数据异常');
            }
        }

        if (1 == $info['is_promoter']) {
            throw new ResourceException('该用户已经是推广员');
        }
        $_data['is_promoter'] = 1;
        $_data['disabled'] = 0;
        $data = $this->promoterNeoEloquent->updateByUserId($userId, $_data);
        $promoterGradeService = new PromoterGradeService();
        $promoterGradeService->upgradeGrade($companyId, $userId);

        return $data;
    }

    /**
     * 获取推广员的身份相关数据，用于添加推广员
     */
    public function getPromoterIdentity($companyId, $params)
    {
        $promoterIdentityRepository = app('registry')->getManager('default')->getRepository(PromoterIdentity::class);
        $identityInfo = $promoterIdentityRepository->getInfo(['company_id' => $companyId, 'id' => $params['identity_id']]);
        if (empty($identityInfo)) {
            throw new ResourceException('推广员身份错误');
        }
        $data = [
            'identity_id' => $identityInfo['id'],
            'is_subordinates' => $identityInfo['is_subordinates'],
        ];
        if ($data['is_subordinates'] == 1) {
            return $data;
        }
        // 推广员身份为B级时，需要选择A级身份的推广员
        
        $data['pid'] = $params['pid'] ?? 0;
        $data['pmobile'] = $params['pmobile'] ?? '';
        $data['pname'] = $params['pname'] ?? '';
        if (!$data['pid'] || !$data['pmobile']) {
            throw new ResourceException('上级推广员信息错误');
        }
        return $data;
    }

    /**
     * 获取A级身份的推广员列表
     * @param  string $companyId 企业ID
     * @param  string $page      页数
     * @param  string $pageSize  每页条数
     */
    public function getFirstIdentityPromoterList($companyId, $page, $pageSize)
    {
        $filter = [
            'company_id' => $companyId,
            'is_subordinates' => 1,
            'is_promoter' => 1,
            'disabled' => 0,
        ];
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $lists = $promoterRepository->lists($filter, $page, $pageSize);
        if ($lists['total_count'] == 0) {
            return $lists;
        }
        $userIds = array_column($lists['list'], 'user_id');
        $memberService = new MemberService();
        $mobiles = $memberService->getMobileByUserIds($companyId, $userIds);
        $identityIds = array_column($lists['list'], 'identity_id');
        $promoterIdentityRepository = app('registry')->getManager('default')->getRepository(PromoterIdentity::class);
        $identityLists = $promoterIdentityRepository->getLists(['id' => $identityIds], 'id,name');
        $identityLists = array_column($identityLists, null, 'id');
        foreach ($lists['list'] as $key => $list) {
            $list['mobile'] = $mobiles[$list['user_id']] ?? '';
            $list['identity_name'] = $identityLists[$list['identity_id']]['name'] ?? '';
            $lists['list'][$key] = $list;
        }
        return $lists;
    }

    /**
     * 校验当前用户是否可以成为推广员
     * 即：当前会员满足商家设定成为推广员的条件
     */
    public function userIsChangePromoter($companyId, $userId, $internalPromoter = false)
    {
        $settingService = new SettingService();
        $isOpen = $settingService->getOpenPopularize($companyId);
        if ($isOpen == 'false') {
            return false;
        }

        $config = $settingService->getConfig($companyId);
        $isPromoter = false;
        switch ($config['change_promoter']['type']) {
        case 'no_threshold':
            $isPromoter = true;
            break;
        case 'internal':
            // 仅内部开启推广
            if ($internalPromoter) {
                $isPromoter = true;
            } else {
                $isPromoter = false;
            }
            break;
        case 'vip_grade':
            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId);
            if (isset($vipgrade['is_vip']) && $vipgrade['is_vip'] && isset($vipgrade['vip_type']) && $vipgrade['vip_type'] == $config['change_promoter']['filter']['vip_grade']) {
                $isPromoter = true;
            }
            break;
        case 'consume_money':
            $memberService = new MemberService();
            $totalConsumption = $memberService->getTotalConsumption($userId);
            if (bcdiv($totalConsumption, 100, 2) >= $config['change_promoter']['filter']['consume_money']) {
                $isPromoter = true;
            }
            break;
        case 'order_num':
            $filter = [
                'user_id' => $userId,
                'company_id' => $companyId,
                'order_status' => 'DONE'
            ];
            $orderAssociationService = new OrderAssociationService();
            $orderTotal = $orderAssociationService->countOrderNum($filter);
            if ($orderTotal >= $config['change_promoter']['filter']['order_num']) {
                $isPromoter = true;
            }
            break;
        }
        return $isPromoter;
    }

    /**
     * 推广员虚拟店铺状态修改
     */
    public function updateShopStatus($companyId, $userId, $status, $reason = null)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterInfo = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $userId, 'is_promoter' => 1, 'disabled' => false]);
        if (!$promoterInfo) {
            throw new ResourceException('当前推广员无权限');
        }

        // 如果当前小店已经开通了，则不需要再次申请
        if ($status == 2 && $promoterInfo['shop_status'] == 1) {
            return true;
        }

        $updateData['shop_status'] = intval($status);
        if ($reason) {
            $updateData['reason'] = trim($reason);
        }

        $promoterRepository->updateOneBy(['id' => $promoterInfo['id']], $updateData);
        return true;
    }


    /**
     * 推广员虚拟店铺状态修改
     */
    public function updateShopStatusSalesperson($companyId, $userId, $status = 1, $reason = null)
    {
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterInfo = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $userId,  'disabled' => false]);
        //
        if (!$promoterInfo) {
            $data= array(
                'company_id' => $companyId,
                'user_id' => $userId
            );
            $dataCreate = $this->create($data);
            // throw new ResourceException('当前推广员无权限');
            $promoterInfo = $promoterRepository->getInfo(['company_id' => $companyId, 'user_id' => $userId,  'disabled' => false]);
        }

        $updateData['is_promoter'] = 1;
        $updateData['shop_status'] = intval($status);
        if ($reason) {
            $updateData['reason'] = trim($reason);
        }

        $promoterRepository->updateOneBy(['id' => $promoterInfo['id']], $updateData);
        return true;
    }


    // SELECT count(1) as order_num,
    // sum(total_fee) as total_Fee,
    // sum(if(aftersales_bn > 0, 1, 0)) as aftersales_num,
    // sum(total_fee) as refund_Fee,
    // if(count(1)>0,sum(total_fee) /count(1),0) as price_fee,
    // count(1) as member_num ,
    // aa.*
    // FROM popularize_brokerage as bb left join orders_normal_orders oo ON bb.order_id = oo.order_id left join aftersales as aa ON bb.order_id = aa.order_id WHERE bb.user_id = 168

    public function getSalesmanCount($authInfo,$params){
        $userId = $authInfo['user_id'];

        if(env('DEBUG_SALESMAN_USERID',false) ){
            $userId = env('DEBUG_SALESMAN_USERID');
            
        }
        $sqlWhereDate = ' ';
        $dateLen = 1;
 
        if(isset($params['date']) && $params['date']){
            switch($params['datetype']){
                case 'y':
                    $date = $params['date'];
                    $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,4) = '".$date."' ";
                    $dateLen = 7;
                    break;
                case 'm':
                    $date = $params['date'];
                    $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,7) = '".$date."' ";
                    $dateLen = 10;
                    break;
                case 'd':
                    $date = $params['date'];
                    $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,10) = '".$date."' ";
                    $dateLen = 10;
                    break;
                }            
        }

        $sqlWhereShopId = ' ';
        if(isset($params['distributor_id']) && $params['distributor_id'] ){
            $sqlWhereShopId = " and oo.distributor_id = ".$params['distributor_id'] . ' ';
        }
        // $conn = app("registry")->getConnection('default');
        // $qb = $conn->createQueryBuilder();
        $countSql = "SELECT    if(count(1)>0 ,  sum(if(price > 0,1 ,0) ),0) AS order_num,
        SUM(if(price > 0,total_fee,0)) AS total_Fee,
        SUM(if(price < 0,total_fee,0)) AS refund_Fee,
                        if(count(1)>0 ,sum(if(aftersales_bn > 0, 1, 0)),0) as aftersales_num,
                        if(count(1)>0 ,sum(refund_fee),0) as aftersale_Fee,
                        if(count(1)>0,sum(total_fee) /count(1),0) as price_fee,
                        count(distinct oo.user_id) as member_num ,
                        concat( oo.user_id)
                        FROM popularize_brokerage as bb 
                        left join orders_normal_orders oo ON bb.order_id = oo.order_id 
                        left join aftersales as aa ON bb.order_id = aa.order_id 
                        WHERE bb.user_id =  {$userId} ";

        $countSql .= $sqlWhereDate ;
        $countSql .= $sqlWhereShopId ;
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-countSql:". json_encode($countSql));

        $conn = app('registry')->getConnection('default');

        $relContents = $conn->executeQuery($countSql)->fetch();

        return $relContents;

    }


    public function getSalesmanStatic($authInfo,$params){
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-params:". json_encode($params));

        // datetype： y/m/d
        // date:   2024/2024-05/2024-05-23
        $sqlWhereDate = ' ';
        $dateLen = 1;

        $level_config = array('all' => "'first_level', 'second_level' ", 'lv1' => "'first_level'", 'lv2' => "'second_level'");
        if( isset($params['tab']) 
            && isset($level_config[$params['tab']]) 
            && $level_config[$params['tab']]  )
        {
            $sqlTab = " and bb.brokerage_type in ( {$level_config[$params['tab']] } ) ";
        }

        $params['datetype'] = $params['datetype'] ?? '';
        switch($params['datetype']){
            case 'y':
                $date = $params['date'];
                $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,4) = '".$date."' ";
                $dateLen = 7;
                break;
            case 'm':
                $date = $params['date'];
                $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,7) = '".$date."' ";
                $dateLen = 10;
                break;
            case 'd':
                $date = $params['date'];
                $sqlWhereDate = " and   substr(from_unixtime(bb.created),1,10) = '".$date."' ";
                $dateLen = 10;
                break;
            }

        $userId = $authInfo['user_id'];

        if(env('DEBUG_SALESMAN_USERID',false) ){
            $userId = env('DEBUG_SALESMAN_USERID');
            
        }

        $sqlWhereShopId = ' ';
        if(isset($params['distributor_id']) && $params['distributor_id'] ){
            $sqlWhereShopId = " and oo.distributor_id = ".$params['distributor_id'] . ' ';
        }

        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $countSql = "SELECT oo.distributor_id,substr(from_unixtime(bb.created) ,1,{$dateLen}) as date_brokerage ,    sum(if(price > 0,1 ,0) ) AS order_num,
        SUM(if(price > 0,total_fee,0)) AS total_Fee,
        SUM(if(price < 0,total_fee,0)) AS refund_Fee, if(count(1)>0,sum(bb.rebate),0 ) as total_rebate, if(count(1)>0 ,sum(if(aftersales_bn > 0, 1, 0)),0) as aftersales_num, if(count(1)>0 ,sum(refund_fee),0) as aftersale_Fee, if(count(1)>0,sum(total_fee) /count(1),0) as price_fee, count(distinct oo.user_id) as buy_member_num , concat( oo.user_id) FROM popularize_brokerage as bb left join orders_normal_orders oo ON bb.order_id = oo.order_id left join aftersales as aa ON bb.order_id = aa.order_id WHERE bb.user_id =  {$userId} ";
        $countSql .= $sqlWhereDate ;
        $countSql .= $sqlWhereShopId ;
        $countSql .= $sqlTab ?? ' ';
        $countSql .= "group by substr(from_unixtime(created) ,1,{$dateLen}) order by created desc";
        $conn = app('registry')->getConnection('default');
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-countSql:". json_encode($countSql));

        $listBrokerage = $conn->executeQuery($countSql)->fetchAll();
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listBrokerage:". json_encode($listBrokerage));
        // 统计推广员增加人数
        $listPromoter = $this->getSalesPromotersStatic($userId, $dateLen , $sqlWhereDate, $sqlWhereShopId ) ;
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listPromoter:". json_encode($listPromoter));

        // 合并数据
        $listMerge    = $this->mergeSaleStaticByDate($listPromoter, $listBrokerage);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listMerge:". json_encode($listMerge));

        return is_array($listMerge) ? $listMerge : array();

    }

    public function getSalesPromotersStatic($userId, $dateLen , $sqlWhereDate, $sqlWhereShopId ) {
        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $countSql = "select substr(from_unixtime(created)  ,1,{$dateLen})  as date_brokerage , count(1) as  member_num from popularize_promoter bb WHERE bb.pid =  {$userId} ";
        $countSql .= $sqlWhereDate ;
        $countSql .= "group by substr(from_unixtime(created) ,1,{$dateLen}) order by created desc";
        $conn = app('registry')->getConnection('default');
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-countSql:". json_encode($countSql));

        $listPromoter = $conn->executeQuery($countSql)->fetchAll();
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-listPromoter:". json_encode($listPromoter));

        return is_array($listPromoter) ? $listPromoter : array();

    }



    /**
     *       ['year_month' => '2023-01', 'data1' => 'value1'],
     *       ['year_month' => '2023-02', 'data1' => 'value2']
     */
    public function mergeSaleStaticByDate($orderList,$memberList)
    {
        // 创建一个新的数组来保存合并后的结果
        $staticListDate = [];        
        // 遍历第一个数组，将数据按照 year_month 添加到新数组中
        foreach ($orderList as $item) {
            $staticListDate[$item['date_brokerage']] = $item; // 这里直接赋值，后续循环会覆盖或添加数据
        }
        
        // 遍历第二个数组，根据 year_month 更新或添加到新数组中
        foreach ($memberList as $item) {
            if (isset($staticListDate[$item['date_brokerage']])) {
                // 如果键已经存在，则将item的数据合并到已存在的数组中
                $staticListDate[$item['date_brokerage']] = array_merge($staticListDate[$item['date_brokerage']], $item);
            } else {
                // 如果键不存在，则直接添加新的数组元素
                $staticListDate[$item['date_brokerage']] = $item;
                $staticListDate[$item['date_brokerage']]['member_num'] = 0;
            }
        }

        // print_r($staticListDate);   
        // $list  = array_shift($staticListDate);
        // print_r($list);
         $ret = array();   
        foreach($staticListDate as $k => &$itemStatic){
            if(!isset($itemStatic['order_num'])){
                $item = array();
                $item['order_num'] = '0';
                $item['distributor_id'] = '0';
                $item['total_Fee'] = '0';
                $item['aftersales_num'] = '0';
                $item['refund_Fee'] = '0';
                $item['price_fee'] = '0';
                $item['buy_member_num'] = '0';
                $item['total_rebate'] = '0';
                $ret[] = array_merge($itemStatic,$item);
            }else{
                $ret[] = $itemStatic;
            }
        }

        foreach($ret as $kr => &$itemRet){
            $itemRet['salesName'] = "推广员tobe";

        }

        // 打印合并后的数组a
        // echo "----------------ret -----------------------";
        //  print_r($ret);
        return $ret;
    }
//     SELECT
//     SUBSTR(FROM_UNIXTIME(bb.created),
//     1,
//     7) AS date_brokerage,
//     IF(COUNT(1) > 0,
//     COUNT(1),
//     0) AS order_num,
//     IF(COUNT(1) > 0,
//     SUM(total_fee),
//     0) AS total_Fee,
//     IF(
//         COUNT(1) > 0,
//         SUM(IF(aftersales_bn > 0, 1, 0)),
//         0
//     ) AS aftersales_num,
//     IF(COUNT(1) > 0,
//     SUM(refund_fee),
//     0) AS refund_Fee,
//     IF(
//         COUNT(1) > 0,
//         SUM(total_fee) / COUNT(1),
//         0
//     ) AS price_fee,
//     COUNT(DISTINCT oo.user_id) AS member_num,
//     CONCAT(oo.user_id)
// FROM
//     popularize_brokerage AS bb
// LEFT JOIN orders_normal_orders oo ON
//     bb.order_id = oo.order_id
// LEFT JOIN aftersales AS aa
// ON
//     bb.order_id = aa.order_id
// WHERE
//     bb.user_id = 168  AND SUBSTR(
//         FROM_UNIXTIME(bb.created),
//         1,
//         4
//     ) = '2022'
// GROUP BY
//     SUBSTR(FROM_UNIXTIME(created),
//     1,
//     7)
// ORDER BY
//     created
// DESC

    /**
     * 获取默认的B级身份
     * @param  [type] $companyId [description]
     * @return [type]            [description]
     */
    public function getDefaultIdentity($companyId)
    {
        $promoterIdentityRepository = app('registry')->getManager('default')->getRepository(PromoterIdentity::class);
        $info = $promoterIdentityRepository->getInfo(['company_id' => $companyId, 'is_default' => 1, 'is_subordinates' => 0]);
        return $info;
    }

    /**
     * 会员，是否可以调整上级（非推广员、有上级）
     * @param  string $companyId 企业ID
     * @param  array $userIds   会员ID
     */
    public function getMemberIsCanChangepid($companyId, $userIds)
    {
        $filter = [
            'company_id' => $companyId,
            'user_id' => $userIds,
            'is_promoter' => 0,
            'pid|gt' => 0,
        ];
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $promoterList = $promoterRepository->getLists($filter, 1, 1000);
        if (!$promoterList['list']) {
            return [];
        }
        $result = [];
        foreach ($promoterList['list'] as $value) {
            $result[$value['user_id']] = true;
        }
        return $result;
    }
}
