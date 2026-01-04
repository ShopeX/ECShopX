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

use EspierBundle\Services\PrinterService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Validation\Rule;

class PrinterController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/printer/list",
     *     summary="获取易联云配置",
     *     tags={"系统"},
     *     description="获取易联云配置",
     *     operationId="getPrinterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="type", in="path", description="打印机类型 yilianyun", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2", description=""),
     *                          @SWG\Property( property="name", type="string", example="test", description="名称"),
     *                          @SWG\Property( property="type", type="string", example="yilianyun", description="打印机类型 yilianyun 易连云"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="86", description="店铺ID"),
     *                          @SWG\Property( property="app_terminal", type="string", example="xxxx", description="打印机终端号"),
     *                          @SWG\Property( property="app_key", type="string", example="xxxx", description="打印机秘钥"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getPrinterList(Request $request)
    {
        $params['company_id'] = app('auth')->user()->get('company_id');

        $page = $request->input('page');
        $pageSize = $request->input('pagesize');
        $printerService = new PrinterService();
        $result = $printerService->lists($params, '*', $page, $pageSize, ['id' => 'desc']);

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/espier/printer/shop",
     *     summary="添加商家易联云打印机",
     *     tags={"系统"},
     *     description="添加商家易联云打印机",
     *     operationId="createPrinter",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="name", in="query", description="名称", required=true, type="string", ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店id", required=true, type="string", ),
     *     @SWG\Parameter( name="app_terminal", in="query", description="打印机终端号", required=true, type="string", ),
     *     @SWG\Parameter( name="app_key", in="query", description="打印机秘钥", required=true, type="string", ),
     *     @SWG\Parameter( name="type", in="query", description="打印机类型 yilianyun", required=true, type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="2", description=""),
     *                  @SWG\Property( property="name", type="string", example="test", description="名称"),
     *                  @SWG\Property( property="type", type="string", example="yilianyun", description="打印机类型 yilianyun 易连云"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="86", description="门店id"),
     *                  @SWG\Property( property="app_terminal", type="string", example="xxxx", description="打印机终端号"),
     *                  @SWG\Property( property="app_key", type="string", example="xxxx", description="打印机秘钥"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function createPrinter(Request $request)
    {
        $params = $request->all('name', 'distributor_id', 'app_terminal', 'app_key', 'type');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $printerService = new PrinterService();
        $rules = [
            'name' => ['required', '打印机名称'],
            'company_id' => ['required', '公司id必填'],
            'distributor_id' => ['required', '请选择所属店铺'],
            'app_terminal' => ['required', '请填写终端号'],
            'app_key' => ['required', '请填写应用密钥'],
            'type' => [['required', Rule::in($printerService->getType()),], '打印机配置类型错误'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $printerService->checkPrinter($params['company_id'], $params['app_terminal']);
        $printerService->checkDistributor($params['company_id'], $params['distributor_id']);
        $result = $printerService->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/espier/printer/shop/{id}",
     *     summary="更新商家易联云打印机",
     *     tags={"系统"},
     *     description="更新商家易联云打印机",
     *     operationId="createPrinter",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="name", in="query", description="名称", required=true, type="string", ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店id", required=true, type="string", ),
     *     @SWG\Parameter( name="app_terminal", in="query", description="打印机终端号", required=true, type="string", ),
     *     @SWG\Parameter( name="app_key", in="query", description="打印机秘钥", required=true, type="string", ),
     *     @SWG\Parameter( name="type", in="query", description="打印机类型 yilianyun", required=true, type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="2", description=""),
     *                  @SWG\Property( property="name", type="string", example="test", description="名称"),
     *                  @SWG\Property( property="type", type="string", example="yilianyun", description="打印机类型 yilianyun 易连云"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="distributor_id", type="string", example="86", description="门店id"),
     *                  @SWG\Property( property="app_terminal", type="string", example="xxxx", description="打印机终端号"),
     *                  @SWG\Property( property="app_key", type="string", example="xxxx", description="打印机秘钥"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function updatePrinter($id, Request $request)
    {
        $params = $request->all('name', 'distributor_id', 'app_terminal', 'app_key', 'type');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $printerService = new PrinterService();
        $rules = [
            'name' => ['required', '打印机名称'],
            'company_id' => ['required', '公司id必填'],
            'distributor_id' => ['required', '请选择所属店铺'],
            'app_terminal' => ['required', '请填写终端号'],
            'app_key' => ['required', '请填写应用密钥'],
            'type' => [['required', Rule::in($printerService->getType()),], '打印机配置类型错误'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $printerService->checkPrinter($params['company_id'], $params['app_terminal'], $id);
        $printerService->checkDistributor($params['company_id'], $params['distributor_id'], $id);
        $result = $printerService->updateOneBy(['id' => $id, 'company_id' => $params['company_id']], $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/espier/printer/shop/{id}",
     *     summary="更新商家易联云打印机",
     *     tags={"系统"},
     *     description="更新商家易联云打印机",
     *     operationId="createPrinter",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function deletePrinter($id)
    {
        $companyId = app('auth')->user()->get('company_id');

        $printerService = new PrinterService();
        $result = $printerService->deleteBy(['id' => $id, 'company_id' => $companyId]);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/printer",
     *     summary="获取易联云配置",
     *     tags={"系统"},
     *     description="获取易联云配置",
     *     operationId="info",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="path",
     *         description="打印机类型 yilianyun",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function info(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $printerService = new PrinterService();
        $type = $request->input('type');
        $result = $printerService->getPrinterInfo($companyId, $type);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/printer",
     *     summary="保存易联云配置",
     *     tags={"系统"},
     *     description="保存易联云配置",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启 true false", required=true, type="string" ),
     *     @SWG\Parameter( name="person_id", in="query", description="用户ID", required=true, type="string" ),
     *     @SWG\Parameter( name="app_id", in="query", description="应用ID", required=true, type="string" ),
     *     @SWG\Parameter( name="app_key", in="query", description="应用密钥", required=true, type="string" ),
     *     @SWG\Parameter( name="type", in="query", description="客服类型 yilianyun", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_open", type="string", example="true", description=""),
     *                  @SWG\Property( property="person_id", type="string", example="aaaadffdsaf", description=""),
     *                  @SWG\Property( property="app_id", type="string", example="sadfsadf", description="app_id"),
     *                  @SWG\Property( property="app_key", type="string", example="sadfasdf", description="app_key"),
     *                  @SWG\Property( property="is_hide", type="string", example="true", description=""),
     *                  @SWG\Property( property="type", type="string", example="yilianyun", description="打印机类型 yilianyun 易连云"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function update(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $data = $request->all('is_open', 'person_id', 'app_id', 'app_key', 'is_hide', 'type');
        $data['is_open'] = 'true' == $data['is_open'] ? 'true' : 'false';
        $data['is_hide'] = 'true' == $data['is_hide'] ? 'true' : 'false';
        $printerService = new PrinterService();
        $result = $printerService->savePrinterInfo($companyId, $data);

        return $this->response->array($result);
    }
}
