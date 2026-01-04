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

namespace WorkWechatBundle\Http\Controllers;

use App\Http\Controllers\Controller as Controller;
use SalespersonBundle\Services\SalespersonService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use WorkWechatBundle\OvertrueWorkWechat\Extend\Callback\WXBizMsgCrypt;
use WorkWechatBundle\Services\WorkWechatRelService;
use WorkWechatBundle\Services\WorkWechatService;

class WorkWechatCallback extends Controller
{
    public function notify($corpid, Request $request)
    {
        // Powered by ShopEx EcShopX
        app('log')->info('--------------------- notify start ---------------------');
        app('log')->info(var_export($request->all(), 1));
        $input = $request->all();
        $workWechatService = new WorkWechatService();
        $config = $workWechatService->getWorkWechatConfig(null, $corpid);
        $wxcpt = new WXBizMsgCrypt($config['agents']['app']['token'], $config['agents']['app']['aes_key'], $config['corpid']);
        if (isset($input['echostr'])) {
            $errCode = $wxcpt->VerifyURL($input['msg_signature'], $input['timestamp'], $input['nonce'], $input['echostr'], $sEchoStr);
            if ($errCode == 0) {
                app('log')->info('--------------------- customer notify success ---------------------');
                app('log')->info(var_export($sEchoStr, 1));
                echo $sEchoStr;
            } else {
                app('log')->info('--------------------- customer notify fail ---------------------');
                app('log')->info($errCode);
            }
        } elseif ($request->getContent()) {
            $errCode = $wxcpt->DecryptMsg($input['msg_signature'], $input['timestamp'], $input['nonce'], $request->getContent(), $sMsg);
            if ($errCode == 0) {
                app('log')->info('--------------------- customer notify success ---------------------');
                app('log')->info("XML info:" . var_export($sMsg, 1));
                app('log')->info("response:" . var_export($this->xmlToArr($sMsg), 1));
            } else {
                app('log')->info('--------------------- customer notify fail ---------------------');
                app('log')->info($errCode);
            }
        }
        app('log')->info('--------------------- notify end ---------------------');
    }

    public function customerNotify($corpid, Request $request)
    {
        try {
            app('log')->info('--------------------- customer notify start ---------------------');
            app('log')->info(var_export($request->all(), 1));
            $input = $request->all();
            $workWechatService = new WorkWechatService();
            $config = $workWechatService->getWorkWechatConfig(null, $corpid);
            $wxcpt = new WXBizMsgCrypt($config['agents']['customer']['token'], $config['agents']['customer']['aes_key'], $config['corpid']);
            if (isset($input['echostr'])) {
                $errCode = $wxcpt->VerifyURL($input['msg_signature'], $input['timestamp'], $input['nonce'], $input['echostr'], $sEchoStr);
                if ($errCode == 0) {
                    app('log')->info('--------------------- customer notify success ---------------------');
                    app('log')->info(var_export($sEchoStr, 1));
                    echo $sEchoStr;
                } else {
                    app('log')->info('--------------------- customer notify fail ---------------------');
                    app('log')->info($errCode);
                }
            } elseif ($request->getContent()) {
                $errCode = $wxcpt->DecryptMsg($input['msg_signature'], $input['timestamp'], $input['nonce'], $request->getContent(), $sMsg);
                if ($errCode == 0) {
                    app('log')->info('--------------------- customer notify success ---------------------');
                    app('log')->info("XML info:" . var_export($sMsg, 1));
                    app('log')->info("response:" . var_export($this->xmlToArr($sMsg), 1));
                    $workWechatRelService = new WorkWechatRelService();
                    $workWechatRelService->relationship($config['company_id'] ?? null, $this->xmlToArr($sMsg));
                } else {
                    app('log')->info('--------------------- customer notify fail ---------------------');
                    app('log')->info($errCode);
                }
            }
            app('log')->info('--------------------- customer notify end ---------------------');
        } catch (\Exception $e) {
            throw new ResourceException('error.');
        }
    }

    public function reportNotify($corpid, Request $request)
    {
        try {
            app('log')->info('--------------------- report notify start ---------------------');
            app('log')->info(var_export($request->all(), 1));
            $input = $request->all();
            $workWechatService = new WorkWechatService();
            $config = $workWechatService->getWorkWechatConfig(null, $corpid);
            $wxcpt = new WXBizMsgCrypt($config['agents']['report']['token'], $config['agents']['report']['aes_key'], $config['corpid']);
            if (isset($input['echostr'])) {
                $errCode = $wxcpt->VerifyURL($input['msg_signature'], $input['timestamp'], $input['nonce'], $input['echostr'], $sEchoStr);
                if ($errCode == 0) {
                    app('log')->info('--------------------- report notify success ---------------------');
                    app('log')->info(var_export($sEchoStr, 1));
                    echo $sEchoStr;
                } else {
                    app('log')->info('--------------------- report notify fail ---------------------');
                    app('log')->info($errCode);
                }
            } elseif ($request->getContent()) {
                $errCode = $wxcpt->DecryptMsg($input['msg_signature'], $input['timestamp'], $input['nonce'], $request->getContent(), $sMsg);
                if ($errCode == 0) {
                    app('log')->info('--------------------- report notify success ---------------------');
                    app('log')->info("XML info:" . var_export($sMsg, 1));
                    app('log')->info("response:" . var_export($this->xmlToArr($sMsg), 1));
                    $salespersonService = new SalespersonService();
                    $salespersonService->workWechatCallback($config['company_id'] ?? null, $this->xmlToArr($sMsg));
                } else {
                    app('log')->info('--------------------- report notify fail ---------------------');
                    app('log')->info($errCode);
                }
            }
            app('log')->info('--------------------- report notify end ---------------------');
        } catch (\Exception $e) {
            throw new ResourceException('error.');
        }
    }

    private function xmlToArr($xml)
    {
        return (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    }
}
