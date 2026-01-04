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

namespace AftersalesBundle\Http\FrontApi\V1\Action;

use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as Controller;

use Dingo\Api\Exception\ResourceException;
use AftersalesBundle\Services\AftersalesService;
use OrdersBundle\Services\Orders\NormalOrderService;
use MembersBundle\Services\MemberService;
use CompanysBundle\Ego\CompanysActivationEgo;
use OrdersBundle\Traits\OrderSettingTrait;

class Aftersales extends Controller
{
    use OrderSettingTrait;

    /**
     * @SWG\Definition(
     *     definition="Aftersales",
     *         @SWG\Property(property="aftersales_status", type="string", example="true",description="售后状态"),
     *         @SWG\Property(property="aftersales_bn", type="string", example="202101195255102",description="售后单号"),
     *         @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND",description="售后类型"),
    *          @SWG\Property(property="company_id", type="integer", example="1",description="公司id"),
    *          @SWG\Property(property="create_time", type="string", example="1611045460",description="创建时间"),
    *          @SWG\Property(property="description", type="string", example="desc",description="申请描述"),
    *          @SWG\Property(property="distributor_id", type="integer", example="101",description="分销商id"),
    *          @SWG\Property(property="evidence_pic", type="string", example="desc",description="图片凭证信息"),
    *          @SWG\Property(property="memo", type="string", example="",description="售后备注"),
    *          @SWG\Property(property="order_id", type="string", example="3306658000280347",description="订单号"),
    *          @SWG\Property(property="progress", type="integer", example="3",description="处理进度"),
    *          @SWG\Property(property="reason", type="string", example="desc",description="申请售后原因"),
    *          @SWG\Property(property="refund_fee", type="string", example="10000",description="应退总金额"),
    *          @SWG\Property(property="refund_point", type="integer", example="0",description="应退总积分"),
    *          @SWG\Property(property="refuse_reason", type="string", example="demo",description="拒绝原因"),
    *          @SWG\Property(property="sendconfirm_data", type="string", example="[]",description="商家重新发货物流信息"),
    *          @SWG\Property(property="shop_id", type="integer", example="0",description="门店id"),
    *          @SWG\Property(property="third_data", type="string", example="",description="百胜等第三方返回的数据"),
    *          @SWG\Property(property="update_time", type="string", example="1611045593",description="更新时间"),
    *          @SWG\Property(property="sendback_data", type="object",
    *              @SWG\Property(property="corp_code", type="string", example="SF", description="物流公司编码"),
    *              @SWG\Property(property="logi_no", type="string", example="6324324", description="物流单号"),
    *              @SWG\Property(property="receiver_address", type="string", example="", description="收货地址"),
    *              @SWG\Property(property="receiver_mobile", type="string", example="", description="收货电话"),
    *          ),
    *          @SWG\Property(property="detail", type="array",
    *               @SWG\Items(
    *                  type="object",
    *                  ref="#/definitions/AftersalesDetail"
    *                )
    *         ),
    * )
    */

    /**
     * @SWG\Definition(
     *     definition="AftersalesDetail",
     *     type="object",
     *     @SWG\Property(property="detail_id", type="integer", example="1077",description="售后明细ID"),
     *     @SWG\Property(property="company_id", type="integer", example="1",description="公司id"),
     *     @SWG\Property(property="user_id", type="integer", example="111",description="用户id"),
     *     @SWG\Property(property="aftersales_bn", type="integer", example="202101195255102",description="售后单号"),
     *     @SWG\Property(property="aftersales_status", type="integer", example="1",description="售后状态"),
     *     @SWG\Property(property="aftersales_type", type="string", example="ONLY_REFUND",description="售后类型"),
     *     @SWG\Property(property="auto_refuse_time", type="integer", example="0",description="售后自动驳回时间"),
     *     @SWG\Property(property="create_time", type="string", example="1611045460",description="创建时间"),
     *     @SWG\Property(property="item_bn", type="string", example="S5FA8AF7D3C37F",description="商品编号"),
     *     @SWG\Property(property="item_id", type="integer", example="5091",description="商品id"),
     *     @SWG\Property(property="item_name", type="string", example="本地测试商品",description="商品名"),
     *     @SWG\Property(property="item_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfFP",description="图片"),
     *     @SWG\Property(property="num", type="integer", example="1",description="数量"),
     *     @SWG\Property(property="order_id", type="string", example="3306658000280347",description="订单号"),
     *     @SWG\Property(property="order_item_type", type="string", example="normal",description="订单商品类型"),
     *     @SWG\Property(property="progress", type="integer", example="3",description="处理进度"),
     *     @SWG\Property(property="refund_fee", type="string", example="10000",description="应退总金额"),
     *     @SWG\Property(property="refund_point", type="string", example="100",description="积分支付应退款积分"),
     *     @SWG\Property(property="sub_order_id", type="string", example="8626",description="订单明细表id"),
     *     @SWG\Property(property="update_time", type="string", example="1611045593",description="更新时间"),
     * )
     */




    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales",
     *     summary="创建售后单",
     *     tags={"售后"},
     *     description="创建售后单",
     *     operationId="apply",
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="交易商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="reason", in="query", description="申请售后原因", required=true, type="string"),
     *     @SWG\Parameter( name="detail", in="query", description="售后商品明细", required=true, type="string",required=true),
     *     @SWG\Parameter( name="aftersales_type", in="query", required=true, description="售后类型， ONLY_REFUND 仅退款 REFUND_GOODS 退货退款 EXCHANGING_GOODS 换货", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="申请描述", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#definitions/Aftersales"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function apply(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all('order_id','self_delivery_operator_id', 'user_id', 'detail', 'aftersales_type', 'reason', 'evidence_pic', 'description', 'return_type', 'aftersales_address_id', 'contact', 'mobile', 'refund_fee', 'refund_point','freight');
        $params['company_id'] = $authInfo['company_id'];
        if(isset($params['self_delivery_operator_id']) && $params['self_delivery_operator_id']){
            $params['self_delivery_operator_id'] = $params['user_id'];
        }else{
            $params['user_id'] = $authInfo['user_id'];
        }
        $validator = app('validator')->make($params, [
            'order_id' => 'required',
            'detail' => 'required',
            // 'item_id' => 'required|integer',
            'company_id' => 'required',
            'user_id' => 'required',
            'aftersales_type' => 'required',
            'reason' => 'required',
            // 'return_type' => 'required_if:aftersales_type,REFUND_GOODS',
            'aftersales_address_id' => 'required_if:return_type,offline',
            'contact' => 'required_if:return_type,offline',
            'mobile' => 'required_if:return_type,offline',
        ], [
            'order_id.*' => '订单号必填,必须为整数',
            'detail.*' => '售后商品明细必填',
            // 'item_id.*' => '商品ID必填,商品ID必须为整数',
            'company_id.*' => '企业id必填',
            'user_id.*' => '会员id必填',
            'aftersales_type.*' => '售后类型必选',
            'reason.*' => '售后原因必选',
            // 'return_type.*' => '退货方式必填',
            'aftersales_address_id.*' => '请选择退货门店',
            'contact.*' => '请填写联系人姓名',
            'mobile.*' => '请填写联系人手机号码',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }
        if (isset($params['detail']) && !is_array($params['detail'])) {
            $params['detail'] = json_decode($params['detail'], 1);
        }

        // if (count($params['detail']) > 1) {
        //     throw new ResourceException('每次只能申请一个商品');
        // }

        // @todo 返回积分获取
        // $normalOrderService = new NormalOrderService();
        // $orderItem = $normalOrderService->getOrderItemInfo($params['company_id'], $params['order_id'], $params['item_id']);
        // $params['number'] = $orderItem['num'];
        // $params['share_points'] = $orderItem['share_points'];

        if (isset($params['return_type']) && $params['return_type'] == 'offline') {
            $company = (new CompanysActivationEgo())->check($authInfo['company_id']);
            if ($company['product_model'] != 'platform') {
                $ifOfflineOftersales = $this->getOrdersSetting($authInfo['company_id'], 'offline_aftersales');
                if (!$ifOfflineOftersales) {
                    throw new ResourceException('未开启到店退货');
                }
            }
        }

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->createByNum($params);

        return $this->response->array(['status' => true, 'result' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/aftersales/info",
     *     summary="获取售后单详情",
     *     tags={"售后"},
     *     description="获取售后单详情",
     *     operationId="getAftersalesDetail",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
    *             @SWG\Property(
    *                 property="data",
    *                 type="object",
    *                 ref="#/definitions/Aftersales"
    *             ),
    *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */


    public function getAftersalesDetail(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $params['user_id'] ?? $authInfo['user_id'];

        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            // 'order_id' => 'required_without:aftersales_bn',
            // 'item_id' => 'required_without:aftersales_bn|integer|min:1',
            'company_id' => 'required',
            'user_id' => 'required',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            // 'order_id.*' => '订单号必填,必须为整数',
            // 'item_id.*' => '商品ID必填,商品ID必须为正整数',
            'company_id.*' => '企业id必填',
            'user_id.*' => '会员id必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getAftersales($params, true);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/aftersales",
     *     summary="获取售后单列表",
     *     tags={"售后"},
     *     description="getAftersalesList",
     *     operationId="getAftersalesList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_type",
     *         in="query",
     *         description="售后类型",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_status",
     *         in="query",
     *         description="售后状态",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", example="1", description="数量"),
     *                 @SWG\Property(property="list", type="array",
     *                     @SWG\Items(
     *                         ref="#definitions/Aftersales"
     *                     ),
     *                 )
     *             ),
     *          ),
     *     ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getAftersalesList(Request $request)
    {
        $authInfo = $request->get('auth');
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 10);
        $params = $request->all();
        $filter['company_id'] = (int)$authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        if (isset($params['aftersales_status']) && is_numeric($params['aftersales_status'])) {
            $filter['aftersales_status'] = $params['aftersales_status'];
        }
        if (isset($params['aftersales_type']) && $params['aftersales_type']) {
            $filter['aftersales_type'] = $params['aftersales_type'];
        }
        if (isset($params['order_id']) && $params['order_id']) {
            $filter['order_id'] = $params['order_id'];
        }
        if (isset($params['order_class']) && $params['order_class']) {
            $filter['order_class'] = $params['order_class'];
        }

        $validator = app('validator')->make($filter, [
            'company_id' => 'required',
            'user_id' => 'required',
        ], [
            'company_id.*' => '企业ID必填',
            'user_id.*' => '用户ID必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }
        if (isset($params['self_delivery_operator_id']) && $params['self_delivery_operator_id']) {
            $filter['self_delivery_operator_id'] = $params['self_delivery_operator_id'];
            unset($filter['user_id']);
        }
        $offset = ($page - 1) * $limit;

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getAftersalesList($filter, $offset, $limit);

        // 追加店铺信息
        (new DistributorService())->appendDistributorInfo($filter['company_id'], $result["list"]);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales/sendback",
     *     summary="售后消费者回寄",
     *     tags={"售后"},
     *     description="售后消费者回寄",
     *     operationId="sendback",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *     @SWG\Parameter( name="corp_code", in="query", description="快递公司", required=true, type="string"),
     *     @SWG\Parameter( name="logi_no", in="query", description="快递单号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="object",ref="#definitions/Aftersales"),
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function sendback(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $params['user_id'] ?? $authInfo['user_id'];

        $aftersalesService = new AftersalesService();
        // 兼容批量操作
        if (isset($params['aftersales_data']) && is_array($params['aftersales_data'])) {
            $aftersalesData = $params['aftersales_data'];
            unset($params['aftersales_data']);
            foreach ($aftersalesData as $kf=> $vf) {
                $params['aftersales_bn'] = $vf;
                $result[] = $aftersalesService->sendBack($params);
            }
        }else {
            $validator = app('validator')->make($params, [
                'aftersales_bn' => 'required',
                'company_id' => 'required',
                'user_id' => 'required',
                'corp_code' => 'required',
                'logi_no' => 'required|min:6|max:30',
                // 'receiver_address' => 'required',
                // 'receiver_mobile' => 'required|numeric|digits:11',
            ], [
                'aftersales_bn.*' => '售后单号必填',
                'company_id.*' => '企业ID必填',
                'user_id.*' => '用户ID必填',
                'corp_code.*' => '物流公司不能为空',
                'logi_no.*' => '物流单号不能为空,运单号不能小于6,运单号不能大于20',
                // 'receiver_address.*' => '收货地址不能为空',
                // 'receiver_mobile.*' => '收货手机号不能为空!,收货手机号格式不对!',
            ]);
            if ($validator->fails()) {
                $errorsMsg = $validator->errors()->toArray();
                $errmsg = '';
                foreach ($errorsMsg as $v) {
                    $msg = implode("，", $v);
                    $errmsg .= $msg . "，";
                }
                throw new ResourceException(trim($errmsg, '，'));
            }

            $params['user_id'] = $authInfo['user_id'];
            $params['company_id'] = $authInfo['company_id'];
            $result = $aftersalesService->sendBack($params);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/aftersales/close",
     *     summary="售后关闭",
     *     tags={"售后"},
     *     description="售后关闭",
     *     operationId="closeConfirm",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Aftersales"
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function closeConfirm(Request $request)
    {
        $authInfo = $request->get('auth');
        $postData = $request->all();
        $params['aftersales_bn'] = $request->input('aftersales_bn');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $postData['user_id'] ?? $authInfo['user_id'];
        $validator = app('validator')->make($params, [
            'aftersales_bn' => 'required',
            'company_id' => 'required',
            'user_id' => 'required',
        ], [
            'aftersales_bn.*' => '售后单号必填',
            'company_id.*' => '企业ID必填',
            'user_id.*' => '用户ID必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException(trim($errmsg, '，'));
        }

        $aftersalesService = new AftersalesService();
//        $params['user_id'] = $authInfo['user_id'];
//        $params['company_id'] = $authInfo['company_id'];
        $params['memo'] = '消费者主动关闭售后';
        $result = $aftersalesService->closeAftersales($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/refunds",
     *     summary="获取退款单",
     *     tags={"售后"},
     *     description="获取退款单",
     *     operationId="getRefundsList",
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="获取退款单", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getRefundsList(Request $request)
    {
        $input = $request->input();
        $rules = [
            'page' => ['required|integer|min:1','数据错误'],
            'pageSize' => ['required|integer|min:1|max:50','数据错误'],
        ];

        $error = validator_params($input, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);
        $offset = ($page - 1) * $limit;
        if ($input['aftersales_status']) {
            switch ($input['aftersales_status']) {
                case 1:
                    $filter['refund_status'] = 'READY';
                    break;
                case 2:
                    $filter['refund_status'] = 'SUCCESS';
                    break;
                case 3:
                    $filter['refund_status'] = 'REFUSE';
                    break;
                case 4:
                    $filter['refund_status'] = 'CANCEL';
                    break;
            }
        }
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getRefundsList($filter, $offset, $limit);
        if (isset($input['is_check']) && $input['is_check']) {
            //记录退款单变化
            $memberService = new MemberService();
            $memberService->removeUserRefundsChange($authInfo['user_id']);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/aftersales/item/price",
     *     summary="获取售后商品价格",
     *     tags={"售后"},
     *     description="获取售后商品价格",
     *     operationId="getRefundAmount",
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="number", in="query", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="aftersales_bn", in="query", description="售后单号", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="object",
     *                 @SWG\Property(property="price", type="string", example="1", description="售后商品价格")
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getRefundAmount(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $validator = app('validator')->make($params, [
            'order_id' => 'required',
            'item_id' => 'required',
            'number' => 'required',
            'company_id' => 'required',
            'user_id' => 'required',
        ], [
            'order_id.*' => '订单号必传',
            'item_id.*' => '商品ID必传',
            'number.*' => '商品数量必传',
            'company_id.*' => '企业ID必填',
            'user_id.*' => '用户ID必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }
        $up = isset($params['aftersales_bn']) && !empty($params['aftersales_bn']) ? 1 : 0;

        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getRefundAmount($params, $params['number'], $up);

        return $this->response->array(['price' => $result]);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/aftersales/remind/detail",
     *     summary="售后提醒内容获取",
     *     tags={"售后"},
     *     description="售后提醒内容获取",
     *     operationId="getRemind",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="company_id", in="query", description="companyID", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="intro", type="string", description="内容详情" ),
     *                     @SWG\Property(property="is_open", type="boolean", description="是否开启", default=false ),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getRemind(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getRemind($company_id);

        return $this->response->array($result);
    }
}
