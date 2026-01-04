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

namespace WechatBundle\Console;

use Illuminate\Console\Command;
use WechatBundle\Services\WeappService;

class ApplySetOrderPathInfo extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'wechat:applysetorderpathinfo';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '申请设置订单页path信息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $offset = 0;
        $limit = 100;
        $weappService = new WeappService();

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('company_id,authorizer_appid')->from('wechat_authorization');
        $qb = $qb->andWhere($qb->expr()->eq('service_type_info', 3))
                 ->andWhere($qb->expr()->eq('bind_status', $qb->expr()->literal('bind')))
                 ->andWhere($qb->expr()->eq('is_direct', 0));
        do {
            $qb->setFirstResult($offset)->setMaxResults($limit);
            $list = $qb->execute()->fetchAll();

            if (!$list) break;

            $weappService->applySetOrderPathInfo(array_column($list, 'authorizer_appid'));
            foreach ($list as $row) {
                try {
                    $result = (new WeappService($row['authorizer_appid'], $row['company_id']))->getOrderPathInfo();
                    $this->info($row['authorizer_appid'].' => '.$result['errmsg']);
                } catch (\Exception $e) {
                    $this->error($row['authorizer_appid'].' => '.$e->getMessage());
                }
            }

            $offset += $limit;
        } while(count($list) == 100);
    }
}