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

namespace OrdersBundle\Services\Rights;

use OrdersBundle\Entities\RightsOperateLogs;
use OrdersBundle\Services\RightsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OperateLogService
{
    public function DelayRights($postdata)
    {
        $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsOperateLogs::class);

        //获取权益的详情
        $rightsId = $postdata['rights_id'];
        $rightsService = new RightsService(new TimesCardService());
        $detail = $rightsService->getRightsDetail($rightsId);
        if (!$detail) {
            throw new BadRequestHttpException('请求数据有误');
        }
        // if ($detail['end_time'] >= $postdata['delay_date']) {
        //     throw new BadRequestHttpException('延期后日期必须大于原始结束日期');
        // }

        //更新权益结束日期
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $params['end_time'] = $postdata['delay_date'];
            $params['company_id'] = $postdata['company_id'];
            $params['status'] = 'valid';
            $result = $rightsService->updateRights($postdata['rights_id'], $params);

            if ($result) {
                $postdata['original_date'] = $detail['end_time'];
                $postdata['user_id'] = $detail['user_id'];
                $result = $rightsLogRepository->create($postdata);
                $conn->commit();
                return $result;
            }
            throw new BadRequestHttpException('更新权益结束日期失败');
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getLogList($rightsId)
    {
        $filter['rights_id'] = $rightsId;
        $rightsLogRepository = app('registry')->getManager('default')->getRepository(RightsOperateLogs::class);
        return $rightsLogRepository->lists($filter);
    }
}
