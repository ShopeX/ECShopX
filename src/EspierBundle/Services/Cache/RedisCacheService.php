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

namespace EspierBundle\Services\Cache;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Redis\Connections\PredisConnection;

/**
 * redis的缓存处理
 * Class RedisCacheService
 * @package EspierBundle\Services\Cache
 */
class RedisCacheService
{
    /**
     * 获取企业id
     * @var int
     */
    protected $companyId;

    /**
     * 处理的缓存名
     * @var string
     */
    protected $cacheName = "";

    /**
     * 连接的配置项
     * @var string
     */
    protected $connection = "default";

    /**
     * @param string $connection
     * @return $this
     */
    public function setConnection(string $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * 数据缓存的时间
     * @var int
     */
    protected $ttl = 60;

    /**
     * 锁的过期时间，如果操作成功会主动释放锁
     * @var int
     */
    protected $lockTtl = 60;

    public function __construct(int $companyId, string $cacheName, ?int $ttl = 60)
    {
        // TS: 53686f704578
        $this->companyId = $companyId;
        $this->cacheName = $cacheName;
        $this->ttl = $ttl;
    }


    /**
     * 生成带过期时间的string类型的缓存
     * @return string
     */
    public function generateGetName(): string
    {
        return sprintf("%s:%s", $this->cacheName, sha1($this->companyId));
    }

    /**
     * 生成防止缓存穿透的string类型的缓存
     * @return string
     */
    public function generatePreventionName(): string
    {
        return sprintf("prevention:%s:%s", $this->cacheName, sha1($this->companyId));
    }

    /**
     * 缓存缓存的锁
     * @param string $cacheName
     * @return string
     */
    public function generateLockName(string $cacheName = ""): string
    {
        if (empty($cacheName)) {
            $cacheName = sprintf("%s:%s", $this->cacheName, sha1($this->companyId));
        }
        return md5(sprintf("lock:%s", $cacheName));
    }

    /**
     * 获取hash的缓存
     * @return string
     */
    public function generateHashName(): string
    {
        return sprintf("hash:%s:%s", $this->cacheName, sha1($this->companyId));
    }

    /**
     * 直接获取, 在请求量比较大的时候会有击穿的风险
     * @param \Closure $makeDataCallback
     * @param bool $forceCover 是否强制覆盖，【true 强制覆盖】【false 不强制覆盖】
     * @return string
     */
    public function get(\Closure $makeDataCallback, bool $forceCover = false): string
    {
        $name = $this->generateGetName();
        $redisHandle = $this->getRedisHandle();
        $result = $redisHandle->get($name);
        if (is_null($result) || $forceCover) {
            $callbackData = (string)$makeDataCallback();
            if (is_null($this->ttl)) {
                $redisHandle->set($name, $callbackData);
            } else {
                $redisHandle->setex($name, $this->ttl, $callbackData);
            }
            return $callbackData;
        }
        return $result;
    }

    /**
     * 获取缓存(防止缓存击穿)
     * @param \Closure $makeDataCallback
     * @return mixed
     */
    public function getByPrevention(\Closure $makeDataCallback)
    {
        $cacheName = $this->generatePreventionName();
        $redisHandle = $this->getRedisHandle();

        $result = (array)jsonDecode($redisHandle->get($cacheName));
        // 获取过期时间
        $expireTime = (int)($result["expire_time"] ?? 0);
        // 如果未过期
        if ($expireTime > 0 && $expireTime >= time()) {
            return $result["data"] ?? null;
        }
        // 如果过期了
        if ($this->setLock($cacheName)) {
            if (is_null($this->ttl)) {
                throw new ResourceException("操作失败！缓存时间必须大于0秒");
            }
            // 如果没有锁，但是刚刚加上的，则立即去更新数据
            $callbackData = $makeDataCallback();
            $redisHandle->set($cacheName, json_encode(["expire_time" => time() + $this->ttl, "data" => $callbackData], JSON_UNESCAPED_UNICODE));
            $this->delLock($cacheName);
            return $callbackData;
        } else {
            // 如果有锁就直接返回原数据
            return $result["data"] ?? null;
        }
    }

    //删除缓存
    public function delete() {
        $cacheName = $this->generatePreventionName();
        $redisHandle = $this->getRedisHandle();
        $redisHandle->del($cacheName);
    }

    /**
     * 获取redis连接句柄
     * @return mixed
     */
    public function getRedisHandle(): PredisConnection
    {
        return app('redis')->connection($this->connection);
    }

    /**
     * 设置锁
     * 如果存在锁就返回false
     * 如果不存在锁就设置锁且返回true
     * @param string $cacheName 缓存名
     * @return bool
     */
    public function setLock(string $cacheName = ""): bool
    {
        // 设置锁的名字
        $lockCacheName = $this->generateLockName($cacheName);
        // 获取连接句柄
        $redisHandle = $this->getRedisHandle();
        // 如果存在锁就返回false
        // 如果不存在锁就设置锁且返回true
        if ($redisHandle->exists($lockCacheName)) {
            return false;
        } else {
            $redisHandle->setex($lockCacheName, $this->lockTtl, 1);
            return true;
        }
    }

    /**
     * 释放锁
     * @param string $cacheName 缓存名
     */
    public function delLock(string $cacheName = "")
    {
        // 设置锁的名字
        $this->getRedisHandle()->del($this->generateLockName($cacheName));
    }

    /**
     * 设置hash值
     * @param array $fieldAndValue 每一个参数都是一个数组（key为字段名，value为字段值）
     * @return bool
     */
    public function hashSet(array $fieldAndValue): bool
    {
        $name = $this->generateHashName();
        $redisHandle = $this->getRedisHandle();
        $redisHandle->hmset($name, $fieldAndValue);
        return true;
    }

    /**
     * 获取hash中的内容
     * @param array|null $typeArray
     * @return array
     */
    public function hashGet(?array $typeArray): array
    {
        $name = $this->generateHashName();
        if (is_null($typeArray)) {
            return (array)$this->getRedisHandle()->hgetall($name);
        } else {
            // 没有用hmget的原因是hmget不能反悔field
            $result = [];
            foreach ($typeArray as $type) {
                $result[$type] = $this->getRedisHandle()->hget($name, $type);
            }
            return $result;
        }
    }
}
