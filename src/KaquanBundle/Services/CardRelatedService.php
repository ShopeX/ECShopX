<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
