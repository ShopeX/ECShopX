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

namespace PromotionsBundle\Services;

use GoodsBundle\Entities\Items;
use PromotionsBundle\Entities\BargainPromotions;
use PromotionsBundle\Entities\UserBargains;
use PromotionsBundle\Entities\BargainLog;

use MembersBundle\Services\WechatUserService;
use OrdersBundle\Services\Orders\BargainOrderService;

use WechatBundle\Services\OpenPlatform;

use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Jobs\BargainFinishSendSmsNotice;
use MembersBundle\Services\UserService;

class UserBargainService
{
    /**
     * UserBargains Repository类
     */
    public $userBargainsRepository = null;

    public $bargainPromotionsRepository = null;

    public $bargainLogRepository = null;
    public $itemsRepository = null;
    public $openPlatform;

    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->userBargainsRepository = app('registry')->getManager('default')->getRepository(UserBargains::class);
        $this->bargainPromotionsRepository = app('registry')->getManager('default')->getRepository(BargainPromotions::class);
        $this->bargainLogRepository = app('registry')->getManager('default')->getRepository(BargainLog::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
    }

    public function createUserBargain($authInfo, $bargainId)
    {
        $bargainInfo = $this->bargainPromotionsRepository->get($bargainId);
        if (!$bargainInfo) {
            throw new ResourceException(trans("PromotionsBundle.bargain_activity_not_exist_with_bargain_id", ["bargain_id" => $bargainId]));
        }
        if ($bargainInfo['end_time'] < time()) {
            throw new ResourceException(trans("PromotionsBundle.activity_ended_expect_next_participation"));
        }
        if ($bargainInfo['begin_time'] > time()) {
            throw new ResourceException(trans("PromotionsBundle.activity_not_started_please_wait"));
        }

        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'bargain_id' => $bargainId,
        ];
        $result = $this->userBargainsRepository->get($filter);
        if (!$result) {
            $data = $bargainInfo;
            $data['cutprice_num'] = $num = mt_rand((int) $bargainInfo['people_range']['min'], (int) $bargainInfo['people_range']['max']);
            $total = $bargainInfo['mkt_price'] - $bargainInfo['price'];
            // $min   = - ($total % $num) / 2;
            $min = -floor(($total / $num) / 2);
            $data['cutprice_range'] = $this->getRandPriceNum($total, $num, $min);
            $data['user_id'] = $authInfo['user_id'];
            $data['authorizer_appid'] = isset($authInfo['woa_appid']) ? $authInfo['woa_appid'] : '';
            $data['wxa_appid'] = isset($authInfo['wxapp_appid']) ? $authInfo['wxapp_appid'] : '';
            $result = $this->userBargainsRepository->create($data);
        }

        return $result;
    }

    public function updateUserBargain($filter, $data)
    {
        $userBargainInfo = $this->userBargainsRepository->get($filter);
        if (!$userBargainInfo) {
            throw new ResourceException(trans("PromotionsBundle.bargain_help_detail_not_found"));
        }
        return $this->userBargainsRepository->update($filter, $data);
    }

    public function getBargainInfo($companyId, $bargainId, $userId, $hasOrder = false)
    {
        $bargainInfo = $this->bargainPromotionsRepository->get($bargainId);
        // 查询商品信息
        if (!empty($bargainInfo['item_id'])) {
            $items_info = $this->itemsRepository->get($bargainInfo['item_id']);
            $bargainInfo['item_intro'] = $items_info['intro'];
        }
        if (!$bargainInfo) {
            throw new ResourceException(trans("PromotionsBundle.activity_not_exist"));
        }
        $leftMicroSecond = ($bargainInfo['end_time'] - time()) * 1000;
        $bargainInfo['left_micro_second'] = $leftMicroSecond;

        $result['bargain_info'] = isset($bargainInfo) ? $bargainInfo : [];

        $filter = [
            'company_id' => $companyId,
            'bargain_id' => $bargainId,
            'user_id' => $userId,
        ];
        $userBargainsInfo = $this->userBargainsRepository->get($filter);

        // 已生成订单的助力查询助力id和订单状态
        if (!empty($userBargainsInfo) and $userBargainsInfo['is_ordered']) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('order_id,order_status')
                ->from('orders_normal_orders')
                ->andWhere('user_id=' . $userBargainsInfo['user_id'])
                ->andWhere('act_id = ' . $userBargainsInfo['bargain_id'])
                ->andWhere('company_id=' . $userBargainsInfo['company_id'])
                ->andWhere("order_class= 'bargain'")
                ->orderBy('create_time', 'DESC');
            $result['bargain_order'] = $qb->execute()->fetch();
        }


        $result['user_bargain_info'] = $userBargainsInfo;
        if ($userBargainsInfo) {
            $userFilter = [
                'company_id' => $companyId,
                'user_id' => $userId,
            ];
            $wechatUserService = new WechatUserService();
            $userInfo = $wechatUserService->getUserInfo($userFilter);
            $result['user_info'] = $userInfo;

            $filter = [
                'bargain_id' => $bargainId,
                'user_id' => $userId,
            ];
            $bargainLog = $this->bargainLogRepository->getList($filter);
            $result['bargain_log'] = isset($bargainLog) ? $bargainLog : [];

            if ($hasOrder) {
                $bargainOrderService = new BargainOrderService();
                $bargainOrder = $bargainOrderService->getOrderList($filter);
                if ($bargainOrder['total_count'] > 0) {
                    $result['bargain_order'] = $bargainOrder['list'][0];
                }
            }
        }

        return $result;
    }

    public function createBargainLog($params)
    {
        $bargainInfo = $this->bargainPromotionsRepository->get($params['bargain_id']);
        if (!$bargainInfo) {
            throw new ResourceException("bargain_id为{$params['bargain_id']}的砍价活动不存在！");
        }
        if ($bargainInfo['end_time'] < time()) {
            throw new ResourceException(trans("PromotionsBundle.activity_expired_expect_next"));
        }

        $filter = [
            'company_id' => $params['company_id'],
            'open_id' => $params['open_id'],
            'user_id' => $params['user_id'],
            'bargain_id' => $params['bargain_id'],
        ];
        $log = $this->bargainLogRepository->get($filter);
        if ($log) {
            throw new ResourceException(trans("PromotionsBundle.already_helped"));
        }

        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'bargain_id' => $params['bargain_id'],
        ];
        $userBargainInfo = $this->userBargainsRepository->get($filter);
        if (!$userBargainInfo) {
            throw new ResourceException("助力活动不存在！");
        }
        if ($userBargainInfo['is_ordered']) {
            throw new ResourceException("助力活动已结束，期待您的下次参与！");
        }
        $totalCutdownAmount = $bargainInfo['mkt_price'] - $bargainInfo['price'];

        if ($totalCutdownAmount <= $userBargainInfo['cutdown_amount']) {
            throw new ResourceException(trans("PromotionsBundle.bargain_activity_completed_expect_next"));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {

            // $params['cutdown_num'] = $bargainInfo['bargain_range']['min'] + ($bargainInfo['bargain_range']['max'] - $bargainInfo['bargain_range']['min'])* mt_rand(1,100)/100;
            $params['cutdown_num'] = $this->getCutdownPrice($userBargainInfo);
            $cutprice_range = $userBargainInfo['cutprice_range'];
            $result = $this->bargainLogRepository->create($params);

            $userBargainInfo = $this->userBargainsRepository->get($filter);
            $total_cutdown = intval($userBargainInfo['cutdown_amount']) + intval($params['cutdown_num']);
            $userBargainInfo = $this->userBargainsRepository->update($filter, ['cutdown_amount' => $total_cutdown, 'cutprice_range' => $cutprice_range]);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        if (!$userBargainInfo['is_ordered'] && (($userBargainInfo['mkt_price'] - $userBargainInfo['price'] - $userBargainInfo['cutdown_amount']) <= 0)) {
            $userService = new UserService();
            $mobile = $userService->getMobileByUserId($userBargainInfo['user_id'], $userBargainInfo['company_id']);
            $userBargainInfo['mobile'] = $mobile;
            $userBargainInfo['end_time'] = $bargainInfo['end_time'];
            $job = (new BargainFinishSendSmsNotice($userBargainInfo))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

            $openid = app('wxaTemplateMsg')->getOpenIdBy($userBargainInfo['user_id'], $userBargainInfo['wxa_appid']);
            if ($openid) {
                //发送小程序模版消息通知
                // $wxaTemplateMsgData = [
                //     'pay_money' => $userBargainInfo['price']/100 .'元',
                //     'item_name' => $userBargainInfo['item_name'],
                //     'cutdown_money' => $userBargainInfo['cutdown_amount']/100 . '元',
                //     'cutdown_time' => date('Y-m-d H:i:s', $userBargainInfo['end_time']),
                // ];
                // $sendData['scenes_name'] = 'cutdownSuccess';
                // $sendData['company_id'] = $params['company_id'];
                // $sendData['appid'] = $userBargainInfo['wxa_appid'];
                // $sendData['openid'] = $openid;
                // $sendData['data'] = $wxaTemplateMsgData;
                // $sendData['page_query_str'] = 'bargain_id='.$userBargainInfo['bargain_id'];
                // app('wxaTemplateMsg')->send($sendData);
            }
        }

        return $result;
    }

    private function getCutdownPrice(&$userBargainInfo)
    {
        foreach ($userBargainInfo['cutprice_range'] as &$v) {
            if (!$v['used']) {
                $v['used'] = 1;
                return $v['cut'];
            }
        }
        // 第一次砍价。肯定是整数，不能为负的
        /*
        if ($userBargainInfo['cutdown_amount'] == 0) {
            foreach ($userBargainInfo['cutprice_range'] as &$v) {
                if ($v['cut'] >= 0 && !$v['used']) {
                    $v['used'] = 1;
                    return $v['cut'];
                }
            }
        } else {
            // 其他次砍价
            foreach ($userBargainInfo['cutprice_range'] as &$v) {
                if ( !$v['used'] && (intval($userBargainInfo['cutdown_amount']) + intval($v['cut'])) >= 0) {
                    $v['used'] = 1;
                    return $v['cut'];
                }
            }
        }
        */
        throw new ResourceException(trans("PromotionsBundle.bargain_activity_completed_cannot_help"));
    }

    /**
     * @param $total integer // 总金额
     * @param $num integer   // 分成多少人
     * @param $min integer   // 每个人最少能收到多少钱
    **/
    /*
    private function getRandPriceNum($total, $num, $min)
    {
        $result = [];
        for ($i = 1; $i < $num; $i++)
        {
            $safe_total = ($total - ($num - $i) * $min) / ($num - $i); // 获得随机安全上限
            $money      = mt_rand($min, $safe_total);
            $total      = $total - $money;
            $result[]   = $money;
        }
        $result[] = $total;
        shuffle($result); // 再随机打散

        $data = [];
        foreach ($result as $k => $v) {
            $data[] = [
                'cut' => $v,
                'used' => 0
            ];
        }
        return $data;
    }
    */

    /**
     * @param $total integer // 总金额
     * @param $num integer   // 分成多少人
     * @param $min integer   // 每个人最少能收到多少钱
    **/
    private function getRandPriceNum($total, $num, $min)
    {
        if ($num == 1) {
            return [[
                'cut' => $total,
                'used' => 0
            ]];
        }
        $i = 1;
        $money = 0;
        $resultTotal = 0;
        $tmptotal = $total;
        while (true) {
            $safe_total = ($total - ($num - $i) * $min) / ($num - $i); // 获得随机安全上限
            $money = mt_rand($min, $safe_total);
            if ($i == 1 && $money < 0) {
                continue;
            }
            $total = $total - $money;
            $resultTotal += $money;
            if ($resultTotal > 0 && $total > 0 && $tmptotal > $money) {
                $result[] = $money;
                $i++;
            } else {
                $total += $money;
            }

            if ($i >= $num) {
                break;
            }
        }

        $result[] = $total;

        $data = [];
        foreach ($result as $k => $v) {
            $data[] = [
                'cut' => $v,
                'used' => 0
            ];
        }
        return $data;
    }

    public function getBargainFriendWxaCode($wxapp_id, $user_id, $bargain_id)
    {
        $data['page'] = 'boost/pages/flop/index';
        $scene = 'uid=' . $user_id . '&bid=' . $bargain_id;
        $app = $this->openPlatform->getAuthorizerApplication($wxapp_id);
        return $app->app_code->getUnlimit($scene, $data);
    }
}
