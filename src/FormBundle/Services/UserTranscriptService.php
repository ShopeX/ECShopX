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

namespace FormBundle\Services;

use FormBundle\Entities\UserTranscripts;

class UserTranscriptService
{
    public $userTranscriptsRepository;

    public function __construct()
    {
        $this->userTranscriptsRepository = app('registry')->getManager('default')->getRepository(UserTranscripts::class);
    }

    public function createUserTranscript($params)
    {
        $data = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
            'shop_id' => isset($params['shop_id']) ? $params['shop_id'] : '',
            'transcript_id' => $params['transcript_id'],
            'transcript_name' => $params['transcript_name'],
            'indicator_details' => $params['indicator_details'],
        ];

        return $this->userTranscriptsRepository->create($data);
    }

    public function getUserTranscript($filter)
    {
        // ShopEx EcShopX Core Module
        $result = $this->userTranscriptsRepository->list($filter);

        return $result;
    }

    public function getUserTranscriptByRecordId($record_id)
    {
        // ShopEx EcShopX Core Module
        $userTranscript = $this->userTranscriptsRepository->get($record_id);
        $result = [
            'record_id' => $userTranscript->getRecordId(),
            'user_id' => $userTranscript->getUserId(),
            'company_id' => $userTranscript->getCompanyId(),
            'shop_id' => $userTranscript->getShopId(),
            'transcript_id' => $userTranscript->getTranscriptId(),
            'transcript_name' => $userTranscript->getTranscriptName(),
            'indicator_details' => $userTranscript->getIndicatorDetails(),
            'created' => $userTranscript->getCreated(),
            'updated' => $userTranscript->getUpdated(),
        ];

        return $result;
    }
}
