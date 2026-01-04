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

namespace GoodsBundle\Routes;

class ServiceApi
{
    // 0x53686f704578
    public static function register()
    {
        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', function ($api) {
            $api->group(['namespace' => 'GoodsBundle\Api\V1\Action', 'prefix' => 'service', 'middleware' => ['servicesign']], function ($api) {
                $api->post('/goods/category/list', ['name' => '获取分类列表', 'as' => 'service.goods.category.list', 'uses' => 'ItemsCategory@getCategory']);
                $api->get('/goods/category/{company_id}/{category_id}', ['name' => '获取单条分类数据', 'as' => 'service.goods.category.get', 'uses' => 'ItemsCategory@getCategoryInfo']);
                $api->post('/goods/category', ['name' => '添加分类', 'as' => 'service.goods.category.create', 'uses' => 'ItemsCategory@createCategory']);
                $api->delete('/goods/category/{category_id}', ['name' => '删除分类', 'as' => 'service.goods.category.delete', 'uses' => 'ItemsCategory@deleteCategory']);
                $api->put('/goods/category/{company_id}/{category_id}', ['name' => '更新单条分类信息', 'as' => 'service.goods.category.update', 'uses' => 'ItemsCategory@updateCategory']);
            });
        });
    }
}
