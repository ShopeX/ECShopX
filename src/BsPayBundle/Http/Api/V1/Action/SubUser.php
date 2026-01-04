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

namespace BsPayBundle\Http\Api\V1\Action;

use BsPayBundle\Services\SubUserService;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

/**
 * 用户
 */
class SubUser extends Controller
{
    /**
     * 斗拱子商户审批列表
     * path = "/bspay/sub_approve/list"
     */
    public function subApproveLists(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all('status', 'user_name', 'address', 'time_start', 'time_end');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $subUserService = new SubUserService();
        $result = $subUserService->getSubApproveLists($companyId, $params, $page, $pageSize);
        return $this->response->array($result);
    }
    
    public function subApproveInfo($id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $subUserService = new SubUserService();
        $result = $subUserService->getSubApproveInfo($companyId, $id);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($datapassBlock) {
            $result['entry_info']['tel_no'] = data_masking('mobile', (string) $result['entry_info']['tel_no']);
            if ($result['entry_info']['member_type'] == 'corp') {
                $result['entry_info']['legal_person'] = data_masking('truename', (string) $result['entry_info']['legal_person']);
                $result['entry_info']['card_no'] = data_masking('bankcard', (string) $result['entry_info']['card_no']);
                $result['entry_info']['legal_cert_id'] = data_masking('idcard', (string) $result['entry_info']['legal_cert_id']);
            } else {
                $result['entry_info']['user_name'] = data_masking('truename', (string) $result['entry_info']['user_name']);
                $result['entry_info']['cert_id'] = data_masking('idcard', (string) $result['entry_info']['cert_id']);
                $result['entry_info']['bank_card_name'] = data_masking('truename', (string) $result['entry_info']['bank_card_name']);
                $result['entry_info']['bank_tel_no'] = data_masking('mobile', (string) $result['entry_info']['bank_tel_no']);
                $result['entry_info']['bank_card_id'] = data_masking('bankcard', (string) $result['entry_info']['bank_card_id']);
                $result['entry_info']['bank_cert_id'] = data_masking('idcard', (string) $result['entry_info']['bank_cert_id']);
            }
            if (isset($result['entry_apply_info']) && $result['entry_apply_info']) {
                $result['entry_apply_info']['user_name'] = data_masking('truename', (string) $result['entry_apply_info']['user_name']);
            }
            if (isset($result['dealer_info']) && $result['dealer_info']) {
                $result['dealer_info']['mobile'] = data_masking('mobile', (string) $result['dealer_info']['mobile']);
            }
            if (isset($result['distributor_info']) && $result['distributor_info']) {
                $result['distributor_info']['mobile'] = data_masking('mobile', (string) $result['distributor_info']['mobile']);
                // $result['distributor_info']['store_address'] = data_masking('detailedaddress', (string) $result['distributor_info']['store_address']);
            }
        }
        return $this->response->array($result);
    }

    /**
     * 斗拱子商户审批保存
     * path = "/bspay/sub_approve/save_audit"
     */
    public function saveAudit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('split_ledger_info','operator_type', 'save_id', 'status', 'comments', 'id', 'save_id');

        $subUserService = new SubUserService();
        $result = $subUserService->saveAudit($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/sub_approve/draw_cash_config",
     *     summary="保存子商户提现限额",
     *     tags={"子商户审批"},
     *     description="保存子商户提现限额",
     *     operationId="draw_cash_config",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="draw_limit", description="暂冻金额" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="draw_limit_list", description="指定商户暂冻金额(json)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_draw_cash", description="是否自动提现(0,1)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_type", description="自动提现类型(day,month)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_day", description="自动提现日期(1-31)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="auto_time", description="自动提现时间(09:30)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="min_cash", description="最小提现金额" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="cash_type", description="取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现。" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function setDrawCashConfig(Request $request)
    {
        $subMerchantService = new SubMerchantService();

        $companyId = app('auth')->user()->get('company_id');

        //暂时冻结金额的设置
        $limit = $request->input('draw_limit');

        //暂时冻结金额的设置。指定商户。
        $draw_limit_list = $request->input('draw_limit_list', '');

        //自动提现的设置
        $auto_draw_cash = $request->input('auto_draw_cash', 'N');
        $auto_type = $request->input('auto_type', '');//每月或者每日
        $auto_day = $request->input('auto_day', '');
        $auto_time = $request->input('auto_time', '');
        $min_cash = $request->input('min_cash', '');
        $cash_type = $request->input('cash_type', '');

        $next_time = -1;
        if ($auto_draw_cash == 'Y') {
            if (!$auto_time) {
                throw new ResourceException('自动提现时间错误');
            }

            if ($auto_type == 'day') {//每天提现一次
                $next_time = date('Y-m-d') . " {$auto_time}";
                if (strtotime($next_time) <= time()) {//必须大于当前时间
                    $next_time = date('Y-m-d', strtotime('+1 days')) . " {$auto_time}";
                }
            } elseif ($auto_type == 'month') {//每月提现一次
                if (!$auto_day) {
                    throw new ResourceException('自动提现日期错误');
                }
                $next_time = date('Y-m') . "-{$auto_day} {$auto_time}";
                if (strtotime($next_time) <= time()) {//必须大于当前时间
                    $next_time = date('Y-m', strtotime('+1 month')) . "-{$auto_day} {$auto_time}";
                }
            } else {
                throw new ResourceException('自动提现类型错误');
            }

            $next_time = strtotime($next_time);
        }

        //冻结金额设置
        $result = $subMerchantService->setDrawLimit($companyId, $limit);

        //指定商户冻结设置
        if ($draw_limit_list) {
            if (!is_string($draw_limit_list)) {
                throw new ResourceException('指定商户暂冻金额参数错误');
            }
            $draw_limit_list = json_decode($draw_limit_list, true);
        } else {
            $draw_limit_list = [];//清空指定设置
        }
        $result = $subMerchantService->setDrawLimitList($companyId, $draw_limit_list);

        //自动提现设置
        $autoCashConfig = [
            'auto_draw_cash' => $auto_draw_cash,
            'auto_type' => $auto_type,
            'auto_day' => $auto_day,
            'auto_time' => $auto_time,
            'min_cash' => $min_cash,
            'cash_type' => $cash_type,
            'next_time' => $next_time,//下一次自动提现的时间节点
        ];
        $result = $subMerchantService->setAutoCashConfig($companyId, $autoCashConfig);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/sub_approve/draw_limit",
     *     summary="保存子商户提现限额(废弃)",
     *     tags={"子商户审批"},
     *     description="保存子商户提现限额(废弃)",
     *     operationId="setDrawLimit",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="draw_limit", description="限制金额" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function setDrawLimit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $limit = $request->input('draw_limit');

        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->setDrawLimit($companyId, $limit);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/sub_approve/draw_cash_config",
     *     summary="获取子商户提现限额",
     *     tags={"子商户审批"},
     *     description="获取子商户提现限额",
     *     operationId="get_draw_cash_config",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="draw_limit", type="string", example="1000", description="限制金额"),
     *                  @SWG\Property( property="cash_type_options", type="array", description="取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现",
     *                       @SWG\Items(
     *                          @SWG\Property(property="label", type="string", description="标签"),
     *                          @SWG\Property(property="value", type="string", description="标签值"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="auto_config", type="object", description="自动提现设置",
     *                      @SWG\Property( property="auto_draw_cash", type="string", description="是否自动提现(0,1)"),
     *                      @SWG\Property( property="auto_type", type="string", description="自动提现类型(day,month)"),
     *                      @SWG\Property( property="auto_draw_day", type="string", description="自动提现日期(1-31)"),
     *                      @SWG\Property( property="auto_draw_time", type="string", description="自动提现时间(09:30)"),
     *                      @SWG\Property( property="min_cash", type="string", description="最小提现金额"),
     *                      @SWG\Property( property="cash_type", type="string", description="取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现"),
     *                  ),
     *                  @SWG\Property( property="draw_limit_list", type="object", description="冻结金额设置",
     *                      @SWG\Property( property="member_id", type="string", description="商户ID"),
     *                      @SWG\Property( property="merchant_name", type="string", description="商户名称"),
     *                      @SWG\Property( property="location", type="string", description="地址"),
     *                      @SWG\Property( property="contact_name", type="string", description="联系人"),
     *                      @SWG\Property( property="draw_limit", type="string", description="暂冻金额(元)"),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function getDrawCashConfig()
    {
        $companyId = app('auth')->user()->get('company_id');
        $subMerchantService = new SubMerchantService();

        $cashTypeOptions = [
            ['label' => 'T+1取现', 'value' => 'T1'],
            ['label' => 'D+1取现', 'value' => 'D1'],
            ['label' => '即时取现', 'value' => 'D0'],
        ];

        $result = $subMerchantService->getDrawLimit($companyId);
        $result['draw_limit'] = $result['draw_limit'] ?? 0;
        if ($result['draw_limit']) {
            $result['draw_limit'] = bcdiv($result['draw_limit'], 100, 2);
        }
        $result['auto_config'] = $subMerchantService->getAutoCashConfig($companyId);
        $result['draw_limit_list'] = $subMerchantService->getDrawLimitList($companyId);
        $result['cash_type_options'] = $cashTypeOptions;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/sub_approve/draw_limit",
     *     summary="获取子商户提现限额(废弃)",
     *     tags={"子商户审批"},
     *     description="获取子商户提现限额(废弃)",
     *     operationId="getDrawLimit",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="draw_limit", type="string", example="1000", description="限制金额"),
     *          ),
     *     )),
     * )
     */
    public function getDrawLimit()
    {
        $companyId = app('auth')->user()->get('company_id');
        $subMerchantService = new SubMerchantService();
        $result = $subMerchantService->getDrawLimit($companyId);

        return $this->response->array($result);
    }
}
