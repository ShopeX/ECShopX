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

namespace SalespersonBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Hashids\Hashids;
use DistributionBundle\Services\DistributorService;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use SalespersonBundle\Entities\ShopSalesperson;
use SalespersonBundle\Entities\SalespersonSignLog;

class SignService
{
    public const STATUS_WXCODE_WRIT = 0; // 等待扫码
    public const STATUS_WXCODE_SWEEP = 1; // 已扫码
    public const STATUS_WXCODE_SIGNIN = 2; // 签到成功
    public const STATUS_WXCODE_ERROR = 3; // 扫码失败
    public const STATUS_WXCODE_EXPIRED = 4; // 二维码过期
    public const STATUS_WXCODE_NOTHING = 5; // 二维码不存在
    public const STATUS_WXCODE_SIGNOUT = 6; // 签退成功
    public const STATUS_WXCODE_AUTHFAIL = 7; // 账号不一致

    public const TOKEN_EXP = 120;

    public $signLogRepository;

    public function __construct()
    {
        $this->signLogRepository = app('registry')->getManager('default')->getRepository(SalespersonSignLog::class);
    }

    /**
     * 创建access_token
     * @return string
     */
    public function accessTokenCreated($distributorId, $salespersonId = 0)
    {
        $token = $this->getAccessToken();

        $info = [
            'exp' => time() + (config('common.pc_wxcode_login') ?? self::TOKEN_EXP),
            'time' => time(),
            'status' => self::STATUS_WXCODE_WRIT,
            'distributor_id' => $distributorId,
            'salesperson_id' => $salespersonId,
        ];
        $redis = app('redis')->connection('companys');
        $key = 'salesperson:signin:' . $token;
        $redis->set($key, json_encode($info));
        $redis->expire($key, (config('common.pc_wxcode_login') ?? (self::TOKEN_EXP + 3600)));
        return $token;
    }


    public function getAccessTokenValid($token)
    {
        $redis = app('redis')->connection('companys');
        $key = 'salesperson:signin:' . $token;
        $info = json_decode($redis->get($key), true);
        if (!isset($info['status']) || !isset($info['exp'])) {
            $data = [
                'status' => self::STATUS_WXCODE_NOTHING,
                'msg' => '二维码信息出错',
            ];
            return $data;
        }
        if (time() > $info['exp'] && $info['status'] == self::STATUS_WXCODE_WRIT) {
            $data = [
                'status' => self::STATUS_WXCODE_EXPIRED,
                'msg' => '验证过期',
            ];
            return $data;
        }

        switch ($info['status']) {
            case self::STATUS_WXCODE_WRIT:
                $data = [
                    'status' => self::STATUS_WXCODE_WRIT,
                    'msg' => '等待扫码',
                ];
                break;
            case self::STATUS_WXCODE_SWEEP:
                $data = [
                    'status' => self::STATUS_WXCODE_SWEEP,
                    'msg' => '扫描成功',
                ];
                break;
            case self::STATUS_WXCODE_SIGNIN:
                $data = [
                    'status' => self::STATUS_WXCODE_SIGNIN,
                    'msg' => '签到成功',
                ];
                $data['salesperson'] = $this->getSalespersonDetail($info['salesperson_id']);
                $data['distributor'] = $this->getDistributor($info['distributor_id']);
                break;
            case self::STATUS_WXCODE_ERROR:
                $data = [
                    'status' => self::STATUS_WXCODE_ERROR,
                    'msg' => '取消确认',
                ];
                break;
            case self::STATUS_WXCODE_SIGNOUT:
                $data = [
                    'status' => self::STATUS_WXCODE_SIGNOUT,
                    'msg' => '签退成功',
                ];
                break;
            case self::STATUS_WXCODE_AUTHFAIL:
                $data = [
                    'status' => self::STATUS_WXCODE_AUTHFAIL,
                    'msg' => '验证错误',
                ];
                break;
        }
        return $data;
    }

    /**
     * 扫描二维码
     * @param $token
     * @return bool
     */
    public function accessTokenSweep($token, $salespersonId, $type)
    {
        try {
            $redis = app('redis')->connection('companys');
            $key = 'salesperson:signin:' . $token;
            $info = json_decode($redis->get($key), true);
            if (!$info) {
                throw new ResourceException('授权失败');
            }
            if (isset($info['exp']) && time() > $info['exp']) {
                throw new ResourceException('授权码已过期');
            }
            if (isset($info['status']) && $info['status'] != self::STATUS_WXCODE_WRIT) {
                throw new ResourceException('授权码已被使用');
            }

            $result['status'] = true;

            if ($type == 'signout') {
                $result['salesperson'] = $this->getSalespersonDetail($info['salesperson_id']);
                if ($info['salesperson_id'] != $salespersonId) {
                    $info['status'] = self::STATUS_WXCODE_AUTHFAIL;
                    $redis->set($key, json_encode($info));

                    $result['status'] = false;
                    $result['msg'] = '您的导购账号未签到';
                    return $result;
                }
            }

            $result['distributor'] = $this->getDistributor($info['distributor_id']);
            $curSalesperson = $this->getSalespersonDetail($salespersonId);
            if (($curSalesperson['distributor_id'] ?? 0) != $info['distributor_id']) {
                $result['status'] = false;
                $result['msg'] = '您的导购账号不属于当前门店';
                return $result;
            }

            $info['status'] = self::STATUS_WXCODE_SWEEP;
            $redis->set($key, json_encode($info));

            return $result;
        } catch (\Exception $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    public function accessTokenAuthorize($companyId, $token, $salespersonId, $type, $status = 0)
    {
        if ($status) {
            return $this->accessTokenSuccess($companyId, $token, $salespersonId, $type);
        } else {
            return $this->accessTokenError($token);
        }
    }

    private function accessTokenSuccess($companyId, $token, $salespersonId, $type)
    {
        try {
            $redis = app('redis')->connection('companys');
            $key = 'salesperson:signin:' . $token;
            $info = json_decode($redis->get($key), true);
            if (!$info) {
                throw new ResourceException('授权失败');
            }
            if (isset($info['status']) && $info['status'] != self::STATUS_WXCODE_SWEEP) {
                throw new ResourceException('授权码已被使用');
            }
            if ($type == 'signin') { //签到
                if ($info['salesperson_id'] > 0 && $info['salesperson_id'] != $salespersonId) {
                    $this->saveSignLog($companyId, $info['salesperson_id'], $info['distributor_id'], 'forceout');
                }
                $this->saveSignLog($companyId, $salespersonId, $info['distributor_id'], 'signin');

                $info['status'] = self::STATUS_WXCODE_SIGNIN;
                $info['salesperson_id'] = $salespersonId;
                $redis->set($key, json_encode($info));
            } else { //签退
                $this->saveSignLog($companyId, $salespersonId, $info['distributor_id'], 'signout');

                $info['status'] = self::STATUS_WXCODE_SIGNOUT;
                $redis->set($key, json_encode($info));
            }
            return ['status' => true];
        } catch (\Exception $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    private function accessTokenError($token)
    {
        try {
            $redis = app('redis')->connection('companys');
            $key = 'salesperson:signin:' . $token;
            $info = json_decode($redis->get($key), true);
            if (!$info) {
                throw new ResourceException('授权失败');
            }
            if (isset($info['status']) && $info['status'] != self::STATUS_WXCODE_SWEEP) {
                throw new ResourceException('授权码已被使用');
            }
            $info['status'] = self::STATUS_WXCODE_ERROR;
            $redis->set($key, json_encode($info));
            return ['status' => true];
        } catch (\Exception $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    /**
     * 获取时间戳到毫秒
     * @return bool|string
     */
    private function getAccessToken()
    {
        $time = time();
        $redis = app('redis')->connection('companys');
        $key = 'salesperson:signin:times:' . $time;
        $num = $redis->incr($key);
        $redis->expire($key, 60);
        $hashids = new Hashids($time, 12);
        return $hashids->encode($num);
    }

    private function getSalespersonDetail($salespersonId)
    {
        $filter = [
            'salesperson_id' => $salespersonId,
            'salesperson_type' => 'shopping_guide',
            'is_valid' => 'true',
        ];
        $salespersonService = new SalespersonService();
        return $salespersonService->getSalespersonDetail($filter);
    }

    private function getDistributor($distributorId)
    {
        $filter = [
            'distributor_id' => $distributorId,
        ];
        $distributorService = new DistributorService();
        return $distributorService->getInfo($filter);
    }

    private function saveSignLog($companyId, $salespersonId, $distributorId, $signType)
    {
        $logParams = [
            'company_id' => $companyId,
            'salesperson_id' => $salespersonId,
            'distributor_id' => $distributorId,
            'sign_type' => $signType,
        ];
        $this->signLogRepository->create($logParams);
    }

    //获取签到记录
    public function getSignLogs($filter, $orderBy = ['created' => 'DESC'], $pageSize = 20, $page = 1)
    {
        $relSalesperson = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        $salesperson = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);
        if (!isset($filter['company_id'])) {
            return ['list' => [], 'total_count' => 0];
        }
        //根据mobile/name筛选导购员ID
        if (isset($filter['mobile']) || isset($filter['name|contains'])) {
            // if($filter['mobile'])
            $f = [];
            if ($filter['mobile'] ?? '') {
                $f['mobile'] = $filter['mobile'];
                unset($filter['mobile']);
            }
            if ($filter['name|contains'] ?? '') {
                $f['name|contains'] = $filter['name|contains'];
                unset($filter['name|contains']);
            }
            $salespersonIds = $salesperson->lists($f);
            if (!$salespersonIds['list']) {
                return ['list' => [], 'total_count' => 0];
            }
            $tmpSalespersonIds = array_column($salespersonIds['list'], 'salesperson_id');
            $filter['salesperson_id'] = ($filter['salesperson_id'] ?? 0) ? array_intersect($filter['salesperson_id'], $tmpSalespersonIds) : $tmpSalespersonIds; //筛选结果取交集
            if (!$filter['salesperson_id']) {
                return ['list' => [], 'total_count' => 0];
            }
        }

        //导购签到记录
        $result = $this->signLogRepository->lists($filter, '*', $page, $pageSize);
        if (!($result ['list'] ?? [])) {
            return ['list' => [], 'total_count' => 0];
        }
        $filter = ['company_id' => $filter['company_id'],'salesperson_id' => array_column($result['list'], 'salesperson_id')];

        //导购员基本信息
        $salespersons = $salesperson->lists($filter);
        $salespersons = array_column($salespersons['list'], null, 'salesperson_id');

        //获取店铺基础信息
        $distributorService = new DistributorService();
        $filter = ['company_id' => $filter['company_id'],'distributor_id' => array_column($result['list'], 'distributor_id')];
        $distributors = $distributorService->getDistributorOriginalList($filter);
        if (!$distributors['list']) {
            return ['list' => [], 'total_count' => 0];
        }
        $distributors = array_column($distributors['list'], null, 'distributor_id');
        $mappings = ['signin' => '签到', 'signout' => '签退', 'forceout' => '被动签退'];
        //数据整理
        foreach ($result['list'] as $key => &$value) {
            $value['name'] = $salespersons[$value['salesperson_id']]['name'];
            $value['mobile'] = $salespersons[$value['salesperson_id']]['mobile'];
            $value['shop_name'] = $distributors[$value['distributor_id']]['name'];
            $value['created'] = date('Y-m-d H:i:s', $value['created']);
            $value['updated'] = date('Y-m-d H:i:s', $value['updated']);
            $value['sign_type'] = $mappings[$value['sign_type']];
        }
        return $result;
    }
}
