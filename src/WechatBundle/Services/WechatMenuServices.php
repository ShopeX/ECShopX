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

namespace WechatBundle\Services;

class WechatMenuServices
{
    /**
     * WechatMenuServices 构造函数.
     */
    public function __construct()
    {
    }

    /**
     * 微信扩展菜单类型
     */
    public function wxsys()
    {
        $wxsys = array(
            'scancode_waitmsg' => "扫码带提示",
            'scancode_push' => "扫码推事件",
            'pic_sysphoto' => "系统拍照发图",
            'pic_photo_or_album' => "拍照或者相册发图",
            'pic_weixin' => "微信相册发图",
            'location_select' => "发送位置",
        );
        return $wxsys;
    }

    /**
     * redis存储时的 key 处理
     */
    private function genId($authorizerAppId, $companyId)
    {
        return 'menuTree:'. sha1($authorizerAppId.$companyId.'menu_tree');
    }

    /**
     *  保存菜单至 redis 并推送至微信
     */
    public function addMenuTree($authorizerAppId, $companyId, $menuTree)
    {
        $genId = $this->genId($authorizerAppId, $companyId);
        $result = app('redis')->connection('wechat')->set($genId, json_encode($menuTree));
        if ($result) {
            $result = $this->pushWechatMenu($authorizerAppId, $menuTree);
            return $result;
        }
        return array();
    }

    /**
     * 获取菜单树形结构
     */
    public function getMenuTree($authorizerAppId, $companyId)
    {
        $genId = $this->genId($authorizerAppId, $companyId);
        $menu = app('redis')->connection('wechat')->get($genId);
        return json_decode($menu, 1);
    }

    /**
     * 推送菜单至微信
     */
    public function pushWechatMenu($appid, $menuTree)
    {
        $wechatMenu = array();
        foreach ($menuTree as $fKey => $fmenu) {
            if (!isset($fmenu['second_menu']) || !$fmenu['second_menu']) {
                $wechatMenu[$fKey] = $this->_getMenuData($fmenu);
                continue;
            }
            $wechatMenu[$fKey]['name'] = $fmenu['name'];
            $SecodeList = $fmenu['second_menu'];
            foreach ($SecodeList as $sKey => $smenu) {
                $wechatMenu[$fKey]['sub_button'][$sKey] = $this->_getMenuData($smenu);
            }
        }
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($appid);

        $result = $app->menu->create($wechatMenu);
        if ($result['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 转换成微信自定义菜单接口所需的数据格式
     */
    private function _getMenuData($menu)
    {
        $menuData = array();
        switch ($menu['menu_type']) {
        case 1:
            if ($menu['news_type'] == 'text') {
                $menuData = array(
                    'type' => "click",
                    'name' => $menu['name'],
                    'key' => "text:".$menu['content'],
                );
            } elseif ($menu['news_type'] == 'news') {
                $menuData = array(
                    'type' => "click",
                    'name' => $menu['name'],
                    'key' => "news:".$menu['content']['media_id'],
                );
            } elseif ($menu['news_type'] == 'image') {
                $menuData = array(
                    'type' => "media_id",
                    'name' => $menu['name'],
                    'media_id' => $menu['content']['media_id'],
                );
            }
            // elseif($menu['news_type'] == 'card')
            // {
            //     $menuData = array(
            //         'type' => "click",
            //         'name' => $menu['name'],
            //         'key' => 'card:'.$menu['content']['card_id'],
            //     );
            // }
            break;
        case 2:
            $menuData = array(
                'type' => "view",
                'name' => $menu['name'],
                'url' => $menu['url'],
            );
            break;
        case 3:
            $menuData = [
                'type' => "miniprogram",
                'name' => $menu['name'],
                'url' => $menu['url'],
                'appid' => $menu['appid'],
                'pagepath' => $menu['pagepath'],
            ];
            break;
        case 4:
            $wxsys = $this->wxsys();
            $menuData = array(
                'type' => $menu['wxsys'],
                'key' => $wxsys[$menu['wxsys']],
                'name' => $menu['name'],
            );
            break;
        }
        return $menuData;
    }
}
