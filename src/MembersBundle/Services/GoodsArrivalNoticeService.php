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

namespace MembersBundle\Services;

use GoodsBundle\Services\ItemsService;
use MembersBundle\Entities\SubscribeNotice;
use MembersBundle\Interfaces\SubscribeInterface;

class GoodsArrivalNoticeService implements SubscribeInterface
{
    public $key = 'arrivalNoticeSub';

    public $membersSubscribeNoticeRepository;

    public $storage = 'mysql';

    public function __construct()
    {
        $this->membersSubscribeNoticeRepository = app('registry')->getManager('default')->getRepository(SubscribeNotice::class);
    }

    public function create(array $subInfo)
    {
        $subInfo['distributor_id'] = $subInfo['distributor_id'] ?? 0;
        $filter = [
                'user_id' => $subInfo['user_id'],
                'company_id' => $subInfo['company_id'],
                'rel_id' => $subInfo['item_id'],
                'sub_type' => 'goods',
                'sub_status' => 'NO',
                'source' => $subInfo['source'],
                'distributor_id' => $subInfo['distributor_id'],
            ];
        if ($this->membersSubscribeNoticeRepository->findOneBy($filter)) {
            //删除
            $this->membersSubscribeNoticeRepository->deleteBy($filter);
        } else {
            //创建
            $itemService = new ItemsService();
            $itemInfo = $itemService->get($subInfo['item_id']);
            $filter['remarks'] = $itemInfo['item_name'];
            $filter['open_id'] = $subInfo['open_id'];
            $this->membersSubscribeNoticeRepository->create($filter);
        }
        //检测是否存在
        if ($this->exists($subInfo['company_id'], $subInfo['source'], $subInfo['user_id'], $subInfo['item_id'], $subInfo['distributor_id'])) {
            //删除
            $this->delete($subInfo);
        } else {
            $data = json_encode(['open_id' => $subInfo['open_id'], 'wxa_appid' => $subInfo['wxa_appid']]);
            if ($subInfo['source'] == 'wechat') {
                if ($subInfo['distributor_id'] > 0) {
                    app('redis')->hset($this->key . ':' . $subInfo['company_id'] . ':' . $subInfo['item_id'] . ':' . $subInfo['distributor_id'], $subInfo['user_id'], $data);
                } else {
                    app('redis')->hset($this->key . ':' . $subInfo['company_id'] . ':' . $subInfo['item_id'], $subInfo['user_id'], $data);
                }
            } else {
                if ($subInfo['distributor_id'] > 0) {
                    app('redis')->hset($this->key . ':' . $subInfo['source'] . ':' . $subInfo['company_id'] . ':' . $subInfo['item_id'] . ':' . $subInfo['distributor_id'], $subInfo['user_id'], $data);
                } else {
                    app('redis')->hset($this->key . ':' . $subInfo['source'] . ':' . $subInfo['company_id'] . ':' . $subInfo['item_id'], $subInfo['user_id'], $data);
                }
            }
        }
        //获取
        $info = $this->membersSubscribeNoticeRepository->getInfo([
                'user_id' => $subInfo['user_id'],
                'company_id' => $subInfo['company_id'],
                'rel_id' => $subInfo['item_id'],
                'sub_type' => 'goods',
                'sub_status' => 'NO',
                'source' => $subInfo['source'],
                'distributor_id' => $subInfo['distributor_id'],
            ]);
        if (!$info) {
            return [];
        }
        return [
                'user_id' => $info['user_id'],
                'item_id' => $info['rel_id'],
                'item_name' => $info['remarks']
            ];
    }

    /**
     * 删除一个字段
     * @param $filter
     */
    public function delete($filter)
    {
        if ($filter['source'] == 'wechat') {
            if ($filter['distributor_id'] > 0) {
                app('redis')->hdel($this->key . ':' . $filter['company_id'] . ':' . $filter['item_id'] . ':' . $filter['distributor_id'], $filter['user_id']);
            } else {
                app('redis')->hdel($this->key . ':' . $filter['company_id'] . ':' . $filter['item_id'], $filter['user_id']);
            }
        } else {
            if ($filter['distributor_id'] > 0) {
                app('redis')->hdel($this->key . ':' . $filter['source'] . ':' . $filter['company_id'] . ':' . $filter['item_id'] . ':' . $filter['distributor_id'], $filter['user_id']);
            } else {
                app('redis')->hdel($this->key . ':' . $filter['source'] . ':' . $filter['company_id'] . ':' . $filter['item_id'], $filter['user_id']);
            }
        }
    }

    /**
     * 获取订阅用户列表
     * @param $filter
     * @return array
     */
    public function getList($filter, $source = 'wechat', $user_id = null)
    {
        $filter['distributor_id'] = $filter['distributor_id'] ?? 0;
        //获取商品详情
        $itemService = new ItemsService();
        $list = $itemService->list(['company_id' => $filter['company_id'], 'item_id' => $filter['item_id']], [], -1);
        $nameList = array_column($list['list'], 'item_name', 'item_id');
        $list = [];
        foreach ($filter['item_id'] as $v) {
            if ($user_id) {
                if ($source == 'wechat') {
                    if ($filter['distributor_id'] > 0) {
                        $subList = app('redis')->hget($this->key . ':' . $filter['company_id'] . ':' . $v . ':' . $filter['distributor_id'], $user_id);
                    } else {
                        $subList = app('redis')->hget($this->key . ':' . $filter['company_id'] . ':' . $v, $user_id);
                    }
                } else {
                    if ($filter['distributor_id'] > 0) {
                        $subList = app('redis')->hget($this->key . ':' . $source . ':' . $filter['company_id'] . ':' . $v . ':' . $filter['distributor_id'], $user_id);
                    } else {
                        $subList = app('redis')->hget($this->key . ':' . $source . ':' . $filter['company_id'] . ':' . $v, $user_id);
                    }
                }
                
                if ($subList) {
                    return [
                        'user_id' => $user_id,
                        'item_id' => $v,
                        'item_name' => $nameList[$v]??'',
                    ];
                } else {
                    return [];
                }
            } else {
                if ($source == 'wechat') {
                    if ($filter['distributor_id'] > 0) {
                        $subList = app('redis')->hgetall($this->key . ':' . $filter['company_id'] . ':' . $v . ':' . $filter['distributor_id']);
                    } else {
                        $subList = app('redis')->hgetall($this->key . ':' . $filter['company_id'] . ':' . $v);
                    }
                } else {
                    if ($filter['distributor_id'] > 0) {
                        $subList = app('redis')->hgetall($this->key . ':' . $source . ':' . $filter['company_id'] . ':' . $v . ':' . $filter['distributor_id']);
                    } else {
                        $subList = app('redis')->hgetall($this->key . ':' . $source . ':' . $filter['company_id'] . ':' . $v);
                    }
                }
                foreach ($subList as $k => $sub) {
                    $data = json_decode($sub, true);
                    $list[] = [
                        'open_id' => $data['open_id'],
                        'wxa_appid' => $data['wxa_appid'],
                        'user_id' => $k,
                        'item_id' => $v,
                        'item_name' => $nameList[$v],
                    ];
                }
            }
        }
        return $list;
    }

    /**
     * 检测是否订阅
     * @param $company_id
     * @param $user_id
     * @param $item_id
     * @return mixed
     */
    public function exists($company_id, $source, $user_id, $item_id, $distributor_id)
    {
        if ($source == 'wechat') {
            if ($distributor_id > 0) {
                return app('redis')->HEXISTS($this->key . ':' . $company_id . ':' . $item_id . ':' . $distributor_id, $user_id);
            } else {
                return app('redis')->HEXISTS($this->key . ':' . $company_id . ':' . $item_id, $user_id);
            }
        } else {
            if ($distributor_id > 0) {
                return app('redis')->HEXISTS($this->key . ':' . $source . ':' . $company_id . ':' . $item_id . ':' . $distributor_id, $user_id);
            } else {
                return app('redis')->HEXISTS($this->key . ':' . $source . ':' . $company_id . ':' . $item_id, $user_id);
            }
        }
    }

    /**
     * 删除key
     * @param $company_id
     * @param $item_id
     */
    public function deleteAll($company_id, $source, $item_id, $distributor_id = 0)
    {
        if ($source == 'wechat') {
            if ($distributor_id > 0) {
                app('redis')->del($this->key . ':' . $company_id . ':' . $item_id . ':' . $distributor_id);
            } else {
                app('redis')->del($this->key . ':' . $company_id . ':' . $item_id);
            }
        } else {
            if ($distributor_id > 0) {
                app('redis')->del($this->key . ':' . $source . ':' . $company_id . ':' . $item_id . ':' . $distributor_id);
            } else {
                app('redis')->del($this->key . ':' . $source . ':' . $company_id . ':' . $item_id);
            }
        }
    }

    /**
     * 获取订阅列表
     *
     * @param $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return mixed
     */
    public function lists($filter, $page = 1, $pageSize = 20, $orderBy = array('created' => 'DESC'))
    {
        $membersService = new MemberService();
        $itemService = new ItemsService();

        $result = $this->membersSubscribeNoticeRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        foreach ($result['list'] as &$value) {
            if ($value['user_id']) {
                $member = $membersService->getMemberInfo(['user_id' => $value['user_id'], 'company_id' => $value['company_id']]);
                $value['username'] = $member['username'] ?? '匿名';
            }
            if ($filter['sub_type'] == 'goods') {
                $item = $itemService->getInfo(['item_id' => $value['rel_id'], 'company_id' => $value['company_id']]);
                $value['item_name'] = $item['item_name'] ?? '';
            }
        }
        return $result;
    }

    public function __call($name, $arguments)
    {
        return $this->membersSubscribeNoticeRepository->$name(...$arguments);
    }
}
