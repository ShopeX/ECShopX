<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands;

use Illuminate\Console\Command;

class HyperfTransferCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'hyperf:transfer';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '生成迁移到hyperf的部分数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->handleDirectoryFile(function ($pathName) {
            $pathInfo = pathinfo($pathName);
            $path = explode('/', $pathInfo['dirname']);
            $entity = "\\".$path['1']."\\".$path['2']."\\".$pathInfo['filename'];
            $tablename = app('registry')->getManager('default')->getClassMetadata($entity)->getTableName();
            # 生成entity
            $entityCommand = "php bin/hyperf.php gen:model {$tablename} --path={$pathInfo['dirname']}/ --table-mapping={$tablename}:{$pathInfo['filename']}";
            echo $entityCommand."\n";

            # 生成repository
            // $repo = app('registry')->getManager('default')->getClassMetadata($entity)->customRepositoryClassName;
            // $repo = explode("\\", $repo);
            // $repoName = $repo['2'];
            // $repoPath = $path[0].'/'.$path[1].'/'.'Repositories/';
            // $repositoryCommand = "php bin/hyperf.php gen:repository --table={$tablename} --path={$repoPath} --entity={$pathInfo['filename']} --repository={$repoName}";
            // echo $repositoryCommand."\n";
        }, 'src', 'Entities/');
    }

    /**
     * 处理目录文件.
     * @param callable $callback 闭包方法
     * @param string $baseDir 基础目录
     * @param string $needle 需要判断目录的条件
     */
    public function handleDirectoryFile(callable $callback, string $baseDir = 'src', string $needle = ''): void
    {
        $entity = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir));
        foreach ($entity as $key => $val) {
            if (! is_file($val->getPathName())) {
                continue;
            }
            if (((! $needle) || (strpos($val->getPathName(), $needle) !== false)) && $callback) {
                $callback($val->getPathName());
            }
        }
    }
}
