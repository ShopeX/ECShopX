<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands;

use Illuminate\Console\Command;
use EspierBundle\Entities\Address;

class SystemAddressInitialization extends Command
{
    /**
    * 命令行执行命令
    * @var string
    */
    protected $signature = 'system:address:initialization';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化系统地区数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // IDX: 2367340174
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // IDX: 2367340174
        try {
            $a = file_get_contents(storage_path('static/district.json'));
            $a = json_decode($a, 1);
        } catch (\Exception $e) {
            echo "读取地区json文件出错".$e->getMessage();
            return true;
        }
        if (!$a) {
            echo "未读取到地区json文件";
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $qb = $conn->createQueryBuilder()->delete('espier_address');
            $qb->execute();
            foreach ($a as $v) {
                $row = [
                    'id' => $v['value'],
                    'label' => $v['label'],
                    'parent_id' => '0',
                    'path' => $v['value']
                ];
                $conn->insert('espier_address', $row);
                foreach ($v['children'] as $v1) {
                    $row = [
                        'id' => $v1['value'],
                        'label' => $v1['label'],
                        'parent_id' => $v['value'],
                        'path' => implode(',', [$v['value'], $v1['value']])
                    ];
                    $conn->insert('espier_address', $row);
                    foreach ($v1['children'] as $v2) {
                        $row = [
                            'id' => $v2['value'],
                            'label' => $v2['label'],
                            'parent_id' => $v1['value'],
                            'path' => implode(',', [$v['value'], $v1['value'], $v2['value']])
                        ];
                        $conn->insert('espier_address', $row);
                    }
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            echo "导入地区到数据表出错：".$e->getMessage();
            return true;
        }
        $addressRepository = app('registry')->getManager('default')->getRepository(Address::class);
        $addressInfo = $addressRepository->lists(['parent_id' => 0]);
        $address = $addressInfo['list'];
        if (!$address) {
            echo "地区数据为空";
            return true;
        }
        foreach ($address as $k => $v) {
            $a = $addressRepository->lists(['parent_id' => $v['id']]);
            $address[$k]['children'] = $a['list'];
            foreach ($address[$k]['children'] as $k1 => $v1) {
                $b = $addressRepository->lists(['parent_id' => $v1['id']]);
                $address[$k]['children'][$k1]['children'] = $b['list'];
            }
        }
        $address = json_encode($address);
        app('redis')->connection('default')->set('address', $address);
    }
}
