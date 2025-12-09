<?php

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use ThirdPartyBundle\Http\Controllers\Controller as Controller;
use ThirdPartyBundle\Services\DmCrm\SubscribeService;

class Dm extends Controller
{
   /**
    * 达摩 消息订阅事件回调地址
    * 所有事件回调都是通过application/json格式，请注意接口接收数据的方式；

    * @param \Illuminate\Http\Request $request
    * @return mixed|\Illuminate\Http\JsonResponse
    */
   public function messageNotify($companyId, Request $request)
   {
        // 获取请求参数
        $params = $request->all();
        app('log')->debug("达摩CRM::Dm_messageNotify:请求参数 ".json_encode($params));
        $subscribeService = new SubscribeService($companyId);
        try {
            $result = $subscribeService ->dispatchEvent($companyId, $params);
            // 记录日志
            $subscribeService->apiLogs('/third/dm/messageNotify', $params, $result, 'response');
        }catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
            app('log')->debug("达摩CRM::Dm_messageNotify:异常:".json_encode($error));
            $subscribeService->apiLogs('/third/dm/messageNotify', $params, $error, 'response', 'fail');
        }
        
        // 返回响应
        return response()->json([
            "code" => "0",
            "message" => "success",
            "_ignore_data" => true
        ]);
    }
    
}
