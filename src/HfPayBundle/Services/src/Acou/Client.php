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

namespace HfPayBundle\Services\src\Acou;

use HfPayBundle\Services\src\Kernel\Kernel;

class Client
{
    private $_kernel;

    public function __construct(Kernel $kernel)
    {
        $this->_kernel = $kernel;
    }

    /**
     * 个人用户开户接口（后台版）
     */
    public function user01(array $data)
    {
        $url = '/api/acou/user01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * 企业开户申请（后台版）
     */
    public function corp01(array $data)
    {
        $url = '/api/acou/corp01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * 个体户开户申请（后台版）
     */
    public function solo01(array $data)
    {
        $url = '/api/acou/solo01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * mer001 代理商商户开户
     */
    public function mer001(array $data)
    {
        $url = '/api/acou/mer001';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * sett01 商户微信支付宝入驻接口
     */
    public function sett01(array $data)
    {
        $url = '/api/acou/sett01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * sett03 商户微信入驻配置接口
     */
    public function sett03(array $data)
    {
        $url = '/api/acou/sett03';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * bind01 商户微信入驻配置接口
     */
    public function bind01(array $data)
    {
        $url = '/api/acou/bind01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * unbd01 银行卡解绑接口
     */
    public function unbd01(array $data)
    {
        $url = '/api/acou/unbd01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * sms001 短信发送接口
     */
    public function sms001(array $data)
    {
        $url = '/api/acou/sms001';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * pwd001 免密授权接口（后台版
     */
    public function pwd001(array $data)
    {
        $url = '/api/acou/pwd001';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * qry001 余额查询接口
     */
    public function qry001(array $data)
    {
        $url = '/api/alse/qry001';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * cash01 取现（接口版）
     */
    public function cash01(array $data)
    {
        $url = '/api/acou/cash01';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }

    /**
     * file01 文件上传接口
     */
    public function file01(array $data, $file)
    {
        $url = '/api/alseFile/file01';
        $reslut = $this->_kernel->upload($url, $data, $file);

        return $reslut;
    }

    /**
     * qry009 开户状态查询
     */
    public function qry009(array $data)
    {
        $url = '/api/alse/qry009';
        $reslut = $this->_kernel->post($url, $data);

        return $reslut;
    }
}
