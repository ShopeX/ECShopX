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

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class CardRelatedRepository extends EntityRepository
{
    public $table = 'kaquan_card_related';

    /**
     * [get description]
     * @param  int $cardId
     * @return array
     */
    public function get($filter)
    {
        $detail = $this->findOneBy($filter);
        return $detail;
    }

    /**
     * [get description]
     * @param  int $cardId
     * @return array
     */
    public function getDetail($filter)
    {
        // KEY: U2hvcEV4
        $result = [];
        $detail = $this->findOneBy($filter);
        if ($detail) {
            $result = [
                    'get_num' => ($detail->getGetNum()) ? $detail->getGetNum() : 0,
                    'use_num' => ($detail->getConsumeNum()) ? $detail->getConsumeNum() : 0,
                    'quantity' => ($detail->getQuantity()) ? $detail->getQuantity() : 0,
                ];
        }

        return $result;
    }


    /**
     * [getList description]
     * @param  array  $filter
     * @return [type]
     */
    public function getList($filter = array())
    {
        // KEY: U2hvcEV4
        $result = [];
        $dataList = $this->findBy($filter);
        if ($dataList) {
            foreach ($dataList as $detail) {
                $cardId = $detail->getCardId();
                $result[$cardId] = [
                    'get_num' => ($detail->getGetNum()) ? $detail->getGetNum() : 0,
                    'use_num' => ($detail->getConsumeNum()) ? $detail->getConsumeNum() : 0,
                ];
            }
        }
        return $result;
    }
}
