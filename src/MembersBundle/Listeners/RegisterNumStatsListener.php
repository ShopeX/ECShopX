<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use MembersBundle\Events\CreateMemberSuccessEvent;
use DataCubeBundle\Services\TrackService;
use DataCubeBundle\Services\SourcesService;
use MembersBundle\Services\MemberTagsService;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterNumStatsListener extends BaseListeners implements ShouldQueue
{
    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  CreateMemberSuccessEvent  $event
     * @return void
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        // ShopEx EcShopX Service Component
        try {
            $trackService = new TrackService();
            $trackParams = [
                'monitor_id' => $event->monitor_id,
                'company_id' => $event->companyId,
                'source_id' => $event->source_id,
            ];
            $trackService->addRegisterNum($trackParams);

            //根据来源为新增会员打标签
            if ($sourceId = $trackParams['source_id']) {
                $userId = $event->userId;
                $companyId = $event->companyId;
                $sourcesService = new SourcesService();
                $source = $sourcesService->getSourcesDetail($sourceId);
                $tagsId = is_array($source['tags_id']) ? $source['tags_id'] : json_decode($source['tags_id'], true);
                if (!$tagsId) {
                    return true;
                }

                $memberTagsService = new MemberTagsService();
                $tags = $memberTagsService->getListTags(['tag_id' => $tagsId]);
                if (!($tags['list'] ?? [])) {
                    return true;
                }
                $tagIds = array_column($tags['list'], 'tag_id');
                if ($tagIds) {
                    return $memberTagsService->createRelTagsByUserId($userId, $tagIds, $companyId);
                }
            }
        } catch (\Exception $e) {
            app('log')->debug('会员注册成功事件：'.$e->getMessage());
        }
        return true;
    }
}
