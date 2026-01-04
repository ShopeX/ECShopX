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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Orders;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use MembersBundle\Services\MemberService;
use OpenapiBundle\Services\Order\AftersalesService as OpenapiAftersalesService;
use AftersalesBundle\Services\AftersalesService;
use AftersalesBundle\Services\AftersalesRefundService;

class Aftersales extends Controller
{
    /**
     * @SWG\Get(
     *     path="/ecx.aftersales.get",
     *     summary="售后搜索",
     *     tags={"订单"},
     *     description="获取售后单列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.aftersales.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页数量,默认：20，最大值为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_begin", description="查询创建售后开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="查询创建售后结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="aftersales_bn", type="string", example="202104265498485", description="售后单号"),
     *                          @SWG\Property( property="order_id", type="string", example="3403597000210032", description="订单编号"),
     *                          @SWG\Property( property="aftersales_type", type="string", example="ONLY_REFUND", description="售后服务类型 ONLY_REFUND-仅退款;REFUND_GOODS-退货退款;"),
     *                          @SWG\Property( property="aftersales_status", type="string", example="0", description="售后状态 0-待处理;1-处理中;2-已处理;3-已驳回;4-已撤销;"),
     *                          @SWG\Property( property="progress", type="string", example="0", description="处理进度 0-等待商家处理;1-商家接受申请，等待消费者回寄;2-消费者回寄，等待商家收货确认;3-已驳回;4-已处理;5-退款驳回;6-退款完成;7-已撤销。已关闭;8-商家确认收货,等待审核退款;9-退款处理中;"),
     *                          @SWG\Property( property="refund_fee", type="string", example="2", description="应退总金额，单位(分)"),
     *                          @SWG\Property( property="refund_point", type="string", example="0", description="应退总积分"),
     *                          @SWG\Property( property="reason", type="string", example="123", description="申请售后原因"),
     *                          @SWG\Property( property="description", type="string", example="2323213123", description="申请描述"),
     *                          @SWG\Property( property="evidence_pic", type="array",
     *                              @SWG\Items( type="string", example="undefined", description="图片凭证信息 图片url"),
     *                          ),
     *                          @SWG\Property( property="refuse_reason", type="string", example="null", description="拒绝原因"),
     *                          @SWG\Property( property="memo", type="string", example="null", description="售后备注"),
     *                          @SWG\Property( property="sendback_data", type="object",
     *                              @SWG\Property( property="logi_no", type="string", example="75467420382001", description="回寄物流单号"),
     *                              @SWG\Property( property="corp_code", type="string", example="YTO", description="回寄物流公司编号"),
     *                              @SWG\Property( property="receiver_mobile", type="string", example="", description="回寄收货人手机号"),
     *                              @SWG\Property( property="receiver_address", type="string", example="", description="回寄收货人地址"),
     *                          ),
     *                          @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                          @SWG\Property( property="aftersales_address", type="object",
     *                              @SWG\Property( property="aftersales_mobile", type="string", example="13000000000", description="售后回寄电话"),
     *                              @SWG\Property( property="aftersales_address", type="string", example="地址", description="售后回寄地址"),
     *                              @SWG\Property( property="aftersales_contact", type="string", example="姓名", description="售后回寄姓名"),
     *                          ),
     *                          @SWG\Property( property="detail", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="item_bn", type="string", example="S6071612A9598C", description="商品编码"),
     *                                  @SWG\Property( property="item_name", type="string", example="贝妮格林活力紧致精华凝露", description="商品名称"),
     *                                  @SWG\Property( property="item_pic", type="string", example="", description="商品图片"),
     *                                  @SWG\Property( property="num", type="string", example="2", description="售后数量"),
     *                                  @SWG\Property( property="refund_fee", type="string", example="2", description="应退总金额，单位(分)"),
     *                                  @SWG\Property( property="refund_point", type="string", example="0", description="应退总积分"),
     *                                  @SWG\Property( property="aftersales_type", type="string", example="ONLY_REFUND", description="售后服务类型 ONLY_REFUND-仅退款;REFUND_GOODS-退货退款;"),
     *                                  @SWG\Property( property="progress", type="string", example="0", description="处理进度 0-等待商家处理;1-商家接受申请，等待消费者回寄;2-消费者回寄，等待商家收货确认;3-已驳回;4-已处理;5-退款驳回;6-退款完成;7-已撤销。已关闭;8-商家确认收货,等待审核退款;9-退款处理中;"),
     *                                  @SWG\Property( property="aftersales_status", type="string", example="0", description="售后状态 0-待处理;1-处理中;2-已处理;3-已驳回;4-已撤销;"),
     *                                  @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                                  @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                                  @SWG\Property( property="auto_refuse_time", type="string", example="", description="售后自动驳回时间"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getAftersalesList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'mobile', 'time_begin', 'time_end');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['mobile']) {
            if (!preg_match('/^1[345789][0-9]{9}$/', $params['mobile'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '请填写正确的手机号');
            }
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
            if (!$memberInfo) {
                throw new ErrorException(ErrorCode::ORDER_MEMBER_NOT_FOUND);
            }
            $filter['user_id'] = $memberInfo['user_id'];
        }
        if ($params['time_begin'] && $params['time_end']) {
            if (strtotime($params['time_begin']) > strtotime($params['time_end'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if ($params['time_begin']) {
            $filter['create_time|gte'] = strtotime($params['time_begin']);
        }
        if ($params['time_end']) {
            $filter['create_time|lte'] = strtotime($params['time_end']);
        }
        $page = $params['page'];
        $limit = $this->getPageSize();
        $offset = ($page - 1) * $limit;
        $aftersalesService = new AftersalesService();
        $aftersalesList = $aftersalesService->getAftersalesList($filter, $offset, $limit);

        $openapiAftersalesService = new OpenapiAftersalesService();
        $return = $openapiAftersalesService->formateAftersalesList($aftersalesList, (int)$page, (int)$limit);
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.aftersales.incr.get",
     *     summary="增量售后单搜索",
     *     tags={"订单"},
     *     description="根据条件，搜索售后单（修改时间倒序）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.aftersales.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页数量,默认：20，最大值为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="start_modified", description="查询更新订单开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="end_modified", description="查询更新订单结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="aftersales_type", description="售后服务类型:ONLY_REFUND 仅退款,REFUND_GOODS 退货退款" ),
     *     @SWG\Parameter( in="query", type="integer", required=false, name="aftersales_status", description="售后状态:0 待处理,1 处理中2 已处理,3 已驳回,4 已撤销" ),
     *     @SWG\Parameter( in="query", type="integer", required=false, name="progress", description="处理进度:0 等待商家处理,1 商家接受申请，等待消费者回寄,2 消费者回寄，等待商家收货确认,8 商家确认收货,等待审核退款,3 已驳回,4 已处理,7 已撤销。已关闭,9 退款处理中,5 退款驳回,6 退款完成" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="aftersales_bn", type="string", example="202104265498485", description="售后单号"),
     *                          @SWG\Property( property="order_id", type="string", example="3403597000210032", description="订单编号"),
     *                          @SWG\Property( property="aftersales_type", type="string", example="ONLY_REFUND", description="售后服务类型 ONLY_REFUND-仅退款;REFUND_GOODS-退货退款;"),
     *                          @SWG\Property( property="aftersales_status", type="string", example="0", description="售后状态 0-待处理;1-处理中;2-已处理;3-已驳回;4-已撤销;"),
     *                          @SWG\Property( property="progress", type="string", example="0", description="处理进度 0-等待商家处理;1-商家接受申请，等待消费者回寄;2-消费者回寄，等待商家收货确认;3-已驳回;4-已处理;5-退款驳回;6-退款完成;7-已撤销。已关闭;8-商家确认收货,等待审核退款;9-退款处理中;"),
     *                          @SWG\Property( property="refund_fee", type="string", example="2", description="应退总金额，单位(分)"),
     *                          @SWG\Property( property="refund_point", type="string", example="0", description="应退总积分"),
     *                          @SWG\Property( property="reason", type="string", example="123", description="申请售后原因"),
     *                          @SWG\Property( property="description", type="string", example="2323213123", description="申请描述"),
     *                          @SWG\Property( property="evidence_pic", type="array",
     *                              @SWG\Items( type="string", example="undefined", description="图片凭证信息 图片url"),
     *                          ),
     *                          @SWG\Property( property="refuse_reason", type="string", example="null", description="拒绝原因"),
     *                          @SWG\Property( property="memo", type="string", example="null", description="售后备注"),
     *                          @SWG\Property( property="sendback_data", type="object",
     *                              @SWG\Property( property="logi_no", type="string", example="75467420382001", description="回寄物流单号"),
     *                              @SWG\Property( property="corp_code", type="string", example="YTO", description="回寄物流公司编号"),
     *                              @SWG\Property( property="receiver_mobile", type="string", example="", description="回寄收货人手机号"),
     *                              @SWG\Property( property="receiver_address", type="string", example="", description="回寄收货人地址"),
     *                          ),
     *                          @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                          @SWG\Property( property="aftersales_address", type="object",
     *                              @SWG\Property( property="aftersales_mobile", type="string", example="13000000000", description="售后回寄电话"),
     *                              @SWG\Property( property="aftersales_address", type="string", example="地址", description="售后回寄地址"),
     *                              @SWG\Property( property="aftersales_contact", type="string", example="姓名", description="售后回寄姓名"),
     *                          ),
     *                          @SWG\Property( property="detail", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="item_bn", type="string", example="S6071612A9598C", description="商品编码"),
     *                                  @SWG\Property( property="item_name", type="string", example="贝妮格林活力紧致精华凝露", description="商品名称"),
     *                                  @SWG\Property( property="item_pic", type="string", example="", description="商品图片"),
     *                                  @SWG\Property( property="num", type="string", example="2", description="售后数量"),
     *                                  @SWG\Property( property="refund_fee", type="string", example="2", description="应退总金额，单位(分)"),
     *                                  @SWG\Property( property="refund_point", type="string", example="0", description="应退总积分"),
     *                                  @SWG\Property( property="aftersales_type", type="string", example="ONLY_REFUND", description="售后服务类型 ONLY_REFUND-仅退款;REFUND_GOODS-退货退款;"),
     *                                  @SWG\Property( property="progress", type="string", example="0", description="处理进度 0-等待商家处理;1-商家接受申请，等待消费者回寄;2-消费者回寄，等待商家收货确认;3-已驳回;4-已处理;5-退款驳回;6-退款完成;7-已撤销。已关闭;8-商家确认收货,等待审核退款;9-退款处理中;"),
     *                                  @SWG\Property( property="aftersales_status", type="string", example="0", description="售后状态 0-待处理;1-处理中;2-已处理;3-已驳回;4-已撤销;"),
     *                                  @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                                  @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                                  @SWG\Property( property="auto_refuse_time", type="string", example="", description="售后自动驳回时间"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getIncrAftersalesList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'mobile', 'start_modified', 'end_modified', 'aftersales_type', 'aftersales_status', 'progress');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['mobile']) {
            if (!preg_match('/^1[345789][0-9]{9}$/', $params['mobile'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '请填写正确的手机号');
            }
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
            if (!$memberInfo) {
                throw new ErrorException(ErrorCode::ORDER_MEMBER_NOT_FOUND);
            }
            $filter['user_id'] = $memberInfo['user_id'];
        }
        if ($params['start_modified'] && $params['end_modified']) {
            if (strtotime($params['start_modified']) > strtotime($params['end_modified'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if ($params['start_modified']) {
            $filter['update_time|gte'] = strtotime($params['start_modified']);
        }
        if ($params['end_modified']) {
            $filter['update_time|lte'] = strtotime($params['end_modified']);
        }
        if ($params['aftersales_type']) {
            $filter['aftersales_type'] = $params['aftersales_type'];
        }
        if ($params['aftersales_status']) {
            $filter['aftersales_status'] = $params['aftersales_status'];
        }
        if ($params['progress']) {
            $filter['progress'] = $params['progress'];
        }
        $page = $params['page'];
        $limit = $this->getPageSize();
        $offset = ($page - 1) * $limit;
        $aftersalesService = new AftersalesService();
        $aftersalesList = $aftersalesService->getAftersalesList($filter, $offset, $limit, ['update_time' => 'DESC']);

        $openapiAftersalesService = new OpenapiAftersalesService();
        $return = $openapiAftersalesService->formateAftersalesList($aftersalesList, (int)$page, (int)$limit);
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.aftersales.detail.get",
     *     summary="售后详情",
     *     tags={"订单"},
     *     description="根据售后单号，获取售后单详情。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.aftersales.detail.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="aftersales_bn", description="售后单号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="aftersales_bn", type="string", example="202104155757102", description="售后单号"),
     *                  @SWG\Property( property="order_id", type="string", example="3392460000180444", description="订单编号"),
     *                  @SWG\Property( property="aftersales_type", type="string", example="REFUND_GOODS", description="售后服务类型 ONLY_REFUND-仅退款;REFUND_GOODS-退货退款;"),
     *                  @SWG\Property( property="aftersales_status", type="string", example="2", description="售后状态 0-待处理;1-处理中;2-已处理;3-已驳回;4-已撤销;"),
     *                  @SWG\Property( property="progress", type="string", example="4", description="处理进度 0-等待商家处理;1-商家接受申请，等待消费者回寄;2-消费者回寄，等待商家收货确认;3-已驳回;4-已处理;5-退款驳回;6-退款完成;7-已撤销。已关闭;8-商家确认收货,等待审核退款;9-退款处理中;"),
     *                  @SWG\Property( property="refund_fee", type="string", example="10", description="应退总金额，单位(分)"),
     *                  @SWG\Property( property="refund_point", type="string", example="0", description="应退总积分"),
     *                  @SWG\Property( property="reason", type="string", example="123", description="申请售后原因"),
     *                  @SWG\Property( property="description", type="string", example="434343", description="申请描述"),
     *                  @SWG\Property( property="evidence_pic", type="array",
     *                      @SWG\Items( type="string", example="", description="图片凭证信息 图片url"),
     *                  ),
     *                  @SWG\Property( property="refuse_reason", type="string", example="null", description="拒绝原因"),
     *                  @SWG\Property( property="memo", type="string", example="null", description="售后备注"),
     *                  @SWG\Property( property="sendback_data", type="object",
     *                          @SWG\Property( property="logi_no", type="string", example="75467420382001", description="回寄物流单号"),
     *                          @SWG\Property( property="corp_code", type="string", example="SF", description="回寄物流公司编号"),
     *                          @SWG\Property( property="receiver_mobile", type="string", example="", description="回寄收货人手机号"),
     *                          @SWG\Property( property="receiver_address", type="string", example="", description="回寄收货人详细地址"),
     *                  ),
     *                  @SWG\Property( property="create_time", type="string", example="2021-04-15 11:48:41", description="创建时间"),
     *                  @SWG\Property( property="update_time", type="string", example="2021-04-15 11:52:29", description="更新时间"),
     *                      @SWG\Property( property="refund_bn", type="string", example="2202012025055559798", description="退款单号"),
     *                  @SWG\Property( property="aftersales_address", type="object",
     *                      @SWG\Property( property="aftersales_mobile", type="string", example="13000000000", description="售后回寄电话"),
     *                      @SWG\Property( property="aftersales_address", type="string", example="地址", description="售后回寄地址"),
     *                      @SWG\Property( property="aftersales_contact", type="string", example="姓名", description="售后回寄姓名"),
     *                  ),
     *                  @SWG\Property( property="detail", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_bn", type="string", example="AQ123456", description="商品编码"),
     *                          @SWG\Property( property="item_name", type="string", example="啊Q实物(床上四件套尺寸:180*150(cm))", description="商品名称"),
     *                          @SWG\Property( property="item_pic", type="string", example="", description="商品图片地址"),
     *                          @SWG\Property( property="num", type="string", example="1", description="售后数量"),
     *                          @SWG\Property( property="refund_fee", type="string", example="10", description="应退总金额，单位(分)"),
     *                          @SWG\Property( property="refund_point", type="string", example="0", description="应退总积分"),
     *                          @SWG\Property( property="aftersales_type", type="string", example="REFUND_GOODS", description="售后服务类型 ONLY_REFUND-仅退款;REFUND_GOODS-退货退款;"),
     *                          @SWG\Property( property="progress", type="string", example="4", description="处理进度 0-等待商家处理;1-商家接受申请，等待消费者回寄;2-消费者回寄，等待商家收货确认;3-已驳回;4-已处理;5-退款驳回;6-退款完成;7-已撤销。已关闭;8-商家确认收货,等待审核退款;9-退款处理中;"),
     *                          @SWG\Property( property="aftersales_status", type="string", example="2", description="售后状态 0-待处理;1-处理中;2-已处理;3-已驳回;4-已撤销;"),
     *                          @SWG\Property( property="create_time", type="string", example="2021-04-15 11:48:41", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-04-15 11:52:29", description="更新时间"),
     *                          @SWG\Property( property="auto_refuse_time", type="string", example="", description="售后自动驳回时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getAftersalesDetail(Request $request)
    {
        $params = $request->all('aftersales_bn');
        $rules = [
            'aftersales_bn' => ['required', '请填写售后单号'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        try {
            $aftersalesService = new AftersalesService();
            $aftersalesDetail = $aftersalesService->getAftersalesDetail($companyId, $params['aftersales_bn']);
            $openapiAftersalesService = new OpenapiAftersalesService();
            $return = $openapiAftersalesService->formateAftersalesDetail($aftersalesDetail);
            return $this->response->array($return);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, $e->getMessage());
        }
    }

    /**
     * @SWG\Get(
     *     path="/ecx.refund.get",
     *     summary="退款单搜索",
     *     tags={"订单"},
     *     description="获取退款单列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.refund.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页数量,默认：20，最大值为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_begin", description="查询创建退款单开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="查询创建退款单结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="refund_bn", type="string", example="2202001131029897984", description="退款单号"),
     *                          @SWG\Property( property="aftersales_bn", type="string", example="202104265498485", description="售后单号"),
     *                          @SWG\Property( property="order_id", type="string", example="3403597000210032", description="订单编号"),
     *                          @SWG\Property( property="trade_id", type="string", example="3530558000360632", description="交易单号"),
     *                          @SWG\Property( property="refund_id", type="string", example="50300402422019111113201138799", description="商户返回退款单号"),
     *                          @SWG\Property( property="refund_status", type="string", example="0", description="退款状态 READY-未审核;AUDIT_SUCCESS-审核成功待退款;SUCCESS-退款成功;REFUSE-退款驳回;CANCEL-撤销退款;REFUNDCLOSE-退款关闭;PROCESSING-退款处理中;PROCESSING-已发起退款等待到账;CHANGE-退款异常;"),
     *                          @SWG\Property( property="refund_fee", type="string", example="2", description="应退金额，单位(分)"),
     *                          @SWG\Property( property="refunded_fee", type="string", example="2", description="实退金额，单位(分)"),
     *                          @SWG\Property( property="refund_point", type="string", example="2", description="应退积分"),
     *                          @SWG\Property( property="refunded_point", type="string", example="2", description="实退积分"),
     *                          
     *                          @SWG\Property( property="pay_type", type="string", example="wxpay", description="支付方式，同对应支付单"),
     *                          @SWG\Property( property="refund_success_time", type="string", example="2021-04-26 15:37:10", description="退款完成时间"),
     *                          @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getRefundList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'mobile', 'time_begin', 'time_end');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['mobile']) {
            if (!preg_match('/^1[345789][0-9]{9}$/', $params['mobile'])) {
                throw new ErrorException(ErrorCode::ORDER_REFUND_HANDLE_ERROR, '请填写正确的手机号');
            }
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
            if (!$memberInfo) {
                throw new ErrorException(ErrorCode::ORDER_MEMBER_NOT_FOUND);
            }
            $filter['user_id'] = $memberInfo['user_id'];
        }
        if ($params['time_begin'] && $params['time_end']) {
            if (strtotime($params['time_begin']) > strtotime($params['time_end'])) {
                throw new ErrorException(ErrorCode::ORDER_REFUND_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if ($params['time_begin']) {
            $filter['create_time|gte'] = strtotime($params['time_begin']);
        }
        if ($params['time_end']) {
            $filter['create_time|lte'] = strtotime($params['time_end']);
        }
        $page = $params['page'];
        $limit = $this->getPageSize();
        $offset = ($page - 1) * $limit;
        $aftersalesRefundService = new AftersalesRefundService();
        $aftersalesRefundList = $aftersalesRefundService->getRefundsList($filter, $offset, $limit);

        $openapiAftersalesService = new OpenapiAftersalesService();
        $return = $openapiAftersalesService->formateRefundList($aftersalesRefundList, (int)$page, (int)$limit);
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.refund.incr.get",
     *     summary="增量退款单搜索",
     *     tags={"订单"},
     *     description="根据条件，搜索退款单（修改时间倒序）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.refund.incr.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页数量,默认：20，最大值为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="start_modified", description="查询更新订单开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="end_modified", description="查询更新订单结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="refund_bn", type="string", example="2202001131029897984", description="退款单号"),
     *                          @SWG\Property( property="aftersales_bn", type="string", example="202104265498485", description="售后单号"),
     *                          @SWG\Property( property="order_id", type="string", example="3403597000210032", description="订单编号"),
     *                          @SWG\Property( property="trade_id", type="string", example="3530558000360632", description="交易单号"),
     *                          @SWG\Property( property="refund_id", type="string", example="50300402422019111113201138799", description="商户返回退款单号"),
     *                          @SWG\Property( property="refund_channel", type="string", example="original", description="退款渠道 original-原路返回;"),
     *                          @SWG\Property( property="refund_status", type="string", example="0", description="退款状态 READY-未审核;AUDIT_SUCCESS-审核成功待退款;SUCCESS-退款成功;REFUSE-退款驳回;CANCEL-撤销退款;REFUNDCLOSE-退款关闭;PROCESSING-退款处理中;PROCESSING-已发起退款等待到账;CHANGE-退款异常;"),
     *                          @SWG\Property( property="refund_fee", type="string", example="2", description="应退金额，单位(分)"),
     *                          @SWG\Property( property="refunded_fee", type="string", example="2", description="实退金额，单位(分)"),
     *                          @SWG\Property( property="refund_point", type="string", example="2", description="应退积分"),
     *                          @SWG\Property( property="refunded_point", type="string", example="2", description="实退积分"),
     *                          
     *                          @SWG\Property( property="pay_type", type="string", example="wxpay", description="支付方式，同对应支付单"),
     *                          @SWG\Property( property="refund_success_time", type="string", example="2021-04-26 15:37:10", description="退款完成时间"),
     *                          @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getIncrRefundList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'mobile', 'start_modified', 'end_modified');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['mobile']) {
            if (!preg_match('/^1[345789][0-9]{9}$/', $params['mobile'])) {
                throw new ErrorException(ErrorCode::ORDER_REFUND_HANDLE_ERROR, '请填写正确的手机号');
            }
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId, 'mobile' => $params['mobile']]);
            if (!$memberInfo) {
                throw new ErrorException(ErrorCode::ORDER_MEMBER_NOT_FOUND);
            }
            $filter['user_id'] = $memberInfo['user_id'];
        }
        if ($params['start_modified'] && $params['end_modified']) {
            if (strtotime($params['start_modified']) > strtotime($params['end_modified'])) {
                throw new ErrorException(ErrorCode::ORDER_REFUND_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if ($params['start_modified']) {
            $filter['update_time|gte'] = strtotime($params['start_modified']);
        }
        if ($params['end_modified']) {
            $filter['update_time|lte'] = strtotime($params['end_modified']);
        }
        
        $page = $params['page'];
        $limit = $this->getPageSize();
        $offset = ($page - 1) * $limit;
        $aftersalesRefundService = new AftersalesRefundService();
        $aftersalesRefundList = $aftersalesRefundService->getRefundsList($filter, $offset, $limit, ['update_time' => 'DESC']);

        $openapiAftersalesService = new OpenapiAftersalesService();
        $return = $openapiAftersalesService->formateRefundList($aftersalesRefundList, (int)$page, (int)$limit);
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.refund.detail.get",
     *     summary="退款单详情",
     *     tags={"订单"},
     *     description="根据退款单号，获取退款单详情。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.refund.detail.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="refund_bn", description="退款单号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="refund_bn", type="string", example="2202001131029897984", description="退款单号"),
     *                  @SWG\Property( property="aftersales_bn", type="string", example="202104265498485", description="售后单号"),
     *                  @SWG\Property( property="order_id", type="string", example="3403597000210032", description="订单编号"),
     *                  @SWG\Property( property="trade_id", type="string", example="3530558000360632", description="交易单号"),
     *                  @SWG\Property( property="refund_id", type="string", example="50300402422019111113201138799", description="商户返回退款单号"),
     *                  @SWG\Property( property="refund_channel", type="string", example="original", description="退款渠道 original-原路返回;"),
     *                  @SWG\Property( property="refund_status", type="string", example="0", description="退款状态 READY-未审核;AUDIT_SUCCESS-审核成功待退款;SUCCESS-退款成功;REFUSE-退款驳回;CANCEL-撤销退款;REFUNDCLOSE-退款关闭;PROCESSING-退款处理中;PROCESSING-已发起退款等待到账;CHANGE-退款异常;"),
     *                  @SWG\Property( property="return_freight", type="string", example="0", description="是否退运费 0-不退运费;1-退运费;"),
     *                  @SWG\Property( property="refund_fee", type="string", example="2", description="应退金额，单位(分)"),
     *                  @SWG\Property( property="refunded_fee", type="string", example="2", description="实退金额，单位(分)"),
     *                  @SWG\Property( property="refund_point", type="string", example="2", description="应退积分"),
     *                  @SWG\Property( property="refunded_point", type="string", example="2", description="实退积分"),
     *                  @SWG\Property( property="pay_type", type="string", example="wxpay", description="支付方式，同对应支付单"),
     *                  @SWG\Property( property="refund_success_time", type="string", example="2021-04-26 15:37:10", description="退款完成时间"),
     *                  @SWG\Property( property="create_time", type="string", example="2021-04-26 15:37:10", description="创建时间"),
     *                  @SWG\Property( property="update_time", type="string", example="2021-04-26 15:37:10", description="更新时间"),
     *                  @SWG\Property( property="cancel_reason", type="string", example="2021-04-26 15:37:10", description="售前的退款原因"),
     *                  
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getRefundDetail(Request $request)
    {
        $params = $request->all('refund_bn');
        $rules = [
            'refund_bn' => ['required', '请填写退款单号'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['company_id'] = $request->get('auth')['company_id'];

        try {
            $aftersalesRefundService = new AftersalesRefundService();
            $refundDetail = $aftersalesRefundService->getRefunds($params);
            $openapiAftersalesService = new OpenapiAftersalesService();
            $return = $openapiAftersalesService->formateRefundDetail($refundDetail);
            return $this->response->array($return);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::ORDER_REFUND_HANDLE_ERROR, $e->getMessage());
        }
    }
}
