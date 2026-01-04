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

namespace AdaPayBundle\Traits;

use Dingo\Api\Exception\ResourceException;

/**
 * 日志文案生成助手
 */
trait GenerateLogHelper
{
    /**
     * 生成日志内容
     *
     * @param string $username
     * @param string $action
     * @param array $other
     * @return string
     */
    private function generateLogContent(string $username, string $action, array $other = []): string
    {
        switch ($action) {
            // 主商户 开户
            case 'merchant_entry/create':
                $content = $this->getMerchantEntryCreateLog($username);
                break;
            // 主商户 入驻申请
            case 'merchant_resident/create':
                $content = $this->getMerchantResidentCreateLog($username);
                break;
            // 主商户 证照信息的填写与提交申请
            case 'license_submit/create':
                $content = $this->getLicenseSubmitCreateLog($username);
                break;
            // 店铺与经销商的关联与解除
            case 'dealer/rel/dealer':
                $content = $this->getDealerRelDealerLog($username, $other);
                break;
            case 'dealer/rel/distributor':
                $content = $this->getDealerRelDistributorLog($username, $other);
                break;
            // 子商户开户申请通过或驳回
            case 'sub_approve/save_split_ledger':
                $content = $this->getSaveSplitLedgerLog($username, $other);
                break;
            // 经销商的新增与删除
            case 'create_operator_dealer':
                $content = $this->getCreateOperatorDealerLog($username, $other);
                break;
            case 'delete_operator_dealer':
                $content = $this->getDeleteOperatorDealerLog($username, $other);
                break;
            // 店铺的新增与删除
            case 'create_operator_distributor':
                $content = $this->getCreateOperatorDistributorLog($username, $other);
                break;
            case 'delete_operator_distributor':
                $content = $this->getDeleteOperatorDistributorLog($username, $other);
                break;
            // 禁用经销商
            case 'dealer/disable':
                $content = $this->getDealerDisableLog($username, $other);
                break;
            // 支付配置的相关操作
            case 'set_payment_setting':
                $content = $this->getSetPaymentSettingLog($username);
                break;
            // 经销商/店铺 修改开户信息
            case 'update_member_log':
                $content = $this->getUpdateMemberLog($username, $other);
                break;
            // 经销商/店铺 开户
            case 'create_member_log':
                $content = $this->getCreateMemberLog($username, $other);
                break;
            // 导出分账
            case 'trade/exportdata':
                $content = $this->getTradeExportDataLog($username);
                break;
            // 提现
            case 'withdraw':
                $content = $this->getWithdrawLog($username);
                break;
            case 'withdrawset':
                $content = $this->getWithdrawSetLog($username, $other);
                break;
            case 'dealer/reset':
                $content = $this->getDealerResetLog($username, $other);
                break;
            default:
                throw new ResourceException("unknown log type");
        }

        return $content;
    }

    private function getMerchantEntryCreateLog(string $username): string
    {
        return '用户' . $username . '提交了开户申请';
    }

    private function getMerchantResidentCreateLog(string $username): string
    {
        return '用户' . $username . '提交了入驻申请';
    }

    private function getLicenseSubmitCreateLog(string $username): string
    {
        return '用户' . $username . '提交了证照信息';
    }

    /**
     * 关联日志-代理商日志
     * example:用户Olivia给慕木集团有限公司解除关联妹妹的店
     *
     * @param string $username
     * @param $other
     * @return string
     */
    private function getDealerRelDealerLog(string $username, $other): string
    {
        $action = isset($other['is_rel']) && $other['is_rel'] ? '关联' : '解除';
        return "用户{$username}给{$other['dealer_name']}{$action}{$other['distributor_name']}";
    }

    /**
     * 关联日志-店铺日志
     * example:用户Olivia给妹妹的店解除关联慕木集团有限公司
     *
     * @param string $username
     * @param $other
     * @return string
     */
    private function getDealerRelDistributorLog(string $username, $other): string
    {
        $action = isset($other['is_rel']) && $other['is_rel'] ? '关联' : '解除';
        return "用户{$username}给{$other['distributor_name']}{$action}{$other['dealer_name']}";
    }

    /**
     * 审批通过日志
     * example:用户Olivia通过妹妹的店开户审批，开通了短信提醒
     *
     * @param string $username
     * @param $other
     * @return string
     */
    private function getSaveSplitLedgerLog(string $username, $other): string
    {
        $action = $other['status'] == 'APPROVED' ? '通过' : '驳回';

        $content = "用户{$username}{$action}{$other['name']}的审批";
        if ($other['is_sms']) {
            $content .= "，开通了短信提醒";
        }

        return $content;
    }

    /**
     * example:用户Olivia新增经销商慕木集团有限公司
     *
     * @param string $username
     * @param $other
     * @return string
     */
    private function getCreateOperatorDealerLog(string $username, $other): string
    {
        return "用户{$username}新增经销商{$other['name']}";
    }

    /**
     * example:用户Olivia删除经销商慕木集团有限公司
     *
     * @param string $username
     * @param $other
     * @return string
     */
    private function getDeleteOperatorDealerLog(string $username, $other): string
    {
        return "用户{$username}删除经销商{$other['name']}";
    }

    private function getCreateOperatorDistributorLog(string $username, $other): string
    {
        return "用户{$username}新增店铺{$other['name']}";
    }

    private function getDeleteOperatorDistributorLog(string $username, $other): string
    {
        return "用户{$username}删除店铺{$other['name']}";
    }

    private function getDealerDisableLog(string $username, $other): string
    {
        $action = $other['is_disable'] == 1 ? '禁用' : '开启';

        return "用户{$username}{$action}了经销商{$other['name']}";
    }

    private function getSetPaymentSettingLog(string $username): string
    {
        return '用户' . $username . '配置支付配置';
    }

    private function getUpdateMemberLog(string $username, $other): string
    {
        if ($other['operator_type'] == 'distributor') {
            $sourceType = '店铺';
        } elseif ($other['operator_type'] == 'dealer') {
            $sourceType = '经销商';
        } else {
            $sourceType = '主商户';
        }

        return "用户{$username}提交了{$sourceType}{$other['name']}的开户信息修改";
    }

    private function getCreateMemberLog(string $username, $other): string
    {
        if ($other['operator_type'] == 'distributor') {
            $sourceType = '店铺';
        } elseif ($other['operator_type'] == 'dealer') {
            $sourceType = '经销商';
        } else {
            $sourceType = '主商户';
        }

        return "用户{$username}提交了{$sourceType}{$other['name']}的开户信息的创建";
    }

    private function getTradeExportDataLog(string $username): string
    {
        return "用户{$username}导出了分账列表";
    }

    private function getWithdrawLog(string $username): string
    {
        return "用户{$username}申请了提现";
    }

    private function getWithdrawSetLog(string $username, $other): string
    {
        return "用户{$username}设置了店铺{$other['name']}提现配置";
    }

    private function getDealerResetLog(string $username, $other): string
    {
        return "用户{$username}重置了经销商{$other['name']}密码";
    }
}
