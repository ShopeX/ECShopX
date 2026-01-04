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

namespace CompanysBundle\Services\Shops;

use EspierBundle\Services\Cache\RedisCacheService;
use GoodsBundle\Services\MultiLang\MagicLangTrait;

class ProtocolService
{
    use MagicLangTrait;
    /**
     * @var int
     */
    protected $companyId;

    /**
     * @var RedisCacheService
     */
    protected $cacheService;

    /**
     * 协议类型字典
     */
    public const TYPE_MEMBER_REGISTER = "member_register"; // 用户注册
    public const TYPE_PRIVACY = "privacy"; // 隐私政策
    public const TYPE_MEMBER_LOGOUT = "member_logout";  // 注销协议
    public const TYPE_MEMBER_LOGOUT_CONFIG = "member_logout_config"; // 注销配置
    /**
     * 类型标题的默认值
     */
    public const TYPE_TITLE_DEFAULT = [
        self::TYPE_MEMBER_REGISTER => "注册协议",
        self::TYPE_PRIVACY => "隐私政策",
        self::TYPE_MEMBER_LOGOUT => "注销协议",
        self::TYPE_MEMBER_LOGOUT_CONFIG => "订单完成之前，无法注销会员。如有疑问，请联系客服",
    ];

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $lang = strtolower($this->getLang());
        $lang = str_replace('-', '', $lang);
        if($lang !== 'zhcn'){
            // 关于redis的库选择是companys而不是members的问题：考虑到后期会有订单协议、活动协议等，所以同意整合进companys里会更合理一些
            $this->cacheService = (new RedisCacheService($companyId, "companyProtocol_".$lang))->setConnection("companys");
        }else{
            $this->cacheService = (new RedisCacheService($companyId, "companyProtocol"))->setConnection("companys");
        }


    }

    /**
     * 设置协议内容
     * @param string $type 协议的类型
     * @param array $params 协议数据
     * @return bool
     */
    public function set(string $type, array $params): bool
    {
        if (strpos($type, '_draft') === false && $type != self::TYPE_MEMBER_LOGOUT_CONFIG) {
            $params['digest'] = md5($params['content']);
        }
        $this->cacheService->hashSet([$type => json_encode($params, JSON_UNESCAPED_UNICODE)]);

        if (strpos($type, '_draft') === false && $type != self::TYPE_MEMBER_LOGOUT_CONFIG) {
            $logService = new ProtocolUpdateLogService();
            $log = $logService->lists(['company_id' => $this->companyId, 'type' => $type], ['created' => 'DESC'], 1, 1);
            if (!$log['list'] || $log['list'][0]['digest'] != $params['digest']) {
                $logData = [
                    'company_id' => $this->companyId,
                    'type' => $type,
                    'content' => $params['content'],
                    'digest' => $params['digest'],
                ];
                $logService->create($logData);
            }
        }
        return true;
    }

    /**
     * 获取协议信息
     * @param array|null $type
     * @return array
     */
    public function get(?array $type)
    {
        $data = $this->cacheService->hashGet($type);
        foreach ($data as $infoType => &$info) {
            $info = (array)jsonDecode($info);
            // 设置默认项
            if (empty($info["title"])) {
                $info["title"] = self::TYPE_TITLE_DEFAULT[$infoType] ?? "";
            }
            $draftTyep = $infoType.'_draft';
            $draft = $this->cacheService->hashGet([$draftTyep]);
            $arrDraft = (array)jsonDecode($draft) ?? [];
            $info['draft'] = (array)jsonDecode($arrDraft[$draftTyep]) ?? [];
        }
        return $data;
    }
}
