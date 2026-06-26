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

namespace OpenapiBundle\Services\Member;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService as CoreMemberService;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Exceptions\ServiceErrorException;
use OpenapiBundle\Services\BaseService;
use PointBundle\Entities\PointMember;
use PointBundle\Entities\PointMemberLog;
use PointBundle\Services\PointMemberService;
use PointBundle\Services\PointMemberShuyunOpenPlatformPointListService;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyPointChangelogSearchService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use ThirdPartyBundle\Services\DmCrm\PointService as DmCrmPointService;

class MemberPointService extends BaseService
{
    public function getEntityClass(): string
    {
        return PointMember::class;
    }

    public function logList(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = '*', bool $needCountSql = true)
    {
        $companyId = (int) ($filter['company_id'] ?? 0);
        $coreMemberService = app(CoreMemberService::class);
        $shuyunPointList = new PointMemberShuyunOpenPlatformPointListService(
            app(ShuyunOpenPlatformLoyaltyPointChangelogSearchService::class),
            app(CompanyShuyunOpenPlatformConfigRepository::class),
            app(ShuyunOpenPlatformShopSyncService::class),
            $coreMemberService,
        );

        if ($shuyunPointList->isShuyunOpenPlatformMemberEnabled($companyId)) {
            try {
                $shuyunPointList->assertEligibleOrThrow($companyId);
            } catch (ResourceException $e) {
                throw new ErrorException(ErrorCode::MEMBER_POINT_ERROR, $e->getMessage());
            }

            $userId = PointMemberShuyunOpenPlatformPointListService::extractSingleUserId($filter);
            $useShuyunChangelog = $userId !== null
                && !PointMemberShuyunOpenPlatformPointListService::hasPointLogDateRangeFilter($filter);

            if ($useShuyunChangelog) {
                $memberInfo = $coreMemberService->getMemberInfo([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                ]);
                if (empty($memberInfo) || empty($memberInfo['user_id'] ?? null)) {
                    $result = $this->getRepository(PointMemberLog::class)->lists($filter, $page, $pageSize, $orderBy);
                    $this->handlerListReturnFormat($result, $page, $pageSize);

                    return $result;
                }
                $regDistributor = (int) ($memberInfo['reg_distributor'] ?? 0);
                if ($regDistributor <= 0) {
                    throw new ErrorException(ErrorCode::MEMBER_POINT_ERROR, '会员缺少注册店铺信息，暂无法从数云查询积分明细');
                }
                try {
                    $raw = $shuyunPointList->buildListFromChangelog($companyId, $userId, $regDistributor, $page, $pageSize, null);
                } catch (ResourceException $e) {
                    throw new ErrorException(ErrorCode::MEMBER_POINT_ERROR, $e->getMessage());
                }
                $result = [
                    'list' => $this->normalizeShuyunChangelogRowsForPointMemberLogList($raw['list'], $userId),
                    'total_count' => $raw['total_count'],
                ];
                $this->handlerListReturnFormat($result, $page, $pageSize);

                return $result;
            }

            $result = $this->getRepository(PointMemberLog::class)->lists($filter, $page, $pageSize, $orderBy);
            $this->handlerListReturnFormat($result, $page, $pageSize);

            return $result;
        }

        $ns = new DmCrmSettingService();
        if ($ns->getDmCrmSetting($companyId)['is_open'] ?? '') {
            return $this->logListFromDmCrm($filter, $page, $pageSize, $orderBy);
        }

        $result = $this->getRepository(PointMemberLog::class)->lists($filter, $page, $pageSize, $orderBy);
        $this->handlerListReturnFormat($result, $page, $pageSize);

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $rows  mapChangelogRow 结果
     * @return list<array<string, mixed>>
     */
    private function normalizeShuyunChangelogRowsForPointMemberLogList(array $rows, int $userId): array
    {
        $out = [];
        foreach ($rows as $r) {
            $created = isset($r['created']) ? (int) $r['created'] : 0;
            $idRaw = $r['id'] ?? '';
            $id = is_numeric($idRaw) ? (int) $idRaw : abs(crc32((string) json_encode($r)));

            $out[] = [
                'id' => $id,
                'user_id' => $userId,
                'journal_type' => (int) ($r['journal_type'] ?? 0),
                'point_desc' => (string) ($r['point_desc'] ?? ''),
                'income' => (int) ($r['income'] ?? 0),
                'outcome' => (int) ($r['outcome'] ?? 0),
                'order_id' => (string) ($r['order_id'] ?? ''),
                'external_id' => '',
                'operater' => (string) ($r['operater'] ?? ''),
                'operater_remark' => '',
                'created' => $created,
            ];
        }

        return $out;
    }

    private function logListFromDmCrm(array $filter, int $page, int $pageSize, array $orderBy): array
    {
        $companyId = (int) ($filter['company_id'] ?? 0);
        $userId = PointMemberShuyunOpenPlatformPointListService::extractSingleUserId($filter);
        if ($userId === null && isset($filter['user_id'])) {
            $u = (int) $filter['user_id'];
            $userId = $u > 0 ? $u : null;
        }
        if ($userId === null || $userId <= 0) {
            $result = ['list' => [], 'total_count' => 0];
            $this->handlerListReturnFormat($result, $page, $pageSize);

            return $result;
        }

        $coreMemberService = app(CoreMemberService::class);
        $memberInfo = $coreMemberService->getMemberInfo([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);
        if (empty($memberInfo) || empty($memberInfo['user_id'] ?? null)) {
            $result = ['list' => [], 'total_count' => 0];
            $this->handlerListReturnFormat($result, $page, $pageSize);

            return $result;
        }

        $pointService = new DmCrmPointService($companyId);
        $paramsData = [
            'mobile' => $memberInfo['mobile'],
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'user_id' => $userId,
            'company_id' => $companyId,
        ];
        $pointList = $pointService->getPointDetailList($paramsData);
        $list = [];
        foreach ($pointList['items'] ?? [] as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }
            $created = (int) ($item['created'] ?? 0);
            $list[] = [
                'id' => abs(crc32((string) ($item['order_remark'] ?? '').(string) $created.(string) $idx)),
                'user_id' => $userId,
                'journal_type' => 0,
                'point_desc' => (string) ($item['point_desc'] ?? ''),
                'income' => (int) ($item['income'] ?? 0),
                'outcome' => (int) ($item['outcome'] ?? 0),
                'order_id' => (string) ($item['order_id'] ?? ''),
                'external_id' => '',
                'operater' => (string) ($item['operater'] ?? ''),
                'operater_remark' => '',
                'created' => $created,
            ];
        }

        if (isset($filter['created|gte'])) {
            $gte = (int) $filter['created|gte'];
            $list = array_values(array_filter($list, static fn (array $row) => ($row['created'] ?? 0) >= $gte));
        }
        if (isset($filter['created|lte'])) {
            $lte = (int) $filter['created|lte'];
            $list = array_values(array_filter($list, static fn (array $row) => ($row['created'] ?? 0) <= $lte));
        }

        $result = [
            'list' => $list,
            'total_count' => (int) ($pointList['totalCount'] ?? count($list)),
        ];
        if (isset($filter['created|gte']) || isset($filter['created|lte'])) {
            $result['total_count'] = count($list);
        }
        $this->handlerListReturnFormat($result, $page, $pageSize);

        return $result;
    }

    /**
     * 更新积分
     * @param array $filter
     * @param array $updateData
     */
    public function update(array $filter, array $updateData)
    {
        if (isset($updateData['increase_point'])) {
            $point = (int) $updateData['increase_point'];
            $status = true;
        } elseif (isset($updateData['decrease_point'])) {
            $point = (int) $updateData['decrease_point'];
            $status = false;
        } else {
            return;
        }
        if ($point < 0) {
            throw new ErrorException(ErrorCode::MEMBER_POINT_ERROR, '积分异常');
        }
        try {
            (new PointMemberService())->addPoint((int) $filter['user_id'], (int) $filter['company_id'], $point, PointMemberService::JOURNAL_TYPE_OPENAPI, $status, '', '', [
                // 操作员名称
                'operater' => CommonConstant::OPERATER,
                // 外部ID
                'external_id' => (string) ($updateData['external_id'] ?? ''),
                // 积分变动原因
                'operater_remark' => (string) $updateData['record'],
            ]);
        } catch (\Exception $exception) {
            throw new ServiceErrorException($exception);
        }
    }
}
