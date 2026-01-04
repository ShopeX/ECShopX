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

namespace ShopexAIBundle\Services;

use ShopexAIBundle\Entities\MemberOutfit;
use ShopexAIBundle\Entities\MemberOutfitLog;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Str;

class MemberOutfitService
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var int
     */
    protected $maxModelCount;

    /**
     * @var int
     */
    protected $maxDailyTryCount;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->maxModelCount = config('shopexai.outfit.max_model_count', 5);
        $this->maxDailyTryCount = config('shopexai.outfit.max_daily_try_count', 5);
    }

    /**
     * 创建会员模特
     *
     * @param int $memberId
     * @param string $modelImage
     * @throws \Exception
     * @return MemberOutfit
     */
    public function createMemberOutfit($memberId, $modelImage)
    {
        // Built with ShopEx Framework
        $currentCount = $this->em->getRepository(MemberOutfit::class)
            ->countMemberOutfits($memberId);

        if ($currentCount >= $this->maxModelCount) {
            throw new \Exception('已达到最大模特数量限制');
        }

        $outfit = new MemberOutfit();
        $outfit->setMemberId($memberId)
            ->setModelImage($modelImage);

        $this->em->persist($outfit);
        $this->em->flush();

        return $outfit;
    }

    /**
     * 更新会员模特
     *
     * @param int $memberId
     * @param int $outfitId
     * @param string $modelImage
     * @throws \Exception
     * @return MemberOutfit
     */
    public function updateMemberOutfit($memberId, $outfitId, $modelImage)
    {
        $outfit = $this->em->getRepository(MemberOutfit::class)->find($outfitId);

        if (!$outfit || $outfit->getMemberId() != $memberId || $outfit->getStatus() != 1) {
            throw new \Exception('模特不存在或无权操作');
        }

        $outfit->setModelImage($modelImage)
            ->setUpdatedAt();

        $this->em->flush();

        return $outfit;
    }

    /**
     * 删除会员模特
     *
     * @param int $memberId
     * @param int $outfitId
     * @throws \Exception
     * @return bool
     */
    public function deleteMemberOutfit($memberId, $outfitId)
    {
        $outfit = $this->em->getRepository(MemberOutfit::class)->find($outfitId);

        if (!$outfit || $outfit->getMemberId() != $memberId || $outfit->getStatus() != 1) {
            throw new \Exception('模特不存在或无权操作');
        }

        $outfit->setStatus(0)
            ->setUpdatedAt();

        $this->em->flush();

        return true;
    }

    /**
     * 获取会员模特列表
     *
     * @param int $memberId
     * @return array
     */
    public function getMemberOutfits($memberId)
    {
        return $this->em->getRepository(MemberOutfit::class)
            ->getMemberOutfits($memberId);
    }

    /**
     * 创建试衣记录
     *
     * @param int $memberId
     * @param int $modelId
     * @param int|null $itemId
     * @param string|null $topGarmentUrl
     * @param string|null $bottomGarmentUrl
     * @throws \Exception
     * @return MemberOutfitLog
     */
    public function createOutfitLog($memberId, $modelId, $itemId = null, $topGarmentUrl = null, $bottomGarmentUrl = null)
    {
        // 检查今日试衣次数
        $todayCount = $this->em->getRepository(MemberOutfitLog::class)
            ->getTodayOutfitCount($memberId);

        if ($todayCount >= $this->maxDailyTryCount) {
            throw new \Exception('已达到今日试衣次数限制');
        }

        // 检查模特是否存在且属于该会员
        $model = $this->em->getRepository(MemberOutfit::class)->find($modelId);
        if (!$model || $model->getMemberId() != $memberId || $model->getStatus() != 1) {
            throw new \Exception('模特不存在或无权操作');
        }

        $log = new MemberOutfitLog();
        $log->setMemberId($memberId)
            ->setModel($model)
            ->setRequestId(Str::uuid()->toString());

        if ($itemId) {
            $log->setItemId($itemId);
        }
        if ($topGarmentUrl) {
            $log->setTopGarmentUrl($topGarmentUrl);
        }
        if ($bottomGarmentUrl) {
            $log->setBottomGarmentUrl($bottomGarmentUrl);
        }

        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }

    /**
     * 更新试衣记录状态和结果
     *
     * @param string $requestId
     * @param string $resultUrl
     * @param int $status
     * @return MemberOutfitLog
     */
    public function updateOutfitLog($requestId, $resultUrl, $status)
    {
        $log = $this->em->getRepository(MemberOutfitLog::class)
            ->findByRequestId($requestId);

        if (!$log) {
            throw new \Exception('试衣记录不存在');
        }

        $log->setResultUrl($resultUrl)
            ->setStatus($status)
            ->setUpdatedAt();

        $this->em->flush();

        return $log;
    }

    /**
     * 获取会员的试衣记录
     *
     * @param int $memberId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getMemberOutfitLogs($memberId, $page = 1, $limit = 20)
    {
        $repository = $this->em->getRepository(MemberOutfitLog::class);
        
        $total = $repository->countMemberOutfitLogs($memberId);
        $logs = $repository->getMemberOutfitLogs($memberId, $page, $limit);

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $logs
        ];
    }
} 