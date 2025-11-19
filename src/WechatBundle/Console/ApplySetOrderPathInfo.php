<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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