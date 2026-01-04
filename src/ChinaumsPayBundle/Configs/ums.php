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

return [
    'uri'     => env('UMS_URI', 'https://api-mop.chinaums.com/v1/netpay'),
    'AppId' => env('UMS_APP_ID'),
    'AppKey'   => env('UMS_APP_KEY'),
    'Md5Key'   => env('UMS_md5_KEY'),
    'pre' => '32C2',
    'group_no' => env('CHINAUMSPAY_GROUP_NO'),// 商户集团编号
    'sftp' => [
        'host' => env('UMS_SFTP_HOST'),
        'port' => 22,
        'username' => env('UMS_SFTP_USERNAME'),
        'password' => env('UMS_SFTP_PASSWORD'),
        'timeout' => 10,
    ],
];