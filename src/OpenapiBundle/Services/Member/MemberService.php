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

use Dingo\Api\Exception\UpdateResourceFailedException;
use Doctrine\DBAL\Query\QueryBuilder;
use KaquanBundle\Services\{
    MemberCardService,
};
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersAssociations;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Data\MemberOperateLogData;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Exceptions\ServiceErrorException;
use OpenapiBundle\Services\BaseService;
use OpenapiBundle\Services\Salesperson\ShopSalespersonService;
use OpenapiBundle\Services\Salesperson\WorkWechatRelService;
use SalespersonBundle\Services\SalespersonService;
use SalespersonBundle\Services\SalespersonTaskRecordService;
use PromotionsBundle\Jobs\FirePromotionsActivity;

class MemberService extends BaseService
{
    public function getEntityClass(): string
    {
        return Members::class;
    }

    /**
     * 来源渠道的映射关键
     */
    public const SOURCE_FROM_MAP = [
        "default" => "小程序/后台渠道",
        "openapi" => "公共接口"
    ];

    /**
     * 会员主表的别名
     * @var string
     */
    protected $aliasMembers = "member";

    /**
     * 会员详情信息表的别名
     * @var string
     */
    protected $aliasMembersInfo = "info";

    /**
     * 会员关联导购表的别名
     * @var string
     */
    protected $aliasWorkWechatRel = "rel_salesperson";

    /**
     * 会员关联标签表的别名
     * @var string
     */
    protected $aliasMembersRelTags = "rel_tag";

    /**
     * 不同平台会员关联表的别名
     * @var string
     */
    protected $aliasMemberAssociations = "associations";

    /**
     * 会员等级表的别名
     * @var string
     */
    protected $aliasMembercardGrade = "membercard_grade";

    /**
     * 会员与付费等级的关联绑定表的别名
     * @var string
     */
    protected $aliasVipRelUser = "vip_rel_user";

    /**
     * @return string
     */
    public function getAliasMembers(): string
    {
        return $this->aliasMembers;
    }

    /**
     * @return string
     */
    public function getAliasMembersInfo(): string
    {
        return $this->aliasMembersInfo;
    }

    /**
     * @return string
     */
    public function getAliasWorkWechatRel(): string
    {
        return $this->aliasWorkWechatRel;
    }

    /**
     * @return string
     */
    public function getAliasMembersRelTags(): string
    {
        return $this->aliasMembersRelTags;
    }

    /**
     * @return string
     */
    public function getAliasMemberAssociations(): string
    {
        return $this->aliasMemberAssociations;
    }

    /**
     * @return string
     */
    public function getAliasVipRelUser(): string
    {
        return $this->aliasVipRelUser;
    }

    /**
     * 获取会员列表(基于join连接的关联查询)
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array|string[] $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @return array
     */
    public function listWithJoin(array $filter, int $page = 1, int $pageSize = 10, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        if (isset($filter['mobile'])) {
            $filter['mobile'] = fixedencrypt($filter['mobile']);
        }
        if (isset($filter['username'])) {
            $filter['username'] = fixedencrypt($filter['username']);
        }

        if ($cols == "*") {
            // 查询的列需要是 表与表之间是一对一的关心，如果存在一对多的关系会出现多条重复的数据
            $colsArray = [
                $this->aliasMembers => ["mobile", "source_from", "disabled", "created", "updated", "remarks", "inviter_id", "grade_id", "user_card_code"],
                $this->aliasMembersInfo => ["username", "avatar", "sex", "birthday", "habbit", "edu_background", "income", "industry", "email", "address"],
                $this->aliasMemberAssociations => ["unionid"],
                $this->aliasMembercardGrade => ["grade_name"],
            ];
            $cols = sprintf("distinct %s.user_id,", $this->aliasMembers);
            foreach ($colsArray as $alias => $item) {
                foreach ($item as $field) {
                    $cols .= sprintf("%s.%s,", $alias, $field);
                }
            }
            $cols = trim($cols, ",");
        } else {
            $cols = sprintf("distinct %s.user_id,%s", $this->aliasMembers, $cols);
        }
        // 获取query对象
        $queryBuilder = app('registry')->getConnection('default')->createQueryBuilder()
            ->from($this->getRepository()->table, $this->aliasMembers)
            // 关联会员详情表
            ->leftJoin($this->aliasMembers, (new MemberInfoService())->getRepository()->table, $this->aliasMembersInfo, sprintf("%s.user_id = %s.user_id", $this->aliasMembersInfo, $this->aliasMembers))
            // 关联会员微信表
            ->leftJoin($this->aliasMembers, "members_associations", $this->aliasMemberAssociations, sprintf("%s.user_id = %s.user_id", $this->aliasMemberAssociations, $this->aliasMembers))
            // 关联会员等级表
            ->leftJoin($this->aliasMembers, "membercard_grade", $this->aliasMembercardGrade, sprintf("%s.grade_id = %s.grade_id", $this->aliasMembercardGrade, $this->aliasMembers));
        // 添加过滤条件
        $this->filter($filter, $queryBuilder);
        // 获取总数量, 没有用COUNT(*)是因为关联查询存在一对多的情况，出现了主表中重复数据的情况
        if ($needCountSql) {
            $count = count($queryBuilder->select(sprintf("distinct %s.user_id", $this->aliasMembers))->execute()->fetchAll());
        } else {
            $count = 0;
        }
        // 添加筛选字段与分页条件
        $queryBuilder->select($cols);
        // 排序方式
        if (empty($orderBy)) {
            $queryBuilder->addOrderBy($this->aliasMembers . ".user_id", "DESC");
        } else {
            foreach ($orderBy as $key => $value) {
                $queryBuilder->addOrderBy($key, $value);
            }
        }
        // 分页逻辑
        if ($page > 0) {
            $queryBuilder->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }
        // 返回列表结果集
        $result = ["list" => $queryBuilder->execute()->fetchAll(), "total_count" => $count];
        foreach($result['list'] as &$v) {
            if (isset($v['mobile'])) {
                $v['mobile'] = fixeddecrypt($v['mobile']);
            }
            if (isset($v['username'])) {
                $v['username'] = fixeddecrypt($v['username']);
            }
        }
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 查询时的过滤条件
     * @param array $filter 过滤条件
     * @param QueryBuilder $queryBuilder 查询数据的对象
     */
    protected function filter(array $filter, QueryBuilder $queryBuilder, ?string $entityClass = null)
    {
        $companyId = (int)$filter["company_id"];
        // 通过手机号过滤
        $queryBuilder->andWhere($queryBuilder->expr()->eq($this->aliasMembers . ".company_id", $queryBuilder->expr()->literal($companyId)));
        // 通过推荐人id
        if (isset($filter["inviter_id"]) && !empty($filter["inviter_id"])) {
            if (is_array($filter["inviter_id"])) {
                array_walk($filter["inviter_id"], function (&$colVal) use (&$queryBuilder) {
                    $colVal = $queryBuilder->expr()->literal($colVal);
                });
                $queryBuilder->andWhere($queryBuilder->expr()->in("inviter_id", $filter["inviter_id"]));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq("inviter_id", $queryBuilder->expr()->literal($filter["inviter_id"])));
            }
        }
        // 通过会员等级id过滤
        if (isset($filter["grade_id"])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq("grade_id", $queryBuilder->expr()->literal($filter["grade_id"])));
        }
        // 通过用户id过滤
        if (isset($filter["user_id"]) && !empty($filter["user_id"])) {
            if (is_array($filter["user_id"])) {
                array_walk($filter["user_id"], function (&$colVal) use (&$queryBuilder) {
                    $colVal = $queryBuilder->expr()->literal($colVal);
                });
                $queryBuilder->andWhere($queryBuilder->expr()->in($this->aliasMembers . ".user_id", $filter["user_id"]));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($this->aliasMembers . ".user_id", $queryBuilder->expr()->literal($filter["user_id"])));
            }
        }
        // 通过手机号过滤
        if (isset($filter["mobile"]) && !empty($filter["mobile"])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq("mobile", $queryBuilder->expr()->literal($filter["mobile"])));
        }
        // 通过渠道类型过滤
        if (isset($filter["source_from"]) && !empty($filter["source_from"])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq("source_from", $queryBuilder->expr()->literal($filter["source_from"])));
        }
        // 通过是否禁用过滤
        if (isset($filter["disabled"]) && $filter["disabled"] > -1) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq("disabled", $queryBuilder->expr()->literal($filter["disabled"])));
        }
        // 通过会员卡号过滤
        if (isset($filter["card_code"]) && !empty($filter["card_code"])) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq("user_card_code", $queryBuilder->expr()->literal($filter["card_code"])));
        }
        // 通过创建时间过滤
        $createStart = $filter["created_start"] ?? null;
        $createEnd = $filter["created_end"] ?? null;
        if ($createStart && $createEnd) {
            $queryBuilder->andWhere($queryBuilder->expr()->andX(
                $queryBuilder->expr()->gte($this->aliasMembers . ".created", $queryBuilder->expr()->literal($createStart)),
                $queryBuilder->expr()->lte($this->aliasMembers . ".created", $queryBuilder->expr()->literal($createEnd))
            ));
        } elseif ($createStart && !$createEnd) {
            $queryBuilder->andWhere($queryBuilder->expr()->andX(
                $queryBuilder->expr()->gte($this->aliasMembers . ".created", $queryBuilder->expr()->literal($createStart))
            ));
        } elseif (!$createStart && $createEnd) {
            $queryBuilder->andWhere($queryBuilder->expr()->andX(
                $queryBuilder->expr()->lte($this->aliasMembers . ".created", $queryBuilder->expr()->literal($createEnd))
            ));
        }
        // 通过是否有过消费记录过滤
        if (isset($filter["have_consume"]) && $filter["have_consume"] > -1) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($this->aliasMembersInfo . ".have_consume", $queryBuilder->expr()->literal($filter["have_consume"])));
        }
        // 通过导购id查询
        if (isset($filter["salesperson_id"]) && $filter["salesperson_id"] > 0) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq($this->aliasWorkWechatRel . ".salesperson_id", $queryBuilder->expr()->literal($filter["salesperson_id"])))
                ->andWhere($queryBuilder->expr()->eq($this->aliasWorkWechatRel . ".is_bind", $queryBuilder->expr()->literal(1)))
                ->andWhere($queryBuilder->expr()->eq($this->aliasWorkWechatRel . ".company_id", $queryBuilder->expr()->literal($companyId)));
        }
        // 通过标签id查询
        if (isset($filter["tag_id"]) && $filter["tag_id"] > 0) {
            $queryBuilder
                ->leftJoin($this->aliasMembers, "members_rel_tags", $this->aliasMembersRelTags, sprintf("%s.user_id = %s.user_id", $this->aliasMembersRelTags, $this->aliasMembers))
                ->andWhere($queryBuilder->expr()->eq($this->aliasMembersRelTags . ".tag_id", $queryBuilder->expr()->literal($filter["tag_id"])))
                ->andWhere($queryBuilder->expr()->eq($this->aliasMembersRelTags . ".company_id", $queryBuilder->expr()->literal($companyId)));
        }
        // 通过付费会员等级id过滤
        if (isset($filter["vip_grade_id"]) && $filter["vip_grade_id"] > 0) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq($this->aliasVipRelUser . ".vip_grade_id", $queryBuilder->expr()->literal($filter["vip_grade_id"])))
                ->andWhere($queryBuilder->expr()->eq($this->aliasVipRelUser . ".company_id", $queryBuilder->expr()->literal($companyId)))
                ->andWhere($queryBuilder->expr()->gt($this->aliasVipRelUser . ".end_date", $queryBuilder->expr()->literal(time())));
        }
    }

    /**
     * 查询会员信息，不通过关联查询
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @param string $cols
     * @param bool $needCountSql
     * @return array
     */
    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = $this->getRepository()->lists($filter, $orderBy, $pageSize, $page, $needCountSql);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * @param array $params
     */
    public function createDetail(int $companyId, array $params): array
    {
        $params["company_id"] = $companyId;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建会员
            $member = $this->create($params);
            // 判断用户id
            $params["user_id"] = (int)($member["user_id"] ?? 0);
            if ($params["user_id"] <= 0) {
                throw new ErrorException(ErrorCode::MEMBER_ERROR);
            }
            // 创建会员具体信息
            (new MemberInfoService())->create($params);
            // 创建会员标签
            (new MemberTagService())->create($params);
            // 导购的绑定
            // 获取导购id
            $salespersonId = 0;
            // 获取导购名字
            $salespersonMobile = (string)($params["salesperson_mobile"] ?? "");
            if (!empty($salespersonMobile)) {
                $this->checkSalesperson($companyId, $salespersonMobile, $salespersonId);
            }
            if ($salespersonId > 0) {
                // 获取导购的绑定服务
                $workWechatRelService = new WorkWechatRelService();
                //查找用户已绑定的导购员
                $boundInfo = $workWechatRelService->find(["company_id" => $companyId, "user_id" => $params["user_id"], "is_bind" => 1]);
                if (!empty($boundInfo)) {
                    $bindSalespersonId = $boundInfo["salesperson_id"] ?? 0;
                    if ($bindSalespersonId == $salespersonId) {
                        throw new ErrorException(ErrorCode::SALESPERSON_RELATION_MEMBER_EXIST, "会员已与该导购绑定");
                    } else {
                        throw new ErrorException(ErrorCode::SALESPERSON_RELATION_MEMBER_EXIST, "会员已与其他导购绑定");
                    }
                }
                // 绑定导购
                $filter = [
                    "user_id" => $params["user_id"],
                    "company_id" => $companyId,
                    "salesperson_id" => $salespersonId,
                ];
                $isBound = $workWechatRelService->find($filter);
                if ($isBound) {
                    $workWechatRelService->updateBatch($filter, ['is_bind' => 1]);
                } else {
                    $workWechatRelService->create(array_merge($filter, ["is_bind" => 1]));
                }
                // 记录日志
                (new \WorkWechatBundle\Services\WorkWechatRelService())->saveWorkWechatRelLogs([
                    'company_id' => $companyId,
                    'salesperson_id' => $salespersonId,
                    'unionid' => $params["unionid"] ?? "",
                    'user_id' => $params["user_id"],
                    'is_friend' => 0,
                    'is_bind' => 1,
                    'bound_time' => time(),
                    'add_friend_time' => 0
                ]);
                // 存在导购id才会计算完成导购拉新任务
                (new SalespersonTaskRecordService())->completeNewUser([
                    'company_id' => $companyId,
                    'salesperson_id' => $salespersonId,
                    'user_id' => $params["user_id"],
                ]);
                // 增加导购的会员数量
                (new SalespersonService())->increaseSalespersonMemberNum($companyId, $params["inviter_id"] ?? 0, $params["user_id"]);
            }
            // 关联用户和微信信息
            if (!empty($params["unionid"])) {
                $this->getRepository(MembersAssociations::class)->create([
                    'user_type' => "wechat",
                    'company_id' => $companyId,
                    'unionid' => $params['unionid'],
                    'user_id' => $params["user_id"],
                ]);
            }
            $conn->commit();
            $result = $member;
        } catch (ErrorException $errorException) {
            $conn->rollback();
            throw $errorException;
        } catch (\Throwable $throwable) {
            $conn->rollback();
            throw new ServiceErrorException($throwable);
        }

        return $result;
        // 标签
//        new MemberTagsService;
        // 会员卡信息
//        new MemberCardService;
//        app('registry')->getManager('default')->getRepository(MemberCard::class);
//        app('registry')->getManager('default')->getRepository(MemberCardGrade::class);
        // 积分
//        new PointMemberService;
//        app('registry')->getManager('default')->getRepository(PointMember::class);
//        app('registry')->getManager('default')->getRepository(PointMemberLog::class);
//        app('registry')->getManager('default')->getRepository(NormalOrders::class);
//        app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        // 储值
//        new DepositTrade;
        // 身份证
    }

    /**
     * 创建会员信息
     * @param int $companyId
     * @param array $createData
     * @return array
     * @throws \Exception
     */
    public function create(array $createData): array
    {
        // 会员内容
        $memberData = [
            "company_id" => (int)($createData["company_id"] ?? -1), // 企业id
            "grade_id" => (int)($createData["grade_id"] ?? -1), // 会员等级
            "mobile" => (string)($createData["mobile"] ?? ""), // 手机号
            "region_mobile" => (string)($createData["mobile"] ?? ""), // 带区号的手机号
            "mobile_country_code" => "86", // 手机的区号
            "password" => (string)substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10),
            "user_card_code" => (string)($createData["card_code"] ?? ""), // 会员卡号可用让用户从外部输入，这时需要去查询一遍，确保唯一性
            "offline_card_code" => "", // 线下会员卡号
            "authorizer_appid" => "", // 公众号的appid
            "wxa_appid" => "", // 小程序的appid
            "inviter_id" => 0, // 推荐人的id
            "source_from" => $createData["source_from"] ?? "default", // 渠道来源
            "disabled" => $createData["status"] == 1 ? 0 : 1, // 是否禁用 【0 可用】【1 禁用】
            "remarks" => $createData["remarks"] ?? "", // 会员备注
            "use_point" => null,
            "third_data" => null,
            "source_id" => 0,
            "monitor_id" => 0,
            "latest_source_id" => 0,
            "latest_monitor_id" => 0,
        ];
        // 插入前的验证
        $this->checkGrade($memberData["company_id"], $memberData["grade_id"])
            ->checkUserCardCode($memberData["company_id"], $memberData["user_card_code"])
            ->checkMobile($memberData["company_id"], $memberData["mobile"])
            ->checkInviter($memberData["company_id"], (string)($createData["inviter_mobile"] ?? ""), $memberData["inviter_id"]);
        // 插入数据
        return $this->getRepository()->create($memberData);
    }

    /**
     * 检查导购员
     * @param int $companyId 企业id
     * @param int $salespersonMobile 导购手机号
     * @param int $salespersonId 导购ID
     * @return $this
     */
    protected function checkSalesperson(int $companyId, string $salespersonMobile, int &$salespersonId)
    {
        if (!empty($salespersonMobile)) {
            $salesperson = (new ShopSalespersonService())->find(["company_id" => $companyId, "mobile" => $salespersonMobile]);
            if (empty($salesperson)) {
                throw new ErrorException(ErrorCode::SALESPERSON_NOT_FOUND);
            }
            $salespersonId = (int)($salesperson["salesperson_id"] ?? 0);
        } else {
            $salespersonId = 0;
        }
        return $this;
    }

    /**
     * 检查会员等级
     * @param int $companyId 企业id
     * @param int $gradeId 会员等级
     * @return $this
     */
    protected function checkGrade(int $companyId, int &$gradeId): self
    {
        // 获取会员等级
        $memberCardService = new MemberCardService();
        if ($gradeId > 0) {
            $gradeInfo = $memberCardService->getGradeInfo(["company_id" => $companyId, "grade_id" => $gradeId]);
            if (empty($gradeInfo)) {
                throw new ErrorException(ErrorCode::MEMBER_GRADE_NOT_FOUND);
            }
        } else {
            $defaultGradeInfo = $memberCardService->getDefaultGradeByCompanyId($companyId);
            $gradeId = $defaultGradeInfo['grade_id'] ?? 0;
        }
        return $this;
    }

    /**
     * 检查会员卡号是否存在
     * @param int $companyId
     * @param string $userCardCode
     * @return $this
     * @throws \Exception
     */
    protected function checkUserCardCode(int $companyId, string &$userCardCode, string $mobile = ""): self
    {
        // 生成会员卡号 并判断卡号是否存在
        if (empty($userCardCode)) {
            $userCardCode = (new \MembersBundle\Services\MemberService())->getCode();
        }
        $userInfo = $this->find(["company_id" => $companyId, "user_card_code" => $userCardCode]);
        // 如果查询到用户 且 （手机号为空 或者 手机号不同）
        if (!empty($userInfo) && (empty($mobile) || $mobile != $userInfo["mobile"])) {
            throw new ErrorException(ErrorCode::MEMBER_CARD_EXIST);
        }
        return $this;
    }

    /**
     * 检查手机号是否存在
     * @param int $companyId
     * @param string $mobile
     * @return $this
     */
    protected function checkMobile(int $companyId, string &$mobile): self
    {
        $mobileCount = $this->getRepository()->count(["company_id" => $companyId, "mobile" => $mobile]);
        if ($mobileCount > 0) {
            throw new ErrorException(ErrorCode::MEMBER_EXIST, "会员手机号已存在");
        }
        return $this;
    }

    /**
     * 验证推荐人是否存在
     * @param int $companyId 企业id
     * @param int $inviterId 推荐人id
     * @return $this
     */
    protected function checkInviter(int $companyId, string $inviterMobile, int &$inviterId): self
    {
        if (!empty($inviterMobile)) {
            $inviter = $this->find(["company_id" => $companyId, "mobile" => $inviterMobile]);
            if (empty($inviter)) {
                throw new ErrorException(ErrorCode::MEMBER_INVITER_NOT_FOUND);
            }
            $inviterId = (int)($inviter["user_id"] ?? 0);
        } elseif ($inviterId > 0) {
            $inviter = $this->find(["company_id" => $companyId, "user_id" => $inviterId]);
            if (empty($inviter)) {
                throw new ErrorException(ErrorCode::MEMBER_INVITER_NOT_FOUND);
            }
            $inviterId = (int)($inviter["user_id"] ?? 0);
        } else {
            $inviterId = 0;
        }
        return $this;
    }

    /**
     * 验证来源渠道
     * @param string $sourceFrom
     */
    protected function checkSourceFrom(string &$sourceFrom)
    {
        if (!isset(self::SOURCE_FROM_MAP[$sourceFrom])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "来源渠道填写错误");
        }
    }

    /**
     * 更新会员信息
     * @param int $companyId
     * @param array $filter
     * @param array $params
     * @throws \Throwable
     */
    public function update(array $filter, array $params)
    {
        $this->transaction(function () use ($filter, $params) {
            // 更新会员信息
            $user = $this->updateDetail($filter, $params);
            // 修改过滤条件
            unset($filter["mobile"]);
            $filter["user_id"] = $user["user_id"] ?? 0;
            // 更新会员详情
            (new MemberInfoService())->updateDetail($filter, $params);
            // 记录操作日志
            (new \OpenapiBundle\Services\Member\MemberOperateLogService())->saveInfo((int)$filter["company_id"], (int)$filter["user_id"]);
        });
    }

    /**
     * 更新手机号
     * @param array $filter 过滤条件
     * @param string $newMobile 新手机号
     * @return array
     */
    public function updateMobile(array $filter, string $newMobile): array
    {
        if (!isset($filter["company_id"])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        // 获取原手机号
        $oldMobileUser = $this->find($filter);
        if (empty($oldMobileUser)) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }
        // 根据新手机号去查询会员
        $newMobileUser = $this->find(["company_id" => $filter["company_id"], "mobile" => $newMobile]);
        if (!empty($newMobileUser) && $oldMobileUser["user_id"] != $newMobileUser["user_id"]) {
            throw new ErrorException(ErrorCode::MEMBER_EXIST, "会员新手机号已存在");
        }
        // 更新手机号 （update底层会做一次查询）
        $result = $this->updateDetail($filter, ["mobile" => $newMobile]);
        // 新老数据做临时存放
        MemberOperateLogData::instance()->register($result, $oldMobileUser);
        // 记录日志
        (new \OpenapiBundle\Services\Member\MemberOperateLogService())->saveMobile($result["company_id"], $result["user_id"]);
        return $result;
    }

    /**
     * 更新会员的信息
     * @param array $filter 过滤条件
     * @param array $updateData 更新的数据
     * @return array
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        if (empty($filter) || !isset($filter["company_id"])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        // 定义实际的更新数据
        $actualUpdateData = [];
        $upGradeFlag = false;
        // 基于推荐人手机号
        if (isset($updateData["inviter_mobile"])) {
            $actualUpdateData["inviter_id"] = 0;
            $this->checkInviter($filter["company_id"], $updateData["inviter_mobile"], $actualUpdateData["inviter_id"]);
        }
        // 是否禁用 【0 可用】【1 禁用】
        if (isset($updateData["status"])) {
            $actualUpdateData["disabled"] = $updateData["status"] == 1 ? false : true;
        }
        // 是否禁用 【0 可用】【1 禁用】
        if (isset($updateData["remarks"])) {
            $actualUpdateData["remarks"] = (string)$updateData["remarks"];
        }
        // 更新手机号
        if (isset($updateData["mobile"])) {
            $actualUpdateData["mobile"] = (string)$updateData["mobile"];
        }
        // 会员卡号
        if (isset($updateData["user_card_code"])) {
            if (!isset($filter["mobile"])) {
                $userInfo = $this->find($filter);
                $mobile = $userInfo["mobile"];
            } else {
                $mobile = $filter["mobile"];
            }
            $this->checkUserCardCode((int)$filter["company_id"], $updateData["user_card_code"], (string)$mobile);
            $actualUpdateData["user_card_code"] = (string)$updateData["user_card_code"];
        }
        // 等级id
        if (isset($updateData["grade_id"])) {
            $grade = (new MemberCardGradeService())->find(["company_id" => $filter["company_id"], "grade_id" => $updateData["grade_id"]]);
            if (empty($grade)) {
                throw new ErrorException(ErrorCode::MEMBER_GRADE_NOT_FOUND);
            }
            $actualUpdateData["grade_id"] = (string)$updateData["grade_id"];
        }
        if (isset($updateData["external_id"])) {
            $grade = (new MemberCardGradeService())->find(["company_id" => $filter["company_id"], "external_id" => $updateData["external_id"]]);
            if (empty($grade)) {
                throw new ErrorException(ErrorCode::MEMBER_GRADE_NOT_FOUND);
            }
            $actualUpdateData["grade_id"] = (string)$grade["grade_id"];
            $upGradeFlag = true;
        }
        if (empty($actualUpdateData)) {
            return [];
        }
        // 修改前的数据
        $oldUser = [];
        try {
            // 修改用户数据，并获取修改后的数据
            $newUser = $this->getRepository()->update($actualUpdateData, $filter, $oldUser);
        } catch (UpdateResourceFailedException $updateResourceFailedException) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }
        // 设置新老数据
        MemberOperateLogData::instance()->register($newUser, $oldUser);
        // 处理场景营销-会员升级
        if ($upGradeFlag) {
            $this->upGrade($filter['company_id'], $newUser, $oldUser);
        }
        return $newUser;
    }

    /**
     * 根据手机号来查询会员信息
     * @param int $companyId
     * @param string $mobile
     * @return array
     */
    public function findByMobile(int $companyId, string $mobile): array
    {
        return $this->find(["company_id" => $companyId, "mobile" => $mobile]);
    }

    /**
     * 会员升级，触发优惠活动
     */
    public function upGrade($companyId, $newUser, $oldUser)
    {
        $gradeService = new MemberCardGradeService();
        $newGrade = $gradeService->find(["company_id" => $companyId, "grade_id" => $newUser['grade_id']]);
        $oldGrade = $gradeService->find(["company_id" => $companyId, "grade_id" => $oldUser['grade_id']]);
        $newGradeLevel = (int)$newGrade['promotion_condition']['total_consumption'];
        $oldGradeLevel = (int)$oldGrade['promotion_condition']['total_consumption'];
        app('log')->info('upGrade companyId:'.$companyId.',user_id:'.$newUser['user_id'].',newGradeId:'.$newUser['grade_id'].',newGradeLevel:'.$newGradeLevel.';oldGradeId:'.$oldUser['grade_id'].',oldGradeLevel:'.$oldGradeLevel);
        if ($newGradeLevel > $oldGradeLevel && (int)$newUser['grade_id'] > (int)$oldUser['grade_id']) {
            $userId = $newUser['user_id'];
            $nextGradeId = $newUser['grade_id'];
            // 会员等级提升，触发优惠活动
            $activityMemberInfo['grade_id'] = $nextGradeId;
            $activityMemberInfo['user_id'] = $userId;
            $activityMemberInfo['mobile'] = $newUser['mobile'];
            $activityMemberInfo['grade_name'] = $newGrade['grade_name'];
            $job = (new FirePromotionsActivity($companyId, $activityMemberInfo, 'member_upgrade'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        return true;
    }
}
