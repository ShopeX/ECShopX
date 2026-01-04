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

use Dingo\Api\Exception\ResourceException;

use MembersBundle\Services\MemberService;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\UserDiscountService;
use KaquanBundle\Services\DiscountCardService as CardService;
use KaquanBundle\Entities\UserDiscount;

class Coupon extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ecx.coupon.create",
     *     summary="云店优惠券发放",
     *     tags={"优惠券"},
     *     description="云店优惠券发放",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称:ecx.coupon.create" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="template", description="优惠券模板ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="rule", description="优惠券规则ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="code", description="优惠券编码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="start_time", description="优惠券生效时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="end_time", description="优惠券失效时间" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="use_limited", description="是否可以多次使用，默认false，仅记录无业务" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="remain_times", description="剩余使用次数，仅记录无业务" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="outer_crm_userid", description="外部会员ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="user_id", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function userGetCard(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $params = $request->all();
        $rules = [
            'template' => ['required|string', '优惠券模板ID必填'],
            'rule' => ['string', '优惠券规则ID必须为字符串'],
            'code' => ['required|string', '优惠券编码必填'],
            'start_time' => ['required', '优惠券生效时间必填'],
            'end_time' => ['required', '优惠券失效时间必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage, null, 'E0001');
        }
        //根据手机号获取会员ID
        $userId = 0;
        if (isset($params['outer_crm_userid']) && $params['outer_crm_userid']) {
            $filter['company_id'] = $companyId;
            $filter['third_data'] = $params['outer_crm_userid'];
            $memberService = new MemberService();
            $result = $memberService->getMemberInfo($filter);
            if (!$result) {
                $this->api_response('fail', '用户不存在', null, 'E0001');
            }
            $userId = $result['user_id'];
        } elseif (isset($params['user_id']) && $params['user_id']) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($params['user_id'], $companyId);
            if (!$userId) {
                $this->api_response('fail', '用户不存在', null, 'E0001');
            }
        }
        $cardRuleCode = isset($params['rule']) ? $params['rule'] : '';
        //领取优惠券时 检测领取权限
        try {
            $cardInfo = $this->__getCardInfo($params['template'], $cardRuleCode, $companyId, $params);
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage(), null, 'E0001');
        }
        //发放数据
        $postdata['status'] = 1;
        $postdata['source_type'] = '第三方发放';
        $postdata['company_id'] = $companyId;
        $postdata['card_id'] = $cardInfo['card_id'];
        $postdata['user_id'] = $userId;
        $postdata['code'] = $params['code'];
        $postdata['salesperson_id'] = '0';
        if ($params['use_limited']) {
            $postdata['use_limited'] = $params['use_limited'];
        }
        if ($params['remain_times']) {
            $postdata['remain_times'] = $params['remain_times'];
        }
        $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
        $status = $userDiscountRepository->userGetCard($postdata, $cardInfo);
        if (!$status) {
            $this->api_response('fail', '发放失败', null, 'E0001');
        }
        $this->api_response('true', "发放成功", $status, 'E0000');
    }

    /**
     * [__getCardInfo 领取优惠券时 检测领取权限]
     * @param  [type] $cardId    [description]
     * @param  [type] $companyId [description]
     * @return [type]            [description]
     */
    private function __getCardInfo($templateCode, $cardRuleCode = '', $companyId, $params)
    {
        $discountCardService = new KaquanService(new CardService());
        $filter['card_code'] = $templateCode;
        $filter['card_rule_code'] = $cardRuleCode;

        $filter['company_id'] = $companyId;
        $cardInfo = $discountCardService->getKaquanDetail($filter);
        if (!$cardInfo) {
            throw new ResourceException('领取的优惠券不存在');
        }
        if ($params['end_time'] && $params['end_time'] <= time()) {
            throw new ResourceException('领取优惠券失败，优惠券已过期');
        }
        $userDiscountService = new UserDiscountService();
        $getNum = $userDiscountService->getCardGetNum($cardInfo['card_id'], $companyId);

        if ($cardInfo['quantity'] <= $getNum) {
            throw new ResourceException('领取的优惠券失败，库存不足了');
        }

        $cardInfo['discount'] = $cardInfo['discount'] ?: 0;
        $cardInfo['least_cost'] = $cardInfo['least_cost'] ?: 0;
        $cardInfo['reduce_cost'] = $cardInfo['reduce_cost'] ?: 0;
        $cardInfo['most_cost'] = $cardInfo['most_cost'] ?: 99999900;

        $cardInfo['use_condition'] = [
            'accept_category' => $cardInfo['accept_category'],
            'reject_category' => $cardInfo['reject_category'],
            'least_cost' => $cardInfo['least_cost'],
            'object_use_for' => $cardInfo['object_use_for'],
            'can_use_with_other_discount' => $cardInfo['can_use_with_other_discount'],
        ];

        if ($cardInfo['use_all_shops'] == "true") {
            $cardInfo['rel_shops_ids'] = 'all';
            $cardInfo['distributor_id'] = 'all';
        } else {
            $shopIds = $cardInfo['rel_shops_ids'] ?? [];
            $cardInfo['rel_shops_ids'] = implode(',', $shopIds) ? ','.implode(',', $shopIds).',' : 'all';
            $distributorIds = $cardInfo['rel_distributor_ids'] ?? [];
            $cardInfo['distributor_id'] = count($distributorIds) > 0 ? ','.implode(',', $distributorIds).',' : 'all';
        }

        if (isset($cardInfo['rel_item_ids']) && $cardInfo['rel_item_ids'] && is_array($cardInfo['rel_item_ids'])) {
            $cardInfo['rel_item_ids'] = ','.implode(',', $cardInfo['rel_item_ids']).',';
        } else {
            $cardInfo['rel_item_ids'] = 'all';
        }
        $cardInfo['begin_date'] = $params['start_time'];
        $cardInfo['end_date'] = $params['end_time'];

        return $cardInfo;
    }

    /**
     * @SWG\Post(
     *     path="/ecx.coupon.verify",
     *     summary="优惠券状态更新",
     *     tags={"优惠券"},
     *     description="优惠券状态更新",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.coupon.verify" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="coupon_code", description="优惠券编码" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="coupon_status", description="1 核销 ，2作废" ),
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
    public function userConsumeCard(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $params = $request->all();

        $rules = [
            'coupon_code' => ['required|string', '优惠券编码必填'],
            'coupon_status' => ['required|string', '状态必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage, null, 'E0001');
        }
        if (!in_array($params['coupon_status'], ['1', '2'])) {
            $this->api_response('fail', '不支持的核销状态', null, 'E0001');
        }
        $code = $params['coupon_code'];
        $status = 6;
        if ($params['coupon_status'] == '1') {
            $status = 2;
        } elseif ($params['coupon_status'] == '2') {
            $status = 6;
        }

        $updateData = [
            'company_id' => $companyId,
            'code' => $code,
            'status' => $status,
            'consume_outer_str' => '第三方核销',
        ];
        $userDiscountService = new UserDiscountService();
        try {
            $result = $userDiscountService->userConsumeCard($companyId, $code, $updateData);
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage(), null, 'E0001');
        }
        $this->api_response('true', "修改成功", $result, 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.coupon.update",
     *     summary="优惠券状态更新",
     *     tags={"优惠券"},
     *     description="优惠券状态更新",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.coupon.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="template", description="优惠券模板ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="rule", description="优惠券规则ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="code", description="优惠券编码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="start_time", description="优惠券生效时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="end_time", description="优惠券失效时间" ),
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
    public function updateUserCard(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $params = $request->all();

        $rules = [
            'template' => ['required|string', '优惠券模板ID必填'],
            'rule' => ['string', '优惠券规则ID必须为字符串'],
            'code' => ['required|string', '优惠券编码必填'],
            'start_time' => ['required', '优惠券生效时间必填'],
            'end_time' => ['required', '优惠券失效时间必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage, null, 'E0001');
        }
        //修改优惠券时 检测
        try {
            $discountCardService = new KaquanService(new CardService());
            $filter['card_code'] = $params['template'];

            if (isset($params['rule']) && !empty($params['rule'])) {
                $filter['card_rule_code'] = $params['rule'];
            } else {
                $filter['card_rule_code'] = '';
            }

            $filter['company_id'] = $companyId;

            $cardInfo = $discountCardService->getKaquanDetail($filter);

            if (!$cardInfo) {
                $this->api_response('fail', '优惠券不存在', null, 'E0001');
            }
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage(), null, 'E0001');
        }


        //修改数据，目前仅支持修改时间
        $postdata['begin_date'] = $params['start_time'];
        $postdata['end_date'] = $params['end_time'];

        $updateFilter = [
            'company_id' => $companyId,
            'card_id' => $cardInfo['card_id'],
            'code' => $params['code'],
        ];
        $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
        $status = $userDiscountRepository->updateUserCard($postdata, $updateFilter);
        if (!$status) {
            $this->api_response('fail', '修改失败，请检查优惠券是否存在', null, 'E0001');
        }
        $this->api_response('true', "修改成功", $status, 'E0000');
    }


    /**
     * @SWG\Get(
     *     path="/ecx.coupon.list",
     *     summary="优惠券列表",
     *     tags={"优惠券"},
     *     description="优惠券列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.coupon.list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="distributor_id", description="门店编号" ),
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
    public function getCouponList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all();
        $discountCardService = new KaquanService(new CardService());
        //这个条件没想好怎么用criteria来组织, 先用SQL来处理
        $conn = app('registry')->getConnection('default');
        $sql = "select card_id,title,description,discount,distributor_id,card_type from kaquan_discount_cards where company_id = " . $companyId;
        if ($params['distributor_id'] ?? 0) {
            $distributor_ids = explode(',', $params['distributor_id']);
            $sql .= " and ( distributor_id = ','";
            $orFilter = "";
            foreach ($distributor_ids as $k => $distributor_id) {
                $orFilter .= " or distributor_id like '%,".$distributor_id.",%'";
            }
            $sql .= $orFilter . ")";
        }
        $sql .= " order by created desc";
        if (isset($params['page_no']) && isset($params['page_size'])) {
            $count = $params['page_size'];
            $offset = ($params['page_no'] - 1) * $count;
            $sql .= " limit ". $offset . "," . $count;
        }
        try {
            $return['list'] = $conn->executeQuery($sql)->fetchAll();
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage(), null, 'E0001');
        }
        $this->api_response('true', '操作成功', $return, 'E0000');
    }
}
