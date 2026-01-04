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

namespace KaquanBundle\Services;

use KaquanBundle\Entities\CardRelated;

class CardRelatedService
{
    public $cardRelatedRepository;

    public function __construct()
    {
        $this->cardRelatedRepository = app('registry')->getManager('default')->getRepository(CardRelated::class);
    }

    public function update($postData, $filter)
    {
        return $this->cardRelatedRepository->update($postData, $filter);
    }

    public function get($filter)
    {
        return $this->cardRelatedRepository->get($filter);
    }

    public function delete($cardId)
    {
        return $this->cardRelatedRepository->remove($cardId);
    }

    public function getList($cols, $cardIds)
    {
        $listData = array();
        $result = $this->cardRelatedRepository->getList($cols, $cardIds);
        foreach ($result as $list) {
            $listData[$list['card_id']] = $list;
        }
        return $listData;
    }
}
