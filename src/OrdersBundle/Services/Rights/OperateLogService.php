<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
