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

namespace ShuyunBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use ShuyunBundle\Services\Client\Request;

/**
 * 会员
 */
class MembersService
{


    private $client;
    public $companyId;
    public $userId;
    public $pointType = [
        'member_care' => '会员关怀',
        'points_off_cash' => '积分抵现',
        'points_refund' => '积分抵现退款',
    ];

    public function __construct($companyId, $userId = null)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->client = new Request($companyId, $userId);
    }

    /**
     * 会员详情接口
     */
    // public function getMemberDetail($params)
    // {
    //     app('log')->debug('数云-获取会员详情参数: =>'.var_export($params, 1));
    //     $url = '/lpee-member-interfaces/v1/spi/spmall/member/get';
    //     $resp = $this->client->json($url, $params);
    //     app('log')->debug('数云-获取会员详情结果: =>'.var_export($resp, 1));
    //     if ($resp->code != 0) {
    //         app('log')->error('数云-获取会员详情结果Error :'. $resp->message);
    //         throw new AccessDeniedHttpException($resp->message);
    //     }
    //     return $resp->data;
    // }

    /**
     * 静默入会查询,使用数云中心小程序appid和商城小程序的unionid进行查询会员的mobile
     * @param  array $data 
     * @return [type]       [description]
     */
    public function memberSilentSearch($data)
    {
        
        $params = [
            'appId' => $data['shuyunappid'],
            'unionid' => $data['unionid'],
        ];
        $url = '/lpee-interfaces-service/v1/spmall/member/silent/search';
        $resp = $this->client->get($url, $params);
        if ($resp->code != 0) {
            app('log')->error('数云-静默入会查询结果Error :'. $resp->message.',code:'.$resp->code);
            return false;
        }
        return $resp->data['mobile'] ?? false;
    }
    /**
     * 会员积分查询
     */
    public function getMemberPoint()
    {
        try {
            $url = '/lpee-interfaces-service/v1/spmall/member/point';
            $resp = $this->client->get($url, []);
            app('log')->debug('数云-会员积分查询结果: =>'.var_export($resp, 1));
            if ($resp->code != 0) {
                app('log')->error('数云-会员积分查询结果Error :'. $resp->message.',code:'.$resp->code);
                return 0;
            }
            return $resp->data['point'] ?? 0;
        } catch (AccessDeniedHttpException $e) {
            app('log')->error('数云-会员积分查询结果Error :'. $e->getMessage());
            return 0;
        }
    }

    /**
     * 会员注册
     */
    public function memberRegister($data)
    {
        $params = [
            'mobile' => $data['mobile'],
            'unionid' => $data['unionid'],
        ];
        app('log')->debug('数云-会员注册参数: =>'.var_export($params, 1));
        $url = '/lpee-member-interfaces/v1/spi/spmall/member/register';
        $resp = $this->client->json($url, $params);
        app('log')->debug('数云-会员注册结果: =>'.var_export($resp, 1));
        if ($resp->code != 0) {
            app('log')->error('数云-会员注册结果Error :'. $resp->message);
            throw new AccessDeniedHttpException($resp->message);
        }
        return $resp->data;
    }

    public function shuyunAddPoint($point, $status = true, $record = '', $orderId = '', array $otherParams = [])
    {
        app('log')->info('shuyunAddPoint start otherParams====>'.var_export($otherParams, true));
        // 1. 积分抵扣
        //   扣减接口，remark=订单号;bizUnit="积分抵现";sequence=订单号
        // 2. 订单取消，看下从哪里知道需要退积分
        //   增加接口，remark=订单号;bizUnit="积分抵现退款";sequence=订单号
        // 3. 新会员注册
        //   增加接口，remark="注册送积分";bizUnit="会员关怀";sequence=user_id
        $point_type = $otherParams['point_type'] ?? '';
        if ($point_type == '' || !isset($this->pointType[$point_type]) || $point == 0) {
            return false;
            // throw new AccessDeniedHttpException("积分失败");
        }
        $bizUnit = $this->pointType[$point_type];
        if ($orderId == '') {
            $remark = $record;
            $sequence = $this->userId;
        } else {
            $remark = $orderId;
            $sequence = $otherParams['refund_bn'] ?? $orderId;// 退款时，为退款单号
        }
        $params = [
            'sequence' => $sequence,
            'changePoint' => $point,
            'remark' => $remark,
            'bizUnit' => $bizUnit,
            'operator' => $this->companyId,
        ];
        try {
            if ($status == true) {
                // 积分发放
                $result = $this->memberPointGain($params);
            } else {
                // 积分扣减
                $result = $this->memberPointDeduct($params);
            }
        } catch (\Exception $e) {
            $error = [
                'status' => $status,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            throw new AccessDeniedHttpException("积分扣减失败");
            app('log')->error('数云-会员积分加减结果Error :'. var_export($error, true));
            return false;
        }
        return $result;
        
    }

    /**
     * 积分扣减
     */
    public function memberPointDeduct($params)
    {
        app('log')->debug('数云-会员积分扣减参数: =>'.var_export($params, 1));
        $url = '/lpee-point-interfaces/v1/spi/spmall/point/deduct';
        $resp = $this->client->json($url, $params);
        app('log')->debug('数云-会员积分扣减结果: =>'.var_export($resp, 1));
        if ($resp->code != 0) {
            app('log')->error('数云-会员积分扣减结果Error :'. $resp->message);
            throw new AccessDeniedHttpException($resp->message);
        }
        return $resp->data;
    }

    /**
     * 积分发放
     */
    public function memberPointGain($params)
    {
        app('log')->debug('数云-会员积分发放参数: =>'.var_export($params, 1));
        $url = '/lpee-point-interfaces/v1/spi/spmall/point/gain';
        $resp = $this->client->json($url, $params);
        app('log')->debug('数云-会员积分发放结果: =>'.var_export($resp, 1));
        if ($resp->code != 0) {
            app('log')->error('数云-会员积分发放结果Error :'. $resp->message);
            throw new AccessDeniedHttpException($resp->message);
        }
        return $resp->data;
    }

    /**
     * 会员信息修改
     */
    public function memberUpdate($params)
    {
        app('log')->debug('数云-会员信息修改参数: =>'.var_export($params, 1));
        $url = '/lpee-member-interfaces/v1/spi/spmall/member/modify';
        $resp = $this->client->json($url, $params);
        app('log')->debug('数云-会员信息修改结果: =>'.var_export($resp, 1));
        if ($resp->code != 0) {
            app('log')->error('数云-会员信息修改结果Error :'. $resp->message);
            return false;
            // throw new AccessDeniedHttpException($resp->message);
        }
        return $resp->data;
    }

    /**
     * 会员注销
     */
    public function memberUnbind($params)
    {
        $url = '/lpee-member-interfaces/v1/spi/spmall/member/unbind';
        $resp = $this->client->json($url, $params);
        if ($resp->code != 0) {
            throw new AccessDeniedHttpException($resp->message);
        }
        return $resp->data;
    }

}
