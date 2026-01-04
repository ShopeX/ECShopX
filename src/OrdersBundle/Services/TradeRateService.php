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

namespace OrdersBundle\Services;

use CompanysBundle\Entities\Operators;
use GoodsBundle\Entities\Items;
use PointsmallBundle\Entities\PointsmallItems;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\TradeRate;
use OrdersBundle\Entities\TradeRateReply;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Dingo\Api\Exception\ResourceException;

class TradeRateService
{
    public $tradeRateRepository;
    public $tradeRateReplyRepository;
    public $itemsRepository;
    public $pointsmallItemsRepository;

    public function __construct()
    {
        $this->tradeRateRepository = app('registry')->getManager('default')->getRepository(TradeRate::class);
        $this->tradeRateReplyRepository = app('registry')->getManager('default')->getRepository(TradeRateReply::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->pointsmallItemsRepository = app('registry')->getManager('default')->getRepository(PointsmallItems::class);
    }

    //管理评价列表
    public function lists($filter, $page = 1, $pageSize = 20, $orderBy = array('created' => 'DESC'))
    {
        $membersService = new MemberService();
        if (isset($filter['distributor_id'])) {
            $result = $this->tradeRateRepository->getListsByDistributor($filter, $page, $pageSize, $orderBy);
        } else {
            $result = $this->tradeRateRepository->lists($filter, $page, $pageSize, $orderBy);
        }
        foreach ($result['list'] as &$value) {
            if ($value['user_id']) {
                $member = $membersService->getMemberInfo(['user_id' => $value['user_id'], 'company_id' => $value['company_id']]);
                $value['username'] = $member['username'] ?? '匿名';
            }
            if (isset($filter['order_type']) && $filter['order_type'] == 'pointsmall') {
                // 积分商城商品
                $item = $this->pointsmallItemsRepository->getInfo(['item_id' => $value['item_id'], 'company_id' => $value['company_id']]);
            } else {
                $item = $this->itemsRepository->getInfo(['item_id' => $value['item_id'], 'company_id' => $value['company_id']]);
            }
            $value['item_name'] = $item['item_name'] ?? '';
        }
        return $result;
    }

    //评价回复
    public function replyTradeRate($data)
    {
        // U2hv framework
        $filter = ['company_id' => $data['company_id'], 'rate_id' => $data['rate_id']];
        $rateInfo = $this->tradeRateRepository->getInfoById($data['rate_id']);
        $createDate = [
            'company_id' => $data['company_id'],
            'parent_id' => $data['rate_id'],
            'item_id' => $rateInfo['item_id'],
            'goods_id' => $rateInfo['goods_id'],
            'order_id' => $rateInfo['order_id'],
            'content' => $data['content'],
            'content_len' => strlen($data['content']),
        ];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            //获取用户信息来记录是买家评论还是管理员评论
            if (isset($data['operator_id'])) {
                $createDate['role'] = 'seller';
                $createDate['operator_id'] = $data['operator_id'];
                $this->tradeRateRepository->updateOneBy($filter, ['is_reply' => true]);
            } else {
                $createDate['role'] = 'buyer';
                $createDate['user_id'] = $data['user_id'];
            }

            $result = $this->tradeRateRepository->create($createDate);
            $conn->commit();
        } catch (BadRequestHttpException $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $result;
    }

    //创建评价
    public function create(array $data)
    {
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderDetail = $normalOrdersRepository->get($data['company_id'], $data['order_id']);
        $order_class = $orderDetail->getOrderClass();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $order_filter = ['order_id' => $data['order_id'], 'company_id' => $data['company_id']];
            //$orderItems = $normalOrdersItemsRepository->getList($order_filter);

            $result = [];

            foreach ($data['rates'] as $rate) {
                $filter = [
                    'item_id' => $rate['item_id'],
                    'order_id' => $data['order_id'],
                    'company_id' => $data['company_id']];

                $tradeRate = $this->tradeRateRepository->getInfo($filter);
                if ($tradeRate) {
                    continue;
                }

                if ($order_class == 'pointsmall') {
                    // 积分商城订单
                    $item = $this->pointsmallItemsRepository->getInfo(['item_id' => $rate['item_id'], 'company_id' => $data['company_id']]);
                } else {
                    $item = $this->itemsRepository->getInfo(['item_id' => $rate['item_id'], 'company_id' => $data['company_id']]);
                }

                if (!$item) {
                    throw new ResourceException(trans('OrdersBundle/Order.item_not_exist'));
                }

                //获取规格描述
                $OrdersItems = $normalOrdersItemsRepository->getRow(['order_id' => $data['order_id'], 'item_id' => $rate['item_id'], 'company_id' => $data['company_id']]);

                $createDate = [
                    'company_id' => $data['company_id'],
                    'item_id' => $rate['item_id'],
                    'goods_id' => $item['goods_id'],
                    'order_id' => $data['order_id'],
                    'user_id' => $data['user_id'],
                    'content' => $rate['content'] ?? '',
                    'content_len' => strlen($rate['content']) ?? 0,
                    'star' => $rate['star'],
                    'anonymous' => $data['anonymous'],
                    'unionid' => $data['unionid'],
                    'item_spec_desc' => $OrdersItems['item_spec_desc'] ?? '',
                    'order_type' => $order_class == 'pointsmall' ? 'pointsmall' : 'normal',
                ];

                if (isset($rate['pics']) && !empty($rate['pics'])) {
                    $createDate['rate_pic'] = implode(',', $rate['pics']);
                    $createDate['rate_pic_num'] = count($rate['pics']);
                }

                $result[] = $this->tradeRateRepository->create($createDate);

                //return $upData;
                $normalOrdersItemsRepository->update($filter, ['is_rate' => true]);
            }

            if ($result) {
                $normalOrdersRepository->update($order_filter, ['is_rate' => true]);
            } else {
                $result = ['status' => 0];
            }


            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $result;
    }

    //评价详情
    public function getTradeRate($id)
    {
        // U2hv framework
        $orderAssociationService = new OrderAssociationService();
        $company_id = app('auth')->user()->get('company_id');
        //评价信息
        $result['rateInfo'] = $this->tradeRateRepository->getInfoById($id);
        $result['rateInfo']['rate_pic'] = $result['rateInfo']['rate_pic'] ? explode(',', $result['rateInfo']['rate_pic']) : [];
        $result['rateInfo']['username'] = $this->getUsername(['user_id' => $result['rateInfo']['user_id'], 'company_id' => $company_id], 0);

        // $order = $orderAssociationService->getOrder($company_id, $result['rateInfo']['order_id']);
        // 获取商品订单数据
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $filter = [
            'company_id' => $company_id,
            'order_id' => $result['rateInfo']['order_id'],
            'item_id' => $result['rateInfo']['item_id'],
        ];
        $orderItemsInfo = $normalOrdersItemsRepository->getRow($filter);
        //商品信息
        if ($result['rateInfo']['order_type'] == 'pointsmall') {
            // 积分商城商品
            $item = $this->pointsmallItemsRepository->getInfo(['item_id' => $result['rateInfo']['item_id'], 'company_id' => $company_id]);
            $item['total_point'] = $orderItemsInfo['point'];
        } else {
            $item = $this->itemsRepository->getInfo(['item_id' => $result['rateInfo']['item_id'], 'company_id' => $company_id]);
        }

        $item['total_fee'] = $orderItemsInfo['total_fee'];
        $result['itemInfo'][0] = $item;

        //管理员回复
        if ($result['rateInfo']['is_reply']) {
            $result['replyInfo'] = $this->tradeRateReplyRepository->getInfo(['rate_id' => $id, 'role' => 'seller']);
            $result['replyInfo']['operator_name'] = $this->getUsername(['operator_id' => $result['replyInfo']['operator_id'], 'company_id' => $company_id]);
        }
        $result['userReply'] = [];

        //商品评价评论
        $filter = ['company_id' => $company_id, 'rate_id' => $id, 'role' => 'buyer'];
        $userReply = $this->tradeRateReplyRepository->lists($filter, $page = 1, $pageSize = 100, $orderBy = array('created' => "ASC"));
        if ($userReply) {
            foreach ($userReply['list'] as &$reply) {
                $reply['username'] = $this->getUsername(['user_id' => $reply['user_id'], 'company_id' => $company_id], 0);
            }
            $result['userReply'] = $userReply['list'];
        }
        return $result;
    }

    public function rateList($filter, $page = 1, $pageSize = 20, $orderBy = array('created' => 'DESC'))
    {
        //反查goods_id
        if (isset($filter['order_type']) && $filter['order_type'] == 'pointsmall') {
            // 积分商城商品
            $item = $this->pointsmallItemsRepository->getInfo(['item_id' => $filter['item_id'], 'company_id' => $filter['company_id']]);
        } else {
            $item = $this->itemsRepository->getInfo(['item_id' => $filter['item_id'], 'company_id' => $filter['company_id']]);
        }

        if (!$item) {
            return [];
        }
        $list_filter['goods_id'] = $item['goods_id'];
        $list_filter['company_id'] = $filter['company_id'];

        if (isset($filter['disabled'])) {
            $list_filter['disabled'] = $filter['disabled'];
        }

        $result = $this->tradeRateRepository->lists($list_filter, $page, $pageSize, $orderBy);

        foreach ($result['list'] as &$value) {
            //获取用户信息
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo(['company_id' => $list_filter['company_id'], 'user_id' => $value['user_id']]);
            $value['avatar'] = $memberInfo['avatar'] ?? '';

            $value['username'] = isset($memberInfo['username']) && $memberInfo['username'] ? data_masking('truename', $memberInfo['username']) : '匿名用户';

            //获取点赞数
            $ratePraiseNum = $this->ratePraiseNum(['rate_id' => $value['rate_id']]);
            $value['praise_num'] = $ratePraiseNum['count'] ?? 0;

            //获取回复数量
            $value['reply']['total_count'] = $this->tradeRateReplyRepository->count(['rate_id' => $value['rate_id']]);

            //规格描述
            $value['item_spec_desc'] = $value['item_spec_desc'] ?? '';
        }

        return $result;
    }

    /**
     * 获取评论详情
     * @param $rate_id
     * @return mixed
     */
    public function rateDetail($rate_id, $company_id)
    {
        //获取详情
        $result = $this->tradeRateRepository->getInfoById($rate_id);

        //获取用户信息
        $wechatUserService = new WechatUserService();
        $wechatUser = $wechatUserService->getWechatUserInfo(['company_id' => $company_id, 'unionid' => $result['unionid']]);

        $result['avatar'] = $wechatUser['headimgurl'] ?? '';
        $result['username'] = isset($wechatUser['nickname']) ? str_limit($wechatUser['nickname'], 4, '***') : '';

        //获取点赞数
        $ratePraiseNum = $this->ratePraiseNum(['rate_id' => $rate_id]);
        $result['praise_num'] = $ratePraiseNum['count'] ?? 0;

        //获取回复数
        $result['reply_count'] = $this->tradeRateReplyRepository->count(['rate_id' => $rate_id]);
        //规格描述
        $result['item_spec_desc'] = $result['item_spec_desc'] ?? '';

        return $result;
    }

    public function ratePraiseStatus($filter, $ids)
    {
        if (empty($ids)) {
            throw new ResourceException(trans('OrdersBundle/Order.param_error'));
        }
        $status = [];
        for ($i = 0; $i < count($ids); $i++) {
            $filter['rate_id'] = $ids[$i];
            $ratePraiseStatus = $this->ratePraiseCheck($filter);
            $status['list'][$ids[$i]]['praise_status'] = $ratePraiseStatus['status'] ?? false;
        }
        return $status;
    }

    public function update($filter, $data)
    {
        $result = $this->tradeRateRepository->updateOneBy($filter, $data);
        return $result;
    }

    //根据角色返回用户名
    private function getUsername($filter, $role = 1)
    {
        $membersService = new MemberService();
        $operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        if ($role) {
            $operators = $operatorsRepository->getInfo($filter);
            return $operators['username'] ?? '';
        } else {
            $member = $membersService->getMemberInfo($filter);
            return $member['username'] ?? '';
        }
    }

    //评价点赞
    public function ratePraise($params)
    {
        if (!$params['user_id'] || !$params['rate_id'] || !$params['company_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.param_error'));
        }

        // 检测评价是否存在
        $filter = ['rate_id' => $params['rate_id'], 'company_id' => $params['company_id']];
        $rateInfo = $this->tradeRateRepository->getInfo($filter);

        if (!$rateInfo) {
            throw new ResourceException(trans('OrdersBundle/Order.rate_not_exist'));
        }

        // 检测是否点赞，如果用户继续点就是取消
        $check = $this->ratePraiseCheck($params);
        if ($check['status']) {
            app('redis')->hDel('ratePraiseUser:' . $rateInfo['company_id'] . ':' . $rateInfo['rate_id'], $params['user_id']);
            app('redis')->hincrby('ratePraise', $rateInfo['rate_id'], -1);

            return $this->ratePraiseNum($params);
        }

        //统计点赞数量
        $result['count'] = app('redis')->hincrby('ratePraise', $rateInfo['rate_id'], +1);

        if ($result['count']) {
            // 记录点赞用户
            app('redis')->hSet('ratePraiseUser:' . $rateInfo['company_id'] . ':' . $rateInfo['rate_id'], $params['user_id'], time());
        }

        return $result;
    }

    // 评价点赞数量
    public function ratePraiseNum($params)
    {
        if (!$params['rate_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.param_error'));
        }

        $result['count'] = app('redis')->hget('ratePraise', $params['rate_id']);
        $result['count'] = $result['count'] ? $result['count'] : 0;
        return $result;
    }

    // 评价点赞检测
    public function ratePraiseCheck($params)
    {
        if (!$params['user_id'] || !$params['rate_id'] || !$params['company_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.param_error'));
        }

        $result = app('redis')->hGet('ratePraiseUser:' . $params['company_id'] . ':' . $params['rate_id'], $params['user_id']);

        return ['status' => $result ? true : false];
    }

    /**
     * 获取平均分
     * @param int $companyId 公司id
     * @param array $distributorIds 店铺id
     * @return array
     */
    public function rateAvgStar(int $companyId, array $distributorIds): array
    {
        $result = $this->tradeRateRepository->getAvgStarByDistributorIds($companyId, $distributorIds);
        return (array)array_column($result, "avg_star", "distributor_id");
    }
}
