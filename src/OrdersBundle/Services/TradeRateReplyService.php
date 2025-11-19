<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use CompanysBundle\Entities\Operators;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Entities\TradeRate;
use OrdersBundle\Entities\TradeRateReply;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Dingo\Api\Exception\ResourceException;

class TradeRateReplyService
{
    public $tradeRateRepository;
    public $tradeRateReplyRepository;

    public function __construct()
    {
        $this->tradeRateRepository = app('registry')->getManager('default')->getRepository(TradeRate::class);
        $this->tradeRateReplyRepository = app('registry')->getManager('default')->getRepository(TradeRateReply::class);
    }

    public function create(array $data)
    {
        $filter = ['company_id' => $data['company_id'], 'rate_id' => $data['rate_id']];
        $createDate = [
            'company_id' => $data['company_id'],
            'rate_id' => $data['rate_id'],
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
                $createDate['unionid'] = $data['unionid'];
            }
            $result = $this->tradeRateReplyRepository->create($createDate);
            $conn->commit();
        } catch (BadRequestHttpException $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $result;
    }

    public function getList($filter, $page = 1, $pageSize = 100, $orderBy = array('operator_id' => 'DESC','created' => 'DESC'))
    {
        $lists = $this->tradeRateReplyRepository->lists($filter, $page, $pageSize, $orderBy);

        foreach ($lists['list'] as &$value) {
            if ($value['role'] == 'buyer' && $value['user_id']) {
                $wechatUserService = new WechatUserService();
                $wechatUser = $wechatUserService->getWechatUserInfo(['company_id' => $filter['company_id'], 'unionid' => $value['unionid']]);

                $value['username'] = isset($wechatUser['nickname']) ? str_limit($wechatUser['nickname'], 4, '***') : '';
            } elseif ($value['role'] == 'seller' && $value['operator_id']) {
                /*$operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);

                $operators = $operatorsRepository->getInfo(['operator_id'=>$value['operator_id']]);*/
                $value['username'] = '管理员回复';
            } else {
                $value['username'] = '';
            }
        }
        return $lists;
    }
}
