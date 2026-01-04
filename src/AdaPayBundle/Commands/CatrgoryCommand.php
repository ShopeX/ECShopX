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

namespace AdaPayBundle\Commands;

use AdaPayBundle\Services\AlipayIndustryCategoryService;
use AdaPayBundle\Services\WxBusinessService;
use Illuminate\Console\Command;

class CatrgoryCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'adapay:get_category {type? : 类目类型：wechat|alipay} {--local : 从本地文件更新}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '聚合支付-获取支付宝和微信经营类目';

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
        $dataSource = 'https://cdn.cloudpnr.com/adapayresource/documents/Adapay%E6%9E%9A%E4%B8%BE%E6%95%B0%E6%8D%AE%E8%A1%A8.xlsx';
        $localPath = 'adapay/category_local.xlsx';

        if ($this->option('local')) {
            $dataPath = storage_path($localPath);
        } else {
            $dataPath = storage_path($localPath);
            $categoryData = file_get_contents($dataSource);
            file_put_contents($dataPath, $categoryData);
        }

        $type = $this->argument('type') ?: 'all';

        $sheets = app('excel')->toArray(new \stdClass(), $dataPath);

        if ($type == 'alipay' || $type == 'all') {
            if (!$sheets[0]) {
                return false;
            }

            $count = 0;
            foreach ($sheets[0] as $val) {
                if (!is_numeric($val[4])) {
                    continue;
                }
                $alipayService = new AlipayIndustryCategoryService();
                $data = [
                    'category_name' => $val[0],
                    'parent_id' => 0,
                    'category_level' => 1,
                ];
                $lv1 = $alipayService->getInfo($data);
                if (!$lv1) {
                    $lv1 = $alipayService->create($data);
                }

                $data = [
                    'category_name' => $val[1],
                    'parent_id' => $lv1['id'],
                    'category_level' => 2,
                ];
                $lv2 = $alipayService->getInfo($data);
                if (!$lv2) {
                    $lv2 = $alipayService->create($data);
                }

                $data = [
                    'category_name' => $val[2],
                    'parent_id' => $lv2['id'],
                    'category_level' => 3,
                ];
                $lv3 = $alipayService->getInfo($data);
                if (!$lv3) {
                    $data['alipay_cls_id'] = $val[4];
                    $data['alipay_category_id'] = $val[5];
                    $lv3 = $alipayService->create($data);
                }
                $count++;
            }

            $this->info("写入 $count 条支付宝行业类目数据(adapay)");
        }

        if ($type == 'wechat' || $type == 'all') {
            if (!$sheets[1]) {
                return false;
            }

            $count = 0;
            foreach ($sheets[1] as $val) {
                //过滤无用信息与不支持怼费率类型
                if ($val[0] != '01' && $val[0] != '02') {
                    continue;
                }

                $wechatService = new WxBusinessService();
                $exist = $wechatService->count(['fee_type' => $val[0], 'business_category_id' => $val[3]]);
                if (!$exist) {
                    $data = [
                        'fee_type' => $val[0],
                        'fee_type_name' => $val[1],
                        'merchant_type_name' => $val[2],
                        'business_category_id' => $val[3],
                    ];
                    $wechatService->create($data);
                }
                $count++;
            }

            $this->info("写入 $count 条微信经营类目数据(adapay)");
        }
    }
}
