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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;

use SalespersonBundle\Services\SalespersonService;

class ShopSalesperson extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ecx.salesperson.push",
     *     summary="新增编辑导购员同步",
     *     tags={"导购"},
     *     description="新增编辑导购员同步（单条记录）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.salesperson.push" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="name", description="导购名称" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="shop_code", description="店铺编码数组" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="employee_status", description="员工类型 [1 员工] [2 编外]" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="salesperson_job", description="职务" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="avatar", description="头像" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="导购员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="work_userid", description="企业微信userid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="is_valid", description="是否启用 true:启用 false:禁用" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="work_qrcode_configid", description="企业微信二维码的configid" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function pushSalesperson(Request $request)
    {
        $params = $request->all();
        $rules = [
            'salesperson_name' => ['required|string', '请填写导购名称'],
            // 'store_bn'    => ['required', '请填写店铺编码'],
            'employee_status' => ['required|in:1,2', '请填写正确的员工类型'],
            'mobile' => ['sometimes|regex:/^1[3456789][0-9]{9}$/', '请填写正确的手机号'],
            'work_userid' => ['required|string', '请填写企业微信userid'],
            'salesperson_status' => ['required|in:0,1,2', '请填写是否启用'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }
        switch ($params['salesperson_status']) {
            case '1':
                $is_valid = 'true';
                break;
            default:
                $is_valid = 'false';
                break;
        }
        $companyId = $request->get('auth')['company_id'];
        $salespersonService = new SalespersonService();
        $param = [
            'company_id' => $companyId,
            'salesperson_type' => 'shopping_guide',
            'name' => $params['salesperson_name'],
            'shop_code' => $params['store_bn'] ?? '',
            'employee_status' => $params['employee_status'],
            'salesperson_job' => $params['salesperson_job'] ?? '',
            'avatar' => $params['salesperson_avatar'],
            'mobile' => $params['mobile'],
            'work_userid' => $params['work_userid'],
            'is_valid' => $is_valid,
            'work_qrcode_configid' => $params['work_qrcode_configid'] ?? '',
        ];
        $data = $salespersonService->__formatSalesperson($param);
        $info = $salespersonService->getInfo(['company_id' => $companyId, 'salesperson_type' => $param['salesperson_type'], 'work_userid' => $param['work_userid']]);
        if ($info) {
            if (isset($params['NewUserID']) && $params['NewUserID'] && $params['work_userid'] != $params['NewUserID']) {
                $data['work_userid'] = $params['NewUserID'];
            }
            $return = $salespersonService->updateSalesperson($companyId, $info['salesperson_id'], $data);
        } else {
            $return = $salespersonService->createSalesperson($data);
        }


        $this->api_response('true', '操作成功', $return, 'E0000');
    }


    /**
     * @SWG\Post(
     *     path="/ecx.salesperson.bathStatusUpdate",
     *     summary="批量更新导购状态",
     *     tags={"导购"},
     *     description="批量更新导购状态",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.salesperson.bathStatusUpdate" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="data", description="json 包含字段 work_userid:企业微信userid，is_valid：是否启用 true:启用 false:禁用；" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function bathUpdateSalespersonStatus(Request $request)
    {
        $params = $request->all('employee_number', 'salesperson_status');
        $companyId = $request->get('auth')['company_id'];
        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $companyId,
            'work_userid' => explode(',', $params['employee_number']),
        ];
        $orderBy = ['created_time' => 'DESC'];
        $salespersonList = $salespersonService->getSalespersonList($filter, $orderBy, -1, 1);
        app('log')->info('bathUpdateSalespersonStatus  filter===>'.var_export($filter, 1));
        app('log')->info('bathUpdateSalespersonStatus  salespersonList===>'.var_export($salespersonList, 1));
        if (!$salespersonList['list']) {
            $this->api_response('fail', '导购员信息查询错误', null, 'E0001');
        }
        $salesperson_ids = array_column($salespersonList['list'], 'salesperson_id');
        switch ($params['salesperson_status']) {
            case '1':
                $is_valid = 'true';
                break;
            default:
                $is_valid = 'false';
                break;
        }
        $filter = [
            'company_id' => $companyId,
            'salesperson_id' => $salesperson_ids,
        ];
        $updateData = ['is_valid' => $is_valid];
        $result = $salespersonService->updateBy($filter, $updateData);
        app('log')->info('bathUpdateSalespersonStatus  filter===>'.var_export($filter, 1));
        app('log')->info('bathUpdateSalespersonStatus  updateData===>'.var_export($updateData, 1));
        app('log')->info('bathUpdateSalespersonStatus  result===>'.var_export($result, 1));
        $this->api_response('true', '操作成功', [], 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.salesperson.destroy",
     *     summary="删除导购",
     *     tags={"导购"},
     *     description="删除导购",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.salesperson.destroy" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="work_userid", description="企业微信userid" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function destroySalesperson(Request $request)
    {
        $params = $request->all();
        $companyId = $request->get('auth')['company_id'];
        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $companyId,
            'work_userid' => $params['employee_number'],
        ];
        $info = $salespersonService->getInfo($filter);
        if (!$info) {
            $this->api_response('fail', '导购员信息查询错误', null, 'E0001');
        }
        $salespersonService->deleteSalesperson($companyId, $info['salesperson_id']);

        $this->api_response('true', '操作成功', [], 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.salesperson.updateStores",
     *     summary="更新导购的绑定店铺",
     *     tags={"导购"},
     *     description="更新导购的绑定店铺",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.salesperson.updateStores" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="work_userid", description="企业微信userid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="work_userid", description="企业微信userid" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="shop_code", description="店铺编号，多个以逗号间隔" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateSalespersonStores(Request $request)
    {
        $params = $request->all();
        $companyId = $request->get('auth')['company_id'];
        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $companyId,
            'work_userid' => $params['employee_number'],
        ];
        $info = $salespersonService->getInfo(['company_id' => $companyId, 'work_userid' => $params['employee_number']]);
        if (!$info) {
            $this->api_response('fail', '导购员信息查询错误', null, 'E0001');
        }
        $salespersonService->updateSalespersonStore($companyId, $info['salesperson_id'], $params['store_bn']);

        $this->api_response('true', '操作成功', [], 'E0000');
    }
}
