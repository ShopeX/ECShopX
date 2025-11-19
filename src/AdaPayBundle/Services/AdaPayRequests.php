<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);
/**
 * This file is part of Shopex .
 *
 * @link     https://www.shopex.cn
 * @document https://club.shopex.cn
 * @contact  dev@shopex.cn
 */
namespace AdaPayBundle\Services;

use GuzzleHttp\Client;

class AdaPayRequests
{
    public function curl_request($url, $postFields = null, $headers = null, $is_json = false, $has_file = false)
    {
        $response = '';
        $statusCode = 0;
        try {
            $client = new Client([
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => 30,
            ]);
            app('log')->info('请求地址：' . $url);
            if (is_array($postFields)) {
                if ($is_json) {
                    $resp = $client->request('POST', $url, ['json' => $postFields]);
                } else {
                    if ($has_file) {
                        $multipart = [];
                        foreach ($postFields as $k => $v) {
                            $multipart[] = [
                                'name' => $k,
                                'contents' => $v,
                            ];
                        }
                        //文件上传
                        $resp = $client->request('POST', $url, [
                            'multipart' => $multipart,
                        ]);
                        app('log')->info(' 文件上传 ');
                    } else {
                        $resp = $client->request('POST', $url, ['form_params' => $postFields]);
                    }
                }
            } else {
                $resp = $client->get($url);
            }
            $response = $resp->getBody()->getContents();
            $statusCode = $resp->getStatusCode();
            app('log')->info('curl返回参数:' . $response);
        } catch (\Throwable $throwable) {
            var_dump($throwable->getMessage());
            app('log')->error($throwable->getMessage());
        }

        return [$statusCode, $response];
    }
}
