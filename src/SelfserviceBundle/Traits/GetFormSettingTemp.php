<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SelfserviceBundle\Traits;

use Dingo\Api\Exception\ResourceException;

trait GetFormSettingTemp
{
    public function getTempId($companyId, $formType = 'physical', $isCheck = false)
    {
        switch ($formType) {
            case 'physical':  //体测报告表单
                $key = 'settingPhysical:'.$companyId;
                $result = app('redis')->connection('companys')->get($key);
                $result = $result ? json_decode($result, true) : [];
                if ((!$result || !$result['status']) && $isCheck) {
                    throw new ResourceException('未开启体测报告功能');
                }
                $tempId = $result['temp_id'] ?? 0;
                return $tempId;
                break;
        }
    }

    public function getStatus($companyId, $formType = 'physical')
    {
        switch ($formType) {
            case 'physical':  //体测报告表单
                $key = 'settingPhysical:'.$companyId;
                $result = app('redis')->connection('companys')->get($key);
                $result = $result ? json_decode($result, true) : [];
                $status = 0;
                if ($result && $result['status']) {
                    $status = 1;
                }
                return $status;
                break;
        }
    }
}
