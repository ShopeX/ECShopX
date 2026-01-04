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
