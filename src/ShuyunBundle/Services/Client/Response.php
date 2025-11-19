<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ShuyunBundle\Services\Client;

class Response
{

    /**
     * 请求响应返回的code
     */
    public $code;

    /**
     * 请求响应返回的信息
     */
    public $message;

    /**
     * 请求响应返回的结果
     */
    public $data;

    /**
     * 获取返回message
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * 获取返回message
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * 获取返回data
     */
    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
