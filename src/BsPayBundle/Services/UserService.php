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

namespace BsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;

use BsPayBundle\Entities\UserCard;
use BsPayBundle\Entities\RegionsThird;
use BsPayBundle\Services\EntryApplyService;
use BsPayBundle\Services\UserEntService;
use BsPayBundle\Services\UserIndvService;

use CompanysBundle\Entities\Operators;

/**
 * 用户
 */
class UserService
{
    public $userCardRepository;

    // 用户审核状态
    public const AUDIT_WAIT = 'A';//待审核
    public const AUDIT_FAIL = 'B';//审核失败
    public const AUDIT_USER_FAIL = 'C';//开户失败
    public const AUDIT_CARD_FAIL = 'D';//开户成功但未创建结算账户
    public const AUDIT_SUCCESS = 'E';//开户和创建结算账户成功

    // 结算卡配置状态
    public const CARD_INREVIEW = 'A';// 审核中
    public const CARD_SUCCESS = 'B';// 配置成功
    public const CARD_FAIL = 'C';// 配置失败

    public const BUSINESS_SUCC = 'S';
    public const BUSINESS_FAIL = 'F';

    public $ent_type_options = [
        '1' => '政府机构',
        '2' => '国营企业',
        '3' => '私营企业',
        '4' => '外资企业',
        '5' => '个体工商户',
        '6' => '其它组织',
        '7' => '事业单位',
        '8' => '集体经济',
    ];

    public function __construct()
    {
        $this->userCardRepository = app('registry')->getManager('default')->getRepository(UserCard::class);
    }

    /**
     * 获取用户进件审核状态
     */
    public function getAuditState($filter)
    {
        $operator = $this->getOperator();
        if (!isset($filter['operator_id']) && !isset($filter['id'])) {
            $filter['operator_id'] = $operator['operator_id'];
            $filter['operator_type'] = $operator['operator_type'];
        }
        app('log')->info('bspay::getAuditState::filter====>'.json_encode($filter));
        // 获取审核
        $result = $this->getUserInfo($filter);
        if (!$result) {
            return ['audit_state' => 'D', 'audit_desc' => '待提交', 'ent_type_options' => $this->ent_type_options];
        }
        switch ($result['audit_state']) {
            case '0':
            case 'A':
                $result['audit_state'] = 'A';
                break;
            case 'B':
            case 'C':
            case 'D':
                $result['audit_state'] = 'B';
                break;
            case 'E':
                $result['audit_state'] = 'C';
                break;
            default:
                $result['audit_state'] = 'A';
                break;
        }

        $res = ['audit_state' => $result['audit_state'], 'audit_desc' => $result['audit_desc'], 'update_time' => $result['approved_time'], 'updated' => $result['updated'], 'user_type' => $result['user_type'], 'ent_type_options' => $this->ent_type_options];
        return $res;
    }

    /**
     * 获取用户进件基本信息
     */
    public function getUserInfo($filter)
    {
        $entryApplyService = new EntryApplyService();
        $applyInfoList = $entryApplyService->getLists($filter, '*', 1, 1, ['created' => 'DESC']);
        app('log')->info('getUserInfo filter====>'.json_encode($filter));
        app('log')->info('getUserInfo applyInfoList====>'.json_encode($applyInfoList));
        $applyInfo = $applyInfoList[0] ?? [];
        app('log')->info('getUserInfo applyInfo====>'.json_encode($applyInfo));
        if (!$applyInfo) {
            return false;
        }
        switch ($applyInfo['user_type']) {
            case 'ent':
                $service = new UserEntService();
                break;
            case 'indv':
                $service = new UserIndvService();
                break;            
            default:
                throw new ResourceException('获取用户进件信息失败');
                break;                
        }
        $userInfo = $service->getInfoById($applyInfo['user_id']);
        $userInfo['user_type'] = $applyInfo['user_type'];
        $userInfo['approved_time'] = $applyInfo['updated'];
        return $userInfo;
    }

    /**
     * 获取用户进件详情
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    public function getUserDetail($filter)
    {
        $operator = $this->getOperator();
        if (!isset($filter['operator_id']) && !isset($filter['id'])) {
            $filter['operator_id'] = $operator['operator_id'];
            $filter['operator_type'] = $operator['operator_type'];
        }
        $userInfo = $this->getUserInfo($filter);
        if (empty($userInfo)) {
            return false;
        }
        $filter = [
            'user_id' => $userInfo['id'],
            'company_id' => $userInfo['company_id'],
            'user_type' => $userInfo['user_type'],
        ];
        $cardInfo = $this->userCardRepository->getInfo($filter);
        if (empty($cardInfo)) {
            return $userInfo;
        }
        unset($cardInfo['id']);
        return array_merge($userInfo, $cardInfo);
    }



    /**
     * 获取管理员信息
     * @return 
     */
    public function getOperator()
    {
        $operatorId = app('auth')->user()->get('operator_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $operatorId = app('auth')->user()->get('distributor_id');
        } elseif ($operatorType == 'merchant') {
            $operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
            $operatorInfo = $operatorsRepository->getInfo(['operator_id' => $operatorId]);
            if (!$operatorInfo) {
                throw new ResourceException('没有账号信息');
            }
            if (isset($operatorInfo['is_merchant_main']) && $operatorInfo['is_merchant_main']) {
                $operatorId = $operatorInfo['merchant_id'];
            }
        }
        // if ($operatorType == 'dealer') {
        //     $operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        //     $operatorInfo = $operatorsRepository->getInfo(['operator_id' => $operatorId]);
        //     if (!$operatorInfo) {
        //         throw new ResourceException('没有账号信息');
        //     }
        //     if (isset($operatorInfo['is_dealer_main']) && !$operatorInfo['is_dealer_main']) {
        //         $operatorId = $operatorInfo['dealer_parent_id'];
        //     }
        // }

        return ['operator_type' => $operatorType, 'operator_id' => $operatorId];
    }

    public function getRegionsList()
    {
        $cacheData = app('redis')->connection('default')->get('bspay_regions');
        if ($cacheData) {
            $regions = json_decode($cacheData, true);
            if ($regions) {
                return $regions;
            }
        }

        $regionsRepository = app('registry')->getManager('default')->getRepository(RegionsThird::class);
        $regionsInfo = $regionsRepository->lists(['pid' => 0]);

        $regions = $regionsInfo['list'];
        foreach ($regions as $k => $v) {
            $a = $regionsRepository->lists(['pid' => $v['id']]);
            $regions[$k]['children'] = $a['list'];
        }
        app('redis')->connection('default')->set('bspay_regions', json_encode($regions, 256));
        return $regions;
    }

    public function getHuifuIdByOperatorId($companyId, $operatorId, $operatorType)
    {
        $filter = [
            'company_id' => $companyId,
            'operator_type' => $operatorType,
            'operator_id' => $operatorId,
            'status' => 'APPROVED',
        ];
        $userInfo = $this->getUserInfo($filter);
        return $userInfo['huifu_id'] ?? false;
    }

    public function getRegionsThirdList()
    {
        $cacheData = app('redis')->connection('default')->get('bspay_regions_third');
        if ($cacheData) {
            $regions = json_decode($cacheData, true);
            if ($regions) {
                return $regions;
            }
        }

        $regionsRepository = app('registry')->getManager('default')->getRepository(RegionsThird::class);
        $regionsInfo = $regionsRepository->lists(['pid' => 0]);
        $regions = $regionsInfo['list'];
        foreach ($regions as $k => $v) {
            $a = $regionsRepository->lists(['pid' => $v['id']]);
            $regions[$k]['children'] = $a['list'];
            foreach ($regions[$k]['children'] as $k1 => $v1) {
                $b = $regionsRepository->lists(['pid' => $v1['id']]);
                $regions[$k]['children'][$k1]['children'] = $b['list'];
            }
        }
        app('redis')->connection('default')->set('bspay_regions_third', json_encode($regions, 256));
        return $regions;
    }
}
