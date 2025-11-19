<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Sdk\Core;

use Exception;
use ReflectionClass;

class BsPayClient extends BsPay
{
    /**
     * @var string $merChantKey
     */
    protected $merChantKey = "default";

    public function __construct($merChantKey = 'default')
    {
        $this->merChantKey = $merChantKey;
    }

    /**
     * 请求接口
     *
     * @param object | array $request 请求实例对象 | 请求参数数组
     * @param mixed $tag 请求类型标识
     *                     boolean  $tag  true (页面接口请求) | false (非页面接口请求)
     *                     CURLFile $tag 待上传的文件
     */
    public function postRequest($request, $tag = false) {
        try {
            // 请求接口获取应答
            if (is_object($request)) {
                $data = $this->objectToArray($request);
            } else {
                $data = $request;
            }

            // 处理文件上传接口
            if (is_object($tag)) {
                $data['params']['needSign'] = false;    // 请求不加签名
                $data['params']['needVerfySign'] = false;   // 返回数据不验证签名
                return self::post($data['funcCode'], $data['params'], $tag, $this->merChantKey);
            }

            // 处理页面接口请求
            if ($tag) {
                $data['params']['needSign'] = true;     // 请求加签名
                $data['params']['needVerfySign'] = false;   // 返回数据不验证签名
            }

            return self::post($data['funcCode'], $data['params'], "", $this->merChantKey);
        } catch (Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    /**
     *  对象转换数组
     * @param object $object  待转化参数对象
     */
    public function objectToArray($object) {
        $class = new ReflectionClass($object);
        $properties = $class->getProperties();
        $arrayData = array();
        $extendInfos = array();

        // 转换接口参数
        foreach ($properties as $key => $value) {
            $attrName = $value->getName();
            $method = 'get'.ucfirst($attrName);
            if ( $class->hasMethod($method) ) {
                $attrValue = $class->getMethod($method)->invoke($object);
                if (isset($attrValue)) {
                    if ($attrName != "extendInfos") {
                        $newKey = strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $attrName));
                        $arrayData[$newKey] = $attrValue;
                    } else {
                        $extendInfos = $attrValue;
                    }
                }
            }
        }

        // 获取接口方法
        $funcCode = $class->getMethod('getFunctionCode')->invoke($object);

        $data = array(
            'params' => array_merge($arrayData, $extendInfos),
            'funcCode' => $funcCode ? $funcCode : '',
        );

        return $data;
    }

}