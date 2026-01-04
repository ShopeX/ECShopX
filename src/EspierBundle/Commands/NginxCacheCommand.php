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

class NginxCacheCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'ngcache {uri?}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '手动生成nginx的memcached缓存';

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
        // XXX: review this code
        $uri  = $this->argument('uri');
        if (!$uri) {
            echo "请输入uri(斜杠开头并且包括参数部分)\n";
            exit;
        }
        $this->cache($uri);
    }

    private function cache($uri)
    {
        $host = 'http://127.0.0.1:8090';
        // $uri = "/api/h5app/wxapp/goods/items/{$itemid}?goods_id=&distributor_id=0&company_id=2";
        $md5_key = md5($uri);
        $url = $host . $uri;

        $http = new \GuzzleHttp\Client;
        $json_response = $http->get($url)->getBody()->getContents(); //json
$content = <<<EOF
HTTP/1.1 200 OK
Content-Type: application/json
X-Powered-By: PHP/7.2.22
Cache-Control: no-cache, private
Access-Control-Allow-Origin: *
Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With
Access-Control-Expose-Headers: Authorization
Access-Control-Allow-Methods: DELETE, GET, HEAD, POST, PUT, OPTIONS, TRACE, PATCH

{$json_response}
EOF;
        echo 'key:'.$uri."\n";
        echo 'md5key:'.$md5_key."\n";
        Cache::store('memcached')->put($md5_key, $content, 600);
    }

}
