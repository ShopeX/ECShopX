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

namespace OrdersBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use WorkWechatBundle\Services\WorkWechatMessageService;

class OrderMessage extends Controller
{
    /**
     * @SWG\Get(
     *     path="/order/message/new",
     *     summary="消息类型",
     *     tags={"message"},
     *     description="获取订单消息列表",
     *     operationId="getOrderMessageNew",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data", type="object",
     *                 @SWG\Property(property="num_type_1",type="string",example="5",description="售后类型未读消息数量"),
     *                 @SWG\Property(property="date_type_1",type="string",example="06月04日",description="最后一条消息日期"),
     *                 @SWG\Property(property="num_type_2",type="string",example="5",description="待发货类型未读消息数量"),
     *                 @SWG\Property(property="date_type_2",type="string",example="06月04日",description="最后一条消息日期"),
     *                 @SWG\Property(property="num_type_3",type="string",example="5",description="未妥投类型未读消息数量"),
     *                 @SWG\Property(property="date_type_3",type="string",example="06月04日",description="最后一条消息日期"),
     *                 @SWG\Property(property="is_empty",type="integer",example="0",description="是否全部为空"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getNewInfo(Request $request)
    {
        // IDX: 2367340174
        $operator_id = app('auth')->user()->get('operator_id');
        $company_id = app('auth')->user()->get('company_id');
        $params = $request->all('distributor_id');
        $distributor_id = $params['distributor_id'];
        $rules = [
            'distributor_id' => 'required|integer',
        ];
        $msg = [
            'distributor_id.required' => '店铺编号必填',
            'distributor_id.integer' => '店铺编号类型错误',
        ];
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }
        $workWechatMessageService = new WorkWechatMessageService();
        $orderBy = ['add_time' => 'desc'];
        $filter1 = ['distributor_id' => $distributor_id, 'company_id' => $company_id, 'is_read' => 0, 'operator_id' => $operator_id, 'msg_type' => 1];
        $return['num_type_1'] = $workWechatMessageService->count($filter1);
        unset($filter1['is_read']);
        $list1 = $workWechatMessageService->lists($filter1, $cols = '*', $page = 1, $pageSize = -1, $orderBy);
        $return['date_type_1'] = !empty($list1['list'][0]['add_time']) ? date('m月d日', $list1['list'][0]['add_time']) : '';
        $filter2 = ['distributor_id' => $distributor_id, 'company_id' => $company_id, 'is_read' => 0, 'operator_id' => $operator_id, 'msg_type' => 2];
        $return['num_type_2'] = $workWechatMessageService->count($filter2);
        unset($filter2['is_read']);
        $list2 = $workWechatMessageService->lists($filter2, $cols = '*', $page = 1, $pageSize = -1, $orderBy);
        $return['date_type_2'] = !empty($list2['list'][0]['add_time']) ? date('m月d日', $list2['list'][0]['add_time']) : '';
        $filter3 = ['distributor_id' => $distributor_id, 'company_id' => $company_id, 'is_read' => 0, 'operator_id' => $operator_id, 'msg_type' => 3];
        $return['num_type_3'] = $workWechatMessageService->count($filter3, $cols = '*', $page = 1, $pageSize = -1, $orderBy);
        unset($filter3['is_read']);
        $list3 = $workWechatMessageService->lists($filter3, $cols = '*', $page = 1, $pageSize = -1, $orderBy);
        $return['date_type_3'] = !empty($list3['list'][0]['add_time']) ? date('m月d日', $list3['list'][0]['add_time']) : '';
        $return['is_empty'] = 0;
        if (empty($return['num_type_1']) && empty($return['date_type_1']) && empty($return['num_type_2']) && empty($return['date_type_2']) && empty($return['num_type_3']) && empty($return['date_type_3'])) {
            $return['is_empty'] = 1;
        }
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/order/message/list",
     *     summary="消息列表",
     *     tags={"message"},
     *     description="获取订单消息列表",
     *     operationId="getOrderMessageList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="integer"),
     *     @SWG\Parameter( name="msg_type", in="query", description="消息类型, 1: 售后, 2: 待发货, 3: 未妥投", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Parameter( name="id", in="query", description="上一页最后一条数据id", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data", type="object",
     *                 @SWG\Property(
     *                     property="total_count",
     *                     type="integer",
     *                     example="5",
     *                  ),
     *                  @SWG\Property(
     *                      property="list",
     *                      type="array",
     *                      @SWG\Items(
     *                          type="object",
     *                          @SWG\Property(property="id", type="string", example="1",description="消息id"),
     *                          @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property(property="msg_type", type="string", example="1",description="消息类型"),
     *                          @SWG\Property(property="content", type="string", example="",description="消息内容"),
     *                          @SWG\Property(property="add_time", type="string", example="1547157552",description="时间"),
     *                          @SWG\Property(property="operator_id", type="string", example="2",description="后台账号id"),
     *                          @SWG\Property(property="is_read", type="string", example="0",description="未读"),
     *                      )
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getList(Request $request)
    {
        $operator_id = app('auth')->user()->get('operator_id');
        $company_id = app('auth')->user()->get('company_id');
        $params = $request->all('page', 'pageSize', 'msg_type', 'id', 'distributor_id');
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);
        $msg_type = $request->input('msg_type');
        $id = $request->input('id', 0);
        $distributor_id = $request->input('distributor_id');
        $rules = [
            'msg_type' => 'required|integer',
            'distributor_id' => 'required|integer',
            'page' => 'required|integer',
            'pageSize' => 'required|integer',
        ];
        $msg = [
            'msg_type.required' => '消息类型必填',
            'msg_type.integer' => '消息类型参数类型错误',
            'distributor_id.required' => '店铺编号必填',
            'distributor_id.integer' => '店铺编号类型错误',
            'page.required' => '页码必填',
            'page.integer' => '页码类型错误',
            'pageSize.required' => '每页数量必填',
            'pageSize.integer' => '每页数量类型错误',
        ];
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }
        $workWechatMessageService = new WorkWechatMessageService();
        $filter = ['distributor_id' => $distributor_id, 'company_id' => $company_id, 'operator_id' => $operator_id, 'msg_type' => $msg_type];
        if (!empty($id)) {
            $filter['id|lt'] = $id;
        }
        $list = $workWechatMessageService->lists($filter, $cols = '*', $page, $limit, ['id' => 'desc']);
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/order/message/update",
     *     summary="更新消息",
     *     tags={"message"},
     *     description="更新消息",
     *     operationId="getOrderMessageList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="msg_type", in="query", description="消息类型, 1: 售后, 2: 待发货, 3: 未妥投", required=true, type="integer"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单编号",  type="string"),
     *     @SWG\Parameter( name="after_sales_bn", in="query", description="售后单号",  type="string"),
     *     @SWG\Parameter( name="is_all_read", in="query", description="是否全部已读", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data", type="object",
     *                 @SWG\Property(
     *                     property="result",type="string",example="更新成功",description="时间"
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateMsg(Request $request)
    {
        // IDX: 2367340174
        $params = $request->all('msg_type', 'order_id', 'after_sales_bn', 'is_all_read', 'distributor_id');
        $company_id = app('auth')->user()->get('company_id');
        $operator_id = app('auth')->user()->get('operator_id');
        $rules = [
            'msg_type' => 'required|integer',
            'distributor_id' => 'required|integer',
        ];
        $msg = [
            'msg_type.required' => '消息类型必填',
            'msg_type.integer' => '消息类型参数类型错误',
            'distributor_id.required' => '店铺编号必填',
            'distributor_id.integer' => '店铺编号类型错误',
        ];
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }
        if (empty($params['order_id']) && empty($params['after_sales_bn']) && empty($params['is_all_read'])) {
            throw new ResourceException('缺少参数');
        }
        $where['msg_type'] = $params['msg_type'];
        $where['company_id'] = $company_id;
        $where['operator_id'] = $operator_id;
        $where['distributor_id'] = $params['distributor_id'];
        $where['is_read'] = 0;
        if (!empty($params['order_id'])) {
            $where['content|like'] = '"orderId":"' . $params['order_id'] . '"';
        }
        if (!empty($params['after_sales_bn'])) {
            $where['content|like'] = '"afterSalesBn":"' . $params['after_sales_bn'] . '"';
        }
        $workWechatMessageService = new WorkWechatMessageService();
        $updateResult = $workWechatMessageService->updateBy($where, ['is_read' => 1,'up_time' => time()]);
        // if (empty($updateResult)) {
        //     throw new ResourceException('更新失败');
        // }
        $rs['result'] = '更新成功';
        return $this->response->array($rs);
    }
}
