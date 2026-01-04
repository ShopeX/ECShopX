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

namespace DataCubeBundle\Services;
/**
 * 布隆过滤器实现UV统计
 */

class UVBloomFilterService {
    private $bitKey;
    private $size;
    private $hashCount;

    /**
     * 初始化布隆过滤器
     * 
     * @param int $monitorId 监控ID
     * @param int $sourceId 监控来源ID
     * @param int $expectedItems 预期元素数量
     * @param float $falsePositiveRate 可接受误判率
     */
    public function __construct($monitorId, $sourceId, $expectedItems = 100000, $falsePositiveRate = 0.01) {
        $this->bitKey = 'uv_bloom_filter:'.$monitorId.':'.$sourceId;
        
        // 计算最优比特数组大小
        $this->size = $this->calculateSize($expectedItems, $falsePositiveRate);
        
        // 计算最优哈希函数数量
        $this->hashCount = $this->calculateHashCount($this->size, $expectedItems);
    }

    /**
     * 计算所需比特数组大小
     */
    private function calculateSize($n, $p) {
        // m = -(n * ln(p)) / (ln(2)^2)
        return (int)ceil(-($n * log($p)) / pow(log(2), 2));
    }
    
    /**
     * 计算最优哈希函数数量
     */
    private function calculateHashCount($m, $n) {
        // k = (m / n) * ln(2)
        return (int)ceil(($m / $n) * log(2));
    }

    /**
     * 计算多个哈希值（使用双哈希法）
     */
    private function getHashes($str) {
        $hashes = [];
        // 使用两种不同的哈希算法
        $hash1 = $this->crc32($str);
        $hash2 = $this->fnv1a32($str);
        
        for ($i = 0; $i < $this->hashCount; $i++) {
            $hashes[] = abs(($hash1 + $i * $hash2) % $this->size);
        }
        
        return $hashes;
    }

    /**
     * CRC32哈希函数
     */
    private function crc32($str) {
        return crc32($str) & 0xFFFFFFFF;
    }
    
    /**
     * FNV-1a 32位哈希函数
     */
    private function fnv1a32($str) {
        $prime = 16777619;
        $offset = 2166136261;
        
        $hash = $offset;
        $len = strlen($str);
        
        for ($i = 0; $i < $len; $i++) {
            $hash ^= ord($str[$i]);
            $hash = ($hash * $prime) & 0xFFFFFFFF;
        }
        
        return $hash;
    }

    /**
     * 检查并添加OpenID（原子操作）
     */
    public function checkAndAdd($str) {
        $positions = $this->getHashes($str);
        
        // 使用Lua脚本保证原子性操作
        $lua = <<<LUA
            local key = KEYS[1]
            local k = tonumber(ARGV[1])
            
            -- 检查所有位是否已设置
            for i = 2, k + 1 do
                if redis.call('GETBIT', key, ARGV[i]) == 0 then
                    -- 有新位未设置，添加用户
                    for j = 2, k + 1 do
                        redis.call('SETBIT', key, ARGV[j], 1)
                    end
                    return 1
                end
            end
            
            -- 所有位已设置，用户已存在
            return 0
LUA;
        
        // 准备参数
        $args = [$this->hashCount];
        $args = array_merge($args, $positions);
        
        $result = app('redis')->eval(
            $lua,
            1,
            ...array_merge([$this->bitKey], $args)
        );
        
        return $result == 1;
    }
}