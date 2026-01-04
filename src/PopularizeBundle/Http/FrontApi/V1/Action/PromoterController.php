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

namespace PopularizeBundle\Http\FrontApi\V1\Action;

use CompanysBundle\Services\Shops\WxShopsService;
use CompanysBundle\Services\ShopsService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PopularizeBundle\Services\PromoterService;
use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Services\BrokerageService;
use PopularizeBundle\Services\PromoterCountService;
use WechatBundle\Services\WeappService;
use PopularizeBundle\Services\SettingService;
use PopularizeBundle\Services\PromoterGoodsService;
use PopularizeBundle\Services\TaskBrokerageService;
use GoodsBundle\Services\ItemsService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use MembersBundle\Services\MemberService;

class PromoterController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/promoter",
     *     summary="会员成为推广员",
     *     tags={"分销推广"},
     *     description="会员成为推广员",
     *     operationId="changePromoter",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function changePromoter(Request $request)
    {
        $promoterService = new PromoterService();
        $authInfo = $request->get('auth');
        if (!isset($authInfo['user_id']) || !$authInfo['user_id']) {
            throw new ResourceException('还未授权，请授权手机号');
        }
        $promoterService->changePromoter($authInfo['company_id'], $authInfo['user_id']);
        return $this->response->array(['status' => 'true']);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/index",
     *     summary="推广员首页数据",
     *     tags={"分销推广"},
     *     description="推广员首页数据",
     *     operationId="indexCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="promoter_order_count", type="integer", example="100", description="推广员订单数量"),
     *                   @SWG\Property(property="promoter_grade_order_count", type="integer", example="100", description="推广员团队订单"),
     *                   @SWG\Property(property="itemTotalPrice", type="integer", example="100", description="营业额"),
     *                   @SWG\Property(property="cashWithdrawalRebate", type="integer", example="100", description="可提现金额"),
     *                   @SWG\Property(property="noCloseRebate", type="integer", example="100", description="未结算金额"),
     *                   @SWG\Property(property="rebateTotal", type="integer", example="100", description="推广费总金额"),
     *                   @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="100", description="冻结金额"),
     *                   @SWG\Property(property="taskBrokerageItemTotalFee", type="integer", example="100", description="任务商品总销售额"),
     *                   @SWG\Property(property="isbuy_promoter", type="integer", example="100", description="购买会员"),
     *                   @SWG\Property(property="notbuy_promoter", type="integer", example="100", description="未购买会员"),
     *                   @SWG\Property(property="pointTotal", type="integer", example="100", description="积分总数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function indexCount(Request $request)
    {
        $authInfo = $request->get('auth');

        $brokerageService = new BrokerageService();
        // 推广员订单
        $data['promoter_order_count'] = $brokerageService->count(['company_id' => $authInfo['company_id'], 'user_id' => $authInfo['user_id'], 'source' => 'order', 'price|gt' => 0]);
        // 推广员团队订单
        $data['promoter_grade_order_count'] = $brokerageService->count(['company_id' => $authInfo['company_id'], 'user_id' => $authInfo['user_id'], 'source' => 'order_team', 'price|gt' => 0]);

        $promoterCountService = new PromoterCountService();
        $countData = $promoterCountService->getPromoterCount($authInfo['company_id'], $authInfo['user_id']);
        // 营业额
        $data['itemTotalPrice'] = $countData['itemTotalPrice'];
        // 可提现金额
        $data['cashWithdrawalRebate'] = $countData['cashWithdrawalRebate'];
        // 未结算金额
        $data['noCloseRebate'] = $countData['noCloseRebate'];
        // 推广费总金额
        $data['rebateTotal'] = $countData['rebateTotal'];
        // 冻结金额
        $data['freezeCashWithdrawalRebate'] = $countData['freezeCashWithdrawalRebate'];
        // 推广积分
        $data['pointTotal'] = $countData['pointTotal'];

        // 任务商品总销售额
        $taskBrokerageService = new TaskBrokerageService();
        $data['taskBrokerageItemTotalFee'] = $taskBrokerageService->getTaskPromoterRebate($authInfo['company_id'], $authInfo['user_id']);

        // 小店营业额
        $data['taskBrokerageItemTotalPoint'] = 0;

        $promoterService = new PromoterService();
        // 购买会员
        $data['isbuy_promoter'] = $promoterService->relationChildrenCountByUserId($authInfo['user_id'], null, ['is_buy' => 1]);
        // 未购买会员
        $data['notbuy_promoter'] = $promoterService->relationChildrenCountByUserId($authInfo['user_id'], null, ['is_buy' => 0]);


        $isSalesmanPage = $request->input('isSalesmanPage', 0);

        if($isSalesmanPage){
            $distributor_id = $request->input('distributor_id', 0);
            $isSalesmanPage = $request->input('isSalesmanPage', 0);

            $salespersonService = new SalespersonService();
            $filter_salesman['company_id'] = $authInfo['company_id'];
            $filter_salesman['user_id']    = $authInfo['user_id'];

            $listdata = $salespersonService->getSalespersonList($filter_salesman, 1, 1000);
            $dIds = array_column($listdata['list'],'shop_id');

            //1 获取全部佣金总额 getSalesmanBrokerageCount
            $brokerageService = new BrokerageService();
            $filter_count['company_id']     = $authInfo['company_id'];

            if(isset($dIds) && $dIds) {
                $filter_count['dIds'] = $dIds;
            }
            if($distributor_id) {
                $filter_count['distributor_id'] = $distributor_id;
                unset($filter_count['dIds']);
            };

            $filter_count['user_id']        = $authInfo['user_id'];
            $countDataShop = $brokerageService->getSalesmanBrokerageCount($filter_count, 1 ,1000);
            $data['countDataShop'] = $countDataShop;
            $data['rebateTotal'] = $countDataShop['rebate_sum'];
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/info",
     *     summary="获取推广员基本信息",
     *     tags={"分销推广"},
     *     description="获取推广员基本信息",
     *     operationId="getPromoterInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="user_id", type="string", example="20376", description="会员ID"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *               @SWG\Property(property="grade_id", type="string", example="4", description="等级id"),
     *               @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
     *               @SWG\Property(property="user_card_code", type="string", example="2D348A58101B", description="会员卡号"),
     *               @SWG\Property(property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="公众号的appid"),
     *               @SWG\Property(property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="小程序的appid"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="监控页面id"),
     *               @SWG\Property(property="latest_source_id", type="string", example="0", description="最近来源id"),
     *               @SWG\Property(property="latest_monitor_id", type="string", example="0", description="最近监控页面id"),
     *               @SWG\Property(property="created", type="integer", example="1610443188", description=""),
     *               @SWG\Property(property="updated", type="string", example="1611041198", description=""),
     *               @SWG\Property(property="created_year", type="string", example="2021", description="创建年份"),
     *               @SWG\Property(property="created_month", type="string", example="1", description="创建月份"),
     *               @SWG\Property(property="created_day", type="string", example="12", description="创建日期"),
     *               @SWG\Property(property="offline_card_code", type="string", example="", description="线下会员卡号"),
     *               @SWG\Property(property="inviter_id", type="string", example="0", description="推荐人id"),
     *               @SWG\Property(property="source_from", type="string", example="default", description="来源类型 default默认"),
     *               @SWG\Property(property="password", type="string", example="$2y$10$CTxx4I7VwWe4bZzf2Z1wje8AtK3GAmfQFPkWqC8IinwYjmTqDTeZq", description="密码"),
     *               @SWG\Property(property="disabled", type="integer", example="0", description="是否有效"),
     *               @SWG\Property(property="use_point", type="string", example="0", description="是否可以使用积分"),
     *               @SWG\Property(property="remarks", type="string", example="", description="会员备注"),
     *               @SWG\Property(property="third_data", type="string", example="", description="第三方数据"),
     *               @SWG\Property(property="username", type="string", example="张三", description="昵称"),
     *               @SWG\Property(property="sex", type="string", example="0", description="性别"),
     *               @SWG\Property(property="created_date", type="string", example="2021-01-12 17:19:48", description="创建时间"),
     *               @SWG\Property(property="id", type="string", example="245", description="ID"),
     *               @SWG\Property(property="promoter_id", type="string", example="245", description=""),
     *               @SWG\Property(property="shop_name", type="string", example="", description="推广员自定义店铺名称"),
     *               @SWG\Property(property="alipay_name", type="string", example="", description="推广员提现的支付宝姓名"),
     *               @SWG\Property(property="shop_pic", type="string", example="", description="推广店铺封面"),
     *               @SWG\Property(property="brief", type="string", example="", description="推广店铺描述"),
     *               @SWG\Property(property="alipay_account", type="string", example="", description="推广员提现的支付宝账号"),
     *               @SWG\Property(property="pid", type="integer", example="0", description="上级会员ID"),
     *               @SWG\Property(property="shop_status", type="integer", example="2", description="开店状态 0 未开店 1已开店 2申请中 3禁用 4申请审核拒绝 "),
     *               @SWG\Property(property="reason", type="string", example="", description="审核拒绝原因"),
     *               @SWG\Property(property="pmobile", type="string", example="0", description="上级手机号"),
     *               @SWG\Property(property="grade_level", type="integer", example="1", description="推广员等级"),
     *               @SWG\Property(property="is_promoter", type="integer", example="1", description="是否为推广员"),
     *               @SWG\Property(property="is_buy", type="integer", example="0", description="是否有购买记录"),
     *               @SWG\Property(property="children_count", type="integer", example="0", description=""),
     *               @SWG\Property(property="bind_date", type="string", example="2021-01-12", description=""),
     *               @SWG\Property(property="promoter_grade_name", type="string", example="等级一", description=""),
     *               @SWG\Property(property="is_open_promoter_grade", type="string", example="false", description=""),
     *               @SWG\Property(property="nickname", type="string", example="曹帅", description=""),
     *               @SWG\Property(property="headimgurl", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJoC7iczcqvp72KScFPhsFFcRNFsOpibpiawiazhmCooJPmoNdOVqHefvib2ONlfUBBAo5WaRX2kibsU8Fg/132", description=""),
     *               @SWG\Property(property="selfInfo", type="string", example="", description=""),
     *               @SWG\Property(property="isOpenShop", type="string", example="true", description=""),
     *               @SWG\Property(property="isOpenPopularize", type="string", example="true", description=""),
     *               @SWG\Property(property="is_valid", type="string", example="", description=""),
     *               @SWG\Property( property="isOpenPromoterInformation", type="string", description="是否开启推广员信息"),
     *               @SWG\Property( property="shop_img", type="string", description="小店图片"),
     *               @SWG\Property( property="share_title", type="string", description="分享标题"),
     *               @SWG\Property( property="share_des", type="string", description="分享描述"),
     *               @SWG\Property( property="applets_share_img", type="string", description="小程序分享图片"),
     *               @SWG\Property( property="h5_share_img", type="string", description="h5/app/海报 分享图片"),
     *               @SWG\Property( property="qrcode_bg_img", type="string", description="二维码背景图片"),
     *               @SWG\Property( property="headquarters_logo", type="string", description="总部头像"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterInfo(Request $request)
    {
        $promoterService = new PromoterService();
        $authInfo = $request->get('auth');

        $selfId = $authInfo['user_id'];
        if ($request->input('user_id')) {
            $userId = $request->input('user_id');
        } else {
            $userId = $authInfo['user_id'];
        }

        if (!$userId) {
            $data = [];
            return $this->response->array($data);
        }

        $info = $promoterService->getPromoterInfo($authInfo['company_id'], $userId);
        if (!$info) {
            $data = [];
            return $this->response->array($data);
        }

        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);
        $isOpenShop = $config['isOpenShop'];
        $isOpenPopularize = $config['isOpenPopularize'];

        // 如果是传过来的参数，那么只能输出不敏感的数据
        $selfInfo = $promoterService->getPromoterInfo($authInfo['company_id'], $selfId);
        if ($request->input('user_id')) {
            $data = [
                'user_id' => $info['user_id'],
                'is_promoter' => $info['is_promoter'],
                'disabled' => $info['disabled'],
                'grade_level' => $info['grade_level'],
                'shop_name' => $info['shop_name'],
                'brief' => $info['brief'],
                'shop_pic' => $info['shop_pic'],
                'headimgurl' => $info['headimgurl'] ?? '',
                'username' => $info['username'] ?? '',
                'nickname' => $info['nickname'] ?? '',
                'mobile' => $info['mobile'] ?? '',
            ];
            if ($selfInfo) {
                $data['selfInfo']['user_id'] = $selfInfo['user_id'];
                $data['selfInfo']['is_valid'] = true;
                if ($isOpenShop == 'false' || $isOpenPopularize == 'fasle' || !$data['is_promoter'] || $data['disabled'] || 1 != $selfInfo['shop_status']) {
                    $data['selfInfo']['is_valid'] = false;
                }
                if ($selfInfo['parent_info'] ?? 0) {
                    $data['parentInfo']['user_id'] = $selfInfo['parent_info']['user_id'];
                    $data['parentInfo']['is_valid'] = true;
                    if ($isOpenShop == 'false' || $isOpenPopularize == 'fasle' || !$data['is_promoter'] || $data['disabled'] || 1 != $selfInfo['parent_info']['shop_status']) {
                        $data['parentInfo']['is_valid'] = false;
                    }
                }
            }
        } else {
            $data = $info;
            $data['selfInfo'] = null;
            if ($authInfo['nickname'] ?? '') {
                $data['nickname'] = $authInfo['nickname'];
            }
            if ($authInfo['mobile'] ?? '') {
                $data['mobile'] = $authInfo['mobile'];
            }
        }
        if ($isOpenShop == 'true') {
            $data['isOpenShop'] = true;
        } else {
            $data['isOpenShop'] = false;
        }
        if ($isOpenPopularize == 'true') {
            $data['isOpenPopularize'] = true;
        } else {
            $data['isOpenPopularize'] = false;
        }
        $shopsService = new ShopsService(new WxShopsService());
        $brand = $shopsService->getWxShopsSetting($authInfo['company_id']);
        if ($brand) {
            $data['headquarters_logo'] = $brand['logo'];
        }
        if (isset($config['isOpenPromoterInformation'])) {
            if ($config['isOpenPromoterInformation'] == 'true') {
                $data['isOpenPromoterInformation'] = true;
            } else {
                $data['isOpenPromoterInformation'] = false;
            }
        }
        if (isset($config['shop_img'])) {
            $data['shop_img'] = $config['shop_img'];
        }
        if (isset($config['banner_img'])) {
            $data['banner_img'] = $config['banner_img'];
        }
        if (isset($config['share_title'])) {
            $data['share_title'] = $config['share_title'];
        }
        if (isset($config['share_des'])) {
            $data['share_des'] = $config['share_des'];
        }
        if (isset($config['applets_share_img'])) {
            $data['applets_share_img'] = $config['applets_share_img'];
        }
        if (isset($config['h5_share_img'])) {
            $data['h5_share_img'] = $config['h5_share_img'];
        }

        $data['qrcode_bg_img'] = $config['qrcode_bg_img'] ?? '';

        if ($isOpenShop == 'false' || $isOpenPopularize == 'fasle' || !$data['is_promoter'] || $data['disabled'] || 1 != $info['shop_status']) {
            $data['is_valid'] = false;
        } else {
            $data['is_valid'] = true;
        }
        // 数云模式，分销员推广二维码是否显示 仅内部推广、A级推广员、身份已开启
        if (config('common.oem-shuyun')) {
            $data['promoter_qrcode_bg_img'] = $config['promoter_qrcode_bg_img'] ?? '';// 分销员推广二维码背景图（仅内部可推广时使用）
            $data['is_show_promoter_qrcode'] = $isFirstPromoter = false;
            if (!empty($selfInfo) && $selfInfo['identity_id'] > 0 && $selfInfo['is_subordinates'] == 1) {
                $isFirstPromoter = true;
            }
            if ($isOpenPopularize == 'fasle' || !$data['is_promoter'] || $data['disabled']) {
                $promoter_is_valid = false;
            } else {
                $promoter_is_valid = true;
            }
            if ($promoter_is_valid && $config['change_promoter']['type'] == 'internal' && $isFirstPromoter && $config['internalOpenIdentity'] == 'true') {
                $data['is_show_promoter_qrcode'] = true;
                // 我的下级推广员数量
                $data['child_promoter_num'] = $promoterService->getPromoterchildrenCount([
                    'company_id' => $authInfo['company_id'],
                    'user_id' => $selfId,
                    'is_promoter' => 1,
                ], 1);
            }
            $data['show_child_promoter'] = ($data['child_promoter_num'] ?? 0) > 0 ? true : false;
        }
        // 数据脱敏
        $data['mobile'] = isset($data['mobile']) && $data['mobile'] ? data_masking('mobile', $data['mobile']) : '';
        $data['username'] = isset($data['username']) && $data['username'] ? data_masking('truename', $data['username']) : '';
        $data['pmobile'] = isset($data['pmobile']) && $data['pmobile'] ? data_masking('mobile', $data['pmobile']) : '';
        $data['nickname'] = isset($data['nickname']) && $data['nickname'] ? data_masking('truename', $data['nickname']) : '';
        $data['parent_info']['mobile'] = isset($data['parent_info']['mobile']) && $data['parent_info']['mobile'] ? data_masking('mobile', $data['parent_info']['mobile']) : '';
        $data['parent_info']['region_mobile'] = isset($data['parent_info']['region_mobile']) && $data['parent_info']['region_mobile'] ? data_masking('mobile', $data['parent_info']['region_mobile']) : '';
        $data['parent_info']['username'] = isset($data['parent_info']['username']) && $data['parent_info']['username'] ? data_masking('truename', $data['parent_info']['username']) : '';
        $data['parent_info']['nickname'] = isset($data['parent_info']['nickname']) && $data['parent_info']['nickname'] ? data_masking('truename', $data['parent_info']['nickname']) : '';

        $data['type_promoter'] = '商家业务员';

        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/promoter",
     *     summary="更新推广员信息",
     *     tags={"分销推广"},
     *     description="更新推广员信息",
     *     operationId="updatePromoterInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="alipay_name", in="query", description="提现支付宝姓名", required=false, type="string"),
     *     @SWG\Parameter( name="alipay_account", in="query", description="提现支付宝账号", required=false, type="string"),
     *     @SWG\Parameter( name="shop_name", in="query", description="推广员自定义店铺名称", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function updatePromoterInfo(Request $request)
    {
        $promoterService = new PromoterService();
        $authInfo = $request->get('auth');

        $data = [];
        if ($request->input('alipay_name', null)) {
            $data['alipay_name'] = trim($request->input('alipay_name'));
        }

        if ($request->input('brief', null)) {
            $data['brief'] = trim($request->input('brief'));
        }

        if ($request->input('shop_pic', null)) {
            $data['shop_pic'] = trim($request->input('shop_pic'));
        }

        if ($request->input('alipay_account', null)) {
            $data['alipay_account'] = trim($request->input('alipay_account'));
        }

        if ($request->input('shop_name', null)) {
            $data['shop_name'] = trim($request->input('shop_name'));
        }

        if ($request->input('shop_status', null)) {
            $promoterService->updateShopStatus($authInfo['company_id'], $authInfo['user_id'], 2);
            $result['status'] = true;
        }

        if ($data) {
            $result = $promoterService->updateByUserId($authInfo['user_id'], $data);
        }
        return $this->response->array($result ?? []);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/children",
     *     summary="获取推广员2级下线",
     *     tags={"分销推广"},
     *     description="获取推广员2级下线",
     *     operationId="getPromoterchildrenList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="header", description="分页", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="header", description="分页页码", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterchildrenList(Request $request)
    {
        $promoterService = new PromoterService();
        $authInfo = $request->get('auth');
        $isBuy = $request->input('isBuy', 1);

        $pageSize = $request->input('pageSize', 20);
        if ($request->input('buy_type') == 'buy') {
            $buyPage = $request->input('page', 1);
            $notBuyPage = 1;
        } elseif ($request->input('buy_type') == 'not_buy') {
            $notBuyPage = $request->input('page', 1);
            $buyPage = 1;
        } else {
            $buyPage = 1;
            $notBuyPage = 1;
        }


        if ($request->input('userName',false) ) {
            $memberService = new MemberService();
            // $memberService->getMemberInfo();
            // $memberService->getInfoByMobile();

            // $memberService->getPromoterInfo

        }

        if ($request->input('mobile',false) ) {

        }
        if ($request->input('shopName',false) ) {

        }
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        // 数云模式，(只有会员C)
        if (config('common.oem-shuyun')) {
            $filter['is_promoter'] = 0;
        }
        $filter['disabled'] = 0;
        $filter['is_buy'] = 1;
        $data['buy'] = $promoterService->getPromoterchildrenList($filter, null, $buyPage, $pageSize, 1);
        $data['buy']['list'] = $this->handlerData($data['buy']['list']);

        $filter['is_buy'] = 0;
        $data['not_buy'] = $promoterService->getPromoterchildrenList($filter, null, $notBuyPage, $pageSize, 1);
        $data['not_buy']['list'] = $this->handlerData($data['not_buy']['list']);

        if($isBuy){
            $data['all'] = array_merge($data['buy'],$data['not_buy']);
            return $this->response->array($data);
        }

        if ($request->input('buy_type') == 'buy') {
            return $this->response->array($data['buy']);
        } elseif ($request->input('buy_type') == 'not_buy') {
            return $this->response->array($data['not_buy']);
        } else {
            return $this->response->array($data);
        }
    }


    private function handlerData($list)
    {
        foreach ($list as $key => $row) {
            if (isset($row['username']) && $row['username']) {
                $list[$key]['username'] = data_masking('truename', $row['username']);
            }
            if (isset($row['nickname']) && $row['nickname']) {
                $list[$key]['nickname'] = data_masking('truename', $row['nickname']);
            }
            if (isset($row['region_mobile']) && $row['region_mobile']) {
                $list[$key]['region_mobile'] = data_masking('mobile', $row['region_mobile']);
            }
            if (isset($row['pmobile']) && $row['pmobile']) {
                $list[$key]['pmobile'] = data_masking('mobile', $row['pmobile']);
            }
            if (isset($row['mobile']) && $row['mobile']) {
                $list[$key]['mobile'] = data_masking('mobile', $row['mobile']);
            }
        }
        return $list;
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/qrcode",
     *     summary="获取推广员小程序码",
     *     tags={"分销推广"},
     *     description="获取推广员小程序码",
     *     operationId="getPromoterQrcode",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="path", in="query", description="需要跳转的地址，默认为首页", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="qrcode", type="string", description="小程序码"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterQrcode(Request $request)
    {
        $authInfo = $request->get('auth');
        if (isset($authInfo['wxapp_appid'])) {
            $weappService = new WeappService($authInfo['wxapp_appid'], $authInfo['company_id']);
            $scene = 'uid=' . $authInfo['user_id'];
            $page = $request->input('path', 'pages/index');
            if (substr($page, 0, 1) == '/') {
                $page = substr($page, 1);
            }
            app('log')->debug('推荐关系跟踪 scene：'.$scene);
            $qrcode = $weappService->createWxaCodeUnlimit($scene, $page);
            $base64 = 'data:image/jpg;base64,' . base64_encode($qrcode);
            return $this->response->array(['qrcode' => $base64]);
        }
        return $this->response->array(['qrcode' => '']);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/qrcode.png",
     *     summary="获取推广员小程序码",
     *     tags={"分销推广"},
     *     description="获取推广员小程序码",
     *     operationId="getPromoterQrcode",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="path", in="query", description="需要跳转的地址，默认为首页", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="qrcode", type="string", description="小程序码"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterQrcodePng(Request $request)
    {
        $companyId = $request->input('company_id');
        $wxappAppid = $request->input('appid');
        if (!$wxappAppid) {
            $weappService = new WeappService();
            $wxappAppid = $weappService->getWxappidByTemplateName($companyId, 'yykweishop');
        }
        $uid = $request->input('uid');
        $userId = $request->input('user_id');
        $dtid    = $request->input('dtid',false);
        $qr = $request->input('qr',false);

        $weappService = new WeappService($wxappAppid, $companyId);
        $scene = 'uid=' . ($uid ?: $userId);


        $page = $request->input('path', 'pages/index');

        if($dtid) {
            $page = 'subpages/store/index';
            $scene .= '&id=' . $dtid;
        }
        if($qr) {
            $page = 'subpages/store/index';
            $scene .= '&qr=' . $qr;
        }
        if ($prescriptionOrderId = $request->input('prescription_order_id', '')) {
            $random = str_random(4);
            $key = 'dianwu_prescription_order_random:' . $prescriptionOrderId . $random;
            app('redis')->set($key, 1);
            app('redis')->expire($key, 1800);
            $scene = 'oi=' . $prescriptionOrderId . '&r=' . $random . '&t=1';
        }

        $page_input = $request->input('page', false);
        $order_id_input = $request->input('order_id', false);
        if($page_input && $order_id_input){
            $page = $page_input;
            $scene = 'oi=' . $order_id_input;

        }

        if (substr($page, 0, 1) == '/') {
            $page = substr($page, 1);
        }


        // app('log')->debug('推荐关系跟踪 scene：'.$scene);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-page:". json_encode($page));
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-scene:". json_encode($scene));

        $qrcode = $weappService->createWxaCodeUnlimit($scene, $page);
        return Response($qrcode)->header('Content-type', 'image/png');
    }

    /**
     * 获取推广邀请码（仅内部推广时，A级邀请会员注册后，成为B级推广员的小程序二维码）
     */
    public function getPromoterNewQrcodePng(Request $request)
    {
        $companyId = $request->input('company_id');
        $wxappAppid = $request->input('appid');
        if (!$wxappAppid) {
            $weappService = new WeappService();
            $wxappAppid = $weappService->getWxappidByTemplateName($companyId, 'yykweishop');
        }
        // 推广员会员ID
        $promoter_user_id = $request->input('puid');

        $weappService = new WeappService($wxappAppid, $companyId);
        $scene = 'puid=' . $promoter_user_id;
        $page = $request->input('path', 'pages/index');
        if (substr($page, 0, 1) == '/') {
            $page = substr($page, 1);
        }
        app('log')->debug('推广邀请码，推荐关系跟踪 scene：'.$scene);
        $qrcode = $weappService->createWxaCodeUnlimit($scene, $page);
        return Response($qrcode)->header('Content-type', 'image/png');
    }

    public function logPromoterQrcode(Request $request)
    {
        $inputData = $request->input();
        app('log')->debug('推荐关系跟踪 inputData：'.var_export($inputData, 1));
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/relgoods",
     *     summary="获取推广员关联的商品",
     *     tags={"分销推广"},
     *     description="获取推广员关联的商品",
     *     operationId="getPromoterGoods",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页记录显示条数", required=false, type="integer"),
     *     @SWG\Parameter( name="goods_id", in="query", description="指定关联的goods_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="goods_id", type="string", description="商品id集合"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterGoods(Request $request)
    {
        $promoterGoodsService = new PromoterGoodsService();

        $authInfo = $request->get('auth');

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        // 如果是传入的user_id则表示是用户访问推广员店铺返回对应的id
        $filter['user_id'] = $request->input('user_id', 0) ? $request->input('user_id') : $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];
        if ($request->input('goods_id')) {
            $filter['goods_id'] = $request->input('goods_id');
        }
        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);
        $filter['is_all_goods'] = $config['goods'];
        $list = $promoterGoodsService->lists($filter, '*', $page, $pageSize);
        $response = array_values(array_unique(array_column($list['list'], 'goods_id')));

        return $this->response->array(['goods_id' => $response]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/promoter/relgoods",
     *     summary="关联推广员关联的商品",
     *     tags={"分销推广"},
     *     description="关联推广员关联的商品",
     *     operationId="relPromoterGoods",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="goods_id", in="query", description="指定的关联的goods_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng", example="true"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function relPromoterGoods(Request $request)
    {
        $promoterGoodsService = new PromoterGoodsService();

        $authInfo = $request->get('auth');
        if (!isset($authInfo['user_id']) || !$authInfo['user_id']) {
            throw new ResourceException('还未授权，请授权手机号');
        }

        $filter['user_id'] = $authInfo['user_id'];
        $filter['goods_id'] = $request->input('goods_id');

        $info = $promoterGoodsService->getInfo(['user_id' => $authInfo['user_id'], 'company_id' => $authInfo['company_id'], 'goods_id' => $filter['goods_id']]);

        if (!$info && $request->input('goods_id')) {
            $data = [
                'goods_id' => $request->input('goods_id'),
                'company_id' => $authInfo['company_id'],
                'user_id' => $authInfo['user_id'],
                'created' => time(),
            ];
            $promoterGoodsService->create($data);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/promoter/relgoods",
     *     summary="删除推广员关联的商品",
     *     tags={"分销推广"},
     *     description="删除推广员关联的商品",
     *     operationId="deleteRelPromoterGoods",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="goods_id", in="query", description="关联的goods_id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="stirng", example="true"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function deleteRelPromoterGoods(Request $request)
    {
        $promoterGoodsService = new PromoterGoodsService();

        $authInfo = $request->get('auth');
        if (!isset($authInfo['user_id']) || !$authInfo['user_id']) {
            throw new ResourceException('还未授权，请授权手机号');
        }

        $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $authInfo['company_id'];
        $filter['goods_id'] = $request->input('goods_id');

        $promoterGoodsService->deleteBy($filter);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/banner",
     *     summary="获取店招默认封面图",
     *     tags={"分销推广"},
     *     description="获取店招默认封面图",
     *     operationId="getPromoterBanner",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="formData", description="图片文件只支持jpg/png格式,必须2MB以下", required=true, type="file"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="banner_img", type="stirng", example="获取店招默认封面图"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterBanner(Request $request)
    {
        $authInfo = $request->get('auth');

        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);

        $data['banner_img'] = $config['banner_img'] ?? '';

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/custompage",
     *     summary="获取设置虚拟店首页模板",
     *     tags={"分销推广"},
     *     description="获取设置虚拟店首页模板",
     *     operationId="getPromoterCustompage",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="custompage_template_id", type="stirng", example="虚拟店首页模板id"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getPromoterCustompage(Request $request)
    {
        $authInfo = $request->get('auth');

        $settingService = new SettingService();
        $config = $settingService->getConfig($authInfo['company_id']);

        $data['custompage_template_id'] = $config['custompage_template_id'] ?? '';

        return $this->response->array($data);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/getSalesmanCount",
     *     summary="推广员首页数据",
     *     tags={"分销推广"},
     *     description="推广员首页数据",
     *     operationId="indexCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="promoter_order_count", type="integer", example="100", description="推广员订单数量"),
     *                   @SWG\Property(property="promoter_grade_order_count", type="integer", example="100", description="推广员团队订单"),
     *                   @SWG\Property(property="itemTotalPrice", type="integer", example="100", description="营业额"),
     *                   @SWG\Property(property="cashWithdrawalRebate", type="integer", example="100", description="可提现金额"),
     *                   @SWG\Property(property="noCloseRebate", type="integer", example="100", description="未结算金额"),
     *                   @SWG\Property(property="rebateTotal", type="integer", example="100", description="推广费总金额"),
     *                   @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="100", description="冻结金额"),
     *                   @SWG\Property(property="taskBrokerageItemTotalFee", type="integer", example="100", description="任务商品总销售额"),
     *                   @SWG\Property(property="isbuy_promoter", type="integer", example="100", description="购买会员"),
     *                   @SWG\Property(property="notbuy_promoter", type="integer", example="100", description="未购买会员"),
     *                   @SWG\Property(property="pointTotal", type="integer", example="100", description="积分总数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getSalesmanCount(Request $request)
    {
        $authInfo = $request->get('auth');
        $inputData = $request->input();

        $brokerageService = new BrokerageService();

        $promoterService = new PromoterService();

        $res = $promoterService->getSalesmanCount($authInfo,$inputData);

        return $this->response->array($res);

        // // 推广员订单
        // $data['promoter_order_count'] = $brokerageService->count(['company_id' => $authInfo['company_id'], 'user_id' => $authInfo['user_id'], 'source' => 'order', 'price|gt' => 0]);
        // // 推广员团队订单
        // $data['promoter_grade_order_count'] = $brokerageService->count(['company_id' => $authInfo['company_id'], 'user_id' => $authInfo['user_id'], 'source' => 'order_team', 'price|gt' => 0]);

        // $promoterCountService = new PromoterCountService();
        // $countData = $promoterCountService->getPromoterCount($authInfo['company_id'], $authInfo['user_id']);
        // // 营业额
        // $data['itemTotalPrice'] = $countData['itemTotalPrice'];
        // // 可提现金额
        // $data['cashWithdrawalRebate'] = $countData['cashWithdrawalRebate'];
        // // 未结算金额
        // $data['noCloseRebate'] = $countData['noCloseRebate'];
        // // 推广费总金额
        // $data['rebateTotal'] = $countData['rebateTotal'];
        // // 冻结金额
        // $data['freezeCashWithdrawalRebate'] = $countData['freezeCashWithdrawalRebate'];
        // // 推广积分
        // $data['pointTotal'] = $countData['pointTotal'];

        // // 任务商品总销售额
        // $taskBrokerageService = new TaskBrokerageService();
        // $data['taskBrokerageItemTotalFee'] = $taskBrokerageService->getTaskPromoterRebate($authInfo['company_id'], $authInfo['user_id']);

        // // 小店营业额
        // $data['taskBrokerageItemTotalPoint'] = 0;

        // $promoterService = new PromoterService();
        // // 购买会员
        // $data['isbuy_promoter'] = $promoterService->relationChildrenCountByUserId($authInfo['user_id'], null, ['is_buy' => 1]);
        // // 未购买会员
        // $data['notbuy_promoter'] = $promoterService->relationChildrenCountByUserId($authInfo['user_id'], null, ['is_buy' => 0]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/getSalesmanStoreitems",
     *     summary="推广员首页数据",
     *     tags={"分销推广"},
     *     description="推广员首页数据",
     *     operationId="indexCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="promoter_order_count", type="integer", example="100", description="推广员订单数量"),
     *                   @SWG\Property(property="promoter_grade_order_count", type="integer", example="100", description="推广员团队订单"),
     *                   @SWG\Property(property="itemTotalPrice", type="integer", example="100", description="营业额"),
     *                   @SWG\Property(property="cashWithdrawalRebate", type="integer", example="100", description="可提现金额"),
     *                   @SWG\Property(property="noCloseRebate", type="integer", example="100", description="未结算金额"),
     *                   @SWG\Property(property="rebateTotal", type="integer", example="100", description="推广费总金额"),
     *                   @SWG\Property(property="freezeCashWithdrawalRebate", type="integer", example="100", description="冻结金额"),
     *                   @SWG\Property(property="taskBrokerageItemTotalFee", type="integer", example="100", description="任务商品总销售额"),
     *                   @SWG\Property(property="isbuy_promoter", type="integer", example="100", description="购买会员"),
     *                   @SWG\Property(property="notbuy_promoter", type="integer", example="100", description="未购买会员"),
     *                   @SWG\Property(property="pointTotal", type="integer", example="100", description="积分总数"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getSalesmanStoreitems(Request $request)
    {
        $authInfo = $request->get('auth');
        $inputData = $request->input();

        // $promoterService = new PromoterService();
        // $res = $promoterService->getSalesmanCount($authInfo);

        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 50);
        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id']    = $authInfo['user_id'];

        $listdata = $salespersonService->getSalespersonList($filter, $page, $pageSize);
        $dIds = array_column($listdata['list'],'shop_id');
        $salesperson = array_column($listdata['list'],null,'shop_id');
        if(!$dIds){
            return $this->response->array(array());
        }
        $params['approve_status'] = ['onsale', 'only_show'];
        $params['company_id'] = $authInfo['company_id'];
        $params['rebate'] = 1;
        $params['distributor_id'] = $dIds;

        $params['is_default'] = $inputData['is_default'] ?? 1;
        if($inputData['distributor_id'] && in_array($inputData['distributor_id'], $dIds)){
            $params['distributor_id'] = $inputData['distributor_id'];
        }

        // if (isset($inputData['is_promoter']) && $inputData['is_promoter']) {
        //     // $params['distributor_id'] = 0;
        //         $params['rebate'] = 1;
        //         $params['distributor_id|in'] = $dIds;
        // }

        $itemsService = new ItemsService();

        $orderBy['item_id'] = 'desc';

        // $result = $itemsService->getItemListData($params, $page, $pageSize, $orderBy, false);
        $result = $itemsService->getSalesmanStoreitems($params, $page, $pageSize, $orderBy, false);

        $distributorService = new DistributorService();
        $listShop = $distributorService->lists(['distributor_id' => $dIds], [], -1, 0);
        $result['listShop'] = $listShop;

        return $this->response->array($result);
    }




    /**
     * @SWG\Get(
     *     path="/wxapp/promoter/getSalesmanStatic",
     *     summary="推广员业绩数据",
     *     tags={"分销推广"},
     *     description="推广员业绩数据",
     *     operationId="indexCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="date_type", type="integer", example="100", description="推广员订单数量"),
     *                   @SWG\Property(property="date", type="integer", example="100", description="推广员团队订单"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PopularizeErrorRespones") ) )
     * )
     */
    public function getSalesmanStatic(Request $request)
    {
        $authInfo = $request->get('auth');
        $inputData   =  $request->input();
        $brokerageService = new BrokerageService();

        $promoterService = new PromoterService();

        $res = $promoterService->getSalesmanStatic($authInfo, $inputData);
        return $this->response->array($res);
    }


    /**
     * 获取顶级推广员下的二级推广员
     */
    public function getChildrenpromoterList(Request $request)
    {
        $promoterService = new PromoterService();
        $authInfo = $request->get('auth');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['is_promoter'] = 1;
        $filter['disabled'] = 0;
        // 根据推广员手机号，进行筛选
        if ($promoter_mobile = $request->input('promoter_mobile', false)) {
            $memberService = new MemberService();
            $promoter_user_id = $memberService->getUserIdByMobile($promoter_mobile, $authInfo['company_id']);
            $filter['promoter_user_id'] = !empty($promoter_user_id) ? $promoter_user_id : '-1';
        }
        $data = $promoterService->getPromoterchildrenList($filter, 1, $page, $pageSize, 1);
        $data['list'] = $this->handlerData($data['list']);
        if ($request->input('buy_type') == 'buy') {
            return $this->response->array($data['buy']);
        } elseif ($request->input('buy_type') == 'not_buy') {
            return $this->response->array($data['not_buy']);
        } else {
            return $this->response->array($data);
        }
    }
}
