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

namespace AliyunsmsBundle\Jobs;

use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class AddSmsSign extends Job
{
    private $params;
    public function __construct($params = [])
    {
        ini_set('memory_limit', '-1');
        $this->params = $params;
    }

    public function handle()
    {
        // $defaultImg = storage_path('static/aliyunsms_sign_default.png');
        $client = new AliyunSmsClient($this->params['company_id']);
        // if($this->params['sign_file']) {
        //     $this->params['sign_file'] = $this->getImg($this->params['sign_file']);
        // }
        // if($this->params['delegate_file']) {
        //     $this->params['delegate_file'] = $this->getImg($this->params['delegate_file']);
        // }
        // if(!$this->params['sign_file'] && !$this->params['delegate_file']) {
        //     $this->params['sign_file'] = $this->getImg($defaultImg);
        // }
        $result = $client->addSmsSign($this->params);
        return true;
    }
    public function getImg($img_file) {
        $img_base64 = '';
        $img_file = $img_file; // 图片路径
        $img_info = getimagesize($img_file); // 取得图片的大小，类型等
        $file_content = base64_encode(file_get_contents($img_file));
        switch ($img_info[2]) {           //判读图片类型
            case 1: $img_type = "gif";
                break;
            case 2: $img_type = "jpg";
                break;
            case 3: $img_type = "png";
                break;
        }
        return ['fileContents' => $file_content, 'fileSuffix' => $img_type]; //返回图片信息
    }
}
