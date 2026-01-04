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

namespace SalespersonBundle\Http\AdminApi\V1\Action;

use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorSalesmanRoleService;
use SalespersonBundle\Services\SignService;

class ShopSalespersonController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/distributorlist",
     *     summary="获取导购店铺列表",
     *     tags={"导购"},
     *     description="获取导购店铺列表",
     *     operationId="getDistributorDataList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="store_type", in="query", description="店铺类型", required=true, type="string", default="distributor"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="门店id"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="store_type", type="string", example="distributor", description="店铺类型"),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="店铺地址"),
     *                          @SWG\Property( property="store_name", type="string", example="【店铺】视力康眼镜(中兴路店)", description="店铺名称"),
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺ID"),
     *                          @SWG\Property( property="shop_logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/...", description="店铺Logo图片地址"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getDistributorDataList(Request $request)
    {
        $authInfo = $this->auth->user();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 500);
        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter['company_id'] = $authInfo['company_id'];
        $dIds = [];
        if ($filter['company_id'] == $authInfo['company_id']) {
            $filter['salesperson_id'] = $authInfo['salesperson_id'];
            $filter['store_type'] = $request->get('store_type', 'distributor');
            $listdata = $salespersonService->getSalespersonRelShopdata($filter, $page, $pageSize);
            $dIds = array_column($listdata['list'], 'distributor_id');
        }
        $distributorService = new DistributorService();
        $distributor = $distributorService->getDefaultDistributor($filter['company_id']);
        if ($distributor && (!$dIds || !in_array($distributor['distributor_id'], $dIds))) {
            $listdata['list'][] = [
                'address' => $distributor['address'],
                'store_name' => $distributor['name'],
                'distributor_id' => $distributor['distributor_id'],
                'shop_logo' => $distributor['logo'],
                'hour' => $distributor['hour'],
            ];
            $listdata['total_count'] = 1;
        }
        return $this->response->array($listdata);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/shoplist",
     *     summary="获取门店列表",
     *     tags={"导购"},
     *     description="获取门店列表",
     *     operationId="getShopDataList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="store_type", in="query", description="店铺类型", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="店铺ID"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="45", description="导购ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="store_type", type="string", example="distributor", description="店铺类型"),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="门店地址"),
     *                          @SWG\Property( property="store_name", type="string", example="【店铺】视力康眼镜(中兴路店)", description="门店名称"),
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="门店所属店铺ID"),
     *                          @SWG\Property( property="shop_logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/...", description="店铺Logo图片地址"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getShopDataList(Request $request)
    {
        $authInfo = $this->auth->user();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 500);
        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'salesperson_id' => $authInfo['salesperson_id'],
        ];
        if ($request->get('store_type')) {
            $filter['store_type'] = $request->get('store_type');
        }
        $listdata = $salespersonService->getSalespersonRelShopdata($filter, $page, $pageSize);
        return $this->response->array($listdata);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/salespersonCount",
     *     summary="导购数据统计",
     *     tags={"导购"},
     *     description="导购数据统计",
     *     operationId="getSalespersonCountData",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="date_range", in="query", description="N日数据， 0是今天", type="integer", default="0"),
     *     @SWG\Parameter( name="date_time", in="query", description="指定月份 2020-10-01", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="sendCouponsNum", type="string", example="0", description="发送优惠券数量"),
     *                  @SWG\Property( property="newUserNum", type="string", example="0", description="新会员数量"),
     *                  @SWG\Property( property="activityForward", type="string", example="0", description="活动奖励"),
     *                  @SWG\Property( property="orderPayFee", type="string", example="0", description="订单付款金额"),
     *                  @SWG\Property( property="orderPayNum", type="string", example="0", description="订单付款数量"),
     *                  @SWG\Property( property="newGuestDivided", type="string", example="0", description="新用户分红"),
     *                  @SWG\Property( property="salesCommission", type="string", example="0", description="佣金"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getSalespersonCountData(Request $request)
    {
        $salesperson_info = $authInfo = $this->auth->user();
        $date_range = $request->input('date_range', 0);
        $date_time = $request->input('date_time', '0');

        $date_range = intval($date_range);
        if ($date_time === '0') {
            if ($date_range == 0) { //今日数据
                $start = strtotime(date('Y-m-d'));
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-'.$date_range.'day')));
            }
            $end = strtotime(date('Y-m-d')) + 24 * 3600 - 1;
        } else {
            $start = strtotime($date_time);//指定月份月初时间戳
            $end = mktime(23, 59, 59, date('m', strtotime($date_time)) + 1, 00, date('Y', strtotime($date_time)));
        }
        $salesperson_service = new SalespersonService();
        $result = $salesperson_service->getSalespersonCountData($salesperson_info, $start, $end);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/salespersonQrcode",
     *     summary="导购二维码获取",
     *     tags={"导购"},
     *     description="导购二维码获取",
     *     operationId="getSalespersonContactQrCode",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="qr_code", type="string", example="https://wework.qpic.cn/wwpic/...", description="二维码图片地址"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getSalespersonContactQrCode(Request $request)
    {
        $authInfo = $this->auth->user();
        $salespersonService = new SalespersonService();
        $info = $salespersonService->getSalespersonDetail(['salesperson_id' => $authInfo['salesperson_id']]);
        if ($info['work_qrcode_configid'] ?? 0) {
            $config = app('wechat.work.wechat')->getConfig($authInfo['company_id']);
            $result = Factory::work($config)->contact_way->get($info['work_qrcode_configid']);
        } else {
            throw new ResourceException('获取导购二维码失败');
        }
        if ($result['errcode']) {
            throw new ResourceException('获取导购二维码失败');
        }
        return $this->response->array(['qr_code' => $result['contact_way']['qr_code']] ?? '');
    }

    /**
     * @SWG\Post(
     *     path="/admin/wxapp/salespersonQrcode",
     *     summary="导购二维码更新",
     *     tags={"导购"},
     *     description="导购二维码更新",
     *     operationId="updateSalespersonContactQrCode",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="qr_code", type="string", example="https://wework.qpic.cn/wwpic/...", description="二维码图片地址"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function updateSalespersonContactQrCode(Request $request)
    {
        $authInfo = $this->auth->user();
        $salespersonService = new SalespersonService();
        $info = $salespersonService->getSalespersonDetail(['salesperson_id' => $authInfo['salesperson_id']]);

        $config = app('wechat.work.wechat')->getConfig($authInfo['company_id']);
        $app = Factory::work($config)->contact_way;
        $app->delete($info['work_qrcode_configid']);

        $createParams = [
            'style' => 1,
            'skip_verify' => true,
            'user' => $info['work_userid'],
        ];
        $qrcodeConfigIdTemp = $app->create(1, 2, $createParams);
        $qrcodeConfigId = $qrcodeConfigIdTemp['config_id'] ?? '';

        $salespersonService->salesperson->updateSalespersonById($authInfo['salesperson_id'], ['work_qrcode_configid' => $qrcodeConfigId]);

        $result = $app->get($qrcodeConfigId);

        if ($result['errcode']) {
            throw new ResourceException('获取导购二维码失败');
        }
        return $this->response->array(['qr_code' => $result['contact_way']['qr_code']] ?? '');
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/getinfo",
     *     summary="导购获取自己的信息",
     *     tags={"导购"},
     *     description="导购获取自己的信息",
     *     operationId="getSelfInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员id "),
     *                  @SWG\Property( property="name", type="string", example="刘", description=" 姓名 "),
     *                  @SWG\Property( property="mobile", type="string", example="17621502659", description="手机号"),
     *                  @SWG\Property( property="created_time", type="string", example="1589884622", description="创建时间 | 积分变动时间 | 入会日期"),
     *                  @SWG\Property( property="salesperson_type", type="string", example="shopping_guide", description="人员类型 admin: 管理员; verification_clerk:核销员; shopping_guide:导购员"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="user_id", type="string", example="0", description="用户id "),
     *                  @SWG\Property( property="child_count", type="string", example="0", description=" 导购员引入的会员数"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description=" 是否有效"),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                  @SWG\Property( property="shop_name", type="string", example="null", description="门店名称 "),
     *                  @SWG\Property( property="number", type="string", example="", description="赠送数量 | 发送优惠券数量 | 数量 | 销售订单数量 | 导购员编号"),
     *                  @SWG\Property( property="friend_count", type="string", example="0", description="导购员会员好友数"),
     *                  @SWG\Property( property="avatar", type="string", example="http://wework.qpic.cn/bizmail/MX1ODnSIHYRdleZPpbicn1zXrFNh5pLOMpHLjG1JGUpUfKPc7L581qA/0", description=" 企业微信头像"),
     *                  @SWG\Property( property="work_userid", type="string", example="Liu", description="企业微信userid "),
     *                  @SWG\Property( property="work_configid", type="string", example="e65bf9c9ef874430fd1d7d524c5cb506", description="企业微信userid"),
     *                  @SWG\Property( property="work_qrcode_configid", type="string", example="f044a745e07941db7cf5e964c5f73e24", description="企业微信userid"),
     *                  @SWG\Property( property="role", type="string", example="6", description="导购权限集合 "),
     *                  @SWG\Property( property="created", type="string", example="1589884622", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1611917545", description=" 修改时间"),
     *                  @SWG\Property( property="shop_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="门店ID"),
     *                  ),
     *                  @SWG\Property( property="distributor_ids", type="array",
     *                      @SWG\Items( type="string", example="33", description="店铺ID"),
     *                  ),
     *                  @SWG\Property( property="store_type", type="string", example="distributor", description="店铺类型，shop：门店, distributor:店铺"),
     *                  @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id "),
     *                  @SWG\Property( property="store_name", type="string", example="视力康眼镜(中兴路店)", description="店铺名称 "),
     *                  @SWG\Property( property="role_info", type="object",
     *                          @SWG\Property( property="salesman_role_id", type="string", example="6", description="角色ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="role_name", type="string", example="店长", description="角色名称"),
     *                          @SWG\Property( property="rule_ids", type="array",
     *                              @SWG\Items( type="string", example="1", description="导购员角色类型"),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getSelfInfo(Request $request)
    {
        $authInfo = $this->auth->user();
        $salespersonService = new SalespersonService();
        $filter = [
            'salesperson_id' => $authInfo['salesperson_id'],
            'company_id' => $authInfo['company_id']
        ];
        $info = $salespersonService->getSalespersonDetail($filter);
        $info['role_info'] = null;
        if ($info['role']) {
            $filter = [
                'salesman_role_id' => $info['role'],
                'company_id' => $authInfo['company_id'],
            ];
            $distributorSalesmanRoleService = new DistributorSalesmanRoleService();

            $info['role_info'] = $distributorSalesmanRoleService->getInfo($filter);
        }
        return $this->response->array($info);
    }

    /**
    * @SWG\Post(
    *     path="/admin/wxapp/shop/signin",
    *     summary="导购端签到确认接口",
    *     tags={"导购"},
    *     description="description",
    *     operationId="signin",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="token", in="query", description="token", required=true, type="string"),
    *     @SWG\Parameter( name="status", in="query", description="状态:0,1", required=true, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="type", type="string", example="signin", description="类型"),
     *          @SWG\Property( property="token", type="string", example="O7MzqwPo0bXl", description="token"),
     *          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id"),
     *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
    * )
    */
    public function signin(Request $request)
    {
        $authInfo = $this->auth->user();
        $validator = app('validator')->make($request->all(), [
            'token' => 'required',
            'status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误.', $validator->errors());
        }

        $input = $request->input();
        $signService = new SignService();
        $result = $signService->accessTokenAuthorize($authInfo['company_id'], $input['token'], $authInfo['salesperson_id'], 'signin', $input['status']);
        return $this->response->array($result);
    }

    /**
    * @SWG\Post(
    *     path="/admin/wxapp/shop/checkSign",
    *     summary="导购端签到扫码回调接口",
    *     tags={"导购"},
    *     description="导购端签到扫码后调用",
    *     operationId="function",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="token", in="query", description="token", required=true, type="string"),
    *     @SWG\Parameter( name="type", in="query", description="signin or signout", required=true, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *                  @SWG\Property( property="distributor", type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id"),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id "),
     *                          @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="mobile", type="string", example="15988939258", description=" 手机号 "),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description=" 店铺地址 "),
     *                          @SWG\Property( property="name", type="string", example="视力康眼镜(中兴路店)", description=" 店铺名称"),
     *                          @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *                          @SWG\Property( property="logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/...", description="店铺logo"),
     *                          @SWG\Property( property="contract_phone", type="string", example="15988939258", description=" 联系电话"),
     *                          @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                          @SWG\Property( property="contact", type="string", example="林先生", description="联系人名称 "),
     *                          @SWG\Property( property="is_valid", type="string", example="true", description="店铺是否有效 "),
     *                          @SWG\Property( property="lng", type="string", example="117.890888", description="地图纬度 "),
     *                          @SWG\Property( property="lat", type="string", example="33.144662", description="地图经度 "),
     *                          @SWG\Property( property="child_count", type="string", example="0", description=" 店铺数量"),
     *                          @SWG\Property( property="is_default", type="string", example="0", description=" 是否是默认门店"),
     *                          @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *                          @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="340000", description="区域编码"),
     *                          ),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="安徽省", description="区域"),
     *                          ),
     *                          @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                          @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                          @SWG\Property( property="province", type="string", example="安徽省", description="省 "),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="city", type="string", example="蚌埠", description="市 "),
     *                          @SWG\Property( property="area", type="string", example="五河县", description="区 "),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description=" 营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="created", type="string", example="1581244268", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1611910310", description=" 修改时间"),
     *                          @SWG\Property( property="shop_code", type="string", example="null", description="店铺号"),
     *                          @SWG\Property( property="wechat_work_department_id", type="string", example="1", description="企业微信的部门ID"),
     *                          @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                          @SWG\Property( property="regionauth_id", type="string", example="1", description=" 区域id"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description=" 是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="rate", type="string", example="", description="货币汇率(与人民币) "),
     *                          @SWG\Property( property="store_address", type="string", example="安徽省蚌埠五河县中兴路实验小学楼下", description="门店地址"),
     *                          @SWG\Property( property="store_name", type="string", example="视力康眼镜(中兴路店)", description=" 门店名称"),
     *                          @SWG\Property( property="phone", type="string", example="15988939258", description="电话"),
     *                  ),
     *          ),
     *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
    * )
    */

    //导购端签到扫码后调用
    public function checkSign(Request $request)
    {
        $authInfo = $this->auth->user();
        $validator = app('validator')->make($request->all(), [
            'token' => 'required',
            'type' => 'required|in:signin,signout',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误.', $validator->errors());
        }
        $inputData = $request->input();
        $signService = new SignService();
        $result = $signService->accessTokenSweep($inputData['token'], $authInfo['salesperson_id'], $inputData['type']);
        return $this->response->array($result);
    }

    /**
    * @SWG\Post(
    *     path="/admin/wxapp/shop/signout",
    *     summary="导购端签退确认接口",
    *     tags={"导购"},
    *     description="description",
    *     operationId="signin",
    *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
    *     @SWG\Parameter( name="token", in="query", description="token", required=true, type="string"),
    *     @SWG\Parameter( name="status", in="query", description="状态:0,1", required=true, type="string"),
    *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="type", type="string", example="signin", description="类型"),
     *          @SWG\Property( property="token", type="string", example="O7MzqwPo0bXl", description="token"),
     *          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id"),
     *     )),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
    * )
    */
    public function signout(Request $request)
    {
        $authInfo = $this->auth->user();
        $validator = app('validator')->make($request->all(), [
            'token' => 'required',
            'status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误.', $validator->errors());
        }

        $input = $request->input();
        $signService = new SignService();
        $result = $signService->accessTokenAuthorize($authInfo['company_id'], $input['token'], $authInfo['salesperson_id'], 'signout', $input['status']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/bydistributor/salespersonQrcode/{company_id}",
     *     summary="公众号获取导购员二维码",
     *     tags={"导购"},
     *     description="description",
     *     operationId="getSalespersonContactQrCodeByDistributor",
     *     @SWG\Parameter( name="company_id", in="path", description="公司ID", required=true, type="integer"),
     *     @SWG\Parameter( name="lng", in="query", description="经度", required=false, type="string"),
     *     @SWG\Parameter( name="lat", in="query", description="纬度", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesperson_id", type="string", example="110", description="导购员id "),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="门店id "),
     *                  @SWG\Property( property="shop_name", type="string", example="null", description="门店名称 "),
     *                  @SWG\Property( property="name", type="string", example="彭张维", description=" 姓名 "),
     *                  @SWG\Property( property="mobile", type="string", example="13564359496", description="手机号"),
     *                  @SWG\Property( property="salesperson_type", type="string", example="shopping_guide", description="人员类型 admin: 管理员; verification_clerk:核销员; shopping_guide:导购员"),
     *                  @SWG\Property( property="created_time", type="string", example="1610614178", description="创建时间"),
     *                  @SWG\Property( property="user_id", type="string", example="0", description="用户id "),
     *                  @SWG\Property( property="child_count", type="string", example="0", description=" 导购员引入的会员数"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="number", type="string", example="", description=" 导购员编号"),
     *                  @SWG\Property( property="friend_count", type="string", example="0", description="导购员会员好友数"),
     *                  @SWG\Property( property="work_userid", type="string", example="15897657274304117", description="企业微信userid "),
     *                  @SWG\Property( property="avatar", type="string", example="https://wework.qpic.cn/bizmail/...", description=" 企业微信头像"),
     *                  @SWG\Property( property="work_configid", type="string", example="44e98e85742e40896ef3e76c4a356c52", description="企业微信userid"),
     *                  @SWG\Property( property="work_qrcode_configid", type="string", example="c900a250bc701d75072d79b0547acecd", description="企业微信userid"),
     *                  @SWG\Property( property="created", type="string", example="1610614178", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1611917787", description=" | 修改时间"),
     *                  @SWG\Property( property="role", type="string", example="6", description="导购权限集合 "),
     *                  @SWG\Property( property="salesman_name", type="string", example="彭张维", description="导购姓名"),
     *                  @SWG\Property( property="address", type="string", example="宜山路700号(近桂林路)", description="店铺地址"),
     *                  @SWG\Property( property="store_name", type="string", example="【店铺】普天信息产业园测试1", description="店铺名称 "),
     *                  @SWG\Property( property="qr_code", type="string", example="https://wework.qpic.cn/wwpic/...", description="二维码地址"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSalespersonContactQrCodeByDistributor($company_id, Request $request)
    {
        $filter['company_id'] = $company_id;
        $filter['distributor_self'] = 0;

        //验证参数todo
        $validator = app('validator')->make($request->all(), [
            'lng' => 'sometimes|numeric|between:-180.0,180.0',
            'lat' => 'sometimes|numeric|between:-90.0,90.0',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('经纬度范围错误.', $validator->errors());
        }

        if ($lng = $request->input('lng')) {
            $filter['lng'] = $lng;
        }
        if ($lat = $request->input('lat')) {
            $filter['lat'] = $lat;
        }

        $orderBy = ['is_default' => 'DESC'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        $distributorService = new DistributorService();
        $result = $distributorService->lists($filter, $orderBy, $pageSize, $page);
        //根据店铺获取关联的导购员
        $distributor_id = array_column($result['list'], 'distributor_id');
        $salespersonService = new SalespersonService();
        $salespersonList = $salespersonService->getSalespersonListByDistributor(['company_id' => $company_id, 'distributor_id' => $distributor_id]);

        $return = [];
        if ($salespersonList['list']) {
            $salesperson = array_filter(array_column($salespersonList['list'], null, 'work_qrcode_configid'));
            $configid = array_values(array_filter(array_column($salespersonList['list'], 'work_qrcode_configid')));
            $work_qrcode_configid = $configid[mt_rand(0, count($configid) - 1)]; //随机取一个

            //获取导购详情
            $return = $salesperson[$work_qrcode_configid];
            //获取导购员ID
            $salesperson_id = $salesperson[$work_qrcode_configid]['salesperson_id'];
            //根据导购员获取绑定的店铺信息
            $rel_distributor = $salespersonService->getSalespersonRelShopdata([
                'company_id' => $company_id,
                'salesperson_id' => $salesperson_id,
                'store_type' => 'distributor'
            ], 1, 1);
            $return['address'] = $rel_distributor['list'][0]['address'] ?? '';
            $return['store_name'] = $rel_distributor['list'][0]['store_name'] ?? '';

            $config = app('wechat.work.wechat')->getConfig($company_id);
            $app = Factory::work($config)->contact_way;
            //更新
            $app->delete($work_qrcode_configid);

            $createParam = [
                'style' => 1,
                'skip_verify' => true,
                'user' => $return['work_userid'],
            ];
            $qrcodeConfigIdTemp = $app->create(1, 2, $createParam);
            $qrcodeConfigId = $qrcodeConfigIdTemp['config_id'] ?? null;

            $salespersonService->salesperson->updateSalespersonById($salesperson_id, ['work_qrcode_configid' => $qrcodeConfigId]);
            $result = $app->get($qrcodeConfigId);

            if ($result['errcode']) {
                throw new ResourceException('获取导购二维码失败');
            }
            $return['qr_code'] = $result['contact_way']['qr_code'] ?? '';
        }
        return $this->response->array($return);
    }
}
