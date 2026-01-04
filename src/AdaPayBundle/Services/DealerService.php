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

namespace AdaPayBundle\Services;

use CompanysBundle\Entities\Operators;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\DistributorService;
use PromotionsBundle\Services\SmsManagerService;

class DealerService
{
    public const AUDIT_WAIT_MAIN = '0';//待云店审批
    public const AUDIT_WAIT = 'A';//待审核
    public const AUDIT_FAIL = 'B';//审核失败
    public const AUDIT_MEMBER_FAIL = 'C';//开户失败
    public const AUDIT_ACCOUNT_FAIL = 'D';//开户成功但未创建结算账户
    public const AUDIT_SUCCESS = 'E';//开户和创建结算账户成功
    public $operatorsRepository;
    public $distributorRepository;

    public function __construct()
    {
        $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    //初始化经销商字段数据：dealer_parent_id
    public function initDealerData()
    {
        // Built with ShopEx Framework
        $operatorId = app('auth')->user()->get('operator_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'dealer') {
            $operatorInfo = $this->operatorsRepository->getInfo(['operator_id' => $operatorId]);
            if ($operatorInfo['dealer_parent_id']) {
                return true;
            }

            $this->operatorsRepository->updateOneBy(['operator_id' => $operatorInfo['operator_id']], ['dealer_parent_id' => $operatorInfo['operator_id']]);
        }
    }

    public function isDealerMain($operatorId)
    {
        $info = $this->operatorsRepository->getInfo(['operator_id' => $operatorId]);
        if ($info['is_dealer_main']) {
            return true;
        } else {
            return false;
        }
    }
    public function dealerListService($companyId, $params, $page, $pageSize, $orderBy = ['created' => 'DESC'])
    {
        $filter['company_id'] = $companyId;
        $filter['operator_type'] = 'dealer';

        $memberService = new MemberService();
        $operator = $memberService->getOperator();
        if ($operator['operator_type'] == 'dealer') {
            $filter['dealer_parent_id'] = $operator['operator_id'];
            $orderBy = ['created' => 'ASC'];
            $this->initDealerData();
        } else {
            $filter['is_dealer_main'] = '1';
        }
        if (isset($params['username']) && $params['username']) {
            $filter['username|contains'] = $params['username'];
        }

        if (isset($params['contact']) && $params['contact']) {
            $filter['contact'] = $params['contact'];
        }

        if (isset($params['mobile']) && $params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }

        if (isset($params['time_start']) && isset($params['time_end']) && $params['time_start'] && $params['time_end']) {
            $filter['created|gte'] = $params['time_start'];
            $filter['created|lte'] = $params['time_end'];
        }

        if (isset($params['open_account_start']) && isset($params['open_account_end']) && $params['open_account_start'] && $params['open_account_end']) {
            $filter['adapay_open_account_time|gte'] = $params['open_account_start'];
            $filter['adapay_open_account_time|lte'] = $params['open_account_end'];
        }

        $res = $this->operatorsRepository->lists($filter, $orderBy, $pageSize, $page);
        $res['count'] = $res['total_count'];
        return $res;
    }

    public function dealerInfo($id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $memberService = new MemberService();
        $result = $memberService->getMemberInfo(['operator_id' => $id, 'operator_type' => 'dealer', 'company_id' => $companyId]);
        return $result;
    }

    public function getDistributorList($params)
    {
        $filter = [];
        $audit_state = [];
        if ($params['company_id'] ?? 0) {
            $filter['company_id'] = $params['company_id'];
        }

        if ($params['name'] ?? 0) {
            $filter['name|contains'] = $params['name'];
        }

        if ($params['contact'] ?? 0) {
            $filter['contact'] = $params['contact'];
        }

        if ($params['mobile'] ?? 0) {
            $filter['mobile'] = $params['mobile'];
        }


        if (($params['time_start'] ?? 0) && ($params['time_end'] ?? 0)) {
            $filter['created|gte'] = $params['time_start'];
            $filter['created|lte'] = $params['time_end'];
        }

        if (isset($params['dealer_id'])) { //主商户平台查看经销商关联店铺, 带上经销商id
            $filter['dealer_id'] = $params['dealer_id'];
        }

        if ($params['province'] ?? 0) {
            $filter['province'] = $params['province'];
        }

        if ($params['city'] ?? 0) {
            $filter['city'] = $params['city'];
        }

        if ($params['area'] ?? 0) {
            $filter['area'] = $params['area'];
        }
        $mer_name = (new AdapayTradeService())->getMerName();
        $auditState = $params['audit_state'] ?? '';
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 0;
        $auth = app('auth')->user()->get();
        if (isset($auth['operator_type']) && $auth['operator_type'] == 'dealer') { //经销商端, 查看关联店铺列表。经销商id从auth里获取
            $memberService = new MemberService();
            $operator = $memberService->getOperator();
            $filter['dealer_id'] = $operator['operator_id'];
        }
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('distribution_distributor', 'd')
            ->leftJoin('d', 'adapay_member', 'm', 'd.distributor_id = m.operator_id and m.company_id = ' .
                $params['company_id'] . ' and m.operator_type = ' . $criteria->expr()->literal("distributor"))
            ->leftJoin('d', 'operators', 'o', 'd.dealer_id = o.operator_id');

        //状态映射 todo
        if ($auditState) {
            if ($auditState == '1') {
                $criteria->where($criteria->expr()->in('audit_state', ["'B'", "'C'", "'D'"]));
                $criteria = $criteria->orWhere(
                    $criteria->expr()->andX(
                        $criteria->expr()->isNull('audit_state')
                    )
                );
            } elseif ($auditState == '2') {
                $criteria->where($criteria->expr()->in('audit_state', ["'A'","'0'"]));
            } else {
                $criteria->where($criteria->expr()->eq('audit_state', $criteria->expr()->literal('E')));
            }
        }
        if ($params['adapay_fee_mode'] ?? 0) {
            $criteria->andWhere("JSON_EXTRACT(d.split_ledger_info ,'$.adapay_fee_mode') =:value")
            ->setParameter('value', $params['adapay_fee_mode']);
        }
        $this->getFilter($filter, $criteria);
        $count = $criteria->execute()->fetchColumn();
        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)
              ->setMaxResults($pageSize);
        }
        $row = 'd.distributor_id,name,d.contact,d.mobile,d.split_ledger_info,d.address,d.created,audit_state,o.username,o.operator_id';
        $lists = $criteria->select($row)->execute()->fetchAll();
        $mer_name = (new AdapayTradeService())->getMerName();
        array_walk($lists, function (&$row) use ($mer_name) {
            $row['audit_state'] = $this->handleAuditState($row['audit_state']);
            $row['audit_state_name'] = $this->handleAuditStateName($row['audit_state']);
            $row['split_ledger_info'] = json_decode($row['split_ledger_info'], true);
            $row['mer_name'] = $mer_name;
            $row['mobile'] = fixeddecrypt(fixeddecrypt($row['mobile']));
            $row['contact'] = fixeddecrypt(fixeddecrypt($row['contact']));
        });
        $result['list'] = $lists;
        $result['count'] = intval($count);
        return $result;
    }

    public function handleAuditState($auditState = '')
    {
        switch ($auditState) {
            case self::AUDIT_WAIT:
            case self::AUDIT_WAIT_MAIN:
                $auditState = '2';//待审核
                break;
            case self::AUDIT_SUCCESS:
                $auditState = '3';//审核通过
                break;
            default:
                $auditState = '1';//未入网
        }

        return $auditState;
    }

    public function handleAuditStateName($auditState = '')
    {
        switch ($auditState) {
            case self::AUDIT_WAIT:
            case self::AUDIT_WAIT_MAIN:
                $auditState = '审核中';
                break;
            case self::AUDIT_SUCCESS:
                $auditState = '入网成功';
                break;
            case self::AUDIT_MEMBER_FAIL:
            case self::AUDIT_FAIL:
                $auditState = '入网失败';
                break;
            default:
                $auditState = '未入网';
        }

        return $auditState;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function getFilter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k("d.".$v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in("d.".$field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq("d.".$field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    public function dealerRelDistributorService($companyId, $params)
    {
//        $distributorIds = json_decode($params['distributor_ids'], true);
        $memberService = new MemberService();
        $memberInfo = $memberService->getInfo(['company_id' => $companyId, 'operator_id' => $params['distributor_id'], 'operator_type' => 'distributor', 'audit_state' => 'E']);

        $operatorInfo = $this->operatorsRepository->getInfo(['company_id' => $companyId, 'operator_id' => $params['operator_id']]);

        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']]);

        if ($params['is_rel']) {
            if ($memberInfo) {
//                if (!$params['headquarters_proportion'] || !$params['dealer_proportion']) {
//                    throw new ResourceException("分账占比设置必填");
//                }

                if ($params['headquarters_proportion'] + $params['dealer_proportion'] > 100) {
                    throw new ResourceException("分账占比设置必须小于等于 100 %");
                }

                $splitLedgerInfo = json_decode($distributorInfo['split_ledger_info'], true);
                $splitLedgerInfo['headquarters_proportion'] = $params['headquarters_proportion'];
                $splitLedgerInfo['dealer_proportion'] = $params['dealer_proportion'];
//                $splitLedgerInfo['headquarters_proportion'] = 100 - $splitLedgerInfo['distributor_proportion'] - $splitLedgerInfo['dealer_proportion'];
                $splitLedgerInfo = json_encode($splitLedgerInfo);

                $distributorService->updateBy(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']], ['dealer_id' => $params['operator_id'], 'split_ledger_info' => $splitLedgerInfo]);
            } else {
                $distributorService->updateBy(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']], ['dealer_id' => $params['operator_id']]);
            }


            if ($operatorInfo['distributor_ids']) {
                $distributorIds = $operatorInfo['distributor_ids'];
                $data = [
                    'name' => $params['name'],
                    'distributor_id' => $params['distributor_id'],
                ];
                array_push($distributorIds, $data);
//                $distributorIds = json_encode($distributorIds);
            } else {
                $distributorIds = [
                    [
                        'name' => $params['name'],
                        'distributor_id' => $params['distributor_id'],
                    ]
                ];
            }

            $this->operatorsRepository->updateOneBy(['company_id' => $companyId, 'operator_id' => $params['operator_id']], ['distributor_ids' => $distributorIds]);
        } else {
            if ($memberInfo) {
//                if (!$params['headquarters_proportion']) {
//                    throw new ResourceException("分账占比设置必填");
//                }

                if ($params['headquarters_proportion'] > 100) {
                    throw new ResourceException("分账占比设置必须小于等于 100 %");
                }

                $splitLedgerInfo = json_decode($distributorInfo['split_ledger_info'], true);
                $splitLedgerInfo['headquarters_proportion'] = $params['headquarters_proportion'];
                $splitLedgerInfo['dealer_proportion'] = '';
//                $splitLedgerInfo['headquarters_proportion'] = 100 - $splitLedgerInfo['distributor_proportion'];
                $splitLedgerInfo = json_encode($splitLedgerInfo);

                $distributorService->updateBy(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']], ['dealer_id' => 0, 'split_ledger_info' => $splitLedgerInfo]);
            } else {
                $distributorService->updateBy(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']], ['dealer_id' => 0]);
            }
            $distributorIds = $operatorInfo['distributor_ids'];
            foreach ($distributorIds as $k => $distributorId) {
                if ($distributorId['distributor_id'] == $params['distributor_id']) {
                    unset($distributorIds[$k]);
                }
            }
//            $distributorIds = json_encode($distributorIds);
            $this->operatorsRepository->updateOneBy(['company_id' => $companyId, 'operator_id' => $params['operator_id']], ['distributor_ids' => $distributorIds]);
        }


        $logParams = [
            'company_id' => $companyId,
            'is_rel' => $params['is_rel'],
            'dealer_name' => $operatorInfo['username'],
            'distributor_name' => $distributorInfo['name'],
        ];
        $adapayLogService = new AdapayLogService();

        if (isset($operatorInfo['is_dealer_main']) && !$operatorInfo['is_dealer_main']) {
            $relDealerId = $operatorInfo['dealer_parent_id'];
        } else {
            $relDealerId = $params['operator_id'];
        }

        $adapayLogService->logRecord($logParams, $params['distributor_id'], 'dealer/rel/distributor', 'distributor');
        $adapayLogService->logRecord($logParams, $relDealerId, 'dealer/rel/dealer', 'dealer');

        return ['status' => true];
    }

    public function resetPasswordService($companyId, $operatorId)
    {
        $data['password'] = $this->generate_password();

        $operatorsService = new OperatorsService();
        $rs = $operatorsService->updateOperator($operatorId, $data);

        //总商户重置经销商随机密码发送短信
        try {
            $data = ['dealer' => $rs['username'], 'password' => $data['password']];
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($rs['mobile'], $companyId, 'dealer_account_reset_pwd', $data);
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }

        $logParams = [
            'company_id' => $companyId,
            'name' => $rs['username']
        ];
        if (isset($rs['is_dealer_main']) && !$rs['is_dealer_main']) {
            $relDealerId = $rs['dealer_parent_id'];
        } else {
            $relDealerId = $rs['operator_id'];
        }
        $relMerchantId = app('auth')->user()->get('operator_id');
        (new AdapayLogService())->logRecord($logParams, $relMerchantId, 'dealer/reset', 'merchant');
        (new AdapayLogService())->logRecord($logParams, $relDealerId, 'dealer/reset', 'dealer');

        return ['status' => true];
    }
    public function generate_password($length = 12)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }

        return $password;
    }
}
