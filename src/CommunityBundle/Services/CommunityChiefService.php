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

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityChief;
use CommunityBundle\Entities\CommunityChiefDistributor;
use CommunityBundle\Entities\CommunityChiefZiti;
use CommunityBundle\Repositories\CommunityChiefDistributorRepository;
use CommunityBundle\Repositories\CommunityChiefRepository;
use CommunityBundle\Repositories\CommunityChiefZitiRepository;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\ShopRelMemberService;

class CommunityChiefService
{
    /**
     * @var CommunityChiefRepository
     */
    private $entityRepository;
    /**
     * @var CommunityChiefDistributorRepository
     */
    private $entityDistributorRepository;
    /**
     * @var CommunityChiefZitiRepository
     */
    private $entityZitiRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityChief::class);
        $this->entityDistributorRepository = app('registry')->getManager('default')->getRepository(CommunityChiefDistributor::class);
        $this->entityZitiRepository = app('registry')->getManager('default')->getRepository(CommunityChiefZiti::class);
    }

    /**
     * 创建团长
     * @param $params
     * @return bool
     */
    public function createChief($params)
    {
        if (empty($params['company_id']) || empty($params['distributor_ids'])) {
            throw new ResourceException('参数错误');
        }
        if (empty($params['user_id']) && empty($params['mobile'])) {
            throw new ResourceException('user_id和mobile必须选一个');
        }
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'] ?? 0,
        ];
        $memberService = new MemberService();
        if (!empty($params['mobile'])) {
            $user_id = $memberService->getUserIdByMobile($params['mobile'], $params['company_id']);
            if ($user_id) {
                $filter['user_id'] = $user_id;
            }
        }
        if (empty($filter['user_id'])) {
            throw new ResourceException('无效的用户');
        }
        $memberInfo = $memberService->getMemberInfo($filter);
        if (empty($memberInfo)) {
            throw new ResourceException('无效的会员');
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $chiefData = [
                'company_id' => $memberInfo['company_id'],
                'chief_name' => $params['chief_name'] ?? $memberInfo['username'],
                'chief_avatar' => $params['chief_avatar'] ?? ($memberInfo['avatar'] ?: ''),
                'chief_mobile' => $params['chief_mobile'] ?? $memberInfo['mobile'],
                'chief_desc' => $params['chief_desc'] ?? '',
                'chief_intro' => $params['chief_intro'] ?? '',
                'user_id' => $memberInfo['user_id'],
            ];
            $chief = $this->entityRepository->getInfo(['user_id' => $memberInfo['user_id']]);
            if ($chief) {
                $result = $this->entityRepository->updateOneBy(['chief_id' => $chief['chief_id']], $chiefData);
            } else {
                $result = $this->entityRepository->create($chiefData);
            }
            if (empty($result['chief_id'])) {
                throw new ResourceException('团长创建失败');
            } 

            $batchData = [];
            $timestramp = time();
            foreach ($params['distributor_ids'] as $distributor_id) {
                $batchData[] = [
                    'chief_id' => $result['chief_id'],
                    'distributor_id' => $distributor_id,
                    'bound_time' => $timestramp,
                ];
            }
            if (!empty($batchData)) {
                if ($chief) {
                    $this->entityDistributorRepository->deleteBy(['chief_id' => $result['chief_id']]);
                }
                $this->entityDistributorRepository->batchInsert($batchData);
            }

            // 关联店铺的团长在店铺的会员列表展示
            $filter['shop_id'] = $params['distributor_ids'];
            $shopRelMemberService = new ShopRelMemberService();
            $relShopList = $shopRelMemberService->lists($filter, 1, count($params['distributor_ids']));
            $toInsetShopIds = array_diff($params['distributor_ids'], array_column($relShopList, 'shop_id'));
            foreach ($toInsetShopIds as $shopId) {
                if (empty($shopId)) {
                    continue;
                }
                $data = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $filter['user_id'],
                    'shop_id' => $shopId,
                    'shop_type' => 'distributor',
                ];
                $shopRelMemberService->create($data);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    /**
     * 搜索团长列表
     * @param $filter
     * @param $page
     * @param $page_size
     * @return array
     */
    public function searchChiefList($filter, $page, $page_size)
    {
        if (empty($filter['distributor_ids'])) {
            return $this->getChiefList($filter, $page, $page_size);
        }
        $distributorFilter = [
            'distributor_id' => $filter['distributor_ids'],
        ];
        $distributorMiddleList = $this->entityDistributorRepository->lists($distributorFilter, '*', $page, $page_size);
        $chief_ids = array_column($distributorMiddleList, 'chief_id');
        if (empty($chief_ids)) {
            return $distributorMiddleList;
        }
        $chiefFilter = [
            'chief_id' => $chief_ids,
        ];
        return $this->getChiefList($chiefFilter,0,-1);
    }

    /**
     * 获取团长列表
     * @param $filter
     * @param $page
     * @param $page_size
     * @param $orderBy
     * @return array
     */
    public function getChiefList($filter,$page,$page_size)
    {
        $list = $this->entityRepository->lists($filter,'*',$page,$page_size);
        $chief_ids = array_values(array_unique(array_column($list['list']??[], 'chief_id')));
        if (empty($chief_ids)) {
            return $list;
        }
        $distributorMiddle = $this->entityDistributorRepository->getLists(['chief_id' => $chief_ids]);
        $distributorIds = array_values(array_unique(array_column($distributorMiddle, 'distributor_id')));
        $distributorList = [];
        if ($distributorIds) {
            $distributorList = $this->getDistributorListByIds($distributorIds);
            $distributorList = array_column($distributorList, null, 'distributor_id');
        }
        $zitiList = $this->entityZitiRepository->getLists(['chief_id' => $chief_ids]);
        foreach ($list['list'] as $key => $value) {
            $list['list'][$key]['distributor'] = [];
            foreach ($distributorMiddle as $middle) {
                if ($middle['chief_id']!=$value['chief_id']) {
                    continue;
                }
                if (!empty($distributorList[$middle['distributor_id']])) {
                    $list['list'][$key]['distributor'][] = $distributorList[$middle['distributor_id']];
                }
            }

            $list['list'][$key]['ziti'] = [];
            foreach ($zitiList as $ziti) {
                if ($ziti['chief_id'] != $value['chief_id']) {
                    continue;
                }
                $list['list'][$key]['ziti'][] = $ziti;
            }
        }
        return $list;
    }

    /**
     * 根据手机号检查是否有未绑定的团长
     * @param $company_id
     * @param $user_id
     * @param $mobile
     * @return array
     */
    public function checkBindChief($company_id,$user_id,$mobile)
    {
        $filter = [
            'company_id' => $company_id,
            'chief_mobile' => $mobile,
            'user_id' => 0,
        ];
        $chiefData = $this->entityRepository->getInfo($filter);
        if (!$chiefData) {
            return [];
        }
        $this->entityRepository->updateOneBy(['chief_id'=>$chiefData['chief_id']],['user_id'=>$user_id]);
        return $this->getChiefInfo(['chief_id' => $chiefData['chief_id']]);
    }

    /**
     * 获取团长基础信息
     * @param $filter
     * @return array
     */
    public function getChiefInfo($filter)
    {
        $chief = $this->entityRepository->getInfo($filter);
        if (!$chief) {
            return $chief;
        }
        // 获取团长的微信头像昵称
        $memberService = new MemberService();
        $memberFilter = [
            'company_id' => $chief['company_id'],
            'user_id' => $chief['user_id'],
        ];
        $memberInfo = $memberService->getMemberInfo($memberFilter);
        if (!empty($memberInfo)) {
            $chief_name = $memberInfo['username'] ?? '';
            $chief_avatar = $memberInfo['avatar'] ?? '';
            if ($chief['chief_name'] != $chief_name || $chief['chief_avatar'] != $chief_avatar) {
                $chief = $this->entityRepository->updateOneBy(['chief_id' => $chief['chief_id']], [
                    'chief_name' => $chief_name,
                    'chief_avatar' => $chief_avatar,
                ]);
            }
        }
        // 获取团长的门店列表
        $distributors = $this->getDistributorListByChiefID($chief['chief_id']);
        $chief['distributors'] = $distributors[0] ?? [];
        return $chief;
    }

    /**
     * 根据user_id获取团长信息
     * @param $user_id
     * @return array
     */
    public function getChiefInfoByUserID($user_id)
    {
        return $this->entityRepository->getInfo(['user_id' => $user_id]);
    }

    /**
     * 获取团长IDs
     * @param   $user_id
     * @return  array
     */
    public function getChiefIDByUserID($params)
    {
        if (!$params['user_id']) {
            return [];
        }
        $list = $this->entityRepository->lists($params);
        if (!$list['list']) {
            return [];
        }
        return array_bind_key($list['list'],'user_id');
    }

    /**
     * 获取用户的店铺列表
     * @param $user_id
     * @return array|void
     */
    public function getDistributorListByUserID($user_id)
    {
        $chief = $this->entityRepository->getInfo(['user_id' => $user_id]);
        if (!$chief) {
            throw new ResourceException('当前用户不是团长');
        }
        $chief_distributors = $this->entityDistributorRepository->getLists(['chief_id' => $chief['chief_id']]);
        $distributor_ids = array_column($chief_distributors, 'distributor_id');
        if (empty($distributor_ids)) {
            return [];
        }
        $distributorList = $this->getDistributorListByIds($distributor_ids);

        return $distributorList;
    }

    /**
     * 获取团长的店铺列表
     * @param $chief_id
     * @return array|mixed
     */
    public function getDistributorListByChiefID($chief_id)
    {
        $chief = $this->entityRepository->getInfo(['chief_id' => $chief_id]);
        if (!$chief) {
            throw new ResourceException('当前用户不是团长');
        }
        $chief_distributors = $this->entityDistributorRepository->getLists(['chief_id' => $chief['chief_id']]);
        $distributor_ids = array_column($chief_distributors, 'distributor_id');
        if (empty($distributor_ids)) {
            return [];
        }
        $distributorList = $this->getDistributorListByIds($distributor_ids);

        return $distributorList;
    }

    /**
     * 根据店铺ID集合获取店铺详情
     * @param $distributor_ids
     * @return mixed
     */
    protected function getDistributorListByIds($distributor_ids)
    {
        $distributorService = new DistributorService();
        $list = $distributorService->lists(['distributor_id' => $distributor_ids], [], -1, 0);

        if (in_array(0, $distributor_ids)) {
            $list['list'][] = [
                'distributor_id' => 0,
                'name' => '平台自营',
            ];
        }

        return $list['list'];
    }


    /**
     * 检查用户是否可修改活动
     * @param $user_id
     * @param $activity_id
     * @return array
     */
    public function checkChiefActivity($user_id, $activity_id)
    {
        $chief = $this->getChiefInfoByUserID($user_id);
        if (empty($chief)) {
            throw new ResourceException('只有团长才可以编辑活动');
        }
        $activityService = new CommunityActivityService();
        $activityInfo = $activityService->getActivity($activity_id);
        if (empty($activityInfo)) {
            throw new ResourceException('无效的拼团活动');
        }
        if ($activityInfo['chief_id'] != $chief['chief_id']) {
            throw new ResourceException('只能修改自己的拼团活动');
        }
        return $chief;
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
