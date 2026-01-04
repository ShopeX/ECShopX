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

namespace WechatBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class WeappRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'wechat_weapp';

    public function deleteWeapp($wxaAppId)
    {
        $conn = app('registry')->getConnection('default');
        return $conn->delete($this->table, ['authorizer_appid' => $wxaAppId]);
    }

    /**
     * 添加绑定小程序账号上架小程序到微信
     */
    public function createWeapp($wxaAppId, $data)
    {
        $conn = app('registry')->getConnection('default');

        // 默认值
        //$data['audit_status'] = 2; // 创建号等待审核
        $data['release_status'] = 0; // 提交后改为未发布
        $data['audit_time'] = time(); // 审核时间
        $data['visitstatus'] = 1; // 发布后是否可见 默认为可见
        $data['updated_at'] = date('Y-m-d H:i:s');

        // 判断是否已提交过审核或已发布过
        if ($this->find($wxaAppId)) {
            return $conn->update($this->table, $data, ['authorizer_appid' => $wxaAppId]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $conn->insert($this->table, $data);
        }
    }

    /**
     * 版本撤回成功后的处理
     */
    public function undocodeaudit($wxaAppId)
    {
        $conn = app('registry')->getConnection('default');

        $data = [
            'audit_status' => 0,
            'release_status' => 1,
        ];
        return $conn->update($this->table, $data, ['authorizer_appid' => $wxaAppId]);
    }

    /**
     * 处理审核
     * @param string $wxaAppId 小程序appid
     * @param integer $status:状态 0:审核失败 1:发布成功(用于自动发布)  2:审核成功
     * @param integer $time 时间
     * @param string $reason 原因
     */
    public function processAudit($wxaAppId, $status, $time, $reason = null)
    {
        $conn = app('registry')->getConnection('default');
        $weappData = $this->find($wxaAppId);
        //存在需要处理的审核，并且为待审核状态
        if ($weappData && $weappData->getAuditStatus() == 2) {
            $data['audit_time'] = $time;
            switch ($status) {
                case 2:
                    $data['audit_status'] = 0;//审核成功
                    break;
                case 1:
                    $data['audit_status'] = 0;//审核成功
                    $data['release_status'] = 1;//发布成功
                    $data['release_ver'] = $weappData->getTemplateVer();//发布成功将模板版本号替换到正式版
                    break;
                default:
                    $data['audit_status'] = 1;//审核失败
                    $data['reason'] = $reason;//审核原因
                    break;
            }
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $conn->update($this->table, $data, ['authorizer_appid' => $wxaAppId]);
        } elseif ($weappData && $weappData->getAuditStatus() == 0) {
            // 审核成功的尝试发布
            switch ($status) {
                case 1:
                    $data['release_status'] = 1;//发布成功
                    $data['release_ver'] = $weappData->getTemplateVer();//发布成功将模板版本号替换到正式版
                    break;
            }
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $conn->update($this->table, $data, ['authorizer_appid' => $wxaAppId]);
        } else {
            return true;
        }
    }

    public function getWeappInfo($companyId, $wxaAppId)
    {
        $detail = $this->findOneBy(['company_id' => $companyId, 'authorizer_appid' => $wxaAppId]);
        if ($detail) {
            return [
                'authorizer_appid' => $wxaAppId,
                'operator_id' => $detail->getOperatorId(),
                'company_id' => $detail->getCompanyId(),
                'reason' => $detail->getReason(),
                'audit_status' => $detail->getAuditStatus(),
                'release_status' => $detail->getReleaseStatus(),
                'audit_time' => $detail->getAuditTime(),
                'template_id' => intval($detail->getTemplateId()),
                'template_name' => $detail->getTemplateName(),
                'template_ver' => $detail->getTemplateVer(),
                'release_ver' => $detail->getReleaseVer(),
                'visitstatus' => $detail->getVisitstatus(),
            ];
        } else {
            return false;
        }
    }

    public function getWeappInfoByTemplateName($companyId, $templateName)
    {
        $detail = $this->findOneBy(['company_id' => $companyId, 'template_name' => $templateName]);
        if ($detail) {
            return [
                'authorizer_appid' => $detail->getAuthorizerAppid(),
                'operator_id' => $detail->getOperatorId(),
                'company_id' => $detail->getCompanyId(),
                'reason' => $detail->getReason(),
                'audit_status' => $detail->getAuditStatus(),
                'release_status' => $detail->getReleaseStatus(),
                'audit_time' => $detail->getAuditTime(),
                'template_id' => intval($detail->getTemplateId()),
                'template_name' => $detail->getTemplateName(),
                'template_ver' => $detail->getTemplateVer(),
                'release_ver' => $detail->getReleaseVer(),
                'visitstatus' => $detail->getVisitstatus(),
            ];
        } else {
            return false;
        }
    }

    public function getWxappidByTemplateName($companyId, $templateName)
    {
        $data = $this->findOneBy(['company_id' => $companyId, 'template_name' => $templateName]);
        if ($data) {
            return $data->getAuthorizerAppid();
        } else {
            return null;
        }
    }

    public function getTemplateidByTemplateName($companyId, $templateName)
    {
        $data = $this->findOneBy(['company_id' => $companyId, 'template_name' => $templateName]);
        if ($data) {
            return $data->getTemplateId();
        } else {
            return null;
        }
    }

    public function getWeappByCompanyId($companyId)
    {
        $list = $this->findBy(['company_id' => $companyId]);
        $data = [];
        if ($list) {
            foreach ($list as $row) {
                $wxaAppId = $row->getAuthorizerAppid();
                $data[$wxaAppId] = [
                    'authorizer_appid' => $wxaAppId,
                    'operator_id' => $row->getOperatorId(),
                    'company_id' => $row->getCompanyId(),
                    'reason' => $row->getReason(),
                    'audit_status' => $row->getAuditStatus(),
                    'release_status' => $row->getReleaseStatus(),
                    'audit_time' => $row->getAuditTime(),
                    'template_id' => intval($row->getTemplateId()),
                    'template_name' => $row->getTemplateName(),
                    'template_ver' => $row->getTemplateVer(),
                    'release_ver' => $row->getReleaseVer(),
                    'visitstatus' => $row->getVisitstatus(),
                ];
            }
        }
        return $data;
    }
}
