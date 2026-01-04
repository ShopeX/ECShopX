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

namespace KaquanBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\VipGradeOrderService;
use KaquanBundle\Jobs\batchReceiveMemberCard;

class VipGradeController extends BaseController
{
    /**
     * @SWG\Put(
     *     path="/membercard/vipgrade",
     *     summary="保存付费会员等级卡",
     *     tags={"卡券"},
     *     description="保存付费会员等级卡",
     *     operationId="addDataVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券ID",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="grade_info",
     *         in="query",
     *         description="等级名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */

    public function addDataVipGrade(Request $request)
    {
        $postdata = $request->all('grade_info');
        $companyId = app('auth')->user()->get('company_id');
        $gradeInfo = $postdata['grade_info'];
        $vipGradeService = new VipGradeService();
        $result = $vipGradeService->createVipGrade($companyId, $gradeInfo);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/membercard/vipgrades",
     *     summary="获取等级列表",
     *     tags={"卡券"},
     *     description="获取等级列表",
     *     operationId="listDataVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items(
     *                 ref="#/definitions/VipGrade"
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */

    public function listDataVipGrade(Request $request)
    {
        if ($request->get('is_disabled') === 'false') {
            $filter['is_disabled'] = false;
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $vipGradeService = new VipGradeService();
        $result = $vipGradeService->listDataVipGrade($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/vipgrades/uselist",
     *     summary="获取指定用户所有的付费会员等级到期时间",
     *     tags={"卡券"},
     *     description="如果未购买那么则为当前到期",
     *     operationId="getAllUserVipGrade",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="vip_grade_id", type="string", example="1", description="付费会员卡等级ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     *                          @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型"),
     *                          @SWG\Property( property="default_grade", type="string", example="false", description="是否默认等级"),
     *                          @SWG\Property( property="is_disabled", type="string", example="false", description="是否禁用"),
     *                          @SWG\Property( property="background_pic_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/MUQsdY0GdK5nQNFBaEhiao8MfBoP4B70L2rfqJDROzKgwUBvANmHMq9bQV2G1IWibKxK8iaukqbHiaicNkGKZPbX8EA/0?wx_fmt=jpeg", description="会员卡背景图"),
     *                          @SWG\Property( property="description", type="string", example="1、VIP2、整场促销3、畅想优惠", description="内容"),
     *                          @SWG\Property( property="price_list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="name", type="string", example="monthly", description="名称"),
     *                                  @SWG\Property( property="price", type="string", example="0.01", description="价格"),
     *                                  @SWG\Property( property="day", type="string", example="30", description="生日日期"),
     *                                  @SWG\Property( property="desc", type="string", example="30天", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="privileges", type="object",
     *                                  @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *                                  @SWG\Property( property="discount_desc", type="string", example="8", description=""),
     *                          ),
     *                          @SWG\Property( property="created", type="string", example="1560947408", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1560947408", description="修改时间"),
     *                          @SWG\Property( property="guide_title", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description="购买引导文本"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否是默认"),
     *                          @SWG\Property( property="end_time", type="string", example="", description="截止时间"),
     *                          @SWG\Property( property="is_had_vip", type="string", example="false", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getAllUserVipGrade(Request $request)
    {
        $vipGradeOrder = new VipGradeOrderService();
        $companyId = app('auth')->user()->get('company_id');
        $userId = $request->input('user_id');
        $vipGradeUseList = $vipGradeOrder->userVipGradeGet($companyId, $userId, true);

        $filter['company_id'] = $companyId;
        $filter['is_disabled'] = false;
        $vipGradeService = new VipGradeService();
        $vipGradeList = $vipGradeService->lists($filter);

        $result['is_open'] = false;
        $result['list'] = [];
        if ($vipGradeList) {
            foreach ($vipGradeList as $row) {
                $row['end_time'] = isset($vipGradeUseList[$row['lv_type']]) ? $vipGradeUseList[$row['lv_type']]['end_time'] : '';
                $row['is_had_vip'] = isset($vipGradeUseList[$row['lv_type']]) ? $vipGradeUseList[$row['lv_type']]['is_had_vip'] : false;
                $result['is_open'] = true;
                $result['list'][] = $row;
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/vipgrades/active_delay",
     *     summary="主动延期付费会员",
     *     tags={"卡券"},
     *     description="主动延期付费会员",
     *     operationId="receiveMemberCard",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="会员手机号", required=true, type="string"),
     *     @SWG\Parameter( name="vipGradeAddDay", in="query", description="付费会员延期参数:{vip :{day,vip_grade_id},svip:{day,vip_grade_id}}", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function receiveMemberCard(Request $request)
    {
        $vipGradeOrder = new VipGradeOrderService();
        $companyId = app('auth')->user()->get('company_id');
        $userId = $request->input('user_id');
        $mobile = $request->input('mobile');

        $vipGradeAddDay = $request->input('vipGradeAddDay');
        $vipGradeAddDay = json_decode($vipGradeAddDay, true);
        foreach ($vipGradeAddDay as $row) {
            if ($row['day'] && $row['day'] > 0) {
                $data = [
                    'vip_grade_id' => $row['vip_grade_id'],
                    'day' => $row['day'],
                    'card_type' => 'custom',
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'mobile' => $mobile,
                    'source_type' => 'admin',
                ];
                $vipGradeOrder->receiveMemberCard($data);
            }
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/vipgrades/batch_active_delay",
     *     summary="批量主动延期付费会员",
     *     tags={"卡券"},
     *     description="批量主动延期付费会员",
     *     operationId="batchReceiveMemberCard",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="add_day", in="query", description="付费会员延期天数", required=true, type="string"),
     *     @SWG\Parameter( name="filter", in="query", description="付费会员延期条件 users:指定会员，expired:付费会员已失效", required=true, type="string"),
     *     @SWG\Parameter( name="vip_grade_id", in="query", description="付费会员ID", required=true, type="string"),
     *     @SWG\Parameter( name="users", in="query", description="指定延期的会员", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function batchReceiveMemberCard(Request $request)
    {
        $vipGradeOrder = new VipGradeOrderService();
        $companyId = app('auth')->user()->get('company_id');

        $vipGradeId = $request->input('vip_grade_id');
        // 判断day是否为正整数
        $day = intval($request->input('add_day'));
        if ($day <= 0) {
            throw new ResourceException(trans('KaquanBundle.extend_days_error'));
        }

        $vipGradeService = new VipGradeService();
        $info = $vipGradeService->getInfo(['vip_grade_id' => $vipGradeId, 'company_id' => $companyId]);
        if (!$info) {
            throw new ResourceException(trans('KaquanBundle.invalid_vip_grade'));
        }

        $users = [];
        if ($request->input('filter') == 'expired') {
            // 获取失效的会员数量
            $count = $vipGradeService->countExpiredVipGrade($companyId, $info['lv_type']);
            if ($count <= 50) {
                $users = $vipGradeService->getExpiredVipGradeUser($companyId, $info['lv_type']);
            } else {
                $jobParams = [
                    'vip_grade_id' => $$vipGradeId,
                    'vip_type' => $info['lv_type'],
                    'add_day' => $day,
                    'filter' => 'expired',
                    'company_id' => $companyId,
                ];
                // 加入队列
                $gotoJob = (new batchReceiveMemberCard($jobParams))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }
        } elseif ($request->input('filter') == 'users') {
            $users = json_decode($request->input('users'), true);
        }

        if ($users) {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                foreach ($users as $row) {
                    $data = [
                        'vip_grade_id' => $vipGradeId,
                        'day' => $day,
                        'card_type' => 'custom',
                        'user_id' => $row['user_id'],
                        'company_id' => $companyId,
                        'mobile' => $row['mobile'],
                        'source_type' => 'admin',
                    ];
                    $vipGradeOrder->receiveMemberCard($data);
                }
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw new ResourceException($e->getMessage());
            }
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/vipgrade/order",
     *     summary="获取会员卡购买记录",
     *     tags={"卡券"},
     *     description="获取会员卡购买记录",
     *     operationId="listDataVipGradeOrder",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员ID", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="order_id", type="string", example="3316619000060399", description="订单编号"),
     *                          @SWG\Property( property="vip_grade_id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20399", description="用户id"),
     *                          @SWG\Property( property="mobile", type="string", example="18530870713", description="手机号"),
     *                          @SWG\Property( property="title", type="string", example="一般付费", description="标题"),
     *                          @SWG\Property( property="price", type="string", example="0", description="价格"),
     *                          @SWG\Property( property="card_type", type="object",
     *                                  @SWG\Property( property="day", type="string", example="1", description="生日日期"),
     *                                  @SWG\Property( property="desc", type="string", example="后台手动赠送1天", description=""),
     *                                  @SWG\Property( property="name", type="string", example="custom", description="名称"),
     *                                  @SWG\Property( property="price", type="string", example="0", description="价格"),
     *                          ),
     *                          @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="source_id", type="string", example="null", description="来源id"),
     *                          @SWG\Property( property="source_type", type="string", example="admin", description="卡券来源类型"),
     *                          @SWG\Property( property="monitor_id", type="string", example="null", description="监控id"),
     *                          @SWG\Property( property="order_status", type="string", example="DONE", description="订单状态"),
     *                          @SWG\Property( property="created", type="string", example="1611905379", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611905379", description="修改时间"),
     *                          @SWG\Property( property="fee_type", type="string", example="CNY", description="货币类型"),
     *                          @SWG\Property( property="fee_rate", type="string", example="1", description="货币汇率"),
     *                          @SWG\Property( property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */

    public function listDataVipGradeOrder(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 100);
        $vipGradeOrder = new VipGradeOrderService();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        $filter['order_status'] = 'DONE';
        $result = $vipGradeOrder->lists($filter, ['created' => 'desc'], $pageSize, $page);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        if ($result['list']) {
            foreach ($result['list'] as $key => $value) {
                if ($datapassBlock) {
                    $result['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
            }
        }
        return $this->response->array($result);
    }
}
