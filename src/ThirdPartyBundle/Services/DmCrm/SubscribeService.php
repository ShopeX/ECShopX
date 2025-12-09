<?php

/**
 *  消息订阅处理类
 */
namespace ThirdPartyBundle\Services\DmCrm;

use App\Exceptions\Handler;
use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Jobs\DmCrm\SyncMemberLevelChangeJob;

class SubscribeService extends DmService
{
    public function getSign_1($msgContent, $timestamp)
    {
        $jsonObject = $msgContent;
        if (!is_array($msgContent)) {
            $jsonObject = json_decode($msgContent, true);
        }
        
        $resultBuilder = '';
        $this->appendParam($resultBuilder, 'appKey', $this->appKey);
        $this->appendParam($resultBuilder, 'params', $jsonObject);
        $this->appendParam($resultBuilder, 'currentTime', $timestamp);
        $resultBuilder .= $this->appSecret;

        return strtoupper(md5($resultBuilder)); 
    }

    private function appendParam(&$builder, $mapKey, $json) {
        if (is_array($json)) {
            // 处理数组类型
            if (array_values($json) === $json) {
                // 索引数组 (JSONArray)
                foreach ($json as $item) {
                    $this->appendParam($builder, '', $item);
                }
            } else {
                // 关联数组 (JSONObject)
                ksort($json); // 按键名排序
                foreach ($json as $key => $value) {
                    if (strcasecmp($key, 'sign') === 0 ||
                        strcasecmp($key, 'key') === 0 ||
                        $value === null) {
                        continue;
                    }
                    // 处理精度问题
                    if (is_float($value)) {
                        $str = number_format($value, 2, '.', '');
                    }
                    $this->appendParam($builder, $key, $value);
                }
            }
        } else {
            // 处理标量类型
            $str = strval($json);
            if (!empty($str)) {
                $builder .= $mapKey . '=' . $str . '&';
            }
        }
    }

    // 解密 msgContent
    public function decryptData($data)
    {
        /**
         * 1. 达摩侧提供密钥encodeAESKey
         * 2. Base64.decode(EncodingAESKey + “=”)生成aesKey
         * 3. 使用aes解密(AES/ECB/PKCS5Padding)
         */
        $key = $this->encodeAESKey;

        // 解密逻辑
        // 这里可以添加具体的解密代码

        return $data; // 返回解密后的数据
    }
    
    public function getSign($msgContent, $timestamp)
    {
        $resultBuilder = $this->appKey.$this->appSecret.$timestamp.$msgContent;
        $sign = sha1($resultBuilder);
        return $sign;
    }

    public function checkSign($params)
    {       
        $sign = $params['sign'];
        $timestamp = $params['timestamp'];
        $msgContent = $params['msgContent'];
        // 如果存在加密就先解密数据
        if ($params['encryptFlag'] === true || $params['encryptFlag'] == 'true') {
            $msgContent = $this->decryptData($msgContent);
        }

        if ($sign != $this->getSign($msgContent, $timestamp)) {
            throw new ResourceException("事件签名校验失败");    
        }
      
        return true;
    }
  
    private function getMap($msgContent)
    {
        // 主题_事件 对应 job类
        $key = $msgContent['topic'].'_'.$msgContent['event'];
        $teMap =  [
            'member_gradeChange' => '\ThirdPartyBundle\Jobs\DmCrm\SyncMemberLevelChangeJob', // 会员等级变更
            'member_baseInfoChange' => '\ThirdPartyBundle\Jobs\DmCrm\SyncBaseInfoChangeJob', // 会员等级基本信息变更
            'member_integralChange' => '\ThirdPartyBundle\Jobs\DmCrm\SyncIntegralChangeJob', // 会员积分变更
            'card_cardTemplateCreate' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardTemplateCreateJob', // 会员卡模板创建
            'card_cardTemplateModify' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardTemplateModifyJob', // 会员卡模板更新
            'card_cardTemplateDelete' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardTemplateDeleteJob', // 会员卡模板删除
            'card_cardDestroyBatch' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardDestroyBatchJob', // 会员卡券批量销毁
            'card_cardDestroy' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardDestroyJob', // 会员卡券销毁（单个）
            'card_cardReceive' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardReceiveJob', // 会员卡券领取(单个)
            'card_cardPutOn' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardPutOnJob', // 会员卡券投放(批量)
            'card_cardOccupy' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardOccupyJob', // 会员卡券占用
            'card_cardRelease' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardReleaseJob', // 会员卡券解除占用
            'card_cardConsume' => '\ThirdPartyBundle\Jobs\DmCrm\SyncCardConsumeJob', // 会员卡券核销
        ];
        $classObj = $teMap[$key] ?? ''; 
        if (empty($classObj)) {
            throw new ResourceException("订阅主题事件不存在...");
        }

        return $classObj;
    }

    private function asyncHandle($companyId, $msgContent)
    {
        $classObj = $this->getMap($msgContent);
        $gotoJob = (new $classObj($companyId, $msgContent['msgBody']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return true;
    }

    private function syncHandle($companyId, $msgContent)
    {
        $classObj = $this->getMap($msgContent);
        (new $classObj($companyId, $msgContent['msgBody']))->handle();

        return true;
    }

    /**
     * 回调事件调度处理
     * @return void
     */
    public function dispatchEvent($companyId, $params)
    {
        // 验签
        $this->checkSign($params);
        
        // 处理对应事件
        $msgContent = $params['msgContent'];
        if (!is_array($params['msgContent'])) {
            $msgContent = json_decode($params['msgContent'], true);
        }
        $this->syncHandle($companyId, $msgContent);
        // 异步
        // $this->asyncHandle($companyId, $msgContent);

        return true;
    }

}
