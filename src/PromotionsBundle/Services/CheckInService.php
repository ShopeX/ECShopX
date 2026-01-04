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

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\CheckInLog;

class CheckInService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CheckInLog::class);
    }

    public function createCheckIn($companyId, $userId, $checkDay = null)
    {
        if (!$checkDay) {
            $checkDay = date('Ymd');
        }
        $postdata = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'create_time' => $checkDay,
        ];
        $info = $this->entityRepository->getInfo($postdata);
        if ($info) {
            throw new ResourceException(trans('PromotionsBundle.already_signed_in_no_repeat'));
        }
        $listdata = $this->getCheckInList($companyId, $userId);
        $count = intval($listdata['total_count']) + 1;
        $postdata['tag'] = $count;
        $result = $this->entityRepository->create($postdata);
        return $result;
    }

    public function getCheckInList($companyId, $userId, $checkType = 'month', $startDate = null, $endDate = null)
    {
        switch ($checkType) {
            case "month":
                $startDate = $startDate ? date('Ymd', strtotime($startDate)) : date('Ym01');
                $endDate = $endDate ? date('Ymd', strtotime($endDate)) : date('Ymd', strtotime("$startDate +1 month -1 day"));
                break;
            case "week":
                $startDate = $startDate ? date('Ymd', strtotime($startDate)) : date('Ymd', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                $endDate = $endDate ? date('Ymd', strtotime($endDate)) : date('Ymd', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                break;
            default:
                $startDate = $startDate ? date('Ymd', strtotime($startDate)) : date('Ymd');
                $endDate = $endDate ? date('Ymd', strtotime($endDate)) : date('Ymd');
                break;
        }
        $filter = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'create_time|gte' => $startDate,
            'create_time|lte' => $endDate,
        ];
        $result = $this->entityRepository->lists($filter);
        return $result;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
