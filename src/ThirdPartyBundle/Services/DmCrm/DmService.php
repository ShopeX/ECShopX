<?php

/**
 *  dm 基础类
 *  目前大部分接口默认调用频次500/分，10w/天(24小时)；
 */
namespace ThirdPartyBundle\Services\DmCrm;

use ThirdPartyBundle\Services\DmCrm\DmCrmLogService;
use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Jobs\DmCrm\DmApiLogsJob;

class DmService
{
    protected $companyId;
    public $isOpen = false;
    protected $baseUrl;
    protected $appKey;
    protected $appSecret;
    protected $entSign;
    protected $encodeAESKey; // 达摩侧提供的密钥, 如果参数加密必须要秘钥解密
    protected $tokenRedisKey;

    public function __construct($companyId = 0)
    {
        $dmCrmSetting = [];
        if ($companyId > 0) {
            $dmCrmSettingService = new DmCrmSettingService();
            $dmCrmSetting = $dmCrmSettingService->getDmCrmSetting($companyId);
        }
        // 初始化服务
        $this->companyId = $companyId;
        $this->isOpen = $dmCrmSetting['is_open'] ?? false;
        $this->baseUrl = 'https://hope.demogic.com';
        $this->appKey = $dmCrmSetting['app_key'] ?? '';
        $this->appSecret = $dmCrmSetting['app_secret'] ?? '';
        $this->entSign = $dmCrmSetting['ent_sign'] ?? '';
        $this->encodeAESKey = $dmCrmSetting['encodeAESKey'] ?? '';
        $this->tokenRedisKey = 'damo_token:'.$companyId;
    }

    /**
     * 获取达摩CRM token
     * token两小时内不变，请自己维护token；旧的token有3小时有效期；建议每小时调用一次getToken，刷新token；
     * @return string|null
     */
    public function getToken()
    {
        // 先查缓存
        $tokenInfo = app('redis')->get($this->tokenRedisKey);
        if ($tokenInfo) {
            $tokenInfo = json_decode($tokenInfo, true);
            $now = time() * 1000; // 毫秒
            $expireTime = (int)$tokenInfo['expireTime'];

            // 如果 token 距过期 < 1 小时，则刷新
            if ($expireTime - $now > (60 * 60 * 1000)) {
                return $tokenInfo['token'];
            }
        }

        // 调接口获取新 token
        $newToken = $this->fetchNewToken();

        return $newToken;
    }

    /**
     * 调接口获取新 token 并写缓存
     *
     * @return string|null
     */
    protected function fetchNewToken()
    {
        $worker = '/cgi-api/auth/get_token';

        $payload = [
            'appKey'    => $this->appKey,
            'appSecret' => $this->appSecret,
        ];
        $client = new Request();
        $url = $this->baseUrl . $worker;
        $response = $client->requestApiPost($url, $payload, [], 3, 30);
        if ($response['status'] == 200) {
            $data = json_decode($response['body'], true);

            if (isset($data['code']) && $data['code'] === '0') {
                $token = $data['result']['token'] ?? null;
                $expireTime = $data['result']['expireTime'] ?? null;

                if ($token && $expireTime) {
                    // 写缓存，过期时间为 token 剩余时间
                    $now = time() * 1000; // 毫秒
                    $ttlMillis = $expireTime - $now;

                    // 防止负数或 0
                    $ttlSeconds = max(60, intval($ttlMillis / 1000));

                    app('redis')->setex(
                        $this->tokenRedisKey,
                        $ttlSeconds,
                        json_encode([
                            'token' => $token,
                            'expireTime' => $expireTime,
                        ])
                    );

                    return $token;
                }
            } else {
                app('log')->error('DamogCrm::获取token失败:'.json_encode($data));
            }
        } else {
            app('log')->error('DamogCrm::获取token失败:'.json_encode($response));
        }

        return null;
    }

    private function getUrl($worker)
    {
        $url = $this->baseUrl. $worker.'?';
        $token = $this->getToken();
        $urlStr = http_build_query(['token' => $token, 'entSign' => $this->entSign]);
        $url .= $urlStr;
        return $url;
    }

    private function commonParams()
    {

    }

    /**
     *
     * @param $worker  // 业务接口
     * @param $params  // 业务参数
     * $param $isJson // 是否json格式 Content-Type：application/json
     * @return void
     */
    public function requestApiPost($worker, $params, $headers = [])
    {
        try {
            // 如果需要公共基础参数，则调用commonParams方法获取
            $url = $this->getUrl($worker);
            $client = new Request();
            $response = $client->requestApiPost($url, $params, $headers, 3, 30);
            $responseBody = json_decode($response['body'], true);
            // 记录日志
            $this->apiLogs($worker, $params, $responseBody);

            return $responseBody;
        }catch (\Exception $e) {
            $responseBody = $e->getMessage();
            $this->apiLogs($worker, $params, $responseBody, 'request','fail');
        }

        return false;
    }

    // 用于统一返回响应数据
    public function returnResponse($data)
    {
        if (!$data) {
            throw new ResourceException( '接口获取失败');
        }

        return $data;
    }

    public function returnResponseException($data, $code = 0)
    {
        if (!$data) {
            throw new ResourceException( '接口获取失败');
        }
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        if (isset($data['code']) && $data['code'] != $code) {
             throw new ResourceException( $data['message']);
        }
        
        return $data['result'];
    }

    public function apiLogs($worker, $params, $result, $apiType = 'request', $status = 'success')
    {
        app('log')->debug("Dm apiLogs worker:".var_export($worker, true));
        app('log')->debug("Dm apiLogs params:".var_export($params, true));
        app('log')->debug("Dm apiLogs result:".var_export($result, true));
        // 记录日志
        $data = [
            'company_id' => $this->companyId,
            'worker' => $worker,
            'params' => $params,
            'result' => $result,
            'api_type' => $apiType,
            'status' => $status,
            'runtime' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // (new DmApiLogsJob($data))->handle();
        $gotoJob = (new DmApiLogsJob($data))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        
        return true;
    }

}
