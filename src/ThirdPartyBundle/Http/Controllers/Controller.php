<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Http\Controllers;

// use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Routing\Helpers;
use SystemLinkBundle\Services\OmsQueueLogService;

class Controller extends BaseController
{
    // 接口帮助调用
    use Helpers;
    //
    public $data_format;
    public $gzip;
    public $logId;
    public $companyId;

    public function __construct($logId = 0, $companyId = 0)
    {
        $this->logId = $logId;
        $this->companyId = $companyId;
    }

    /**
     * API 返回值
     * @param resCode 返回码
     * @param msg 错误信息描述
     * @param 返回数据
    */
    public function api_response($status, $msg = '', $data = null, $code = 0, $data_format = 'json')
    {
        $this->data_format = $data_format;
        $resposilbe = [
            'true' => 'success',
            'fail' => 'fail',
            'wait' => 'wait'
        ];
        $result['result'] = $resposilbe[$status];
        $result['code'] = $code;
        $result['shopex_time'] = time();
        $result['msg'] = $msg;
        $result['info'] = $data;

        if ($this->logId) {
            $this->updateResponseLog($result, $this->logId);
        }
        $this->return_date($result);
        exit();
    }

    private function updateResponseLog($result, $logId)
    {
        if (!$logId) {
            return false;
        }

        $status = 'fail';
        if (isset($result['result']) && $result['result'] == 'success') {
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

    /**
    * 数据返回
    * @param array 数据源
    * @author DreamDream
    * @return 将数据按照设定的类型返回
    */

    public function return_date($data)
    {
        switch ($this->data_format) {
            case 'string':
                $result = print_r($data, true);
            break;
            case 'json':
                $this->_header('text/html');
                $result = json_encode($data);
            break;
            case 'xml':
            break;
            case 3:

            break;
            case 'soap':

                //soap
            break;
            default:
                 $this->api_response('fail', 'language error', $data);
            break;

        }
        echo $result;
        exit();
    }

    /**
    * 头文件
    * @param string 文件类型
    * @param string 编码格式
    * @author DreamDream
    */
    public function _header($content = 'text/html', $charset = 'utf-8')
    {
        header('Content-type: '.$content.';charset='.$charset);
        if ($this->gzip && function_exists('gzencode')) {
            header('Content-Encoding: gzip');
        }
        header("Cache-Control: no-cache,no-store , must-revalidate");
        $expires = gmdate("D, d M Y H:i:s", time() + 20);
        header("Expires: " .$expires. " GMT");
    }
}
