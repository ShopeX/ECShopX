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

namespace OpenapiBundle\Http\Controllers;

// use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\Controller as BaseController;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ApiResponseException;
use OpenapiBundle\Exceptions\ErrorException;

class Controller extends BaseController
{
    /**
     * API 返回值
     * @param resCode 返回码
     * @param msg 错误信息描述
     * @param 返回数据
     */
    public function api_response($status, $msg = '', $data = null, $code = 0, $data_format = 'json')
    {
        // ShopEx EcShopX Service Component
        $exception = new ApiResponseException();
        $resposilbe = [
            'true' => 'success',
            'fail' => 'fail',
            'wait' => 'wait'
        ];
        $result['status'] = $resposilbe[$status];
        $result['code'] = $code;
        $result['message'] = $msg;
        // $result['data'] = json_encode($data);
        $result['data'] = $data;

        switch ($data_format) {
            case 'json':
                //$result = json_encode($result);
                break;
            case 'xml':
                break;
            case 'string':
                break;
            default:
                break;
        }

        $exception->set($result);
        $exception->setDataType($data_format);

        throw $exception;

        echo $result;
        exit;
    }

    /**
     * 获取企业id
     * @return int
     */
    protected function getCompanyId(): int
    {
        // 获取企业id
        $auth = (array)app("request")->attributes->get("auth");
        // 企业id
        return (int)$auth["company_id"];
    }

    /**
     * 获取当前页
     * @return int
     */
    protected function getPage(): int
    {
        return max((int)app("request")->input("page", 1), 1);
    }

    /**
     * 获取每页的大小
     * @return int
     */
    protected function getPageSize(): int
    {
        $pageSize = max((int)app("request")->input("page_size", CommonConstant::DEFAULT_PAGE_SIZE), CommonConstant::DEFAULT_PAGE_SIZE);
        if ($pageSize >= 500) {
            throw new ErrorException(ErrorCode::SERVICE_PARAMS_FORMAT_ERROR, "每页的大小最大为500！");
        }
        return $pageSize;
    }
}
