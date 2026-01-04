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

namespace WorkWechatBundle\Services;

use EasyWeChat\Factory;
use WorkWechatBundle\Entities\WorkWechatMessageTemplate;
use SalespersonBundle\Services\SalespersonService;
use SalespersonBundle\Services\SalespersonTaskService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorSalesmanRoleService;

class WorkWechatMessageTemplateService
{
    public const TEMPLATE_WAITING_DELIVERY = 'waitingDeliveryNotice';
    public const TEMPLATE_SALESPERSON_TASK = 'salespersonTaskNotice';
    public const TEMPLATE_COMPLETE_TASK = 'completeTaskNotice';


    public $workWechatMessageTemplateRepository;

    public function __construct()
    {
        $this->workWechatMessageTemplateRepository = app('registry')->getManager('default')->getRepository(WorkWechatMessageTemplate::class);
    }

    /**
     * 企业微信通知模板保存
     *
     * @param int $companyId
     * @param int $templateId
     * @param array $params
     * @return void
     */
    public function saveTemplate($companyId, $templateId, $params)
    {
        if (!in_array($templateId, $this->templateNotice())) {
            throw new ResourceException('没有该通知模版类型');
        }
        $filter = [
            'company_id' => $companyId,
            'template_id' => $templateId,
        ];

        if (!isset($params['title'])) {
            $data = [
                'disabled' => 'true' == $params['disabled'] ? true : false,
            ];
            $result = $this->workWechatMessageTemplateRepository->updateOneBy($filter, $data);
            return $params;
        }
        $rules = [
            'title' => ['required|string|min:4|max:12', '企业微信通知模板标题长度在4-12个字符'],
            'description' => ['required|string|min:4|max:12', '企业微信通知模板内容长度在4-12个字符'],
            'disabled' => ['required', '模版开启规则必填'],
            'emphasis_first_item' => ['required', '是否放大第一个内容']
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $info = $this->workWechatMessageTemplateRepository->getInfo($filter);
        $data = [
            'company_id' => $companyId,
            'template_id' => $templateId,
            'disabled' => 'true' == $params['disabled'] ? true : false,
            'emphasis_first_item' => 'true' == $params['emphasis_first_item'] ? true : false,
            'title' => $params['title'],
            'description' => $params['description'],
            'content' => $params['content'],
        ];
        if (!$info) {
            $result = $this->workWechatMessageTemplateRepository->create($data);
        } else {
            $result = $this->workWechatMessageTemplateRepository->updateOneBy($filter, $data);
        }
        return $result;
    }

    /**
     * 发送等待发货通知
     *
     * @param int $companyId
     * @param string $orderId
     * @param int $salesperonId
     * @return void
     */
    public function sendWaitingDeliveryNotice($companyId, $orderId, $distributorId)
    {
        $salespersonService = new SalespersonService();
        $lists = $salespersonService->relSalesperson->getLists(['company_id' => $companyId, 'shop_id' => $distributorId, 'store_type' => 'distributor']);
        if (!$lists) {
            return true;
        }
        $salespersonIds = array_column($lists, 'salesperson_id');

        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();
        $roles = $distributorSalesmanRoleService->lists(['rule_ids|contains' => '"1"']);
        if (!$roles['total_count']) {
            return true;
        }
        $roleIds = array_column($roles['list'], 'salesman_role_id');

        $salespersonList = $salespersonService->salesperson->getLists(['company_id' => $companyId, 'salesperson_id' => $salespersonIds, 'role' => $roleIds]);

        $workUserid = [];
        foreach ($salespersonList as $v) {
            if ($v['work_userid']) {
                $workUserid[] = $v['work_userid'];
            } else {
                app('log')->debug('salesperson_id:' . $v['salesperson_id'] . '-name:' . $v['name'] . ', 导购发货通知:导购不存在');
            }
        }

        if (!$workUserid) {
            return true;
        }

        $filter = [
            'company_id' => $companyId,
            'template_id' => self::TEMPLATE_WAITING_DELIVERY
        ];
        $config = app('wechat.work.wechat')->getConfig($companyId);
        if (!isset($config['appid'])) {
            app('log')->info('companyId:' . $companyId . ', 导购发货通知:企业微信未配置');
            return false;
        }
        $info = $this->workWechatMessageTemplateRepository->getInfo($filter);
        if ($info['disabled'] ?? true) {
            app('log')->info('companyId:' . $companyId . ', 导购发货通知:未开启');
            return false;
        }

        $messageApp = Factory::work($config)->messenger;
        foreach ($workUserid as $workid) {
            $params = [
                'appid' => $config['appid'],
                "page" => 'pages/ship/index',
                'title' => $info['title'],
                'description' => $info['description'],
                'emphasis_first_item' => $info['emphasis_first_item'] ? true : false,
            ];
            foreach ($info['content'] as $v) {
                $replace = array(date('Y-m-d H:i:s'), $orderId);
                $find = array('{{$dateTime}}', '{{$orderId}}');
                $params['content_item'][] = [
                    'key' => $v['key'],
                    'value' => str_replace($find, $replace, $v['value']),
                ];
            }
            $message = new \EasyWeChat\Kernel\Messages\MiniprogramNotice($params);
            $result = $messageApp->message($message)->toUser($workid)->send();
            if (is_array($result)) {
                app('log')->debug('导购 ' . $workid . ' 通知发送 -> ' . var_export($result, 1));
            } else {
                app('log')->debug('导购 ' . $workid . ' 通知发送 -> ' . $result);
            }
        }
        return true;
    }

    /**
     * 发送等待发货通知
     *
     * @param int $companyId
     * @param string $orderId
     * @param int $salesperonId
     * @return void
     */
    public function sendTaskProgressNotice($companyId, $taskId, $salespersonId, $username = '')
    {
        $salespersonService = new SalespersonService();
        $salespersonInfo = $salespersonService->getSalespersonDetail(['company_id' => $companyId, 'salesperson_id' => $salespersonId]);

        if (!($salespersonInfo['work_userid'] ?? 0)) {
            return true;
        }
        $config = app('wechat.work.wechat')->getConfig($companyId);
        if (!isset($config['appid'])) {
            app('log')->info('companyId:' . $companyId . ', 导购发货通知:企业微信未配置');
            return false;
        }

        $params = [
            'touser' => $salespersonInfo['work_userid'],
            'msgtype' => 'miniprogram_notice',
            'miniprogram_notice' => [
                'appid' => $config['appid'],
                'title' => '物料转发任务通知',
                'description' => date('m月d日 H:i'),
                'emphasis_first_item' => false,
                // "page" => 'pages/ship/index',
            ]
        ];
        $salespersonTaskService = new SalespersonTaskService();
        $taskInfo = $salespersonTaskService->getTaskProcessInfo($taskId, $companyId, $salespersonId);
        $params['miniprogram_notice']['content_item'] = [
            [
                'key' => '任务名称',
                'value' => $taskInfo['task_name'],
            ],
            [
                'key' => '任务进度',
                'value' => '你完成了一个指标',
            ],
            [
                'key' => '客户名称',
                'value' => $username ?: '微信用户',
            ],
            [
                'key' => '完成时间',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
        $result = Factory::work($config)->message->send($params);
        app('log')->debug('导购 ' . $salespersonInfo['work_userid'] . ' 通知发送 -> ' . var_export($result, 1));
        return true;
    }

    /**
     * 导购通知类型
     *
     * @return array
     */
    public function templateNotice()
    {
        return [
            self::TEMPLATE_WAITING_DELIVERY,
            self::TEMPLATE_SALESPERSON_TASK,
            self::TEMPLATE_COMPLETE_TASK
        ];
    }

    /**
     * Dynamically call the WorkWechatMessageTemplateService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->workWechatMessageTemplateRepository->$method(...$parameters);
    }
}
