<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Http\Controllers;

// use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Routing\Helpers;
use SystemLinkBundle\Services\OmsQueueLogService;

class Controller extends BaseController
{
    // 接口帮助调用
    use Helpers;
    //

    public $logId;

    public function __construct($logId = 0)
    {
        $this->logId = $logId;
    }

    /**
     * API 返回值
     * @param resCode 返回码
     * @param msg 错误信息描述
     * @param 返回数据
    */
    public function api_response($status, $msg = '', $data = null, $code = 0, $data_format = 'json')
    {
        // FIXME: check performance
        $resposilbe = [
            'true' => 'succ',
            'fail' => 'fail',
            'wait' => 'wait'
        ];
        $result['rsp'] = $resposilbe[$status];
        $result['code'] = $code;
        $result['err_msg'] = $msg;
        $result['data'] = json_encode($data, 256);

        if ($this->logId) {
            $this->updateResponseLog($result, $this->logId);
        }

        switch ($data_format) {
            case 'json':
                $result = json_encode($result, 256);
                break;
            case 'xml':
                break;
            case 'string':
                break;
            default:
                break;
        }

        echo $result;
        exit;
    }

    /**
     * 数云API 返回值
     * @param resCode 返回码
     * @param msg 错误信息描述
     * @param 返回数据
    */
    public function api_response_shuyun($status, $msg = '', $data = null, $code = 0, $data_format = 'json')
    {
        $resposilbe = [
            'true' => '0',
            'fail' => '-1',
            'wait' => '-2'
        ];
        $result['code'] = $resposilbe[$status];
        $result['err_msg'] = $msg;
        // $result['data'] = json_encode($data, 256);

        if ($this->logId) {
            $this->updateResponseLog($result, $this->logId);
        }

        switch ($data_format) {
            case 'json':
                $result = json_encode($result, 256);
                break;
            case 'xml':
                break;
            case 'string':
                break;
            default:
                break;
        }

        echo $result;
        exit;
    }

    private function updateResponseLog($result, $logId)
    {
        if (!$logId) {
            return false;
        }

        $status = 'fail';
        if (isset($result['rsp']) && $result['rsp'] == 'succ') {
            $status = 'success';
        }

        $filter = ['id' => $logId];
        $data = [
            'result' => is_array($result) ? json_encode($result, 256) : $result,
            'status' => $status,
        ];
        $omsQueueLogService = new OmsQueueLogService();
        $logResult = $omsQueueLogService->updateOneBy($filter, $data);
        return $logResult;
    }
}
