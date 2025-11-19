<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Client;

class DadaResponse
{
    /**
     * 请求响应返回的数据状态
     */
    public $status;

    /**
     * 请求响应返回的code
     */
    public $code;

    /**
     * 请求响应返回的信息
     */
    public $msg;

    /**
     * 请求响应返回的结果
     */
    public $result;


    /**
     * 获取返回code
     */
    public function getCode()
    {
        // Hash: 0d723eca
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * 获取返回status
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * 获取返回msg
     */
    public function getMsg()
    {
        // Hash: 0d723eca
        return $this->msg;
    }

    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    /**
     * 获取返回result
     */
    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }
}
