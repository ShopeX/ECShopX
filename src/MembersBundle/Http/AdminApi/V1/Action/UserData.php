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

namespace MembersBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;

use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use MembersBundle\Services\MemberTagsService;
use MembersBundle\Services\MemberRelGroupService;
use KaquanBundle\Services\VipGradeOrderService;
use KaquanBundle\Services\UserDiscountService;
use PointBundle\Services\PointMemberService;
use DepositBundle\Services\DepositTrade;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;
use WorkWechatBundle\Services\WorkWechatRelService;
use MembersBundle\Services\MemberBrowseHistoryService;

class UserData extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/getUserData",
     *     summary="根据手机号获取用户信息",
     *     tags={"会员"},
     *     description="根据手机号获取用户信息",
     *     operationId="getUserData",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号 与 会员码二选一必填", required=true, type="string"),
     *     @SWG\Parameter(name="user_card_code", in="query", description="会员码 与 手机号 二选一必填", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="grade_id", type="string", example="4", description="等级ID"),
     *                  @SWG\Property( property="mobile", type="string", example="18321148690", description="手机号"),
     *                  @SWG\Property( property="user_card_code", type="string", example="324A50B01181", description="会员卡号"),
     *                  @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     *                  @SWG\Property( property="username", type="string", example="heihei", description="姓名"),
     *                  @SWG\Property( property="sex", type="string", example="2", description="性别。0 未知 1 男 2 女"),
     *                  @SWG\Property( property="avatar", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/28Qz0boz9fjJYJiapHjxu5nNcBKDZMcNlrpctITqfTawwnsw8Wu9Af4k6DzIXlSv01m3nvxV48Nic9JsroJ9NuGA/132", description="头像"),
     *                  @SWG\Property( property="nickname", type="string", example="倩儿", description="昵称"),
     *                  @SWG\Property( property="headimgurl", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/28Qz0boz9fjJYJiapHjxu5nNcBKDZMcNlrpctITqfTawwnsw8Wu9Af4k6DzIXlSv01m3nvxV48Nic9JsroJ9NuGA/132", description="头像url"),
     *                  @SWG\Property( property="tags", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="tag_id", type="string", example="209", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_name", type="string", example="会员标签", description="标签名称"),
     *                          @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                          @SWG\Property( property="description", type="string", example="会员标签会员标签", description="内容"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="created", type="string", example="1599541286", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1599541286", description="修改时间"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="source", type="string", example="self", description="备注说明 | 标签来源,self:商户自定义，staff:系统固定员工tag"),
     *                          @SWG\Property( property="saleman_id", type="string", example="0", description="导购员id"),
     *                          @SWG\Property( property="tag_status", type="string", example="online", description="标签类型，online：线上发布, self: 私有自定义"),
     *                          @SWG\Property( property="category_id", type="string", example="2", description="分类id"),
     *                          @SWG\Property( property="self_tag_count", type="string", example="120", description="自定义标签下会员数量"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="grade", type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description="id"),
     *                          @SWG\Property( property="name", type="string", example="普通会员", description="配置名称"),
     *                          @SWG\Property( property="lv_type", type="string", example="normal", description="等级类型"),
     *                          @SWG\Property( property="discount", type="string", example="0", description="折扣值"),
     *                          @SWG\Property( property="userVipData", type="object",
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                                  @SWG\Property( property="vip_type", type="string", example="vip", description="会员类型"),
     *                                  @SWG\Property( property="vip_grade_id", type="string", example="1", description="付费会员卡等级ID"),
     *                                  @SWG\Property( property="end_date", type="string", example="1601434134", description="到期时间"),
     *                                  @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型,可选值有 vip:普通vip;svip:进阶vip"),
     *                                  @SWG\Property( property="is_had_vip", type="string", example="true", description=""),
     *                                  @SWG\Property( property="is_vip", type="string", example="false", description=""),
     *                                  @SWG\Property( property="end_time", type="string", example="2020-09-30", description="权益结束时间"),
     *                                  @SWG\Property( property="day", type="string", example="0", description="生日日期"),
     *                                  @SWG\Property( property="valid", type="string", example="false", description=""),
     *                                  @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                                  @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *                                  @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     *                                  @SWG\Property( property="guide_title", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description="购买引导文本"),
     *                                  @SWG\Property( property="background_pic_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/MUQsdY0GdK5nQNFBaEhiao8MfBoP4B70L2rfqJDROzKgwUBvANmHMq9bQV2G1IWibKxK8iaukqbHiaicNkGKZPbX8EA/0?wx_fmt=jpeg", description="背景图"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="external_userid", type="string", example="null", description="企业微信外部成员id"),
     *                  @SWG\Property( property="salesperson_id", type="string", example="null", description="导购员id"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
     * )
     */
    public function getUserData(Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $result = [];
        $mobile = $request->get('mobile', 0);
        $user_card_code = $request->get('user_card_code', 0);
        if (!$mobile && !$user_card_code) {
            throw new ResourceException('手机号 或 会员码二选一必填');
        }
        $authInfo = $this->auth->user();
        if ($mobile) {
            $filter = [
                'company_id' => $authInfo['company_id'],
                'mobile' => $mobile,
            ];
        } elseif ($user_card_code) {
            $filter = [
                'company_id' => $authInfo['company_id'],
                'user_card_code' => $user_card_code,
            ];
        }
        //获取该手机号对应的用户
        $memberService = new MemberService();

        $col = "m.company_id,m.user_id,m.grade_id,m.mobile,m.user_card_code,m.remarks,info.username,info.sex,info.avatar";
        $userData = $memberService->getMemberDataLists($filter, $col, 1, 1);
        if ($userData['total_count'] > 0) {
            $result = $userData['list'][0];
            $filter = [
                'user_id' => $result['user_id'],
                'company_id' => $result['company_id'],
            ];
            $wechatUserService = new WechatUserService();
            $col = "nickname,headimgurl";
            $wechatUsers = $wechatUserService->getWechatUserList($filter, $col);
            if ($wechatUsers) {
                $wechatUser = $wechatUsers[0];
                $result = array_merge($result, $wechatUser);
            }

            //获取会员的标签
            $memberTagsService = new MemberTagsService();
            $filter = [
                'user_id' => $result['user_id'],
                'company_id' => $result['company_id'],
            ];
            $tagdata = $memberTagsService->getUserRelTagList($filter);
            $result['tags'] = $tagdata;

            //获取会员等级信息
            $result['grade'] = $memberService->getValidUserGradeUniqueByUserId($result['user_id'], $result['company_id']);

            $workWechatRelService = new WorkWechatRelService();
            $workRelInfo = $workWechatRelService->getInfo(['company_id' => $authInfo['company_id'], 'salesperson_id' => $authInfo['salesperson_id'], 'user_id' => $result['user_id']]);
            $result['external_userid'] = $workRelInfo['external_userid'] ?? null;
            $result['salesperson_id'] = $workRelInfo['salesperson_id'] ?? null;
            $result['username'] = $result['username'] ?? $result['nickname'];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/asyncGetUserData",
     *     summary="根据手机号获取用户信息",
     *     tags={"会员"},
     *     description="根据手机号获取用户信息",
     *     operationId="getUserData",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter(name="mobile", in="query", description="会员手机号 与 会员码二选一必填", required=true, type="string"),
     *     @SWG\Parameter(name="user_card_code", in="query", description="会员码 与 手机号 二选一必填", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                  @SWG\Property( property="mobile", type="string", example="18321148690", description="手机号"),
     *                  @SWG\Property( property="user_card_code", type="string", example="324A50B01181", description="会员卡号"),
     *                  @SWG\Property( property="coupon_count", type="string", example="2", description=""),
     *                  @SWG\Property( property="point", type="string", example="200", description="总积分"),
     *                  @SWG\Property( property="onlineOrderNum", type="string", example="5", description=""),
     *                  @SWG\Property( property="offlineOrderNum", type="string", example="0", description=""),
     *                  @SWG\Property( property="total_consumption", type="string", example="0", description=""),
     *                  @SWG\Property( property="deposit", type="string", example="0", description=""),
     *                  @SWG\Property( property="order_list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/OrderInfo"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="frequent_item_list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/FrequentItem"
     *                       ),
     *                  ),
     *                  @SWG\Property( property="frequent_category_list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/FrequentCategory"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
     * )
     */
    public function asyncGetUserData(Request $request)
    {
        $result = [];
        $mobile = $request->get('mobile', 0);
        $user_card_code = $request->get('user_card_code', 0);
        if (!$mobile && !$user_card_code) {
            throw new ResourceException('手机号 或 会员码二选一必填');
        }
        if ($mobile) {
            $filter = [
                'mobile' => $mobile,
            ];
        } elseif ($user_card_code) {
            $filter = [
                'user_card_code' => $user_card_code,
            ];
        }
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];
        //获取该手机号对应的用户
        $memberService = new MemberService();

        $col = "m.company_id,m.user_id,m.mobile,m.user_card_code";
        $userData = $memberService->getMemberDataLists($filter, $col, 1, 1);
        if ($userData['total_count'] > 0) {
            $result = $userData['list'][0];
            //获取会员所拥有的优惠券数量
            $userDiscountService = new UserDiscountService();
            $filter = [
                'user_id' => $result['user_id'],
                'status' => 1,
                'end_date|gte' => time(),
            ];
            $result['coupon_count'] = $userDiscountService->getUserDiscountCount($filter);
            //获取会员积分
            $pointMemberService = new PointMemberService();
            $pointMember = $pointMemberService->getInfo(['user_id' => $result['user_id']]);
            $result['point'] = isset($pointMember['point']) ? $pointMember['point'] : 0;
            // 获取订单数量
            $orderService = new OrderService(new NormalOrderService());
            $onlineOrderCount = $orderService->getOrdersCount($result['user_id'], $result['mobile']);
            $result['onlineOrderNum'] = $onlineOrderCount['online'] ?? 0;
            $result['offlineOrderNum'] = $onlineOrderCount['offline'] ?? 0;
            //获取会员总消费金额
            $result['total_consumption'] = $memberService->getTotalConsumption($result['user_id']);
            $depositTrade = new DepositTrade();
            // 会员储值接口
            $result['deposit'] = $depositTrade->getUserDepositTotal($authInfo['company_id'], $result['user_id']);

            // $result['total_consumption'] = $onlineOrderCount['total_fee'] ?? 0;
            $orderFilter = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $result['user_id'],
                'pay_status' => 'PAYED'
            ];
            $orderList = $orderService->getOrderList($orderFilter, 1, 5);
            $result['order_list'] = $orderList['list'] ?? [];
            $result['frequent_item_list'] = $orderService->getFrequentItemList($authInfo['company_id'], $result['user_id']);
            $result['frequent_category_list'] = $orderService->getFrequentCategoryList($authInfo['company_id'], $result['user_id']);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/taglist",
     *     summary="获取会员标签列表",
     *     tags={"会员"},
     *     description="获取会员标签列表",
     *     operationId="getTagsList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="24", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="241", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_name", type="string", example="尊尊会员", description="标签名称"),
     *                          @SWG\Property( property="description", type="string", example="", description="内容"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="saleman_id", type="string", example="0", description="导购员id"),
     *                          @SWG\Property( property="tag_status", type="string", example="online", description="标签类型，online：线上发布, self: 私有自定义"),
     *                          @SWG\Property( property="category_id", type="string", example="2", description="分类id"),
     *                          @SWG\Property( property="self_tag_count", type="string", example="1", description="自定义标签下会员数量"),
     *                          @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="rgba(8, 5, 5, 1)", description="字体颜色"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="created", type="string", example="1612158857", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612158857", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTagsList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'tag_name');
        $rules = [
            'page' => ['required', 'page 必填'],
            'page_size' => ['required', 'page_size 必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $page = $params['page'] ?: 1;
        $pageSize = $params['page_size'] ?: 100;

        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $filter['company_id'] = $companyId;
        if (isset($params['tag_name']) && $params['tag_name']) {
            $filter['tag_name|contains'] = $params['tag_name'];
        }
        $orderBy = ['created' => 'DESC'];
        $memberTagService = new MemberTagsService();

        $result = $memberTagService->getListTags($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/reltag",
     *     summary="关联会员标签",
     *     tags={"会员"},
     *     description="关联会员标签",
     *     operationId="userRelTag",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_ids", in="formData", description="tagId", required=true, type="string"),
     *     @SWG\Parameter( name="user_ids", in="formData", description="userId", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function userRelTag(Request $request)
    {
        $memberTagService = new MemberTagsService();
        $params = $request->all('tag_ids', 'user_ids');
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        if (!$params['user_ids'] || !$params['tag_ids']) {
            throw new ResourceException('参数错误');
        }

        if (!is_array($params['user_ids']) && !is_numeric($params['user_ids'])) {
            $params['user_ids'] = json_decode($params['user_ids'], true);
        }
        if (!is_array($params['tag_ids']) && !is_numeric($params['tag_ids'])) {
            $params['tag_ids'] = json_decode($params['tag_ids'], true);
        }

        if (is_array($params['user_ids']) && is_array($params['tag_ids'])) {
            $result = $memberTagService->createRelTags($params['user_ids'], $params['tag_ids'], $companyId);
        } elseif (!is_array($params['user_ids'])) {
            $result = $memberTagService->createRelTagsByUserId($params['user_ids'], $params['tag_ids'], $companyId);
        } elseif (is_array($params['user_ids']) && !is_array($params['tag_ids'])) {
            $result = $memberTagService->createRelTagsByTagId($params['user_ids'], $params['tag_ids'], $companyId);
        }
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/delreltag",
     *     summary="删除会员标签关联关系",
     *     tags={"会员"},
     *     description="删除会员标签关联关系",
     *     operationId="delRelUserTag",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_ids", in="query", description="tagId", required=true, type="string"),
     *     @SWG\Parameter( name="user_ids", in="query", description="userId", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function delRelUserTag(Request $request)
    {
        $memberTagService = new MemberTagsService();
        $params = $request->all('tag_ids', 'user_ids');
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        if (!$params['user_ids'] || !$params['tag_ids']) {
            throw new ResourceException('参数错误');
        }
        $result = $memberTagService->userRelTagDelete($companyId, $params['user_ids'], $params['tag_ids']);
        return $this->response->array(['status' => $result]);
    }

    /**
      * @SWG\Get(
      *     path="/wxapp/getUserList",
      *     summary="获取指定店铺下关联的会员列表",
      *     tags={"会员"},
      *     description="获取指定店铺下关联的会员列表",
      *     operationId="getUserList",
      *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
      *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
      *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
      *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
      *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string" ),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="list", type="array",
      *                      @SWG\Items( type="object",
      *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
      *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
      *                          @SWG\Property( property="grade_id", type="string", example="4", description="等级ID"),
      *                          @SWG\Property( property="mobile", type="string", example="18321148690", description="手机号"),
      *                          @SWG\Property( property="user_card_code", type="string", example="324A50B01181", description="会员卡号"),
      *                          @SWG\Property( property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="appid"),
      *                          @SWG\Property( property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="appid"),
      *                          @SWG\Property( property="source_id", type="string", example="0", description="来源id"),
      *                          @SWG\Property( property="monitor_id", type="string", example="0", description="监控id"),
      *                          @SWG\Property( property="latest_source_id", type="string", example="0", description="最近来源id"),
      *                          @SWG\Property( property="latest_monitor_id", type="string", example="0", description="最近监控页面id"),
      *                          @SWG\Property( property="created", type="string", example="1598845028", description=""),
      *                          @SWG\Property( property="updated", type="string", example="1611648120", description="修改时间"),
      *                          @SWG\Property( property="created_year", type="string", example="2020", description="创建年份"),
      *                          @SWG\Property( property="created_month", type="string", example="8", description="创建月份"),
      *                          @SWG\Property( property="created_day", type="string", example="31", description="创建日期"),
      *                          @SWG\Property( property="offline_card_code", type="string", example="null", description="线下会员卡号"),
      *                          @SWG\Property( property="inviter_id", type="string", example="0", description="推荐人id"),
      *                          @SWG\Property( property="source_from", type="string", example="default", description="来源类型 default默认"),
      *                          @SWG\Property( property="password", type="string", example="$2y$10$jZfU/nxvU8TgdFuX6wD2aOwvUSvR6nJqMvw5OP98ytaPR7rRxROsu", description="密码"),
      *                          @SWG\Property( property="disabled", type="string", example="0", description="是否禁用 true=禁用,false=启用"),
      *                          @SWG\Property( property="use_point", type="string", example="0", description="是否可以使用积分"),
      *                          @SWG\Property( property="remarks", type="string", example="null", description="备注"),
      *                          @SWG\Property( property="third_data", type="string", example="100102866937", description="第三方数据"),
      *                          @SWG\Property( property="username", type="string", example="heihei", description="姓名"),
      *                          @SWG\Property( property="sex", type="string", example="0", description="性别。0 未知 1 男 2 女"),
      *                          @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
      *                          @SWG\Property( property="address", type="string", example="null", description="具体地址"),
      *                          @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
      *                          @SWG\Property( property="industry", type="string", example="null", description="从事行业"),
      *                          @SWG\Property( property="income", type="string", example="null", description="收入"),
      *                          @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
      *                          @SWG\Property( property="habbit", type="string", example="[]", description="爱好(DC2Type:json_array)"),
      *                          @SWG\Property( property="wechatUser", type="object",
      *                                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
      *                                  @SWG\Property( property="nickname", type="string", example="倩儿", description="昵称"),
      *                                  @SWG\Property( property="headimgurl", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/L9bV1ATkhyNWQegOqHrNkD8ZVQVxA1q78JaalUPrBQ9M8JeKBCJjRkZPNbibeIW8klLj0c7hyhzPaU6lBGbrlMA/132", description="头像url"),
      *                                  @SWG\Property( property="sex", type="string", example="2", description="用户性别"),
      *                          ),
      *                          @SWG\Property( property="tags", type="array",
      *                              @SWG\Items( type="object",
      *                                  ref="#/definitions/MemberTag"
      *                               ),
      *                          ),
      *                       ),
      *                  ),
      *                  @SWG\Property( property="total_count", type="string", example="1", description=""),
      *          ),
      *     )),
      *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
      * )
      */
    public function getUserList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'mobile', 'tag_id', 'user_card_code');
        $rules = [
            'page' => ['required','页码必填'],
            'page_size' => ['required', '行数必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $authInfo = $this->auth->user();
        $memFilter['company_id'] = $authInfo['company_id'];
        $page = intval($params['page']) ? intval($params['page']) : 1;
        $limit = intval($params['page_size']) ? intval($params['page_size']) : 20;
        if ($params['tag_id']) {
            $memFilter['tag_id'] = $params['tag_id'];
        }
        if ($params['mobile']) {
            $memFilter['mobile'] = $params['mobile'];
        } elseif ($params['user_card_code']) {
            $memFilter['user_card_code'] = $params['user_card_code'];
        } else {
            $result['list'] = [];
            $result['total_count'] = 0;
            $shopId = $request->input('shop_id');
            if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
                return $result;
            }
            if ($shopId) {
                $memFilter['shop_id'] = $shopId;
            }

            $distributorId = $request->get('distributor_id');
            if ($authInfo['distributor_ids'] && $distributorId && !in_array($distributorId, $authInfo['distributor_ids'])) {
                return $result;
            }
            if ($distributorId) {
                $memFilter['distributor_id'] = $distributorId;
            }
        }

        $memberService = new MemberService();
        $result['list'] = $memberService->getMemberList($memFilter, $page, $limit);
        $result['total_count'] = $memberService->getMemberCount($memFilter);

        if ($result['list'] && $result['total_count']) {
            $userIds = array_column($result['list'], 'user_id');
            //获取tag
            $tagFilter = [
                'user_id' => $userIds,
                'company_id' => $memFilter['company_id'],
            ];
            $memberTagService = new MemberTagsService();
            $tagList = $memberTagService->getUserRelTagList($tagFilter);
            foreach ($tagList as $value) {
                $newTags[$value['user_id']][] = $value;
            }
            //获取微信信息
            $wecFilter = [
                'user_id' => $userIds,
                'company_id' => $memFilter['company_id'],
            ];
            $wechatUserService = new WechatUserService();
            $wechatUsers = $wechatUserService->getWechatUserList($wecFilter);
            $wechatUser = array_column($wechatUsers, null, 'user_id');
            foreach ($result['list'] as &$value) {
                $value['wechatUser'] = $wechatUser[$value['user_id']] ?? [];
                $value['tags'] = $newTags[$value['user_id']] ?? [];
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributors/getUserList",
     *     summary="获取指定店铺下关联的会员列表",
     *     tags={"会员"},
     *     description="获取指定店铺下关联的会员列表(包含等级)",
     *     operationId="getDistributorUserList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", type="string"),
     *     @SWG\Parameter( name="username", in="query", description="用户名", type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="标签json字符串", type="string"),
     *     @SWG\Parameter( name="group_id", in="query", description="分组id", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="user_id", type="string", example="20206", description=""),
     *                          @SWG\Property( property="grade_id", type="string", example="4", description="等级ID"),
     *                          @SWG\Property( property="mobile", type="string", example="13681644055", description="手机号"),
     *                          @SWG\Property( property="user_card_code", type="string", example="05D153288AA4", description="会员卡号"),
     *                          @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     *                          @SWG\Property( property="username", type="string", example="小西瓜", description="姓名"),
     *                          @SWG\Property( property="sex", type="string", example="1", description="用户性别"),
     *                          @SWG\Property( property="avatar", type="string", example="", description="头像"),
     *                          @SWG\Property( property="id", type="string", example="97", description=""),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="work_userid", type="string", example="null", description="企业微信userid"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="38", description="导购员id"),
     *                          @SWG\Property( property="external_userid", type="string", example="null", description="企业微信外部成员id"),
     *                          @SWG\Property( property="unionid", type="string", example="ofQlA04QsRJX25Pm-Yp5MfJkuZTs", description="unionid"),
     *                          @SWG\Property( property="is_friend", type="string", example="0", description="是否好友 0 否 1 是"),
     *                          @SWG\Property( property="is_bind", type="string", example="0", description="是否绑定 0 否 1 是"),
     *                          @SWG\Property( property="bound_time", type="string", example="1596092027", description="绑定时间"),
     *                          @SWG\Property( property="add_friend_time", type="string", example="0", description="添加好友时间"),
     *                          @SWG\Property( property="wechatUser", type="object",
     *                                  @SWG\Property( property="user_id", type="string", example="20206", description="用户id"),
     *                                  @SWG\Property( property="nickname", type="string", example="小辰", description="昵称"),
     *                                  @SWG\Property( property="headimgurl", type="string", example="https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJRseEUoVK7oeqvnQEpjoPPD7BMN1CBn2IAvRyaAe7bufSCKzOwCibamCST1RicicLfoR5B5ibZaBLs1w/132", description="头像url"),
     *                                  @SWG\Property( property="sex", type="string", example="1", description="性别"),
     *                          ),
     *                          @SWG\Property( property="vipgrade", type="string", example="", description=""),
     *                          @SWG\Property( property="item_histrory", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="browse_item_count", type="string", example="0", description=""),
     *                          @SWG\Property( property="total_consumption", type="string", example="0", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones")))
     * )
     */
    public function getDistributorUserList(Request $request)
    {
        $result = [
            'list' => [],
            'total_count' => 0
        ];

        $params = $request->all('page', 'page_size', 'mobile', 'tag_id', 'user_card_code', 'username', 'group_id');

        $rules = [
            'page' => ['required','页码必填'],
            'page_size' => ['required', '行数必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $memberService = new MemberService();
        $page = intval($params['page']) ? intval($params['page']) : 1;
        $limit = intval($params['page_size']) ? intval($params['page_size']) : 20;
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $userIds = [];
        // 通过手机号筛选用户
        if ($mobile = $request->get('mobile', 0)) {
            $userIds = $memberService->getUserIdByMobile2($mobile);
            if (!$userIds) {
                return $this->response->array($result);
            }
        }
        // 通过会员卡筛选用户
        if ($user_card_code = $request->get('user_card_code', 0)) {
            $userIds = $memberService->getUserIdsByUserCardCode2($user_card_code);
            if (!$userIds) {
                return $this->response->array($result);
            }
        }
        // 通过用户姓名筛选用户
        if ($username = $request->get('username', 0)) {
            $filter = [
               'company_id' => $companyId,
                'username|contains' => $username,
            ];
            $infoList = $memberService->getMemberInfoList($filter, $page, $limit);
            if (!$infoList['list']) {
                return $this->response->array($result);
            }
            $userIds = array_column($infoList['list'], 'user_id');
        }
        // 通过标签筛选用户
        if ($tagIds = $request->get('tag_id', 0)) {
            $tagIds = is_array($tagIds) ? $tagIds : json_decode($tagIds, true);
            $memberTagService = new MemberTagsService();
            $filter = [
                'tag_id' => $tagIds,
               'company_id' => $authInfo['company_id'],
            ];
            if ($userIds) {
                $filter['user_id'] = $userIds;
            }
            $userIds = $memberTagService->getUserIdBy($filter, $page, $limit);
            if (!$userIds) {
                return $this->response->array($result);
            }
        }

        $tabtype = $request->get('group_id', null);
        // 通过是否存在订单筛选用户
        // if ($tabtype == 'have_consume' || $tabtype == 'no_consume') {
        //     $filter = [
        //         'company_id' => $companyId,
        //         'have_consume' => ($tabtype == 'have_consume') ? true : false,
        //     ];
        //     if ($userIds) {
        //         $filter['user_id'] = $userIds;
        //     }
        //     $infoList = $memberService->getMemberInfoList($filter, $page, $limit);
        //     if (!$infoList){
        //         return $this->response->array($result);
        //     }
        //     $userIds = array_column($infoList['list'], 'user_id');
        // }

        if ($authInfo['salesperson_type'] == 'shopping_guide') {
            $relFilter['company_id'] = $authInfo['company_id'];
            $relFilter['salesperson_id'] = $authInfo['salesperson_id'];
            $relFilter['user_id|gt'] = 0;
            //如果是导购员，获取导购员绑定的或加过好友的会员列表
            if ($userIds) {
                $relFilter['user_id'] = $userIds;
            }
            if (is_numeric($tabtype)) {
                $relFilter['group_id'] = intval($tabtype);
                $memberRelGroupService = new MemberRelGroupService();
                $relDataLists = $memberRelGroupService->lists($relFilter, '*', $page, $limit);
            } else {
                if ($tabtype == 'have_consume' || $tabtype == 'no_consume') {
                    $conn = app("registry")->getConnection("default");
                    $criteria = $conn->createQueryBuilder();

                    $criteria->from('work_wechat_rel', 'work')
                        ->leftJoin('work', 'members_info', 'info', 'info.user_id = work.user_id')
                        ->andWhere($criteria->expr()->eq('work.company_id', $authInfo['company_id']))
                        ->andWhere($criteria->expr()->eq('work.salesperson_id', $authInfo['salesperson_id']))
                        ->andWhere($criteria->expr()->eq('info.have_consume', ($tabtype == 'have_consume') ? 1 : 0));
                    if ($userIds) {
                        $criteria->andWhere($criteria->expr()->in('work.user_id', $userIds));
                    }
                    $relDataLists['total_count'] = $criteria->select('count(work.id)')->execute()->fetchColumn();

                    $criteria = $criteria->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
                    $relDataLists['list'] = $criteria->select('work.*')->execute()->fetchAll();
                } else {
                    if ($tabtype == 'friends_only') {
                        $relFilter['is_friend'] = true;
                    }

                    $workWechatRelService = new WorkWechatRelService();
                    $relDataLists = $workWechatRelService->lists($relFilter, '*', $page, $limit, ['bound_time' => 'DESC', 'id' => 'DESC']);
                }
            }
            if (!$relDataLists) {
                return $this->response->array($result);
            }
            $userIds = array_column($relDataLists['list'], 'user_id');
        }

        $memFilter['company_id'] = $authInfo['company_id'];
        if ($userIds) {
            $memFilter['user_id'] = $userIds;
        }
        if (!$userIds) {
            return $this->response->array($result);
        }
        $col = "m.user_id,m.grade_id,m.mobile,m.user_card_code,m.remarks,info.username,info.sex,info.avatar";
        $result = $memberService->getMemberDataLists($memFilter, $col, 1, $limit);
        if ($result['list'] && $result['total_count']) {
            $member = array_column($result['list'], null, 'user_id');
            $list = [];
            foreach ($relDataLists['list'] as $value) {
                if (!($member[$value['user_id']] ?? null)) {
                    continue;
                }
                $list[] = array_merge($member[$value['user_id']], $value);
            }
            $result['list'] = $list;
            $result['total_count'] = $relDataLists['total_count'];

            $userIds = array_column($result['list'], 'user_id');
            $vipGradeOrderService = new VipGradeOrderService();
            $vipgrades = $vipGradeOrderService->getUserVipGrade2($userIds, true, 'user_id, vip_type, grade_name, end_date');
            $vipgrade = [];
            foreach ($vipgrades as $userId => $value) {
                if (isset($value['svip'])) {
                    $vipgrade[$userId] = $value['svip'];
                } elseif (isset($value['vip'])) {
                    $vipgrade[$userId] = $value['vip'];
                } else {
                    $vipgrade[$userId] = ['vip_type' => 'normal', 'user_id' => $userId];
                }
            }

            //获取微信信息
            $wecFilter = [
                'user_id' => $userIds,
               'company_id' => $memFilter['company_id'],
            ];
            $wechatUserService = new WechatUserService();
            $wechatUsers = $wechatUserService->getWechatUserList($wecFilter);
            $wechatUser = array_column($wechatUsers, null, 'user_id');
            $memberBrowseHistoryService = new MemberBrowseHistoryService();
            $memberBrowseHistoryTemp = $memberBrowseHistoryService->getLastBrowseHistory($userIds);
            $memberBrowseHistory = array_column($memberBrowseHistoryTemp, null, 'user_id');
            foreach ($result['list'] as &$value) {
                $value['wechatUser'] = $wechatUser[$value['user_id']] ?? [];
                $value['vipgrade'] = $vipgrade[$value['user_id']] ?? '';
                $value['username'] = $value['username'] ?? $value['wechatUser']['nickname'];
                $value['item_histrory'] = $memberBrowseHistory[$value['user_id']] ?? [];
                $value['browse_item_count'] = $memberBrowseHistoryService->getUserHistoryItemCount($companyId, $value['user_id']);
                $value['total_consumption'] = $memberService->getTotalConsumption($value['user_id']);
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/browse/history/{user_id}",
     *     summary="获取商品浏览记录",
     *     tags={"会员"},
     *     description="获取商品浏览记录",
     *     operationId="getBrowseHistory",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="user_id", in="path", description="用户id", type="string", required=true),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="135", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="history_id", type="string", example="3621", description="id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20261", description="用户id"),
     *                          @SWG\Property( property="item_id", type="string", example="5290", description="商品id"),
     *                          @SWG\Property( property="created", type="string", example="1607623630", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1609901588", description="修改时间"),
     *                          @SWG\Property( property="itemData", type="object",
     *                                  ref="#/definitions/ItemData"
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getBrowseHistory($userId, Request $request)
    {
        $params = $request->input();
        $authInfo = $this->auth->user();
        $params = array_merge((array)$params, (array)$authInfo);
        $params['user_id'] = $userId;
        $memberBrowseHistoryServiceService = new MemberBrowseHistoryService();
        $result = $memberBrowseHistoryServiceService->getBrowseHistoryList($params);
        return $this->response->array($result);
    }
}
