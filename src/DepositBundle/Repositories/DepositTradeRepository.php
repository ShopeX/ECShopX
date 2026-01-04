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

namespace DepositBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\Common\Collections\Criteria;
use DepositBundle\Entities\DepositTrade;

class DepositTradeRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'deposit_trade';

    /**
     * 创建储值充值消费记录
     *
     * @param int $companyId 企业ID
     */
    public function createDepositTrade($data)
    {
        if (isset($data['mobile'])) {
            $data['mobile'] = fixedencrypt($data['mobile']);
        }
        $conn = app('registry')->getConnection('default');
        if (isset($data['body'])) {
            unset($data['body']);
        }
        $result = $conn->insert($this->table, $data);
        $res = [];
        if ($result) {
            $res = $depositEnt = $this->findDepositTrade($data['deposit_trade_id']);
        }

        return $res;
    }

    public function findDepositTrade($depositTradeId)
    {
        $depositEnt = $this->find($depositTradeId);
        if (!$depositEnt) {
            throw new ResourceException("添加失败");
        }
        $res = [
            'deposit_trade_id' => $depositEnt->getDepositTradeId(),
            'company_id' => $depositEnt->getCompanyId(),
            'member_card_code' => $depositEnt->getMemberCardCode(),
            'shop_id' => $depositEnt->getShopId(),
            'shop_name' => $depositEnt->getShopName(),
            'user_id' => $depositEnt->getUserId(),
            'mobile' => $depositEnt->getMobile(),
            'open_id' => $depositEnt->getOpenId(),
            'money' => $depositEnt->getMoney(),
            'trade_type' => $depositEnt->getTradeType(),
            'authorizer_appid' => $depositEnt->getAuthorizerAppid(),
            'wxa_appid' => $depositEnt->getWxaAppid(),
            'detail' => $depositEnt->getDetail(),
            'time_start' => $depositEnt->getTimeStart(),
            'time_expire' => $depositEnt->getTimeExpire(),
            'trade_status' => $depositEnt->getTradeStatus(),
            'transaction_id' => $depositEnt->getTransactionId(),
            'bank_type' => $depositEnt->getBankType(),
            'recharge_rule_id' => $depositEnt->getRechargeRuleId(),
            'pay_type' => $depositEnt->getPayType(),
            'fee_type' => $depositEnt->getFeeType(),
            'cur_fee_type' => $depositEnt->getCurFeeType(),
            'cur_fee_symbol' => $depositEnt->getCurFeeSymbol(),
            'cur_pay_fee' => $depositEnt->getCurPayFee(),
        ];

        return $res;
    }

    /**
     * 更新支付状态
     */
    public function updateStatus($depositTradeId, $status = null, $options = array())
    {
        $data = $this->find($depositTradeId);

        if ($data->getTradeStatus() === 'SUCCESS') {
            throw new BadRequestHttpException('更新已处理，不需要更新');
        }

        if ($data && $data->getTradeStatus()) {
            $updateData = [
                'trade_status' => $status,
                'bank_type' => isset($options['bank_type']) ? $options['bank_type'] : null,
                'transaction_id' => isset($options['transaction_id']) ? $options['transaction_id'] : null,
                'pay_type' => isset($options['pay_type']) ? $options['pay_type'] : null,
                'time_expire' => time()
            ];


            $conn = app('registry')->getConnection('default');
            $conn->update($this->table, $updateData, ['deposit_trade_id' => $depositTradeId]);
            return $data;
        }
    }

    /**
     * 获取储值记录
     *
     * @param array $filter 查询条件
     */
    public function getDepositTradeList($filter, $pageSize = 20, $page = 1, $orderBy = ['time_start' => 'DESC'])
    {
        $filter = $this->fixedencryptCol($filter);
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if ($field == 'date_begin') {
                $criteria = $criteria->andWhere(Criteria::expr()->gte("time_start", $value));
            } elseif ($field == 'date_end') {
                $criteria = $criteria->andWhere(Criteria::expr()->lte("time_start", $value));
            } elseif (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $data = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $value = normalize($v);
                $data[] = $value;
            }
        }
        $res['list'] = $data;
        return $res;
    }

    /**
     * 充值总数
     * @param $userId
     * @return mixed
     */
    public function getDepositCountByUser($userId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $result = $qb->select('SUM(money) money_sum')
            ->from('deposit_trade')
            ->where('user_id = ' . $userId . " and trade_type = 'recharge' and trade_status = 'SUCCESS'")
            ->execute()->fetch();
        return $result;
    }

    /**
     * 批量统计充值总数
     * @param array $userIds
     * @return array
     */
    public function getDepositCountByUsers(array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $query = $qb->select('user_id,SUM(money) money_sum')
            ->from('deposit_trade');

        array_walk($userIds, function (&$colVal) use (&$qb) {
            $colVal = $qb->expr()->literal($colVal);
        });
        return $query->andWhere($qb->expr()->in("user_id", $userIds))
            ->andWhere($qb->expr()->eq("trade_status", $qb->expr()->literal("SUCCESS")))
            ->andWhere($qb->expr()->eq("trade_type", $qb->expr()->literal("recharge")))
            ->groupBy("user_id")
            ->execute()->fetchAll();
    }

    /**
     * 对filter中的部分字段，加密处理
     * @param  [type] $filter [description]
     * @return [type]         [description]
     */
    private function fixedencryptCol($filter)
    {
        $fixedencryptCol = ['mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        return $filter;
    }
}
