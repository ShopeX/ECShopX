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
