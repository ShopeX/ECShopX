<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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