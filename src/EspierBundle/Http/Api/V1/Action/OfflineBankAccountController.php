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

namespace EspierBundle\Http\Api\V1\Action;

use EspierBundle\Services\SubdistrictService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\OfflineBankAccountService;

/**
 * 线下转账--收款账户
 */
class OfflineBankAccountController extends Controller
{
    public function __construct()
    {
        $this->service = new OfflineBankAccountService();
    }

    /**
     * @SWG\Get(
     *     path="/espier/offline/backaccount/lists",
     *     summary="获取线下收款账户列表",
     *     tags={"系统"},
     *     description="获取线下收款账户列表",
     *     operationId="getLists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="total_count", type="integer", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="integer", description="ID"),
     *                          @SWG\Property( property="company_id", type="integer", description="企业ID"),
     *                          @SWG\Property( property="bank_account_name", type="string", description="收款账户名称"),
     *                          @SWG\Property( property="bank_account_no", type="string", description="银行账号"),
     *                          @SWG\Property( property="bank_name", type="string", description="开户银行"),
     *                          @SWG\Property( property="china_ums_no", type="string", description="银联号"),
     *                          @SWG\Property( property="pic", type="string", description="图片"),
     *                          @SWG\Property( property="remark", type="string", description="备注"),
     *                          @SWG\Property( property="is_default", type="string", description="是否默认"),
     *                          @SWG\Property( property="created", type="string", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", description="更新时间"),
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getLists(Request $request){
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
        ];
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $result = $this->service->lists($filter, '*', $page, $pageSize, ['created' => 'DESC']);
        $result['list'] = array_map(function($item) {
            $item['is_default'] = $item['is_default'] == 1 ? 'true' : 'false';
            return $item;
        }, $result['list']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/espier/offline/backaccount/create",
     *     summary="创建收款账户",
     *     tags={"系统"},
     *     description="创建收款账户",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="bank_account_name", in="query", description="收款账户名称", required=true, type="string"),
     *     @SWG\Parameter( name="bank_account_no", in="query", description="银行账号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_name", in="query", description="开户银行", required=true, type="string"),
     *     @SWG\Parameter( name="china_ums_no", in="query", description="银联号", required=true, type="string"),
     *     @SWG\Parameter( name="pic", in="query", description="图片", required=false, type="string"),
     *     @SWG\Parameter( name="remark", in="query", description="备注", required=false, type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="是否默认", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object", description="", required={"status"},
     *              @SWG\Property(property="status", type="boolean", default="true", description="更新的状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function create(Request $request){
        $params = $request->all('bank_account_name', 'bank_account_no', 'bank_name', 'china_ums_no', 'pic', 'remark', 'is_default');
        $rules = [
            'bank_account_name' => ['required', '收款账户名称必填'],
            'bank_account_no'     => ['required', '银行账号必填'],
            'bank_name'     => ['required', '开户银行必填'],
            'china_ums_no'     => ['required', '银联号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['is_default'] = $params['is_default'] == 'true' ? 1 : 0;
        $params['pic'] = $params['pic'] ?? '';
        $params['remark'] = $params['remark'] ?? '';
        $this->service->createData($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/espier/offline/backaccount/update",
     *     summary="更新收款账户",
     *     tags={"系统"},
     *     description="更新收款账户",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="", required=false, type="integer"),
     *     @SWG\Parameter( name="bank_account_name", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="bank_account_no", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="bank_name", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="china_ums_no", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="pic", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="remark", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="is_default", in="query", description="", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object", description="", required={"status"},
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="integer", description=""),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function update(Request $request){
        $params = $request->all('id', 'bank_account_name', 'bank_account_no', 'bank_name', 'china_ums_no', 'pic', 'remark', 'is_default');
        $rules = [
            'bank_account_name' => ['required', '收款账户名称必填'],
            'bank_account_no'     => ['required', '银行账号必填'],
            'bank_name'     => ['required', '开户银行必填'],
            'china_ums_no'     => ['required', '银联号必填'],
            'is_default'     => ['required', '是否默认必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['is_default'] = $params['is_default'] == 'true' ? 1 : 0;
        $params['pic'] = $params['pic'] ?? '';
        $params['remark'] = $params['remark'] ?? '';
        $this->service->update([
            'id' => $params['id'],
            'company_id' => $params['company_id'],
        ], $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/espier/offline/backaccount/delete",
     *     summary="删除线下收款账户",
     *     tags={"系统"},
     *     description="删除线下收款账户",
     *     operationId="delete",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object", description="", required={"status"},
     *              @SWG\Property(property="status", type="boolean", default="true", description="更新的状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function delete($id, Request $request){
        $filter = [
            'id' => $id,
            'company_id' => app('auth')->user()->get('company_id'),
        ];
        $this->service->deleteBy($filter);
        return $this->response->array(['status' => true]);
    }
    
    /**
     * @SWG\Get(
     *     path="/espier/offline/backaccount/info",
     *     summary="获取线下收款账户信息",
     *     tags={"系统"},
     *     description="获取线下收款账户信息",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property(property="data", type="object", description="", required={"status"},
     *              @SWG\Property(property="status", type="boolean", default="true", description="更新的状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getInfo($id, Request $request){
        $filter = [
            'id' => $id,
            'company_id' => app('auth')->user()->get('company_id'),
        ];
        $info = $this->service->getInfo($filter);
        $info['is_default'] = $info['is_default'] == 1 ? 'true' : 'false';
        return $this->response->array($info);
    }

}
