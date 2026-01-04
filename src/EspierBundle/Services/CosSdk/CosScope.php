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

namespace EspierBundle\Services\CosSdk;

class CosScope{
    private $action;
    private $bucket;
    private $region;
    private $resourcePrefix;
    public function __construct($action, $bucket, $region, $resourcePrefix){
        $this->action = $action;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->resourcePrefix = $resourcePrefix;
    }
    public function get_action(){
        return $this->action;
    }

    public function get_resource(){
        $index = strripos($this->bucket, '-');
        $bucketName = substr($this->bucket, 0, $index);
        $appid = substr($this->bucket, $index + 1);
        if(!(strpos($this->resourcePrefix, '/') === 0)){
            $this->resourcePrefix = '/' . $this->resourcePrefix;
        }
        return 'qcs::cos:' . $this->region . ':uid/' . $appid . ':prefix//' . $appid . '/' . $bucketName . $this->resourcePrefix;
    }
}
